import React, { useState } from 'react';
import { documentService } from '../../services/api';
import './DocumentList.css';

const DocumentList = ({ documents, onDownload, onDelete, onRefresh }) => {
  const [deletingId, setDeletingId] = useState(null);
  const [deleteConfirmId, setDeleteConfirmId] = useState(null);

  // Gérer la suppression d'un document
  const handleDelete = async (documentId) => {
    if (deletingId) return;
    
    setDeletingId(documentId);
    
    try {
      await documentService.delete(documentId);
      onDelete();
      setDeleteConfirmId(null);
    } catch (error) {
      console.error('Erreur suppression:', error);
      // Ici ajouter notification d'erreur
    } finally {
      setDeletingId(null);
    }
  };

  // Confirmer la suppression
  const confirmDelete = (documentId) => {
    setDeleteConfirmId(documentId);
  };

  // Annuler la suppression
  const cancelDelete = () => {
    setDeleteConfirmId(null);
  };

  // Obtenir la classe CSS selon le statut d'expiration - UTILISE LES DONNÉES SERVEUR
  const getExpirationClass = (document) => {
    if (!document.date_expiration) return '';
    
    // Utiliser les données calculées côté serveur au lieu de recalculer
    if (document.is_expire) {
      return 'document-card--expired';
    } else if (document.expire_bientot) {
      return 'document-card--expiring';
    } else {
      return 'document-card--valid';
    }
  };

  // Formater la date d'expiration - UTILISE LES DONNÉES SERVEUR
  const formatExpirationInfo = (document) => {
    if (!document.date_expiration) return null;
    
    // Utiliser jours_avant_expiration qui vient du serveur (308-309 jours)
    const jours = document.jours_avant_expiration;
    
    if (document.is_expire) {
      return {
        message: `Expiré depuis ${Math.abs(jours)} jour(s)`,
        color: '#EF4444',
        icon: '❌',
        priority: 'critical'
      };
    } else if (jours <= 30) {
      // Rouge - expire dans moins de 30 jours
      return {
        message: `Expire dans ${jours} jour(s)`,
        color: '#EF4444',
        icon: '🚨',
        priority: 'critical'
      };
    } else if (jours <= 60) {
      // Orange - expire dans 31-60 jours
      return {
        message: `Expire dans ${jours} jour(s)`,
        color: '#F59E0B',
        icon: '⚠️',
        priority: 'warning'
      };
    } else {
      // Vert - plus de 60 jours
      return {
        message: `Valide encore ${jours} jour(s)`,
        color: '#10B981',
        icon: '✅',
        priority: 'success'
      };
    }
  };

  if (documents.length === 0) {
    return (
      <div className="document-list-empty">
        <div className="document-list-empty__icon">📄</div>
        <h3>Aucun document déposé</h3>
        <p>
          Vous n'avez pas encore déposé de documents. 
          Commencez par déposer votre certificat de vie annuel.
        </p>
      </div>
    );
  }

  return (
    <div className="document-list">
      <div className="document-list__grid">
        {documents.map((document) => {
          const expirationInfo = formatExpirationInfo(document);
          
          return (
            <div 
              key={document.id} 
              className={`document-card ${getExpirationClass(document)}`}
            >
              
              {/* En-tête de la carte */}
              <div className="document-card__header">
                <div className="document-card__icon">
                  {document.icone_type}
                </div>
                <div className="document-card__info">
                  <h3 className="document-card__name" title={document.nom_original}>
                    {document.nom_original}
                  </h3>
                  <div className="document-card__meta">
                    <span className="document-card__type">
                      {document.nom_type}
                    </span>
                    <span className="document-card__size">
                      {document.taille_formatee}
                    </span>
                  </div>
                </div>
                <div className="document-card__actions">
                  <button
                    className="document-card__action document-card__action--download"
                    onClick={() => onDownload(document.id, document.nom_original)}
                    title="Télécharger"
                  >
                    ⬇️
                  </button>
                  <button
                    className="document-card__action document-card__action--delete"
                    onClick={() => confirmDelete(document.id)}
                    disabled={deletingId === document.id}
                    title="Supprimer"
                  >
                    {deletingId === document.id ? '⏳' : '🗑️'}
                  </button>
                </div>
              </div>

              {/* Informations détaillées */}
              <div className="document-card__details">
                <div className="document-detail">
                  <span className="document-detail__label">Déposé le :</span>
                  <span className="document-detail__value">{document.date_depot}</span>
                </div>
                
                {document.date_emission && (
                  <div className="document-detail">
                    <span className="document-detail__label">Date d'émission :</span>
                    <span className="document-detail__value">{document.date_emission}</span>
                  </div>
                )}
                
                {document.autorite_emission && (
                  <div className="document-detail">
                    <span className="document-detail__label">Autorité :</span>
                    <span className="document-detail__value">{document.autorite_emission}</span>
                  </div>
                )}
                
                {document.description && (
                  <div className="document-detail">
                    <span className="document-detail__label">Description :</span>
                    <span className="document-detail__value">{document.description}</span>
                  </div>
                )}
              </div>

              {/* Informations d'expiration */}
              {expirationInfo && (
                <div className={`document-card__expiration document-card__expiration--${expirationInfo.priority}`}>
                  <span className="document-card__expiration-icon">
                    {expirationInfo.icon}
                  </span>
                  <span className="document-card__expiration-message">
                    {expirationInfo.message}
                  </span>
                  {document.date_expiration && (
                    <span className="document-card__expiration-date">
                      (Échéance : {document.date_expiration})
                    </span>
                  )}
                </div>
              )}

              {/* Modal de confirmation de suppression */}
              {deleteConfirmId === document.id && (
                <div className="document-card__delete-confirm">
                  <div className="delete-confirm__content">
                    <h4>Confirmer la suppression</h4>
                    <p>
                      Êtes-vous sûr de vouloir supprimer ce document ? 
                      Cette action est irréversible.
                    </p>
                    <div className="delete-confirm__actions">
                      <button 
                        className="btn btn--secondary"
                        onClick={cancelDelete}
                      >
                        Annuler
                      </button>
                      <button 
                        className="btn btn--danger"
                        onClick={() => handleDelete(document.id)}
                        disabled={deletingId === document.id}
                      >
                        {deletingId === document.id ? 'Suppression...' : 'Supprimer'}
                      </button>
                    </div>
                  </div>
                </div>
              )}

            </div>
          );
        })}
      </div>

      {/* Informations utiles en bas */}
      <div className="document-list__footer">
        <div className="document-list__legend">
          <h4>Légende des couleurs :</h4>
          <div className="legend-items">
            <div className="legend-item">
              <div className="legend-color legend-color--valid"></div>
              <span>Document valide (plus de 60 jours)</span>
            </div>
            <div className="legend-item">
              <div className="legend-color legend-color--expiring"></div>
              <span>Expire bientôt (30-60 jours)</span>
            </div>
            <div className="legend-item">
              <div className="legend-color legend-color--expired"></div>
              <span>Document expiré ou urgent (moins de 30 jours)</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default DocumentList;