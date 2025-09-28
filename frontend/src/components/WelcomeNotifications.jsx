import React, { useState, useEffect } from 'react';

const WelcomeNotifications = ({ user, userType }) => {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isVisible, setIsVisible] = useState(true);
  const [notifications, setNotifications] = useState([]);

  // Fonction pour déterminer la civilité
  const getCivilite = (sexe, situationMatrimoniale) => {
    if (!sexe) return '';
    
    const sexeNormalized = sexe.toString().toUpperCase();
    
    if (sexeNormalized === 'M' || sexeNormalized === 'MASCULIN' || sexeNormalized === 'HOMME') {
      return 'M.';
    } else if (sexeNormalized === 'F' || sexeNormalized === 'FEMININ' || sexeNormalized === 'FEMME') {
      const situation = situationMatrimoniale?.toLowerCase();
      if (situation === 'célibataire' || situation === 'celibataire') {
        return 'Mlle';
      } else {
        return 'Mme';
      }
    }
    return '';
  };

  // Créer les notifications une seule fois
  useEffect(() => {
    if (!user) return;

    const civilite = getCivilite(user.sexe, user.situation_matrimoniale);
    const nomComplet = `${user.prenoms} ${user.nom}`;
    
    const welcomeNotifications = [
      {
        id: 'welcome',
        type: 'success',
        icon: '👋',
        title: 'Bienvenue !',
        message: `Bonjour ${civilite} ${nomComplet}, bienvenue sur votre tableau de bord !`,
        duration: 4000
      },
      {
        id: 'rdv-reminder',
        type: 'info',
        icon: '📅',
        title: 'Rappel important',
        message: 'N\'oubliez pas de consulter régulièrement vos demandes de rendez-vous.',
        duration: 4000
      },
      {
        id: 'email-reminder',
        type: 'warning',
        icon: '📧',
        title: 'Suivi des réclamations',
        message: 'Pensez à vérifier votre boîte mail pour le suivi de vos réclamations.',
        duration: 4000
      }
    ];

    setNotifications(welcomeNotifications);
  }, [user]);

  // Auto-slide des notifications
  useEffect(() => {
    if (notifications.length === 0) return;

    const timer = setTimeout(() => {
      if (currentIndex < notifications.length - 1) {
        // Passer à la notification suivante
        setCurrentIndex(prev => prev + 1);
      } else {
        // Dernière notification - disparaître en dégradé
        setIsVisible(false);
      }
    }, notifications[currentIndex]?.duration || 4000);

    return () => clearTimeout(timer);
  }, [currentIndex, notifications]);

  // Fonction pour passer manuellement à la notification suivante
  const nextNotification = () => {
    if (currentIndex < notifications.length - 1) {
      setCurrentIndex(prev => prev + 1);
    } else {
      setIsVisible(false);
    }
  };

  // Fonction pour fermer toutes les notifications
  const closeNotifications = () => {
    setIsVisible(false);
  };

  // Ne pas afficher si pas visible ou pas de notifications
  if (!isVisible || notifications.length === 0) return null;

  const currentNotification = notifications[currentIndex];
  if (!currentNotification) return null;

  const getNotificationStyles = (type) => {
    const styles = {
      success: {
        backgroundColor: '#10b981',
        borderColor: '#059669',
        iconBg: '#ecfdf5',
        iconColor: '#059669'
      },
      info: {
        backgroundColor: '#3b82f6',
        borderColor: '#2563eb',
        iconBg: '#eff6ff',
        iconColor: '#2563eb'
      },
      warning: {
        backgroundColor: '#f59e0b',
        borderColor: '#d97706',
        iconBg: '#fffbeb',
        iconColor: '#d97706'
      }
    };
    return styles[type] || styles.info;
  };

  const notifStyles = getNotificationStyles(currentNotification.type);

  return (
    <>
      <div className="welcome-notification-container">
        <div 
          className="welcome-notification"
          style={{ 
            backgroundColor: notifStyles.backgroundColor,
            borderColor: notifStyles.borderColor 
          }}
        >
          <div className="welcome-notification__content">
            <div 
              className="welcome-notification__icon"
              style={{ 
                backgroundColor: notifStyles.iconBg,
                color: notifStyles.iconColor
              }}
            >
              {currentNotification.icon}
            </div>
            
            <div className="welcome-notification__text">
              <h4 className="welcome-notification__title">
                {currentNotification.title}
              </h4>
              <p className="welcome-notification__message">
                {currentNotification.message}
              </p>
            </div>
          </div>

          <div className="welcome-notification__actions">
            {/* Bouton fermer */}
            <button
              className="welcome-notification__close"
              onClick={closeNotifications}
              type="button"
              title="Fermer toutes les notifications"
            >
              ✕
            </button>
            
            {/* Bouton suivant (sauf sur la dernière) */}
            {currentIndex < notifications.length - 1 && (
              <button
                className="welcome-notification__skip"
                onClick={nextNotification}
                type="button"
                title="Notification suivante"
              >
                →
              </button>
            )}
          </div>

          {/* Indicateur de progression */}
          <div className="welcome-notification__progress">
            <div className="welcome-notification__progress-bar">
              {notifications.map((_, index) => (
                <div 
                  key={index}
                  className={`welcome-notification__progress-dot ${index <= currentIndex ? 'active' : ''}`}
                />
              ))}
            </div>
            <span className="welcome-notification__progress-text">
              {currentIndex + 1} / {notifications.length}
            </span>
          </div>

          {/* Barre de progression temporelle */}
          <div className="welcome-notification__timer">
            <div 
              className="welcome-notification__timer-bar"
              style={{
                animation: `timerProgress ${currentNotification.duration}ms linear`
              }}
            />
          </div>
        </div>
      </div>

      <style>{`
        .welcome-notification-container {
          position: fixed;
          top: 20px;
          right: 20px;
          z-index: 9999;
          pointer-events: none;
        }

        .welcome-notification {
          background: #10b981;
          color: white;
          border-radius: 12px;
          padding: 16px;
          min-width: 320px;
          max-width: 400px;
          box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
          border: 1px solid;
          animation: slideInRight 0.5s ease-out;
          pointer-events: all;
          position: relative;
          overflow: hidden;
        }

        @keyframes slideInRight {
          from {
            transform: translateX(100%);
            opacity: 0;
          }
          to {
            transform: translateX(0);
            opacity: 1;
          }
        }

        .welcome-notification__content {
          display: flex;
          align-items: flex-start;
          gap: 12px;
          margin-right: 60px;
          margin-bottom: 16px;
        }

        .welcome-notification__icon {
          width: 40px;
          height: 40px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 18px;
          flex-shrink: 0;
          font-weight: bold;
          animation: iconPulse 2s infinite;
        }

        @keyframes iconPulse {
          0%, 100% { transform: scale(1); }
          50% { transform: scale(1.05); }
        }

        .welcome-notification__text {
          flex: 1;
        }

        .welcome-notification__title {
          margin: 0 0 4px 0;
          font-size: 16px;
          font-weight: 600;
          color: white;
        }

        .welcome-notification__message {
          margin: 0;
          font-size: 14px;
          line-height: 1.4;
          color: rgba(255, 255, 255, 0.95);
        }

        .welcome-notification__actions {
          position: absolute;
          top: 8px;
          right: 8px;
          display: flex;
          gap: 4px;
        }

        .welcome-notification__close,
        .welcome-notification__skip {
          background: rgba(255, 255, 255, 0.2);
          border: none;
          color: white;
          font-size: 16px;
          font-weight: bold;
          cursor: pointer;
          padding: 6px;
          border-radius: 6px;
          transition: all 0.2s ease;
          line-height: 1;
          width: 28px;
          height: 28px;
          display: flex;
          align-items: center;
          justify-content: center;
        }

        .welcome-notification__close:hover,
        .welcome-notification__skip:hover {
          background: rgba(255, 255, 255, 0.3);
          transform: scale(1.1);
        }

        .welcome-notification__skip {
          font-size: 14px;
          font-weight: normal;
        }

        .welcome-notification__progress {
          display: flex;
          align-items: center;
          justify-content: space-between;
          margin-bottom: 8px;
          padding-top: 8px;
          border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .welcome-notification__progress-bar {
          display: flex;
          gap: 4px;
        }

        .welcome-notification__progress-dot {
          width: 8px;
          height: 8px;
          border-radius: 50%;
          background: rgba(255, 255, 255, 0.3);
          transition: all 0.3s ease;
        }

        .welcome-notification__progress-dot.active {
          background: rgba(255, 255, 255, 0.9);
          transform: scale(1.2);
        }

        .welcome-notification__progress-text {
          font-size: 12px;
          color: rgba(255, 255, 255, 0.8);
          font-weight: 500;
        }

        .welcome-notification__timer {
          position: absolute;
          bottom: 0;
          left: 0;
          width: 100%;
          height: 3px;
          background: rgba(255, 255, 255, 0.2);
        }

        .welcome-notification__timer-bar {
          height: 100%;
          background: rgba(255, 255, 255, 0.6);
          width: 0%;
        }

        @keyframes timerProgress {
          from { width: 0%; }
          to { width: 100%; }
        }

        /* Responsive */
        @media (max-width: 768px) {
          .welcome-notification-container {
            top: 10px;
            right: 10px;
            left: 10px;
            display: flex;
            justify-content: center;
          }

          .welcome-notification {
            min-width: auto;
            max-width: none;
            width: 100%;
          }

          .welcome-notification__content {
            margin-right: 50px;
          }

          .welcome-notification__message {
            font-size: 13px;
          }

          .welcome-notification__actions {
            top: 6px;
            right: 6px;
          }

          .welcome-notification__close,
          .welcome-notification__skip {
            width: 24px;
            height: 24px;
            font-size: 14px;
          }
        }
      `}</style>
    </>
  );
};
  
export default WelcomeNotifications;