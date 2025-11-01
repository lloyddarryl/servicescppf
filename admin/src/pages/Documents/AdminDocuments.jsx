import { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import AdminHeader from '../../components/AdminHeader';
import AdminNav from '../../components/AdminNav';
import { adminDocumentService } from '../../services/adminApi';
import DocumentDetailsModal from './DocumentDetailsModal';
import RappelCertificatsMassif from './RappelCertificatsMassif';
import './AdminDocuments.css';

const AdminDocuments = () => {
  const [documents, setDocuments] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [selectedDocument, setSelectedDocument] = useState(null);
  const [showDetailsModal, setShowDetailsModal] = useState(false);
  const [showRappelModal, setShowRappelModal] = useState(false);
  
  // Filtres
  const [filters, setFilters] = useState({
    type: 'tous',
    statut: 'tous',
    recherche: '',
    filtre: '',
    sort: 'date_depot',
    order: 'desc',
    per_page: 20
  });

  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    total: 0
  });

  const navigate = useNavigate();
  
  // Timer pour debounce de la recherche
  const debounceTimer = useRef(null);

  // Charger le dashboard au montage
  useEffect(() => {
    loadDashboard();
  }, []);

  // ✅ DEBOUNCE pour la recherche uniquement
  useEffect(() => {
    // Annuler le timer précédent
    if (debounceTimer.current) {
      clearTimeout(debounceTimer.current);
    }

    // Si la recherche est vide, charger immédiatement
    if (filters.recherche === '') {
      loadDocuments();
      return;
    }

    // Sinon, attendre 500ms après la dernière frappe
    debounceTimer.current = setTimeout(() => {
      loadDocuments();
    }, 500);

    // Cleanup
    return () => {
      if (debounceTimer.current) {
        clearTimeout(debounceTimer.current);
      }
    };
  }, [filters.recherche]);

  // ✅ Charger immédiatement pour les autres filtres (sans debounce)
  useEffect(() => {
    loadDocuments();
  }, [
    filters.type, 
    filters.statut, 
    filters.filtre, 
    filters.sort, 
    filters.order, 
    filters.per_page,
    pagination.current_page
  ]);

  const loadDashboard = async () => {
    try {
      const response = await adminDocumentService.getDashboard();
      if (response.data.success) {
        setStats(response.data.stats);
      }
    } catch (error) {
      console.error('Erreur chargement dashboard:', error);
    }
  };

  const loadDocuments = async () => {
    try {
      setLoading(true);
      setError(null);

      const params = {
        ...filters,
        page: pagination.current_page
      };

      // Ne pas envoyer la recherche si vide
      if (!params.recherche || params.recherche.trim() === '') {
        delete params.recherche;
      }

      const response = await adminDocumentService.getAll(params);
      
      if (response.data.success) {
        setDocuments(response.data.documents || []);
        
        if (response.data.pagination) {
          setPagination(response.data.pagination);
        }
      } else {
        throw new Error('Erreur lors du chargement');
      }
    } catch (error) {
      console.error('Erreur chargement documents:', error);
      setError('Impossible de charger les documents');
      setDocuments([]);
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({
      ...prev,
      [key]: value
    }));
    // Reset pagination sauf pour recherche (géré par debounce)
    if (key !== 'recherche') {
      setPagination(prev => ({ ...prev, current_page: 1 }));
    }
  };

  const handleResetFilters = () => {
    setFilters({
      type: 'tous',
      statut: 'tous',
      recherche: '',
      filtre: '',
      sort: 'date_depot',
      order: 'desc',
      per_page: 20
    });
    setPagination({ current_page: 1, last_page: 1, total: 0 });
  };

  const handleViewDetails = async (docId) => {
    try {
      const response = await adminDocumentService.getById(docId);
      if (response.data.success) {
        setSelectedDocument(response.data.document);
        setShowDetailsModal(true);
      }
    } catch (error) {
      console.error('Erreur chargement détails:', error);
      alert('Impossible de charger les détails du document');
    }
  };

  const handleCloseModal = () => {
    setShowDetailsModal(false);
    setSelectedDocument(null);
    loadDocuments();
    loadDashboard();
  };

  const handleOuvrirRappelMassif = () => {
    setShowRappelModal(true);
  };

  const handleCloseRappelModal = () => {
    setShowRappelModal(false);
    loadDashboard();
    loadDocuments();
  };

  const getStatusBadgeClass = (doc) => {
    if (doc.is_expire) return 'badge-danger';
    if (doc.expire_bientot) return 'badge-warning';
    return 'badge-success';
  };

  const getStatusText = (doc) => {
    if (doc.is_expire) return '❌ Expiré';
    if (doc.expire_bientot) {
      const jours = doc.jours_avant_expiration;
      return `⚠️ Expire dans ${jours}j`;
    }
    return '✅ Valide';
  };

  if (loading && documents.length === 0) {
    return (
      <div className="admin-dashboard">
        <AdminHeader title="Gestion des Documents" breadcrumb="Documents" />
        <AdminNav />
        <main className="admin-main">
          <div className="loading-container">
            <div className="spinner"></div>
            <p>Chargement des documents...</p>
          </div>
        </main>
      </div>
    );
  }

  return (
    <div className="admin-dashboard">
      <AdminHeader title="Gestion des Documents" breadcrumb="Validation et suivi des documents" />
      <AdminNav />

      <main className="admin-main">
        <div className="documents-container">
          
          {/* Statistiques */}
          {stats && (
            <div className="stats-section">
              <div className="stats-grid-compact">
                <div className="stat-card-mini">
                  <div className="stat-icon">📋</div>
                  <div className="stat-content">
                    <h4>Certificats de vie</h4>
                    <p className="stat-number">{stats.certificats_total}</p>
                    <span className="stat-detail">
                      {stats.certificats_valides} valides • {stats.certificats_expires} expirés
                    </span>
                  </div>
                </div>

                <div 
                  className="stat-card-mini urgent clickable" 
                  onClick={() => handleFilterChange('filtre', 'certificats_expires')}
                >
                  <div className="stat-icon">❌</div>
                  <div className="stat-content">
                    <h4>Expirés</h4>
                    <p className="stat-number danger">{stats.certificats_expires}</p>
                    <span className="stat-detail">Nécessitent renouvellement</span>
                  </div>
                </div>

                <div 
                  className="stat-card-mini warning clickable" 
                  onClick={() => handleFilterChange('filtre', 'certificats_expirant')}
                >
                  <div className="stat-icon">⚠️</div>
                  <div className="stat-content">
                    <h4>Expire bientôt</h4>
                    <p className="stat-number warning">{stats.certificats_expirant_30j}</p>
                    <span className="stat-detail">Dans les 30 jours</span>
                  </div>
                </div>

                <div 
                  className="stat-card-mini danger clickable" 
                  onClick={handleOuvrirRappelMassif}
                >
                  <div className="stat-icon">🚨</div>
                  <div className="stat-content">
                    <h4>Sans certificat</h4>
                    <p className="stat-number danger">{stats.retraites_sans_certificat}</p>
                    <span className="stat-detail">
                      📧 Envoyer rappels massifs
                    </span>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Filtres */}
          <div className="filters-section">
            <div className="filters-row">
              <div className="filter-group">
                <label>Type de document</label>
                <select 
                  value={filters.type} 
                  onChange={(e) => handleFilterChange('type', e.target.value)}
                  className="filter-select"
                >
                  <option value="tous">Tous les types</option>
                  <option value="certificat_vie">📋 Certificat de vie</option>
                  <option value="autre">📄 Autres documents</option>
                </select>
              </div>

              <div className="filter-group">
                <label>Statut</label>
                <select 
                  value={filters.statut} 
                  onChange={(e) => handleFilterChange('statut', e.target.value)}
                  className="filter-select"
                >
                  <option value="tous">Tous les statuts</option>
                  <option value="actif">✅ Actif</option>
                  <option value="expire">❌ Expiré</option>
                  <option value="expirant">⚠️ Expire bientôt</option>
                  <option value="remplace">🔄 Remplacé</option>
                </select>
              </div>

              <div className="filter-group search-group">
                <label>Rechercher</label>
                <input
                  type="text"
                  placeholder="N° pension, nom, prénom..."
                  value={filters.recherche}
                  onChange={(e) => handleFilterChange('recherche', e.target.value)}
                  className="filter-input"
                />
                {filters.recherche && (
                  <small className="search-hint">
                    ⏱️ Recherche automatique après 0.5s...
                  </small>
                )}
              </div>

              <div className="filter-actions">
                <button onClick={handleResetFilters} className="btn-reset">
                  🔄 Réinitialiser
                </button>
              </div>
            </div>
          </div>

          {/* Liste des documents */}
          <div className="documents-list-section">
            <div className="section-header">
              <h3>
                Documents ({pagination.total || documents.length})
              </h3>
              <div className="sort-controls">
                <select 
                  value={filters.sort} 
                  onChange={(e) => handleFilterChange('sort', e.target.value)}
                  className="sort-select"
                >
                  <option value="date_depot">Date de dépôt</option>
                  <option value="date_expiration">Date d'expiration</option>
                  <option value="retraite.nom">Nom du retraité</option>
                </select>
                <button 
                  onClick={() => handleFilterChange('order', filters.order === 'asc' ? 'desc' : 'asc')}
                  className="btn-sort"
                  title={filters.order === 'asc' ? 'Croissant' : 'Décroissant'}
                >
                  {filters.order === 'asc' ? '↑' : '↓'}
                </button>
              </div>
            </div>

            {error && (
              <div className="error-message">
                <p>{error}</p>
                <button onClick={loadDocuments}>Réessayer</button>
              </div>
            )}

            {loading ? (
              <div className="loading-overlay">
                <div className="spinner-small"></div>
                <span>Chargement...</span>
              </div>
            ) : !loading && documents.length === 0 ? (
              <div className="empty-state">
                <div className="empty-icon">📄</div>
                <h3>Aucun document trouvé</h3>
                <p>Aucun document ne correspond aux critères de recherche</p>
              </div>
            ) : (
              <div className="documents-table-wrapper">
                <table className="documents-table">
                  <thead>
                    <tr>
                      <th>Type</th>
                      <th>Retraité</th>
                      <th>Document</th>
                      <th>Date dépôt</th>
                      <th>Expiration</th>
                      <th>Statut</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {documents.map((doc) => (
                      <tr key={doc.id} className={doc.is_expire ? 'row-danger' : doc.expire_bientot ? 'row-warning' : ''}>
                        <td>
                          <div className="doc-type">
                            <span className="type-icon">{doc.icone_type}</span>
                            <span>{doc.nom_type}</span>
                          </div>
                        </td>
                        <td>
                          <div className="retraite-info">
                            <strong>{doc.retraite?.nom_complet}</strong>
                            <small>{doc.retraite?.numero_pension}</small>
                          </div>
                        </td>
                        <td>
                          <div className="doc-info">
                            <span className="doc-name">{doc.nom_original}</span>
                            <small>{doc.taille_formatee}</small>
                          </div>
                        </td>
                        <td>{doc.date_depot}</td>
                        <td>
                          {doc.date_expiration ? (
                            <div className={`expiration-cell ${getStatusBadgeClass(doc)}`}>
                              {doc.date_expiration}
                              {doc.jours_avant_expiration !== null && (
                                <small>({doc.jours_avant_expiration}j)</small>
                              )}
                            </div>
                          ) : (
                            <span className="text-muted">-</span>
                          )}
                        </td>
                        <td>
                          <span className={`badge ${getStatusBadgeClass(doc)}`}>
                            {getStatusText(doc)}
                          </span>
                        </td>
                        <td>
                          <div className="action-buttons">
                            <button 
                              onClick={() => handleViewDetails(doc.id)}
                              className="btn-action btn-view"
                              title="Voir détails"
                            >
                              👁️
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}

            {/* Pagination */}
            {pagination.last_page > 1 && (
              <div className="pagination">
                <button
                  onClick={() => setPagination(prev => ({ ...prev, current_page: prev.current_page - 1 }))}
                  disabled={pagination.current_page === 1}
                  className="btn-pagination"
                >
                  ← Précédent
                </button>
                <span className="pagination-info">
                  Page {pagination.current_page} sur {pagination.last_page}
                  <small>({pagination.total} documents)</small>
                </span>
                <button
                  onClick={() => setPagination(prev => ({ ...prev, current_page: prev.current_page + 1 }))}
                  disabled={pagination.current_page === pagination.last_page}
                  className="btn-pagination"
                >
                  Suivant →
                </button>
              </div>
            )}
          </div>
        </div>
      </main>

      {/* Modal détails */}
      {showDetailsModal && selectedDocument && (
        <DocumentDetailsModal
          document={selectedDocument}
          onClose={handleCloseModal}
        />
      )}

      {/* Modal rappel massif */}
      {showRappelModal && (
        <RappelCertificatsMassif
          onClose={handleCloseRappelModal}
          onSuccess={handleCloseRappelModal}
        />
      )}
    </div>
  );
};

export default AdminDocuments;