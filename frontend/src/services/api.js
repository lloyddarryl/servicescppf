import axios from 'axios';

// Configuration de base de l'API
const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

// Créer une instance axios (VERSION SIMPLIFIÉE)
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  // RETIRÉ : withCredentials: true, pour éviter les problèmes CSRF
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
    console.error('API Error:', error.response?.status, error.response?.data);
    
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

// Services d'authentification avec les bonnes routes
export const authService = {
  firstLoginActifs: (data) => api.post('/auth/first-login/actifs', data),
  firstLoginRetraites: (data) => api.post('/auth/first-login/retraites', data),
  setupProfile: (data) => api.post('/auth/setup-profile', data),
  standardLogin: (data) => api.post('/auth/standard-login', data),
  logout: () => api.post('/auth/logout'),
  verifyToken: () => api.get('/auth/verify'),
  getCurrentUser: () => api.get('/auth/user'),
  
  // CORRECTION : Routes auth pour la vérification pendant le setup
  verifyPhone: (data) => api.post('/auth/verify-phone-setup', data),
  resendVerification: () => api.post('/auth/resend-verification-setup')
};

// Services utilisateur
export const userService = {
  // Obtenir le profil utilisateur
  getProfile: () => api.get('/profile'),
  
  // Mettre à jour le profil
  updateProfile: (data) => api.put('/profile', data),
  
  // Changer le mot de passe
  changePassword: (data) => api.put('/profile/password', data),


   // ✅ Ajouter ces méthodes pour la vérification
  verifyPhone: (data) => api.post('/profile/verify-phone', data),
  resendVerification: () => api.post('/profile/resend-verification')

};

// Services pour les agents actifs
export const agentService = {
  // Obtenir le dashboard
  getDashboard: () => api.get('/agent/dashboard'),
  
  // Obtenir les attestations
  getAttestations: () => api.get('/agent/attestations'),
  
  // Demander une attestation
  requestAttestation: (data) => api.post('/agent/attestations', data),
  
  // Obtenir les prestations familiales
  getPrestations: () => api.get('/agent/prestations'),
  
  // Obtenir l'historique des cotisations
  getCotisations: () => api.get('/agent/cotisations'),
};

// Services pour les retraités
export const retraiteService = {
  // Obtenir le dashboard
  getDashboard: () => api.get('/retraite/dashboard'),
  
  // Obtenir les informations de pension
  getPensionInfo: () => api.get('/retraite/pension'),
  
  // Obtenir les certificats de vie
  getCertificatsVie: () => api.get('/retraite/certificats-vie'),
  
  // Soumettre un certificat de vie
  submitCertificatVie: (data) => api.post('/retraite/certificats-vie', data),
  
  // Obtenir l'historique professionnel
  getHistorique: () => api.get('/retraite/historique'),
};

// Utilitaires
export const utils = {
  // Formater les erreurs de validation
  formatValidationErrors: (errors) => {
    const formattedErrors = {};
    for (const field in errors) {
      formattedErrors[field] = errors[field][0]; // Prendre le premier message d'erreur
    }
    return formattedErrors;
  },
  
  // Vérifier si l'utilisateur est connecté
  isAuthenticated: () => {
    const token = localStorage.getItem('auth_token');
    return !!token;
  },
  
  // Obtenir les données utilisateur stockées
  getUserData: () => {
    const userData = localStorage.getItem('user_data');
    return userData ? JSON.parse(userData) : null;
  },
  
  // Stocker les données utilisateur
  setUserData: (userData) => {
    localStorage.setItem('user_data', JSON.stringify(userData));
  },
  
  // Nettoyer les données de session
  clearSession: () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('setup_token');
    localStorage.removeItem('user_data');
  },
  
  // Formater les numéros de téléphone
  formatPhoneNumber: (phone) => {
    if (!phone) return '';
    // Supprimer tous les caractères non numériques
    const cleaned = phone.replace(/\D/g, '');
    // Ajouter l'indicatif +241 si nécessaire
    if (cleaned.length === 8 || cleaned.length === 9) {
      return `+241${cleaned}`;
    }
    return phone;
  },
  
  // Valider le format du matricule solde
  validateMatriculeSolde: (matricule) => {
    return /^[0-9]{6}[A-Z]$/.test(matricule);
  },
  
  // Valider le format du numéro de pension
  validateNumeroPension: (numero) => {
    return /^[0-9]+$/.test(numero);
  },
  
  // Formater les montants en FCFA
  formatCurrency: (amount) => {
    if (!amount) return '0 FCFA';
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XAF',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount).replace('XAF', 'FCFA');
  },
  
  // Formater les dates
  formatDate: (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  },
  
  // Calculer l'âge
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

// Export par défaut
export default api;
