import { useState, useEffect } from 'react';
import './RappelCertificatsMassif.css';

const RappelCertificatsMassif = ({ onClose, onSuccess }) => {
  const [loading, setLoading] = useState(false);
  const [loadingData, setLoadingData] = useState(true);
  const [retraitesSansCertificat, setRetraitesSansCertificat] = useState([]);
  const [selectedRetraites, setSelectedRetraites] = useState([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [searchQuery, setSearchQuery] = useState('');
  const [showOnlyWithEmail, setShowOnlyWithEmail] = useState(true);
  
  // Progression envoi
  const [progress, setProgress] = useState({ current: 0, total: 0 });
  const [results, setResults] = useState({ success: [], failed: [] });
  const [sendingComplete, setSendingComplete] = useState(false);

  const itemsPerPage = 10;

  // Charger les retraités sans certificat
  useEffect(() => {
    loadRetraites();
  }, []);

  const loadRetraites = async () => {
    try {
      setLoadingData(true);
      const token = localStorage.getItem('admin_token');
      
      const response = await fetch('http://localhost:8000/api/admin/documents?filtre=certificats_manquants', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      const data = await response.json();
      
      if (data.success && data.type === 'retraites_sans_certificat') {
        setRetraitesSansCertificat(data.data);
      }
    } catch (error) {
      console.error('Erreur chargement retraités:', error);
    } finally {
      setLoadingData(false);
    }
  };

  // Filtrer les retraités
  const getFilteredRetraites = () => {
    let filtered = retraitesSansCertificat;

    // Filtre email
    if (showOnlyWithEmail) {
      filtered = filtered.filter(r => r.email);
    }

    // Recherche
    if (searchQuery.trim()) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(r => 
        r.nom_complet.toLowerCase().includes(query) ||
        r.numero_pension.toLowerCase().includes(query) ||
        (r.email && r.email.toLowerCase().includes(query))
      );
    }

    return filtered;
  };

  const filteredRetraites = getFilteredRetraites();
  const totalPages = Math.ceil(filteredRetraites.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const currentRetraites = filteredRetraites.slice(startIndex, startIndex + itemsPerPage);

  // Stats
  const stats = {
    total: retraitesSansCertificat.length,
    avecEmail: retraitesSansCertificat.filter(r => r.email).length,
    sansEmail: retraitesSansCertificat.filter(r => !r.email).length,
    selected: selectedRetraites.length
  };

  // Sélection
  const handleToggle = (id) => {
    setSelectedRetraites(prev => 
      prev.includes(id) 
        ? prev.filter(rId => rId !== id)
        : [...prev, id]
    );
  };

  const handleToggleAll = () => {
    const currentIds = currentRetraites.map(r => r.id);
    const allSelected = currentIds.every(id => selectedRetraites.includes(id));
    
    if (allSelected) {
      // Désélectionner tous ceux de la page actuelle
      setSelectedRetraites(prev => prev.filter(id => !currentIds.includes(id)));
    } else {
      // Sélectionner tous ceux de la page actuelle
      setSelectedRetraites(prev => [...new Set([...prev, ...currentIds])]);
    }
  };

  const handleSelectAllPages = () => {
    const allIds = filteredRetraites.map(r => r.id);
    setSelectedRetraites(allIds);
  };

  const handleDeselectAll = () => {
    setSelectedRetraites([]);
  };

  // Envoi des rappels
  const handleEnvoyerRappels = async () => {
    if (selectedRetraites.length === 0) {
      alert('Veuillez sélectionner au moins un retraité');
      return;
    }

    const retraitesAEnvoyer = retraitesSansCertificat.filter(r => 
      selectedRetraites.includes(r.id) && r.email
    );

    if (retraitesAEnvoyer.length === 0) {
      alert('Aucun retraité sélectionné n\'a d\'adresse email');
      return;
    }

    if (!window.confirm(`Envoyer ${retraitesAEnvoyer.length} rappel(s) par email ?`)) {
      return;
    }

    setLoading(true);
    setProgress({ current: 0, total: retraitesAEnvoyer.length });
    setResults({ success: [], failed: [] });
    setSendingComplete(false);

    const token = localStorage.getItem('admin_token');

    for (let i = 0; i < retraitesAEnvoyer.length; i++) {
      const retraite = retraitesAEnvoyer[i];
      
      try {
        const response = await fetch(`http://localhost:8000/api/admin/retraites/${retraite.id}/rappel-certificat`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        });

        const data = await response.json();

        if (data.success) {
          setResults(prev => ({
            ...prev,
            success: [...prev.success, { 
              id: retraite.id, 
              nom: retraite.nom_complet, 
              email: retraite.email 
            }]
          }));
        } else {
          throw new Error(data.message || 'Erreur inconnue');
        }
      } catch (error) {
        console.error(`Erreur envoi à ${retraite.nom_complet}:`, error);
        setResults(prev => ({
          ...prev,
          failed: [...prev.failed, { 
            id: retraite.id, 
            nom: retraite.nom_complet, 
            email: retraite.email, 
            error: error.message 
          }]
        }));
      }

      setProgress({ current: i + 1, total: retraitesAEnvoyer.length });
      
      // Pause entre chaque envoi
      await new Promise(resolve => setTimeout(resolve, 300));
    }

    setLoading(false);
    setSendingComplete(true);
    
    // Fermer automatiquement après 3s si tout est OK
    if (results.failed.length === 0) {
      setTimeout(() => {
        onSuccess?.();
        onClose();
      }, 3000);
    }
  };

  if (loadingData) {
    return (
      <div className="modal-overlay" onClick={onClose}>
        <div className="modal-content rappel-modal" onClick={(e) => e.stopPropagation()}>
          <div className="modal-header">
            <h2>📧 Rappels de certificat</h2>
            <button onClick={onClose} className="btn-close">✕</button>
          </div>
          <div className="modal-body">
            <div className="loading-state">
              <div className="spinner"></div>
              <p>Chargement des retraités...</p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content rappel-modal" onClick={(e) => e.stopPropagation()}>
        
        {/* Header */}
        <div className="modal-header">
          <h2>📧 Envoi de rappels - Certificats de vie</h2>
          <button onClick={onClose} className="btn-close" disabled={loading}>✕</button>
        </div>

        {/* Body */}
        <div className="modal-body">
          
          {/* Stats */}
          <div className="rappel-stats">
            <div className="stat-box">
              <strong>{stats.total}</strong>
              <span>Total</span>
            </div>
            <div className="stat-box success">
              <strong>{stats.avecEmail}</strong>
              <span>Avec email</span>
            </div>
            <div className="stat-box warning">
              <strong>{stats.sansEmail}</strong>
              <span>Sans email</span>
            </div>
            <div className="stat-box primary">
              <strong>{stats.selected}</strong>
              <span>Sélectionnés</span>
            </div>
          </div>

          {/* Progression envoi */}
          {loading && (
            <div className="progress-section">
              <div className="progress-bar">
                <div 
                  className="progress-fill" 
                  style={{ width: `${(progress.current / progress.total) * 100}%` }}
                />
              </div>
              <p className="progress-text">
                📤 Envoi en cours... {progress.current}/{progress.total}
              </p>
            </div>
          )}

          {/* Résultats */}
          {sendingComplete && (
            <div className="results-section">
              {results.success.length > 0 && (
                <div className="result-group success">
                  <h4>✅ {results.success.length} envoi(s) réussi(s)</h4>
                  <div className="result-list">
                    {results.success.map(r => (
                      <div key={r.id} className="result-item">
                        <strong>{r.nom}</strong>
                        <small>{r.email}</small>
                      </div>
                    ))}
                  </div>
                </div>
              )}
              
              {results.failed.length > 0 && (
                <div className="result-group failed">
                  <h4>❌ {results.failed.length} échec(s)</h4>
                  <div className="result-list">
                    {results.failed.map(r => (
                      <div key={r.id} className="result-item">
                        <strong>{r.nom}</strong>
                        <small>{r.email}</small>
                        <small className="error-msg">{r.error}</small>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Liste de sélection */}
          {!sendingComplete && (
            <>
              {/* Filtres et recherche */}
              <div className="filters-bar">
                <div className="search-box">
                  <input
                    type="text"
                    placeholder="🔍 Rechercher par nom, N° pension, email..."
                    value={searchQuery}
                    onChange={(e) => {
                      setSearchQuery(e.target.value);
                      setCurrentPage(1);
                    }}
                    disabled={loading}
                  />
                </div>
                <label className="checkbox-filter">
                  <input
                    type="checkbox"
                    checked={showOnlyWithEmail}
                    onChange={(e) => {
                      setShowOnlyWithEmail(e.target.checked);
                      setCurrentPage(1);
                    }}
                    disabled={loading}
                  />
                  <span>Avec email uniquement</span>
                </label>
              </div>

              {/* Actions de sélection */}
              <div className="selection-actions">
                <div className="selection-info">
                  {selectedRetraites.length > 0 && (
                    <span className="selected-count">
                      {selectedRetraites.length} sélectionné(s)
                    </span>
                  )}
                </div>
                <div className="selection-buttons">
                  <button
                    onClick={handleToggleAll}
                    className="btn-select"
                    disabled={loading || currentRetraites.length === 0}
                  >
                    {currentRetraites.every(r => selectedRetraites.includes(r.id)) 
                      ? '☐ Désélectionner page' 
                      : '☑ Sélectionner page'}
                  </button>
                  <button
                    onClick={handleSelectAllPages}
                    className="btn-select"
                    disabled={loading || filteredRetraites.length === 0}
                  >
                    ☑ Tout sélectionner ({filteredRetraites.length})
                  </button>
                  {selectedRetraites.length > 0 && (
                    <button
                      onClick={handleDeselectAll}
                      className="btn-select deselect"
                      disabled={loading}
                    >
                      ✕ Tout désélectionner
                    </button>
                  )}
                </div>
              </div>

              {/* Liste avec cases à cocher */}
              <div className="retraites-list">
                {currentRetraites.length === 0 ? (
                  <div className="empty-list">
                    <p>Aucun retraité trouvé</p>
                  </div>
                ) : (
                  currentRetraites.map(retraite => (
                    <div 
                      key={retraite.id} 
                      className={`retraite-card ${!retraite.email ? 'no-email' : ''} ${selectedRetraites.includes(retraite.id) ? 'selected' : ''}`}
                    >
                      <label className="retraite-label">
                        <input
                          type="checkbox"
                          checked={selectedRetraites.includes(retraite.id)}
                          onChange={() => handleToggle(retraite.id)}
                          disabled={loading || !retraite.email}
                        />
                        <div className="retraite-content">
                          <div className="retraite-header">
                            <strong className="retraite-nom">{retraite.nom_complet}</strong>
                            {!retraite.email && (
                              <span className="badge-no-email">Sans email</span>
                            )}
                          </div>
                          <div className="retraite-details">
                            <span className="pension-number">📋 {retraite.numero_pension}</span>
                            {retraite.email ? (
                              <span className="email">📧 {retraite.email}</span>
                            ) : (
                              <span className="no-email-text">❌ Pas d'adresse email</span>
                            )}
                            {retraite.telephone && (
                              <span className="phone">📱 {retraite.telephone}</span>
                            )}
                          </div>
                        </div>
                      </label>
                    </div>
                  ))
                )}
              </div>

              {/* Pagination */}
              {totalPages > 1 && (
                <div className="pagination-bar">
                  <button
                    onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
                    disabled={currentPage === 1 || loading}
                    className="btn-page"
                  >
                    ← Précédent
                  </button>
                  
                  <div className="page-numbers">
                    {Array.from({ length: Math.min(totalPages, 5) }, (_, i) => {
                      let pageNum;
                      if (totalPages <= 5) {
                        pageNum = i + 1;
                      } else if (currentPage <= 3) {
                        pageNum = i + 1;
                      } else if (currentPage >= totalPages - 2) {
                        pageNum = totalPages - 4 + i;
                      } else {
                        pageNum = currentPage - 2 + i;
                      }
                      
                      return (
                        <button
                          key={pageNum}
                          onClick={() => setCurrentPage(pageNum)}
                          className={`btn-page-num ${currentPage === pageNum ? 'active' : ''}`}
                          disabled={loading}
                        >
                          {pageNum}
                        </button>
                      );
                    })}
                  </div>
                  
                  <button
                    onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))}
                    disabled={currentPage === totalPages || loading}
                    className="btn-page"
                  >
                    Suivant →
                  </button>
                </div>
              )}

              <div className="pagination-info-text">
                Page {currentPage} sur {totalPages} • {filteredRetraites.length} retraité(s)
              </div>
            </>
          )}
        </div>

        {/* Footer */}
        <div className="modal-footer">
          <button 
            onClick={onClose} 
            className="btn btn-secondary"
            disabled={loading}
          >
            {sendingComplete ? 'Fermer' : 'Annuler'}
          </button>
          {!sendingComplete && (
            <button 
              onClick={handleEnvoyerRappels} 
              className="btn btn-primary"
              disabled={loading || selectedRetraites.length === 0}
            >
              📧 Envoyer {selectedRetraites.length} rappel(s)
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default RappelCertificatsMassif;