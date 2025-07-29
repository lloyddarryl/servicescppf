import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { authService } from '../services/api';

const StandardLogin = ({ userType, onModeSwitch }) => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    identifier: '',
    password: ''
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: '', text: '' });

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    
    // Validation en temps réel pour l'identifiant
    let formattedValue = value;
    
    if (name === 'identifier') {
      if (userType === 'actifs') {
        // Pour les actifs : matricule solde (6 chiffres + 1 lettre)
        formattedValue = value.replace(/[^0-9A-Z]/g, '').slice(0, 7);
      } else {
        // Pour les retraités : numéro de pension (chiffres uniquement)
        formattedValue = value.replace(/[^0-9]/g, '');
      }
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

    // Validation identifiant
    if (!formData.identifier) {
      newErrors.identifier = userType === 'actifs' 
        ? 'Le matricule solde est requis' 
        : 'Le numéro de pension est requis';
    } else if (userType === 'actifs') {
      // Validation matricule solde pour les actifs
      if (formData.identifier.length !== 7) {
        newErrors.identifier = 'Le matricule doit contenir exactement 7 caractères';
      } else if (!/^[0-9]{6}[A-Z]$/.test(formData.identifier)) {
        newErrors.identifier = 'Format invalide : 6 chiffres suivis d\'une lettre majuscule';
      }
    } else {
      // Validation numéro de pension pour les retraités
      if (!/^[0-9]+$/.test(formData.identifier)) {
        newErrors.identifier = 'Le numéro de pension doit contenir uniquement des chiffres';
      } else if (formData.identifier.length < 3) {
        newErrors.identifier = 'Le numéro de pension doit contenir au moins 3 chiffres';
      }
    }

    // Validation mot de passe
    if (!formData.password) {
      newErrors.password = 'Le mot de passe est requis';
    } else if (formData.password.length < 8) {
      newErrors.password = 'Le mot de passe doit contenir au moins 8 caractères';
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
      const loginData = {
        identifier: formData.identifier,
        password: formData.password,
        user_type: userType
      };

      console.log('Envoi des données:', loginData); // DEBUG

      const response = await authService.standardLogin(loginData);
      
      console.log('Réponse reçue:', response.data); // DEBUG

      if (response.data.success) {
        // Stocker le token d'authentification
        localStorage.setItem('auth_token', response.data.token);
        localStorage.setItem('user_data', JSON.stringify(response.data.user));
        
        setMessage({ 
          type: 'success', 
          text: 'Connexion réussie ! Redirection vers votre tableau de bord...' 
        });

        // Rediriger vers le dashboard
        setTimeout(() => {
          navigate('/dashboard');
        }, 2000);
      }
    } catch (error) {
      console.error('Erreur de connexion complète:', error); // DEBUG
      console.error('Détails de la réponse:', error.response); // DEBUG
      
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      } else if (error.response?.data?.message) {
        setMessage({ type: 'error', text: error.response.data.message });
      } else {
        setMessage({ 
          type: 'error', 
          text: 'Erreur de connexion. Vérifiez vos identifiants et votre connexion internet.' 
        });
      }
    } finally {
      setLoading(false);
    }
  };

  const getIdentifierLabel = () => {
    return userType === 'actifs' ? 'Matricule solde' : 'Numéro de pension';
  };

  const getIdentifierPlaceholder = () => {
    return userType === 'actifs' ? '123456A' : '123456789';
  };

  const getIdentifierHelp = () => {
    return userType === 'actifs' 
      ? 'Votre matricule solde (6 chiffres + 1 lettre)'
      : 'Votre numéro de pension (chiffres uniquement)';
  };

  return (
    <form className="login-form" onSubmit={handleSubmit}>
      <h2 className="login-form__title">
        Connexion standard - {userType === 'actifs' ? 'Agents Actifs' : 'Retraités'}
      </h2>
      <p className="login-form__subtitle">
        Utilisez vos identifiants configurés pour accéder à votre espace personnel.
      </p>

      {message.text && (
        <div className={`login-form__message login-form__message--${message.type}`}>
          {message.text}
        </div>
      )}

      <div className="login-form__group">
        <label htmlFor="identifier" className="login-form__label">
          {getIdentifierLabel()} *
        </label>
        <input
          type="text"
          id="identifier"
          name="identifier"
          value={formData.identifier}
          onChange={handleInputChange}
          placeholder={getIdentifierPlaceholder()}
          className={`login-form__input ${errors.identifier ? 'login-form__input--error' : ''}`}
          required
          disabled={loading}
          autoComplete="username"
        />
        {errors.identifier && (
          <div className="login-form__error">{errors.identifier}</div>
        )}
        <div className="login-form__help">
          <small>{getIdentifierHelp()}</small>
        </div>
      </div>

      <div className="login-form__group">
        <label htmlFor="password" className="login-form__label">
          Mot de passe *
        </label>
        <input
          type="password"
          id="password"
          name="password"
          value={formData.password}
          onChange={handleInputChange}
          placeholder="Votre mot de passe"
          className={`login-form__input ${errors.password ? 'login-form__input--error' : ''}`}
          required
          disabled={loading}
          autoComplete="current-password"
        />
        {errors.password && (
          <div className="login-form__error">{errors.password}</div>
        )}
        <div className="login-form__help">
          <small>Le mot de passe que vous avez créé lors de votre première connexion</small>
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
        <p className="login-form__switch-text">Première fois sur la plateforme ?</p>
        <button 
          type="button" 
          className="login-form__switch-button"
          onClick={() => onModeSwitch('first')}
          disabled={loading}
        >
          Utiliser la première connexion
        </button>
      </div>
    </form>
  );
};

export default StandardLogin;