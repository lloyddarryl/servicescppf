import React, { useState, useEffect } from 'react';
import { historiquePaiementService } from '../../services/api';
import Header from '../../components/Header';
import './HistoriquePaiements.css';

const HistoriquePaiements = () => {
  // États du composant
  const [historique, setHistorique] = useState([]);
  const [pagination, setPagination] = useState({});
  const [resume, setResume] = useState({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  
  // États pour les filtres - MODIFIÉ : annee vide par défaut
  const [filtres, setFiltres] = useState({
    annee: '', 
    mois: '',
    etat: '',
    per_page: 12
  });
  
  // États pour la recherche
  const [termesRecherche, setTermesRecherche] = useState('');
  const [modeRecherche, setModeRecherche] = useState(false);
  
  // États pour les statistiques
  const [statistiques, setStatistiques] = useState(null);
  const [afficherStatistiques, setAfficherStatistiques] = useState(false);

  // NOUVEAUX ÉTATS pour "Voir plus/moins"
  const [anneesVisibles, setAnneesVisibles] = useState(1);
  const [vueTableau, setVueTableau] = useState(true);
  const [loadingPDF, setLoadingPDF] = useState(false);

  // Charger l'historique au montage du composant
  useEffect(() => {
    chargerHistorique();
  }, [filtres]);

  // Fonction pour charger l'historique - MODIFIÉE
  const chargerHistorique = async () => {
    try {
      setLoading(true);
      setError('');
      
      const params = {};
      // MODIFICATION : Ne pas envoyer le paramètre annee si c'est vide (toutes les années)
      if (filtres.annee && filtres.annee !== '') params.annee = filtres.annee;
      if (filtres.mois) params.mois = filtres.mois;
      if (filtres.etat) params.etat = filtres.etat;
      if (filtres.per_page) params.per_page = filtres.per_page;

      const response = await historiquePaiementService.getHistorique(params);
      
      if (response.data.success) {
        setHistorique(response.data.data.paiements);
        setPagination(response.data.data.pagination);
        setResume(response.data.data.resume);
      } else {
        setError('Erreur lors du chargement de l\'historique');
      }
    } catch (err) {
      console.error('Erreur chargement historique:', err);
      setError('Impossible de charger l\'historique des paiements');
    } finally {
      setLoading(false);
    }
  };

  // NOUVELLE FONCTION POUR CALCULER LES ANNÉES DISPONIBLES SELON LE FILTRE
  const calculerAnneesDisponibles = () => {
    if (!historique || historique.length === 0) return 0;
    
    // Si on filtre par année spécifique, une seule année est disponible
    if (filtres.annee && filtres.annee !== '') {
      return 1;
    }
    
    // Sinon, calculer toutes les années uniques dans les données
    const anneesUniques = [...new Set(
      historique.map(paiement => new Date(paiement.date_paiement).getFullYear())
    )];
    
    return anneesUniques.length;
  };

  // Fonction pour charger les statistiques
  const chargerStatistiques = async () => {
    try {
      const params = {};
      if (filtres.annee && filtres.annee !== '') params.annee = filtres.annee;
      
      const response = await historiquePaiementService.getStatistiques(params);
      
      if (response.data.success) {
        setStatistiques(response.data.data);
      }
    } catch (err) {
      console.error('Erreur chargement statistiques:', err);
    }
  };

  // Gérer les changements de filtres
  const handleFiltreChange = (nom, valeur) => {
    setFiltres(prev => ({
      ...prev,
      [nom]: valeur
    }));
  };

  // Fonction de recherche
  const effectuerRecherche = async () => {
    if (!termesRecherche.trim()) {
      setModeRecherche(false);
      chargerHistorique();
      return;
    }

    try {
      setLoading(true);
      setModeRecherche(true);
      
      const response = await historiquePaiementService.rechercher(termesRecherche, {
        per_page: filtres.per_page
      });
      
      if (response.data.success) {
        setHistorique(response.data.data.paiements);
        setPagination(response.data.data.pagination);
      }
    } catch (err) {
      console.error('Erreur recherche:', err);
      setError('Erreur lors de la recherche');
    } finally {
      setLoading(false);
    }
  };

  // Télécharger le PDF - TOUS LES VERSEMENTS
  const telechargerPDF = async () => {
    try {
      setLoadingPDF(true);
      setError('');
      
      // Passer les filtres actuels pour le PDF
      const params = {};
      if (filtres.annee && filtres.annee !== '') params.annee = filtres.annee;
      if (filtres.mois) params.mois = filtres.mois;
      
      const response = await historiquePaiementService.telechargerPDF(params);
      
      const filename = historiquePaiementService.utils.genererNomFichier(
        'historique_versements',
        resume.retraite_info,
        params,
        'pdf'
      );
      
      historiquePaiementService.utils.downloadBlob(response.data, filename);
      
    } catch (err) {
      console.error('Erreur téléchargement PDF:', err);
      setError('Erreur lors du téléchargement du PDF');
    } finally {
      setLoadingPDF(false);
    }
  };

  // Afficher/masquer les statistiques
  const toggleStatistiques = () => {
    if (!statistiques && !afficherStatistiques) {
      chargerStatistiques();
    }
    setAfficherStatistiques(!afficherStatistiques);
  };

  // Changer de page
  const changerPage = (nouvellePage) => {
    if (nouvellePage >= 1 && nouvellePage <= pagination.last_page) {
      setFiltres(prev => ({ ...prev, page: nouvellePage }));
    }
  };

  // FONCTION pour obtenir la civilité selon la situation matrimoniale
  const obtenirCivilite = (retraiteInfo) => {
    if (!retraiteInfo) return 'M.';
    
    // Si le titre_civilite existe déjà, l'utiliser
    if (retraiteInfo.titre_civilite) {
      return retraiteInfo.titre_civilite === 'M' ? 'M.' : retraiteInfo.titre_civilite;
    }
    
    // Sinon, déterminer selon la situation matrimoniale et le genre
    const situationMatrimoniale = retraiteInfo.situation_matrimoniale?.toLowerCase();
    const genre = retraiteInfo.genre?.toLowerCase();
    
    // Pour les femmes
    if (genre === 'f' || genre === 'femme' || genre === 'féminin') {
      if (situationMatrimoniale === 'mariée' || situationMatrimoniale === 'mariee' || situationMatrimoniale === 'veuve') {
        return 'Mme';
      } else {
        return 'Mlle';
      }
    }
    
    // Pour les hommes (par défaut)
    return 'M.';
  };

  // Organiser les paiements par année
  const organiserPaiementsParAnnee = () => {
    const paiementsParAnnee = {};
    
    historique.forEach(paiement => {
      const annee = new Date(paiement.date_paiement).getFullYear();
      if (!paiementsParAnnee[annee]) {
        paiementsParAnnee[annee] = [];
      }
      paiementsParAnnee[annee].push(paiement);
    });

    // Trier les années du plus récent au moins récent
    const anneesTriees = Object.keys(paiementsParAnnee)
      .sort((a, b) => parseInt(b) - parseInt(a));

    return anneesTriees.map(annee => ({
      annee: parseInt(annee),
      paiements: paiementsParAnnee[annee].sort((a, b) => 
        new Date(b.date_paiement) - new Date(a.date_paiement)
      )
    }));
  };

  // Composant pour afficher un paiement (style liste)
  const PaiementItem = ({ paiement }) => (
    <div className="paiement-item">
      <div className="paiement-header">
        <div className="paiement-numero">
          <span className="label">N° {paiement.numero_titre}</span>
          <span 
            className="statut-badge"
            style={{ backgroundColor: historiquePaiementService.utils.getCouleurEtat(paiement.etat_paiement) }}
          >
            {historiquePaiementService.utils.getIconeEtat(paiement.etat_paiement)}
            {historiquePaiementService.utils.getLibelleEtat(paiement.etat_paiement)}
          </span>
        </div>
        <div className="paiement-date">
          {historiquePaiementService.utils.formatDate(paiement.date_paiement)}
        </div>
      </div>
      
      <div className="paiement-content">
        <div className="paiement-montant">
          <span className="montant-value">
            {historiquePaiementService.utils.formatMontant(paiement.montant_net)}
          </span>
        </div>
        
        <div className="paiement-details">
          <div className="detail-item">
            <span className="detail-label">Mode :</span>
            <span className="detail-value">{paiement.mode_reglement}</span>
          </div>
          <div className="detail-item">
            <span className="detail-label">Régime :</span>
            <span className="detail-value">{paiement.regime}</span>
          </div>
        </div>
      </div>
    </div>
  );

  // Composant tableau pour une année
  const TableauAnnee = ({ annee, paiements }) => {
    const totalMontant = paiements.reduce((sum, p) => sum + (parseFloat(p.montant_net) || 0), 0);
    
    return (
      <div className="annee-section">
        <div className="annee-header">
          <h4>Année {annee}</h4>
          <div className="annee-stats">
            <span className="stat-item">
              {paiements.length} versement{paiements.length > 1 ? 's' : ''}
            </span>
            <span className="stat-item">
              {historiquePaiementService.utils.formatMontant(totalMontant)}
            </span>
          </div>
        </div>

        <div className="tableau-wrapper">
          <table className="paiements-tableau">
            <thead>
              <tr>
                <th>Date</th>
                <th>N° Titre</th>
                <th>État</th>
                <th>Montant</th>
                <th>Mode</th>
                <th>Régime</th>
              </tr>
            </thead>
            <tbody>
              {paiements.map(paiement => (
                <tr key={paiement.id} className={`paiement-row etat-${paiement.etat_paiement}`}>
                  <td className="date-cell">
                    {historiquePaiementService.utils.formatDate(paiement.date_paiement)}
                  </td>
                  <td className="titre-cell">
                    <strong>{paiement.numero_titre}</strong>
                  </td>
                  <td className="etat-cell">
                    <span 
                      className="statut-badge"
                      style={{ backgroundColor: historiquePaiementService.utils.getCouleurEtat(paiement.etat_paiement) }}
                    >
                      {historiquePaiementService.utils.getIconeEtat(paiement.etat_paiement)}
                      {historiquePaiementService.utils.getLibelleEtat(paiement.etat_paiement)}
                    </span>
                  </td>
                  <td className="montant-cell">
                    <strong className="montant-value">
                      {historiquePaiementService.utils.formatMontant(paiement.montant_net)}
                    </strong>
                  </td>
                  <td className="mode-cell">
                    {paiement.mode_reglement}
                  </td>
                  <td className="regime-cell">
                    {paiement.regime}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    );
  };

  // Composant pour les statistiques
  const StatistiquesPanel = () => {
    if (!statistiques) return null;

    const stats = statistiques.statistiques_generales;
    
    return (
      <div className="statistiques-panel">
        <h3>Statistiques</h3>
        <div className="stats-grid">
          <div className="stat-card">
            <div className="stat-icon">💰</div>
            <div className="stat-content">
              <div className="stat-value">
                {historiquePaiementService.utils.formatMontant(stats.montant_total)}
              </div>
              <div className="stat-label">Total versé</div>
            </div>
          </div>
          
          <div className="stat-card">
            <div className="stat-icon">📊</div>
            <div className="stat-content">
              <div className="stat-value">{stats.total_paiements}</div>
              <div className="stat-label">Nombre de paiements</div>
            </div>
          </div>
          
          <div className="stat-card">
            <div className="stat-icon">📈</div>
            <div className="stat-content">
              <div className="stat-value">
                {historiquePaiementService.utils.formatMontant(stats.moyenne_mensuelle)}
              </div>
              <div className="stat-label">Moyenne mensuelle</div>
            </div>
          </div>
        </div>

        {statistiques.evolution_mensuelle && (
          <div className="evolution-mensuelle">
            <h4>Évolution mensuelle</h4>
            <div className="mois-grid">
              {statistiques.evolution_mensuelle.map(mois => (
                <div 
                  key={mois.mois} 
                  className={`mois-item ${mois.verse ? 'verse' : 'non-verse'}`}
                >
                  <div className="mois-nom">{mois.nom_mois?.substring(0, 3)}</div>
                  <div className="mois-montant">
                    {mois.verse 
                      ? historiquePaiementService.utils.formatMontant(mois.montant)
                      : "—"
                    }
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    );
  };

  // NOUVEAU COMPOSANT pour les filtres
  const RenduFiltres = () => (
    <section className="filtres-section">
      <div className="filtres-row">
        <div className="filtre-group">
          <label>Année :</label>
          <select 
            value={filtres.annee} 
            onChange={(e) => handleFiltreChange('annee', e.target.value)}
          >
            {/* NOUVEAU : Option "Toutes les années" */}
            <option value="">Toutes les années</option>
            {resume.annees_disponibles?.map(annee => (
              <option key={annee} value={annee}>{annee}</option>
            ))}
          </select>
        </div>

        <div className="filtre-group">
          <label>Mois :</label>
          <select 
            value={filtres.mois} 
            onChange={(e) => handleFiltreChange('mois', e.target.value)}
          >
            {historiquePaiementService.utils.genererOptionsMois().map(option => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        </div>

        <div className="filtre-group">
          <label>État :</label>
          <select 
            value={filtres.etat} 
            onChange={(e) => handleFiltreChange('etat', e.target.value)}
          >
            {historiquePaiementService.utils.genererOptionsEtats().map(option => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        </div>

        <div className="filtre-group">
          <label>Par page :</label>
          <select 
            value={filtres.per_page} 
            onChange={(e) => handleFiltreChange('per_page', parseInt(e.target.value))}
          >
            <option value={12}>12</option>
            <option value={24}>24</option>
            <option value={36}>36</option>
          </select>
        </div>
      </div>

      {/* Barre de recherche */}
      <div className="recherche-row">
        <div className="recherche-group">
          <input
            type="text"
            placeholder="Rechercher par numéro, mode de paiement..."
            value={termesRecherche}
            onChange={(e) => setTermesRecherche(e.target.value)}
            onKeyPress={(e) => e.key === 'Enter' && effectuerRecherche()}
          />
          <button 
            type="button"
            onClick={effectuerRecherche}
            className="btn-recherche"
          >
            Rechercher
          </button>
          {modeRecherche && (
            <button 
              type="button"
              onClick={() => {
                setModeRecherche(false);
                setTermesRecherche('');
                chargerHistorique();
              }}
              className="btn-reset"
            >
              Réinitialiser
            </button>
          )}
        </div>
      </div>
    </section>
  );

  if (loading && historique.length === 0) {
    return (
      <div className="historique-paiements">
        <Header />
        <div className="loading">
          <div className="spinner"></div>
          <p>Chargement de votre historique des paiements...</p>
        </div>
      </div>
    );
  }

  const paiementsParAnnee = organiserPaiementsParAnnee();
  const anneesDisponibles = calculerAnneesDisponibles(); // NOUVEAU : utiliser la fonction calculée

  return (
    <div className="historique-paiements">
      <Header />
      
      <main className="historique-main">
        <div className="historique-container">
          
          {/* En-tête avec civilité corrigée */}
          <section className="historique-header">
            <div className="header-content">
              <h1>Votre historique des Paiements</h1>
              {resume.retraite_info && (
                <div className="retraite-info">
                  <p>
                    <strong>
                      {obtenirCivilite(resume.retraite_info)} {resume.retraite_info.nom_complet}
                    </strong>
                  </p>
                  <p>Pension N° {resume.retraite_info.numero_pension}</p>
                </div>
              )}
            </div>
            
            <div className="header-actions">
              <button 
                className="btn-statistiques"
                onClick={toggleStatistiques}
                type="button"
              >
                📊 {afficherStatistiques ? 'Masquer' : 'Voir'} Statistiques
              </button>
              <button 
                className="btn-export"
                onClick={telechargerPDF}
                disabled={loadingPDF}
                type="button"
              >
                📄 {loadingPDF ? 'Génération...' : 'Télécharger votre historique'}
              </button>
            </div>
          </section>

          {/* Statistiques */}
          {afficherStatistiques && <StatistiquesPanel />}

          {/* Filtres et recherche */}
          <RenduFiltres />

          {/* Messages d'état */}
          {error && (
            <div className="error-message">
              <div className="error-content">
                <span className="error-icon">⚠️</span>
                <span>{error}</span>
                <button onClick={() => setError('')} className="error-close">×</button>
              </div>
            </div>
          )}

          {modeRecherche && (
            <div className="info-message">
              <span>Résultats de recherche pour : "{termesRecherche}"</span>
              <span>({pagination.total || 0} résultat{(pagination.total || 0) > 1 ? 's' : ''})</span>
            </div>
          )}

          {/* Résumé de la période */}
          {!modeRecherche && resume.statistiques_annee && (
            <section className="resume-periode">
              <h3>Résumé - {historiquePaiementService.utils.formatPeriode(filtres)}</h3>
              <div className="resume-stats">
                <div className="resume-item">
                  <span className="resume-label">Paiements versés :</span>
                  <span className="resume-value">{resume.statistiques_annee.total_paiements}</span>
                </div>
                <div className="resume-item">
                  <span className="resume-label">Montant total :</span>
                  <span className="resume-value">
                    {historiquePaiementService.utils.formatMontant(resume.statistiques_annee.montant_total)}
                  </span>
                </div>
                <div className="resume-item">
                  <span className="resume-label">Moyenne mensuelle :</span>
                  <span className="resume-value">
                    {historiquePaiementService.utils.formatMontant(resume.statistiques_annee.moyenne_mensuelle)}
                  </span>
                </div>
              </div>
            </section>
          )}

          {/* SECTION CORRIGÉE : Vue avec Voir plus/moins */}
          <section className="paiements-section">
            {/* Boutons de contrôle TOUJOURS VISIBLES si on a des données */}
            {!loading && historique.length > 0 && (
              <div className="view-controls-header">
                <div className="view-toggle">
                   <button 
                    className={`btn-toggle ${vueTableau ? 'active' : ''}`}
                    onClick={() => setVueTableau(true)}
                  >
                    Vue Tableau par Année
                  </button>
                  <button 
                    className={`btn-toggle ${!vueTableau ? 'active' : ''}`}
                    onClick={() => setVueTableau(false)}
                  >
                    Vue Liste
                  </button>
                 
                </div>
                
                {/* LOGIQUE CORRIGÉE : Afficher les contrôles selon le contexte */}
                <div className="annees-controls">
                  <span>
                    {filtres.annee && filtres.annee !== '' 
                      ? `Année ${filtres.annee} sélectionnée`
                      : `Afficher ${anneesVisibles}/${anneesDisponibles} année${anneesDisponibles > 1 ? 's' : ''}`
                    }
                  </span>
                  
                  {/* Boutons seulement si "Toutes les années" ET plus d'une année disponible */}
                  {(!filtres.annee || filtres.annee === '') && anneesDisponibles > 1 && (
                    <>
                      {anneesVisibles < anneesDisponibles && (
                        <button 
                          className="btn-voir-plus"
                          onClick={() => setAnneesVisibles(prev => Math.min(prev + 1, anneesDisponibles))}
                        >
                          Voir plus ({anneesDisponibles - anneesVisibles} restante{(anneesDisponibles - anneesVisibles) > 1 ? 's' : ''})
                        </button>
                      )}
                      
                      {anneesVisibles > 1 && (
                        <button 
                          className="btn-voir-moins"
                          onClick={() => setAnneesVisibles(prev => Math.max(prev - 1, 1))}
                        >
                          Voir moins
                        </button>
                      )}
                    </>
                  )}
                  
                  {/* Messages informatifs */}
                  {(filtres.annee && filtres.annee !== '') && (
                    <span className="info-message">Filtré par année - utilisez "Toutes les années" pour voir plus</span>
                  )}
                  
                  {(!filtres.annee || filtres.annee === '') && anneesDisponibles <= 1 && (
                    <span className="info-message">Une seule année disponible</span>
                  )}
                </div>
              </div>
            )}

            {/* Contenu selon l'état */}
            {loading ? (
              <div className="loading-inline">
                <div className="spinner-small"></div>
                <span>Chargement...</span>
              </div>
            ) : historique.length > 0 ? (
              <>
                {/* Vue Liste */}
                {!vueTableau && (
                  <>
                    <div className="paiements-liste">
                      {/* MODIFICATION : Si "Toutes les années", organiser par année aussi en vue liste */}
                      {(!filtres.annee || filtres.annee === '') && anneesDisponibles > 1 ? (
                        // Vue liste organisée par années
                        paiementsParAnnee.slice(0, anneesVisibles).map(({ annee, paiements }) => (
                          <div key={annee} className="annee-groupe-liste">
                            <div className="annee-separator">
                              <h4>Année {annee}</h4>
                              <span>{paiements.length} versement{paiements.length > 1 ? 's' : ''}</span>
                            </div>
                            {paiements.map(paiement => (
                              <PaiementItem key={paiement.id} paiement={paiement} />
                            ))}
                          </div>
                        ))
                      ) : (
                        // Vue liste normale (année filtrée)
                        historique.map(paiement => (
                          <PaiementItem key={paiement.id} paiement={paiement} />
                        ))
                      )}
                    </div>

                    {/* Pagination pour vue liste (seulement si année filtrée) */}
                    {(filtres.annee && filtres.annee !== '') && pagination.last_page > 1 && (
                      <div className="pagination">
                        <div className="pagination-info">
                          Affichage de {pagination.from} à {pagination.to} sur {pagination.total} résultats
                        </div>
                        
                        <div className="pagination-controls">
                          <button 
                            onClick={() => changerPage(pagination.current_page - 1)}
                            disabled={pagination.current_page <= 1}
                            className="btn-page"
                          >
                            ← Précédent
                          </button>
                          
                          <div className="pages-numbers">
                            {Array.from({ length: Math.min(5, pagination.last_page) }, (_, i) => {
                              const page = i + Math.max(1, pagination.current_page - 2);
                              if (page > pagination.last_page) return null;
                              
                              return (
                                <button
                                  key={page}
                                  onClick={() => changerPage(page)}
                                  className={`btn-page ${page === pagination.current_page ? 'active' : ''}`}
                                >
                                  {page}
                                </button>
                              );
                            })}
                          </div>
                          
                          <button 
                            onClick={() => changerPage(pagination.current_page + 1)}
                            disabled={pagination.current_page >= pagination.last_page}
                            className="btn-page"
                          >
                            Suivant →
                          </button>
                        </div>
                      </div>
                    )}
                  </>
                )}

                {/* Vue Tableau par Année */}
                {vueTableau && (
                  <div className="annees-container">
                    {paiementsParAnnee.slice(0, anneesVisibles).map(({ annee, paiements }) => (
                      <TableauAnnee key={annee} annee={annee} paiements={paiements} />
                    ))}
                  </div>
                )}
              </>
            ) : (
              <div className="empty-state">
                <div className="empty-icon">🔭</div>
                <h3>
                  {modeRecherche 
                    ? historiquePaiementService.utils.getMessageInfo('recherche_vide', { terme: termesRecherche })
                    : historiquePaiementService.utils.getMessageInfo('aucun_paiement')
                  }
                </h3>
                <p>
                  {modeRecherche 
                    ? 'Essayez avec d\'autres termes de recherche'
                    : 'Aucun paiement n\'a été trouvé pour cette période'
                  }
                </p>
                {modeRecherche && (
                  <button 
                    onClick={() => {
                      setModeRecherche(false);
                      setTermesRecherche('');
                      chargerHistorique();
                    }}
                    className="btn-retour"
                  >
                    Voir tous les paiements
                  </button>
                )}
              </div>
            )}
          </section>

          {/* Graphique mensuel (seulement si année spécifique) */}
          {!modeRecherche && !vueTableau && (filtres.annee && filtres.annee !== '') && resume.paiements_par_mois && (
            <section className="graphique-section">
              <h3>Répartition mensuelle - {filtres.annee}</h3>
              <div className="graphique-mensuel">
                {resume.paiements_par_mois.map(mois => (
                  <div 
                    key={mois.mois}
                    className={`mois-bar ${mois.verse ? 'verse' : 'non-verse'}`}
                    title={`${mois.nom_mois} : ${mois.verse ? historiquePaiementService.utils.formatMontant(mois.montant) : 'Non versé'}`}
                  >
                    <div className="mois-label">{mois.nom_mois?.substring(0, 3)}</div>
                    <div className="mois-montant">
                      {mois.verse 
                        ? historiquePaiementService.utils.formatMontant(mois.montant)
                        : "—"
                      }
                    </div>
                    <div className={`mois-indicator ${mois.verse ? 'success' : 'empty'}`}></div>
                  </div>
                ))}
              </div>
            </section>
          )}

        </div>
      </main>
    </div>
  );
};

export default HistoriquePaiements;