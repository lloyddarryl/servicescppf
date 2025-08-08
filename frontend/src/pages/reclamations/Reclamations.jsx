import React, { useState, useEffect, useCallback } from 'react';
import Header from '../../components/Header';
import { apiCall } from '../../services/urlHelper';
import './Reclamations.css';

const Reclamations = () => {
  // États principaux
  const [activeTab, setActiveTab] = useState('nouvelle');
  const [reclamations, setReclamations] = useState([]);
  const [typesReclamations, setTypesReclamations] = useState({});
  const [loading, setLoading] = useState(false);
  const [statistiques, setStatistiques] = useState({});
  const [notification, setNotification] = useState(null);

  // États pour le formulaire de nouvelle réclamation
  const [formData, setFormData] = useState({
    type_reclamation: '',
    sujet_personnalise: '',
    description: '',
    priorite: 'normale'
  });
  const [selectedFiles, setSelectedFiles] = useState([]);
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  // États pour les filtres
  const [filtres, setFiltres] = useState({
    statut: '',
    type: ''
  });

  // États pour la suppression
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [reclamationASupprimer, setReclamationASupprimer] = useState(null);
  const [motifSuppression, setMotifSuppression] = useState('');
  const [deletingReclamation, setDeletingReclamation] = useState(false);

  // Pagination
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });

  // Réclamation sélectionnée pour les détails
  const [reclamationSelectionnee, setReclamationSelectionnee] = useState(null);

  // Fonction pour charger les types de réclamations (memoized)
  const chargerTypesReclamations = useCallback(async () => {
    try {
      const response = await apiCall('/reclamations/types');
      const data = await response.json();
      if (data.success) {
        setTypesReclamations(data.types_reclamations);
      }
    } catch (error) {
      console.error('Erreur chargement types:', error);
      afficherNotification('Erreur lors du chargement des types de réclamations', 'error');
    }
  }, []);

  // Fonction pour afficher les notifications (memoized)
  const afficherNotification = useCallback((message, type = 'info') => {
    setNotification({ message, type });
    setTimeout(() => setNotification(null), 5000);
  }, []);

  // Fonction pour charger les réclamations (memoized)
  const chargerReclamations = useCallback(async (page = 1) => {
    setLoading(true);
    try {
      const params = new URLSearchParams({
        page: page.toString(),
        ...filtres
      });

      const response = await apiCall(`/reclamations?${params}`);
      const data = await response.json();

      if (data.success) {
        setReclamations(data.reclamations);
        setPagination(data.pagination);
        setStatistiques(data.statistiques);
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Erreur chargement réclamations:', error);
      afficherNotification('Erreur lors du chargement des réclamations', 'error');
    } finally {
      setLoading(false);
    }
  }, [filtres, afficherNotification]);

  // Charger les données initiales
  useEffect(() => {
    chargerTypesReclamations();
    chargerReclamations();
  }, [chargerTypesReclamations, chargerReclamations]);

  // Charger les réclamations quand les filtres changent
  useEffect(() => {
    chargerReclamations();
  }, [chargerReclamations]);

  const soumettreReclamation = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setFormErrors({});

    try {
      // Validation côté client
      const erreurs = {};
      if (!formData.type_reclamation) erreurs.type_reclamation = 'Le type est obligatoire';
      if (!formData.description.trim()) erreurs.description = 'La description est obligatoire';
      if (formData.description.length < 10) erreurs.description = 'La description doit contenir au moins 10 caractères';
      if (formData.description.length > 2000) erreurs.description = 'La description ne peut pas dépasser 2000 caractères';

      // Vérifier si des documents sont nécessaires
      const typeInfo = typesReclamations[formData.type_reclamation];
      if (typeInfo?.necessite_document && selectedFiles.length === 0) {
        erreurs.documents = 'Ce type de réclamation nécessite au moins un document';
      }

      if (Object.keys(erreurs).length > 0) {
        setFormErrors(erreurs);
        setSubmitting(false);
        return;
      }

      // Préparer les données pour l'envoi
      const formDataToSend = new FormData();
      formDataToSend.append('type_reclamation', formData.type_reclamation);
      formDataToSend.append('sujet_personnalise', formData.sujet_personnalise);
      formDataToSend.append('description', formData.description);
      formDataToSend.append('priorite', formData.priorite);

      // Ajouter les fichiers
      selectedFiles.forEach((file, index) => {
        formDataToSend.append(`documents[${index}]`, file);
      });

      const response = await fetch(
        `http://localhost:8000/api/${localStorage.getItem('user_type') === 'retraite' ? 'retraites' : 'actifs'}/reclamations`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
            'Accept': 'application/json'
          },
          body: formDataToSend
        }
      );

      const data = await response.json();

      if (data.success) {
        afficherNotification(
          `Réclamation soumise avec succès ! N° ${data.reclamation.numero_reclamation}`,
          'success'
        );
        
        // Réinitialiser le formulaire
        setFormData({
          type_reclamation: '',
          sujet_personnalise: '',
          description: '',
          priorite: 'normale'
        });
        setSelectedFiles([]);
        
        // Recharger les réclamations
        chargerReclamations();
        
        // Changer d'onglet
        setActiveTab('historique');
      } else {
        if (data.errors) {
          setFormErrors(data.errors);
        } else {
          throw new Error(data.message);
        }
      }
    } catch (error) {
      console.error('Erreur soumission:', error);
      afficherNotification('Erreur lors de la soumission de votre réclamation', 'error');
    } finally {
      setSubmitting(false);
    }
  };

  const confirmerSuppression = (reclamation) => {
    setReclamationASupprimer(reclamation);
    setShowDeleteModal(true);
    setMotifSuppression('');
  };

  const executerSuppression = async () => {
    if (!reclamationASupprimer) return;
    
    setDeletingReclamation(true);
    
    try {
      const userType = localStorage.getItem('user_type');
      const endpoint = userType === 'retraite' 
        ? `/retraites/reclamations/${reclamationASupprimer.id}` 
        : `/actifs/reclamations/${reclamationASupprimer.id}`;

      const response = await fetch(`http://localhost:8000/api${endpoint}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          motif: motifSuppression.trim()
        })
      });

      const data = await response.json();

      if (data.success) {
        afficherNotification(
          `Réclamation ${reclamationASupprimer.numero_reclamation} supprimée avec succès`,
          'success'
        );
        
        // Fermer le modal
        setShowDeleteModal(false);
        setReclamationASupprimer(null);
        setMotifSuppression('');
        
        // Si on est en détails et qu'on supprime cette réclamation, retourner à l'historique
        if (activeTab === 'details' && reclamationSelectionnee?.id === reclamationASupprimer.id) {
          setActiveTab('historique');
          setReclamationSelectionnee(null);
        }
        
        // Recharger les réclamations
        chargerReclamations();
        
      } else {
        throw new Error(data.message || 'Erreur lors de la suppression');
      }
    } catch (error) {
      console.error('Erreur suppression:', error);
      afficherNotification(
        error.message || 'Erreur lors de la suppression de la réclamation',
        'error'
      );
    } finally {
      setDeletingReclamation(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    
    // Effacer les erreurs au fur et à mesure
    if (formErrors[name]) {
      setFormErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const handleFileChange = (e) => {
    const files = Array.from(e.target.files);
    
    // Validation des fichiers
    const erreurs = [];
    const typesAutorises = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    const tailleMax = 5 * 1024 * 1024; // 5MB

    files.forEach(file => {
      const extension = file.name.split('.').pop().toLowerCase();
      if (!typesAutorises.includes(extension)) {
        erreurs.push(`${file.name}: Type de fichier non autorisé`);
      }
      if (file.size > tailleMax) {
        erreurs.push(`${file.name}: Fichier trop volumineux (max 5MB)`);
      }
    });

    if (erreurs.length > 0) {
      afficherNotification(erreurs.join('\n'), 'error');
      return;
    }

    setSelectedFiles(files);
    if (formErrors.documents) {
      setFormErrors(prev => ({ ...prev, documents: '' }));
    }
  };

  const supprimerFichier = (index) => {
    setSelectedFiles(prev => prev.filter((_, i) => i !== index));
  };

  const formatTaillefichier = (taille) => {
    if (taille < 1024) return `${taille} B`;
    if (taille < 1024 * 1024) return `${(taille / 1024).toFixed(1)} KB`;
    return `${(taille / (1024 * 1024)).toFixed(1)} MB`;
  };

  const getStatutBadge = (statut, couleur) => (
    <span 
      className="reclamations__statut-badge"
      style={{ backgroundColor: couleur }}
    >
      {statut}
    </span>
  );

  const getPrioriteBadge = (priorite, couleur) => (
    <span 
      className="reclamations__priorite-badge"
      style={{ backgroundColor: couleur }}
    >
      {priorite}
    </span>
  );

  return (
    <div className="reclamations">
      <Header />
      
      {/* Notification */}
      {notification && (
        <div className={`reclamations__notification reclamations__notification--${notification.type}`}>
          <div className="reclamations__notification-content">
            <span className="reclamations__notification-icon">
              {notification.type === 'success' ? '✅' : notification.type === 'error' ? '❌' : 'ℹ️'}
            </span>
            <span className="reclamations__notification-message">
              {notification.message}
            </span>
            <button 
              className="reclamations__notification-close"
              onClick={() => setNotification(null)}
            >
              ✕
            </button>
          </div>
        </div>
      )}

      <main className="reclamations__main">
        <div className="reclamations__container">
          
          {/* En-tête */}
          <div className="reclamations__header">
            <h1 className="reclamations__title">Réclamations</h1>
            <p className="reclamations__subtitle">
              Gérez vos réclamations et suivez leur traitement en temps réel
            </p>
          </div>

          {/* Statistiques */}
          <div className="reclamations__stats">
            <div className="reclamations__stat-card">
              <div className="reclamations__stat-icon">📊</div>
              <div className="reclamations__stat-content">
                <div className="reclamations__stat-value">{statistiques.total || 0}</div>
                <div className="reclamations__stat-label">Total</div>
              </div>
            </div>
            <div className="reclamations__stat-card">
              <div className="reclamations__stat-icon">⏳</div>
              <div className="reclamations__stat-content">
                <div className="reclamations__stat-value">{statistiques.en_attente || 0}</div>
                <div className="reclamations__stat-label">En attente</div>
              </div>
            </div>
            <div className="reclamations__stat-card">
              <div className="reclamations__stat-icon">🔄</div>
              <div className="reclamations__stat-content">
                <div className="reclamations__stat-value">{statistiques.en_cours || 0}</div>
                <div className="reclamations__stat-label">En cours</div>
              </div>
            </div>
            <div className="reclamations__stat-card">
              <div className="reclamations__stat-icon">✅</div>
              <div className="reclamations__stat-content">
                <div className="reclamations__stat-value">{statistiques.resolues || 0}</div>
                <div className="reclamations__stat-label">Résolues</div>
              </div>
            </div>
          </div>

          {/* Navigation par onglets */}
          <div className="reclamations__tabs">
            <button 
              className={`reclamations__tab ${activeTab === 'nouvelle' ? 'reclamations__tab--active' : ''}`}
              onClick={() => setActiveTab('nouvelle')}
            >
              <span className="reclamations__tab-icon">➕</span>
              Nouvelle réclamation
            </button>
            <button 
              className={`reclamations__tab ${activeTab === 'historique' ? 'reclamations__tab--active' : ''}`}
              onClick={() => setActiveTab('historique')}
            >
              <span className="reclamations__tab-icon">📋</span>
              Mes réclamations ({statistiques.total || 0})
            </button>
          </div>

          {/* Contenu des onglets */}
          <div className="reclamations__content">
            
            {/* Formulaire nouvelle réclamation */}
            {activeTab === 'nouvelle' && (
              <div className="reclamations__nouvelle">
                <div className="reclamations__form-header">
                  <h2 className="reclamations__form-title">Déposer une nouvelle réclamation</h2>
                  <p className="reclamations__form-description">
                    Décrivez votre problème de manière détaillée pour un traitement optimal
                  </p>
                </div>

                <form onSubmit={soumettreReclamation} className="reclamations__form">
                  
                  {/* Type de réclamation */}
                  <div className="reclamations__form-group">
                    <label className="reclamations__label">
                      Type de réclamation *
                    </label>
                    <select 
                      name="type_reclamation"
                      value={formData.type_reclamation}
                      onChange={handleInputChange}
                      className={`reclamations__select ${formErrors.type_reclamation ? 'reclamations__select--error' : ''}`}
                    >
                      <option value="">Sélectionnez un type</option>
                      {Object.entries(typesReclamations).map(([key, type]) => (
                        <option key={key} value={key}>
                          {type.nom}
                        </option>
                      ))}
                    </select>
                    {formErrors.type_reclamation && (
                      <span className="reclamations__error">{formErrors.type_reclamation}</span>
                    )}
                    
                    {/* Description du type sélectionné */}
                    {formData.type_reclamation && typesReclamations[formData.type_reclamation] && (
                      <div className="reclamations__type-info">
                        <span className="reclamations__type-description">
                          {typesReclamations[formData.type_reclamation].description}
                        </span>
                        {typesReclamations[formData.type_reclamation].necessite_document && (
                          <span className="reclamations__document-required">
                            📎 Document justificatif requis
                          </span>
                        )}
                      </div>
                    )}
                  </div>

                  {/* Sujet personnalisé (pour type "autre") */}
                  {formData.type_reclamation === 'autre' && (
                    <div className="reclamations__form-group">
                      <label className="reclamations__label">
                        Sujet de votre réclamation *
                      </label>
                      <input 
                        type="text"
                        name="sujet_personnalise"
                        value={formData.sujet_personnalise}
                        onChange={handleInputChange}
                        className="reclamations__input"
                        placeholder="Précisez le sujet de votre réclamation..."
                        maxLength={255}
                      />
                    </div>
                  )}

                  {/* Priorité */}
                  <div className="reclamations__form-group">
                    <label className="reclamations__label">
                      Priorité
                    </label>
                    <select 
                      name="priorite"
                      value={formData.priorite}
                      onChange={handleInputChange}
                      className="reclamations__select"
                    >
                      <option value="basse">Basse</option>
                      <option value="normale">Normale</option>
                      <option value="haute">Haute</option>
                      <option value="urgente">Urgente</option>
                    </select>
                  </div>

                  {/* Description */}
                  <div className="reclamations__form-group">
                    <label className="reclamations__label">
                      Description détaillée *
                      <span className="reclamations__char-count">
                        {formData.description.length}/2000
                      </span>
                    </label>
                    <textarea 
                      name="description"
                      value={formData.description}
                      onChange={handleInputChange}
                      className={`reclamations__textarea ${formErrors.description ? 'reclamations__textarea--error' : ''}`}
                      placeholder="Décrivez votre problème de manière détaillée : contexte, étapes effectuées, erreurs rencontrées..."
                      rows={6}
                      maxLength={2000}
                    />
                    {formErrors.description && (
                      <span className="reclamations__error">{formErrors.description}</span>
                    )}
                  </div>

                  {/* Documents */}
                  <div className="reclamations__form-group">
                    <label className="reclamations__label">
                      Documents justificatifs
                      {formData.type_reclamation && 
                       typesReclamations[formData.type_reclamation]?.necessite_document && (
                        <span className="reclamations__required"> *</span>
                      )}
                    </label>
                    
                    <div className="reclamations__file-upload">
                      <input 
                        type="file"
                        id="documents"
                        multiple
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                        onChange={handleFileChange}
                        className="reclamations__file-input"
                      />
                      <label htmlFor="documents" className="reclamations__file-label">
                        <span className="reclamations__file-icon">📎</span>
                        Choisir des fichiers
                        <span className="reclamations__file-hint">
                          PDF, DOC, DOCX, JPG, PNG (max 5MB chacun)
                        </span>
                      </label>
                    </div>

                    {formErrors.documents && (
                      <span className="reclamations__error">{formErrors.documents}</span>
                    )}

                    {/* Fichiers sélectionnés */}
                    {selectedFiles.length > 0 && (
                      <div className="reclamations__selected-files">
                        <h4 className="reclamations__files-title">Fichiers sélectionnés:</h4>
                        {selectedFiles.map((file, index) => (
                          <div key={index} className="reclamations__selected-file">
                            <span className="reclamations__file-info">
                              <span className="reclamations__file-name">{file.name}</span>
                              <span className="reclamations__file-size">
                                ({formatTaillefichier(file.size)})
                              </span>
                            </span>
                            <button 
                              type="button"
                              onClick={() => supprimerFichier(index)}
                              className="reclamations__file-remove"
                            >
                              ✕
                            </button>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>

                  {/* Actions */}
                  <div className="reclamations__form-actions">
                    <button 
                      type="submit"
                      disabled={submitting}
                      className={`reclamations__submit-btn ${submitting ? 'reclamations__submit-btn--loading' : ''}`}
                    >
                      {submitting ? (
                        <>
                          <span className="reclamations__spinner"></span>
                          Soumission...
                        </>
                      ) : (
                        <>
                          <span className="reclamations__submit-icon">📤</span>
                          Soumettre la réclamation
                        </>
                      )}
                    </button>
                  </div>
                </form>
              </div>
            )}

            {/* Liste des réclamations */}
            {activeTab === 'historique' && (
              <div className="reclamations__historique">
                
                {/* Filtres */}
                <div className="reclamations__filtres">
                  <div className="reclamations__filtre-group">
                    <label className="reclamations__filtre-label">Statut:</label>
                    <select 
                      value={filtres.statut}
                      onChange={(e) => setFiltres(prev => ({ ...prev, statut: e.target.value }))}
                      className="reclamations__filtre-select"
                    >
                      <option value="">Tous</option>
                      <option value="en_attente">En attente</option>
                      <option value="en_cours">En cours</option>
                      <option value="en_revision">En révision</option>
                      <option value="resolu">Résolu</option>
                      <option value="ferme">Fermé</option>
                      <option value="rejete">Rejeté</option>
                    </select>
                  </div>

                  <div className="reclamations__filtre-group">
                    <label className="reclamations__filtre-label">Type:</label>
                    <select 
                      value={filtres.type}
                      onChange={(e) => setFiltres(prev => ({ ...prev, type: e.target.value }))}
                      className="reclamations__filtre-select"
                    >
                      <option value="">Tous</option>
                      {Object.entries(typesReclamations).map(([key, type]) => (
                        <option key={key} value={key}>{type.nom}</option>
                      ))}
                    </select>
                  </div>

                  <button 
                    onClick={() => chargerReclamations()}
                    className="reclamations__refresh-btn"
                    disabled={loading}
                  >
                    🔄 Actualiser
                  </button>
                </div>

                {/* Liste */}
                {loading ? (
                  <div className="reclamations__loading">
                    <div className="reclamations__spinner"></div>
                    <p>Chargement des réclamations...</p>
                  </div>
                ) : reclamations.length === 0 ? (
                  <div className="reclamations__empty">
                    <div className="reclamations__empty-icon">📝</div>
                    <h3>Aucune réclamation</h3>
                    <p>Vous n'avez pas encore déposé de réclamation</p>
                    <button 
                      onClick={() => setActiveTab('nouvelle')}
                      className="reclamations__empty-btn"
                    >
                      Créer une réclamation
                    </button>
                  </div>
                ) : (
                  <div className="reclamations__liste">
                    {reclamations.map(reclamation => (
                      <div key={reclamation.id} className="reclamations__card">
                        
                        {/* En-tête de carte */}
                        <div className="reclamations__card-header">
                          <div className="reclamations__card-info">
                            <h3 className="reclamations__card-title">
                              {reclamation.type_reclamation_info?.nom}
                              {reclamation.sujet_personnalise && (
                                <span className="reclamations__sujet-personnalise">
                                  : {reclamation.sujet_personnalise}
                                </span>
                              )}
                            </h3>
                            <p className="reclamations__card-numero">
                              N° {reclamation.numero_reclamation}
                            </p>
                          </div>
                          <div className="reclamations__card-badges">
                            {getStatutBadge(reclamation.statut_libelle, reclamation.couleur_statut)}
                            {getPrioriteBadge(
                              reclamation.priorite_info.nom, 
                              reclamation.priorite_info.couleur
                            )}
                          </div>
                        </div>

                        {/* Corps de carte */}
                        <div className="reclamations__card-body">
                          <p className="reclamations__card-description">
                            {reclamation.description.length > 150 
                              ? `${reclamation.description.substring(0, 150)}...`
                              : reclamation.description
                            }
                          </p>
                          
                          {reclamation.documents && reclamation.documents.length > 0 && (
                            <div className="reclamations__card-documents">
                              <span className="reclamations__documents-count">
                                📎 {reclamation.documents.length} document(s) joint(s)
                              </span>
                            </div>
                          )}
                        </div>

                        {/* Pied de carte */}
                        <div className="reclamations__card-footer">
                          <div className="reclamations__card-meta">
                            <span className="reclamations__card-date">
                              {reclamation.date_soumission_formatee}
                            </span>
                            <span className="reclamations__card-temps">
                              {reclamation.temps_ecoule}
                            </span>
                          </div>
                          
                          <div className="reclamations__card-actions">
                            {reclamation.peut_supprimer && (
                              <button 
                                onClick={(e) => {
                                  e.stopPropagation();
                                  confirmerSuppression(reclamation);
                                }}
                                className="reclamations__delete-btn"
                                title="Supprimer cette réclamation"
                              >
                                🗑️
                              </button>
                            )}
                            <button 
                              onClick={() => {
                                setReclamationSelectionnee(reclamation);
                                setActiveTab('details');
                              }}
                              className="reclamations__card-btn"
                            >
                              Voir détails
                            </button>
                          </div>
                        </div>
                      </div>
                    ))}

                    {/* Pagination */}
                    {pagination.last_page > 1 && (
                      <div className="reclamations__pagination">
                        {Array.from({ length: pagination.last_page }, (_, i) => (
                          <button 
                            key={i + 1}
                            onClick={() => chargerReclamations(i + 1)}
                            className={`reclamations__page-btn ${
                              pagination.current_page === i + 1 ? 'reclamations__page-btn--active' : ''
                            }`}
                          >
                            {i + 1}
                          </button>
                        ))}
                      </div>
                    )}
                  </div>
                )}
              </div>
            )}

            {/* Détails d'une réclamation */}
            {activeTab === 'details' && reclamationSelectionnee && (
              <div className="reclamations__details">
                
                {/* En-tête détails */}
                <div className="reclamations__details-header">
                  <button 
                    onClick={() => setActiveTab('historique')}
                    className="reclamations__back-btn"
                  >
                    ← Retour
                  </button>
                  
                  <div className="reclamations__details-title">
                    <h2>Réclamation N° {reclamationSelectionnee.numero_reclamation}</h2>
                    <div className="reclamations__details-actions">
                      <div className="reclamations__details-badges">
                        {getStatutBadge(
                          reclamationSelectionnee.statut_libelle, 
                          reclamationSelectionnee.couleur_statut
                        )}
                        {getPrioriteBadge(
                          reclamationSelectionnee.priorite_info.nom,
                          reclamationSelectionnee.priorite_info.couleur
                        )}
                      </div>
                      {reclamationSelectionnee.peut_supprimer && (
                        <button 
                          onClick={() => confirmerSuppression(reclamationSelectionnee)}
                          className="reclamations__delete-btn reclamations__delete-btn--large"
                          title="Supprimer cette réclamation"
                        >
                          🗑️ Supprimer
                        </button>
                      )}
                    </div>
                  </div>
                </div>

                {/* Contenu détails */}
                <div className="reclamations__details-content">
                  
                  {/* Informations principales */}
                  <div className="reclamations__details-section">
                    <h3 className="reclamations__details-section-title">Informations</h3>
                    <div className="reclamations__details-grid">
                      <div className="reclamations__detail-item">
                        <label>Type:</label>
                        <span>{reclamationSelectionnee.type_reclamation_info?.nom}</span>
                      </div>
                      {reclamationSelectionnee.sujet_personnalise && (
                        <div className="reclamations__detail-item">
                          <label>Sujet:</label>
                          <span>{reclamationSelectionnee.sujet_personnalise}</span>
                        </div>
                      )}
                      <div className="reclamations__detail-item">
                        <label>Date:</label>
                        <span>{reclamationSelectionnee.date_soumission_formatee}</span>
                      </div>
                      <div className="reclamations__detail-item">
                        <label>Temps écoulé:</label>
                        <span>{reclamationSelectionnee.temps_ecoule}</span>
                      </div>
                    </div>
                  </div>

                  {/* Description */}
                  <div className="reclamations__details-section">
                    <h3 className="reclamations__details-section-title">Description</h3>
                    <div className="reclamations__description-box">
                      {reclamationSelectionnee.description}
                    </div>
                  </div>

                  {/* Documents */}
                  {reclamationSelectionnee.documents && reclamationSelectionnee.documents.length > 0 && (
                    <div className="reclamations__details-section">
                      <h3 className="reclamations__details-section-title">Documents joints</h3>
                      <div className="reclamations__documents-liste">
                        {reclamationSelectionnee.documents.map((document, index) => (
                          <div key={index} className="reclamations__document-item">
                            <span className="reclamations__document-icon">📄</span>
                            <div className="reclamations__document-info">
                              <span className="reclamations__document-name">
                                {document.nom_original}
                              </span>
                              <span className="reclamations__document-meta">
                                {formatTaillefichier(document.taille)} • 
                                {document.type.toUpperCase()} •
                                {new Date(document.date_upload).toLocaleDateString('fr-FR')}
                              </span>
                            </div>
                            <button 
                              onClick={() => {
                                window.open(
                                  `http://localhost:8000/api/${localStorage.getItem('user_type') === 'retraite' ? 'retraites' : 'actifs'}/reclamations/${reclamationSelectionnee.id}/documents/${index}`,
                                  '_blank'
                                );
                              }}
                              className="reclamations__document-download"
                            >
                              📥 Télécharger
                            </button>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Commentaires admin */}
                  {reclamationSelectionnee.commentaires_admin && (
                    <div className="reclamations__details-section">
                      <h3 className="reclamations__details-section-title">Commentaire de l'administration</h3>
                      <div className="reclamations__admin-comment">
                        {reclamationSelectionnee.commentaires_admin}
                      </div>
                    </div>
                  )}

                  {/* Historique des statuts */}
                  <div className="reclamations__details-section">
                    <h3 className="reclamations__details-section-title">Suivi de la réclamation</h3>
                    <div className="reclamations__timeline">
                      {reclamationSelectionnee.historique.map((etape, index) => (
                        <div key={etape.id} className="reclamations__timeline-item">
                          <div className="reclamations__timeline-marker"></div>
                          <div className="reclamations__timeline-content">
                            <div className="reclamations__timeline-header">
                              <span className="reclamations__timeline-statut">
                                {etape.nouveau_statut}
                              </span>
                              <span className="reclamations__timeline-date">
                                {etape.date}
                              </span>
                            </div>
                            {etape.commentaire && (
                              <p className="reclamations__timeline-commentaire">
                                {etape.commentaire}
                              </p>
                            )}
                            {etape.modifie_par && (
                              <span className="reclamations__timeline-auteur">
                                Par: {etape.modifie_par}
                              </span>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Modal de suppression */}
          {showDeleteModal && (
            <div className="reclamations__modal-overlay">
              <div className="reclamations__modal">
                <div className="reclamations__modal-header">
                  <h3 className="reclamations__modal-title">
                    ⚠️ Confirmer la suppression
                  </h3>
                  <button 
                    className="reclamations__modal-close"
                    onClick={() => setShowDeleteModal(false)}
                    disabled={deletingReclamation}
                  >
                    ✕
                  </button>
                </div>
                
                <div className="reclamations__modal-body">
                  <p className="reclamations__modal-warning">
                    Êtes-vous sûr de vouloir supprimer définitivement la réclamation 
                    <strong> N° {reclamationASupprimer?.numero_reclamation}</strong> ?
                  </p>
                  
                  <div className="reclamations__modal-info">
                    <p><strong>Type :</strong> {reclamationASupprimer?.type_reclamation_info?.nom}</p>
                    <p><strong>Date :</strong> {reclamationASupprimer?.date_soumission_formatee}</p>
                  </div>
                  
                  <div className="reclamations__form-group">
                    <label className="reclamations__label">
                      Motif de suppression (optionnel)
                    </label>
                    <textarea 
                      value={motifSuppression}
                      onChange={(e) => setMotifSuppression(e.target.value)}
                      className="reclamations__textarea"
                      placeholder="Expliquez pourquoi vous supprimez cette réclamation..."
                      rows={3}
                      maxLength={500}
                      disabled={deletingReclamation}
                    />
                    <div className="reclamations__char-count">
                      {motifSuppression.length}/500
                    </div>
                  </div>
                  
                  <div className="reclamations__modal-warning-text">
                    ⚠️ Cette action est irréversible. Une notification sera envoyée à l'administration.
                  </div>
                </div>
                
                <div className="reclamations__modal-actions">
                  <button 
                    className="reclamations__modal-btn reclamations__modal-btn--secondary"
                    onClick={() => setShowDeleteModal(false)}
                    disabled={deletingReclamation}
                  >
                    Annuler
                  </button>
                  <button 
                    className="reclamations__modal-btn reclamations__modal-btn--danger"
                    onClick={executerSuppression}
                    disabled={deletingReclamation}
                  >
                    {deletingReclamation ? (
                      <>
                        <span className="reclamations__spinner"></span>
                        Suppression...
                      </>
                    ) : (
                      <>
                        🗑️ Supprimer définitivement
                      </>
                    )}
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      </main>
    </div>
  );
};

export default Reclamations;