import React, { useState, useEffect } from 'react';
import { Navigate } from 'react-router-dom';
import { utils } from '../services/api';

const ProtectedRoute = ({ children, requiredUserType = null }) => {
  const [isAuthenticated, setIsAuthenticated] = useState(null); // null = loading
  const [userType, setUserType] = useState(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const checkAuth = async () => {
      try {
        // Vérifier si on a un token
        const token = localStorage.getItem('auth_token');
        if (!token) {
          setIsAuthenticated(false);
          setIsLoading(false);
          return;
        }

        // Vérifier la validité du token avec le serveur
        const response = await fetch('http://localhost:8000/api/auth/verify', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          }
        });

        if (response.ok) {
          setIsAuthenticated(true);
          
          // Récupérer les données utilisateur pour déterminer le type
          const userResponse = await fetch('http://localhost:8000/api/auth/user', {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Accept': 'application/json',
            }
          });

          if (userResponse.ok) {
            const userData = await userResponse.json();
            if (userData.success) {
              setUserType(userData.user.type);
              // Mettre à jour le localStorage avec les données fraîches
              localStorage.setItem('user_type', userData.user.type);
              localStorage.setItem('user_data', JSON.stringify(userData.user));
            }
          }
        } else {
          // Token invalide
          utils.clearSession();
          setIsAuthenticated(false);
        }
      } catch (error) {
        console.error('Erreur vérification auth:', error);
        utils.clearSession();
        setIsAuthenticated(false);
      } finally {
        setIsLoading(false);
      }
    };

    checkAuth();
  }, []);

  // Affichage du loading
  if (isLoading) {
    return (
      <div style={{
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
        minHeight: '100vh',
        flexDirection: 'column',
        gap: '1rem'
      }}>
        <div style={{
          width: '2rem',
          height: '2rem',
          border: '3px solid #e5e7eb',
          borderTop: '3px solid #3B82F6',
          borderRadius: '50%',
          animation: 'spin 1s linear infinite'
        }}></div>
        <p>Vérification de votre session...</p>
      </div>
    );
  }

  // Si pas authentifié, rediriger vers les services
  if (!isAuthenticated) {
    return <Navigate to="/services" replace />;
  }

  // Si un type d'utilisateur spécifique est requis, vérifier
  if (requiredUserType && userType !== requiredUserType) {
    // Rediriger vers le bon type de page selon l'utilisateur
    const redirectPath = userType === 'actif' ? '/actifs/profil' : '/retraites/profil';
    return <Navigate to={redirectPath} replace />;
  }

  // Tout est OK, afficher le composant protégé
  return children;
};

export default ProtectedRoute;