// File: frontend/src/services/api.js - Services famille universels

import axios from 'axios';

// Configuration de base de l'API
const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

// Créer une instance axios
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 30000,
});

// Intercepteur pour ajouter le token d'authentification
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token') || localStorage.getItem('setup_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Intercepteur pour gérer les réponses et erreurs
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    console.error('API Error:', {
      status: error.response?.status,
      data: error.response?.data,
      url: error.config?.url,
      method: error.config?.method
    });
    
    // Gestion des erreurs d'authentification
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('setup_token');
      localStorage.removeItem('user_data');
      
      if (!window.location.pathname.includes('/login')) {
        window.location.href = '/services';
      }
    }

    return Promise.reject(error);
  }
);

// Services d'authentification
export const authService = {
  firstLoginActifs: (data) => api.post('/auth/first-login/actifs', data),
  firstLoginRetraites: (data) => api.post('/auth/first-login/retraites', data),
  setupProfile: (data) => api.post('/auth/setup-profile', data),
  standardLogin: (data) => api.post('/auth/standard-login', data),
  logout: () => api.post('/auth/logout'),
  verifyToken: () => api.get('/auth/verify'),
  getCurrentUser: () => api.get('/auth/user'),
  verifyPhone: (data) => api.post('/auth/verify-phone-setup', data),
  resendVerification: () => api.post('/auth/resend-verification-setup')
};

// Services utilisateur
export const userService = {
  getProfile: () => api.get('/profile'),
  updateProfile: (data) => api.put('/profile', data),
  changePassword: (data) => api.put('/profile/password', data),
  verifyPhone: (data) => api.post('/profile/verify-phone', data),
  resendVerification: () => api.post('/profile/resend-verification')
};

// Services pour le simulateur de pension (actifs uniquement)
export const pensionSimulatorService = {
  getProfile: () => api.get('/actifs/simulateur-pension/profil'),
  simulate: (data) => api.post('/actifs/simulateur-pension/simuler', data),
  getHistory: () => api.get('/actifs/simulateur-pension/historique'),
  getParameters: () => api.get('/actifs/simulateur-pension/parametres')
};

// Services pour les agents actifs
export const agentService = {
  getDashboard: () => api.get('/actifs/dashboard'),
  getAttestations: () => api.get('/actifs/attestations'),
  requestAttestation: (data) => api.post('/actifs/attestations', data),
  getPrestations: () => api.get('/actifs/prestations'),
  getCotisations: () => api.get('/actifs/cotisations'),
};

// Services pour les retraités
export const retraiteService = {
  getDashboard: () => api.get('/retraites/dashboard'),
  getPensionInfo: () => api.get('/retraites/pension'),
  getCertificatsVie: () => api.get('/retraites/certificats-vie'),
  submitCertificatVie: (data) => api.post('/retraites/certificats-vie', data),
  getHistorique: () => api.get('/retraites/historique'),
};

export const familleService = {
  // Obtenir la grappe familiale complète
  getGrappeFamiliale: () => {
    const userType = localStorage.getItem('user_type');
    console.log('Type utilisateur:', userType); // Debug
    
    if (userType === 'retraite') {
      return api.get('/retraites/famille'); 
    } else {
      return api.get('/actifs/famille'); 
    }
  },
  
  // Gestion du conjoint
  saveConjoint: (data) => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' ? '/retraites/famille/conjoint' : '/actifs/famille/conjoint';
    return api.post(endpoint, data);
  },
  
  // Gestion des enfants
  addEnfant: (data) => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' ? '/retraites/famille/enfants' : '/actifs/famille/enfants';
    return api.post(endpoint, data);
  },
  
  updateEnfant: (id, data) => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' ? `/retraites/famille/enfants/${id}` : `/actifs/famille/enfants/${id}`;
    return api.put(endpoint, data);
  },
  
  deleteEnfant: (id) => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' ? `/retraites/famille/enfants/${id}` : `/actifs/famille/enfants/${id}`;
    return api.delete(endpoint);
  }
};

