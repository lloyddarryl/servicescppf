// admin-cppf/src/services/adminApi.js

import axios from 'axios';

const API_BASE_URL = 'http://localhost:8000/api/admin';

const adminApi = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 30000,
});

// Intercepteur pour ajouter le token admin
adminApi.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('admin_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Intercepteur pour gérer les erreurs
adminApi.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('admin_token');
      localStorage.removeItem('admin_data');
      window.location.href = '/admin/login';
    }
    return Promise.reject(error);
  }
);

// Services Auth Admin
export const adminAuthService = {
  login: (credentials) => adminApi.post('/login', credentials),
  logout: () => adminApi.post('/logout'),
  getProfile: () => adminApi.get('/profile'),
};

// Services Dashboard Admin
export const adminDashboardService = {
  getStats: () => adminApi.get('/dashboard'),
  getStatistiques: () => adminApi.get('/statistiques'),
};

// À ajouter dans adminApi.js

// Services Rendez-vous Admin (remplacer la section existante)
export const adminRdvService = {
  // Récupérer tous les RDV avec filtres
  getAll: (params = {}) => {
    const queryParams = new URLSearchParams();
    
    // Ajouter les paramètres de filtrage
    Object.keys(params).forEach(key => {
      if (params[key] !== '' && params[key] !== null && params[key] !== undefined) {
        queryParams.append(key, params[key]);
      }
    });

    return adminApi.get(`/rendez-vous?${queryParams.toString()}`);
  },

  // Récupérer un RDV spécifique
  getById: (id) => adminApi.get(`/rendez-vous/${id}`),

  // Changer le statut d'un RDV
  changerStatut: (id, data) => {
    console.log('Changement statut RDV:', { id, data });
    return adminApi.put(`/rendez-vous/${id}/statut`, data);
  },

  // Traitement en lot
  traitementLot: (data) => {
    console.log('Traitement lot RDV:', data);
    return adminApi.post('/rendez-vous/traitement-lot', data);
  },

  // Statistiques des RDV
  getStatistiques: () => adminApi.get('/rendez-vous/statistiques'),

  // Utilitaires pour les RDV
  utils: {
    // Formater la date et heure
    formatDateHeure: (date, heure) => {
      const dateObj = new Date(date);
      return `${dateObj.toLocaleDateString('fr-FR')} à ${heure}`;
    },
    
    // Obtenir la couleur du statut
    getStatutColor: (statut) => {
      const colors = {
        'en_attente': '#f59e0b',
        'accepte': '#10b981',
        'refuse': '#ef4444',
        'reporte': '#8b5cf6',
        'annule': '#6b7280'
      };
      return colors[statut] || '#6b7280';
    },

    // Obtenir le label du statut
    getStatutLabel: (statut) => {
      const labels = {
        'en_attente': 'En attente',
        'accepte': 'Accepté',
        'refuse': 'Refusé',
        'reporte': 'Reporté',
        'annule': 'Annulé'
      };
      return labels[statut] || statut;
    },

    // Vérifier si un RDV est urgent
    isUrgent: (rdv) => {
      if (rdv.statut !== 'en_attente') return false;
      
      const datesoumission = new Date(rdv.date_soumission);
      const now = new Date();
      const diffInDays = Math.floor((now - datesoumission) / (1000 * 60 * 60 * 24));
      
      return diffInDays > 2;
    },

    // Calculer les jours d'attente
    getJoursAttente: (datesoumission) => {
      const datesoumissionObj = new Date(datesoumission);
      const now = new Date();
      return Math.floor((now - datesoumissionObj) / (1000 * 60 * 60 * 24));
    },

    // Vérifier si un RDV peut être modifié
    peutEtreModifie: (statut) => {
      return ['en_attente', 'reporte'].includes(statut);
    },

    // Messages prédéfinis pour chaque action
    getMessagePredefini: (action) => {
      const messages = {
        accepte: "Votre rendez-vous a été confirmé. Merci de vous présenter à l'heure avec les documents nécessaires.",
        refuse: "Nous regrettons de ne pouvoir donner suite à votre demande. Vous pouvez soumettre une nouvelle demande.",
        reporte: "Votre rendez-vous a été reporté en raison de contraintes d'agenda. Veuillez noter la nouvelle date.",
        annule: "Votre rendez-vous a été annulé. Vous pouvez prendre un nouveau rendez-vous si nécessaire."
      };
      return messages[action] || '';
    },

    // Créneaux horaires disponibles
    getCreneauxDisponibles: () => {
      return [
        { value: '08:00', label: '08h00' },
        { value: '09:00', label: '09h00' },
        { value: '10:00', label: '10h00' },
        { value: '11:00', label: '11h00' },
        { value: '14:00', label: '14h00' },
        { value: '15:00', label: '15h00' },
        { value: '16:00', label: '16h00' },
        { value: '17:00', label: '17h00' }
      ];
    },

    // Valider les données avant envoi
    validerDonneesStatut: (action, data) => {
      const errors = [];

      if (!action) {
        errors.push('Action requise');
      }

      if (action === 'reporte') {
        if (!data.nouvelle_date) {
          errors.push('Nouvelle date requise pour le report');
        }
        if (!data.nouvelle_heure) {
          errors.push('Nouvelle heure requise pour le report');
        }
        
        // Vérifier que la nouvelle date est dans le futur
        const nouvelleDate = new Date(data.nouvelle_date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (nouvelleDate <= today) {
          errors.push('La nouvelle date doit être dans le futur');
        }
      }

      return {
        isValid: errors.length === 0,
        errors
      };
    }
  }
};

// Réclamatins Admin Services
export const adminReclamationService = {
  // Récupérer toutes les réclamations
  getAll: (params = {}) => {
    const queryParams = new URLSearchParams();
    
    Object.keys(params).forEach(key => {
      if (params[key] !== '' && params[key] !== null && params[key] !== undefined) {
        queryParams.append(key, params[key]);
      }
    });

    return adminApi.get(`/reclamations?${queryParams.toString()}`);
  },

  // Récupérer une réclamation spécifique
  getById: (id) => adminApi.get(`/reclamations/${id}`),

  // Traiter une réclamation
  traiter: (id, data) => {
    console.log('Traitement réclamation:', { id, data });
    return adminApi.put(`/reclamations/${id}/traiter`, data);
  },

    // ✅ MÉTHODE CORRIGÉE : Télécharger un document de réclamation (ADMIN)
  downloadDocument: async (reclamationId, documentIndex, documentName = 'document') => {
    try {
      console.log('📥 [ADMIN] Téléchargement document:', { 
        reclamationId, 
        documentIndex, 
        documentName 
      });

      const token = localStorage.getItem('admin_token');
      
      if (!token) {
        throw new Error('Token admin non trouvé');
      }

      // Construire l'URL pour l'admin
      const endpoint = `http://localhost:8000/api/admin/reclamations/${reclamationId}/document/${documentIndex}`;

      // Faire la requête avec fetch
      const response = await fetch(endpoint, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/octet-stream'
        }
      });

      if (!response.ok) {
        const errorText = await response.text();
        console.error('❌ Erreur serveur:', errorText);
        throw new Error(`Erreur ${response.status}: ${response.statusText}`);
      }

      // Récupérer le blob
      const blob = await response.blob();
      console.log('✅ Blob reçu:', { size: blob.size, type: blob.type });
      
      // Créer un URL temporaire
      const url = window.URL.createObjectURL(blob);
      
      // Créer un lien et le cliquer pour déclencher le téléchargement
      const link = document.createElement('a');
      link.href = url;
      link.download = documentName;
      document.body.appendChild(link);
      link.click();
      
      // Nettoyer
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      
      console.log('✅ Document téléchargé avec succès');
      return { success: true };
      
    } catch (error) {
      console.error('❌ Erreur téléchargement document admin:', error);
      throw error;
    }
  },

    // Supprimer une réclamation
  supprimer: (id) => {
    console.log('Suppression réclamation:', id);
    return adminApi.delete(`/reclamations/${id}`);
  },

  // Statistiques
  getStatistiques: () => adminApi.get('/reclamations/statistiques'),

  // Utils
  utils: {
    getStatutLabel: (statut) => {
      const labels = {
        'en_attente': 'En attente',
        'en_cours': 'En cours',
        'en_revision': 'En révision',
        'resolu': 'Résolu',
        'ferme': 'Fermé',
        'rejete': 'Rejeté'
      };
      return labels[statut] || statut;
    },

    getPrioriteLabel: (priorite) => {
      const labels = {
        'basse': 'Basse',
        'normale': 'Normale',
        'haute': 'Haute',
        'urgente': 'Urgente'
      };
      return labels[priorite] || priorite;
    },

    getTypeIcon: (type) => {
      const icons = {
        'cotisation': '💰',
        'prestation': '🎁',
        'pension': '💵',
        'attestation': '📄',
        'compte': '👤',
        'service_client': '📞',
        'technique': '⚙️',
        'autre': '❓'
      };
      return icons[type] || '📋';
    }
,

    // Réponses pré-remplies selon le statut
    getReponsePredefinie: (statut) => {
      const reponses = {
        'en_cours': `Bonjour,

Nous avons bien pris en compte votre réclamation et elle est actuellement en cours de traitement par nos services.

Notre équipe examine attentivement votre demande et nous vous tiendrons informé(e) de l'avancement.

Délai estimé : 3 à 5 jours ouvrables.

Cordialement,
L'équipe CPPF`,

        'en_revision': `Bonjour,

Votre réclamation est en cours de révision par notre service compétent.

Nous procédons à une analyse approfondie de votre dossier afin de vous apporter une réponse adaptée.

Nous vous contacterons prochainement avec plus de détails.

Cordialement,
L'équipe CPPF`,

        'resolu': `Bonjour,

Nous avons le plaisir de vous informer que votre réclamation a été traitée et résolue avec succès.

Les actions nécessaires ont été effectuées et votre demande a été prise en compte.

Si vous avez d'autres questions, n'hésitez pas à nous contacter.

Cordialement,
L'équipe CPPF`,

        'ferme': `Bonjour,

Suite au traitement de votre réclamation, nous clôturons ce dossier.

Toutes les mesures appropriées ont été prises concernant votre demande.

Si vous avez besoin d'une assistance supplémentaire, vous pouvez soumettre une nouvelle réclamation.

Cordialement,
L'équipe CPPF`,

        'rejete': `Bonjour,

Après examen attentif de votre réclamation, nous regrettons de ne pas pouvoir donner une suite favorable à votre demande.

Raison : [Veuillez préciser la raison du rejet]

Si vous souhaitez obtenir plus d'informations ou contester cette décision, vous pouvez nous contacter directement.

Cordialement,
L'équipe CPPF`
      };
      return reponses[statut] || '';
    }
  }
};

