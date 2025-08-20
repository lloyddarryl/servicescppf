import React, { useState, useEffect, useCallback } from 'react';
import Header from '../../components/Header';
import { rendezVousService } from '../../services/rendezVousService';
import './PriseRendezVous.css';

const PriseRendezVous = () => {
  // États principaux
  const [activeTab, setActiveTab] = useState('nouveau');
  const [pageInfo, setPageInfo] = useState(null);
  const [historique, setHistorique] = useState([]);
  const [loading, setLoading] = useState(false);
  const [notification, setNotification] = useState(null);

  // ✅ Fonction de debug améliorée
  const debugDate = (date) => {
    console.log('🧪 [COMPONENT] DEBUG Date sélectionnée:', {
      date_string: date,
      date_object: new Date(date + 'T00:00:00'),
      jour_semaine: new Date(date + 'T00:00:00').getDay(),
      jour_nom: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'][new Date(date + 'T00:00:00').getDay()],
      est_weekend: new Date(date + 'T00:00:00').getDay() === 0 || new Date(date + 'T00:00:00').getDay() === 6,
      est_jour_ouvrable: estJourOuvrable(date),
      date_min_component: getDateMin(),
      date_max_component: getDateMax()
    });

    // Test du service
    rendezVousService.utils.testDate(date);
  };


  // États pour le formulaire
  const [formData, setFormData] = useState({
    date_demandee: '',
    heure_demandee: '',
    motif: '',
    motif_autre: '',
    commentaires: ''
  });
  const [formErrors, setFormErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);
  const [creneauxDisponibles, setCreneauxDisponibles] = useState([]);
  const [loadingCreneaux, setLoadingCreneaux] = useState(false);

  // États pour les filtres historique
  const [filtres, setFiltres] = useState({
    statut: '',
    motif: ''
  });

  // États pour l'annulation
  const [showAnnulationModal, setShowAnnulationModal] = useState(false);
  const [demandeAAnnuler, setDemandeAAnnuler] = useState(null);
  const [motifAnnulation, setMotifAnnulation] = useState('');
  const [annulationEnCours, setAnnulationEnCours] = useState(false);

  // Pagination
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  });

  // Fonction pour afficher les notifications
  const afficherNotification = useCallback((message, type = 'info') => {
    setNotification({ message, type });
    setTimeout(() => setNotification(null), 5000);
  }, []);

  // Fonction pour obtenir la civilité selon le sexe et situation matrimoniale
  const getCivilite = (user) => {
    if (!user) return '';

    const sexe = user.sexe?.toUpperCase();
    const situationMatrimoniale = user.situation_matrimoniale?.toLowerCase();

    if (sexe === 'M' || sexe === 'MASCULIN') {
      return 'M.';
    }
    else if (sexe === 'F' || sexe === 'FEMININ') {
      if (['mariee', 'marie', 'marié', 'mariée'].includes(situationMatrimoniale)) {
        return 'Mme';
      } else {
        return 'Mlle';
      }
    }

    return '';
  };

  // Fonction pour obtenir l'identité complète avec civilité
  const getIdentiteComplete = (user) => {
    if (!user) return '';

    const civilite = getCivilite(user);
    const nomComplet = `${user.prenoms || ''} ${user.nom || ''}`.trim();

    return civilite ? `${civilite} ${nomComplet}` : nomComplet;
  };

  // Charger les informations de la page
  const chargerPageInfo = useCallback(async () => {
    try {
      setLoading(true);
      const response = await rendezVousService.getPageInfo();

      if (response.data.success) {
        setPageInfo(response.data);
      } else {
        throw new Error(response.data.message);
      }
    } catch (error) {
      console.error('Erreur chargement page RDV:', error);
      afficherNotification('Erreur lors du chargement de la page', 'error');
    } finally {
      setLoading(false);
    }
  }, [afficherNotification]);

  // ✅ CORRECTION : Charger créneaux avec meilleur debug
  const chargerCreneaux = useCallback(async (date) => {
    if (!date) {
      console.log('🚫 [COMPONENT] Aucune date fournie');
      setCreneauxDisponibles([]);
      return;
    }

    console.log('🔄 [COMPONENT] Début chargement créneaux pour:', date);

    // Vérifier si c'est un jour ouvrable côté client d'abord
    if (!estJourOuvrable(date)) {
      console.log('❌ [COMPONENT] Weekend détecté côté client');
      setCreneauxDisponibles([]);
      afficherNotification('Les rendez-vous ne sont disponibles que du lundi au vendredi', 'warning');
      return;
    }

    try {
      setLoadingCreneaux(true);
      console.log('📡 [COMPONENT] Appel API en cours...');

      const response = await rendezVousService.getCreneauxDisponibles(date);

      console.log('📨 [COMPONENT] Réponse reçue:', {
        success: response.data.success,
        message: response.data.message,
        nombre_creneaux: response.data.creneaux?.length || 0,
        creneaux: response.data.creneaux
      });

      if (response.data.success && response.data.creneaux) {
        setCreneauxDisponibles(response.data.creneaux);

        if (response.data.creneaux.length === 0) {
          afficherNotification('Aucun créneau disponible pour cette date', 'warning');
        } else {
          console.log('✅ [COMPONENT] Créneaux chargés avec succès:', response.data.creneaux);
          afficherNotification(`${response.data.creneaux.length} créneaux disponibles`, 'success');
        }
      } else {
        setCreneauxDisponibles([]);
        afficherNotification(response.data.message || 'Aucun créneau disponible', 'warning');
      }
    } catch (error) {
      console.error('💥 [COMPONENT] Erreur chargement créneaux:', error);
      setCreneauxDisponibles([]);
      afficherNotification('Erreur lors du chargement des créneaux', 'error');
    } finally {
      setLoadingCreneaux(false);
    }
  }, [afficherNotification]);

  // Charger l'historique des demandes
  const chargerHistorique = useCallback(async (page = 1) => {
    try {
      setLoading(true);

      const cleanFilters = {};
      if (filtres.statut && filtres.statut !== '' && filtres.statut !== 'tous') {
        cleanFilters.statut = filtres.statut;
      }
      if (filtres.motif && filtres.motif !== '' && filtres.motif !== 'tous') {
        cleanFilters.motif = filtres.motif;
      }

      const params = {
        page: page.toString(),
        ...cleanFilters
      };

      const response = await rendezVousService.getHistorique(params);

      if (response.data.success) {
        setHistorique(response.data.demandes || []);
        if (response.data.pagination) {
          setPagination(response.data.pagination);
        }
      } else {
        throw new Error(response.data.message);
      }
    } catch (error) {
      console.error('Erreur chargement historique:', error);
      afficherNotification('Erreur lors du chargement de l\'historique', 'error');
    } finally {
      setLoading(false);
    }
  }, [filtres, afficherNotification]);

  // Charger les données initiales
  useEffect(() => {
    chargerPageInfo();
  }, [chargerPageInfo]);

  // Charger l'historique quand les filtres changent
  useEffect(() => {
    if (activeTab === 'historique') {
      chargerHistorique();
    }
  }, [chargerHistorique, activeTab]);

  // Charger les créneaux quand la date change
  useEffect(() => {
    if (formData.date_demandee) {
      chargerCreneaux(formData.date_demandee);
    }
  }, [formData.date_demandee, chargerCreneaux]);

  // ✅ CORRECTION : Gestion des changements avec debug
  const handleInputChange = (e) => {
    const { name, value } = e.target;

    console.log('🔄 [COMPONENT] Changement de champ:', { name, value });

    setFormData(prev => ({ ...prev, [name]: value }));

    // Effacer les erreurs
    if (formErrors[name]) {
      setFormErrors(prev => ({ ...prev, [name]: '' }));
    }

    // Debug et traitement spécial pour la date
    if (name === 'date_demandee' && value) {
      console.log('📅 [COMPONENT] Nouvelle date sélectionnée:', value);
      debugDate(value);

      // Réinitialiser l'heure
      if (formData.heure_demandee) {
        console.log('🔄 [COMPONENT] Réinitialisation de l\'heure');
        setFormData(prev => ({ ...prev, heure_demandee: '' }));
      }
    }
  };

  // Soumission du formulaire
  const soumettreFormulaire = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setFormErrors({});

    try {
      // ✅ CORRECTION : Nettoyer les données avant envoi
      const dataToSend = {
        date_demandee: formData.date_demandee,
        heure_demandee: formData.heure_demandee,
        motif: formData.motif,
        motif_autre: formData.motif === 'autre' ? formData.motif_autre || '' : '', // ✅ Toujours string
        commentaires: formData.commentaires || '' // ✅ Toujours string
      };

      console.log('📤 [COMPONENT] Données à envoyer:', dataToSend);

      // Validation côté client
      const validation = rendezVousService.utils.validerFormulaire(dataToSend);
      if (!validation.isValid) {
        setFormErrors(validation.errors);
        setSubmitting(false);
        return;
      }

      const response = await rendezVousService.creerDemande(dataToSend);

      if (response.data.success) {
        afficherNotification(response.data.message, 'success');

        // Réinitialiser le formulaire
        setFormData({
          date_demandee: '',
          heure_demandee: '',
          motif: '',
          motif_autre: '',
          commentaires: ''
        });
        setCreneauxDisponibles([]);

        // Recharger les informations de la page et basculer vers l'historique
        await chargerPageInfo();
        setActiveTab('historique');
      } else {
        if (response.data.errors) {
          setFormErrors(response.data.errors);
        } else {
          throw new Error(response.data.message);
        }
      }
    } catch (error) {
      console.error('💥 [COMPONENT] Erreur soumission:', error);
      afficherNotification(`Erreur lors de la soumission: ${error.message}`, 'error');
    } finally {
      setSubmitting(false);
    }
  };

  // Confirmer l'annulation
  const confirmerAnnulation = (demande) => {
    setDemandeAAnnuler(demande);
    setShowAnnulationModal(true);
    setMotifAnnulation('');
  };

  // Exécuter l'annulation
  const executerAnnulation = async () => {
    if (!demandeAAnnuler) return;

    setAnnulationEnCours(true);

    try {
      const response = await rendezVousService.annuler(demandeAAnnuler.id, motifAnnulation);

      if (response.data.success) {
        afficherNotification(response.data.message, 'success');

        // Fermer le modal
        setShowAnnulationModal(false);
        setDemandeAAnnuler(null);
        setMotifAnnulation('');

        // Recharger l'historique et les infos de page
        await chargerHistorique();
        await chargerPageInfo();
      } else {
        throw new Error(response.data.message);
      }
    } catch (error) {
      console.error('Erreur annulation:', error);
      afficherNotification(`Erreur lors de l'annulation: ${error.message}`, 'error');
    } finally {
      setAnnulationEnCours(false);
    }
  };

  // ✅ CORRECTION : Fonction pour obtenir la date minimale (48h à l'avance)
  const getDateMin = () => {
    const maintenant = new Date();
    const dans48h = new Date(maintenant.getTime() + (48 * 60 * 60 * 1000));
    dans48h.setHours(0, 0, 0, 0); // S'assurer qu'on a une date complète
    return dans48h.toISOString().split('T')[0];
  };

  // Obtenir la date maximale (1 mois à l'avance)
  const getDateMax = () => {
    const dans1mois = new Date();
    dans1mois.setMonth(dans1mois.getMonth() + 1);
    return dans1mois.toISOString().split('T')[0];
  };

  // Vérifier si une date est un jour ouvrable
  const estJourOuvrable = (dateString) => {
    const date = new Date(dateString);
    const jour = date.getDay();
    return jour >= 1 && jour <= 5; // Lundi (1) à Vendredi (5)
  };

  if (loading && !pageInfo) {
    return (
      <div className="prise-rdv">
        <Header />
        <div className="prise-rdv__loading">
          <div className="prise-rdv__spinner"></div>
          <p>Chargement...</p>
        </div>
      </div>
    );
  }



  return (
    <div className="prise-rdv">
      <Header />

      {/* Notification */}
      {notification && (
        <div className={`prise-rdv__notification prise-rdv__notification--${notification.type}`}>
          <div className="prise-rdv__notification-content">
            <span className="prise-rdv__notification-icon">
              {notification.type === 'success' ? '✓' : notification.type === 'error' ? '✕' : notification.type === 'warning' ? '!' : 'i'}
            </span>
            <span className="prise-rdv__notification-message">
              {notification.message}
            </span>
            <button
              className="prise-rdv__notification-close"
              onClick={() => setNotification(null)}
            >
              ✕
            </button>
          </div>
        </div>
      )}

      <main className="prise-rdv__main">
        <div className="prise-rdv__container">

          {/* Section Header */}
          {pageInfo?.user_info && (
            <div className="prise-rdv__header">
              <div className="prise-rdv__header-content">
                <div className="prise-rdv__title-section">
                  <h1 className="prise-rdv__title">Prise de Rendez-vous - Agent Actif</h1>
                  <div className="prise-rdv__user-welcome">
                    Bienvenue {getIdentiteComplete(pageInfo.user_info)}
                  </div>
                  <p className="prise-rdv__subtitle">
                    Gérez vos demandes de rendez-vous et planifiez vos rencontres avec nos conseillers
                  </p>
                </div>
                <div className="prise-rdv__header-actions">
                  <button
                    onClick={() => window.location.href = '/dashboard'}
                    className="prise-rdv__dashboard-btn"
                    title="Retour au tableau de bord"
                  >
                    ← Retour au tableau de bord
                  </button>
                </div>
              </div>
            </div>
          )}

          {/* Navigation par onglets */}
          <div className="prise-rdv__navigation">
            <div className="prise-rdv__tabs">
              <button
                className={`prise-rdv__tab ${activeTab === 'nouveau' ? 'prise-rdv__tab--active' : ''}`}
                onClick={() => setActiveTab('nouveau')}
              >
                Nouveau rendez-vous
              </button>
              <button
                className={`prise-rdv__tab ${activeTab === 'vue-ensemble' ? 'prise-rdv__tab--active' : ''}`}
                onClick={() => setActiveTab('vue-ensemble')}
              >
                Vue d'ensemble
              </button>
              <button
                className={`prise-rdv__tab ${activeTab === 'historique' ? 'prise-rdv__tab--active' : ''}`}
                onClick={() => setActiveTab('historique')}
              >
                Historique
                {pageInfo?.statistiques?.total_demandes > 0 && (
                  <span className="prise-rdv__tab-count">
                    {pageInfo.statistiques.total_demandes}
                  </span>
                )}
              </button>
            </div>
          </div>

          {/* Contenu des onglets */}
          <div className="prise-rdv__content">

            {/* Vue d'ensemble */}
            {activeTab === 'vue-ensemble' && (
              <>
                {/* Statistiques */}
                {pageInfo?.statistiques && (
                  <div className="prise-rdv__stats-section">
                    <div className="prise-rdv__stats">
                      <div className="prise-rdv__stat-card prise-rdv__stat-card--total">
                        <div className="prise-rdv__stat-header">
                          <div className="prise-rdv__stat-icon">📊</div>
                          <div className="prise-rdv__stat-title">Total demandes</div>
                        </div>
                        <div className="prise-rdv__stat-value">{pageInfo.statistiques.total_demandes || 0}</div>
                      </div>
                      <div className="prise-rdv__stat-card prise-rdv__stat-card--pending">
                        <div className="prise-rdv__stat-header">
                          <div className="prise-rdv__stat-icon">⏳</div>
                          <div className="prise-rdv__stat-title">En attente</div>
                        </div>
                        <div className="prise-rdv__stat-value">{pageInfo.statistiques.en_attente || 0}</div>
                      </div>
                      <div className="prise-rdv__stat-card prise-rdv__stat-card--accepted">
                        <div className="prise-rdv__stat-header">
                          <div className="prise-rdv__stat-icon">✅</div>
                          <div className="prise-rdv__stat-title">Acceptés</div>
                        </div>
                        <div className="prise-rdv__stat-value">{pageInfo.statistiques.acceptees || 0}</div>
                      </div>
                      <div className="prise-rdv__stat-card prise-rdv__stat-card--month">
                        <div className="prise-rdv__stat-header">
                          <div className="prise-rdv__stat-icon">📅</div>
                          <div className="prise-rdv__stat-title">Ce mois</div>
                        </div>
                        <div className="prise-rdv__stat-value">{pageInfo.statistiques.ce_mois || 0}</div>
                      </div>
                    </div>
                  </div>
                )}

                {/* Prochains RDV confirmés */}
                {pageInfo?.prochains_rdv && pageInfo.prochains_rdv.length > 0 && (
                  <div className="prise-rdv__prochains-section">
                    <h2 className="prise-rdv__section-title">Vos prochains rendez-vous</h2>
                    <div className="prise-rdv__prochains-list">
                      {pageInfo.prochains_rdv.map(rdv => (
                        <div key={rdv.id} className="prise-rdv__prochain-item">
                          <div className="prise-rdv__prochain-icon">
                            {rendezVousService.utils.getIconeMotif(rdv.motif)}
                          </div>
                          <div className="prise-rdv__prochain-content">
                            <h3 className="prise-rdv__prochain-motif">{rdv.motif_complet}</h3>
                            <p className="prise-rdv__prochain-date">
                              {new Date(rdv.date_rdv_confirme).toLocaleDateString('fr-FR', {
                                weekday: 'long',
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                              })}
                            </p>
                            {rdv.lieu_rdv && (
                              <p className="prise-rdv__prochain-lieu">📍 {rdv.lieu_rdv}</p>
                            )}
                          </div>
                          <div className="prise-rdv__prochain-delai">
                            {rendezVousService.utils.getDelaiRendezVous(rdv.date_rdv_confirme)}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </>
            )}

            {/* Formulaire nouveau rendez-vous */}
            {activeTab === 'nouveau' && (
              <div className="prise-rdv__tab-content-area">
                <div className="prise-rdv__form-header">
                  <h2 className="prise-rdv__form-title">Demander un nouveau rendez-vous</h2>
                  <p className="prise-rdv__form-description">
                    Choisissez une date et heure qui vous conviennent. Créneaux disponibles du lundi au vendredi de 9h à 16h.
                  </p>
                </div>

                <form onSubmit={soumettreFormulaire} className="prise-rdv__form">

                  {/* Date souhaitée */}
                  <div className="prise-rdv__form-group">
                    <label className="prise-rdv__label">Date souhaitée *</label>
                    <input
                      type="date"
                      name="date_demandee"
                      value={formData.date_demandee}
                      onChange={handleInputChange}
                      min={getDateMin()}
                      max={getDateMax()}
                      className={`prise-rdv__input ${formErrors.date_demandee ? 'prise-rdv__input--error' : ''}`}
                    />
                    {formErrors.date_demandee && (
                      <span className="prise-rdv__error">{formErrors.date_demandee}</span>
                    )}
                    {formData.date_demandee && !estJourOuvrable(formData.date_demandee) && (
                      <span className="prise-rdv__warning">
                        Les rendez-vous ne sont disponibles que du lundi au vendredi
                      </span>
                    )}
                  </div>

                  {/* Heure souhaitée */}
                  <div className="prise-rdv__form-group">
                    <label className="prise-rdv__label">
                      Heure souhaitée *
                      {loadingCreneaux && (
                        <span className="prise-rdv__loading-text">Chargement des créneaux...</span>
                      )}
                    </label>
                    <select
                      name="heure_demandee"
                      value={formData.heure_demandee}
                      onChange={handleInputChange}
                      disabled={!formData.date_demandee || !estJourOuvrable(formData.date_demandee) || loadingCreneaux}
                      className={`prise-rdv__select ${formErrors.heure_demandee ? 'prise-rdv__select--error' : ''}`}
                    >
                      <option value="">Sélectionnez une heure</option>
                      {creneauxDisponibles.map(creneau => (
                        <option key={creneau} value={creneau}>
                          {creneau}
                        </option>
                      ))}
                    </select>
                    {formErrors.heure_demandee && (
                      <span className="prise-rdv__error">{formErrors.heure_demandee}</span>
                    )}
                    {formData.date_demandee && !estJourOuvrable(formData.date_demandee) && (
                      <span className="prise-rdv__warning">
                        Les rendez-vous ne sont disponibles que du lundi au vendredi
                      </span>
                    )}
                    {formData.date_demandee && estJourOuvrable(formData.date_demandee) && creneauxDisponibles.length === 0 && !loadingCreneaux && (
                      <span className="prise-rdv__warning">
                        Aucun créneau disponible pour cette date. Essayez une autre date.
                      </span>
                    )}
                    {creneauxDisponibles.length > 0 && (
                      <div className="prise-rdv__motif-info">
                        <span className="prise-rdv__motif-description">
                          {creneauxDisponibles.length} créneau{creneauxDisponibles.length > 1 ? 'x' : ''} disponible{creneauxDisponibles.length > 1 ? 's' : ''} (créneaux de 30 minutes entre 9h et 16h)
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Motif du rendez-vous */}
                  <div className="prise-rdv__form-group">
                    <label className="prise-rdv__label">Motif du rendez-vous *</label>
                    <select
                      name="motif"
                      value={formData.motif}
                      onChange={handleInputChange}
                      className={`prise-rdv__select ${formErrors.motif ? 'prise-rdv__select--error' : ''}`}
                    >
                      <option value="">Sélectionnez un motif</option>
                      {pageInfo?.motifs && Object.entries(pageInfo.motifs).map(([key, motif]) => (
                        <option key={key} value={key}>
                          {motif.icon} {motif.nom}
                        </option>
                      ))}
                    </select>
                    {formErrors.motif && (
                      <span className="prise-rdv__error">{formErrors.motif}</span>
                    )}

                    {/* Description du motif sélectionné */}
                    {formData.motif && pageInfo?.motifs?.[formData.motif] && (
                      <div className="prise-rdv__motif-info">
                        <span className="prise-rdv__motif-description">
                          {pageInfo.motifs[formData.motif].description}
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Motif autre (si sélectionné) */}
                  {formData.motif === 'autre' && (
                    <div className="prise-rdv__form-group">
                      <label className="prise-rdv__label">Précisez votre motif *</label>
                      <input
                        type="text"
                        name="motif_autre"
                        value={formData.motif_autre}
                        onChange={handleInputChange}
                        className={`prise-rdv__input ${formErrors.motif_autre ? 'prise-rdv__input--error' : ''}`}
                        placeholder="Décrivez brièvement votre demande..."
                        maxLength={255}
                      />
                      {formErrors.motif_autre && (
                        <span className="prise-rdv__error">{formErrors.motif_autre}</span>
                      )}
                    </div>
                  )}

                  {/* Commentaires */}
                  <div className="prise-rdv__form-group">
                    <label className="prise-rdv__label">
                      Commentaires supplémentaires
                      <span className="prise-rdv__char-count">
                        {formData.commentaires.length}/1000
                      </span>
                    </label>
                    <textarea
                      name="commentaires"
                      value={formData.commentaires}
                      onChange={handleInputChange}
                      className={`prise-rdv__textarea ${formErrors.commentaires ? 'prise-rdv__textarea--error' : ''}`}
                      placeholder="Ajoutez des informations complémentaires si nécessaire..."
                      rows={4}
                      maxLength={1000}
                    />
                    {formErrors.commentaires && (
                      <span className="prise-rdv__error">{formErrors.commentaires}</span>
                    )}
                  </div>

                  {/* Actions */}
                  <div className="prise-rdv__form-actions">
                    <button
                      type="submit"
                      disabled={submitting || !formData.date_demandee || !formData.heure_demandee || !formData.motif}
                      className={`prise-rdv__submit-btn ${submitting ? 'prise-rdv__submit-btn--loading' : ''}`}
                    >
                      {submitting ? (
                        <>
                          <span className="prise-rdv__spinner"></span>
                          Soumission...
                        </>
                      ) : (
                        <>
                          <span className="prise-rdv__submit-icon">Soumettre</span>
                        </>
                      )}
                    </button>
                  </div>
                </form>
              </div>
            )}

            {/* Historique des demandes */}
            {activeTab === 'historique' && (
              <div className="prise-rdv__tab-content-area">

                {/* Filtres */}
                <div className="prise-rdv__filtres">
                  <div className="prise-rdv__filtre-group">
                    <label className="prise-rdv__filtre-label">Statut:</label>
                    <select
                      value={filtres.statut}
                      onChange={(e) => setFiltres(prev => ({ ...prev, statut: e.target.value }))}
                      className="prise-rdv__filtre-select"
                    >
                      <option value="">Tous</option>
                      {pageInfo?.statuts && Object.entries(pageInfo.statuts).map(([key, statut]) => (
                        <option key={key} value={key}>{statut.nom}</option>
                      ))}
                    </select>
                  </div>

                  <div className="prise-rdv__filtre-group">
                    <label className="prise-rdv__filtre-label">Motif:</label>
                    <select
                      value={filtres.motif}
                      onChange={(e) => setFiltres(prev => ({ ...prev, motif: e.target.value }))}
                      className="prise-rdv__filtre-select"
                    >
                      <option value="">Tous</option>
                      {pageInfo?.motifs && Object.entries(pageInfo.motifs).map(([key, motif]) => (
                        <option key={key} value={key}>{motif.nom}</option>
                      ))}
                    </select>
                  </div>

                  <button
                    onClick={() => chargerHistorique()}
                    className="prise-rdv__refresh-btn"
                    disabled={loading}
                  >
                    🔄 Actualiser
                  </button>
                </div>

                {/* Liste */}
                {loading ? (
                  <div className="prise-rdv__loading">
                    <div className="prise-rdv__spinner"></div>
                    <p>Chargement de l'historique...</p>
                  </div>
                ) : historique.length === 0 ? (
                  <div className="prise-rdv__empty">
                    <div className="prise-rdv__empty-icon">RDV</div>
                    <h3>Aucune demande de rendez-vous</h3>
                    <p>Vous n'avez pas encore fait de demande de rendez-vous</p>
                    <button
                      onClick={() => setActiveTab('nouveau')}
                      className="prise-rdv__empty-btn"
                    >
                      Faire une demande
                    </button>
                  </div>
                ) : (
                  <div className="prise-rdv__liste">
                    {historique.map(demande => (
                      <div key={demande.id} className="prise-rdv__card">

                        {/* En-tête de carte */}
                        <div className="prise-rdv__card-header">
                          <div className="prise-rdv__card-info">
                            <h3 className="prise-rdv__card-title">
                              {rendezVousService.utils.getIconeMotif(demande.motif)} {demande.motif_complet}
                            </h3>
                            <p className="prise-rdv__card-numero">
                              N° {demande.numero_demande}
                            </p>
                          </div>
                          <div className="prise-rdv__card-badges">
                            <span
                              className="prise-rdv__statut-badge"
                              style={{ backgroundColor: rendezVousService.utils.getCouleurStatut(demande.statut) }}
                            >
                              {demande.statut_info?.icon} {demande.statut_info?.nom}
                            </span>
                          </div>
                        </div>

                        {/* Corps de carte */}
                        <div className="prise-rdv__card-body">
                          <div className="prise-rdv__card-details">
                            <div className="prise-rdv__card-detail">
                              <span className="prise-rdv__detail-label">Date demandée:</span>
                              <span className="prise-rdv__detail-value">
                                {demande.date_heure_formatee || 'Date non disponible'}
                              </span>
                            </div>

                            {/* Debug temporaire - à retirer après test */}
                            <div style={{ fontSize: '10px', color: '#666' }}>
                              Debug: {JSON.stringify({
                                date_heure_formatee: demande.date_heure_formatee,
                                date_demandee: demande.date_demandee,
                                heure_demandee: demande.heure_demandee
                              })}
                            </div>

                            {demande.date_rdv_confirme && (
                              <div className="prise-rdv__card-detail prise-rdv__card-detail--confirmed">
                                <span className="prise-rdv__detail-label">✅ Date confirmée:</span>
                                <span className="prise-rdv__detail-value">{demande.date_rdv_confirme}</span>
                              </div>
                            )}

                            {demande.lieu_rdv && (
                              <div className="prise-rdv__card-detail">
                                <span className="prise-rdv__detail-label">📍 Lieu:</span>
                                <span className="prise-rdv__detail-value">{demande.lieu_rdv}</span>
                              </div>
                            )}
                          </div>

                          {demande.commentaires && (
                            <div className="prise-rdv__card-commentaires">
                              <strong>Commentaires:</strong>
                              <p>{demande.commentaires.length > 100
                                ? `${demande.commentaires.substring(0, 100)}...`
                                : demande.commentaires
                              }</p>
                            </div>
                          )}

                          {demande.reponse_admin && (
                            <div className="prise-rdv__card-reponse">
                              <strong>💬 Réponse de l'administration:</strong>
                              <p>{demande.reponse_admin}</p>
                            </div>
                          )}
                        </div>

                        {/* Pied de carte */}
                        <div className="prise-rdv__card-footer">
                          <div className="prise-rdv__card-meta">
                            <span className="prise-rdv__card-date">
                              Demandé le {demande.date_soumission}
                            </span>
                            <span className="prise-rdv__card-temps">
                              {demande.temps_ecoule}
                            </span>
                          </div>

                          <div className="prise-rdv__card-actions">
                            {demande.peut_modifier && (
                              <button
                                onClick={() => confirmerAnnulation(demande)}
                                className="prise-rdv__annuler-btn"
                                title="Annuler cette demande"
                              >
                                Annuler
                              </button>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}

                    {/* Pagination */}
                    {pagination.last_page > 1 && (
                      <div className="prise-rdv__pagination">
                        {Array.from({ length: pagination.last_page }, (_, i) => (
                          <button
                            key={i + 1}
                            onClick={() => chargerHistorique(i + 1)}
                            className={`prise-rdv__page-btn ${pagination.current_page === i + 1 ? 'prise-rdv__page-btn--active' : ''
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
          </div>

          {/* Modal d'annulation */}
          {showAnnulationModal && (
            <div className="prise-rdv__modal-overlay">
              <div className="prise-rdv__modal">
                <div className="prise-rdv__modal-header">
                  <h3 className="prise-rdv__modal-title">
                    Confirmer l'annulation
                  </h3>
                  <button
                    className="prise-rdv__modal-close"
                    onClick={() => setShowAnnulationModal(false)}
                    disabled={annulationEnCours}
                  >
                    ✕
                  </button>
                </div>

                <div className="prise-rdv__modal-body">
                  <p className="prise-rdv__modal-warning">
                    Êtes-vous sûr de vouloir annuler votre demande de rendez-vous
                    <strong> N° {demandeAAnnuler?.numero_demande}</strong> ?
                  </p>

                  <div className="prise-rdv__modal-info">
                    <p><strong>Motif :</strong> {demandeAAnnuler?.motif_complet}</p>
                    <p><strong>Date demandée :</strong> {demandeAAnnuler?.date_heure_formatee}</p>
                  </div>

                  <div className="prise-rdv__form-group">
                    <label className="prise-rdv__label">
                      Motif d'annulation (optionnel)
                    </label>
                    <textarea
                      value={motifAnnulation}
                      onChange={(e) => setMotifAnnulation(e.target.value)}
                      className="prise-rdv__textarea"
                      placeholder="Expliquez pourquoi vous annulez cette demande..."
                      rows={3}
                      maxLength={500}
                      disabled={annulationEnCours}
                    />
                    <div className="prise-rdv__char-count">
                      {motifAnnulation.length}/500
                    </div>
                  </div>

                  <div className="prise-rdv__modal-warning-text">
                    Cette action est irréversible. Une notification sera envoyée à l'administration.
                  </div>
                </div>

                <div className="prise-rdv__modal-actions">
                  <button
                    className="prise-rdv__modal-btn prise-rdv__modal-btn--secondary"
                    onClick={() => setShowAnnulationModal(false)}
                    disabled={annulationEnCours}
                  >
                    Annuler
                  </button>
                  <button
                    className="prise-rdv__modal-btn prise-rdv__modal-btn--danger"
                    onClick={executerAnnulation}
                    disabled={annulationEnCours}
                  >
                    {annulationEnCours ? (
                      <>
                        <span className="prise-rdv__spinner"></span>
                        Annulation...
                      </>
                    ) : (
                      <>
                        🚫 Confirmer l'annulation
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

export default PriseRendezVous;