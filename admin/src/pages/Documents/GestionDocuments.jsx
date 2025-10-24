// admin-cppf/src/pages/Documents/GestionDocuments.jsx

import React, { useState, useEffect } from 'react';
import { adminDocumentService } from '../../services/adminApi';
import ModalDocumentDetails from '../../components/ModalDocumentDetails';
import './GestionDocuments.css';

const GestionDocuments = () => {
  const [documents, setDocuments] = useState([]);
  const [loading, setLoading] = useState(false);
  const [filtres, setFiltres] = useState({
    type: '',
    validite: '',
    recherche: ''
  });
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    total: 0
  });
  const [selectedDocument, setSelectedDocument] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [notification, setNotification] = useState(null);
  const [statistiques, setStatistiques] = useState(null);

  useEffect(() => {
    chargerDocuments();
    chargerStatistiques();
  }, [filtres, pagination.current_page]);

  const chargerDocuments = async () => {
    try {
      setLoading(true);
      const params = {
        ...filtres,
        page: pagination.current_page
      };
      
      const response = await adminDocumentService.getAll(params);
      
      if (response.data.success) {
        setDocuments(response.data.documents);
        setPagination(response.data.pagination);
      }
    } catch (error) {
      console.error('Erreur chargement documents:', error);
      afficherNotification('Erreur de chargement', 'error');
    } finally {
      setLoading(false);
    }
  };

  const chargerStatistiques = async () => {
    try {
      const response = await adminDocumentService.getStatistiques();
if (response.data.success) {
        setStatistiques(response.data.statistiques);
      }
    } catch (error) {
      console.error('Erreur chargement statistiques:', error);
    }
  };

  const afficherNotification = (message, type = 'info') => {
    setNotification({ message, type });
    setTimeout(() => setNotification(null), 5000);
  };

  const handleValider = async (id, commentaire) => {
    try {
      const response = await adminDocumentService.valider(id, { commentaire });
      
      if (response.data.success) {
        afficherNotification('Document validé avec succès', 'success');
        chargerDocuments();
        chargerStatistiques();
        setShowModal(false);
      }
    } catch (error) {
      console.error('Erreur validation:', error);
      afficherNotification('Erreur lors de la validation', 'error');
    }
  };

  const handleRejeter = async (id, motifRejet) => {
    try {
      const response = await adminDocumentService.rejeter(id, { motif_rejet: motifRejet });
      
      if (response.data.success) {
        afficherNotification('Document rejeté', 'success');
        chargerDocuments();
        chargerStatistiques();
        setShowModal(false);
      }
    } catch (error) {
      console.error('Erreur rejet:', error);
      afficherNotification('Erreur lors du rejet', 'error');
    }
  };

  const handleSupprimer = async (id, motifSuppression) => {
    if (!window.confirm('⚠️ Attention: Cette action est irréversible. Confirmer la suppression définitive ?')) {
      return;
    }

    try {
      const response = await adminDocumentService.supprimer(id, {
        motif_suppression: motifSuppression,
        confirmation: true
      });
      
      if (response.data.success) {
        afficherNotification('Document supprimé définitivement', 'success');
        chargerDocuments();
        chargerStatistiques();
        setShowModal(false);
      }
    } catch (error) {
      console.error('Erreur suppression:', error);
      afficherNotification('Erreur lors de la suppression', 'error');
    }
  };

  const handleTelecharger = async (id, nomOriginal) => {
    try {
      const response = await adminDocumentService.download(id);
      adminDocumentService.utils.downloadBlob(response.data, nomOriginal);
      afficherNotification('Téléchargement démarré', 'success');
    } catch (error) {
      console.error('Erreur téléchargement:', error);
      afficherNotification('Erreur lors du téléchargement', 'error');
    }
  };

  return (
    <div className="admin-documents">
      {/* Header */}
      <div className="admin-documents__header">
        <div className="admin-documents__header-left">
          <h1>Gestion des Documents</h1>
          <p className="admin-documents__subtitle">
            {pagination.total} document{pagination.total > 1 ? 's' : ''} au total
          </p>
        </div>
        <div className="admin-documents__header-right">
          <button 
            onClick={chargerDocuments}
            className="btn btn-primary"
          >
            🔄 Actualiser
          </button>
        </div>
      </div>

      {/* Notification */}
      {notification && (
        <div className={`notification notification--${notification.type}`}>
          {notification.message}
          <button onClick={() => setNotification(null)}>✕</button>
        </div>
      )}

      {/* Statistiques */}
      {statistiques && (
        <div className="admin-documents__stats">
          <div className="stat-card stat-card--primary">
            <div className="stat-card__icon">📄</div>
            <div className="stat-card__content">
              <div className="stat-card__value">{statistiques.total}</div>
              <div className="stat-card__label">Total documents</div>
            </div>
          </div>
          <div className="stat-card stat-card--success">
            <div className="stat-card__icon">📋</div>
            <div className="stat-card__content">
              <div className="stat-card__value">{statistiques.certificats_vie}</div>
              <div className="stat-card__label">Certificats de vie</div>
            </div>
          </div>
          <div className="stat-card stat-card--danger">
            <div className="stat-card__icon">⚠️</div>
            <div className="stat-card__content">
              <div className="stat-card__value">{statistiques.expires}</div>
              <div className="stat-card__label">Documents expirés</div>
            </div>
          </div>
          <div className="stat-card stat-card--warning">
            <div className="stat-card__icon">⏰</div>
            <div className="stat-card__content">
              <div className="stat-card__value">{statistiques.expirent_bientot}</div>
              <div className="stat-card__label">Expirent bientôt</div>
            </div>
          </div>
        </div>
      )}

      {/* Filtres */}
      <div className="admin-documents__filtres">
        <div className="filtres-row">
          <div className="filtre-group">
            <label>Type de document</label>
            <select 
              value={filtres.type}
              onChange={(e) => setFiltres({...filtres, type: e.target.value})}
              className="filtre-select"
            >
              <option value="">Tous les types</option>
              <option value="certificat_vie">Certificats de vie</option>
              <option value="autre">Autres documents</option>
            </select>
          </div>

          <div className="filtre-group">
            <label>Validité</label>
            <select 
              value={filtres.validite}
              onChange={(e) => setFiltres({...filtres, validite: e.target.value})}
              className="filtre-select"
            >
              <option value="">Tous les états</option>
              <option value="valide">Valides</option>
              <option value="expire">Expirés</option>
              <option value="expire_bientot">Expire bientôt (30j)</option>
              <option value="en_attente">En attente validation</option>
            </select>
          </div>

          <div className="filtre-group filtre-group--wide">
            <label>Recherche</label>
            <input
              type="text"
              value={filtres.recherche}
              onChange={(e) => setFiltres({...filtres, recherche: e.target.value})}
              placeholder="Nom du retraité, N° pension..."
              className="filtre-input"
            />
          </div>
        </div>

        <button 
          onClick={() => setFiltres({ type: '', validite: '', recherche: '' })}
          className="btn-reset-filtres"
        >
          🔄 Réinitialiser
        </button>
      </div>

      {/* Liste des documents */}
      {loading ? (
        <div className="admin-documents__loading">
          <div className="spinner"></div>
          <p>Chargement des documents...</p>
        </div>
      ) : documents.length === 0 ? (
        <div className="admin-documents__empty">
          <div className="empty-icon">📄</div>
          <h3>Aucun document</h3>
          <p>Aucun document ne correspond à vos critères de recherche</p>
        </div>
      ) : (
        <>
          <div className="admin-documents__table-container">
            <table className="admin-documents__table">
              <thead>
                <tr>
                  <th>Retraité</th>
                  <th>N° Pension</th>
                  <th>Document</th>
                  <th>Type</th>
                  <th>Date dépôt</th>
                  <th>Expiration</th>
                  <th>Taille</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {documents.map(document => {
                  const expirationStatus = adminDocumentService.utils.getExpirationStatus(document.date_expiration);
                  
                  return (
                    <tr 
                      key={document.id}
                      className={`table-row ${document.is_expire ? 'row-expired' : ''}`}
                    >
                      <td>
                        <div className="user-cell">
                          <div className="user-name">{document.retraite_nom}</div>
                        </div>
                      </td>
                      <td>
                        <strong>{document.retraite_pension}</strong>
                      </td>
                      <td>
                        <div className="document-cell">
                          <div className="document-icon">
                            {document.type_document === 'certificat_vie' ? '📋' : '📄'}
                          </div>
                          <div className="document-info">
                            <div className="document-name">{document.nom_original}</div>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span className={`badge badge-${document.type_document}`}>
                          {document.type_document === 'certificat_vie' ? 'Certificat de vie' : 'Autre'}
                        </span>
                      </td>
                      <td>
                        <div className="date-cell">
                          {new Date(document.date_depot).toLocaleDateString('fr-FR')}
                        </div>
                      </td>
                      <td>
                        {document.date_expiration ? (
                          <div className="expiration-cell">
                            <div className="expiration-date">
                              {new Date(document.date_expiration).toLocaleDateString('fr-FR')}
                            </div>
                            {expirationStatus && (
                              <div 
                                className="expiration-status"
                                style={{ color: expirationStatus.color }}
                              >
                                {expirationStatus.label}
                                {document.jours_avant_expiration !== null && (
                                  <span className="expiration-days">
                                    {document.jours_avant_expiration > 0 
                                      ? ` (${document.jours_avant_expiration}j)`
                                      : ` (${Math.abs(document.jours_avant_expiration)}j)`
                                    }
                                  </span>
                                )}
                              </div>
                            )}
                          </div>
                        ) : (
                          <span className="no-expiration">-</span>
                        )}
                      </td>
                      <td>
                        <span className="file-size">{document.taille_formatee}</span>
                      </td>
                      <td>
                        {document.is_expire ? (
                          <span className="status-badge status-badge--expired">
                            ⚠️ Expiré
                          </span>
                        ) : document.jours_avant_expiration !== null && document.jours_avant_expiration <= 30 ? (
                          <span className="status-badge status-badge--expiring">
                            ⏰ Expire bientôt
                          </span>
                        ) : (
                          <span className="status-badge status-badge--valid">
                            ✅ Valide
                          </span>
                        )}
                      </td>
                      <td>
                        <div className="actions-cell">
                          <button
                            onClick={() => handleTelecharger(document.id, document.nom_original)}
                            className="btn-action btn-action--download"
                            title="Télécharger"
                          >
                            📥
                          </button>
                          <button
                            onClick={() => {
                              setSelectedDocument(document);
                              setShowModal(true);
                            }}
                            className="btn-action btn-action--primary"
                            title="Gérer"
                          >
                            ⚙️
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          {pagination.last_page > 1 && (
            <div className="admin-documents__pagination">
              <button
                onClick={() => setPagination({...pagination, current_page: pagination.current_page - 1})}
                disabled={pagination.current_page === 1}
                className="pagination-btn"
              >
                ← Précédent
              </button>
              
              <div className="pagination-pages">
                {Array.from({ length: Math.min(pagination.last_page, 10) }, (_, i) => {
                  const page = i + 1;
                  return (
                    <button
                      key={page}
                      onClick={() => setPagination({...pagination, current_page: page})}
                      className={`pagination-page ${pagination.current_page === page ? 'active' : ''}`}
                    >
                      {page}
                    </button>
                  );
                })}
              </div>

              <button
                onClick={() => setPagination({...pagination, current_page: pagination.current_page + 1})}
                disabled={pagination.current_page === pagination.last_page}
                className="pagination-btn"
              >
                Suivant →
              </button>
            </div>
          )}
        </>
      )}

      {/* Modal de gestion */}
      {showModal && selectedDocument && (
        <ModalDocumentDetails
          document={selectedDocument}
          onClose={() => {
            setShowModal(false);
            setSelectedDocument(null);
          }}
          onValider={handleValider}
          onRejeter={handleRejeter}
          onSupprimer={handleSupprimer}
          onTelecharger={handleTelecharger}
        />
      )}
    </div>
  );
};

export default GestionDocuments;