import React from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../../components/Header';
import './HomeServices.css';

const Home = () => {
  const navigate = useNavigate();

  const handleDiscoverServices = () => {
    navigate('/services');
  };

  return (
    <div className="home">
      <Header />
      <main className="home__main">
        <section className="hero">
          {/* Background Elements */}
          <div className="hero__background">
            <div className="hero__gradient-overlay"></div>
            <div className="hero__pattern"></div>
          </div>
          
          <div className="hero__content">
            <div className="hero__text-content">
              {/* Main Title */}
              <h4 className="hero__title">
                <span className="hero__title-blue">Bienvenue sur</span>
                <span className="hero__title-blue">CPPF e-Services</span>
              </h4>

              {/* Subtitle */}
              <p className="hero__subtitle">
                Caisse des Pensions et des Prestations Familiales des agents de l'État
              </p>

              {/* CTA Button - Plus visible */}
              <button 
                className="hero__cta-button"
                onClick={handleDiscoverServices}
                type="button"
              >
                <span className="hero__cta-text">Découvrir nos Services</span>
                <div className="hero__cta-overlay"></div>
              </button>
            </div>

            {/* Floating Decorative Elements */}
            <div className="hero__decorations">
              <div className="hero__decoration hero__decoration--green"></div>
              <div className="hero__decoration hero__decoration--yellow"></div>
              <div className="hero__decoration hero__decoration--blue"></div>
            </div>
          </div>
        </section>
      </main>
    </div>
  );
};

export default Home;