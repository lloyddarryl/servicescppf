import React, { useState } from 'react';

const RdvNotifications = ({ notifications = [] }) => {
  const [expandedNotifs, setExpandedNotifs] = useState(new Set());

  const toggleExpanded = (notifId) => {
    const newExpanded = new Set(expandedNotifs);
    if (newExpanded.has(notifId)) {
      newExpanded.delete(notifId);
    } else {
      newExpanded.add(notifId);
    }
    setExpandedNotifs(newExpanded);
  };

  const getPriorityOrder = (priorite) => {
    const ordre = {
      'critique': 1,
      'urgent': 2,
      'haute': 3,
      'normale': 4
    };
    return ordre[priorite] || 5;
  };

  const notificationsSortees = [...notifications].sort((a, b) => {
    return getPriorityOrder(a.priorite) - getPriorityOrder(b.priorite);
  });

  if (notifications.length === 0) {
    return null;
  }

  return (
    <section className="dashboard__rdv-notifications">
      <div className="dashboard__section-header">
        <h2 className="dashboard__section-title">
          Notifications Rendez-vous
          <span className="dashboard__notif-count">{notifications.length}</span>
        </h2>
      </div>

      <div className="dashboard__notifications-list">
        {notificationsSortees.map((notif) => (
          <div
            key={notif.id}
            className={`dashboard__notification-card dashboard__notification-card--${notif.priorite}`}
            style={{ borderLeftColor: notif.couleur }}
          >
            <div className="dashboard__notification-header">
              <div className="dashboard__notification-icon">
                {notif.icone}
              </div>
              <div className="dashboard__notification-title-section">
                <h3 className="dashboard__notification-title">
                  {notif.titre}
                </h3>
                <span className="dashboard__notification-numero">
                  N° {notif.numero_demande}
                </span>
              </div>
              <div className="dashboard__notification-meta">
                {notif.delai_jours !== undefined && (
                  <span className="dashboard__notification-delai">
                    {notif.delai_jours === 0 
                      ? `${Math.ceil(notif.delai_heures)}h`
                      : `${Math.ceil(notif.delai_jours)}j`
                    }
                  </span>
                )}
              </div>
            </div>

            <div className="dashboard__notification-body">
              <p className="dashboard__notification-message">
                {notif.message}
              </p>

              {notif.motif && (
                <div className="dashboard__notification-details">
                  <strong>Motif:</strong> {notif.motif}
                </div>
              )}

              {expandedNotifs.has(notif.id) && (
                <div className="dashboard__notification-expanded">
                  {notif.lieu_rdv && (
                    <div className="dashboard__notification-lieu">
                      <strong>Lieu:</strong> {notif.lieu_rdv}
                    </div>
                  )}
                  <div className="dashboard__notification-date-complete">
                    <strong>Date et heure:</strong> {notif.date_rdv}
                  </div>
                </div>
              )}
            </div>

            <div className="dashboard__notification-actions">
              {notif.actions?.map((action, index) => (
                <button
                  key={index}
                  className={`dashboard__notification-btn dashboard__notification-btn--${action.type}`}
                  onClick={() => {
                    if (action.url === '#') {
                      toggleExpanded(notif.id);
                    } else {
                      window.location.href = action.url;
                    }
                  }}
                >
                  {action.label}
                </button>
              ))}
              
              <button
                className="dashboard__notification-btn dashboard__notification-btn--toggle"
                onClick={() => toggleExpanded(notif.id)}
              >
                {expandedNotifs.has(notif.id) ? 'Réduire' : 'Détails'}
              </button>
            </div>
          </div>
        ))}
      </div>
    </section>
  );
};

export default RdvNotifications;