// Services Documents Admin
export const adminDocumentService = {
  getAll: (params) => {
    const queryParams = new URLSearchParams(params).toString();
    return adminApi.get(`/documents?${queryParams}`);
  },
  getById: (id) => adminApi.get(`/documents/${id}`),
  download: (id) => adminApi.get(`/documents/${id}/download`, {
    responseType: 'blob'
  }),
  valider: (id, data) => adminApi.put(`/documents/${id}/valider`, data),
  rejeter: (id, data) => adminApi.put(`/documents/${id}/rejeter`, data),
  supprimer: (id, data) => adminApi.delete(`/documents/${id}`, { data }),
  getStatistiques: () => adminApi.get('/documents/statistiques'),
  
  utils: {
    formatFileSize: (bytes) => {
      if (bytes === 0) return '0 B';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    },
    
    getExpirationStatus: (dateExpiration) => {
      if (!dateExpiration) return null;
      
      const now = new Date();
      const expiration = new Date(dateExpiration);
      const diffDays = Math.ceil((expiration - now) / (1000 * 60 * 60 * 24));
      
      if (diffDays < 0) {
        return { status: 'expired', color: '#EF4444', label: 'Expiré' };
      } else if (diffDays <= 30) {
        return { status: 'expiring_soon', color: '#F59E0B', label: 'Expire bientôt' };
      } else {
        return { status: 'valid', color: '#10B981', label: 'Valide' };
      }
    },
    
    downloadBlob: (blob, filename) => {
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', filename);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    }
  }
};

// Services Messages Admin
export const adminMessageService = {
  envoyer: (data) => adminApi.post('/messages/envoyer', data),
  getHistorique: (params) => {
    const queryParams = new URLSearchParams(params).toString();
    return adminApi.get(`/messages/historique?${queryParams}`);
  },
  getMessagesUtilisateur: (userId, userType) => 
    adminApi.get(`/messages/utilisateur/${userId}/${userType}`),
  supprimer: (id) => adminApi.delete(`/messages/${id}`),
};

// Services Utilisateurs Admin
export const adminUtilisateurService = {
  getAll: (params) => {
    const queryParams = new URLSearchParams(params).toString();
    return adminApi.get(`/utilisateurs?${queryParams}`);
  },
  getAgents: (params) => {
    const queryParams = new URLSearchParams(params).toString();
    return adminApi.get(`/utilisateurs/agents?${queryParams}`);
  },
  getRetraites: (params) => {
    const queryParams = new URLSearchParams(params).toString();
    return adminApi.get(`/utilisateurs/retraites?${queryParams}`);
  },
  getById: (type, id) => adminApi.get(`/utilisateurs/${type}/${id}`),
  suspendre: (type, id, data) => adminApi.put(`/utilisateurs/${type}/${id}/suspendre`, data),
  reactiver: (type, id) => adminApi.put(`/utilisateurs/${type}/${id}/reactiver`),
  getActivites: (type, id) => adminApi.get(`/utilisateurs/${type}/${id}/activites`),
  getStatistiques: () => adminApi.get('/utilisateurs/statistiques'),
};

// Services Rapports
export const adminRapportService = {
  getMensuel: (params) => {
    const queryParams = new URLSearchParams(params).toString();
    return adminApi.get(`/rapports/mensuel?${queryParams}`);
  },
  getActivites: (params) => {
    const queryParams = new URLSearchParams(params).toString();
    return adminApi.get(`/rapports/activites?${queryParams}`);
  },
  exportExcel: (params) => adminApi.get('/rapports/export/excel', {
    params,
    responseType: 'blob'
  }),
  exportPDF: (params) => adminApi.get('/rapports/export/pdf', {
    params,
    responseType: 'blob'
  }),
};

export default adminApi;