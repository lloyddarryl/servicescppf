import React, { useState } from 'react';

const RdvNotifications = ({ notifications }) => {
  const [showAll, setShowAll] = useState(false);
  const [expandedNotifs, setExpandedNotifs] = useState(new Set());
  const [dismissedNotifs, setDismissedNotifs] = useState(new Set());

  // Gestion robuste des différents formats de notifications
  let notificationsList = [];
  let totaux = {};

  console.log('RdvNotifications received:', notifications);

  if (!notifications) {
    return null;
  }

  // Gérer les différents formats possibles
  if (Array.isArray(notifications)) {
    notificationsList = notifications;
  } else if (typeof notifications === 'object') {
    if (notifications.notifications && Array.isArray(notifications.notifications)) {
      notificationsList = notifications.notifications;
      totaux = notifications.totaux || {};
    } else if (notifications.length !== undefined) {
      notificationsList = Object.values(notifications);
    }
  }

  // Filtrer les notifications masquées
  notificationsList = notificationsList.filter(notif => !dismissedNotifs.has(notif.id));

  if (!notificationsList || notificationsList.length === 0) {
    return null;
  }

  const notificationsToShow = showAll ? notificationsList : notificationsList.slice(0, 1);
  const hasMore = notificationsList.length > 1;

  const toggleExpanded = (notifId) => {
    const newExpanded = new Set(expandedNotifs);
    if (newExpanded.has(notifId)) {
      newExpanded.delete(notifId);
    } else {
      newExpanded.add(notifId);
    }
    setExpandedNotifs(newExpanded);
  };

  const dismissNotification = (notifId) => {
    setDismissedNotifs(prev => new Set([...prev, notifId]));
  };

  const getPriorityStyles = (priorite) => {
    const styles = {
      critique: {
        borderColor: '#DC2626',
        backgroundColor: 'linear-gradient(135deg, #fef2f2, #ffffff)',
        shadowColor: 'rgba(220, 38, 38, 0.15)',
        pulseClass: 'rdv-notif--critique'
      },
      urgent: {
        borderColor: '#EA580C',
        backgroundColor: 'linear-gradient(135deg, #fff7ed, #ffffff)',
        shadowColor: 'rgba(234, 88, 12, 0.15)',
        pulseClass: 'rdv-notif--urgent'
      },
      haute: {
        borderColor: '#EAB308',
        backgroundColor: 'linear-gradient(135deg, #fefce8, #ffffff)',
        shadowColor: 'rgba(234, 179, 8, 0.15)',
        pulseClass: 'rdv-notif--haute'
      },
      normale: {
        borderColor: '#3B82F6',
        backgroundColor: '#ffffff',
        shadowColor: 'rgba(59, 130, 246, 0.15)',
        pulseClass: 'rdv-notif--normale'
      }
    };
    return styles[priorite] || styles.normale;
  };

  const formatDelai = (delaiJours, delaiHeures) => {
    if (delaiHeures <= 2) return `${Math.ceil(delaiHeures)}h`;
    if (delaiJours <= 1) return 'demain';
    if (delaiJours <= 7) return `${Math.ceil(delaiJours)}j`;
    return `${Math.ceil(delaiJours)} jours`;
  };

  // ✅ CORRECTION : Formater la date et heure du RDV selon la logique de PriseRendezVous
  const formatDateHeure = (dateRdv, heureRdv) => {
    if (!dateRdv) return null;
    
    try {
      let date;
      
      // Gestion de différents formats de date
      if (typeof dateRdv === 'string') {
        if (dateRdv.includes('T')) {
          // Format ISO (2024-09-12T00:00:00.000Z)
          date = new Date(dateRdv);
        } else if (dateRdv.match(/^\d{4}-\d{2}-\d{2}$/)) {
          // Format YYYY-MM-DD
          date = new Date(dateRdv + 'T00:00:00');
        } else {
          date = new Date(dateRdv);
        }
      } else {
        date = new Date(dateRdv);
      }
      
      const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      };
      
      const dateFormatee = date.toLocaleDateString('fr-FR', options);
      
      if (heureRdv) {
        // Nettoyer l'heure (enlever les secondes si présentes)
        const heureNettoyee = heureRdv.toString().substring(0, 5);
        return `${dateFormatee} à ${heureNettoyee}`;
      }
      
      return dateFormatee;
    } catch (error) {
      console.error('Erreur formatage date RDV:', error, { dateRdv, heureRdv });
      return dateRdv;
    }
  };

  // ✅ CORRECTION MAJEURE : Améliorer le message avec date/heure formatée
  const getEnhancedMessage = (notification) => {
    let message = notification.message;
    
    console.log('📄 Processing notification:', {
      id: notification.id,
      originalMessage: message,
      date_rdv: notification.date_rdv,
      heure_rdv: notification.heure_rdv
    });
    
    // Récupérer la date et heure formatées
    const dateHeureFormatee = formatDateHeure(notification.date_rdv, notification.heure_rdv);
    
    if (dateHeureFormatee) {
      // Patterns à remplacer pour améliorer le message
      const patterns = [
        // Pattern principal : "n° RDV-XXXX-XXXXX"
        /n°\s*[A-Z]+-[0-9]+-[A-Za-z0-9]+/gi,
        // Pattern alternatif : "RDV n° XXXX"
        /RDV\s+n°\s*[A-Z0-9-]+/gi,
        // Pattern simple : "demande RDV"
        /demande\s+RDV/gi
      ];
      
      let messageAmeliore = message;
      let patternTrouve = false;
      
      patterns.forEach(pattern => {
        if (pattern.test(messageAmeliore) && !patternTrouve) {
          messageAmeliore = messageAmeliore.replace(pattern, `rendez-vous du ${dateHeureFormatee}`);
          patternTrouve = true;
        }
      });
      
      // Si aucun pattern trouvé mais on a des dates, restructurer le message
      if (!patternTrouve) {
        if (message.toLowerCase().includes('rendez-vous') || message.toLowerCase().includes('rdv')) {
          // Trouver et remplacer "rendez-vous" par "rendez-vous du [date]"
          messageAmeliore = message.replace(
            /(rendez-vous|rdv)/gi, 
            `rendez-vous du ${dateHeureFormatee}`
          );
        } else {
          // Ajouter la date au début du message
          messageAmeliore = `Rendez-vous du ${dateHeureFormatee} : ${message}`;
        }
      }
      
      console.log('✅ Enhanced message:', {
        original: message,
        enhanced: messageAmeliore,
        dateHeure: dateHeureFormatee
      });
      
      return messageAmeliore;
    }
    
    return message;
  };

  // ✅ AMÉLIORER LE TITRE AUSSI
  const getEnhancedTitle = (notification) => {
    let title = notification.titre;
    
    if (notification.date_rdv || notification.heure_rdv) {
      const dateHeureFormatee = formatDateHeure(notification.date_rdv, notification.heure_rdv);
      
      if (dateHeureFormatee) {
        // Si le titre mentionne juste "Demande de rendez-vous", l'améliorer
        if (title && title.toLowerCase().includes('demande de rendez-vous')) {
          title = `Rendez-vous du ${dateHeureFormatee}`;
        } else if (title && title.toLowerCase().includes('rappel')) {
          title = `Rappel - Rendez-vous du ${dateHeureFormatee}`;
        }
      }
    }
    
    return title;
  };

  return (
    <>
      <div className="rdv-notifications">
        <div className="rdv-notifications__header">
          <div className="rdv-notifications__title-section">
            <h2 className="rdv-notifications__title">
              📅 Rendez-vous
              {totaux?.total > 0 && (
                <span className="rdv-notifications__count">
                  {totaux.total}
                </span>
              )}
            </h2>
          </div>
          
          {hasMore && (
            <button 
              className="rdv-notifications__toggle"
              onClick={() => setShowAll(!showAll)}
              type="button"
            >
              {showAll ? '⬆️ Voir moins' : '⬇️ Voir plus'}
            </button>
          )}
        </div>

        <div className="rdv-notifications__list">
          {notificationsToShow.map((notification) => {
            const isExpanded = expandedNotifs.has(notification.id);
            const priorityStyles = getPriorityStyles(notification.priorite);
            
            return (
              <div
                key={notification.id}
                className={`rdv-notification-card ${priorityStyles.pulseClass}`}
                style={{
                  borderLeftColor: priorityStyles.borderColor,
                  background: priorityStyles.backgroundColor,
                  boxShadow: `0 2px 8px ${priorityStyles.shadowColor}`
                }}
              >
                <div className="rdv-notification-card__main">
                  <div className="rdv-notification-card__header">
                    <div className="rdv-notification-card__icon">
                      {notification.icone || '📅'}
                    </div>
                    
                    <div className="rdv-notification-card__info">
                      <div className="rdv-notification-card__title-line">
                        <h3 className="rdv-notification-card__title">
                          {getEnhancedTitle(notification)}
                        </h3>
                        {notification.delai_jours !== undefined && (
                          <span className="rdv-notification-card__delai">
                            {formatDelai(notification.delai_jours, notification.delai_heures)}
                          </span>
                        )}
                      </div>
                      
                      <p className="rdv-notification-card__message">
                        {getEnhancedMessage(notification)}
                      </p>
                      
                      {notification.numero_demande && (
                        <div className="rdv-notification-card__numero">
                          N° {notification.numero_demande}
                        </div>
                      )}
                    </div>

                    {/* Bouton pour masquer la notification */}
                    <button
                      className="rdv-notification-card__dismiss"
                      onClick={() => dismissNotification(notification.id)}
                      type="button"
                      title="Masquer cette notification"
                    >
                      ✕
                    </button>

                    {/* Bouton d'expansion pour les détails */}
                    {(notification.lieu_rdv || notification.motif || notification.date_rdv || notification.heure_rdv) && (
                      <button
                        className="rdv-notification-card__expand"
                        onClick={() => toggleExpanded(notification.id)}
                        type="button"
                        title={isExpanded ? 'Masquer les détails' : 'Voir les détails'}
                      >
                        {isExpanded ? '⬆️' : '⬇️'}
                      </button>
                    )}
                  </div>

                  {/* Détails expandables */}
                  {isExpanded && (notification.lieu_rdv || notification.motif || notification.date_rdv || notification.heure_rdv) && (
                    <div className="rdv-notification-card__details">
                      {(notification.date_rdv || notification.heure_rdv) && (
                        <div className="rdv-notification-card__detail">
                          🕐 <strong>Date/Heure:</strong> {formatDateHeure(notification.date_rdv, notification.heure_rdv)}
                        </div>
                      )}
                      {notification.lieu_rdv && (
                        <div className="rdv-notification-card__detail">
                          📍 <strong>Lieu:</strong> {notification.lieu_rdv}
                        </div>
                      )}
                      {notification.motif && (
                        <div className="rdv-notification-card__detail">
                          💼 <strong>Motif:</strong> {notification.motif}
                        </div>
                      )}
                    </div>
                  )}

                  {/* Actions */}
                  {notification.actions && notification.actions.length > 0 && (
                    <div className="rdv-notification-card__actions">
                      {notification.actions.map((action, index) => (
                        <a
                          key={index}
                          href={action.url}
                          className={`rdv-notification-card__action rdv-notification-card__action--${action.type}`}
                        >
                          {action.label}
                        </a>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            );
          })}
        </div>

        {/* Résumé compact en bas */}
        {totaux && (totaux.rdv_confirmes > 0 || totaux.rdv_en_attente > 0) && (
          <div className="rdv-notifications__summary">
            <div className="rdv-notifications__summary-stats">
              {totaux.rdv_confirmes > 0 && (
                <span className="rdv-notifications__stat rdv-notifications__stat--confirmes">
                  ✅ {totaux.rdv_confirmes} confirmé{totaux.rdv_confirmes > 1 ? 's' : ''}
                </span>
              )}
              {totaux.rdv_en_attente > 0 && (
                <span className="rdv-notifications__stat rdv-notifications__stat--attente">
                  ⏳ {totaux.rdv_en_attente} en attente
                </span>
              )}
            </div>
          </div>
        )}
      </div>

      {/* CSS intégré */}
      <style>{`
        .rdv-notifications {
          background: white;
          border-radius: 12px;
          padding: 20px;
          margin-bottom: 24px;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
          border: 1px solid #e5e7eb;
        }

        .rdv-notifications__header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          margin-bottom: 16px;
          padding-bottom: 12px;
          border-bottom: 1px solid #f3f4f6;
        }

        .rdv-notifications__title {
          font-size: 1.25rem;
          font-weight: 600;
          color: #1f2937;
          margin: 0;
          display: flex;
          align-items: center;
          gap: 8px;
        }

        .rdv-notifications__count {
          background: #ef4444;
          color: white;
          font-size: 0.75rem;
          font-weight: 500;
          padding: 2px 8px;
          border-radius: 12px;
          min-width: 20px;
          text-align: center;
          animation: pulse-count 2s infinite;
        }

        @keyframes pulse-count {
          0%, 100% { transform: scale(1); }
          50% { transform: scale(1.1); }
        }

        .rdv-notifications__toggle {
          background: #f3f4f6;
          color: #4b5563;
          border: 1px solid #d1d5db;
          padding: 6px 12px;
          border-radius: 6px;
          font-size: 0.875rem;
          cursor: pointer;
          transition: all 0.2s ease;
        }

        .rdv-notifications__toggle:hover {
          background: #e5e7eb;
          color: #374151;
        }

        .rdv-notifications__list {
          display: flex;
          flex-direction: column;
          gap: 12px;
          margin-bottom: 16px;
        }

        .rdv-notification-card {
          border: 1px solid #e5e7eb;
          border-radius: 8px;
          padding: 16px;
          border-left-width: 4px;
          transition: all 0.2s ease;
          position: relative;
        }

        .rdv-notification-card:hover {
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
          transform: translateY(-2px);
        }

        .rdv-notif--critique {
          animation: pulse-critical 2s infinite;
        }

        .rdv-notif--urgent {
          animation: pulse-urgent 3s infinite;
        }

        @keyframes pulse-critical {
          0%, 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); }
          50% { box-shadow: 0 0 0 8px rgba(220, 38, 38, 0); }
        }

        @keyframes pulse-urgent {
          0%, 100% { box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.3); }
          50% { box-shadow: 0 0 0 6px rgba(234, 88, 12, 0); }
        }

        .rdv-notification-card__header {
          display: flex;
          align-items: flex-start;
          gap: 12px;
        }

        .rdv-notification-card__icon {
          font-size: 1.25rem;
          min-width: 32px;
          text-align: center;
          flex-shrink: 0;
        }

        .rdv-notification-card__info {
          flex: 1;
        }

        .rdv-notification-card__title-line {
          display: flex;
          align-items: center;
          justify-content: space-between;
          margin-bottom: 4px;
        }

        .rdv-notification-card__title {
          font-size: 1rem;
          font-weight: 600;
          color: #1f2937;
          margin: 0;
        }

        .rdv-notification-card__delai {
          background: #f59e0b;
          color: white;
          padding: 2px 8px;
          border-radius: 12px;
          font-size: 0.75rem;
          font-weight: 500;
          flex-shrink: 0;
        }

        .rdv-notification-card__message {
          color: #4b5563;
          margin: 0 0 8px 0;
          line-height: 1.4;
          font-size: 0.9rem;
        }

        .rdv-notification-card__numero {
          font-size: 0.8rem;
          color: #6b7280;
          font-weight: 500;
        }

        .rdv-notification-card__dismiss {
          position: absolute;
          top: 8px;
          right: 8px;
          background: none;
          border: none;
          color: #9ca3af;
          font-size: 16px;
          cursor: pointer;
          padding: 4px;
          border-radius: 4px;
          transition: all 0.2s ease;
          width: 24px;
          height: 24px;
          display: flex;
          align-items: center;
          justify-content: center;
        }

        .rdv-notification-card__dismiss:hover {
          background-color: rgba(239, 68, 68, 0.1);
          color: #ef4444;
        }

        .rdv-notification-card__expand {
          background: none;
          border: none;
          color: #6b7280;
          font-size: 16px;
          cursor: pointer;
          padding: 4px;
          border-radius: 4px;
          transition: all 0.2s ease;
          flex-shrink: 0;
          margin-right: 30px;
        }

        .rdv-notification-card__expand:hover {
          background-color: rgba(0, 0, 0, 0.05);
          color: #374151;
        }

        .rdv-notification-card__details {
          margin-top: 12px;
          padding-top: 12px;
          border-top: 1px solid #f3f4f6;
          display: flex;
          flex-direction: column;
          gap: 8px;
        }

        .rdv-notification-card__detail {
          font-size: 0.875rem;
          color: #4b5563;
          display: flex;
          align-items: center;
          gap: 8px;
        }

        .rdv-notification-card__actions {
          margin-top: 12px;
          display: flex;
          gap: 8px;
        }

        .rdv-notification-card__action {
          padding: 6px 12px;
          border-radius: 6px;
          font-size: 0.875rem;
          font-weight: 500;
          text-decoration: none;
          transition: all 0.2s ease;
          cursor: pointer;
          display: inline-block;
        }

        .rdv-notification-card__action--primary {
          background: #3b82f6;
          color: white;
          border: 1px solid #3b82f6;
        }

        .rdv-notification-card__action--primary:hover {
          background: #2563eb;
          border-color: #2563eb;
        }

        .rdv-notification-card__action--secondary {
          background: white;
          color: #6b7280;
          border: 1px solid #d1d5db;
        }

        .rdv-notification-card__action--secondary:hover {
          background: #f9fafb;
          color: #4b5563;
        }

        .rdv-notifications__summary {
          padding-top: 12px;
          border-top: 1px solid #f3f4f6;
        }

        .rdv-notifications__summary-stats {
          display: flex;
          gap: 12px;
          justify-content: center;
        }

        .rdv-notifications__stat {
          padding: 4px 8px;
          border-radius: 6px;
          font-size: 0.8rem;
          font-weight: 500;
        }

        .rdv-notifications__stat--confirmes {
          background: #d1fae5;
          color: #065f46;
        }

        .rdv-notifications__stat--attente {
          background: #fef3c7;
          color: #92400e;
        }

        @media (max-width: 768px) {
          .rdv-notifications {
            padding: 16px;
          }

          .rdv-notifications__header {
            flex-direction: column;
            gap: 12px;
            align-items: flex-start;
          }

          .rdv-notifications__toggle {
            align-self: flex-end;
            font-size: 0.8rem;
          }

          .rdv-notification-card {
            padding: 12px;
          }

          .rdv-notification-card__header {
            gap: 8px;
          }

          .rdv-notification-card__title-line {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
          }

          .rdv-notification-card__actions {
            flex-direction: column;
          }

          .rdv-notification-card__action {
            width: 100%;
            text-align: center;
          }

          .rdv-notifications__summary-stats {
            flex-direction: column;
            gap: 6px;
          }

          .rdv-notifications__stat {
            text-align: center;
          }

          .rdv-notification-card__expand {
            margin-right: 24px;
          }
        }

        @keyframes notification-enter {
          from {
            opacity: 0;
            transform: translateY(-10px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        .rdv-notification-card {
          animation: notification-enter 0.3s ease-out;
        }
      `}</style>
    </>
  );
};

export default RdvNotifications;