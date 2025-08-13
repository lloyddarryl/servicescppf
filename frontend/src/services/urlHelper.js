// services/urlHelper.js

export const urlHelper = {
  // Obtenir le type d'utilisateur depuis le token ou localStorage
  getUserType: () => {
    const userType = localStorage.getItem('user_type');
    return userType || 'actif'; // par défaut actif
  },

  // Construire l'URL selon le type d'utilisateur
  buildUrl: (endpoint) => {
    const userType = urlHelper.getUserType();
    const baseUrl = 'http://localhost:8000/api';
    
    // Routes communes (sans préfixe de type)
    const commonRoutes = [
      '/auth/',
      '/profile/',
      '/dashboard' 
    ];
    
    // Vérifier si c'est une route commune
    const isCommonRoute = commonRoutes.some(route => endpoint.startsWith(route));
    
    if (isCommonRoute) {
      return `${baseUrl}${endpoint}`;
    }
    
    // Routes spécifiques selon le type d'utilisateur
    const userPrefix = userType === 'retraite' ? 'retraites' : 'actifs';
    
    // Supprimer le slash initial si présent
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint.substring(1) : endpoint;
    
    return `${baseUrl}/${userPrefix}/${cleanEndpoint}`;
  },

  // URLs spécifiques pour chaque type d'utilisateur
  urls: {
    // URLs communes
    auth: {
      logout: '/auth/logout',
      user: '/auth/user',
      verify: '/auth/verify'
    },
    profile: {
      show: '/profile',
      update: '/profile',
      changePassword: '/profile/password'
    },
    
    // URLs pour actifs
    actifs: {
      dashboard: '/dashboard',
      attestations: '/attestations',
      prestations: '/prestations',
      cotisations: '/cotisations',
      carriere: '/carriere',
      profil: '/profil',
      documents: '/documents',
      notifications: '/notifications'
    },
    
    // URLs pour retraités
    retraites: {
      dashboard: '/dashboard',
      pension: '/pension',
      pensionHistorique: '/pension/historique',
      certificatsVie: '/certificats-vie',
      attestations: '/attestations',
      historique: '/historique',
      suiviPaiements: '/suivi-paiements',
      profil: '/profil',
      documents: '/documents',
      notifications: '/notifications'
    }
  }
};

// Fonction utilitaire pour faire des appels API
export const apiCall = async (endpoint, options = {}) => {
  const url = urlHelper.buildUrl(endpoint);
  
  const defaultOptions = {
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  };
  
  const mergedOptions = {
    ...defaultOptions,
    ...options,
    headers: {
      ...defaultOptions.headers,
      ...options.headers
    }
  };
  
  return fetch(url, mergedOptions);
};