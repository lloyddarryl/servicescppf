import React, { useState } from 'react';
import './NotificationsCertificat.css';

const NotificationsCertificat = ({ notifications, onDismiss }) => {
  const [dismissingTypes, setDismissingTypes] = useState(new Set());

  if (!notifications || notifications.length === 0) { 
    return null;
  } 

  // Gérer la fermeture d'une notification
  const handleDismiss = async (type) => {
    if (!onDismiss || dismissingTypes.has(type)) return;
    
    setDismissingTypes(prev => new Set(prev).add(type));
    
    try {
      await onDismiss(type);
    } catch (error) {
      console.error('Erreur lors de la fermeture de notification:', error);
    } finally {
      setDismissingTypes(prev => {
        const newSet = new Set(prev);
        newSet.delete(type);
        return newSet;
      });
    }
  };

  return (
    <section className="notifications-certificat">
      <div className="notifications-certificat__header">
        <h2 className="notifications-certificat__title">
          Notifications Certificat de Vie
        </h2>
        <span className="notifications-certificat__count">
          {notifications.length}
        </span>
      </div>

      <div className="notifications-certificat__list">
        {notifications.map((notification, index) => (
          <div
            key={`${notification.type}-${index}`}
            className={`notification-card notification-card--${notification.niveau}`}
            style={{ '--notification-color': notification.couleur }}
          >
            
            {/* Icône et contenu principal */}
            <div className="notification-card__content">
              <div className="notification-card__icon">
                {notification.icone}
              </div>
              <div className="notification-card__text">
                <h3 className="notification-card__title">
                  {notification.titre}
                </h3>
                <p className="notification-card__message">
                  {notification.message}
                </p>
              </div>
            </div>

            {/* Actions */}
            <div className="notification-card__actions">
              {notification.dismissible && (
                <button
                  className="notification-card__dismiss"
                  onClick={() => handleDismiss(notification.type)}
                  disabled={dismissingTypes.has(notification.type)}
                  title="Masquer cette notification"
                >
                  {dismissingTypes.has(notification.type) ? '⏳' : '×'}
                </button>
              )}
            </div>

            {/* Barre de couleur selon le niveau */}
            <div className="notification-card__bar"></div>
          </div>
        ))}
      </div>

      {/* Informations supplémentaires */}
      <div className="notifications-certificat__info">
        <p className="notifications-certificat__help">
          <strong>Rappel :</strong> Les certificats de vie sont obligatoires et doivent être renouvelés chaque année. 
          Ils attestent que vous êtes toujours en vie et permettent de maintenir le versement de votre pension.
        </p>
      </div>
    </section>
  );
};

export default NotificationsCertificat;