// Services pour les réclamations (universels actifs/retraités)
export const reclamationService = {
  // Obtenir les types de réclamations disponibles
  getTypes: () => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' ? '/retraites/reclamations/types' : '/actifs/reclamations/types';
    return api.get(endpoint);
  },

  // Obtenir toutes les réclamations de l'utilisateur
  getAll: (params = {}) => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' ? '/retraites/reclamations' : '/actifs/reclamations';
    
    const queryParams = new URLSearchParams(params).toString();
    const url = queryParams ? `${endpoint}?${queryParams}` : endpoint;
    
    return api.get(url);
  },

  // Créer une nouvelle réclamation
  create: (formData) => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' ? '/retraites/reclamations' : '/actifs/reclamations';
    
    return api.post(endpoint, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
  },

  // Obtenir une réclamation spécifique
  getById: (id) => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' ? `/retraites/reclamations/${id}` : `/actifs/reclamations/${id}`;
    return api.get(endpoint);
  },

  // Télécharger un document de réclamation
  downloadDocument: (reclamationId, documentIndex) => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' 
      ? `/retraites/reclamations/${reclamationId}/documents/${documentIndex}` 
      : `/actifs/reclamations/${reclamationId}/documents/${documentIndex}`;
    
    return api.get(endpoint, {
      responseType: 'blob'
    });
  },

  // Utilitaires pour les réclamations
  utils: {
    // Formater la taille des fichiers
    formatFileSize: (bytes) => {
      if (bytes === 0) return '0 B';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    },

    validateFile: (file, maxSize = 5 * 1024 * 1024) => {
      const allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
      const extension = file.name.split('.').pop().toLowerCase();
      
      const errors = [];
      
      if (!allowedTypes.includes(extension)) {
        errors.push('Type de fichier non autorisé');
      }
      
      if (file.size > maxSize) {
        errors.push('Fichier trop volumineux (max 5MB)');
      }
      
      return {
        isValid: errors.length === 0,
        errors
      };
    },

    getReclamationIcon: (type) => {
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
    },

    getStatusColor: (statut) => {
      const colors = {
        'en_attente': '#F59E0B',
        'en_cours': '#3B82F6',
        'en_revision': '#8B5CF6',
        'resolu': '#10B981',
        'ferme': '#6B7280',
        'rejete': '#EF4444'
      };
      return colors[statut] || '#6B7280';
    },

    getPriorityColor: (priorite) => {
      const colors = {
        'basse': '#10B981',
        'normale': '#3B82F6',
        'haute': '#F59E0B',
        'urgente': '#EF4444'
      };
      return colors[priorite] || '#3B82F6';
    }
  }
};

// Utilitaires
export const utils = {
  formatValidationErrors: (errors) => {
    const formattedErrors = {};
    for (const field in errors) {
      formattedErrors[field] = errors[field][0];
    }
    return formattedErrors;
  },
  
  isAuthenticated: () => {
    const token = localStorage.getItem('auth_token');
    return !!token;
  },
  
  getUserData: () => {
    const userData = localStorage.getItem('user_data');
    return userData ? JSON.parse(userData) : null;
  },
  
  setUserData: (userData) => {
    localStorage.setItem('user_data', JSON.stringify(userData));
  },
  
  clearSession: () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('setup_token');
    localStorage.removeItem('user_data');
    localStorage.removeItem('user_type');
  },
  
  formatPhoneNumber: (phone) => {
    if (!phone) return '';
    const cleaned = phone.replace(/\D/g, '');
    if (cleaned.length === 8 || cleaned.length === 9) {
      return `+241${cleaned}`;
    }
    return phone;
  },
  
  validateMatriculeSolde: (matricule) => {
    return /^[0-9]{6}[A-Z]$/.test(matricule);
  },
  
  validateNumeroPension: (numero) => {
    return /^[0-9]+$/.test(numero);
  },
  
  formatCurrency: (amount) => {
    if (!amount) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XAF',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount).replace('XAF', 'FCFA');
  },
  
  formatDate: (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  },
  
  calculateAge: (birthDate) => {
    if (!birthDate) return 0;
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
      age--;
    }
    
    return age;
  },

  // Ajoutez ces nouvelles méthodes à l'objet utils existant
  generateReclamationReference: () => {
    const now = new Date();
    const prefix = `REC-${now.getFullYear()}${(now.getMonth() + 1).toString().padStart(2, '0')}`;
    const suffix = Math.random().toString(36).substr(2, 6).toUpperCase();
    return `${prefix}-${suffix}`;
  },

  formatDateReclamation: (date) => {
    if (!date) return '';
    const dateObj = new Date(date);
    return dateObj.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  },

  getTimeElapsed: (date) => {
    if (!date) return '';
    const now = new Date();
    const past = new Date(date);
    const diffMs = now - past;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffMinutes = Math.floor(diffMs / (1000 * 60));

    if (diffDays > 0) {
      return `Il y a ${diffDays} jour${diffDays > 1 ? 's' : ''}`;
    } else if (diffHours > 0) {
      return `Il y a ${diffHours} heure${diffHours > 1 ? 's' : ''}`;
    } else if (diffMinutes > 0) {
      return `Il y a ${diffMinutes} minute${diffMinutes > 1 ? 's' : ''}`;
    } else {
      return 'À l\'instant';
    }
  },

  validateReclamationDescription: (description) => {
    const errors = [];
    
    if (!description || !description.trim()) {
      errors.push('La description est obligatoire');
    } else {
      if (description.trim().length < 10) {
        errors.push('La description doit contenir au moins 10 caractères');
      }
      if (description.trim().length > 2000) {
        errors.push('La description ne peut pas dépasser 2000 caractères');
      }
    }
    
    return {
      isValid: errors.length === 0,
      errors
    };
  },

  getReclamationNotificationMessage: (action, data = {}) => {
    switch (action) {
      case 'created':
        return `Réclamation créée avec succès ! N° ${data.numero || 'N/A'}`;
      case 'updated':
        return 'Réclamation mise à jour avec succès';
      case 'status_changed':
        return `Statut de votre réclamation changé : ${data.statut || 'Mis à jour'}`;
      case 'document_uploaded':
        return 'Document ajouté avec succès';
      case 'error':
        return data.message || 'Une erreur est survenue';
      default:
        return 'Action effectuée avec succès';
    }
  }
};

export default api;