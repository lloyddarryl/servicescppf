// File: frontend/src/services/rendezVousService.js

import api from './api';

// Services pour les rendez-vous (universels actifs/retraités)
export const rendezVousService = {
  // Obtenir les informations de la page de prise de RDV
  getPageInfo: () => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' ? '/retraites/rendez-vous' : '/actifs/rendez-vous';
    return api.get(endpoint);
  },

  // Obtenir les créneaux disponibles pour une date
  getCreneauxDisponibles: (date) => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' 
      ? `/retraites/rendez-vous/creneaux-disponibles/${date}` 
      : `/actifs/rendez-vous/creneaux-disponibles/${date}`;
    return api.get(endpoint);
  },

  // Créer une nouvelle demande de rendez-vous
  creerDemande: (data) => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' ? '/retraites/rendez-vous' : '/actifs/rendez-vous';
    return api.post(endpoint, data);
  },

  // Obtenir l'historique des demandes
  getHistorique: (params = {}) => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' ? '/retraites/rendez-vous/historique' : '/actifs/rendez-vous/historique';
    
    const queryParams = new URLSearchParams(params).toString();
    const url = queryParams ? `${endpoint}?${queryParams}` : endpoint;
    
    return api.get(url);
  },

  // Obtenir une demande spécifique
  getById: (id) => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' ? `/retraites/rendez-vous/${id}` : `/actifs/rendez-vous/${id}`;
    return api.get(endpoint);
  },

  // Annuler une demande
  annuler: (id, motifAnnulation = '') => {
    const userType = localStorage.getItem('user_type');
    const endpoint = userType === 'retraite' 
      ? `/retraites/rendez-vous/${id}/annuler` 
      : `/actifs/rendez-vous/${id}/annuler`;
    
    return api.put(endpoint, {
      motif_annulation: motifAnnulation
    });
  },

  // Utilitaires pour les rendez-vous
  utils: {
    // Valider une date (jour ouvrable, dans le futur, avec préavis 48h)
    validerDate: (date) => {
      const dateObj = new Date(date);
      const maintenant = new Date();
      const dans48h = new Date(maintenant.getTime() + (48 * 60 * 60 * 1000));
      
      const errors = [];
      
      // Vérifier que c'est dans le futur avec 48h de préavis
      if (dateObj < dans48h) {
        errors.push('La date doit être au moins 48h à l\'avance');
      }
      
      // Vérifier que c'est un jour ouvrable (lundi = 1, vendredi = 5)
      const jourSemaine = dateObj.getDay();
      if (jourSemaine === 0 || jourSemaine === 6) {
        errors.push('Les rendez-vous ne sont disponibles que du lundi au vendredi');
      }
      
      return {
        isValid: errors.length === 0,
        errors
      };
    },

    // Valider une heure (9h-16h)
    validerHeure: (heure) => {
      const heureNum = parseInt(heure.split(':')[0]);
      const errors = [];
      
      if (heureNum < 9 || heureNum >= 16) {
        errors.push('Les créneaux sont disponibles de 9h à 16h');
      }
      
      return {
        isValid: errors.length === 0,
        errors
      };
    },

    // Générer les créneaux horaires disponibles
    genererCreneaux: () => {
      const creneaux = [];
      for (let heure = 9; heure < 16; heure++) {
        creneaux.push({
          value: `${heure.toString().padStart(2, '0')}:00`,
          label: `${heure}h00`
        });
      }
      return creneaux;
    },

    // Formater une date pour l'affichage
    formaterDate: (date) => {
      if (!date) return '';
      const dateObj = new Date(date);
      return dateObj.toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },

    // Formater date et heure ensemble
    formaterDateHeure: (date, heure) => {
      if (!date || !heure) return '';
      const dateFormatee = rendezVousService.utils.formaterDate(date);
      const heureFormatee = heure.substring(0, 5); // HH:MM
      return `${dateFormatee} à ${heureFormatee}`;
    },

    // Obtenir la couleur selon le statut
    getCouleurStatut: (statut) => {
      const couleurs = {
        'en_attente': '#F59E0B',
        'accepte': '#10B981',
        'refuse': '#EF4444',
        'reporte': '#8B5CF6',
        'annule': '#6B7280'
      };
      return couleurs[statut] || '#6B7280';
    },

    // Obtenir l'icône selon le motif
    getIconeMotif: (motif) => {
      const icones = {
        'probleme_cotisations': '💰',
        'questions_pension': '🏦',
        'mise_a_jour_dossier': '📂',
        'reclamation_complexe': '🔍',
        'autre': '📋'
      };
      return icones[motif] || '📅';
    },

    // Vérifier si une demande peut être annulée
    peutAnnuler: (demande) => {
      if (!demande) return false;
      
      const statuts_modifiables = ['en_attente'];
      const date_soumission = new Date(demande.date_soumission);
      const maintenant = new Date();
      const diffHeures = (maintenant - date_soumission) / (1000 * 60 * 60);
      
      return statuts_modifiables.includes(demande.statut) && diffHeures < 24;
    },

    // Calculer le délai avant le rendez-vous
    getDelaiRendezVous: (dateRdv) => {
      if (!dateRdv) return null;
      
      const rdv = new Date(dateRdv);
      const maintenant = new Date();
      const diffMs = rdv - maintenant;
      
      if (diffMs < 0) return 'Passé';
      
      const diffJours = Math.floor(diffMs / (1000 * 60 * 60 * 24));
      const diffHeures = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      
      if (diffJours > 0) {
        return `Dans ${diffJours} jour${diffJours > 1 ? 's' : ''}`;
      } else if (diffHeures > 0) {
        return `Dans ${diffHeures} heure${diffHeures > 1 ? 's' : ''}`;
      } else {
        return 'Très bientôt';
      }
    },

    // Valider le formulaire de demande
    validerFormulaire: (formData) => {
      const errors = {};
      
      if (!formData.date_demandee) {
        errors.date_demandee = 'La date est obligatoire';
      } else {
        const validationDate = rendezVousService.utils.validerDate(formData.date_demandee);
        if (!validationDate.isValid) {
          errors.date_demandee = validationDate.errors[0];
        }
      }
      
      if (!formData.heure_demandee) {
        errors.heure_demandee = 'L\'heure est obligatoire';
      } else {
        const validationHeure = rendezVousService.utils.validerHeure(formData.heure_demandee);
        if (!validationHeure.isValid) {
          errors.heure_demandee = validationHeure.errors[0];
        }
      }
      
      if (!formData.motif) {
        errors.motif = 'Le motif est obligatoire';
      }
      
      if (formData.motif === 'autre' && !formData.motif_autre?.trim()) {
        errors.motif_autre = 'Veuillez préciser le motif';
      }
      
      if (formData.commentaires && formData.commentaires.length > 1000) {
        errors.commentaires = 'Les commentaires ne peuvent pas dépasser 1000 caractères';
      }
      
      return {
        isValid: Object.keys(errors).length === 0,
        errors
      };
    },

    // Formater la taille des fichiers (pour d'éventuels documents)
    formatFileSize: (bytes) => {
      if (bytes === 0) return '0 B';
      const k = 1024;
      const sizes = ['B', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    },

    // Générer une référence unique pour une demande
    generateReference: () => {
      const now = new Date();
      const prefix = `RDV-${now.getFullYear()}${(now.getMonth() + 1).toString().padStart(2, '0')}`;
      const suffix = Math.random().toString(36).substr(2, 6).toUpperCase();
      return `${prefix}-${suffix}`;
    },

    // Formater une date pour les inputs
    formatDateForInput: (date) => {
      if (!date) return '';
      const dateObj = new Date(date);
      return dateObj.toISOString().split('T')[0];
    },

    // Obtenir le libellé d'un jour de la semaine
    getDayLabel: (date) => {
      const dateObj = new Date(date);
      const jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
      return jours[dateObj.getDay()];
    },

    // Vérifier si une date est aujourd'hui
    isToday: (date) => {
      const dateObj = new Date(date);
      const today = new Date();
      return dateObj.toDateString() === today.toDateString();
    },

    // Vérifier si une date est demain
    isTomorrow: (date) => {
      const dateObj = new Date(date);
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      return dateObj.toDateString() === tomorrow.toDateString();
    },

    // Obtenir le message de temps relatif
    getRelativeTimeMessage: (date) => {
      if (rendezVousService.utils.isToday(date)) {
        return 'Aujourd\'hui';
      } else if (rendezVousService.utils.isTomorrow(date)) {
        return 'Demain';
      } else {
        const dateObj = new Date(date);
        const today = new Date();
        const diffTime = dateObj - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays > 0 && diffDays <= 7) {
          return `Dans ${diffDays} jour${diffDays > 1 ? 's' : ''}`;
        } else {
          return rendezVousService.utils.formaterDate(date);
        }
      }
    }
  }
};

export default rendezVousService;