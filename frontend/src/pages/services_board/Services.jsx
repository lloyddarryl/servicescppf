import React from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../../components/Header';
import './Services.css';

const Services = () => {
  const navigate = useNavigate();

  const handleCardClick = (userType) => {
    navigate(`/login/${userType}`);
  };

  return (
    
    <div className="services">
      <Header />
        
      <main className="services__main">
        
        <section className="services__hero">
          
          <div className="services__hero-content">
            <h3 className="services__title">
              Nos Services
            </h3>
            <p className="services__subtitle">
              Accédez à vos services selon votre statut professionnel
            </p>
          </div>
        </section>

        <section className="services__cards-section">
         
          <div className="services__container">
            <div className="services__cards">
              
              {/* Carte Actifs */}
              <article 
                className="service-card service-card--actifs"
                onClick={() => handleCardClick('actifs')}
                role="button"
                tabIndex={0}
                onKeyDown={(e) => {
                  if (e.key === 'Enter' || e.key === ' ') {
                    handleCardClick('actifs');
                  }
                }}
              >
                <div className="service-card__background">
                  <div className="service-card__gradient service-card__gradient--actifs"></div>
                </div>
                
                <div className="service-card__content">
                  <div className="service-card__icon">
                    <svg className="service-card__icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6z" />
                    </svg>
                  </div>
                  
                  <h2 className="service-card__title">ACTIFS</h2>
                  
                  <p className="service-card__description">
                    Services dédiés aux agents de l'État en activité. 
                    Gérez vos cotisations, demandes d'attestations et prestations familiales.
                  </p>
                  
                  <div className="service-card__features">
                    <ul className="service-card__list">
                      <li className="service-card__list-item">Suivi des cotisations</li>
                      <li className="service-card__list-item">Grappe familiale</li>
                      <li className="service-card__list-item">Prise de rendez-vous</li>
                    </ul>
                  </div>
                  
                  <div className="service-card__cta">
                    <span className="service-card__cta-text">Accéder aux services</span>
                    <svg className="service-card__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                  </div>
                </div>
              </article>

              {/* Carte Retraités */}
              <article 
                className="service-card service-card--retraites"
                onClick={() => handleCardClick('retraites')}
                role="button"
                tabIndex={0}
                onKeyDown={(e) => {
                  if (e.key === 'Enter' || e.key === ' ') {
                    handleCardClick('retraites');
                  }
                }}
              >
                <div className="service-card__background">
                  <div className="service-card__gradient service-card__gradient--retraites"></div>
                </div>
                
                <div className="service-card__content">
                  <div className="service-card__icon">
                    <svg className="service-card__icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                  </div>
                  
                  <h2 className="service-card__title">RETRAITÉS</h2>
                  
                  <p className="service-card__description">
                    Services pour les anciens agents de l'État à la retraite. 
                    Consultez vos pensions, certificats de vie et historique professionnel.
                  </p>
                  
                  <div className="service-card__features">
                    <ul className="service-card__list">
                      <li className="service-card__list-item">Suivi des pensions</li>
                      <li className="service-card__list-item">Grappe familiale </li>
                      <li className="service-card__list-item">Informations personnelles</li>
                    </ul>
                  </div>
                  
                  <div className="service-card__cta">
                    <span className="service-card__cta-text">Accéder aux services</span>
                    <svg className="service-card__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                  </div>
                </div>
              </article>

            </div>
          </div>
        </section>
      </main>
    </div>
  );
};

export default Services;