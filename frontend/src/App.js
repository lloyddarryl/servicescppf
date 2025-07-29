import React, { useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import api from './services/api';
import ProtectedRoute from './components/ProtectedRoute';
import HomeServices from './pages/homeservice/HomeServices';
import Services from './pages/services_board/Services';
import Login from './pages/login/Login';
import SetupProfile from './pages/setup_profile/SetupProfile';
import Dashboard from './pages/dashboard/Dashboard';
import EditProfile from './pages/dashboard/edit_profile/EditProfile';
import './App.css';

function App() {
  // Initialize Sanctum CSRF cookie on app load
  useEffect(() => {
    api.get('/sanctum/csrf-cookie')
      .then(() => {
        // Test API connection
        return api.get('/api/auth/user');
      })
      .then(response => {
        console.log('API connection successful', response.data);
      })
      .catch(error => {
        console.error('API connection failed:', error);
        if (error.response) {
          console.error('Response status:', error.response.status);
          console.error('Response data:', error.response.data);
        }
      });
  }, []);

  return (
    <Router>
      <div className="App">
        <Routes>
          {/* ✅ Routes publiques */}
          <Route path="/" element={<HomeServices />} />
          <Route path="/services" element={<Services />} />
          <Route path="/login/:type" element={<Login />} />
          <Route path="/contact" element={<div style={{padding: '6rem 2rem', textAlign: 'center'}}>Page Contact - En construction</div>} />
          
          {/* ✅ Routes de setup (avec token temporaire) */}
          <Route path="/setup-profile" element={<SetupProfile />} />
          
          {/* ✅ Routes protégées par authentification */}
          <Route path="/dashboard" element={
            <ProtectedRoute>
              <Dashboard />
            </ProtectedRoute>
          } />
          
          {/* ✅ Routes protégées spécifiques aux actifs */}
          <Route path="/actifs/profil" element={
            <ProtectedRoute requiredUserType="actif">
              <EditProfile userType="actif" />
            </ProtectedRoute>
          } />
          
          {/* ✅ Routes protégées spécifiques aux retraités */}
          <Route path="/retraites/profil" element={
            <ProtectedRoute requiredUserType="retraite">
              <EditProfile userType="retraite" />
            </ProtectedRoute>
          } />
          
          {/* ✅ Route de compatibilité */}
          <Route path="/dashboard/edit-profile" element={
            <ProtectedRoute>
              <EditProfile />
            </ProtectedRoute>
          } />

          {/* ✅ Route catch-all pour les URLs invalides */}
          <Route path="*" element={<HomeServices />} />
        </Routes>
      </div>
    </Router>
  );
}

export default App;