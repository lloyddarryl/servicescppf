import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import './Header.css';

const Header = () => {
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      setScrolled(window.scrollY > 50);
    };
    
    window.addEventListener('scroll', handleScroll);
    return () => {
      window.removeEventListener('scroll', handleScroll);
    };
  }, []);

  return (
    <header className={`header ${scrolled ? 'header--scrolled' : ''}`}>
      <div className="header__container">
        <div className="header__content">
          {/* Logo Section */}
          <div className="header__logo-section">
  <div className="header__logo-wrapper">
    <Link to="/">
      <img 
        src="/images/cppf.png" 
        alt="CPPF Logo" 
        className="header__logo"
      />
      <div className="header__logo-overlay"></div>
    </Link>
  </div>
</div>


          {/* Navigation */}
          <nav className="header__nav">
        
            <Link to="/" className="header__nav-link">
              Services
            </Link>
            <Link to="/contact" className="header__nav-link">
              Contact
            </Link>
          </nav>

          {/* CTA Button */}
          <button className="header__cta-button">
            Newsletter
          </button>
        </div>
      </div>
    </header>
  );
};

export default Header;