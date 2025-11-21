import React, { useState, useEffect } from 'react';
import { adminReclamationService } from '../services/adminApi';
import './StatistiquesReclamations.css';

const StatistiquesReclamations = ({ showTitle = true, compact = false }) => {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    loadStats();
  }, []);

  const loadStats = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await adminReclamationService.getStatistiques();
      if (response.data.success) {
        setStats(response.data.data);
      } else {
        throw new Error('Erreur lors du chargement des statistiques');
      }
    } catch (error) {
      console.error('Erreur chargement stats réclamations:', error);
      setError('Impossible de charger les statistiques');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className={`stats-widget ${compact ? 'compact' : ''}`}>
        <div className="loading-stats">
          <div className="spinner-small"></div>
          <span>Chargement des statistiques...</span>
        </div>
      </div>
    );
  }

  if (error || !stats) {
    return (
      <div className={`stats-widget error ${compact ? 'compact' : ''}`}>
        <h3>📊 Réclamations</h3>
        <p className="error-message">{error || 'Données non disponibles'}</p>
        <button onClick={loadStats} className="retry-btn">
          🔄 Réessayer
        </button>
      </div>
    );
  }

  const { globales, periode, par_type, par_priorite } = stats;

  // Calculer le pourcentage de résolution
  const tauxResolution = globales.total > 0 
    ? Math.round((globales.resolues / globales.total) * 100) 
    : 0;

  // Calculer les réclamations nécessitant une attention
  const attentionRequise = globales.en_attente + globales.urgentes;

  if (compact) {
    return (
      <div className="stats-widget compact">
        {showTitle && <h3>📋 Réclamations</h3>}
        <div className="stats-grid-compact">
          <div className="stat-mini urgent">
            <span className="stat-value">{attentionRequise}</span>
            <span className="stat-label">À traiter</span>
          </div>
          <div className="stat-mini info">
            <span className="stat-value">{globales.en_cours}</span>
            <span className="stat-label">En cours</span>
          </div>
          <div className="stat-mini success">
            <span className="stat-value">{tauxResolution}%</span>
            <span className="stat-label">Résolution</span>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="stats-widget">
      {showTitle && <h3>📊 Réclamations - Vue d'ensemble</h3>}
      
      {/* Statistiques principales */}
      <div className="stats-grid-mini">
        <div className="stat-mini urgent">
          <span className="stat-value">{globales.en_attente}</span>
          <span className="stat-label">En attente</span>
          {globales.en_attente > 5 && (
            <span className="stat-badge">{globales.en_attente}</span>
          )}
        </div>
        <div className="stat-mini info">
          <span className="stat-value">{globales.en_cours}</span>
          <span className="stat-label">En cours</span>
        </div>
        <div className="stat-mini warning">
          <span className="stat-value">{globales.urgentes}</span>
          <span className="stat-label">Urgentes</span>
          {globales.urgentes > 0 && (
            <span className="stat-badge">!</span>
          )}
        </div>
        <div className="stat-mini success">
          <span className="stat-value">{globales.resolues}</span>
          <span className="stat-label">Résolues</span>
        </div>
      </div>

      {/* Métriques de performance */}
      <div className="stats-summary">
        <div className="summary-item">
          <span>Total:</span>
          <strong>{globales.total} réclamations</strong>
        </div>
        <div className="summary-item">
          <span>Taux de résolution:</span>
          <strong style={{ color: tauxResolution >= 70 ? '#059669' : '#d97706' }}>
            {tauxResolution}%
          </strong>
        </div>
        <div className="summary-item">
          <span>Ce mois:</span>
          <strong>{periode.nouvelles_ce_mois} nouvelles</strong>
        </div>
        <div className="summary-item">
          <span>Traitées ce mois:</span>
          <strong>{periode.traitees_ce_mois}</strong>
        </div>
      </div>

      {/* Alertes si nécessaire */}
      {globales.urgentes > 0 && (
        <div className="stats-alert urgent">
          ⚠️ <strong>{globales.urgentes}</strong> réclamation(s) urgente(s) nécessitent une attention immédiate
        </div>
      )}

      {globales.en_attente > 10 && (
        <div className="stats-alert warning">
          📋 <strong>{globales.en_attente}</strong> réclamations en attente de traitement
        </div>
      )}

      {/* Répartition par type (top 3) */}
      {par_type && par_type.length > 0 && (
        <div className="stats-breakdown">
          <h4>📊 Types les plus fréquents</h4>
          <div className="breakdown-list">
            {par_type.slice(0, 3).map((type, index) => (
              <div key={index} className="breakdown-item">
                <span className="breakdown-label">
                  {index === 0 && '🥇 '}
                  {index === 1 && '🥈 '}
                  {index === 2 && '🥉 '}
                  {type.type_reclamation}
                </span>
                <span className="breakdown-value">{type.total}</span>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default StatistiquesReclamations;