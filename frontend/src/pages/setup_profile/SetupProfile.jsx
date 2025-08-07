import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../../components/Header';
import { authService, utils } from '../../services/api';
import './SetupProfile.css';

const SetupProfile = () => {
  const navigate = useNavigate();
  const [userData, setUserData] = useState(null);
  const [currentStep, setCurrentStep] = useState(1);
  const [formData, setFormData] = useState({
    email: '',
    telephone: '',
    password: '',
    password_confirmation: '',
    verification_code: ''
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: '', text: '' });

  useEffect(() => {
    // Vérifier qu'on a bien un token de setup et les données utilisateur
    const setupToken = localStorage.getItem('setup_token');
    const storedUserData = utils.getUserData();

    if (!setupToken || !storedUserData || !storedUserData.first_login) {
      localStorage.removeItem('setup_token');
      localStorage.removeItem('user_data');
      navigate('/services');
      return;
    }

    setUserData(storedUserData);
 // Vérifier si l'utilisateur avait commencé la configuration
  const hasStartedSetup = localStorage.getItem('setup_started');
  if (hasStartedSetup && !storedUserData.phone_verified) {
    // L'utilisateur avait commencé mais n'a pas terminé
    // On nettoie et on recommence
    console.log('⚠️ Configuration incomplète détectée, nettoyage...');
    
    // Nettoyer les données partielles côté serveur
    fetch('http://localhost:8000/api/auth/cleanup-setup', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${setupToken}`,
        'Content-Type': 'application/json',
      }
    }).catch(e => console.log('Nettoyage serveur échoué:', e));
    
    // Nettoyer côté client
    localStorage.removeItem('setup_started');
    localStorage.removeItem('setup_token');
    localStorage.removeItem('user_data');
    
    // Rediriger vers la connexion
    navigate(`/login/${storedUserData.type}s`, { 
      state: { 
        message: 'Configuration incomplète. Veuillez recommencer la première connexion.',
        type: 'warning'
      }
    });
    return;
  }
}, [navigate]);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    
    let formattedValue = value;
    
    // Formatage automatique du téléphone
    if (name === 'telephone') {
      // Supprimer tous les caractères non numériques
      formattedValue = value.replace(/[^0-9]/g, '');
      // Limiter à 8-9 chiffres après l'indicatif
      formattedValue = formattedValue.slice(0, 9);
    }

    // Formatage du code de vérification
    if (name === 'verification_code') {
      formattedValue = value.replace(/[^0-9]/g, '').slice(0, 6);
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

  const validateStep1 = () => {
    const newErrors = {};

    // Validation email
    if (!formData.email) {
      newErrors.email = 'L\'email est requis';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors.email = 'Format d\'email invalide';
    }

    // Validation téléphone
    if (!formData.telephone) {
      newErrors.telephone = 'Le numéro de téléphone est requis';
    } else if (formData.telephone.length < 8 || formData.telephone.length > 9) {
      newErrors.telephone = 'Le numéro doit contenir 8 ou 9 chiffres';
    }

    // Validation mot de passe
    if (!formData.password) {
      newErrors.password = 'Le mot de passe est requis';
    } else if (formData.password.length < 8) {
      newErrors.password = 'Le mot de passe doit contenir au moins 8 caractères';
    } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(formData.password)) {
      newErrors.password = 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre';
    }

    // Validation confirmation mot de passe
    if (!formData.password_confirmation) {
      newErrors.password_confirmation = 'La confirmation du mot de passe est requise';
    } else if (formData.password !== formData.password_confirmation) {
      newErrors.password_confirmation = 'Les mots de passe ne correspondent pas';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const validateStep2 = () => {
    const newErrors = {};

    if (!formData.verification_code) {
      newErrors.verification_code = 'Le code de vérification est requis';
    } else if (formData.verification_code.length !== 6) {
      newErrors.verification_code = 'Le code doit contenir 6 chiffres';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleStep1Submit = async (e) => {
    e.preventDefault();
    
    if (!validateStep1()) {
      return;
    }

    setLoading(true);
    setMessage({ type: '', text: '' });

    try {

      // Marquer que la configuration a commencé
      localStorage.setItem('setup_started', 'true');

      const setupData = {
        email: formData.email,
        telephone: formData.telephone,
        password: formData.password,
        password_confirmation: formData.password_confirmation,
        user_type: userData.type,
        user_id: userData.id
      };

      const response = await authService.setupProfile(setupData);

      if (response.data.success) {
        setMessage({ 
          type: 'success', 
          text: 'Profil configuré ! Un code de vérification a été envoyé à votre téléphone.' 
        });
        setCurrentStep(2);
      }
    } catch (error) {
      console.error('Erreur de configuration:', error);
      
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      } else if (error.response?.data?.message) {
        setMessage({ type: 'error', text: error.response.data.message });
      } else {
        setMessage({ 
          type: 'error', 
          text: 'Erreur de configuration. Veuillez réessayer.' 
        });
      }
    } finally {
      setLoading(false);
    }
  };

// Méthode handleStep2Submit corrigée
  const handleStep2Submit = async (e) => {
    e.preventDefault();
    
    if (!validateStep2()) {
      return;
    }

    setLoading(true);
    setMessage({ type: '', text: '' });

    try {
      //Utiliser la route setup pour la vérification
      const response = await authService.verifyPhone({
        verification_code: formData.verification_code
      });

      if (response.data.success) {
        localStorage.removeItem('setup_token');
        localStorage.removeItem('user_data');
        
        setMessage({ 
          type: 'success', 
          text: 'Téléphone vérifié ! Redirection vers la connexion...' 
        });

        setTimeout(() => {
          // Mapper le type utilisateur vers l'URL correcte
          const userTypeUrl = userData.type === 'actif' ? 'actifs' : 'retraites';
          navigate(`/login/${userTypeUrl}`, { 
            state: { message: 'Configuration terminée. Connectez-vous avec vos nouveaux identifiants.' }
          });
        }, 2000);
      }
    } catch (error) {
      console.error('Erreur vérification setup:', error);
      setMessage({ 
        type: 'error', 
        text: error.response?.data?.message || 'Erreur de vérification. Veuillez réessayer.' 
      });
    } finally {
      setLoading(false);
    }
  };


  // Méthode resendCode 
  const resendCode = async () => {
    setLoading(true);
    setMessage({ type: '', text: '' });

    console.log('🔄 Tentative de renvoi du code de vérification...');
    
    try {
      // Debug des tokens
      const setupToken = localStorage.getItem('setup_token');
      const authToken = localStorage.getItem('auth_token');
      console.log('🔑 Setup token:', setupToken ? 'Présent' : 'Absent');
      console.log('🔑 Auth token:', authToken ? 'Présent' : 'Absent');
      
      // Utiliser la méthode pour le setup
      const response = await authService.resendVerification();
      console.log('✅ Réponse API reçue:', response.data);
      
      if (response.data.success) {
        setMessage({
          type: 'success',
          text: `Nouveau code envoyé au +241${formData.telephone}`
        });
        console.log('📱 SMS envoyé avec succès');
      }
    } catch (error) {
      console.error('❌ Erreur lors du renvoi:', error);
      console.error('📝 Status:', error.response?.status);
      console.error('📝 Data:', error.response?.data);
      
      setMessage({ 
        type: 'error', 
        text: error.response?.data?.message || 'Erreur lors du renvoi du code.' 
      });
    } finally {
      setLoading(false);
    }
  };
  
  if (!userData) {
    return <div>Chargement...</div>;
  }

  return (
    <div className="setup-profile">
      <Header />
      
      <main className="setup-profile__main">
        <div className="setup-profile__container">
          <div className="setup-profile__content">
            
            {/* Progress Steps */}
            <div className="setup-profile__progress">
              <div className={`setup-profile__step ${currentStep >= 1 ? 'setup-profile__step--active' : ''}`}>
                <div className="setup-profile__step-number">1</div>
                <div className="setup-profile__step-text">Configuration</div>
              </div>
              <div className="setup-profile__step-line"></div>
              <div className={`setup-profile__step ${currentStep >= 2 ? 'setup-profile__step--active' : ''}`}>
                <div className="setup-profile__step-number">2</div>
                <div className="setup-profile__step-text">Vérification</div>
              </div>
            </div>

            {/* Welcome Message */}
            <div className="setup-profile__welcome">
              <h1 className="setup-profile__title">
                Bienvenue {userData.prenoms} {userData.nom} !
              </h1>
              
              <p className="setup-profile__description">
                Configurez votre profil pour accéder à vos services e-CPPF.
              </p>
            </div>

            {/* Form Container */}
            <div className="setup-profile__form-container">
              
              {message.text && (
                <div className={`setup-profile__message setup-profile__message--${message.type}`}>
                  {message.text}
                </div>
              )}

              {/* Step 1: Configuration */}
              {currentStep === 1 && (
                <form className="setup-profile__form" onSubmit={handleStep1Submit}>
                  <h2 className="setup-profile__form-title">Configuration de votre profil</h2>
                  
                  <div className="setup-profile__form-group">
                    <label htmlFor="email" className="setup-profile__label">
                      Adresse email *
                    </label>
                    <input
                      type="email"
                      id="email"
                      name="email"
                      value={formData.email}
                      onChange={handleInputChange}
                      placeholder="votre@email.com"
                      className={`setup-profile__input ${errors.email ? 'setup-profile__input--error' : ''}`}
                      required
                      disabled={loading}
                    />
                    {errors.email && (
                      <div className="setup-profile__error">{errors.email}</div>
                    )}
                  </div>

                  <div className="setup-profile__form-group">
                    <label htmlFor="telephone" className="setup-profile__label">
                      Numéro de téléphone *
                    </label>
                    <div className="setup-profile__phone-input">
                      <span className="setup-profile__phone-prefix">+241</span>
                      <input
                        type="tel"
                        id="telephone"
                        name="telephone"
                        value={formData.telephone}
                        onChange={handleInputChange}
                        placeholder="01234567"
                        className={`setup-profile__input ${errors.telephone ? 'setup-profile__input--error' : ''}`}
                        required
                        disabled={loading}
                        maxLength={9}
                      />
                    </div>
                    {errors.telephone && (
                      <div className="setup-profile__error">{errors.telephone}</div>
                    )}
                    <div className="setup-profile__help">
                      <small>Format : 8 ou 9 chiffres (sans l'indicatif +241)</small>
                    </div>
                  </div>

                  <div className="setup-profile__form-group">
                    <label htmlFor="password" className="setup-profile__label">
                      Nouveau mot de passe *
                    </label>
                    <input
                      type="password"
                      id="password"
                      name="password"
                      value={formData.password}
                      onChange={handleInputChange}
                      placeholder="Minimum 8 caractères"
                      className={`setup-profile__input ${errors.password ? 'setup-profile__input--error' : ''}`}
                      required
                      disabled={loading}
                    />
                    {errors.password && (
                      <div className="setup-profile__error">{errors.password}</div>
                    )}
                    <div className="setup-profile__help">
                      <small>Au moins 8 caractères avec majuscule, minuscule et chiffre</small>
                    </div>
                  </div>

                  <div className="setup-profile__form-group">
                    <label htmlFor="password_confirmation" className="setup-profile__label">
                      Confirmer le mot de passe *
                    </label>
                    <input
                      type="password"
                      id="password_confirmation"
                      name="password_confirmation"
                      value={formData.password_confirmation}
                      onChange={handleInputChange}
                      placeholder="Répétez votre mot de passe"
                      className={`setup-profile__input ${errors.password_confirmation ? 'setup-profile__input--error' : ''}`}
                      required
                      disabled={loading}
                    />
                    {errors.password_confirmation && (
                      <div className="setup-profile__error">{errors.password_confirmation}</div>
                    )}
                  </div>

                  <button 
                    type="submit" 
                    className="setup-profile__button"
                    disabled={loading}
                  >
                    {loading ? (
                      <div className="setup-profile__loading">
                        <div className="setup-profile__spinner"></div>
                        Configuration en cours...
                      </div>
                    ) : (
                      'Configurer mon profil'
                    )}
                  </button>
                </form>
              )}

              {/* Step 2: Phone Verification */}
              {currentStep === 2 && (
                <form className="setup-profile__form" onSubmit={handleStep2Submit}>
                  <h2 className="setup-profile__form-title">Vérification du téléphone</h2>
                  <p className="setup-profile__verification-text">
                    Un code de vérification a été envoyé au numéro :<br/>
                    <strong>+241{formData.telephone}</strong>
                  </p>
                  
                  <div className="setup-profile__form-group">
                    <label htmlFor="verification_code" className="setup-profile__label">
                      Code de vérification *
                    </label>
                    <input
                      type="text"
                      id="verification_code"
                      name="verification_code"
                      value={formData.verification_code}
                      onChange={handleInputChange}
                      placeholder="123456"
                      className={`setup-profile__input setup-profile__input--center ${errors.verification_code ? 'setup-profile__input--error' : ''}`}
                      required
                      disabled={loading}
                      maxLength={6}
                    />
                    {errors.verification_code && (
                      <div className="setup-profile__error">{errors.verification_code}</div>
                    )}
                    <div className="setup-profile__help">
                      <small>Un code de vérification à 6 chiffres a été envoyé à votre numéro</small>
                    </div>

                  </div>

                  <button 
                    type="submit" 
                    className="setup-profile__button"
                    disabled={loading}
                  >
                    {loading ? (
                      <div className="setup-profile__loading">
                        <div className="setup-profile__spinner"></div>
                        Vérification en cours...
                      </div>
                    ) : (
                      'Vérifier et terminer'
                    )}
                  </button>

                  <div className="setup-profile__resend">
                  <p>Vous n'avez pas reçu le code ?</p>
                  <button 
                    type="button" 
                    className="setup-profile__resend-button"
                    onClick={resendCode}
                    disabled={loading}
                  >
                    {loading ? 'Envoi en cours...' : 'Renvoyer le code'}
                  </button>
                  {message.type === 'error' && (
                    <p className="setup-profile__error-text">
                      {message.text}
                    </p>
                  )}

                  </div>
                </form>
              )}

            </div>
          </div>
        </div>
      </main>
    </div>
  );
};

export default SetupProfile;
