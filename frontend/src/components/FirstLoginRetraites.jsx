import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { authService } from '../services/api';

const FirstLoginRetraites = ({ onModeSwitch }) => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    numero_pension: '',
    date_naissance: ''
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: '', text: '' });

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    
    // Validation en temps réel
    let formattedValue = value;
    
    if (name === 'numero_pension') {
      // Supprimer tous les caractères non numériques
      formattedValue = value.replace(/[^0-9]/g, '');
    }
    
    setFormData(prev => ({
      ...prev,
      [name]: formattedValue
    }));

    // Effacer l'erreur pour ce champ
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
  };

  const validateForm = () => {
    const newErrors = {};

    // Validation numéro de pension
    if (!formData.numero_pension) {
      newErrors.numero_pension = 'Le numéro de pension est requis';
    } else if (!/^[0-9]+$/.test(formData.numero_pension)) {
      newErrors.numero_pension = 'Le numéro de pension doit contenir uniquement des chiffres';
    } else if (formData.numero_pension.length < 3) {
      newErrors.numero_pension = 'Le numéro de pension doit contenir au moins 3 chiffres';
    }

    // Validation date de naissance
    if (!formData.date_naissance) {
      newErrors.date_naissance = 'La date de naissance est requise';
    } else {
      const birthDate = new Date(formData.date_naissance);
      const today = new Date();
      const age = today.getFullYear() - birthDate.getFullYear();
      
      if (birthDate >= today) {
        newErrors.date_naissance = 'La date de naissance doit être antérieure à aujourd\'hui';
      } else if (age < 18) {
        newErrors.date_naissance = 'Vous devez avoir au moins 18 ans';
      } else if (age > 120) {
        newErrors.date_naissance = 'Date de naissance invalide';
      }
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    setLoading(true);
    setMessage({ type: '', text: '' });

    try {
      const response = await authService.firstLoginRetraites(formData);

      if (response.data.success) {
        // Stocker le token temporaire pour la configuration
        localStorage.setItem('setup_token', response.data.token);
        localStorage.setItem('user_data', JSON.stringify(response.data.user));
        
        setMessage({ 
          type: 'success', 
          text: 'Connexion réussie ! Redirection vers la configuration de votre profil...' 
        });

        // Rediriger vers la page de configuration du profil
        setTimeout(() => {
          navigate('/setup-profile');
        }, 2000);
      }
    } catch (error) {
      console.error('Erreur de connexion:', error);
      
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      } else if (error.response?.data?.message) {
        if (error.response.data.redirect === 'standard_login') {
          setMessage({ 
            type: 'info', 
            text: error.response.data.message  
          });
          setTimeout(() => onModeSwitch('standard'), 3000);
        } else {
          setMessage({ type: 'error', text: error.response.data.message });
        }
      } else {
        setMessage({ 
          type: 'error', 
          text: 'Erreur de connexion. Vérifiez votre connexion internet.' 
        });
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <form className="login-form" onSubmit={handleSubmit}>
      <h2 className="login-form__title">Première connexion - Retraités</h2>
      <p className="login-form__subtitle">
        Utilisez votre numéro de pension et votre date de naissance pour vous connecter 
        pour la première fois.
      </p>

      {message.text && (
        <div className={`login-form__message login-form__message--${message.type}`}>
          {message.text}
        </div>
      )}

      <div className="login-form__group">
        <label htmlFor="numero_pension" className="login-form__label">
          Numéro de pension *
        </label>
        <input
          type="text"
          id="numero_pension"
          name="numero_pension"
          value={formData.numero_pension}
          onChange={handleInputChange}
          placeholder="123456789"
          className={`login-form__input ${errors.numero_pension ? 'login-form__input--error' : ''}`}
          required
          disabled={loading}
        />
        {errors.numero_pension && (
          <div className="login-form__error">{errors.numero_pension}</div>
        )}
        <div className="login-form__help">
          <small>Votre numéro de pension (chiffres uniquement)</small>
        </div>
      </div>

      <div className="login-form__group">
        <label htmlFor="date_naissance" className="login-form__label">
          Date de naissance *
        </label>
        <input
          type="date"
          id="date_naissance"
          name="date_naissance"
          value={formData.date_naissance}
          onChange={handleInputChange}
          className={`login-form__input ${errors.date_naissance ? 'login-form__input--error' : ''}`}
          required
          disabled={loading}
          max={new Date().toISOString().split('T')[0]}
        />
        {errors.date_naissance && (
          <div className="login-form__error">{errors.date_naissance}</div>
        )}
        <div className="login-form__help">
          <small>Votre date de naissance telle qu'enregistrée dans vos dossiers</small>
        </div>
      </div>

      <button 
        type="submit" 
        className="login-form__button"
        disabled={loading}
      >
        {loading ? (
          <div className="login-form__loading">
            <div className="login-form__spinner"></div>
            Connexion en cours...
          </div>
        ) : (
          'Se connecter'
        )}
      </button>

      <div className="login-form__switch">
        <p className="login-form__switch-text">Déjà configuré votre compte ?</p>
        <button 
          type="button" 
          className="login-form__switch-button"
          onClick={() => onModeSwitch('standard')}
          disabled={loading}
        >
          Utiliser la connexion standard
        </button>
      </div>
    </form>
  );
};

export default FirstLoginRetraites;