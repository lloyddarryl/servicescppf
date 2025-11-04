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
      '/dashboard',
      // ❌ RETIRÉ: '/messages/' car les routes sont dans /actifs et /retraites
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
      notifications: '/notifications',
      messages: '/messages/conversations', // ✅ Messages dans /actifs
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
      notifications: '/notifications',
      messages: '/messages/conversations', // ✅ Messages dans /retraites
    }
  }
};

// Fonction utilitaire pour faire des appels API
export const apiCall = async (endpoint, options = {}) => {
  const url = urlHelper.buildUrl(endpoint);
  
  // ✅ Headers par défaut
  const defaultHeaders = {
    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
    'Accept': 'application/json',
  };

  // ✅ Ajouter Content-Type SEULEMENT si ce n'est pas FormData
  const isFormData = options.body instanceof FormData;
  
  if (!options.skipContentType && !isFormData) {
    defaultHeaders['Content-Type'] = 'application/json';
  }
  
  const mergedOptions = {
    ...options,
    headers: {
      ...defaultHeaders,
      ...options.headers
    }
  };

  // ✅ Supprimer skipContentType car ce n'est pas un paramètre fetch valide
  delete mergedOptions.skipContentType;
  
  return fetch(url, mergedOptions);
};