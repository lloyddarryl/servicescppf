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
  }
};

export default api;