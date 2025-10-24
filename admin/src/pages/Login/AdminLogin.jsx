import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import adminApi from '../../services/adminApi';
import './AdminLogin.css';

const AdminLogin = () => {
  const [formData, setFormData] = useState({
    email: '',
    password: ''
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const navigate = useNavigate();

  // Vérifier si l'admin est déjà connecté
  useEffect(() => {
    const token = localStorage.getItem('admin_token');
    if (token) {
      // Vérifier la validité du token
      adminApi.get('/me')
        .then(() => {
          navigate('/dashboard');
        })
        .catch(() => {
          // Token invalide, le supprimer
          localStorage.removeItem('admin_token');
          localStorage.removeItem('admin_data');
        });
    }
  }, [navigate]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Effacer l'erreur du champ modifié
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
  };

  const validateForm = () => {
    const newErrors = {};

    if (!formData.email.trim()) {
      newErrors.email = 'L\'email est obligatoire';
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Format d\'email invalide';
    }

    if (!formData.password) {
      newErrors.password = 'Le mot de passe est obligatoire';
    } else if (formData.password.length < 6) {
      newErrors.password = 'Le mot de passe doit contenir au moins 6 caractères';
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
    
    try {

    console.log('Tentative de connexion...'); // Debug

      const response = await adminApi.post('/login', {
        email: formData.email,
        password: formData.password
      });

    console.log('Réponse complète:', response); // Debug
    console.log('Data:', response.data); // Debug

      if (response.data.success) {
        console.log('Stockage des données...'); // Debug

        // Stocker les données d'authentification
        localStorage.setItem('admin_token', response.data.data.token);
        localStorage.setItem('admin_data', JSON.stringify(response.data.data.admin));
        localStorage.setItem('admin_permissions', JSON.stringify(response.data.data.permissions));
        
    console.log('Données stockées:'); // Debug
      console.log('Token:', localStorage.getItem('admin_token'));
      console.log('Admin data:', localStorage.getItem('admin_data'));        

        // Rediriger vers le dashboard
        navigate('/dashboard');
      } else {
        setErrors({ general: response.data.message || 'Erreur de connexion' });
      }
    } catch (error) {
    console.error('Erreur complète:', error); // Debug
    console.error('Response error:', error.response); // Debug
      console.error('Erreur de connexion:', error);
      
      if (error.response?.data?.errors) {
        // Erreurs de validation du serveur
        setErrors(error.response.data.errors);
      } else if (error.response?.data?.message) {
        // Message d'erreur du serveur
        setErrors({ general: error.response.data.message });
      } else if (error.response?.status === 401) {
        setErrors({ general: 'Identifiants incorrects' });
      } else if (error.response?.status === 403) {
        setErrors({ general: 'Votre compte administrateur n\'est pas actif' });
      } else {
        setErrors({ general: 'Erreur de connexion. Vérifiez votre connexion internet.' });
      }
    } finally {
      setLoading(false);
    }
  };

  const togglePasswordVisibility = () => {
    setShowPassword(!showPassword);
  };

  return (
    <div className="admin-login-container">
      <div className="admin-login-background">
        <div className="admin-login-overlay"></div>
      </div>
      
      <div className="admin-login-card">
        <div className="admin-login-header">
          <div className="admin-logo">
            <img src="/logo-cppf.png" alt="CPPF" className="logo-image" />
          </div>
          <h1 className="admin-title">Administration CPPF</h1>
          <p className="admin-subtitle">Connectez-vous à votre espace administrateur</p>
        </div>

        <form onSubmit={handleSubmit} className="admin-login-form">
          {errors.general && (
            <div className="error-message general-error">
              <span className="error-icon">⚠️</span>
              {errors.general}
            </div>
          )}

          <div className="form-group">
            <label htmlFor="email" className="form-label">
              Adresse email
            </label>
            <div className="input-container">
              <span className="input-icon">📧</span>
              <input
                type="email"
                id="email"
                name="email"
                value={formData.email}
                onChange={handleChange}
                className={`form-input ${errors.email ? 'error' : ''}`}
                placeholder="admin@cppf.ga"
                disabled={loading}
                autoComplete="email"
              />
            </div>
            {errors.email && (
              <span className="error-message">{errors.email}</span>
            )}
          </div>

          <div className="form-group">
            <label htmlFor="password" className="form-label">
              Mot de passe
            </label>
            <div className="input-container">
              <span className="input-icon">🔒</span>
              <input
                type={showPassword ? 'text' : 'password'}
                id="password"
                name="password"
                value={formData.password}
                onChange={handleChange}
                className={`form-input ${errors.password ? 'error' : ''}`}
                placeholder="Votre mot de passe"
                disabled={loading}
                autoComplete="current-password"
              />
              <button
                type="button"
                onClick={togglePasswordVisibility}
                className="password-toggle"
                disabled={loading}
              >
                {showPassword ? '👁️' : '👁️‍🗨️'}
              </button>
            </div>
            {errors.password && (
              <span className="error-message">{errors.password}</span>
            )}
          </div>

          <button
            type="submit"
            className={`submit-button ${loading ? 'loading' : ''}`}
            disabled={loading}
          >
            {loading ? (
              <>
                <span className="spinner"></span>
                Connexion...
              </>
            ) : (
              'Se connecter'
            )}
          </button>
        </form>

        <div className="admin-login-footer">
          <p className="footer-text">
            © 2024 CPPF Gabon - Interface d'Administration
          </p>
          <div className="security-info">
            <span className="security-icon">🔐</span>
            <span>Connexion sécurisée</span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AdminLogin;