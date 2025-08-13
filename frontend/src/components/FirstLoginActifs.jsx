import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';

const FirstLoginActifs = ({ onModeSwitch }) => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    matricule_solde: '',
    password: ''
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: '', text: '' });

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    
    // Validation en temps réel
    let formattedValue = value;
    
    if (name === 'matricule_solde') {
      // Supprimer tous les caractères non alphanumériques
      formattedValue = value.replace(/[^0-9A-Z]/g, '');
      // Limiter à 13 caractères maximum
      formattedValue = formattedValue.slice(0, 13);
    }
    
    if (name === 'password') {
      // Supprimer tous les caractères non numériques
      formattedValue = value.replace(/[^0-9]/g, '');
      // Limiter à 12 chiffres maximum (pour le format 13 caractères)
      formattedValue = formattedValue.slice(0, 12);
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

    // Validation matricule solde
    if (!formData.matricule_solde) {
      newErrors.matricule_solde = 'Le matricule solde est requis';
    } else {
      const length = formData.matricule_solde.length;
      
      if (length === 7) {
        // Format 7 caractères : 6 chiffres + 1 lettre
        if (!/^[0-9]{6}[A-Z]$/.test(formData.matricule_solde)) {
          newErrors.matricule_solde = 'Format 7 caractères : 6 chiffres suivis d\'une lettre majuscule';
        }
      } else if (length === 13) {
        // Format 13 caractères : 12 chiffres + 1 lettre
        if (!/^[0-9]{12}[A-Z]$/.test(formData.matricule_solde)) {
          newErrors.matricule_solde = 'Format 13 caractères : 12 chiffres suivis d\'une lettre majuscule';
        }
      } else if (length > 0 && length < 7) {
        newErrors.matricule_solde = 'Matricule incomplet. Format attendu : 7 ou 13 caractères';
      } else if (length > 7 && length < 13) {
        newErrors.matricule_solde = 'Matricule incomplet. Format attendu : 7 ou 13 caractères';
      } else if (length > 13) {
        newErrors.matricule_solde = 'Matricule trop long. Maximum 13 caractères';
      } else {
        newErrors.matricule_solde = 'Format invalide. Attendu : 7 caractères (6 chiffres + 1 lettre) ou 13 caractères (12 chiffres + 1 lettre)';
      }
    }

    // Validation mot de passe temporaire
    if (!formData.password) {
      newErrors.password = 'Le mot de passe temporaire est requis';
    } else {
      const matriculeLength = formData.matricule_solde.length;
      
      if (matriculeLength === 7) {
        // Pour le format 7 caractères, le mot de passe doit faire 6 chiffres
        if (formData.password.length !== 6) {
          newErrors.password = 'Le mot de passe doit contenir exactement 6 chiffres';
        } else if (!/^[0-9]{6}$/.test(formData.password)) {
          newErrors.password = 'Le mot de passe doit contenir uniquement des chiffres';
        }
      } else if (matriculeLength === 13) {
        // Pour le format 13 caractères, le mot de passe doit faire 12 chiffres
        if (formData.password.length !== 12) {
          newErrors.password = 'Le mot de passe doit contenir exactement 12 chiffres';
        } else if (!/^[0-9]{12}$/.test(formData.password)) {
          newErrors.password = 'Le mot de passe doit contenir uniquement des chiffres';
        }
      }
    }

    // Vérifier que le mot de passe correspond aux chiffres du matricule
    if (formData.matricule_solde.length === 7 && formData.password.length === 6) {
      const matriculeNumbers = formData.matricule_solde.slice(0, 6);
      if (matriculeNumbers !== formData.password) {
        newErrors.password = 'Le mot de passe doit correspondre aux 6 premiers chiffres du matricule';
      }
    } else if (formData.matricule_solde.length === 13 && formData.password.length === 12) {
      const matriculeNumbers = formData.matricule_solde.slice(0, 12);
      if (matriculeNumbers !== formData.password) {
        newErrors.password = 'Le mot de passe doit correspondre aux 12 premiers chiffres du matricule';
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
      const response = await api.post('/auth/first-login/actifs', formData);

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
            text: error.response.data.message + ' Utilisez la connexion standard.' 
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
      <h2 className="login-form__title">Première connexion - Agents Actifs</h2>
      

      {message.text && (
        <div className={`login-form__message login-form__message--${message.type}`}>
          {message.text}
        </div>
      )}

      <div className="login-form__group">
        <label htmlFor="matricule_solde" className="login-form__label">
          Matricule solde *
        </label>
        <input
          type="text"
          id="matricule_solde"
          name="matricule_solde"
          value={formData.matricule_solde}
          onChange={handleInputChange}
          placeholder="123456A ou 123456789012B"
          className={`login-form__input ${errors.matricule_solde ? 'login-form__input--error' : ''}`}
          maxLength={13}
          required
          disabled={loading}
        />
        {errors.matricule_solde && (
          <div className="login-form__error">{errors.matricule_solde}</div>
        )}
        <div className="login-form__help">
          <small>Format accepté : 7 caractères (6 chiffres + 1 lettre) ou 13 caractères (12 chiffres + 1 lettre)</small>
        </div>
      </div>

      <div className="login-form__group">
        <label htmlFor="password" className="login-form__label">
          Mot de passe temporaire *
        </label>
        <input
          type="password"
          id="password"
          name="password"
          value={formData.password}
          onChange={handleInputChange}
          placeholder={formData.matricule_solde.length === 13 ? "123456789012" : "123456"}
          className={`login-form__input ${errors.password ? 'login-form__input--error' : ''}`}
          maxLength={12}
          required
          disabled={loading}
        />
        {errors.password && (
          <div className="login-form__error">{errors.password}</div>
        )}
        <div className="login-form__help">
          <small>
            {formData.matricule_solde.length === 13 
              ? "Les 12 premiers chiffres de votre matricule solde"
              : "Les 6 premiers chiffres de votre matricule solde"
            }
          </small>
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

export default FirstLoginActifs;