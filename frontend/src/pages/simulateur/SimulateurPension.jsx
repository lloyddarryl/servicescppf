// File: frontend/src/pages/simulateur/SimulateurPension.jsx

import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../../components/Header';
import { utils } from '../../services/api';
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

  const makeApiCall = useCallback(async (endpoint, options = {}) => {
    const baseUrl = 'http://localhost:8000/api';
    const url = `${baseUrl}${endpoint}`;
    
    const defaultOptions = {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    };
    
    return fetch(url, {
      ...defaultOptions,
      ...options,
      headers: {
        ...defaultOptions.headers,
        ...options.headers
      }
    });
  }, []);

  const loadHistory = useCallback(async () => {
    try {
      const response = await makeApiCall('/actifs/simulateur-pension/historique');
      const data = await response.json();
      
      if (data.success) {
        setSimulationHistory(data.simulations);
      }
    } catch (error) {
      console.error('Erreur historique:', error);
    }
  }, [makeApiCall]);

  const simulatePension = useCallback(async (indiceCustom = null, dateRetraiteCustom = null) => {
    try {
      const requestData = {};
      if (indiceCustom) requestData.indice = indiceCustom;
      if (dateRetraiteCustom) requestData.date_retraite = dateRetraiteCustom;
      
      const response = await makeApiCall('/actifs/simulateur-pension/simuler', {
        method: 'POST',
        body: JSON.stringify(requestData)
      });
      
      const data = await response.json();
      
      if (data.success) {
        setSimulationData(data.simulation);
        await loadHistory(); // Recharger l'historique après simulation
      }
      
    } catch (error) {
      console.error('Erreur simulation:', error);
    }
  }, [makeApiCall, loadHistory]);

  const loadInitialData = useCallback(async () => {
    try {
      setLoading(true);
      
      const profileResponse = await makeApiCall('/actifs/simulateur-pension/profil');
      const profileData = await profileResponse.json();
      
      if (profileData.success) {
        setUserProfile(profileData.profile);
        await simulatePension(profileData.profile.indice);
      }
      
    } catch (error) {
      console.error('Erreur chargement données:', error);
    } finally {
      setLoading(false);
    }
  }, [makeApiCall, simulatePension]);

  // Charger les données initiales
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

  return (
    <div className="simulateur-pension">
      <Header />
      
      <main className="simulateur-main">
        <div className="simulateur-container">
          
          {/* En-tête */}
          <div className="simulateur-header">
            <div className="header-content">
              <h1 className="simulateur-title">
                <span className="title-icon">🧮</span>
                Simulateur de Pension CPPF
              </h1>
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

          {/* Navigation */}
          <div className="simulateur-nav">
            <button
              className={`nav-button ${activeTab === 'profile' ? 'active' : ''}`}
              onClick={() => setActiveTab('profile')}
            >
              👤 Mon Profil
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
                
                {/* Informations personnelles */}
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
                        <label>Situation matrimoniale:</label>
                        <span>{userProfile.situationMatrimoniale}</span>
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

                {/* Rémunération */}
                <div className="profile-card salary-card">
                  <div className="card-header">
                    <h2>💰 Rémunération Actuelle</h2>
                  </div>
                  <div className="card-content">
                    <div className="salary-display">
                      <div className="salary-amount">
                        {formatCurrency(userProfile.indice * 500)}
                      </div>
                      <div className="salary-formula">
                        Calcul: {userProfile.indice} × 500 = {formatCurrency(userProfile.indice * 500)}
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
                    
                    {/* Statistiques principales */}
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
                          <div className="stat-value">{simulationData.dureeServiceRetraite} ans</div>
                          <div className="stat-subtitle">À la retraite</div>
                        </div>
                      </div>

                      <div className="stat-card info">
                        <div className="stat-icon">📊</div>
                        <div className="stat-content">
                          <div className="stat-label">Taux de Liquidation</div>
                          <div className="stat-value">{simulationData.tauxLiquidation}%</div>
                          <div className="stat-subtitle">Du salaire de référence</div>
                        </div>
                      </div>

                      <div className="stat-card warning">
                        <div className="stat-icon">💰</div>
                        <div className="stat-content">
                          <div className="stat-label">Pension Estimée</div>
                          <div className="stat-value">{formatCurrency(simulationData.pensionTotale)}</div>
                          <div className="stat-subtitle">Par mois</div>
                        </div>
                      </div>
                    </div>

                    {/* Détails du calcul selon Article 94 */}
                    <div className="calculation-details">
                      <div className="card-header">
                        <h3>🧮 Détail du Calcul (Article 94)</h3>
                      </div>
                      <div className="calculation-steps">
                        <div className="step">
                          <div className="step-label">Solde de base (SB):</div>
                          <div className="step-value">{formatCurrency(simulationData.salaireReference)}</div>
                          <div className="step-formula">Indice {simulationData.indiceSimule} × 500</div>
                        </div>

                        <div className="step">
                          <div className="step-label">Taux de liquidation:</div>
                          <div className="step-value">{simulationData.tauxLiquidation}%</div>
                          <div className="step-formula">
                            {simulationData.dureeServiceRetraite < 15 
                              ? "Moins de 15 ans - Pas de pension"
                              : `${simulationData.dureeServiceRetraite} années × 1,8% = ${simulationData.tauxLiquidation}%`
                            }
                          </div>
                        </div>

                        <div className="step">
                          <div className="step-label">Pension de base:</div>
                          <div className="step-value">{formatCurrency(simulationData.pensionBase)}</div>
                          <div className="step-formula">
                            SB × Taux = {formatCurrency(simulationData.salaireReference)} × {simulationData.tauxLiquidation}%
                          </div>
                        </div>

                        {simulationData.coefficientTemporel && (
                          <div className="step">
                            <div className="step-label">Coefficient {simulationData.anneePension}:</div>
                            <div className="step-value">{simulationData.coefficientTemporel}%</div>
                            <div className="step-formula">Article 94 - Coefficient temporel</div>
                          </div>
                        )}

                        {simulationData.pensionApresCoefficient && (
                          <div className="step">
                            <div className="step-label">Après coefficient:</div>
                            <div className="step-value">{formatCurrency(simulationData.pensionApresCoefficient)}</div>
                            <div className="step-formula">
                              {formatCurrency(simulationData.pensionBase)} × {simulationData.coefficientTemporel}%
                            </div>
                          </div>
                        )}

                        {simulationData.bonifications > 0 && (
                          <div className="step">
                            <div className="step-label">Bonifications:</div>
                            <div className="step-value">+{formatCurrency(simulationData.bonifications)}</div>
                            <div className="step-formula">Majorations familiales</div>
                          </div>
                        )}

                        <div className="step total">
                          <div className="step-label">Pension totale:</div>
                          <div className="step-value">{formatCurrency(simulationData.pensionTotale)}</div>
                          <div className="step-formula">Montant mensuel final</div>
                        </div>
                      </div>
                    </div>

                    {/* Alertes Article 94 */}
                    <div className="alerts-section">
                      {!simulationData.eligible && (
                        <div className="alert alert-warning">
                          ⚠️ Attention: Avec {simulationData.dureeServiceRetraite} années de service, 
                          vous n'êtes pas encore éligible à une pension (minimum 15 ans requis).
                        </div>
                      )}
                      
                      <div className="alert alert-info">
                        ℹ️ Calcul selon Article 94 : Formule {simulationData.methodeCalcul || 'Années × 1,8%'} 
                        avec coefficient temporel de {simulationData.coefficientTemporel}% pour {simulationData.anneePension}.
                      </div>

                      <div className="alert alert-info">
                        📈 Évolution des coefficients : 2025=91%, 2026=94%, 2027=96%, 2028=98%, 2029+=100%
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
                              <div>Service: {sim.dureeService} ans</div>
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