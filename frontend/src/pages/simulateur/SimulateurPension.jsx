// File: frontend/src/pages/simulateur/SimulateurPension.jsx

import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../../components/Header';
import { utils, pensionSimulatorService } from '../../services/api';
import './SimulateurPension.css';

const SimulateurPension = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('profile');
  const [userProfile, setUserProfile] = useState(null);
  const [simulationData, setSimulationData] = useState(null);
  const [simulationHistory, setSimulationHistory] = useState([]);
  const [customSimulation, setCustomSimulation] = useState({
    indice: '',
    dateRetraite: ''
  });
  const [error, setError] = useState(null);

  // Fonction pour déterminer la civilité
  const getTitle = (sexe, situationMatrimoniale) => {
    if (sexe === 'F') {
      if (situationMatrimoniale === 'Marié(e)' || situationMatrimoniale === 'Mariée') {
        return 'Mme';
      }
      return 'Mlle';
    }
    return 'M.';
  };

  // ✅ CORRECTION : Ajouter espace entre prénom et nom
  const getFullName = (userProfile) => {
    if (!userProfile) return '';
    const title = getTitle(userProfile.sexe, userProfile.situationMatrimoniale);
    const prenoms = userProfile.prenoms || '';
    const nom = userProfile.nom || '';
    return `${title} ${prenoms} ${nom}`.trim();
  };

  const loadHistory = useCallback(async () => {
    try {
      const response = await pensionSimulatorService.getHistory();
      if (response.data.success) {
        setSimulationHistory(response.data.simulations);
      }
    } catch (error) {
      console.error('Erreur historique:', error);
      // Ne pas afficher d'erreur pour l'historique, c'est optionnel
    }
  }, []);

  const simulatePension = useCallback(async (indiceCustom = null, dateRetraiteCustom = null) => {
    try {
      setError(null);
      const requestData = {};
      if (indiceCustom) requestData.indice = indiceCustom;
      if (dateRetraiteCustom) requestData.date_retraite = dateRetraiteCustom;

      const response = await pensionSimulatorService.simulate(requestData);

      if (response.data.success) {
        setSimulationData(response.data.simulation);
        await loadHistory(); // Recharger l'historique après simulation
      } else {
        setError(response.data.message || 'Erreur lors de la simulation');
      }
    } catch (error) {
      console.error('Erreur simulation:', error);
      setError(
        error.response?.data?.message || 
        'Erreur lors de la simulation de la pension'
      );
    }
  }, [loadHistory]);

  const loadInitialData = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      
      const profileResponse = await pensionSimulatorService.getProfile();
      
      if (profileResponse.data.success) {
        setUserProfile(profileResponse.data.profile);
        // Lancer une simulation automatique avec les données du profil
        await simulatePension(profileResponse.data.profile.indice);
      } else {
        setError(profileResponse.data.message || 'Erreur lors du chargement du profil');
      }
    } catch (error) {
      console.error('Erreur lors du chargement des données :', error);
      setError(
        error.response?.data?.message || 
        'Erreur lors du chargement des données du simulateur'
      );
    } finally {
      setLoading(false);
    }
  }, [simulatePension]);

  useEffect(() => {
    if (!utils.isAuthenticated()) {
      navigate('/services');
      return;
    }
    loadInitialData();
  }, [navigate, loadInitialData]);

  const handleCustomSimulation = async (e) => {
    e.preventDefault();
    const indice = customSimulation.indice || userProfile?.indice;
    const dateRetraite = customSimulation.dateRetraite || null;
    await simulatePension(indice, dateRetraite);
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
  };

  const calculateAge = (birthDate) => {
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
      age--;
    }
    return age;
  };

  const calculateServiceYears = (startDate) => {
    const today = new Date();
    const start = new Date(startDate);
    return today.getFullYear() - start.getFullYear();
  };

  if (loading) {
    return (
      <div className="simulateur-pension">
        <Header />
        <div className="loading-container">
          <div className="spinner"></div>
          <p>Chargement du simulateur...</p>
        </div>
      </div>
    );
  }

  if (error && !userProfile) {
    return (
      <div className="simulateur-pension">
        <Header />
        <div className="loading-container">
          <div className="alert alert-warning">
            <p>⚠ {error}</p>
            <button 
              className="simulate-button" 
              onClick={() => loadInitialData()}
              style={{ marginTop: '1rem' }}
            >
              Réessayer
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="simulateur-pension">
      <Header />
      
      <main className="simulateur-main">
        <div className="simulateur-container">
          
          {/* En-tête personnalisé */}
          <div className="simulateur-header">
            <div className="header-content">
              <h1 className="simulateur-title">
                <span className="title-icon">🧮</span>
                Simulateur de Pension CPPF
              </h1>
              {userProfile && (
                <div className="user-welcome">
                  Bienvenue {getFullName(userProfile)}
                </div>
              )}
              <p className="simulateur-subtitle">
                Estimez votre future pension de retraite en tant qu'agent de l'État
              </p>
            </div>
            
            <button 
              className="back-button"
              onClick={() => navigate('/dashboard')}
            >
              ← Retour au tableau de bord
            </button>
          </div>

          {/* Affichage des erreurs */}
          {error && (
            <div className="alert alert-warning" style={{ marginBottom: '2rem' }}>
              ⚠️ {error}
            </div>
          )}

          {/* Navigation */}
          <div className="simulateur-nav">
            <button
              className={`nav-button ${activeTab === 'profile' ? 'active' : ''}`}
              onClick={() => setActiveTab('profile')}
            >
              👤 Mes Informations
            </button>
            <button
              className={`nav-button ${activeTab === 'simulation' ? 'active' : ''}`}
              onClick={() => setActiveTab('simulation')}
            >
              🧮 Simulation
            </button>
            <button
              className={`nav-button ${activeTab === 'history' ? 'active' : ''}`}
              onClick={() => setActiveTab('history')}
            >
              📊 Historique
            </button>
          </div>

          {/* Contenu */}
          <div className="simulateur-content">
            
            {/* Onglet Profil */}
            {activeTab === 'profile' && userProfile && (
              <div className="profile-section">
                
                {/* Informations personnelles enrichies */}
                <div className="profile-card">
                  <div className="card-header">
                    <h2>👤 Informations Personnelles</h2>
                  </div>
                  <div className="card-content">
                    <div className="profile-grid">
                      <div className="profile-item">
                        <label>Nom complet:</label>
                        <span>{userProfile.prenoms} {userProfile.nom}</span>
                      </div>
                      <div className="profile-item">
                        <label>Matricule:</label>
                        <span>{userProfile.matricule}</span>
                      </div>
                      <div className="profile-item">
                        <label>Âge:</label>
                        <span>{calculateAge(userProfile.dateNaissance)} ans</span>
                      </div>
                      <div className="profile-item">
                        <label>Date de naissance:</label>
                        <span>{new Date(userProfile.dateNaissance).toLocaleDateString('fr-FR')}</span>
                      </div>
                      <div className="profile-item">
                        <label>Sexe:</label>
                        <span>{userProfile.sexe === 'M' ? 'Masculin' : 'Féminin'}</span>
                      </div>
                      <div className="profile-item">
                        <label>Situation matrimoniale:</label>
                        <span>{userProfile.situationMatrimoniale}</span>
                      </div>
                      <div className="profile-item">
                        <label>Téléphone:</label>
                        <span>{userProfile.telephone || 'Non renseigné'}</span>
                      </div>
                      <div className="profile-item">
                        <label>Email:</label>
                        <span>{userProfile.email || 'Non renseigné'}</span>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Informations professionnelles */}
                <div className="profile-card">
                  <div className="card-header">
                    <h2>🏢 Informations Professionnelles</h2>
                  </div>
                  <div className="card-content">
                    <div className="profile-grid">
                      <div className="profile-item">
                        <label>Poste actuel:</label>
                        <span>{userProfile.posteActuel}</span>
                      </div>
                      <div className="profile-item">
                        <label>Direction:</label>
                        <span>{userProfile.direction}</span>
                      </div>
                      <div className="profile-item">
                        <label>Grade:</label>
                        <span>{userProfile.grade}</span>
                      </div>
                      <div className="profile-item">
                        <label>Date d'embauche:</label>
                        <span>{new Date(userProfile.dateEmbauche).toLocaleDateString('fr-FR')}</span>
                      </div>
                      <div className="profile-item">
                        <label>Années de service:</label>
                        <span>{calculateServiceYears(userProfile.dateEmbauche)} ans</span>
                      </div>
                      <div className="profile-item">
                        <label>Indice:</label>
                        <span>{userProfile.indice}</span>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Rémunération avec bonifications */}
                <div className="profile-card salary-card">
                  <div className="card-header">
                    <h2>💰 Rémunération et Bonifications</h2>
                  </div>
                  <div className="card-content">
                    <div className="salary-display">
                      <div className="salary-row">
                        <label>Solde de Base (SB):</label>
                        <div className="salary-amount">{formatCurrency(userProfile.indice * 500)}</div>
                        <div className="salary-formula">Indice {userProfile.indice} × 500</div>
                      </div>
                      
                      {userProfile.montant_bonifications > 0 && (
                        <div className="salary-row">
                          <label>Bonifications (BD):</label>
                          <div className="salary-amount bonus">{formatCurrency(userProfile.montant_bonifications)}</div>
                          <div className="salary-formula">Montant de vos bonifications</div>
                        </div>
                      )}
                      
                      <div className="salary-row total">
                        <label>Solde Modifié (SBm):</label>
                        <div className="salary-amount">
                          {formatCurrency((userProfile.indice * 500) + (userProfile.montant_bonifications || 0))}
                          </div>
                        <div className="salary-formula">SB + Bonifications</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Onglet Simulation */}
            {activeTab === 'simulation' && (
              <div className="simulation-section">
                
                {/* Simulation personnalisée */}
                <div className="simulation-card">
                  <div className="card-header">
                    <h2>🎯 Simulation Personnalisée</h2>
                  </div>
                  <div className="card-content">
                    <form onSubmit={handleCustomSimulation} className="custom-form">
                      <div className="form-row">
                        <div className="form-group">
                          <label>Indice à simuler:</label>
                          <input
                            type="number"
                            value={customSimulation.indice}
                            onChange={(e) => setCustomSimulation(prev => ({
                              ...prev,
                              indice: e.target.value
                            }))}
                            placeholder={userProfile?.indice}
                            min="400"
                            max="1500"
                          />
                        </div>
                        <div className="form-group">
                          <label>Date de retraite:</label>
                          <input
                            type="date"
                            value={customSimulation.dateRetraite}
                            onChange={(e) => setCustomSimulation(prev => ({
                              ...prev,
                              dateRetraite: e.target.value
                            }))}
                          />
                        </div>
                        <button type="submit" className="simulate-button">
                          Simuler
                        </button>
                      </div>
                    </form>
                  </div>
                </div>

                {/* Résultats de simulation */}
                {simulationData && (
                  <div className="results-section">
                    
                    {/* Statistiques principales avec 5 cartes */}
                    <div className="stats-grid">
                      <div className="stat-card primary">
                        <div className="stat-icon">📅</div>
                        <div className="stat-content">
                          <div className="stat-label">Date de Retraite</div>
                          <div className="stat-value">{simulationData.dateRetraitePrevisionnelle}</div>
                          <div className="stat-subtitle">À {simulationData.ageRetraite} ans</div>
                        </div>
                      </div>

                      <div className="stat-card success">
                        <div className="stat-icon">⏱️</div>
                        <div className="stat-content">
                          <div className="stat-label">Durée de Service</div>
                          <div className="stat-value">
                            {Math.round(simulationData.dureeServiceAnnuite)} ans
                          </div>
                          <div className="stat-subtitle">Principe d'annuité</div>
                        </div>
                      </div>

                      <div className="stat-card info">
                        <div className="stat-icon">📊</div>
                        <div className="stat-content">
                          <div className="stat-label">Taux de Liquidation</div>
                          <div className="stat-value">{simulationData.tauxLiquidation}%</div>
                          <div className="stat-subtitle">Du solde final</div>
                        </div>
                      </div>

                      <div className="stat-card warning">
                        <div className="stat-icon">📈</div>
                        <div className="stat-content">
                          <div className="stat-label">Coefficient de Progressivité</div>
                          <div className="stat-value">{simulationData.coefficientTemporel}%</div>
                          <div className="stat-subtitle">Article 94</div>
                        </div>
                      </div>

                      <div className="stat-card primary">
                        <div className="stat-icon">💰</div>
                        <div className="stat-content">
                          <div className="stat-label">Pension Estimée</div>
                          <div className="stat-value">{formatCurrency(simulationData.pensionTotale)}</div>
                          <div className="stat-subtitle">Par mois</div>
                        </div>
                      </div>
                    </div>

                    {/* Détails du calcul selon Article 94 avec écrêtement */}
                    <div className="calculation-details">
                      <div className="card-header">
                        <h3>🧮 Détail du Calcul (Article 94 avec écrêtement)</h3>
                      </div>
                      <div className="calculation-steps">
                        
                        {/* Étape 1: Solde de base */}
                        <div className="step">
                          <div className="step-label"> Solde de base (SB):</div>
                          <div className="step-value">{formatCurrency(simulationData.soldeBase)}</div>
                          <div className="step-formula">Indice {simulationData.indiceSimule} × 500</div>
                        </div>

                        {/* Étape 2: Bonifications */}
                        {simulationData.bonificationsBD > 0 && (
                          <div className="step">
                            <div className="step-label"> Bonifications:</div>
                            <div className="step-value">{formatCurrency(simulationData.bonificationsBD)}</div>
                            <div className="step-formula">Montant de vos bonifications </div>
                          </div>
                        )}

                        {/* Étape 3: Solde modifié */}
                        <div className="step">
                          <div className="step-label"> Solde modifié (SBm):</div>
                          <div className="step-value">{formatCurrency(simulationData.soldeModifie)}</div>
                          <div className="step-formula">
                            SB + Bonifications = {formatCurrency(simulationData.soldeBase)} + {formatCurrency(simulationData.bonificationsBD)}
                          </div>
                        </div>

                        {/* Étape 4: Écrêtement si applicable */}
                        {simulationData.ecretementApplique && (
                          <>
                            <div className="step ecretement">
                              <div className="step-label"> Écrêtement appliqué:</div>
                              <div className="step-value">-{formatCurrency(simulationData.montantEcrete)}</div>
                              <div className="step-formula">
                                SBm {'>'} {formatCurrency(simulationData.seuilEcretement)} → Écrêtement
                              </div>
                            </div>
                            <div className="step">
                              <div className="step-label"> Solde final après écrêtement:</div>
                              <div className="step-value">{formatCurrency(simulationData.soldeFinal)}</div>
                              <div className="step-formula">
                                (({formatCurrency(simulationData.soldeModifie)} - {formatCurrency(simulationData.seuilEcretement)}) ÷ 2) + {formatCurrency(simulationData.seuilEcretement)}
                              </div>
                            </div>
                          </>
                        )}

                        {!simulationData.ecretementApplique && (
                          <div className="step">
                            <div className="step-label"> Solde final:</div>
                            <div className="step-value">{formatCurrency(simulationData.soldeFinal)}</div>
                            <div className="step-formula">
                              Pas d'écrêtement (≤ {formatCurrency(simulationData.seuilEcretement)})
                            </div>
                          </div>
                        )}

                        {/* Étape: Taux de liquidation */}
                        <div className="step">
                          <div className="step-label">Taux de liquidation:</div>
                          <div className="step-value">{simulationData.tauxLiquidation}%</div>
                          <div className="step-formula">
                            {simulationData.dureeServiceRetraite < 15 
                              ? "Moins de 15 ans - Pas de pension"
                              : `${Math.round(simulationData.dureeServiceAnnuite)} années (annuité) × 1,8% = ${simulationData.tauxLiquidation}%`
                            }
                          </div>
                        </div>

                        {/* Étape: Pension de base */}
                        <div className="step">
                          <div className="step-label">Pension de base:</div>
                          <div className="step-value">{formatCurrency(simulationData.pensionBase)}</div>
                          <div className="step-formula">
                            Solde final × Taux = {formatCurrency(simulationData.soldeFinal)} × {simulationData.tauxLiquidation}%
                          </div>
                        </div>

                        {/* Étape: Coefficient temporel */}
                        <div className="step">
                          <div className="step-label">Coefficient {simulationData.anneePension}:</div>
                          <div className="step-value">{simulationData.coefficientTemporel}%</div>
                          <div className="step-formula">Article 94 - Coefficient temporel</div>
                        </div>

                        {/* Étape finale: Pension totale */}
                        <div className="step total">
                          <div className="step-label">Pension totale finale:</div>
                          <div className="step-value">{formatCurrency(simulationData.pensionTotale)}</div>
                          <div className="step-formula">
                            {formatCurrency(simulationData.pensionBase)} × {simulationData.coefficientTemporel}%
                          </div>
                        </div>
                      </div>
                    </div>

                    {/* Alertes Article 94 */}
                    <div className="alerts-section">
                      {!simulationData.eligible && (
                        <div className="alert alert-warning">
                          ⚠️ Attention: Avec {Math.round(simulationData.dureeServiceAnnuite)} années de service, 
                          vous n'êtes pas encore éligible à une pension (minimum 15 ans requis).
                        </div>
                      )}
                      
                      {simulationData.ecretementApplique && (
                        <div className="alert alert-info">
                          ✂️ Écrêtement appliqué: Votre solde modifié ({formatCurrency(simulationData.soldeModifie)}) 
                          dépasse le seuil de {formatCurrency(simulationData.seuilEcretement)}, 
                          réduction de {formatCurrency(simulationData.montantEcrete)}.
                        </div>
                      )}
                      
                      <div className="alert alert-info">
                        ℹ️ Calcul selon Article 94 : Formule {simulationData.methodeCalcul || 'Années × 1,8%'} 
                        avec coefficient temporel de {simulationData.coefficientTemporel}% pour {simulationData.anneePension}.
                      </div>

                      <div className="alert alert-info">
                        📈 Évolution des coefficients : 2025=91%, 2026=94%, 2027=96%, 2028=98%, 2029+=100%
                      </div>

                      <div className="alert alert-info">
                        ⚖️ Principe d'annuité appliqué : &lt;6 mois = +0,5 an | ≥6 mois = +1 an complet
                      </div>
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* Onglet Historique */}
            {activeTab === 'history' && (
              <div className="history-section">
                <div className="history-card">
                  <div className="card-header">
                    <h2>📊 Historique des Simulations</h2>
                  </div>
                  <div className="card-content">
                    {simulationHistory.length > 0 ? (
                      <div className="history-list">
                          {simulationHistory.map(sim => ( 
                          <div key={sim.id} className="history-item">
                            <div className="history-date">{sim.date}</div>
                            <div className="history-details">
                              <div>Retraite: {sim.dateRetraite}</div>
                              <div>Service: {Math.floor(sim.dureeService)} ans 
                                {Math.floor((sim.dureeService % 1) * 12) > 0 && 
                                ` et ${Math.floor((sim.dureeService % 1) * 12)} mois`}
                              </div>
                              <div>Indice: {sim.indice}</div>
                            </div>
                            <div className="history-result">
                              {formatCurrency(sim.pensionTotale)}
                            </div>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div className="no-history">
                        <p>Aucune simulation enregistrée</p>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            )}

          </div>
        </div>
      </main>
    </div>
  );
};

export default SimulateurPension;