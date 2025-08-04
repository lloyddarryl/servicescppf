// File: frontend/src/pages/famille/GrappeFamiliale.jsx

import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../../components/Header';
import { utils, familleService } from '../../services/api';
import './GrappeFamiliale.css';

const GrappeFamiliale = () => {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [grappeFamiliale, setGrappeFamiliale] = useState(null);
  const [activeTab, setActiveTab] = useState('vue-ensemble');
  const [error, setError] = useState(null);
  const [showConjointForm, setShowConjointForm] = useState(false);
  const [showEnfantForm, setShowEnfantForm] = useState(false);
  const [editingEnfant, setEditingEnfant] = useState(null);

  // États des formulaires
  const [conjointForm, setConjointForm] = useState({
    nom: '',
    prenoms: '',
    sexe: '',
    date_naissance: '',
    date_mariage: '',
    matricule_conjoint: '',
    nag_conjoint: '',
    profession: ''
  });

  const [enfantForm, setEnfantForm] = useState({
    enfant_id: '',
    nom: '',
    prenoms: '',
    sexe: '',
    date_naissance: '',
    prestation_familiale: false,
    scolarise: true,
    niveau_scolaire: ''
  });

  const loadGrappeFamiliale = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await familleService.getGrappeFamiliale();
      
      if (response.data.success) {
        setGrappeFamiliale(response.data.grappe_familiale);
      } else {
        setError(response.data.message || 'Erreur lors du chargement');
      }
    } catch (error) {
      console.error('Erreur chargement famille:', error);
      setError('Erreur lors du chargement de la grappe familiale');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!utils.isAuthenticated()) {
      navigate('/services');
      return;
    }
    loadGrappeFamiliale();
  }, [navigate, loadGrappeFamiliale]);

  const handleSaveConjoint = async (e) => {
    e.preventDefault();
    try {
      setError(null);
      
      const response = await familleService.saveConjoint(conjointForm);
      
      if (response.data.success) {
        setShowConjointForm(false);
        setConjointForm({
          nom: '', prenoms: '', sexe: '', date_naissance: '',
          date_mariage: '', matricule_conjoint: '', nag_conjoint: '', profession: ''
        });
        await loadGrappeFamiliale();
      } else {
        setError(response.data.message || 'Erreur lors de l\'enregistrement');
      }
    } catch (error) {
      console.error('Erreur sauvegarde conjoint:', error);
      setError('Erreur lors de l\'enregistrement du conjoint');
    }
  };

  const handleSaveEnfant = async (e) => {
    e.preventDefault();
    try {
      setError(null);
      
      let response;
      if (editingEnfant) {
        response = await familleService.updateEnfant(editingEnfant.id, enfantForm);
      } else {
        response = await familleService.addEnfant(enfantForm);
      }
      
      if (response.data.success) {
        setShowEnfantForm(false);
        setEditingEnfant(null);
        setEnfantForm({
          enfant_id: '', nom: '', prenoms: '', sexe: '',
          date_naissance: '', prestation_familiale: false,
          scolarise: true, niveau_scolaire: ''
        });
        await loadGrappeFamiliale();
      } else {
        setError(response.data.message || 'Erreur lors de l\'enregistrement');
      }
    } catch (error) {
      console.error('Erreur sauvegarde enfant:', error);
      setError('Erreur lors de l\'enregistrement de l\'enfant');
    }
  };

  const handleDeleteEnfant = async (enfantId) => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer cet enfant ?')) {
      return;
    }

    try {
      const response = await familleService.deleteEnfant(enfantId);
      
      if (response.data.success) {
        await loadGrappeFamiliale();
      } else {
        setError(response.data.message || 'Erreur lors de la suppression');
      }
    } catch (error) {
      console.error('Erreur suppression enfant:', error);
      setError('Erreur lors de la suppression de l\'enfant');
    }
  };

  const handleEditEnfant = (enfant) => {
    setEditingEnfant(enfant);
    setEnfantForm({
      enfant_id: enfant.enfant_id,
      nom: enfant.nom,
      prenoms: enfant.prenoms,
      sexe: enfant.sexe,
      date_naissance: enfant.date_naissance,
      prestation_familiale: enfant.prestation_familiale,
      scolarise: enfant.scolarise,
      niveau_scolaire: enfant.niveau_scolaire || ''
    });
    setShowEnfantForm(true);
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

  const getTitle = (sexe, situationMatrimoniale) => {
    if (sexe === 'F') {
      if (situationMatrimoniale === 'Marié(e)' || situationMatrimoniale === 'Mariée') {
        return 'Mme';
      }
      return 'Mlle';
    }
    return 'M.';
  };

  if (loading) {
    return (
      <div className="famille-page">
        <Header />
        <div className="loading-container">
          <div className="spinner"></div>
          <p>Chargement de la grappe familiale...</p>
        </div>
      </div>
    );
  }

  if (error && !grappeFamiliale) {
    return (
      <div className="famille-page">
        <Header />
        <div className="loading-container">
          <div className="alert alert-warning">
            <p>❌ {error}</p>
            <button 
              className="btn-primary" 
              onClick={() => loadGrappeFamiliale()}
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
    <div className="famille-page">
      <Header />
      
      <main className="famille-main">
        <div className="famille-container">
          
          {/* En-tête */}
          <div className="famille-header">
            <div className="header-content">
              <h1 className="famille-title">
                <span className="title-icon">👨‍👩‍👧‍👦</span>
                Ma Grappe Familiale
              </h1>
              {grappeFamiliale && (
                <div className="user-welcome">
                  {getTitle(grappeFamiliale.agent.sexe, grappeFamiliale.agent.situation_matrimoniale)} {grappeFamiliale.agent.nom_complet}
                </div>
              )}
              <p className="famille-subtitle">
                Gérez les informations de votre famille et les prestations familiales
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
          <div className="famille-nav">
            <button
              className={`nav-button ${activeTab === 'vue-ensemble' ? 'active' : ''}`}
              onClick={() => setActiveTab('vue-ensemble')}
            >
              🏠 Vue d'ensemble
            </button>
            <button
              className={`nav-button ${activeTab === 'conjoint' ? 'active' : ''}`}
              onClick={() => setActiveTab('conjoint')}
            >
              💑 Conjoint(e)
            </button>
            <button
              className={`nav-button ${activeTab === 'enfants' ? 'active' : ''}`}
              onClick={() => setActiveTab('enfants')}
            >
              👶 Enfants
            </button>
          </div>

          {/* Contenu */}
          <div className="famille-content">
            
            {/* Vue d'ensemble */}
            {activeTab === 'vue-ensemble' && grappeFamiliale && (
              <div className="vue-ensemble-section">
                
                {/* Statistiques famille */}
                <div className="stats-grid">
                  <div className="stat-card primary">
                    <div className="stat-icon">👑</div>
                    <div className="stat-content">
                      <div className="stat-label">Chef de famille</div>
                      <div className="stat-value">{grappeFamiliale.agent.nom_complet}</div>
                      <div className="stat-subtitle">Matricule: {grappeFamiliale.agent.matricule}</div>
                    </div>
                  </div>

                  <div className="stat-card success">
                    <div className="stat-icon">💑</div>
                    <div className="stat-content">
                      <div className="stat-label">Conjoint(e)</div>
                      <div className="stat-value">
                        {grappeFamiliale.statistiques.conjoint_presente ? 'Présent(e)' : 'Aucun(e)'}
                      </div>
                      <div className="stat-subtitle">
                        {grappeFamiliale.conjoint && grappeFamiliale.statistiques.conjoint_travaille ? 'Travaille' : ''}
                      </div>
                    </div>
                  </div>

                  <div className="stat-card info">
                    <div className="stat-icon">👶</div>
                    <div className="stat-content">
                      <div className="stat-label">Enfants</div>
                      <div className="stat-value">{grappeFamiliale.statistiques.nombre_enfants}</div>
                      <div className="stat-subtitle">
                        {grappeFamiliale.statistiques.enfants_mineurs} mineur(s)
                      </div>
                    </div>
                  </div>

                  <div className="stat-card warning">
                    <div className="stat-icon">💰</div>
                    <div className="stat-content">
                      <div className="stat-label">Prestations</div>
                      <div className="stat-value">{grappeFamiliale.statistiques.enfants_avec_prestations}</div>
                      <div className="stat-subtitle">Enfant(s) bénéficiaire(s)</div>
                    </div>
                  </div>
                </div>

                {/* Aperçu famille */}
                <div className="famille-overview">
                  <div className="overview-card">
                    <div className="card-header">
                      <h3>👨‍👩‍👧‍👦 Composition Familiale</h3>
                    </div>
                    <div className="card-content">
                      
                      {/* Agent principal */}
                      <div className="membre-famille agent">
                        <div className="membre-info">
                          <div className="membre-nom">
                            <strong>{grappeFamiliale.agent.nom_complet}</strong>
                            <span className="badge chef">Chef de famille</span>
                          </div>
                          <div className="membre-details">
                            Matricule: {grappeFamiliale.agent.matricule} | 
                            Sexe: {grappeFamiliale.agent.sexe === 'M' ? 'Masculin' : 'Féminin'}
                          </div>
                        </div>
                      </div>

                      {/* Conjoint */}
                      {grappeFamiliale.conjoint ? (
                        <div className="membre-famille conjoint">
                          <div className="membre-info">
                            <div className="membre-nom">
                              <strong>{grappeFamiliale.conjoint.nom_complet}</strong>
                              <span className="badge conjoint">Conjoint(e)</span>
                            </div>
                            <div className="membre-details">
                              Âge: {grappeFamiliale.conjoint.age} ans | 
                              {grappeFamiliale.conjoint.travaille ? 
                                `Matricule: ${grappeFamiliale.conjoint.identifiant}` : 
                                `NAG: ${grappeFamiliale.conjoint.identifiant}`
                              }
                            </div>
                          </div>
                        </div>
                      ) : (
                        <div className="membre-famille no-conjoint">
                          <div className="membre-info">
                            <div className="membre-nom">Aucun conjoint déclaré</div>
                            <button 
                              className="btn-add-small"
                              onClick={() => {
                                setShowConjointForm(true);
                                setActiveTab('conjoint');
                              }}
                            >
                              + Ajouter conjoint
                            </button>
                          </div>
                        </div>
                      )}

                      {/* Enfants */}
                      {grappeFamiliale.enfants.length > 0 ? (
                        grappeFamiliale.enfants.map(enfant => (
                          <div key={enfant.id} className="membre-famille enfant">
                            <div className="membre-info">
                              <div className="membre-nom">
                                <strong>{enfant.nom_complet}</strong>
                                <span className="badge enfant">Enfant</span>
                                {enfant.prestation_familiale && (
                                  <span className="badge prestation">Prestation</span>
                                )}
                              </div>
                              <div className="membre-details">
                                Âge: {enfant.age} ans | NAG: {enfant.enfant_id} | 
                                {enfant.scolarise ? `Scolarisé (${enfant.niveau_scolaire || 'Non précisé'})` : 'Non scolarisé'}
                              </div>
                            </div>
                          </div>
                        ))
                      ) : (
                        <div className="membre-famille no-enfants">
                          <div className="membre-info">
                            <div className="membre-nom">Aucun enfant déclaré</div>
                            <button 
                              className="btn-add-small"
                              onClick={() => {
                                setShowEnfantForm(true);
                                setActiveTab('enfants');
                              }}
                            >
                              + Ajouter enfant
                            </button>
                          </div>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Section Conjoint */}
            {activeTab === 'conjoint' && (
              <div className="conjoint-section">
                {grappeFamiliale?.conjoint ? (
                  <div className="conjoint-card">
                    <div className="card-header">
                      <h3>💑 Informations du Conjoint</h3>
                      <button 
                        className="btn-edit"
                        onClick={() => {
                          setConjointForm({
                            nom: grappeFamiliale.conjoint.nom,
                            prenoms: grappeFamiliale.conjoint.prenoms,
                            sexe: grappeFamiliale.conjoint.sexe,
                            date_naissance: grappeFamiliale.conjoint.date_naissance,
                            date_mariage: grappeFamiliale.conjoint.date_mariage || '',
                            matricule_conjoint: grappeFamiliale.conjoint.travaille ? grappeFamiliale.conjoint.identifiant || '' : '',
                            nag_conjoint: !grappeFamiliale.conjoint.travaille ? grappeFamiliale.conjoint.identifiant || '' : '',
                            profession: grappeFamiliale.conjoint.profession || ''
                          });
                          setShowConjointForm(true);
                        }}
                      >
                        ✏️ Modifier
                      </button>
                    </div>
                    <div className="card-content">
                      <div className="profile-grid">
                        <div className="profile-item">
                          <label>Nom complet:</label>
                          <span>{grappeFamiliale.conjoint.nom_complet}</span>
                        </div>
                        <div className="profile-item">
                          <label>Sexe:</label>
                          <span>{grappeFamiliale.conjoint.sexe === 'M' ? 'Masculin' : 'Féminin'}</span>
                        </div>
                        <div className="profile-item">
                          <label>Âge:</label>
                          <span>{grappeFamiliale.conjoint.age} ans</span>
                        </div>
                        <div className="profile-item">
                          <label>Date de naissance:</label>
                          <span>{new Date(grappeFamiliale.conjoint.date_naissance).toLocaleDateString('fr-FR')}</span>
                        </div>
                        <div className="profile-item">
                          <label>Date de mariage:</label>
                          <span>
                            {grappeFamiliale.conjoint.date_mariage ? 
                              new Date(grappeFamiliale.conjoint.date_mariage).toLocaleDateString('fr-FR') : 
                              'Non précisée'
                            }
                          </span>
                        </div>
                        <div className="profile-item">
                          <label>Statut professionnel:</label>
                          <span>{grappeFamiliale.conjoint.travaille ? 'Travaille' : 'Ne travaille pas'}</span>
                        </div>
                        <div className="profile-item">
                          <label>Identifiant:</label>
                          <span>
                            {grappeFamiliale.conjoint.travaille ? 
                              `Matricule: ${grappeFamiliale.conjoint.identifiant}` :
                              `NAG: ${grappeFamiliale.conjoint.identifiant}`
                            }
                          </span>
                        </div>
                        <div className="profile-item">
                          <label>Profession:</label>
                          <span>{grappeFamiliale.conjoint.profession || 'Non précisée'}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                ) : (
                  <div className="no-conjoint-card">
                    <div className="card-header">
                      <h3>💑 Aucun Conjoint Déclaré</h3>
                    </div>
                    <div className="card-content">
                      <p>Vous n'avez pas encore déclaré de conjoint.</p>
                      <button 
                        className="btn-primary"
                        onClick={() => setShowConjointForm(true)}
                      >
                        + Ajouter un conjoint
                      </button>
                    </div>
                  </div>
                )}

                {/* Formulaire conjoint */}
                {showConjointForm && (
                  <div className="form-modal">
                    <div className="form-modal-content">
                      <div className="form-header">
                        <h3>Informations du Conjoint</h3>
                        <button 
                          className="btn-close"
                          onClick={() => setShowConjointForm(false)}
                        >
                          ✕
                        </button>
                      </div>
                      <form onSubmit={handleSaveConjoint}>
                        <div className="form-grid">
                          <div className="form-group">
                            <label>Nom *</label>
                            <input
                              type="text"
                              required
                              value={conjointForm.nom}
                              onChange={(e) => setConjointForm(prev => ({...prev, nom: e.target.value}))}
                            />
                          </div>
                          <div className="form-group">
                            <label>Prénoms *</label>
                            <input
                              type="text"
                              required
                              value={conjointForm.prenoms}
                              onChange={(e) => setConjointForm(prev => ({...prev, prenoms: e.target.value}))}
                            />
                          </div>
                          <div className="form-group">
                            <label>Sexe *</label>
                            <select
                              required
                              value={conjointForm.sexe}
                              onChange={(e) => setConjointForm(prev => ({...prev, sexe: e.target.value}))}
                            >
                              <option value="">Choisir...</option>
                              <option value="M">Masculin</option>
                              <option value="F">Féminin</option>
                            </select>
                          </div>
                          <div className="form-group">
                            <label>Date de naissance *</label>
                            <input
                              type="date"
                              required
                              value={conjointForm.date_naissance}
                              onChange={(e) => setConjointForm(prev => ({...prev, date_naissance: e.target.value}))}
                            />
                          </div>
                          <div className="form-group">
                            <label>Date de mariage</label>
                            <input
                              type="date"
                              value={conjointForm.date_mariage}
                              onChange={(e) => setConjointForm(prev => ({...prev, date_mariage: e.target.value}))}
                            />
                          </div>
                          <div className="form-group">
                            <label>Matricule (si travaille)</label>
                            <input
                              type="text"
                              value={conjointForm.matricule_conjoint}
                              onChange={(e) => setConjointForm(prev => ({...prev, matricule_conjoint: e.target.value, nag_conjoint: e.target.value ? '' : prev.nag_conjoint}))}
                              placeholder="Ex: 123456A"
                            />
                          </div>
                          <div className="form-group">
                            <label>NAG CNAMGS (si ne travaille pas)</label>
                            <input
                              type="text"
                              value={conjointForm.nag_conjoint}
                              onChange={(e) => setConjointForm(prev => ({...prev, nag_conjoint: e.target.value, matricule_conjoint: e.target.value ? '' : prev.matricule_conjoint}))}
                              placeholder="Ex: 1234567890"
                            />
                          </div>
                          <div className="form-group">
                            <label>Profession</label>
                            <input
                              type="text"
                              value={conjointForm.profession}
                              onChange={(e) => setConjointForm(prev => ({...prev, profession: e.target.value}))}
                              placeholder="Ex: Enseignant"
                            />
                          </div>
                        </div>
                        <div className="form-actions">
                          <button type="button" onClick={() => setShowConjointForm(false)}>
                            Annuler
                          </button>
                          <button type="submit" className="btn-primary">
                            Enregistrer
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* Section Enfants */}
            {activeTab === 'enfants' && (
              <div className="enfants-section">
                <div className="enfants-header">
                  <h3>👶 Liste des Enfants</h3>
                  <button 
                    className="btn-primary"
                    onClick={() => {
                      setEditingEnfant(null);
                      setEnfantForm({
                        enfant_id: '', nom: '', prenoms: '', sexe: '',
                        date_naissance: '', prestation_familiale: false,
                        scolarise: true, niveau_scolaire: ''
                      });
                      setShowEnfantForm(true);
                    }}
                  >
                    + Ajouter un enfant
                  </button>
                </div>

                {grappeFamiliale?.enfants?.length > 0 ? (
                  <div className="enfants-list">
                    {grappeFamiliale.enfants.map(enfant => (
                      <div key={enfant.id} className="enfant-card">
                        <div className="enfant-info">
                          <div className="enfant-nom">
                            <strong>{enfant.nom_complet}</strong>
                            <div className="enfant-badges">
                              <span className="badge age">{enfant.age} ans</span>
                              {enfant.est_mineur && <span className="badge mineur">Mineur</span>}
                              {enfant.prestation_familiale && <span className="badge prestation">Prestation</span>}
                              {enfant.scolarise && <span className="badge scolarise">Scolarisé</span>}
                            </div>
                          </div>
                          <div className="enfant-details">
                            <div>NAG: {enfant.enfant_id}</div>
                            <div>Né(e) le: {new Date(enfant.date_naissance).toLocaleDateString('fr-FR')}</div>
                            {enfant.niveau_scolaire && <div>Niveau: {enfant.niveau_scolaire}</div>}
                          </div>
                        </div>
                        <div className="enfant-actions">
                          <button 
                            className="btn-edit"
                            onClick={() => handleEditEnfant(enfant)}
                          >
                            ✏️ Modifier
                          </button>
                          <button 
                            className="btn-delete"
                            onClick={() => handleDeleteEnfant(enfant.id)}
                          >
                            🗑️ Supprimer
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="no-enfants">
                    <p>Aucun enfant déclaré</p>
                    <button 
                      className="btn-primary"
                      onClick={() => setShowEnfantForm(true)}
                    >
                      + Ajouter le premier enfant
                    </button>
                  </div>
                )}

                {/* Formulaire enfant */}
                {showEnfantForm && (
                  <div className="form-modal">
                    <div className="form-modal-content">
                      <div className="form-header">
                        <h3>{editingEnfant ? 'Modifier l\'enfant' : 'Ajouter un enfant'}</h3>
                        <button 
                          className="btn-close"
                          onClick={() => {
                            setShowEnfantForm(false);
                            setEditingEnfant(null);
                          }}
                        >
                          ✕
                        </button>
                      </div>
                      <form onSubmit={handleSaveEnfant}>
                        <div className="form-grid">
                          <div className="form-group">
                            <label>NAG de l'enfant *</label>
                            <input
                              type="text"
                              required
                              value={enfantForm.enfant_id}
                              onChange={(e) => setEnfantForm(prev => ({...prev, enfant_id: e.target.value}))}
                              placeholder="Ex: 1234567890"
                              disabled={editingEnfant} // Ne pas modifier le NAG en édition
                            />
                          </div>
                          <div className="form-group">
                            <label>Nom *</label>
                            <input
                              type="text"
                              required
                              value={enfantForm.nom}
                              onChange={(e) => setEnfantForm(prev => ({...prev, nom: e.target.value}))}
                            />
                          </div>
                          <div className="form-group">
                            <label>Prénoms *</label>
                            <input
                              type="text"
                              required
                              value={enfantForm.prenoms}
                              onChange={(e) => setEnfantForm(prev => ({...prev, prenoms: e.target.value}))}
                            />
                          </div>
                          <div className="form-group">
                            <label>Sexe *</label>
                            <select
                              required
                              value={enfantForm.sexe}
                              onChange={(e) => setEnfantForm(prev => ({...prev, sexe: e.target.value}))}
                            >
                              <option value="">Choisir...</option>
                              <option value="M">Masculin</option>
                              <option value="F">Féminin</option>
                            </select>
                          </div>
                          <div className="form-group">
                            <label>Date de naissance *</label>
                            <input
                              type="date"
                              required
                              value={enfantForm.date_naissance}
                              onChange={(e) => setEnfantForm(prev => ({...prev, date_naissance: e.target.value}))}
                            />
                          </div>
                          <div className="form-group">
                            <label>Niveau scolaire</label>
                            <input
                              type="text"
                              value={enfantForm.niveau_scolaire}
                              onChange={(e) => setEnfantForm(prev => ({...prev, niveau_scolaire: e.target.value}))}
                              placeholder="Ex: CP, CE1, 6ème, Licence..."
                            />
                          </div>
                          <div className="form-group checkbox-group">
                            <label>
                              <input
                                type="checkbox"
                                checked={enfantForm.scolarise}
                                onChange={(e) => setEnfantForm(prev => ({...prev, scolarise: e.target.checked}))}
                              />
                              Scolarisé
                            </label>
                          </div>
                          <div className="form-group checkbox-group">
                            <label>
                              <input
                                type="checkbox"
                                checked={enfantForm.prestation_familiale}
                                onChange={(e) => setEnfantForm(prev => ({...prev, prestation_familiale: e.target.checked}))}
                              />
                              Bénéficie de prestations familiales
                            </label>
                          </div>
                        </div>
                        <div className="form-actions">
                          <button 
                            type="button" 
                            onClick={() => {
                              setShowEnfantForm(false);
                              setEditingEnfant(null);
                            }}
                          >
                            Annuler
                          </button>
                          <button type="submit" className="btn-primary">
                            {editingEnfant ? 'Modifier' : 'Ajouter'}
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                )}
              </div>
            )}

          </div>
        </div>
      </main>
    </div>
  );
};

export default GrappeFamiliale;