import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import Header from '../../components/Header';
import { documentService, utils } from '../../services/api';
import DocumentUpload from '../../components/documents/DocumentUpload';
import DocumentList from '../../components/documents/DocumentList';
import NotificationsCertificat from '../../components/documents/NotificationsCertificat';
import './MesDocuments.css';

const MesDocuments = () => {
  const navigate = useNavigate();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [uploadModalOpen, setUploadModalOpen] = useState(false);
  const [refreshTrigger, setRefreshTrigger] = useState(0);

  // Charger les données initiales
  const fetchData = useCallback(async () => {
    try {
      if (!utils.isAuthenticated()) {
        navigate('/services');
        return;
      }

      const response = await documentService.getAll();
      
      if (response.data.success) {
        setData(response.data);
      } else {
        throw new Error(response.data.message || 'Erreur de chargement');
      }
    } catch (error) {
      console.error('Erreur chargement documents:', error);
      setError('Impossible de charger vos documents');
      
      if (error.response?.status === 401) {
        utils.clearSession();
        navigate('/services');
      }
    } finally {
      setLoading(false);
    }
  }, [navigate]);

  useEffect(() => {
    fetchData();
  }, [fetchData, refreshTrigger]);

  // Rafraîchir les données après une action
  const handleRefresh = useCallback(() => {
    setRefreshTrigger(prev => prev + 1);
  }, []);

  // Gérer l'upload terminé avec succès
  const handleUploadSuccess = useCallback((result) => {
    setUploadModalOpen(false);
    handleRefresh();
    
    const message = documentService.utils.getNotificationMessage('upload_success', { 
      count: result.documents.length 
    });
    
    console.log('Success:', message);
  }, [handleRefresh]);

  // Gérer la suppression d'un document
  const handleDocumentDeleted = useCallback(() => {
    handleRefresh();
  }, [handleRefresh]);

  // Gérer le téléchargement d'un document
  const handleDocumentDownload = useCallback(async (documentId, nomOriginal) => {
    try {
      const response = await documentService.download(documentId);
      documentService.utils.downloadBlob(response.data, nomOriginal);
    } catch (error) {
      console.error('Erreur téléchargement:', error);
    }
  }, []);

  if (loading) {
    return (
      <div className="mes-documents">
        <Header />
        <div className="mes-documents__loading">
          <div className="mes-documents__spinner"></div>
          <p>Chargement de vos documents...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="mes-documents">
        <Header />
        <div className="mes-documents__error">
          <h2>Erreur</h2>
          <p>{error}</p>
          <button onClick={() => handleRefresh()}>Réessayer</button>
        </div>
      </div>
    );
  }

  const { retraite, documents, notifications, statistiques, limites } = data;

  return (
    <div className="mes-documents">
      <Header />
      
      <main className="mes-documents__main">
        <div className="mes-documents__container">
          
          {/* Section de bienvenue */}
          <section className="mes-documents__welcome">
            <div className="mes-documents__welcome-content">
              <div className="mes-documents__welcome-text">
                <h1 className="mes-documents__title">
                  Bienvenue {retraite.nom_complet_avec_titre}
                </h1>
                <p className="mes-documents__subtitle">
                  Numéro de pension : <strong>{retraite.numero_pension}</strong>
                </p>
                <p className="mes-documents__description">
                  Gérez vos documents personnels et certificats de vie en toute sécurité.
                </p>
              </div>
              <div className="mes-documents__welcome-actions">
                <button 
                  className="mes-documents__btn mes-documents__btn--primary"
                  onClick={() => setUploadModalOpen(true)}
                >
                  Déposer des documents
                </button>
              </div>
            </div>
          </section>

          {/* Notifications de certificat de vie */}
          {notifications && notifications.length > 0 && (
            <NotificationsCertificat 
              notifications={notifications}
              onDismiss={(type) => documentService.dismissNotification(type)}
            />
          )}

          {/* Statistiques rapides */}
          <section className="mes-documents__stats">
            <div className="mes-documents__stats-grid">
              <div className="mes-documents__stat-card mes-documents__stat-card--primary">
                <div className="mes-documents__stat-icon">📄</div>
                <div className="mes-documents__stat-content">
                  <h3 className="mes-documents__stat-title">Total Documents</h3>
                  <p className="mes-documents__stat-value">{statistiques.total_documents}</p>
                </div>
              </div>
              
              <div className="mes-documents__stat-card mes-documents__stat-card--success">
                <div className="mes-documents__stat-icon">📋</div>
                <div className="mes-documents__stat-content">
                  <h3 className="mes-documents__stat-title">Certificats de Vie</h3>
                  <p className="mes-documents__stat-value">{statistiques.certificats_vie}</p>
                </div>
              </div>
              
              <div className="mes-documents__stat-card mes-documents__stat-card--info">
                <div className="mes-documents__stat-icon">📁</div>
                <div className="mes-documents__stat-content">
                  <h3 className="mes-documents__stat-title">Autres Documents</h3>
                  <p className="mes-documents__stat-value">{statistiques.autres_documents}</p>
                </div>
              </div>
              
              {statistiques.documents_expires > 0 && (
                <div className="mes-documents__stat-card mes-documents__stat-card--warning">
                  <div className="mes-documents__stat-icon">⚠️</div>
                  <div className="mes-documents__stat-content">
                    <h3 className="mes-documents__stat-title">Documents Expirés</h3>
                    <p className="mes-documents__stat-value">{statistiques.documents_expires}</p>
                  </div>
                </div>
              )}
            </div>
          </section>

          {/* Section principale avec liste des documents */}
          <section className="mes-documents__content">
            <div className="mes-documents__content-header">
              <h2 className="mes-documents__section-title">Vos Documents</h2>
              <div className="mes-documents__content-actions">
                <button 
                  className="mes-documents__btn mes-documents__btn--secondary"
                  onClick={handleRefresh}
                >
                  Actualiser
                </button>
                <button 
                  className="mes-documents__btn mes-documents__btn--primary"
                  onClick={() => setUploadModalOpen(true)}
                >
                  + Ajouter
                </button>
              </div>
            </div>

            <DocumentList
              documents={documents}
              onDownload={handleDocumentDownload}
              onDelete={handleDocumentDeleted}
              onRefresh={handleRefresh}
            />
          </section>

          {/* Informations sur les limites */}
          <section className="mes-documents__info">
            <div className="mes-documents__info-card">
              <h3>Informations importantes</h3>
              <ul>
                <li>Maximum <strong>{limites.max_fichiers}</strong> fichiers à la fois</li>
                <li>Taille maximale : <strong>{limites.taille_max_mo} MB</strong> par fichier</li>
                <li>Formats acceptés : <strong>{limites.extensions_autorisees.join(', ').toUpperCase()}</strong></li>
                <li>Les certificats de vie sont <strong>obligatoires</strong> et doivent être renouvelés annuellement</li>
              </ul>
            </div>
          </section>

        </div>
      </main>

      {/* Modal d'upload */}
      {uploadModalOpen && (
        <DocumentUpload
          isOpen={uploadModalOpen}
          onClose={() => setUploadModalOpen(false)}
          onSuccess={handleUploadSuccess}
          limites={limites}
        />
      )}
    </div>
  );
};

export default MesDocuments;