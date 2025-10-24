import React, { useState, useEffect } from 'react';
import adminApi from '../../services/adminApi';
import AdminHeader from '../../components/AdminHeader';
import AdminNav from '../../components/AdminNav';
import './AdminRendezVous.css';

const AdminRendezVous = () => {
  const [rdvs, setRdvs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedRdv, setSelectedRdv] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [filters, setFilters] = useState({
    statut: '',
    search: '',
    urgence: ''
  });
  const [selectedRdvs, setSelectedRdvs] = useState([]);
  const [pagination, setPagination] = useState({
    current_page: 1,
    per_page: 15,
    total: 0
  });

  useEffect(() => {
    loadRdvs();
  }, [filters, pagination.current_page]);

  const loadRdvs = async () => {
    try {
      setLoading(true);
      const params = {
        ...filters,
        page: pagination.current_page,
        per_page: pagination.per_page
      };
      
      const response = await adminApi.get('/rendez-vous', { params });
      if (response.data.success) {
        setRdvs(response.data.data.data);
        setPagination({
          current_page: response.data.data.current_page,
          per_page: response.data.data.per_page,
          total: response.data.data.total,
          last_page: response.data.data.last_page
        });
      }
    } catch (error) {
      console.error('Erreur chargement RDV:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleStatusChange = async (rdvId, newStatus, commentaire = '', nouvelleDate = '', nouvelleHeure = '') => {
    try {
      const data = {
        statut: newStatus,
        commentaire_admin: commentaire
      };

      if (newStatus === 'reporte') {
        data.nouvelle_date = nouvelleDate;
        data.nouvelle_heure = nouvelleHeure;
      }

      const response = await adminApi.put(`/rendez-vous/${rdvId}/statut`, data);
      
      if (response.data.success) {
        loadRdvs(); // Recharger la liste
        setShowModal(false);
        setSelectedRdv(null);
        alert('Statut modifié avec succès');
      }
    } catch (error) {
      console.error('Erreur changement statut:', error);
      alert('Erreur lors du changement de statut');
    }
  };

  const getStatusColor = (statut) => {
    const colors = {
      'en_attente': '#f59e0b',
      'accepte': '#10b981',
      'refuse': '#ef4444',
      'reporte': '#8b5cf6',
      'annule': '#6b7280'
    };
    return colors[statut] || '#6b7280';
  };

  const getStatusLabel = (statut) => {
    const labels = {
      'en_attente': 'En attente',
      'accepte': 'Accepté',
      'refuse': 'Refusé', 
      'reporte': 'Reporté',
      'annule': 'Annulé'
    };
    return labels[statut] || statut;
  };

  const handleSelectRdv = (rdvId) => {
    setSelectedRdvs(prev => 
      prev.includes(rdvId) 
        ? prev.filter(id => id !== rdvId)
        : [...prev, rdvId]
    );
  };

  const handleSelectAll = () => {
    if (selectedRdvs.length === rdvs.length) {
      setSelectedRdvs([]);
    } else {
      setSelectedRdvs(rdvs.map(rdv => rdv.id));
    }
  };

  return (

     <div className="admin-dashboard">
      <AdminHeader 
        title="Gestion des Rendez-vous" 
        breadcrumb="Administration - Rendez-vous"
      />
      <AdminNav />
      
      <header className="admin-header">
        <div className="header-left">
          <h1>Gestion des Rendez-vous</h1>
          <span className="breadcrumb">Administration CPPF - Rendez-vous</span>
        </div>
      </header>

      <main className="admin-main">
        <div className="dashboard-content">
          {/* Filtres */}
          <section className="welcome-section">
            <h2>Filtres et recherche</h2>
            <div className="stats-grid">
              <div className="admin-info-card">
                <input
                  type="text"
                  placeholder="Rechercher par nom, matricule..."
                  value={filters.search}
                  onChange={(e) => setFilters({...filters, search: e.target.value})}
                  className="form-input"
                />
              </div>
              <div className="admin-info-card">
                <select
                  value={filters.statut}
                  onChange={(e) => setFilters({...filters, statut: e.target.value})}
                  className="form-select"
                >
                  <option value="">Tous les statuts</option>
                  <option value="en_attente">En attente</option>
                  <option value="accepte">Accepté</option>
                  <option value="refuse">Refusé</option>
                  <option value="reporte">Reporté</option>
                  <option value="annule">Annulé</option>
                </select>
              </div>
              <div className="admin-info-card">
                <select
                  value={filters.urgence}
                  onChange={(e) => setFilters({...filters, urgence: e.target.value})}
                  className="form-select"
                >
                  <option value="">Tous</option>
                  <option value="urgent">Urgents seulement</option>
                </select>
              </div>
            </div>
          </section>

          {/* Actions en lot */}
          {selectedRdvs.length > 0 && (
            <section className="welcome-section">
              <h3>Actions en lot ({selectedRdvs.length} sélectionnés)</h3>
              <div className="actions-grid">
                <button 
                  className="action-btn"
                  onClick={() => alert('Accepter en lot - À implémenter')}
                >
                  Accepter tout
                </button>
                <button 
                  className="action-btn"
                  onClick={() => alert('Refuser en lot - À implémenter')}
                >
                  Refuser tout
                </button>
                <button 
                  className="action-btn"
                  onClick={() => alert('Reporter en lot - À implémenter')}
                >
                  Reporter tout
                </button>
              </div>
            </section>
          )}

          {/* Liste des RDV */}
          <section className="welcome-section">
            <div className="table-header">
              <h2>Liste des rendez-vous ({pagination.total})</h2>
              <label>
                <input
                  type="checkbox"
                  checked={selectedRdvs.length === rdvs.length && rdvs.length > 0}
                  onChange={handleSelectAll}
                />
                Tout sélectionner
              </label>
            </div>

            {loading ? (
              <div className="loading-container">
                <div className="spinner"></div>
                <p>Chargement des rendez-vous...</p>
              </div>
            ) : (
              <div className="table-container">
                <table className="admin-table">
                  <thead>
                    <tr>
                      <th>
                        <input
                          type="checkbox"
                          checked={selectedRdvs.length === rdvs.length && rdvs.length > 0}
                          onChange={handleSelectAll}
                        />
                      </th>
                      <th>Agent</th>
                      <th>Motif</th>
                      <th>Date demandée</th>
                      <th>Heure</th>
                      <th>Statut</th>
                      <th>Soumis le</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {rdvs.map((rdv) => (
                      <tr key={rdv.id} className={rdv.urgent ? 'row-urgent' : ''}>
                        <td>
                          <input
                            type="checkbox"
                            checked={selectedRdvs.includes(rdv.id)}
                            onChange={() => handleSelectRdv(rdv.id)}
                          />
                        </td>
                        <td>
                          <div>
                            <strong>{rdv.agent?.nom} {rdv.agent?.prenom}</strong>
                            <br />
                            <small>{rdv.agent?.matricule}</small>
                          </div>
                        </td>
                        <td>{rdv.motif}</td>
                        <td>{new Date(rdv.date_demandee).toLocaleDateString('fr-FR')}</td>
                        <td>{rdv.heure_demandee}</td>
                        <td>
                          <span 
                            className="status-badge"
                            style={{ backgroundColor: getStatusColor(rdv.statut) }}
                          >
                            {getStatusLabel(rdv.statut)}
                          </span>
                          {rdv.urgent && <span className="urgent-badge">URGENT</span>}
                        </td>
                        <td>
                          {new Date(rdv.date_soumission).toLocaleDateString('fr-FR')}
                          <br />
                          <small>{rdv.jours_attente} jour(s)</small>
                        </td>
                        <td>
                          <div className="action-buttons">
                            <button
                              className="btn-view"
                              onClick={() => {
                                setSelectedRdv(rdv);
                                setShowModal(true);
                              }}
                            >
                              Traiter
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
                  disabled={pagination.current_page === 1}
                  onClick={() => setPagination({...pagination, current_page: pagination.current_page - 1})}
                  className="pagination-btn"
                >
                  Précédent
                </button>
                
                <span className="pagination-info">
                  Page {pagination.current_page} sur {pagination.last_page} 
                  ({pagination.total} résultats)
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
          </section>
        </div>
      </main>

      {/* Modal de traitement */}
      {showModal && selectedRdv && (
        <TraiterRdvModal
          rdv={selectedRdv}
          onClose={() => {
            setShowModal(false);
            setSelectedRdv(null);
          }}
          onStatusChange={handleStatusChange}
        />
      )}
    </div>
  );
};

// Composant Modal pour traiter un RDV
const TraiterRdvModal = ({ rdv, onClose, onStatusChange }) => {
  const [action, setAction] = useState('');
  const [commentaire, setCommentaire] = useState('');
  const [nouvelleDate, setNouvelleDate] = useState('');
  const [nouvelleHeure, setNouvelleHeure] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      await onStatusChange(rdv.id, action, commentaire, nouvelleDate, nouvelleHeure);
    } catch (error) {
      console.error('Erreur:', error);
    } finally {
      setLoading(false);
    }
  };

  const messagesPredefinis = {
    accepte: "Votre rendez-vous a été confirmé. Merci de vous présenter à l'heure avec les documents nécessaires.",
    refuse: "Nous regrettons de ne pouvoir donner suite à votre demande. Vous pouvez soumettre une nouvelle demande.",
    reporte: "Votre rendez-vous a été reporté en raison de contraintes d'agenda. Veuillez noter la nouvelle date.",
    annule: "Votre rendez-vous a été annulé. Vous pouvez prendre un nouveau rendez-vous si nécessaire."
  };

  const handleActionChange = (newAction) => {
    setAction(newAction);
    setCommentaire(messagesPredefinis[newAction] || '');
  };

  return (
    <div className="modal-overlay">
      <div className="modal-content">
        <div className="modal-header">
          <h3>Traiter le rendez-vous</h3>
          <button onClick={onClose} className="modal-close">×</button>
        </div>

        <div className="modal-body">
          {/* Informations du RDV */}
          <div className="rdv-info">
            <h4>Informations du rendez-vous</h4>
            <div className="info-grid">
              <div className="info-item">
                <strong>Agent:</strong> {rdv.agent?.nom} {rdv.agent?.prenom}
              </div>
              <div className="info-item">
                <strong>Matricule:</strong> {rdv.agent?.matricule}
              </div>
              <div className="info-item">
                <strong>Motif:</strong> {rdv.motif}
              </div>
              <div className="info-item">
                <strong>Date demandée:</strong> {new Date(rdv.date_demandee).toLocaleDateString('fr-FR')}
              </div>
              <div className="info-item">
                <strong>Heure:</strong> {rdv.heure_demandee}
              </div>
              <div className="info-item">
                <strong>Soumis le:</strong> {new Date(rdv.date_soumission).toLocaleDateString('fr-FR')}
              </div>
              <div className="info-item">
                <strong>Attente:</strong> {rdv.jours_attente} jour(s)
              </div>
              <div className="info-item">
                <strong>Statut actuel:</strong> {rdv.statut}
              </div>
            </div>
          </div>

          {/* Formulaire de traitement */}
          <form onSubmit={handleSubmit} className="traitement-form">
            <div className="form-group">
              <label>Action à effectuer</label>
              <div className="action-buttons">
                <button
                  type="button"
                  className={`action-btn ${action === 'accepte' ? 'active' : ''}`}
                  onClick={() => handleActionChange('accepte')}
                >
                  Accepter
                </button>
                <button
                  type="button"
                  className={`action-btn ${action === 'refuse' ? 'active' : ''}`}
                  onClick={() => handleActionChange('refuse')}
                >
                  Refuser
                </button>
                <button
                  type="button"
                  className={`action-btn ${action === 'reporte' ? 'active' : ''}`}
                  onClick={() => handleActionChange('reporte')}
                >
                  Reporter
                </button>
                <button
                  type="button"
                  className={`action-btn ${action === 'annule' ? 'active' : ''}`}
                  onClick={() => handleActionChange('annule')}
                >
                  Annuler
                </button>
              </div>
            </div>

            {/* Champs pour report */}
            {action === 'reporte' && (
              <div className="form-group">
                <div className="form-row">
                  <div className="form-col">
                    <label>Nouvelle date</label>
                    <input
                      type="date"
                      value={nouvelleDate}
                      onChange={(e) => setNouvelleDate(e.target.value)}
                      min={new Date().toISOString().split('T')[0]}
                      required
                      className="form-input"
                    />
                  </div>
                  <div className="form-col">
                    <label>Nouvelle heure</label>
                    <select
                      value={nouvelleHeure}
                      onChange={(e) => setNouvelleHeure(e.target.value)}
                      required
                      className="form-select"
                    >
                      <option value="">Choisir une heure</option>
                      <option value="08:00">08:00</option>
                      <option value="09:00">09:00</option>
                      <option value="10:00">10:00</option>
                      <option value="11:00">11:00</option>
                      <option value="14:00">14:00</option>
                      <option value="15:00">15:00</option>
                      <option value="16:00">16:00</option>
                    </select>
                  </div>
                </div>
              </div>
            )}

            {/* Commentaire */}
            <div className="form-group">
              <label>Message à envoyer à l'agent</label>
              <textarea
                value={commentaire}
                onChange={(e) => setCommentaire(e.target.value)}
                placeholder="Message qui sera envoyé automatiquement..."
                rows="4"
                className="form-textarea"
              />
              <small>
                Ce message sera envoyé automatiquement à l'agent via le système de messagerie.
              </small>
            </div>

            {/* Boutons */}
            <div className="form-actions">
              <button
                type="button"
                onClick={onClose}
                className="btn-secondary"
                disabled={loading}
              >
                Annuler
              </button>
              <button
                type="submit"
                className="btn-primary"
                disabled={!action || loading}
              >
                {loading ? 'Traitement...' : 'Confirmer le traitement'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default AdminRendezVous;