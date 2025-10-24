// admin-cppf/src/components/AdminNav.jsx
import React from 'react';
import { NavLink } from 'react-router-dom';
import './AdminNav.css';

const AdminNav = () => {
  const menuItems = [
    { path: '/admin/dashboard', icon: '📊', label: 'Dashboard' },
    { path: '/admin/rendez-vous', icon: '📅', label: 'Rendez-vous' },
    { path: '/admin/reclamations', icon: '📋', label: 'Réclamations' },
    { path: '/admin/documents', icon: '📄', label: 'Documents' },
    { path: '/admin/utilisateurs', icon: '👥', label: 'Utilisateurs' },
    { path: '/admin/messages', icon: '💬', label: 'Messages' },
    { path: '/admin/rapports', icon: '📈', label: 'Rapports' },
  ];

  return (
    <nav className="admin-nav">
      {menuItems.map(item => (
        <NavLink 
          key={item.path}
          to={item.path}
          className={({ isActive }) => `nav-item ${isActive ? 'active' : ''}`}
        >
          <span className="nav-icon">{item.icon}</span>
          <span className="nav-label">{item.label}</span>
        </NavLink>
      ))}
    </nav>
  );
};

export default AdminNav;