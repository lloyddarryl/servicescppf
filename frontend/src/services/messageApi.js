// Service API pour la messagerie (utilisateurs)
import { apiCall } from './urlHelper';

const messageApi = {
  // Récupérer toutes les conversations
  getConversations: async () => {
    try {
      const response = await apiCall('/messages/conversations');
      return await response.json();
    } catch (error) {
      console.error('Erreur récupération conversations:', error);
      throw error;
    }
  },

  // Récupérer une conversation spécifique avec ses messages
  getConversation: async (conversationId) => {
    try {
      const response = await apiCall(`/messages/conversations/${conversationId}`);
      return await response.json();
    } catch (error) {
      console.error('Erreur récupération conversation:', error);
      throw error;
    }
  },

  // Créer une nouvelle conversation
  createConversation: async (data) => {
    try {
      const formData = new FormData();
      
      formData.append('sujet', data.sujet || '');
      formData.append('message', data.message || '');
      
      if (data.categorie) formData.append('categorie', data.categorie);
      if (data.priorite) formData.append('priorite', data.priorite);
      if (data.template_code) formData.append('template_code', data.template_code);

      if (data.attachments && data.attachments.length > 0) {
        data.attachments.forEach((file) => {
          formData.append('attachments[]', file);
        });
      }

      const response = await apiCall('/messages/conversations', {
        method: 'POST',
        body: formData,
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
        skipContentType: true,
      });
      
      return await response.json();
    } catch (error) {
      console.error('Erreur création conversation:', error);
      throw error;
    }
  },

  // Envoyer un message dans une conversation
  sendMessage: async (conversationId, data) => {
    try {
      const formData = new FormData();
      formData.append('message', data.message || '');
      if (data.template_code) formData.append('template_code', data.template_code);

      if (data.attachments && data.attachments.length > 0) {
        data.attachments.forEach((file) => {
          formData.append('attachments[]', file);
        });
      }

      const response = await apiCall(`/messages/conversations/${conversationId}/messages`, {
        method: 'POST',
        body: formData,
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
        skipContentType: true,
      });
      
      return await response.json();
    } catch (error) {
      console.error('Erreur envoi message:', error);
      throw error;
    }
  },

  // ✅ NOUVEAU - Modifier un message
  updateMessage: async (messageId, data) => {
    try {
      const response = await apiCall(`/messages/messages/${messageId}`, {
        method: 'PUT',
        body: JSON.stringify(data),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
      });
      return await response.json();
    } catch (error) {
      console.error('Erreur modification message:', error);
      throw error;
    }
  },

  // ✅ NOUVEAU - Supprimer un message
  deleteMessage: async (messageId) => {
    try {
      const response = await apiCall(`/messages/messages/${messageId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
      });
      return await response.json();
    } catch (error) {
      console.error('Erreur suppression message:', error);
      throw error;
    }
  },

  // Récupérer les templates de messages
  getTemplates: async () => {
    try {
      const response = await apiCall('/messages/templates');
      return await response.json();
    } catch (error) {
      console.error('Erreur récupération templates:', error);
      throw error;
    }
  },

  // Obtenir le nombre de messages non lus
  getUnreadCount: async () => {
    try {
      const response = await apiCall('/messages/unread-count');
      return await response.json();
    } catch (error) {
      console.error('Erreur récupération notifications:', error);
      throw error;
    }
  },
};

export default messageApi;