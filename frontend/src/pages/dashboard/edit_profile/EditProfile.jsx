import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../../../components/Header';
import { utils } from '../../../services/api';
import './EditProfile.css';

const EditProfile = ({ userType }) => {
  const navigate = useNavigate();
  const [userData, setUserData] = useState(null);
  const [formData, setFormData] = useState({
    email: '',
    telephone: '',
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
  });
  
  const [activeTab, setActiveTab] = useState('info');
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState({ type: '', text: '' });
  const [notifications, setNotifications] = useState([]);
  
  // ✅ ÉTATS POUR LA VÉRIFICATION TÉLÉPHONE AMÉLIORÉS
  const [phoneVerification, setPhoneVerification] = useState({
    isRequired: false,
    step: 0, // 0: normal, 1: en attente code, 2: vérifié
    pendingPhone: '',
    originalPhone: '',
    showModal: false,
    canLeave: true
  });
  const [verificationCode, setVerificationCode] = useState('');

  // Fonction helper pour les appels API
  const makeApiCall = async (endpoint, options = {}) => {
    const baseUrl = 'http://localhost:8000/api';
    const url = `${baseUrl}${endpoint}`;
    
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

  // Charger les données utilisateur
  const fetchUserData = useCallback(async () => {
    try {
      console.log('📄 Tentative de chargement du profil...');
      
      const response = await makeApiCall('/profile');
      console.log('📡 Réponse reçue:', response.status);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      console.log('📋 Données reçues:', data);
      
      if (data.success) {
        setUserData(data.profile);
        setFormData({
          email: data.profile.email || '',
          telephone: data.profile.telephone ? data.profile.telephone.replace('+241', '') : '',
          current_password: '',
          new_password: '',
          new_password_confirmation: ''
        });
        
        setPhoneVerification(prev => ({
          ...prev,
          originalPhone: data.profile.telephone || ''
        }));
        
        // Vérifier si le téléphone n'est pas vérifié
        if (data.profile.telephone && !data.profile.phone_verified) {
          addNotification('warning', 'Votre numéro de téléphone n\'est pas vérifié. Veuillez le vérifier pour sécuriser votre compte.');
        }
      } else {
        setMessage({ type: 'error', text: data.message || 'Erreur lors du chargement des données' });
      }
    } catch (error) {
      console.error('❌ Erreur chargement profil:', error);
      setMessage({ type: 'error', text: `Erreur lors du chargement des données: ${error.message}` });
    }
  }, []);

  useEffect(() => {
    if (!utils.isAuthenticated()) {
      navigate('/services');
      return;
    }
    
    fetchUserData();
  }, [navigate, fetchUserData]);

  // ✅ NOUVELLE FONCTION : Gestion de la navigation avec vérification en cours
  useEffect(() => {
    const handleBeforeUnload = (e) => {
      if (phoneVerification.isRequired && !phoneVerification.canLeave) {
        e.preventDefault();
        e.returnValue = 'Vous avez un changement de téléphone en cours de vérification. Voulez-vous vraiment quitter sans terminer ?';
        return e.returnValue;
      }
    };

    const handlePopState = (e) => {
      if (phoneVerification.isRequired && !phoneVerification.canLeave) {
        if (!window.confirm('Vous avez un changement de téléphone en cours de vérification. Voulez-vous vraiment quitter sans terminer ?')) {
          e.preventDefault();
          window.history.pushState(null, '', window.location.pathname);
          return;
        }
      }
    };

    if (phoneVerification.isRequired && !phoneVerification.canLeave) {
      window.addEventListener('beforeunload', handleBeforeUnload);
      window.addEventListener('popstate', handlePopState);
      
      // Ajouter un état à l'historique pour intercepter le retour
      window.history.pushState(null, '', window.location.pathname);
    }

    return () => {
      window.removeEventListener('beforeunload', handleBeforeUnload);
      window.removeEventListener('popstate', handlePopState);
    };
  }, [phoneVerification.isRequired, phoneVerification.canLeave]);

  const addNotification = (type, text) => {
    const id = Date.now();
    setNotifications(prev => [...prev, { id, type, text }]);
  };

  const removeNotification = (id) => {
    setNotifications(prev => prev.filter(notif => notif.id !== id));
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    
    let formattedValue = value;
    
    if (name === 'telephone') {
      formattedValue = value.replace(/[^0-9]/g, '').slice(0, 9);
    }
    
    if (name === 'verification_code') {
      formattedValue = value.replace(/[^0-9]/g, '').slice(0, 6);
    }

    if (name === 'verification_code') {
      setVerificationCode(formattedValue);
    } else {
      setFormData(prev => ({
        ...prev,
        [name]: formattedValue
      }));
    }

    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
  };

  // Validation des informations de contact
  const validateContactInfo = () => {
    const newErrors = {};

    if (!formData.email) {
      newErrors.email = 'L\'email est requis';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors.email = 'Format d\'email invalide';
    }

    if (!formData.telephone) {
      newErrors.telephone = 'Le numéro de téléphone est requis';
    } else if (formData.telephone.length < 8 || formData.telephone.length > 9) {
      newErrors.telephone = 'Le numéro doit contenir 8 ou 9 chiffres';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // Validation du changement de mot de passe
  const validatePassword = () => {
    const newErrors = {};

    if (!formData.current_password) {
      newErrors.current_password = 'Le mot de passe actuel est requis';
    }

    if (!formData.new_password) {
      newErrors.new_password = 'Le nouveau mot de passe est requis';
    } else if (formData.new_password.length < 8) {
      newErrors.new_password = 'Le mot de passe doit contenir au moins 8 caractères';
    } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(formData.new_password)) {
      newErrors.new_password = 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre';
    }

    if (!formData.new_password_confirmation) {
      newErrors.new_password_confirmation = 'La confirmation du mot de passe est requise';
    } else if (formData.new_password !== formData.new_password_confirmation) {
      newErrors.new_password_confirmation = 'Les mots de passe ne correspondent pas';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // ✅ MISE À JOUR DES INFORMATIONS DE CONTACT AVEC GESTION TÉLÉPHONE
  const handleContactInfoSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateContactInfo()) return;

    setLoading(true);
    setMessage({ type: '', text: '' });

    try {
      const updateData = {
        email: formData.email,
        telephone: `+241${formData.telephone}`
      };

      const response = await makeApiCall('/profile', {
        method: 'PUT',
        body: JSON.stringify(updateData)
      });

      const data = await response.json();

      if (data.success) {
        // ✅ VÉRIFIER SI VÉRIFICATION TÉLÉPHONE REQUISE
        if (data.phone_verification_required) {
          setPhoneVerification({
            isRequired: true,
            step: 1,
            pendingPhone: data.pending_phone,
            originalPhone: phoneVerification.originalPhone,
            showModal: true,
            canLeave: false
          });
          
          setMessage({ 
            type: 'info', 
            text: 'Code de vérification envoyé au nouveau numéro' 
          });
          
          addNotification('info', 'Vérifiez votre nouveau numéro avec le code SMS reçu');
        } else {
          setMessage({ type: 'success', text: data.message });
          await fetchUserData();
        }
      } else {
        if (data.errors) {
          setErrors(data.errors);
        } else {
          setMessage({ type: 'error', text: data.message || 'Erreur lors de la mise à jour' });
        }
      }
    } catch (error) {
      console.error('Erreur mise à jour:', error);
      setMessage({ type: 'error', text: 'Erreur serveur lors de la mise à jour' });
    } finally {
      setLoading(false);
    }
  };

  // Changement de mot de passe
  const handlePasswordSubmit = async (e) => {
    e.preventDefault();
    
    if (!validatePassword()) return;

    setLoading(true);
    setMessage({ type: '', text: '' });

    try {
      const passwordData = {
        current_password: formData.current_password,
        new_password: formData.new_password,
        new_password_confirmation: formData.new_password_confirmation
      };

      const response = await makeApiCall('/profile/password', {
        method: 'PUT',
        body: JSON.stringify(passwordData)
      });

      const data = await response.json();

      if (data.success) {
        setMessage({ type: 'success', text: 'Mot de passe modifié avec succès' });
        setFormData(prev => ({
          ...prev,
          current_password: '',
          new_password: '',
          new_password_confirmation: ''
        }));
      } else {
        if (data.errors) {
          setErrors(data.errors);
        } else {
          setMessage({ type: 'error', text: data.message || 'Erreur lors du changement de mot de passe' });
        }
      }
    } catch (error) {
      console.error('Erreur changement mot de passe:', error);
      setMessage({ type: 'error', text: 'Erreur serveur lors du changement de mot de passe' });
    } finally {
      setLoading(false);
    }
  };

  // ✅ RENVOYER LE CODE DE VÉRIFICATION
  const sendVerificationCode = async () => {
    setLoading(true);
    try {
      const response = await makeApiCall('/profile/resend-verification', {
        method: 'POST'
      });

      const data = await response.json();

      if (data.success) {
        setMessage({ type: 'success', text: 'Code de vérification renvoyé' });
      } else {
        setMessage({ type: 'error', text: data.message || 'Erreur lors de l\'envoi du code' });
      }
    } catch (error) {
      console.error('Erreur envoi code:', error);
      setMessage({ type: 'error', text: 'Erreur lors de l\'envoi du code SMS' });
    } finally {
      setLoading(false);
    }
  };

  // ✅ VÉRIFIER LE CODE SMS
  const verifyPhoneCode = async () => {
    if (verificationCode.length !== 6) {
      setErrors({ verification_code: 'Le code doit contenir 6 chiffres' });
      return;
    }

    setLoading(true);
    try {
      const response = await makeApiCall('/profile/verify-phone', {
        method: 'POST',
        body: JSON.stringify({ verification_code: verificationCode })
      });

      const data = await response.json();

      if (data.success) {
        // ✅ VÉRIFICATION RÉUSSIE
        setPhoneVerification({
          isRequired: false,
          step: 2,
          pendingPhone: '',
          originalPhone: '',
          showModal: false,
          canLeave: true
        });
        
        setMessage({ type: 'success', text: 'Numéro de téléphone vérifié avec succès' });
        setVerificationCode('');
        
        // Enlever les notifications de téléphone non vérifié
        setNotifications(prev => prev.filter(notif => !notif.text.includes('téléphone n\'est pas vérifié')));
        
        await fetchUserData();
      } else {
        setErrors({ verification_code: data.message || 'Code de vérification invalide' });
      }
    } catch (error) {
      console.error('Erreur vérification:', error);
      setErrors({ verification_code: 'Erreur lors de la vérification' });
    } finally {
      setLoading(false);
    }
  };

  // ✅ ANNULER LA VÉRIFICATION
  const cancelPhoneVerification = () => {
    if (window.confirm('Annuler la vérification du nouveau numéro ? Vos modifications ne seront pas sauvegardées.')) {
      setPhoneVerification({
        isRequired: false,
        step: 0,
        pendingPhone: '',
        originalPhone: phoneVerification.originalPhone,
        showModal: false,
        canLeave: true
      });
      
      setVerificationCode('');
      setErrors({});
      
      // Remettre l'ancien numéro dans le formulaire
      setFormData(prev => ({
        ...prev,
        telephone: phoneVerification.originalPhone.replace('+241', '') || ''
      }));
    }
  };

  const getPageTitle = () => {
    if (!userType) return 'Mon Profil';
    return userType === 'actif' ? 'Mon Profil - Agent Actif' : 'Mon Profil - Retraité';
  };

  // Affichage du loading
  if (!userData) {
    return (
      <div className="edit-profile">
        <Header />
        <div className="edit-profile__loading">
          <div className="edit-profile__spinner"></div>
          <p>Chargement de votre profil...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="edit-profile">
      <Header />
      
      {/* ✅ MODAL DE VÉRIFICATION TÉLÉPHONE */}
      {phoneVerification.showModal && (
        <div className="edit-profile__verification-overlay">
          <div className="edit-profile__verification-modal">
            <div className="edit-profile__verification-header">
              <h3>Vérification du nouveau numéro</h3>
              <button 
                className="edit-profile__modal-close"
                onClick={cancelPhoneVerification}
                type="button"
              >
                ×
              </button>
            </div>
            
            <div className="edit-profile__verification-content">
              <p>Un code de vérification a été envoyé au numéro :</p>
              <p className="edit-profile__pending-phone">{phoneVerification.pendingPhone}</p>
              
              <div className="edit-profile__verification-form">
                <input
                  type="text"
                  value={verificationCode}
                  onChange={(e) => handleInputChange({
                    target: { name: 'verification_code', value: e.target.value }
                  })}
                  placeholder="123456"
                  className={`edit-profile__verification-input ${errors.verification_code ? 'edit-profile__input--error' : ''}`}
                  maxLength={6}
                  disabled={loading}
                />
                {errors.verification_code && (
                  <div className="edit-profile__error">{errors.verification_code}</div>
                )}
                
                <div className="edit-profile__verification-actions">
                  <button 
                    type="button"
                    className="edit-profile__button edit-profile__button--success"
                    onClick={verifyPhoneCode}
                    disabled={loading || verificationCode.length !== 6}
                  >
                    {loading ? 'Vérification...' : 'Vérifier'}
                  </button>
                  <button 
                    type="button"
                    className="edit-profile__button edit-profile__button--outline"
                    onClick={sendVerificationCode}
                    disabled={loading}
                  >
                    Renvoyer le code
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
      
      <main className="edit-profile__main">
        <div className="edit-profile__container">
          
          {/* Notifications */}
          {notifications.length > 0 && (
            <div className="edit-profile__notifications">
              {notifications.map(notification => (
                <div key={notification.id} className={`edit-profile__notification edit-profile__notification--${notification.type}`}>
                  <div className="edit-profile__notification-content">
                    <span className="edit-profile__notification-icon">⚠️</span>
                    <span className="edit-profile__notification-text">{notification.text}</span>
                  </div>
                  <button 
                    className="edit-profile__notification-close"
                    onClick={() => removeNotification(notification.id)}
                    type="button"
                  >
                    ✕
                  </button>
                </div>
              ))}
            </div>
          )}

          <div className="edit-profile__content">
            {/* Header avec infos utilisateur */}
            <div className="edit-profile__header">
              <div className="edit-profile__user-info">
                <h1 className="edit-profile__title">{getPageTitle()}</h1>
                <div className="edit-profile__user-details">
                  <p className="edit-profile__user-name">
                    {userData.prenoms} {userData.nom}
                  </p>
                  <p className="edit-profile__user-role">
                    {userType === 'actif' ? 'Agent en activité' : 'Retraité'}
                  </p>
                  <div className="edit-profile__verification-status">
                    <span className={`edit-profile__badge ${userData.phone_verified ? 'edit-profile__badge--success' : 'edit-profile__badge--warning'}`}>
                      Téléphone {userData.phone_verified ? 'vérifié' : 'non vérifié'}
                    </span>
                  </div>
                </div>
              </div>
              
              <button 
                className="edit-profile__back-button"
                onClick={() => navigate('/dashboard')}
                type="button"
              >
                ← Retour au tableau de bord
              </button>
            </div>

            {/* Messages */}
            {message.text && (
              <div className={`edit-profile__message edit-profile__message--${message.type}`}>
                {message.text}
              </div>
            )}

            {/* Tabs */}
            <div className="edit-profile__tabs">
              <button 
                className={`edit-profile__tab ${activeTab === 'info' ? 'edit-profile__tab--active' : ''}`}
                onClick={() => setActiveTab('info')}
                type="button"
              >
                Informations de contact
              </button>
              <button 
                className={`edit-profile__tab ${activeTab === 'password' ? 'edit-profile__tab--active' : ''}`}
                onClick={() => setActiveTab('password')}
                type="button"
              >
                Changer le mot de passe
              </button>
            </div>

            {/* Contenu des tabs */}
            <div className="edit-profile__tab-content">
              
              {/* Tab Informations de contact */}
              {activeTab === 'info' && (
                <div className="edit-profile__form-section">
                  <form onSubmit={handleContactInfoSubmit}>
                    <div className="edit-profile__form-grid">
                      
                      {/* Email */}
                      <div className="edit-profile__form-group">
                        <label htmlFor="email" className="edit-profile__label">
                          Adresse email *
                        </label>
                        <input
                          type="email"
                          id="email"
                          name="email"
                          value={formData.email}
                          onChange={handleInputChange}
                          className={`edit-profile__input ${errors.email ? 'edit-profile__input--error' : ''}`}
                          disabled={loading || phoneVerification.isRequired}
                        />
                        {errors.email && (
                          <div className="edit-profile__error">{errors.email}</div>
                        )}
                      </div>

                      {/* Téléphone */}
                      <div className="edit-profile__form-group">
                        <label htmlFor="telephone" className="edit-profile__label">
                          Numéro de téléphone *
                        </label>
                        <div className="edit-profile__phone-input">
                          <span className="edit-profile__phone-prefix">+241</span>
                          <input
                            type="tel"
                            id="telephone"
                            name="telephone"
                            value={formData.telephone}
                            onChange={handleInputChange}
                            placeholder="01234567"
                            className={`edit-profile__input ${errors.telephone ? 'edit-profile__input--error' : ''}`}
                            disabled={loading || phoneVerification.isRequired}
                            maxLength={9}
                          />
                          {/* Indicateur de vérification */}
                          <div className="edit-profile__phone-status">
                            {userData.phone_verified ? (
                              <span className="edit-profile__phone-verified">
                                <svg className="edit-profile__check-icon" viewBox="0 0 24 24" fill="currentColor">
                                  <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                </svg>
                              </span>
                            ) : (
                              <span className="edit-profile__phone-unverified">
                                <svg className="edit-profile__warning-icon" viewBox="0 0 24 24" fill="currentColor">
                                  <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                                </svg>
                              </span>
                            )}
                          </div>
                        </div>
                        {errors.telephone && (
                          <div className="edit-profile__error">{errors.telephone}</div>
                        )}
                        
                        {/* Section de vérification téléphone */}
                        <div className="edit-profile__phone-verification">
                          {!userData.phone_verified ? (
                            <div className="edit-profile__phone-action">
                              <div className="edit-profile__phone-warning">
                                <span className="edit-profile__warning-text">
                                  ⚠️ Votre numéro n'est pas vérifié
                                </span>
                              </div>
                              <button
                                type="button"
                                className="edit-profile__verify-button"
                                onClick={sendVerificationCode}
                                disabled={loading || !formData.telephone || phoneVerification.isRequired}
                              >
                                {loading ? (
                                  <span className="edit-profile__button-loading">
                                    <div className="edit-profile__spinner-small"></div>
                                    Envoi en cours...
                                  </span>
                                ) : (
                                  <span>
                                    📱 Vérifier mon numéro
                                  </span>
                                )}
                              </button>
                            </div>
                          ) : (
                            <div className="edit-profile__phone-success">
                              <span className="edit-profile__success-text">
                                ✅ Numéro vérifié avec succès
                              </span>
                            </div>
                          )}
                        </div>
                      </div>

                    </div>

                    <button 
                      type="submit" 
                      className="edit-profile__button edit-profile__button--primary"
                      disabled={loading || phoneVerification.isRequired}
                    >
                      {loading ? 'Mise à jour...' : 'Mettre à jour les informations'}
                    </button>
                  </form>
                </div>
              )}

              {/* Tab Changement de mot de passe */}
              {activeTab === 'password' && (
                <div className="edit-profile__form-section">
                  <form onSubmit={handlePasswordSubmit}>
                    <div className="edit-profile__form-grid">
                      
                      {/* Mot de passe actuel */}
                      <div className="edit-profile__form-group">
                        <label htmlFor="current_password" className="edit-profile__label">
                          Mot de passe actuel *
                        </label>
                        <input
                          type="password"
                          id="current_password"
                          name="current_password"
                          value={formData.current_password}
                          onChange={handleInputChange}
                          className={`edit-profile__input ${errors.current_password ? 'edit-profile__input--error' : ''}`}
                          disabled={loading || phoneVerification.isRequired}
                        />
                        {errors.current_password && (
                          <div className="edit-profile__error">{errors.current_password}</div>
                        )}
                      </div>

                      {/* Nouveau mot de passe */}
                      <div className="edit-profile__form-group">
                        <label htmlFor="new_password" className="edit-profile__label">
                          Nouveau mot de passe *
                        </label>
                        <input
                          type="password"
                          id="new_password"
                          name="new_password"
                          value={formData.new_password}
                          onChange={handleInputChange}
                          className={`edit-profile__input ${errors.new_password ? 'edit-profile__input--error' : ''}`}
                          disabled={loading || phoneVerification.isRequired}
                        />
                        {errors.new_password && (
                          <div className="edit-profile__error">{errors.new_password}</div>
                        )}
                        <div className="edit-profile__help">
                          <small>Au moins 8 caractères avec majuscule, minuscule et chiffre</small>
                        </div>
                      </div>

                      {/* Confirmation nouveau mot de passe */}
                      <div className="edit-profile__form-group">
                        <label htmlFor="new_password_confirmation" className="edit-profile__label">
                          Confirmer le nouveau mot de passe *
                        </label>
                        <input
                          type="password"
                          id="new_password_confirmation"
                          name="new_password_confirmation"
                          value={formData.new_password_confirmation}
                          onChange={handleInputChange}
                          className={`edit-profile__input ${errors.new_password_confirmation ? 'edit-profile__input--error' : ''}`}
                          disabled={loading || phoneVerification.isRequired}
                        />
                        {errors.new_password_confirmation && (
                          <div className="edit-profile__error">{errors.new_password_confirmation}</div>
                        )}
                      </div>

                    </div>

                    <button 
                      type="submit" 
                      className="edit-profile__button edit-profile__button--primary"
                      disabled={loading || phoneVerification.isRequired}
                    >
                      {loading ? 'Changement...' : 'Changer le mot de passe'}
                    </button>
                  </form>
                </div>
              )}

            </div>
          </div>
        </div>
      </main>
    </div>
  );
};

export default EditProfile;