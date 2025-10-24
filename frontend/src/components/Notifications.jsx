import React, { useState, useEffect } from 'react';
import { notificationService } from '../services/api';
import './Notifications.css';

const Notifications = () => {
  const [notifications, setNotifications] = useState([]);
  const [nonLues, setNonLues] = useState(0);
  const [showDropdown, setShowDropdown] = useState(false);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    chargerNotifications();
    
    // Actualiser toutes les 30 secondes
    const interval = setInterval(chargerNotifications, 30000);
    return () => clearInterval(interval);
  }, []);

  const chargerNotifications = async () => {
    try {
      const response = await notificationService.getAll();
      if (response.data.success) {
        setNotifications(response.data.data);
        setNonLues(response.data.non_lues);
      }
    } catch (error) {
      console.error('Erreur chargement notifications:', error);
    }
  };

  const marquerLue = async (id) => {
    try {
      await notificationService.marquerLue(id);
      chargerNotifications();
    } catch (error) {
      console.error('Erreur:', error);
    }
  };

  const marquerToutesLues = async () => {
    try {
      await notificationService.marquerToutesLues();
      chargerNotifications();
    } catch (error) {
      console.error('Erreur:', error);
    }
  };

  return (
    <div className="notifications-container">
      <button 
        className="notifications-bell"
        onClick={() => setShowDropdown(!showDropdown)}
      >
        🔔
        {nonLues > 0 && (
          <span className="notifications-badge">{nonLues}</span>
        )}
      </button>

      {showDropdown && (
        <div className="notifications-dropdown">
          <div className="notifications-header">
            <h3>Notifications ({nonLues})</h3>
            {nonLues > 0 && (
              <button onClick={marquerToutesLues}>
                Tout marquer comme lu
              </button>
            )}
          </div>

          <div className="notifications-list">
            {notifications.length === 0 ? (
              <p className="notifications-vide">Aucune notification</p>
            ) : (
              notifications.map(notif => (
                <div 
                  key={notif.id}
                  className={`notification-item ${!notif.lu ? 'non-lue' : ''}`}
                  onClick={() => {
                    if (!notif.lu) marquerLue(notif.id);
                    if (notif.lien) window.location.href = notif.lien;
                  }}
                >
                  <div className="notification-content">
                    <strong>{notif.titre}</strong>
                    <p>{notif.message}</p>
                    <small>{notif.temps_ecoule}</small>
                  </div>
                  {!notif.lu && <span className="notification-dot"></span>}
                </div>
              ))
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default Notifications;