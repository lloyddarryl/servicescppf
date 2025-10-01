import React, { useState, useEffect, useCallback } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import Header from '../../components/Header';
import api from '../../services/api';
import './SuiviCotisations.css';

const SuiviCotisations = () => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [filtres, setFiltres] = useState({
    annee: '',
    statut: '',
    page: 1
  });
  const [generationPDF, setGenerationPDF] = useState(false);
  const [recherche, setRecherche] = useState('');

  // Fonctions de civilité (inchangées)
  const getCivilite = (user) => {
    if (!user) return '';
    
    const sexe = user.sexe?.toUpperCase();
    const situationMatrimoniale = user.situation_matrimoniale?.toLowerCase();
    
    if (sexe === 'M' || sexe === 'MASCULIN') {
      return 'M.';
    } 
    else if (sexe === 'F' || sexe === 'FEMININ') {
      if (['mariee', 'marie', 'mariée', 'marié'].includes(situationMatrimoniale)) {
        return 'Mme';
      } else {
        return 'Mlle';
      }
    }
    
    return '';
  };

  const getIdentiteComplete = (user) => {
    if (!user) return '';
    
    const civilite = getCivilite(user);
    const nomComplet = `${user.prenoms || ''} ${user.nom || ''}`.trim();
    
    return civilite ? `${civilite} ${nomComplet}` : nomComplet;
  };

  const getSituationMatrimonialeLibelle = (situationMatrimoniale) => {
    if (!situationMatrimoniale) return 'Non spécifiée';
    
    const situations = {
      'celibataire': 'Célibataire',
      'marie': 'Marié(e)',
      'mariee': 'Mariée',
      'divorce': 'Divorcé(e)',
      'divorcee': 'Divorcée',
      'veuf': 'Veuf/Veuve',
      'veuve': 'Veuve',
      'concubinage': 'En concubinage',
      'separe': 'Séparé(e)',
      'separee': 'Séparée'
    };
    
    const key = situationMatrimoniale.toLowerCase();
    return situations[key] || situationMatrimoniale.charAt(0).toUpperCase() + situationMatrimoniale.slice(1);
  };

  // Fonction pour charger les cotisations (backend utilise les carrières)
  const fetchCotisations = useCallback(async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      
      if (filtres.annee) params.append('annee', filtres.annee);
      if (filtres.statut) params.append('statut', filtres.statut);
      params.append('page', filtres.page);
      params.append('per_page', 10);

      // L'URL reste /actifs/cotisations (interface unchanged)
      const response = await api.get(`/actifs/cotisations?${params}`);
      
      if (response.data.success) {
        setData(response.data.data);
      } else {
        throw new Error(response.data.message || 'Erreur de chargement');
      }
    } catch (err) {
      console.error('Erreur chargement cotisations:', err);
      setError('Impossible de charger les données des cotisations');
    } finally {
      setLoading(false);
    }
  }, [filtres]);

  // Scroll to top au chargement
  useEffect(() => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }, []);

  // Charger les données
  useEffect(() => {
    fetchCotisations();
  }, [fetchCotisations]);

  // Générer le PDF
  const genererPDF = async () => {
    try {
      setGenerationPDF(true);
      
      // URL reste /actifs/cotisations/releve-pdf
      const response = await api.get('/actifs/cotisations/releve-pdf', {
        responseType: 'blob'
      });
      
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `releve_situation_${data?.agent?.matricule_solde || 'agent'}_${new Date().toISOString().split('T')[0]}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
      
    } catch (err) {
      console.error('Erreur génération PDF:', err);
      setError('Erreur lors de la génération du relevé PDF');
    } finally {
      setGenerationPDF(false);
    }
  };

  // Gérer les changements de filtres
  const handleFiltreChange = (key, value) => {
    setFiltres(prev => ({
      ...prev,
      [key]: value,
      page: 1
    }));
  };

  // Gérer la pagination
  const handlePageChange = (page) => {
    setFiltres(prev => ({ ...prev, page }));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // Recherche
  const handleRecherche = async () => {
    if (!recherche.trim()) {
      fetchCotisations();
      return;
    }

    try {
      setLoading(true);
      // URL reste /actifs/cotisations/search
      const response = await api.get(`/actifs/cotisations/search?q=${encodeURIComponent(recherche)}`);
      
      if (response.data.success) {
        setData(prev => ({
          ...prev,
          cotisations: response.data.data.data,
          pagination: response.data.data
        }));
      }
    } catch (err) {
      console.error('Erreur recherche:', err);
      setError('Erreur lors de la recherche');
    } finally {
      setLoading(false);
    }
  };

  const formatMontant = (montant) => {
    return new Intl.NumberFormat('fr-FR').format(montant) + ' FCFA';
  };

  if (loading && !data) {
    return (
      <div className="suivi-cotisations">
        <Header />
        <div className="suivi-cotisations__loading">
          <div className="spinner"></div>
          <p>Chargement de vos cotisations...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="suivi-cotisations">
        <Header />
        <div className="suivi-cotisations__error">
          <h2>Erreur</h2>
          <p>{error}</p>
          <button onClick={fetchCotisations} className="btn btn--primary">
            Réessayer
          </button>
        </div>
      </div>
    );
  }

  const { agent, cotisations, pagination, statistiques, graphique, filtres: filtresDisponibles } = data || {};

  return (
    <div className="suivi-cotisations">
      <Header />
      
      <main className="suivi-cotisations__main">
        <div className="container">
          
          {/* Section de bienvenue */}
          {agent && (
            <div className="suivi-cotisations__welcome">
              <div className="suivi-cotisations__welcome-content">
                <h1 className="suivi-cotisations__welcome-title">
                  Suivi des Cotisations
                </h1>
                <p className="suivi-cotisations__welcome-subtitle">
                  <span className="suivi-cotisations__welcome-user">
                    Bienvenue {getIdentiteComplete(agent)}
                  </span>
                  <span className="suivi-cotisations__welcome-badge">
                    {agent.type_compte}
                    {agent.sexe && (
                      <span style={{ marginLeft: '8px', fontSize: '0.85em', opacity: '0.8' }}>
                        • {agent.sexe.toUpperCase() === 'M' || agent.sexe.toUpperCase() === 'MASCULIN' ? 'Masculin' : 'Féminin'}
                      </span>
                    )}
                    {agent.situation_matrimoniale && (
                      <span style={{ marginLeft: '8px', fontSize: '0.85em', opacity: '0.8' }}>
                        • {getSituationMatrimonialeLibelle(agent.situation_matrimoniale)}
                      </span>
                    )}
                  </span>
                  Consultez l'historique de vos cotisations et téléchargez votre relevé de situation
                </p>
                
                <div className="suivi-cotisations__welcome-details">
                  <div className="suivi-cotisations__detail">
                    <strong>Matricule:</strong> {agent?.matricule_solde}
                  </div>
                  <div className="suivi-cotisations__detail">
                    <strong>N° Affiliation:</strong> {agent?.num_affiliation}
                  </div>
                  <div className="suivi-cotisations__detail">
                    <strong> Grade:</strong> {agent?.grade}
                  </div>
                  <div className="suivi-cotisations__detail">
                    <strong>Indice:</strong> {agent?.indice}
                  </div>
                  <div className="suivi-cotisations__detail">
                    <strong>Statut:</strong> 
                    <span className={`status ${agent?.statut?.toLowerCase()}`}>
                      {agent?.statut}
                    </span>
                  </div>
                </div>
              </div>
              
              <div className="suivi-cotisations__welcome-actions">
                <button 
                  onClick={() => window.location.href = '/dashboard'}
                  className="suivi-cotisations__dashboard-btn"
                  title="Retour au tableau de bord"
                >
                  ← Retour au tableau de bord
                </button>
                <button 
                  onClick={genererPDF}
                  disabled={generationPDF}
                  className="btn btn--primary"
                >
                  {generationPDF ? 'Génération...' : 'Télécharger Relevé de situation📄'}
                </button>
              </div>
            </div>
          )}

          {/* Statistiques rapides */}
          <section className="statistiques-grid">
            <div className="stat-card stat-card--primary">
              <div className="stat-icon">💰</div>
              <div className="stat-content">
                <h3>Total Cotisations</h3>
                <p className="stat-value">{statistiques?.total_cotisations}</p>
              </div>
            </div>
            
            <div className="stat-card stat-card--success">
              <div className="stat-icon">⏱️</div>
              <div className="stat-content">
                <h3>Durée de Service</h3>
                <p className="stat-value">{statistiques?.duree_totale}</p>
              </div>
            </div>
            
            <div className="stat-card stat-card--info">
              <div className="stat-icon">📊</div>
              <div className="stat-content">
                <h3>Cotisation Moyenne</h3>
                <p className="stat-value">{statistiques?.cotisation_moyenne}</p>
              </div>
            </div>
            
            <div className="stat-card stat-card--warning">
              <div className="stat-icon">✅</div>
              <div className="stat-content">
                <h3>Droit à Pension</h3>
                <p className={`stat-value ${statistiques?.droit_pension === 'OUI' ? 'success' : 'warning'}`}>
                  {statistiques?.droit_pension}
                </p>
              </div>
            </div>
          </section>

          {/* Graphique d'évolution */}
          <section className="graphique-section">
            <h2 className="section-title">Évolution des Cotisations (12 derniers mois)</h2>
            <div className="graphique-container">
              <ResponsiveContainer width="100%" height={300}>
                <LineChart data={graphique}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="mois" />
                  <YAxis 
  domain={[0, (dataMax) => {
    // Arrondir vers le haut à la dizaine de milliers la plus proche
    return Math.ceil(dataMax / 10000) * 10000;
  }]}
  ticks={(() => {
    // Calculer dynamiquement les ticks de 0 à max par paliers de 10K
    const maxData = Math.max(...graphique.map(item => item.retenue || 0));
    const maxTick = Math.ceil(maxData / 10000) * 10000;
    const ticks = [];
    for (let i = 0; i <= maxTick; i += 10000) {
      ticks.push(i);
    }
    return ticks;
  })()}
  tickFormatter={(value) => {
    if (value >= 1000) {
      return `${Math.round(value / 1000)}K`;
    }
    return value.toString();
  }}
/>
                  <Tooltip 
                    formatter={(value) => [formatMontant(value), 'Cotisation mensuelle']}
                    labelFormatter={(label) => `Mois: ${label}`}
                  />
                  <Line 
                    type="monotone" 
                    dataKey="retenue" 
                    stroke="#3B82F6" 
                    strokeWidth={2}
                    dot={{ fill: '#3B82F6', strokeWidth: 2, r: 4 }}
                  />
                </LineChart>
              </ResponsiveContainer>
            </div>
          </section>

          {/* Filtres et recherche */}
          <section className="filtres-section">
            <div className="filtres-container">
              <div className="filtre-group">
                <label htmlFor="annee-select">Année:</label>
                <select 
                  id="annee-select"
                  value={filtres.annee} 
                  onChange={(e) => handleFiltreChange('annee', e.target.value)}
                  className="form-select"
                >
                  <option value="">Toutes les années</option>
                  {filtresDisponibles?.annees_disponibles?.map(annee => (
                    <option key={annee} value={annee}>{annee}</option>
                  ))}
                </select>
              </div>

              <div className="recherche-group">
                <input
                  type="text"
                  placeholder="Rechercher dans les cotisations..."
                  value={recherche}
                  onChange={(e) => setRecherche(e.target.value)}
                  className="form-input"
                  onKeyPress={(e) => e.key === 'Enter' && handleRecherche()}
                />
                <button onClick={handleRecherche} className="btn btn--secondary">
                  🔍 Rechercher
                </button>
              </div>
            </div>
          </section>

          {/* Tableau des cotisations */}
          <section className="cotisations-section">
            <h2 className="section-title">
              Historique des Cotisations
              <span className="count">({pagination?.total || 0} périodes)</span>
            </h2>
            
            <div className="table-container">
              {loading ? (
                <div className="table-loading">
                  <div className="spinner"></div>
                  <p>Chargement des données...</p>
                </div>
              ) : (
                <table className="cotisations-table">
                  <thead>
                    <tr>
                      <th>Période</th>
                      <th>Position</th>
                      <th>Établissement</th>
                      <th>Grade</th>
                      <th>Indice</th>
                      <th>Retenue</th>
                      <th>Durée</th>
                      <th>Statut</th>
                    </tr>
                  </thead>
                  <tbody>
                    {cotisations?.map((cotisation, index) => (
                      <tr key={cotisation.id || index} className="cotisation-row">
                        <td>
                        {cotisation.periode_formatee || 
                        `${cotisation.date_debut || 'N/A'} - ${cotisation.date_fin || 'En cours'}`}
                      </td>
                        <td>{cotisation.position}</td>
                        <td className="etablissement-cell" title={cotisation.etablissement}>
                          {cotisation.etablissement?.substring(0, 30) || 'N/A'}
                          {cotisation.etablissement?.length > 30 && '...'}
                        </td>
                        <td>{cotisation.grade}</td>
                        <td>{cotisation.indice}</td>
                        <td className="montant">{cotisation.retenue_formatee}</td>
                        <td>{cotisation.duree_formatee}</td>
                        <td>
                          <span className={`status-badge status-${cotisation.statut?.toLowerCase()}`}>
                            {cotisation.statut?.toUpperCase()}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>

            {/* Pagination */}
            {pagination && pagination.last_page > 1 && (
              <div className="pagination">
                <button
                  onClick={() => handlePageChange(1)}
                  disabled={pagination.current_page === 1}
                  className="pagination-btn"
                  title="Première page"
                >
                  ⏮️
                </button>
                
                <button
                  onClick={() => handlePageChange(pagination.current_page - 1)}
                  disabled={pagination.current_page <= 1}
                  className="pagination-btn"
                >
                  ← Précédent
                </button>
                
                {(() => {
                  const pages = [];
                  const startPage = Math.max(1, pagination.current_page - 2);
                  const endPage = Math.min(pagination.last_page, pagination.current_page + 2);
                  
                  for (let i = startPage; i <= endPage; i++) {
                    pages.push(
                      <button
                        key={i}
                        onClick={() => handlePageChange(i)}
                        className={`pagination-btn ${
                          pagination.current_page === i ? 'pagination-btn--active' : ''
                        }`}
                      >
                        {i}
                      </button>
                    );
                  }
                  return pages;
                })()}
                
                <span className="pagination-info">
                  Page {pagination.current_page} sur {pagination.last_page}
                  ({pagination.from}-{pagination.to} sur {pagination.total})
                </span>
                
                <button
                  onClick={() => handlePageChange(pagination.current_page + 1)}
                  disabled={pagination.current_page >= pagination.last_page}
                  className="pagination-btn"
                >
                  Suivant →
                </button>
                
                <button
                  onClick={() => handlePageChange(pagination.last_page)}
                  disabled={pagination.current_page === pagination.last_page}
                  className="pagination-btn"
                  title="Dernière page"
                >
                  ⏭️
                </button>
              </div>
            )}
          </section>

        </div>
      </main>
    </div>
  );
};

export default SuiviCotisations;