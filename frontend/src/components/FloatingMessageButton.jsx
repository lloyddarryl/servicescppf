import { useState, useEffect } from 'react';
import messageApi from '../services/messageApi';
import MessageModal from './MessageModal';
import './FloatingMessageButton.css';

const FloatingMessageButton = () => {
  const [unreadCount, setUnreadCount] = useState(0);
  const [showModal, setShowModal] = useState(false);

  // Récupérer le nombre de messages non lus
  const fetchUnreadCount = async () => {
    try {
      const response = await messageApi.getUnreadCount();
      if (response.success) {
        setUnreadCount(response.unread_count);
      }
    } catch (error) {
      console.error('Erreur récupération notifications:', error);
    }
  };

  useEffect(() => {
    fetchUnreadCount();

    // Rafraîchir toutes les 30 secondes
    const interval = setInterval(() => {
      fetchUnreadCount();
    }, 30000);

    return () => clearInterval(interval);
  }, []);

  const handleOpenModal = () => {
    setShowModal(true);
  };

  const handleCloseModal = () => {
    setShowModal(false);
    // Rafraîchir le compteur après fermeture
    fetchUnreadCount();
  };

  return (
    <>
      {/* Bouton flottant */}
      <button 
        className="floating-message-btn"
        onClick={handleOpenModal}
        aria-label="Messages"
        title="Ouvrir la messagerie"
      >
        <div className="floating-message-btn__icon">
          💬
        </div>
        {unreadCount > 0 && (
          <div className="floating-message-btn__badge">
            {unreadCount > 99 ? '99+' : unreadCount}
          </div>
        )}
        <div className="floating-message-btn__pulse"></div>
      </button>

      {/* Modal de messagerie */}
      {showModal && (
        <MessageModal 
          onClose={handleCloseModal}
          initialUnreadCount={unreadCount}
        />
      )}
    </>
  );
};

export default FloatingMessageButton;