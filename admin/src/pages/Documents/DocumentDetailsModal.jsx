// ✅ CORRECTIONS APPLIQUÉES:
// 1. Suppression de documentUrl des dépendances du useEffect pour éviter la boucle infinie
// 2. Ajout de eslint-disable pour les warnings

import { useState, useEffect } from 'react';
import { adminDocumentService } from '../../services/adminApi';
import './DocumentDetailsModal.css';

const DocumentDetailsModal = ({ document, onClose }) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [viewMode, setViewMode] = useState('details'); // 'details' ou 'preview'
  const [documentUrl, setDocumentUrl] = useState(null);
  
  // États pour validation/rejet
  const [showValidationForm, setShowValidationForm] = useState(false);
  const [showRejetForm, setShowRejetForm] = useState(false);
  const [validationData, setValidationData] = useState({
    commentaire: '',
    envoyer_notification: true
  });
  const [rejetData, setRejetData] = useState({
    motif: '',
    commentaire: '',
    envoyer_notification: true
  });
  const [motifsRejet, setMotifsRejet] = useState([]);

  // ✅ CORRECTION: useEffect sans documentUrl dans les dépendances
  useEffect(() => {
    loadMotifsRejet();
    loadDocumentPreview();
    
    // Cleanup: Libérer l'URL du blob quand le document change ou le composant se démonte
    return () => {
      if (documentUrl && documentUrl.startsWith('blob:')) {
        URL.revokeObjectURL(documentUrl);
      }
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [document.id]); // ✅ Seulement document.id, pas documentUrl

  const loadMotifsRejet = async () => {
    try {
      const response = await adminDocumentService.getMotifsRejet();
      if (response.data.success) {
        setMotifsRejet(response.data.motifs);
      }
    } catch (error) {
      console.error('Erreur chargement motifs:', error);
    }
  };

  const loadDocumentPreview = async () => {
    try {
      // Pour les PDF et images, on peut charger une prévisualisation
      if (['pdf', 'jpg', 'jpeg', 'png'].includes(document.extension)) {
        const token = localStorage.getItem('admin_token');
        
        const response = await fetch(
          `http://localhost:8000/api/admin/documents/${document.id}/view`,
          {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Accept': 'application/pdf,image/*'
            }
          }
        );
        
        if (response.ok) {
          const blob = await response.blob();
          const blobUrl = URL.createObjectURL(blob);
          setDocumentUrl(blobUrl);
        } else {
          setError('Impossible de charger le document');
        }
      }
    } catch (error) {
      console.error('Erreur preview:', error);
      setError('Erreur lors du chargement');
    }
  };

  const handleValider = async () => {
    if (loading) return;

    // Validation des données
    const validation = adminDocumentService.utils.validerDonneesValidation(validationData);
    if (!validation.isValid) {
      setError(validation.errors.join(', '));
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const response = await adminDocumentService.valider(document.id, validationData);
      
      if (response.data.success) {
        setSuccess('✅ Document validé avec succès !');
        setTimeout(() => {
          onClose();
        }, 1500);
      }
    } catch (error) {
      console.error('Erreur validation:', error);
      setError(error.response?.data?.message || 'Erreur lors de la validation');
    } finally {
      setLoading(false);
    }
  };

  const handleRejeter = async () => {
    if (loading) return;

    // Validation des données
    const validation = adminDocumentService.utils.validerDonneesRejet(rejetData);
    if (!validation.isValid) {
      setError(validation.errors.join(', '));
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const response = await adminDocumentService.rejeter(document.id, rejetData);
      
      if (response.data.success) {
        setSuccess('✅ Document rejeté !');
        setTimeout(() => {
          onClose();
        }, 1500);
      }
    } catch (error) {
      console.error('Erreur rejet:', error);
      setError(error.response?.data?.message || 'Erreur lors du rejet');
    } finally {
      setLoading(false);
    }
  };

  const handleSupprimer = async () => {
    if (loading) return;
    
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer ce document ? Cette action est irréversible.')) {
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const response = await adminDocumentService.supprimer(document.id);
      
      if (response.data.success) {
        setSuccess('✅ Document supprimé !');
        setTimeout(() => {
          onClose();
        }, 1500);
      }
    } catch (error) {
      console.error('Erreur suppression:', error);
      setError(error.response?.data?.message || 'Erreur lors de la suppression');
    } finally {
      setLoading(false);
    }
  };

  const handleTelecharger = async () => {
    try {
      setLoading(true);
      await adminDocumentService.download(document.id, document.nom_original);
      setSuccess('✅ Téléchargement lancé !');
      setTimeout(() => setSuccess(null), 2000);
    } catch (error) {
      console.error('Erreur téléchargement:', error);
      setError('Erreur lors du téléchargement');
    } finally {
      setLoading(false);
    }
  };

  const getExpirationStatus = () => {
    if (!document.date_expiration) return null;
    return adminDocumentService.utils.getExpirationStatus(document.date_expiration);
  };

  const expirationStatus = getExpirationStatus();

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content document-modal" onClick={(e) => e.stopPropagation()}>
        
        {/* Header */}
        <div className="modal-header">
          <div className="modal-title-section">
            <h2>
              <span className="doc-icon">{document.icone_type}</span>
              {document.nom_type}
            </h2>
            <p className="doc-filename">{document.nom_original}</p>
          </div>
          <button onClick={onClose} className="btn-close">✕</button>
        </div>

        {/* Messages */}
        {error && (
          <div className="alert alert-danger">
            <strong>❌ Erreur:</strong> {error}
          </div>
        )}
        {success && (
          <div className="alert alert-success">
            {success}
          </div>
        )}

        {/* Tabs */}
        <div className="modal-tabs">
          <button
            className={`tab-btn ${viewMode === 'details' ? 'active' : ''}`}
            onClick={() => setViewMode('details')}
          >
            📋 Détails
          </button>
          <button
            className={`tab-btn ${viewMode === 'preview' ? 'active' : ''}`}
            onClick={() => setViewMode('preview')}
            disabled={!documentUrl}
          >
            👁️ Prévisualisation
          </button>
        </div>

        {/* Body */}
        <div className="modal-body">
          {viewMode === 'details' ? (
            <>
              {/* Informations retraité */}
              <div className="info-section">
                <h3>👤 Retraité</h3>
                <div className="info-grid">
                  <div className="info-item">
                    <strong>Nom complet:</strong>
                    <span>{document.retraite?.nom_complet_avec_titre || 'N/A'}</span>
                  </div>
                  <div className="info-item">
                    <strong>N° Pension:</strong>
                    <span>{document.retraite?.numero_pension || 'N/A'}</span>
                  </div>
                </div>
              </div>

              {/* Informations document */}
              <div className="info-section">
                <h3>📄 Document</h3>
                <div className="info-grid">
                  <div className="info-item">
                    <strong>Type:</strong>
                    <span>{document.nom_type}</span>
                  </div>
                  <div className="info-item">
                    <strong>Date de dépôt:</strong>
                    <span>{document.date_depot}</span>
                  </div>
                  <div className="info-item">
                    <strong>Taille:</strong>
                    <span>{document.taille_formatee}</span>
                  </div>
                  <div className="info-item">
                    <strong>Format:</strong>
                    <span>{document.extension.toUpperCase()}</span>
                  </div>
                  <div className="info-item">
                    <strong>Statut:</strong>
                    <span className={`badge badge-${document.statut}`}>
                      {document.statut}
                    </span>
                  </div>
                  
                  {document.date_emission && (
                    <div className="info-item">
                      <strong>Date d'émission:</strong>
                      <span>{document.date_emission}</span>
                    </div>
                  )}
                  
                  {document.date_expiration && (
                    <div className="info-item">
                      <strong>Date d'expiration:</strong>
                      <span className={document.is_expire ? 'text-danger' : document.expire_bientot ? 'text-warning' : ''}>
                        {document.date_expiration}
                        {document.is_expire && ' (Expiré)'}
                        {!document.is_expire && document.expire_bientot && (
                          <small> ({document.jours_avant_expiration} jours)</small>
                        )}
                      </span>
                    </div>
                  )}
                  
                  {document.autorite_emission && (
                    <div className="info-item full-width">
                      <strong>Autorité d'émission:</strong>
                      <span>{document.autorite_emission}</span>
                    </div>
                  )}
                  
                  {document.description && (
                    <div className="info-item full-width">
                      <strong>Description:</strong>
                      <span>{document.description}</span>
                    </div>
                  )}
                </div>
              </div>

              {/* Statut expiration */}
              {expirationStatus && (
                <div className={`status-banner status-${expirationStatus.status}`}>
                  <span className="status-icon">{expirationStatus.icon}</span>
                  <div className="status-content">
                    <strong>{expirationStatus.label}</strong>
                    {document.is_expire && (
                      <p>Ce document a expiré et nécessite un renouvellement</p>
                    )}
                    {document.expire_bientot && !document.is_expire && (
                      <p>Ce document expire dans {document.jours_avant_expiration} jour(s)</p>
                    )}
                  </div>
                </div>
              )}

              {/* Métadonnées (validation/rejet précédents) */}
              {document.metadata && (
                <div className="info-section">
                  <h3>📝 Historique</h3>
                  
                  {document.metadata.validation && (
                    <div className="history-item validation">
                      <div className="history-header">
                        <strong>✅ Validé</strong>
                        <span>{new Date(document.metadata.validation.date).toLocaleString('fr-FR')}</span>
                      </div>
                      <p><strong>Par:</strong> {document.metadata.validation.admin_nom}</p>
                      {document.metadata.validation.commentaire && (
                        <p><strong>Commentaire:</strong> {document.metadata.validation.commentaire}</p>
                      )}
                    </div>
                  )}
                  
                  {document.metadata.rejet && (
                    <div className="history-item rejet">
                      <div className="history-header">
                        <strong>❌ Rejeté</strong>
                        <span>{new Date(document.metadata.rejet.date).toLocaleString('fr-FR')}</span>
                      </div>
                      <p><strong>Par:</strong> {document.metadata.rejet.admin_nom}</p>
                      <p><strong>Motif:</strong> {document.metadata.rejet.motif_libelle}</p>
                      {document.metadata.rejet.commentaire && (
                        <p><strong>Commentaire:</strong> {document.metadata.rejet.commentaire}</p>
                      )}
                    </div>
                  )}
                </div>
              )}

              {/* Formulaires de validation/rejet */}
              {showValidationForm && (
                <div className="action-form validation-form">
                  <h3>✅ Valider le document</h3>
                  <div className="form-group">
                    <label>Commentaire (optionnel)</label>
                    <textarea
                      value={validationData.commentaire}
                      onChange={(e) => setValidationData({ ...validationData, commentaire: e.target.value })}
                      placeholder="Ajouter un commentaire..."
                      rows={3}
                      maxLength={500}
                    />
                    <small>{validationData.commentaire.length}/500 caractères</small>
                  </div>
                  <div className="form-group">
                    <label className="checkbox-label">
                      <input
                        type="checkbox"
                        checked={validationData.envoyer_notification}
                        onChange={(e) => setValidationData({ ...validationData, envoyer_notification: e.target.checked })}
                      />
                      Envoyer une notification par email au retraité
                    </label>
                  </div>
                  <div className="form-actions">
                    <button onClick={() => setShowValidationForm(false)} className="btn btn-secondary">
                      Annuler
                    </button>
                    <button onClick={handleValider} className="btn btn-success" disabled={loading}>
                      {loading ? 'Validation...' : '✅ Confirmer la validation'}
                    </button>
                  </div>
                </div>
              )}

              {showRejetForm && (
                <div className="action-form rejet-form">
                  <h3>❌ Rejeter le document</h3>
                  <div className="form-group">
                    <label>Motif du rejet *</label>
                    <select
                      value={rejetData.motif}
                      onChange={(e) => setRejetData({ ...rejetData, motif: e.target.value })}
                      required
                    >
                      <option value="">Sélectionner un motif</option>
                      {motifsRejet.map((motif) => (
                        <option key={motif.value} value={motif.value}>
                          {motif.label}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div className="form-group">
                    <label>
                      Commentaire {rejetData.motif === 'autre' ? '*' : '(optionnel)'}
                    </label>
                    <textarea
                      value={rejetData.commentaire}
                      onChange={(e) => setRejetData({ ...rejetData, commentaire: e.target.value })}
                      placeholder="Préciser le motif du rejet..."
                      rows={4}
                      maxLength={500}
                      required={rejetData.motif === 'autre'}
                    />
                    <small>{rejetData.commentaire.length}/500 caractères</small>
                  </div>
                  <div className="form-group">
                    <label className="checkbox-label">
                      <input
                        type="checkbox"
                        checked={rejetData.envoyer_notification}
                        onChange={(e) => setRejetData({ ...rejetData, envoyer_notification: e.target.checked })}
                      />
                      Envoyer une notification par email au retraité
                    </label>
                  </div>
                  <div className="form-actions">
                    <button onClick={() => setShowRejetForm(false)} className="btn btn-secondary">
                      Annuler
                    </button>
                    <button onClick={handleRejeter} className="btn btn-danger" disabled={loading || !rejetData.motif}>
                      {loading ? 'Rejet...' : '❌ Confirmer le rejet'}
                    </button>
                  </div>
                </div>
              )}

            </>
          ) : (
            // Prévisualisation
            <div className="preview-container">
              {documentUrl ? (
                <iframe
                  src={documentUrl}
                  title="Prévisualisation du document"
                  className="document-preview"
                />
              ) : (
                <div className="preview-unavailable">
                  <p>Chargement de la prévisualisation...</p>
                </div>
              )}
            </div>
          )}
        </div>

        {/* Footer - Actions */}
        {viewMode === 'details' && !showValidationForm && !showRejetForm && (
          <div className="modal-footer">
            <div className="footer-actions-left">
              <button onClick={handleTelecharger} className="btn btn-secondary" disabled={loading}>
                📥 Télécharger
              </button>
              <button onClick={handleSupprimer} className="btn btn-danger-outline" disabled={loading}>
                🗑️ Supprimer
              </button>
            </div>
            <div className="footer-actions-right">
              <button onClick={() => setShowRejetForm(true)} className="btn btn-danger" disabled={loading}>
                ❌ Rejeter
              </button>
              <button onClick={() => setShowValidationForm(true)} className="btn btn-success" disabled={loading}>
                ✅ Valider
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default DocumentDetailsModal;