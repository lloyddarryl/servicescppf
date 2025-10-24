import React, { useState, useEffect } from 'react';
import AdminHeader from '../../components/AdminHeader';
import AdminNav from '../../components/AdminNav';
import { adminReclamationService } from '../../services/adminApi';
import StatistiquesReclamations from '../../components/StatistiquesReclamations';
import './AdminReclamations.css';

const AdminReclamations = () => {
  const [reclamations, setReclamations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedReclamation, setSelectedReclamation] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [filters, setFilters] = useState({
    statut: '',
    priorite: '',
    type_reclamation: '',
    search: ''
  });
  const [pagination, setPagination] = useState({
    current_page: 1,
    per_page: 15,
    total: 0
  });

  useEffect(() => {
    loadReclamations();
  }, [filters, pagination.current_page]);

  const loadReclamations = async () => {
    try {
      setLoading(true);
      const params = {
        ...filters,
        page: pagination.current_page,
        per_page: pagination.per_page
      };
      
      const response = await adminReclamationService.getAll(params);
      if (response.data.success) {
        setReclamations(response.data.data.data);
        setPagination({
          current_page: response.data.data.current_page,
          per_page: response.data.data.per_page,
          total: response.data.data.total,
          last_page: response.data.data.last_page
        });
      }
    } catch (error) {
      console.error('Erreur chargement réclamations:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleTraiter = async (reclamation) => {
  try {
    console.log('🔄 Chargement des détails:', reclamation.id);
    
    // ✅ Charger les détails complets
    const response = await adminReclamationService.getById(reclamation.id);
    
    if (response.data.success) {
      console.log('✅ Détails chargés:', response.data.data);
      console.log('📄 Documents:', response.data.data.documents_info);
      
      setSelectedReclamation(response.data.data);
      setShowModal(true);
    }
  } catch (error) {
    console.error('❌ Erreur:', error);
    alert('Erreur lors du chargement des détails');
  }
};

  const handleSupprimer = async (reclamation) => {
    if (!window.confirm(`Êtes-vous sûr de vouloir supprimer définitivement la réclamation #${reclamation.numero_reclamation} ?\n\nCette action est irréversible.`)) {
      return;
    }

    try {
      const response = await adminReclamationService.supprimer(reclamation.id);
      if (response.data.success) {
        alert('Réclamation supprimée avec succès');
        loadReclamations();
      }
    } catch (error) {
      console.error('Erreur suppression:', error);
      alert('Erreur lors de la suppression de la réclamation');
    }
  };

  const getStatusColor = (statut) => {
    const colors = {
      'en_attente': '#f59e0b',
      'en_cours': '#3b82f6',
      'en_revision': '#8b5cf6',
      'resolu': '#10b981',
      'ferme': '#6b7280',
      'rejete': '#ef4444'
    };
    return colors[statut] || '#6b7280';
  };

  const getPriorityColor = (priorite) => {
    const colors = {
      'basse': '#10b981',
      'normale': '#3b82f6',
      'haute': '#f59e0b',
      'urgente': '#ef4444'
    };
    return colors[priorite] || '#3b82f6';
  };

  return (
    <div className="admin-dashboard">
      <AdminHeader 
        title="Gestion des Réclamations" 
        breadcrumb="Administration - Réclamations"
      />
      <AdminNav />
      
       {/* Widget Statistiques Réclamations */}
          <StatistiquesReclamations showTitle={true} compact={false} />

      <main className="admin-main">
        <div className="dashboard-content">
          {/* Filtres */}
          <section className="filters-section">
            <h2>Filtres et recherche</h2>
            <div className="filters-grid">
              <input
                type="text"
                placeholder="Rechercher (numéro, nom, description)..."
                value={filters.search}
                onChange={(e) => setFilters({...filters, search: e.target.value})}
                className="filter-input"
              />
              
              <select
                value={filters.statut}
                onChange={(e) => setFilters({...filters, statut: e.target.value})}
                className="filter-select"
              >
                <option value="">Tous les statuts</option>
                <option value="en_attente">En attente</option>
                <option value="en_cours">En cours</option>
                <option value="en_revision">En révision</option>
                <option value="resolu">Résolu</option>
                <option value="ferme">Fermé</option>
                <option value="rejete">Rejeté</option>
              </select>

              <select
                value={filters.priorite}
                onChange={(e) => setFilters({...filters, priorite: e.target.value})}
                className="filter-select"
              >
                <option value="">Toutes les priorités</option>
                <option value="basse">Basse</option>
                <option value="normale">Normale</option>
                <option value="haute">Haute</option>
                <option value="urgente">Urgente</option>
              </select>

              <button onClick={loadReclamations} className="refresh-btn">
                🔄 Actualiser
              </button>
            </div>
          </section>

          {/* Liste des réclamations */}
          <section className="reclamations-section">
            <h2>Liste des réclamations ({pagination.total})</h2>

            {loading ? (
              <div className="loading-container">
                <div className="spinner"></div>
                <p>Chargement des réclamations...</p>
              </div>
            ) : (
              <div className="table-container">
                <table className="admin-table">
                  <thead>
                    <tr>
                      <th>N° Réclamation</th>
                      <th>Utilisateur</th>
                      <th>Type</th>
                      <th>Priorité</th>
                      <th>Statut</th>
                      <th>Date soumission</th>
                      <th>Attente</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {reclamations.map((reclamation) => (
                      <tr key={reclamation.id} className={reclamation.urgent ? 'row-urgent' : ''}>
                        <td>
                          <strong>{reclamation.numero_reclamation}</strong>
                        </td>
                        <td>
                          <div>
                            <strong>{reclamation.user_info?.nom_complet}</strong>
                            <br />
                            <small style={{ color: '#6b7280', fontSize: '0.75rem' }}>
                              Mat: {reclamation.user_info?.matricule_solde || 'N/A'}
                            </small>
                          </div>
                        </td>
                        <td>{reclamation.type_reclamation}</td>
                        <td>
                          <span 
                            className="priority-badge"
                            style={{ backgroundColor: getPriorityColor(reclamation.priorite) }}
                          >
                            {reclamation.priorite}
                          </span>
                        </td>
                        <td>
                          <span 
                            className="status-badge"
                            style={{ backgroundColor: getStatusColor(reclamation.statut) }}
                          >
                            {reclamation.statut.replace('_', ' ')}
                          </span>
                        </td>
                        <td>
                          {new Date(reclamation.date_soumission).toLocaleDateString('fr-FR')}
                        </td>
                        <td>
                          <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                            <span style={{ fontWeight: 500 }}>
                              {reclamation.temps_attente_format || `${reclamation.jours_attente} jour(s)`}
                            </span>
                            {reclamation.urgent && <span className="urgent-badge">URGENT</span>}
                          </div>
                        </td>
                        <td>
                          <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap' }}>
                            <button
                              className="btn-view"
                              onClick={() => handleTraiter(reclamation)}
                            >
                              {reclamation.peut_traiter ? '✏️ Traiter' : '👁️ Voir'}
                            </button>
                            <button
                              className="btn-delete"
                              onClick={() => handleSupprimer(reclamation)}
                              title="Supprimer"
                            >
                              🗑️
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>

                {/* Pagination */}
                {pagination.last_page > 1 && (
                  <div className="pagination">
                    <button
                      disabled={pagination.current_page === 1}
                      onClick={() => setPagination({...pagination, current_page: pagination.current_page - 1})}
                      className="pagination-btn"
                    >
                      Précédent
                    </button>
                    
                    <span className="pagination-info">
                      Page {pagination.current_page} sur {pagination.last_page}
                    </span>
                    
                    <button
                      disabled={pagination.current_page === pagination.last_page}
                      onClick={() => setPagination({...pagination, current_page: pagination.current_page + 1})}
                      className="pagination-btn"
                    >
                      Suivant
                    </button>
                  </div>
                )}
              </div>
            )}
          </section>
        </div>
      </main>

      {/* Modal de traitement */}
      {showModal && selectedReclamation && (
        <TraiterReclamationModal
          reclamation={selectedReclamation}
          onClose={() => {
            setShowModal(false);
            setSelectedReclamation(null);
          }}
          onTraiter={async (data) => {
            try {
              const response = await adminReclamationService.traiter(selectedReclamation.id, data);
              if (response.data.success) {
                alert('Réclamation traitée avec succès');
                setShowModal(false);
                loadReclamations();
              }
            } catch (error) {
              console.error('Erreur:', error);
              alert('Erreur lors du traitement');
            }
          }}
        />
      )}
    </div>
  );
};

// Modal de traitement
const TraiterReclamationModal = ({ reclamation, onClose, onTraiter }) => {
  const [formData, setFormData] = useState({
    statut: reclamation.statut,
    priorite: reclamation.priorite,
    reponse_admin: ''
  });
  const [downloadingDoc, setDownloadingDoc] = useState(null);

  // Charger la réponse pré-remplie quand le statut change
  const handleStatutChange = (newStatut) => {
    setFormData(prev => ({
      ...prev,
      statut: newStatut,
      reponse_admin: adminReclamationService.utils.getReponsePredefinie(newStatut)
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!formData.reponse_admin.trim()) {
      alert('Veuillez saisir une réponse');
      return;
    }
    onTraiter(formData);
  };

// ✅ FONCTION CORRIGÉE (ligne ~329) :
const handleDownloadDocument = async (index) => {
  try {
    setDownloadingDoc(index);
    console.log('📥 [ADMIN] Début téléchargement document:', { 
      reclamationId: reclamation.id, 
      index 
    });
    
    const token = localStorage.getItem('admin_token');
    
    if (!token) {
      alert('Session expirée. Veuillez vous reconnecter.');
      return;
    }

    // Obtenir les infos du document
    const documentInfo = reclamation.documents_info?.[index];
    const documentName = documentInfo?.nom || `document_${index + 1}`;

    console.log('📄 Document à télécharger:', documentInfo);

    // Construire l'URL
    const endpoint = `http://localhost:8000/api/admin/reclamations/${reclamation.id}/document/${index}`;

    // Faire la requête avec fetch pour plus de contrôle
    const response = await fetch(endpoint, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/octet-stream'
      }
    });

    console.log('📡 Réponse serveur:', { 
      status: response.status, 
      statusText: response.statusText,
      contentType: response.headers.get('content-type')
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error('❌ Erreur serveur:', errorText);
      throw new Error(`Erreur ${response.status}: ${response.statusText}`);
    }

    // Récupérer le blob
    const blob = await response.blob();
    console.log('✅ Blob reçu:', { size: blob.size, type: blob.type });
    
    // Créer un URL temporaire
    const url = window.URL.createObjectURL(blob);
    
    // Créer un lien et le cliquer
    const link = document.createElement('a');
    link.href = url;
    link.download = documentName;
    document.body.appendChild(link);
    link.click();
    
    // Nettoyer
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    
    console.log('✅ Document téléchargé avec succès');
    
  } catch (error) {
    console.error('❌ Erreur téléchargement document:', error);
    alert('Erreur lors du téléchargement du document: ' + error.message);
  } finally {
    setDownloadingDoc(null);
  }
};

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content large" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h3>Traiter la réclamation #{reclamation.numero_reclamation}</h3>
          <button onClick={onClose} className="modal-close">×</button>
        </div>

        <div className="modal-body">
          {/* Détails de la réclamation */}
          <div className="reclamation-details">
            <h4>Informations de la réclamation</h4>
            <div className="detail-grid">
              <div className="detail-item">
                <label>Soumis par:</label>
                <span>{reclamation.user_info?.nom_complet}</span>
              </div>
              <div className="detail-item">
                <label>Matricule:</label>
                <span>{reclamation.user_info?.matricule_solde || 'N/A'}</span>
              </div>
              <div className="detail-item">
                <label>Email:</label>
                <span>{reclamation.user_info?.email}</span>
              </div>
              <div className="detail-item">
                <label>Type:</label>
                <span>{reclamation.type_reclamation}</span>
              </div>
              <div className="detail-item">
                <label>Date soumission:</label>
                <span>
                  {new Date(reclamation.date_soumission).toLocaleDateString('fr-FR', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                  })}
                </span>
              </div>
              <div className="detail-item">
                <label>Priorité actuelle:</label>
                <span>{reclamation.priorite}</span>
              </div>
              <div className="detail-item">
                <label>Temps d'attente:</label>
                <span style={{ fontWeight: 600, color: '#3b82f6' }}>
                  {reclamation.temps_attente_format || `${reclamation.jours_attente} jour(s)`}
                </span>
              </div>
              {reclamation.date_traitement && (
                <div className="detail-item">
                  <label>Date traitement:</label>
                  <span style={{ color: '#10b981', fontWeight: 500 }}>
                    {new Date(reclamation.date_traitement).toLocaleDateString('fr-FR', {
                      day: 'numeric',
                      month: 'long',
                      year: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit'
                    })}
                  </span>
                </div>
              )}
            </div>

            <div className="description-section">
              <h5>Description:</h5>
              <p className="description-text">{reclamation.description}</p>
            </div>

            {reclamation.documents_info?.length > 0 && (
              <div className="documents-section">
                <h5>Documents joints ({reclamation.documents_info.length}):</h5>
                <div className="documents-list">
                  {reclamation.documents_info.map((doc, index) => (
                    <div key={index} className="document-item">
                      <div className="document-info">
                        <span className="document-icon">📎</span>
                        <div className="document-details">
                          <strong>{doc.nom}</strong>
                          <small>{(doc.taille / 1024).toFixed(2)} KB</small>
                        </div>
                      </div>
                      <button
                        className="btn-download"
                        onClick={() => handleDownloadDocument(index)}
                        disabled={downloadingDoc === index}
                      >
                        {downloadingDoc === index ? '⏳ Téléchargement...' : '⬇️ Télécharger'}
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {reclamation.reponse_admin && (
              <div className="reponse-precedente">
                <h5>Réponse précédente de l'administration:</h5>
                <p className="description-text">{reclamation.reponse_admin}</p>
              </div>
            )}
          </div>

          {/* Formulaire de traitement */}
          {reclamation.peut_traiter ? (
            <form onSubmit={handleSubmit} className="traitement-form">
              <h4>Traitement de la réclamation</h4>
              
              <div className="form-group">
                <label>Nouveau statut*</label>
                <select
                  value={formData.statut}
                  onChange={(e) => handleStatutChange(e.target.value)}
                  required
                  className="form-select"
                >
                  <option value="en_cours">En cours</option>
                  <option value="en_revision">En révision</option>
                  <option value="resolu">Résolu</option>
                  <option value="ferme">Fermé</option>
                  <option value="rejete">Rejeté</option>
                </select>
                <small style={{ color: '#6b7280', fontSize: '0.75rem', marginTop: '0.25rem', display: 'block' }}>
                  💡 La réponse ci-dessous sera automatiquement pré-remplie selon le statut choisi
                </small>
              </div>

              <div className="form-group">
                <label>Priorité</label>
                <select
                  value={formData.priorite}
                  onChange={(e) => setFormData({...formData, priorite: e.target.value})}
                  className="form-select"
                >
                  <option value="basse">Basse</option>
                  <option value="normale">Normale</option>
                  <option value="haute">Haute</option>
                  <option value="urgente">Urgente</option>
                </select>
              </div>

              <div className="form-group">
                <label>Réponse à l'utilisateur*</label>
                <textarea
                  value={formData.reponse_admin}
                  onChange={(e) => setFormData({...formData, reponse_admin: e.target.value})}
                  required
                  rows="10"
                  className="form-textarea"
                  placeholder="Saisissez votre réponse détaillée..."
                />
                <small style={{ color: '#6b7280', fontSize: '0.75rem', marginTop: '0.25rem', display: 'block' }}>
                  Cette réponse sera visible par l'utilisateur dans son espace de réclamations
                </small>
              </div>

              <div className="form-actions">
                <button type="button" onClick={onClose} className="btn-secondary">
                  Annuler
                </button>
                <button type="submit" className="btn-primary">
                  📤 Envoyer la réponse
                </button>
              </div>
            </form>
          ) : (
            <div className="info-message">
              <p>Cette réclamation ne peut plus être modifiée (statut: {reclamation.statut})</p>
              <button onClick={onClose} className="btn-secondary">
                Fermer
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AdminReclamations;