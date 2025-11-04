// Service API pour la messagerie (admins)
import adminApi from './adminApi';

const adminMessageApi = {
  // Récupérer toutes les conversations avec filtres
  getConversations: async (filters = {}) => {
    try {
      const params = new URLSearchParams(filters).toString();
      const response = await adminApi.get(`/messages/conversations?${params}`);
      return response.data;
    } catch (error) {
      console.error('Erreur récupération conversations admin:', error);
      throw error;
    }
  },

  // Récupérer une conversation spécifique
  getConversation: async (conversationId) => {
    try {
      const response = await adminApi.get(`/messages/conversations/${conversationId}`);
      return response.data;
    } catch (error) {
      console.error('Erreur récupération conversation admin:', error);
      throw error;
    }
  },

  // Envoyer un message de réponse
  sendMessage: async (conversationId, data) => {
    try {
      const formData = new FormData();
      formData.append('message', data.message);
      if (data.template_code) formData.append('template_code', data.template_code);

      // Ajouter les pièces jointes
      if (data.attachments && data.attachments.length > 0) {
        data.attachments.forEach((file) => {
          formData.append('attachments[]', file);
        });
      }

      const response = await adminApi.post(
        `/messages/conversations/${conversationId}/messages`,
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        }
      );
      return response.data;
    } catch (error) {
      console.error('Erreur envoi message admin:', error);
      throw error;
    }
  },

  // ✅ NOUVEAU - Modifier un message
  updateMessage: async (messageId, data) => {
    try {
      const response = await adminApi.put(`/messages/messages/${messageId}`, data);
      return response.data;
    } catch (error) {
      console.error('Erreur modification message admin:', error);
      throw error;
    }
  },

  // ✅ NOUVEAU - Supprimer un message
  deleteMessage: async (messageId) => {
    try {
      const response = await adminApi.delete(`/messages/messages/${messageId}`);
      return response.data;
    } catch (error) {
      console.error('Erreur suppression message admin:', error);
      throw error;
    }
  },

  // Créer un message groupé
  createGroupMessage: async (data) => {
    try {
      const response = await adminApi.post('/messages/conversations', data);
      return response.data;
    } catch (error) {
      console.error('Erreur création message groupé:', error);
      throw error;
    }
  },

  // Mettre à jour une conversation
  updateConversation: async (conversationId, data) => {
    try {
      const response = await adminApi.put(`/messages/conversations/${conversationId}`, data);
      return response.data;
    } catch (error) {
      console.error('Erreur mise à jour conversation:', error);
      throw error;
    }
  },

  // Récupérer les templates de réponses
  getTemplates: async () => {
    try {
      const response = await adminApi.get('/messages/templates');
      return response.data;
    } catch (error) {
      console.error('Erreur récupération templates admin:', error);
      throw error;
    }
  },

  // Rechercher des utilisateurs pour message groupé
  searchUsers: async (search, type = 'all') => {
    try {
      const response = await adminApi.get(`/messages/search-users?search=${search}&type=${type}`);
      return response.data;
    } catch (error) {
      console.error('Erreur recherche utilisateurs:', error);
      throw error;
    }
  },
};

export default adminMessageApi;