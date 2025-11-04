import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import AdminLogin from './pages/Login/AdminLogin';
import AdminDashboard from './pages/Dashboard/AdminDashboard';
import AdminDocuments from './pages/Documents/AdminDocuments';
import AdminMessages from './pages/Messages/AdminMessages';
import './App.css';
import AdminRendezVous from './pages/RendezVous/AdminRendezVous';
import AdminReclamations from './pages/Reclamations/AdminReclamations';


// Fonction pour vérifier si l'admin est connecté
const isAuthenticated = () => {
  const token = localStorage.getItem('admin_token');
  const adminData = localStorage.getItem('admin_data');
  return !!(token && adminData);
};

// Composant pour protéger les routes admin
const ProtectedRoute = ({ children }) => {
  return isAuthenticated() ? children : <Navigate to="/login" replace />;
};

// Composant pour rediriger si déjà connecté
const PublicRoute = ({ children }) => {
  return isAuthenticated() ? <Navigate to="/dashboard" replace /> : children;
};

function App() {
  return (
    <Router>
      <div className="App">
        <Routes>
          {/* Route par défaut - rediriger vers login */}
          <Route path="/" element={<Navigate to="/login" replace />} />
          
          {/* Route de login - accessible seulement si non connecté */}
          <Route 
            path="/login" 
            element={
              <PublicRoute>
                <AdminLogin />
              </PublicRoute>
            } 
          />
          
          {/* Routes protégées - accessible seulement si connecté */}
          <Route 
            path="/dashboard" 
            element={
              <ProtectedRoute>
                <AdminDashboard />
              </ProtectedRoute>
            } 
          />

          <Route path="/admin/rendez-vous" element={<AdminRendezVous />} />

          <Route path="/admin/reclamations" element={<AdminReclamations />} />


          <Route path="/admin/documents" element={<AdminDocuments />} />

          <Route path="/admin/messages" element={<AdminMessages />}  />

          
          {/* Route de fallback - rediriger vers login */}
          <Route path="*" element={<Navigate to="/login" replace />} />
        </Routes>
      </div>
    </Router>
  );
}

export default App;