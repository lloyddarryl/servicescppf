import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../../components/Header';
import { authService, utils } from '../../services/api';
import { apiCall } from '../../services/urlHelper';
import './Dashboard.css';

const Dashboard = () => {
  const navigate = useNavigate();
  const [dashboardData, setDashboardData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const fetchDashboardData = async () => {
    try {
      // Vérifier l'authentification
      if (!utils.isAuthenticated()) {
        navigate('/services');
        return;
      }

      // Utiliser le helper pour construire l'URL
      const response = await apiCall('/dashboard');
      const data = await response.json();

      if (data.success) {
        setDashboardData(data);
        // Stocker le type d'utilisateur pour les futurs appels
        localStorage.setItem('user_type', data.user_type);
      } else {
        throw new Error(data.message || 'Erreur de chargement');
      }
    } catch (error) {
      console.error('Erreur dashboard:', error);
      setError('Impossible de charger le tableau de bord');
      // Rediriger vers la connexion si erreur d'auth
      if (error.message?.includes('401')) {
        utils.clearSession();
        navigate('/services');
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboardData();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const handleLogout = async () => {
    try {
      await authService.logout();
    } catch (error) {
      console.error('Erreur déconnexion:', error);
    } finally {
      utils.clearSession();
      navigate('/services');
    }
  };

  // Fonction pour gérer les clics sur les services
  const handleServiceClick = (serviceId) => {
    const userType = localStorage.getItem('user_type');
    
    // Construire l'URL selon le type d'utilisateur
    const serviceUrls = {
      actif: {
      simulateur_pension: '/actifs/simulateur-pension',
      grappe_familiale: '/actifs/grappe-familiale',
      cotisations: '/actifs/cotisations',
      prise_rdv: '/actifs/rendez-vous',
      attestations: '/actifs/attestations',
      profil: '/actifs/profil'
      },
     retraite: {
      pension: '/retraites/pension',
      certificats: '/retraites/certificats-vie',
      historique: '/retraites/historique',
      attestations: '/retraites/attestations',
      profil: '/retraites/profil'
      }
    };

    const targetUrl = serviceUrls[userType]?.[serviceId];
    if (targetUrl) {
      navigate(targetUrl);
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'completed':
        return '✅';
      case 'pending':
        return '⏳';
      case 'warning':
        return '⚠️';
      default:
        return 'ℹ️';
    }
  };

  const getServiceIcon = (iconName) => {
    const icons = {
      'document': '📄',
      'users': '👥',
      'chart': '📊',
      'user': '👤',
      'banknotes': '💰',
      'document-check': '📋',
      'pencil': '✏️',
      'academic-cap': '🎓',
      'cog': '⚙️',
      'bell': '🔔',
      'file-alt': '📑',
      'certificate': '📜',
      'calendar': '📅',
    };
    return icons[iconName] || '📌';
  };

  // Fonction améliorée pour afficher le titre "M." ou "Mme" en fonction du sexe
  const getGenderTitle = (sexe) => {
    if (!sexe) return ''; // Si pas de sexe défini, ne pas afficher de titre
    
    // Gérer différents formats possibles
    const sexeNormalized = sexe.toString().toUpperCase();
    
    switch (sexeNormalized) {
      case 'M':
      case 'MASCULIN':
      case 'HOMME':
        return 'M.';
      case 'F':
      case 'FEMININ':
      case 'FEMME':
        return 'Mme';
      default:
        return ''; // Si format non reconnu, ne pas afficher de titre
    }
  };

  // Fonction pour générer un message de bienvenue personnalisé
  const getWelcomeMessage = (user, userType) => {
    const title = getGenderTitle(user.sexe);
    const fullName = `${user.prenoms} ${user.nom}`;
    
    if (userType === 'actif') {
      return `Bienvenue ${title} ${fullName} !`;
    } else {
      return `Bienvenue ${title} ${fullName} !`;
    }
  };

  if (loading) {
    return (
      <div className="dashboard">
        <Header />
        <div className="dashboard__loading">
          <div className="dashboard__spinner"></div>
          <p>Chargement de votre tableau de bord...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="dashboard">
        <Header />
        <div className="dashboard__error">
          <h2>Erreur</h2>
          <p>{error}</p>
          <button onClick={() => fetchDashboardData()}>Réessayer</button>
        </div>
      </div>
    );
  }

  const { user, dashboard, user_type } = dashboardData;

  return (
    <div className="dashboard">
      <Header />
      
      <main className="dashboard__main">
        <div className="dashboard__container">
          
          {/* Welcome Section */}
          <section className="dashboard__welcome">
            <div className="dashboard__welcome-content">
              <div className="dashboard__welcome-text">
                <h1 className="dashboard__title">
                  {getWelcomeMessage(user, user_type)}
                </h1>
              </div>

              <div className="dashboard__welcome-actions">
                <button 
                  className="dashboard__btn"
                  onClick={() => handleServiceClick('profil')}
                  type="button"
                >
                  Editer mon profil
                </button>
              </div>

              <div className="dashboard__welcome-actions">
                <button 
                  className="dashboard__logout-btn"
                  onClick={handleLogout}
                  type="button"
                >
                  Déconnexion
                </button>
              </div>
            </div>
          </section>

          

          {/* Stats Cards */}
          <section className="dashboard__stats">
            <div className="dashboard__stats-grid">
              {user_type === 'actif' ? (
                <>
                  <div className="dashboard__stat-card dashboard__stat-card--primary">
                    <div className="dashboard__stat-icon">💰</div>
                    <div className="dashboard__stat-content">
                      <h3 className="dashboard__stat-title">Cotisations Totales</h3>
                      <p className="dashboard__stat-value">
                        {formatCurrency(dashboard.stats.cotisations_totales)}
                      </p>
                    </div>
                  </div>
                  
                  <div className="dashboard__stat-card dashboard__stat-card--success">
                    <div className="dashboard__stat-icon">🎁</div>
                    <div className="dashboard__stat-content">
                      <h3 className="dashboard__stat-title">Prestations Reçues</h3>
                      <p className="dashboard__stat-value">
                        {formatCurrency(dashboard.stats.prestations_recues)}
                      </p>
                    </div>
                  </div>
                  
                  <div className="dashboard__stat-card dashboard__stat-card--info">
                    <div className="dashboard__stat-icon">📄</div>
                    <div className="dashboard__stat-content">
                      <h3 className="dashboard__stat-title">Attestations</h3>
                      <p className="dashboard__stat-value">
                        {dashboard.stats.attestations_demandees}
                      </p>
                    </div>
                  </div>
                  
                  <div className="dashboard__stat-card dashboard__stat-card--warning">
                    <div className="dashboard__stat-icon">📋</div>
                    <div className="dashboard__stat-content">
                      <h3 className="dashboard__stat-title">Dossiers en Cours</h3>
                      <p className="dashboard__stat-value">
                        {dashboard.stats.dossiers_en_cours}
                      </p>
                    </div>
                  </div>
                </>
              ) : (
                <>
                  <div className="dashboard__stat-card dashboard__stat-card--primary">
                    <div className="dashboard__stat-icon">💰</div>
                    <div className="dashboard__stat-content">
                      <h3 className="dashboard__stat-title">Pension Mensuelle</h3>
                      <p className="dashboard__stat-value">
                        {formatCurrency(dashboard.stats.pension_mensuelle)}
                      </p>
                    </div>
                  </div>
                  
                  <div className="dashboard__stat-card dashboard__stat-card--success">
                    <div className="dashboard__stat-icon">📅</div>
                    <div className="dashboard__stat-content">
                      <h3 className="dashboard__stat-title">Pensions Reçues</h3>
                      <p className="dashboard__stat-value">
                        {dashboard.stats.pensions_recues} mois
                      </p>
                    </div>
                  </div>
                  
                  <div className="dashboard__stat-card dashboard__stat-card--info">
                    <div className="dashboard__stat-icon">💵</div>
                    <div className="dashboard__stat-content">
                      <h3 className="dashboard__stat-title">Total Perçu</h3>
                      <p className="dashboard__stat-value">
                        {formatCurrency(dashboard.stats.total_percu)}
                      </p>
                    </div>
                  </div>
                  
                  <div className="dashboard__stat-card dashboard__stat-card--warning">
                    <div className="dashboard__stat-icon">📋</div>
                    <div className="dashboard__stat-content">
                      <h3 className="dashboard__stat-title">Certificats Valides</h3>
                      <p className="dashboard__stat-value">
                        {dashboard.stats.certificats_valides}
                      </p>
                    </div>
                  </div>
                </>
              )}
            </div>
          </section>

          <div className="dashboard__content-grid">
            
            {/* Services Section */}
            <section className="dashboard__services">
              <h2 className="dashboard__section-title">Services Disponibles</h2>
              <div className="dashboard__services-grid">
                {dashboard.services_disponibles.map(service => (
                  <div 
                    key={service.id}
                    className={`dashboard__service-card ${!service.available ? 'dashboard__service-card--disabled' : ''}`}
                    onClick={() => service.available && handleServiceClick(service.id)}
                    style={{ cursor: service.available ? 'pointer' : 'not-allowed' }}
                  >
                    <div className="dashboard__service-icon">
                      {getServiceIcon(service.icon)}
                    </div>
                    <div className="dashboard__service-content">
                      <h3 className="dashboard__service-title">{service.name}</h3>
                      <p className="dashboard__service-description">{service.description}</p>
                    </div>
                    <div className="dashboard__service-arrow">→</div>
                  </div>
                ))}
              </div>
            </section>

            {/* Activities Section */}
            <section className="dashboard__activities">
              <h2 className="dashboard__section-title">Activités Récentes</h2>
              <div className="dashboard__activities-list">
                {dashboard.activites_recentes.map(activite => (
                  <div key={activite.id} className="dashboard__activity-item">
                    <div className="dashboard__activity-icon">
                      {getStatusIcon(activite.status)}
                    </div>
                    <div className="dashboard__activity-content">
                      <p className="dashboard__activity-description">
                        {activite.description}
                      </p>
                      <p className="dashboard__activity-date">
                        {new Date(activite.date).toLocaleDateString('fr-FR', {
                          day: 'numeric',
                          month: 'long',
                          year: 'numeric'
                        })}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            </section>

          </div>

        </div>
      </main>
    </div>
  );
};

export default Dashboard;