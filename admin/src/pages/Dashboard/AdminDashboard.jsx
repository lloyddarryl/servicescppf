import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import AdminHeader from '../../components/AdminHeader';
import AdminNav from '../../components/AdminNav';
import adminApi from '../../services/adminApi';
import './AdminDashboard.css';

const AdminDashboard = () => {
  const [dashboardData, setDashboardData] = useState(null);
  const [adminData, setAdminData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const navigate = useNavigate();

  useEffect(() => {
    const loadDashboard = async () => {
      try {
        setLoading(true);
        
        const storedData = localStorage.getItem('admin_data');
        if (storedData) {
          setAdminData(JSON.parse(storedData));
        }

        const response = await adminApi.get('/dashboard');
        if (response.data.success) {
          setDashboardData(response.data);
        } else {
          throw new Error('Erreur lors du chargement du dashboard');
        }
      } catch (error) {
        console.error('Erreur dashboard:', error);
        
        if (error.response?.status === 401) {
          localStorage.clear();
          navigate('/login');
          return;
        }
        
        setError('Erreur de chargement des données');
      } finally {
        setLoading(false);
      }
    };

    loadDashboard();
  }, [navigate]);

  const handleLogout = async () => {
    try {
      await adminApi.post('/logout');
    } catch (error) {
      console.error('Erreur déconnexion:', error);
    } finally {
      localStorage.clear();
      navigate('/login');
    }
  };

  if (loading) {
    return (
      <div className="admin-dashboard">
        <div className="loading-container">
          <div className="spinner"></div>
          <p>Chargement du dashboard...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="admin-dashboard">
        <div className="error-container">
          <h2>Erreur</h2>
          <p>{error}</p>
          <button onClick={() => window.location.reload()}>Recharger</button>
        </div>
      </div>
    );
  }

  const { stats, alertes_urgentes, repartition_types, activites_recentes } = dashboardData;

  return (
    <div className="admin-dashboard">
      <AdminHeader 
        title="Tableau de bord" 
        breadcrumb="Vue d'ensemble du système"
      />
      <AdminNav />

      <main className="admin-main">
        <div className="dashboard-content">
          {/* Message de bienvenue */}
          <section className="welcome-section">
            <h2>Bienvenue, {adminData?.prenom} {adminData?.nom}</h2>
            <p>Tableau de bord administrateur - {new Date().toLocaleDateString('fr-FR', { 
              weekday: 'long', 
              year: 'numeric', 
              month: 'long', 
              day: 'numeric' 
            })}</p>
          </section>

         

          {/* Alertes urgentes */}
          {alertes_urgentes && alertes_urgentes.length > 0 && (
            <section className="welcome-section urgent-alerts">
              <h2>🚨 Alertes urgentes</h2>
              <div className="stats-grid">
                {alertes_urgentes.map((alerte, index) => (
                  <div key={index} className="stat-card urgent">
                    <div className="stat-icon">{alerte.icone}</div>
                    <div className="stat-content">
                      <h3>{alerte.titre}</h3>
                      <p className="stat-number">{alerte.count}</p>
                      <span className="stat-label">{alerte.message}</span>
                    </div>
                  </div>
                ))}
              </div>
            </section>
          )}

          {/* Statistiques principales */}
          <section className="welcome-section">
            <h2>Vue d'ensemble du système</h2>
            <div className="stats-grid">
              <div className="stat-card">
                <div className="stat-icon">📅</div>
                <div className="stat-content">
                  <h3>Rendez-vous</h3>
                  <p className="stat-number">{stats.rdv_total}</p>
                  <span className="stat-label">
                    {stats.rdv_en_attente} en attente • {stats.rdv_acceptes} acceptés
                  </span>
                </div>
              </div>

              <div className="stat-card">
                <div className="stat-icon">📋</div>
                <div className="stat-content">
                  <h3>Réclamations</h3>
                  <p className="stat-number">{stats.reclamations_total}</p>
                  <span className="stat-label">
                    {stats.reclamations_actives} actives • {stats.reclamations_resolues} résolues
                  </span>
                </div>
              </div>

              <div className="stat-card">
                <div className="stat-icon">📄</div>
                <div className="stat-content">
                  <h3>Documents</h3>
                  <p className="stat-number">{stats.documents_total}</p>
                  <span className="stat-label">
                    {stats.documents_en_attente} en attente • {stats.certificats_expires} expirés
                  </span>
                </div>
              </div>

              <div className="stat-card">
                <div className="stat-icon">👥</div>
                <div className="stat-content">
                  <h3>Utilisateurs</h3>
                  <p className="stat-number">{stats.total_agents_actifs + stats.total_retraites}</p>
                  <span className="stat-label">
                    {stats.total_agents_actifs} agents • {stats.total_retraites} retraités
                  </span>
                </div>
              </div>

              <div className="stat-card">
                <div className="stat-icon">🌐</div>
                <div className="stat-content">
                  <h3>Connexions aujourd'hui</h3>
                  <p className="stat-number">{stats.connexions_today}</p>
                  <span className="stat-label">Utilisateurs connectés</span>
                </div>
              </div>
            </div>
          </section>

          {/* Performance ce mois */}
          <section className="welcome-section">
            <h2>Performance ce mois</h2>
            <div className="stats-grid">
              <div className="stat-card">
                <div className="stat-icon">✅</div>
                <div className="stat-content">
                  <h3>RDV traités</h3>
                  <p className="stat-number">{stats.rdv_traites_mois}</p>
                  <span className="stat-label">Ce mois-ci</span>
                </div>
              </div>
              <div className="stat-card">
                <div className="stat-icon">🎯</div>
                <div className="stat-content">
                  <h3>Réclamations résolues</h3>
                  <p className="stat-number">{stats.reclamations_resolues_mois}</p>
                  <span className="stat-label">Ce mois-ci</span>
                </div>
              </div>
              <div className="stat-card">
                <div className="stat-icon">📋</div>
                <div className="stat-content">
                  <h3>Documents validés</h3>
                  <p className="stat-number">{stats.documents_valides_mois}</p>
                  <span className="stat-label">Ce mois-ci</span>
                </div>
              </div>
              <div className="stat-card">
                <div className="stat-icon">⏱️</div>
                <div className="stat-content">
                  <h3>Temps moyen</h3>
                  <p className="stat-number">{stats.temps_moyen_traitement}h</p>
                  <span className="stat-label">Délai traitement</span>
                </div>
              </div>
            </div>
          </section>

          {/* Actions rapides */}
          <section className="quick-actions">
            <h3>Actions rapides</h3>
            <div className="actions-grid">
              <button className="action-btn" onClick={() => navigate('/admin/rendez-vous')}>
                <div className="action-icon">📅</div>
                <span>Gérer les RDV</span>
                <small>{stats.rdv_en_attente} en attente</small>
              </button>
              
              <button className="action-btn" onClick={() => navigate('/admin/reclamations')}>
                <div className="action-icon">📋</div>
                <span>Traiter réclamations</span>
                <small>{stats.reclamations_actives} actives</small>
              </button>
              
              <button className="action-btn" onClick={() => navigate('/admin/documents')}>
                <div className="action-icon">📄</div>
                <span>Valider documents</span>
                <small>{stats.documents_en_attente} en attente</small>
              </button>
              
              <button className="action-btn" onClick={() => navigate('/admin/messages')}>
                <div className="action-icon">💬</div>
                <span>Envoyer message</span>
                <small>Aux utilisateurs</small>
              </button>
              
              <button className="action-btn" onClick={() => alert('Gestion utilisateurs - À implémenter')}>
                <div className="action-icon">👥</div>
                <span>Gérer utilisateurs</span>
                <small>{stats.total_agents_actifs + stats.total_retraites} actifs</small>
              </button>
              
              <button className="action-btn" onClick={() => alert('Rapports - À implémenter')}>
                <div className="action-icon">📊</div>
                <span>Générer rapport</span>
                <small>Export PDF/Excel</small>
              </button>
            </div>
          </section>

          {/* Activités récentes */}
          {activites_recentes && activites_recentes.length > 0 && (
            <section className="welcome-section">
              <h2>Activités récentes</h2>
              <div className="admin-info-card">
                {activites_recentes.slice(0, 8).map((activite, index) => (
                  <div key={index} className="info-item">
                    <strong>{activite.icone} {activite.titre}:</strong> {activite.description}
                    <br />
                    <small>Par {activite.admin} • {new Date(activite.date).toLocaleString('fr-FR')}</small>
                  </div>
                ))}
              </div>
            </section>
          )}

          {/* Répartition par type */}
          {(repartition_types?.rdv_par_motif?.length > 0 || repartition_types?.reclamations_par_type?.length > 0) && (
            <section className="welcome-section">
              <h2>Répartition par type</h2>
              <div className="stats-grid">
                {repartition_types?.rdv_par_motif?.length > 0 && (
                  <div className="admin-info-card">
                    <h3>Motifs de rendez-vous</h3>
                    <div className="info-grid">
                      {repartition_types.rdv_par_motif.slice(0, 6).map((item, index) => (
                        <div key={index} className="info-item">
                          <strong>{item.motif}:</strong> {item.total}
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {repartition_types?.reclamations_par_type?.length > 0 && (
                  <div className="admin-info-card">
                    <h3>Types de réclamations</h3>
                    <div className="info-grid">
                      {repartition_types.reclamations_par_type.slice(0, 6).map((item, index) => (
                        <div key={index} className="info-item">
                          <strong>{item.type}:</strong> {item.total}
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </section>
          )}

          {/* Status système */}
          <section className="test-section">
            <h3>Status système</h3>
            <div className="test-status">
              <div className="test-item">
                <span className="status-icon">✅</span>
                <span>Connexion admin active - {adminData?.nom_complet}</span>
              </div>
              <div className="test-item">
                <span className="status-icon">✅</span>
                <span>API Dashboard fonctionnelle</span>
              </div>
              <div className="test-item">
                <span className="status-icon">📊</span>
                <span>Données temps réel - {new Date().toLocaleTimeString('fr-FR')}</span>
              </div>
              <div className="test-item">
                <span className="status-icon">👤</span>
                <span>Rôle: {adminData?.role}</span>
              </div>
            </div>
            <div className="test-actions">
              <button className="logout-btn" onClick={handleLogout}>Déconnexion</button>
            </div>
          </section>
        </div>
      </main>
    </div>
  );
};

export default AdminDashboard;