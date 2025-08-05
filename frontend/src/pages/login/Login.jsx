import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import Header from '../../components/Header';
import FirstLoginActifs from '../../components/FirstLoginActifs';
import FirstLoginRetraites from '../../components/FirstLoginRetraites';
import StandardLogin from '../../components/StandardLogin';
import './Login.css';

const Login = () => {
  const { type } = useParams(); // 'actifs' ou 'retraites'
  const navigate = useNavigate();
  const location = useLocation();
  const [loginMode, setLoginMode] = useState('standard'); // 'first' ou 'standard'
  const [successMessage, setSuccessMessage] = useState('');

  useEffect(() => {
    // Vérifier que le type est valide
    if (!['actifs', 'retraites'].includes(type)) {
      navigate('/services');
    }
  }, [type, navigate]);

  // Gérer le message de succès du setup
  useEffect(() => {
    if (location.state?.message) {
      setSuccessMessage(location.state.message);
      
      // Effacer le message après 8 secondes
      setTimeout(() => setSuccessMessage(''), 8000);
      
      // Nettoyer l'état de navigation pour éviter que le message réapparaisse
      navigate(location.pathname, { replace: true, state: {} });
      
      // Automatiquement basculer vers connexion standard car le setup est terminé
      setLoginMode('standard');
    }
  }, [location.state, navigate, location.pathname]);

  const handleModeSwitch = (mode) => {
    setLoginMode(mode);
    // Effacer le message de succès si on change de mode
    if (successMessage) {
      setSuccessMessage('');
    }
  };

  const renderLoginForm = () => {
    if (loginMode === 'first') {
      return type === 'actifs' ? 
        <FirstLoginActifs onModeSwitch={handleModeSwitch} /> : 
        <FirstLoginRetraites onModeSwitch={handleModeSwitch} />;
    } else {
      return <StandardLogin userType={type} onModeSwitch={handleModeSwitch} />;
    }
  };

  return (
    <div className="login-page">
      <Header />
      
      <main className="login-page__main">
        <div className="login-page__container">
          <div className="login-page__content">
            
            {/* Section gauche - Info */}
            <div className="login-page__info">
              <div className="login-page__info-content">
                <h1 className="login-page__title">
                  Connexion {type === 'actifs' ? 'Agents Actifs' : 'Retraités'}
                </h1>
                
                <p className="login-page__description">
                  {type === 'actifs' 
                    ? 'Accédez à vos services en tant qu\'agent de l\'État en activité. Gérez vos cotisations, attestations et prestations familiales.'
                    : 'Accédez à vos services en tant qu\'ancien agent de l\'État. Consultez vos pensions, certificats de vie et historique professionnel.'
                  }
                </p>

                <div className="login-page__features">
                  <h3 className="login-page__features-title">Services disponibles :</h3>
                  <ul className="login-page__features-list">
                    {type === 'actifs' ? (
                      <>
                        <li className="login-page__feature-item">Simulateur de pension</li>
                        <li className="login-page__feature-item">Grappe familiale</li>
                        <li className="login-page__feature-item">Suivi des cotisations</li>
                        <li className="login-page__feature-item">Prise de rendez-vous</li>
                      </>
                    ) : (
                      <>
                        <li className="login-page__feature-item">Suivi des pensions</li>
                        <li className="login-page__feature-item">Grappe familiale</li>
                        <li className="login-page__feature-item">Historique de paiements</li>
                        <li className="login-page__feature-item">Documentation personnelle</li>
                      </>
                    )}
                  </ul>
                </div>

                <div className="login-page__help">
                  <p className="login-page__help-text">
                    Besoin d'aide ? Contactez notre support technique.
                  </p>
                </div>
              </div>
            </div>

            {/* Section droite - Formulaire */}
            <div className="login-page__form-section">
              <div className="login-page__form-container">
                
                {/* Message de succès du setup */}
                {successMessage && (
                  <div className="login-page__success-banner">
                    <div className="login-page__success-content">
                      <div className="login-page__success-icon">✅</div>
                      <div className="login-page__success-text">
                        <strong>Configuration terminée !</strong>
                        <p>{successMessage}</p>
                      </div>
                    </div>
                  </div>
                )}
                
                {/* Tabs pour choisir le mode */}
                <div className="login-page__tabs">
                   <button 
                    className={`login-page__tab ${loginMode === 'standard' ? 'login-page__tab--active' : ''}`}
                    onClick={() => handleModeSwitch('standard')}
                    type="button"
                  >
                    Connexion standard
                  </button>
                  <button 
                    className={`login-page__tab ${loginMode === 'first' ? 'login-page__tab--active' : ''}`}
                    onClick={() => handleModeSwitch('first')}
                    type="button"
                  >
                    Première connexion
                  </button>
                 
                </div>

                {/* Formulaire dynamique */}
                <div className="login-page__form-wrapper">
                  {renderLoginForm()}
                </div>

              </div>
            </div>

          </div>
        </div>
      </main>
    </div>
  );
};

export default Login;