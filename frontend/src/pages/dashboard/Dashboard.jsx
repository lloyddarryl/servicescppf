import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../../components/Header';
import { authService, utils } from '../../services/api';
import { apiCall } from '../../services/urlHelper';
import RdvNotifications from '../../components/RdvNotifications';
import WelcomeNotifications from '../../components/WelcomeNotifications';
import './Dashboard.css';

const Dashboard = () => {
  const navigate = useNavigate();
  const [dashboardData, setDashboardData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  
  // État pour gérer l'affichage de la notification téléphone
  const [showPhoneNotification, setShowPhoneNotification] = useState(true);
  
  // ✅ NOUVEAU : État pour gérer l'affichage des notifications de bienvenue
  const [showWelcomeNotifications, setShowWelcomeNotifications] = useState(false);
  
  // État pour gérer l'affichage des activités
  const [showAllActivities, setShowAllActivities] = useState(false);

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
        
        // ✅ DÉCLENCHER LES NOTIFICATIONS DE BIENVENUE
        // Vérifier si c'est une nouvelle session (pas de flag dans sessionStorage)
        const hasSeenWelcome = sessionStorage.getItem('welcome_notifications_shown');
        if (!hasSeenWelcome && data.user) {
          setShowWelcomeNotifications(true);
          sessionStorage.setItem('welcome_notifications_shown', 'true');
        }
        
        console.log('🎯 Dashboard data loaded:', data);
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
      // ✅ NETTOYER LE FLAG DE BIENVENUE À LA DÉCONNEXION
      sessionStorage.removeItem('welcome_notifications_shown');
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
        attestations: '/actifs/attestations',
        profil: '/actifs/profil',
        reclamations: '/actifs/reclamations',
        prise_rdv: '/actifs/rendez-vous',
        rendez_vous: '/actifs/rendez-vous',
      },
      retraite: {
        pension: '/retraites/pension', 
        grappe_familiale: '/retraites/grappe-familiale', 
        certificats: '/retraites/certificats-vie',
        historique: '/retraites/historique',
        attestations: '/retraites/attestations',
        profil: '/retraites/profil',
        reclamations: '/retraites/reclamations', 
        rendez_vous: '/retraites/rendez-vous', 
        prise_rdv: '/retraites/rendez-vous', 
        documents: '/retraites/documents',
        "historique-paiements": "/retraites/historique-paiements",
      }
    };

    const targetUrl = serviceUrls[userType]?.[serviceId];
    if (targetUrl) {
      navigate(targetUrl);
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR').format(amount || 0) + ' FCFA';
  };

  // Fonction améliorée pour formater la durée de service
  const formatDureeService = (annees, mois) => {
    if (!annees && !mois) return 'Aucune donnée';
    
    const parts = [];
    // Arrondir les années à l'entier
    const anneesEntier = Math.floor(annees);
    
    if (anneesEntier > 0) {
      parts.push(`${anneesEntier} an${anneesEntier > 1 ? 's' : ''}`);
    }
    if (mois > 0) {
      parts.push(`${mois} mois`);
    }
    
    return parts.length > 0 ? parts.join(' et ') : 'Débutant';
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'completed':
        return '✅';
      case 'pending':
        return '⏳';
      case 'warning':
        return '⚠️';
      case 'in_progress':
        return '🔄';
      default:
        return 'ℹ️';
    }
  };

  // Fonction améliorée pour les descriptions d'activités
  const getActivityTypeLabel = (type) => {
    const labels = {
      'cotisation': 'Cotisation',
      'simulation': 'Simulation',
      'rendez_vous': 'Rendez-vous',
      'reclamation': 'Réclamation',
      'prestation': 'Prestation',
      'pension': 'Pension'
    };
    return labels[type] || 'Activité';
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
      'file-alt': '📃',
      'certificate': '📜',
      'calendar': '📅',
      'phone': '📞',
      'envelope': '✉️',
      'shield': '🛡️',
      'heart': '❤️',
      'star': '⭐',
      'home': '🏠',
      'info': 'ℹ️',
      'question': '❓',
      'check': '✔️',
      'times': '❌',
      'exclamation': '❗',
      'plus': '➕',
      'minus': '➖',
      'arrow-right': '➡️',
      'arrow-left': '⬅️',
      'arrow-up': '⬆️',
      'arrow-down': '⬇️',
      'search': '🔍',
      'reclamation': '📢',
    };
    return icons[iconName] || '📌';
  };

  // Fonction améliorée pour afficher le titre "M." ou "Mme" en fonction du sexe
  const getGenderTitle = (sexe) => {
    if (!sexe) return '';
    
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
        return '';
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

  // Fonction pour déterminer si on doit afficher la notification téléphone
  const shouldShowPhoneNotification = () => {
    return dashboardData && 
           dashboardData.user && 
           dashboardData.user.telephone && 
           !dashboardData.user.phone_verified &&
           showPhoneNotification;
  };

  // Fonction pour masquer temporairement la notification
  const dismissPhoneNotification = () => {
    setShowPhoneNotification(false);
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
      
      {/* ✅ NOTIFICATIONS DE BIENVENUE POP-UP */}
      {showWelcomeNotifications && (
        <WelcomeNotifications 
          user={user} 
          userType={user_type} 
        />
      )}
      
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

          {/* Notification téléphone non vérifié */}
          {shouldShowPhoneNotification() && (
            <section className="dashboard__system-notifications">
              <div className="dashboard__notification dashboard__notification--warning">
                <div className="dashboard__notification-icon">📱</div>
                <div className="dashboard__notification-content">
                  <h3 className="dashboard__notification-title">Téléphone non vérifié</h3>
                  <p className="dashboard__notification-message">
                    Votre numéro de téléphone n'est pas vérifié. Vérifiez-le pour sécuriser votre compte et recevoir les notifications importantes.
                  </p>
                  <button 
                    className="dashboard__notification-action"
                    onClick={() => handleServiceClick('profil')}
                    type="button"
                  >
                    Vérifier maintenant
                  </button>
                </div>
                <button 
                  className="dashboard__notification-dismiss"
                  onClick={dismissPhoneNotification}
                  type="button"
                  title="Masquer cette notification"
                >
                  ✕
                </button>
              </div>
            </section>
          )}

          {/* Notifications RDV améliorées */}
          {dashboard.notifications_rdv && 
           dashboard.notifications_rdv.notifications && 
           dashboard.notifications_rdv.notifications.length > 0 && (
            <RdvNotifications notifications={dashboard.notifications_rdv} />
          )}

          {/* Stats Cards avec données dynamiques */}
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
                    <div className="dashboard__stat-icon">⏳</div>
                    <div className="dashboard__stat-content">
                      <h3 className="dashboard__stat-title">Années de service</h3>
                      <p className="dashboard__stat-value">
                        {formatDureeService(dashboard.stats.duree_service_annees, dashboard.stats.duree_service_mois)}
                      </p>
                    </div>
                  </div>
                  
                  <div className="dashboard__stat-card dashboard__stat-card--info">
                    <div className="dashboard__stat-icon">📅</div>
                    <div className="dashboard__stat-content">
                      <h3 className="dashboard__stat-title">Rendez-vous pris</h3>
                      <p className="dashboard__stat-value">
                        {dashboard.stats.rendez_vous_pris || 0}
                      </p>
                    </div>
                  </div>
                  
                  <div className="dashboard__stat-card dashboard__stat-card--warning">
                    <div className="dashboard__stat-icon">📢</div>
                    <div className="dashboard__stat-content">
                      <h3 className="dashboard__stat-title">Réclamations</h3>
                      <p className="dashboard__stat-value">
                        {dashboard.stats.reclamations_total || 0}
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
                      <h3 className="dashboard__stat-title">Certificats valides</h3>
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

            {/* Activités Section avec affichage compact */}
            <section className="dashboard__activities">
              <div className="dashboard__activities-header">
                <h2 className="dashboard__section-title">Activités Récentes</h2>
                {dashboard.activites_recentes && dashboard.activites_recentes.length > 3 && (
                  <button 
                    className="dashboard__activities-toggle"
                    onClick={() => setShowAllActivities(!showAllActivities)}
                    type="button"
                  >
                    {showAllActivities ? '⬆️ Voir moins' : `⬇️ Voir plus (${dashboard.activites_recentes.length - 3})`}
                  </button>
                )}
              </div>
              
              <div className="dashboard__activities-list">
                {dashboard.activites_recentes && dashboard.activites_recentes.length > 0 ? (
                  (showAllActivities ? dashboard.activites_recentes : dashboard.activites_recentes.slice(0, 3))
                    .map((activite, index) => (
                    <div key={activite.id || index} className="dashboard__activity-item dashboard__activity-item--compact">
                      <div className="dashboard__activity-icon">
                        {getStatusIcon(activite.status)}
                      </div>
                      <div className="dashboard__activity-content">
                        <div className="dashboard__activity-header">
                          <span className="dashboard__activity-type">
                            {getActivityTypeLabel(activite.type)}
                          </span>
                          <span className={`dashboard__activity-status dashboard__activity-status--${activite.status}`}>
                            {activite.status === 'completed' ? 'Terminé' : 
                             activite.status === 'pending' ? 'En attente' :
                             activite.status === 'warning' ? 'Attention' : 'En cours'}
                          </span>
                        </div>
                        <p className="dashboard__activity-description dashboard__activity-description--compact">
                          {activite.description}
                        </p>
                        <div className="dashboard__activity-footer dashboard__activity-footer--compact">
                          <p className="dashboard__activity-date">
                            {new Date(activite.date).toLocaleDateString('fr-FR', {
                              day: 'numeric',
                              month: 'short',
                              hour: '2-digit',
                              minute: '2-digit'
                            })} 
                          </p>
                          {/* Métadonnées compactes */}
                          {activite.metadata && (
                            <div className="dashboard__activity-metadata dashboard__activity-metadata--compact">
                              {activite.metadata.montant && (
                                <span className="dashboard__activity-meta dashboard__activity-meta--compact">
                                  💰 {formatCurrency(activite.metadata.montant)}
                                </span>
                              )}
                              {activite.metadata.numero_demande && (
                                <span className="dashboard__activity-meta dashboard__activity-meta--compact">
                                  🔄 {activite.metadata.numero_demande}
                                </span>
                              )}
                              {activite.metadata.pension_estimee && (
                                <span className="dashboard__activity-meta dashboard__activity-meta--compact">
                                </span>
                              )}
                            </div>
                          )}
                        </div>
                      </div>
                    </div>
                  ))
                ) : (
                  <div className="dashboard__no-activities">
                    <p>Aucune activité récente</p>
                  </div>
                )}
              </div>
            </section>

          </div>

        </div>
      </main>
    </div>
  );
};

export default Dashboard;