// admin-cppf/src/components/AdminHeader.jsx
import React from 'react';
import { useNavigate } from 'react-router-dom';
import './AdminHeader.css';

const AdminHeader = ({ title, breadcrumb }) => {
  const navigate = useNavigate();
  const adminData = JSON.parse(localStorage.getItem('admin_data') || '{}');

  const handleLogout = () => {
    localStorage.clear();
    navigate('/login');
  };

  return (
    <header className="admin-header-main">
      <div className="admin-header-top">
        <div className="admin-logo-section">
          <img src="/cppf.png" alt="CPPF Logo" className="admin-logo" />
          <div className="admin-title-info">
            <h1>Administration CPPF e-Services</h1>
            <span className="admin-subtitle">Espace Administrateur</span>
          </div>
        </div>
        
        <div className="admin-user-section">
          <div className="admin-user-info">
            <div className="admin-avatar">
              {adminData?.nom?.charAt(0) || 'A'}
            </div>
            <div className="admin-user-details">
              <span className="admin-name">{adminData?.nom_complet}</span>
              <span className="admin-role">{adminData?.role}</span>
            </div>
          </div>
          <button onClick={handleLogout} className="admin-logout-btn">
            Déconnexion
          </button>
        </div>
      </div>
      
      <div className="admin-header-bottom">
        <h2 className="page-title">{title}</h2>
        {breadcrumb && <span className="breadcrumb">{breadcrumb}</span>}
      </div>
    </header>
  );
};

export default AdminHeader;