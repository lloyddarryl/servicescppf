import React, { useState, useRef } from 'react';
import { documentService } from '../../services/api';
import './DocumentUpload.css';

const DocumentUpload = ({ isOpen, onClose, onSuccess, limites }) => {
  const [files, setFiles] = useState([]);
  const [uploading, setUploading] = useState(false);
  const [errors, setErrors] = useState([]);
  const fileInputRef = useRef(null);

  // Réinitialiser le composant quand il s'ouvre
  React.useEffect(() => {
    if (isOpen) {
      setFiles([]);
      setErrors([]);
      setUploading(false);
    }
  }, [isOpen]);

  // Gérer la sélection de fichiers
  const handleFileSelect = (event) => {
    const selectedFiles = Array.from(event.target.files);
    
    // Validation des fichiers
    const validation = documentService.utils.validateFiles(selectedFiles);
    
    if (!validation.isValid) {
      setErrors(validation.errors);
      return;
    }

    // Créer des objets fichier avec métadonnées
    const fileObjects = selectedFiles.map((file, index) => ({
      id: `file-${Date.now()}-${index}`,
      file,
      name: file.name,
      size: file.size,
      extension: file.name.split('.').pop().toLowerCase(),
      type: 'autre', // Type par défaut
      description: '',
      dateEmission: '',
      autoriteEmission: ''
    }));

    setFiles(fileObjects);
    setErrors([]);
  };

  // Mettre à jour les métadonnées d'un fichier
  const updateFileMetadata = (fileId, field, value) => {
    setFiles(prevFiles => 
      prevFiles.map(file => 
        file.id === fileId ? { ...file, [field]: value } : file
      )
    );
  };

  // Supprimer un fichier de la liste
  const removeFile = (fileId) => {
    setFiles(prevFiles => prevFiles.filter(file => file.id !== fileId));
  };

  // Valider les données avant envoi
  const validateBeforeUpload = () => {
    const validationErrors = [];

    files.forEach((fileObj, index) => {
      const fieldValidation = documentService.utils.validateRequiredFields(fileObj.type, {
        description: fileObj.description,
        dateEmission: fileObj.dateEmission,
        autoriteEmission: fileObj.autoriteEmission
      });

      if (!fieldValidation.isValid) {
        fieldValidation.errors.forEach(error => {
          validationErrors.push(`Fichier ${index + 1} (${fileObj.name}): ${error}`);
        });
      }
    });

    return validationErrors;
  };

  // Dans DocumentUpload.jsx - Corriger la méthode handleUpload

const handleUpload = async () => {
  if (files.length === 0) return;

  const validationErrors = validateBeforeUpload();
  if (validationErrors.length > 0) {
    setErrors(validationErrors);
    return;
  }

  setUploading(true);
  setErrors([]);

  try {
    // Créer FormData manuellement pour un meilleur contrôle
    const formData = new FormData();
    
    // Ajouter les fichiers
    files.forEach((fileObj, index) => {
      formData.append(`documents[${index}]`, fileObj.file);
      console.log(`Ajout fichier ${index}:`, fileObj.file.name, fileObj.file.size);
    });
    
    // Ajouter les types
    files.forEach((fileObj, index) => {
      formData.append(`types[${index}]`, fileObj.type);
      console.log(`Type ${index}:`, fileObj.type);
    });
    
    // Ajouter les descriptions
    files.forEach((fileObj, index) => {
      if (fileObj.description && fileObj.description.trim()) {
        formData.append(`descriptions[${index}]`, fileObj.description.trim());
        console.log(`Description ${index}:`, fileObj.description);
      }
    });
    
    // Ajouter les dates d'émission
    files.forEach((fileObj, index) => {
      if (fileObj.dateEmission) {
        formData.append(`dates_emission[${index}]`, fileObj.dateEmission);
        console.log(`Date émission ${index}:`, fileObj.dateEmission);
      }
    });
    
    // Ajouter les autorités d'émission
    files.forEach((fileObj, index) => {
      if (fileObj.autoriteEmission && fileObj.autoriteEmission.trim()) {
        formData.append(`autorites_emission[${index}]`, fileObj.autoriteEmission.trim());
        console.log(`Autorité ${index}:`, fileObj.autoriteEmission);
      }
    });

    // Débugger le contenu du FormData
    console.log('Contenu FormData:');
    for (let [key, value] of formData.entries()) {
      console.log(key, value);
    }

    const response = await documentService.upload(formData);

    if (response.data.success) {
      console.log('Upload réussi:', response.data);
      onSuccess(response.data);
    } else {
      console.error('Erreur serveur:', response.data);
      setErrors([response.data.message || 'Erreur lors du dépôt']);
    }
  } catch (error) {
    console.error('Erreur upload:', error);
    
    if (error.response?.status === 422) {
      // Erreurs de validation
      const validationErrors = error.response.data.errors;
      const errorMessages = [];
      
      Object.keys(validationErrors).forEach(field => {
        validationErrors[field].forEach(message => {
          errorMessages.push(message);
        });
      });
      
      setErrors(errorMessages);
    } else {
      const errorMessage = error.response?.data?.message || 'Erreur lors du dépôt des documents';
      setErrors([errorMessage]);
    }
  } finally {
    setUploading(false);
  }
};

  if (!isOpen) return null;

  return (
    <div className="document-upload-modal">
      <div className="document-upload-modal__overlay" onClick={onClose}></div>
      <div className="document-upload-modal__content">
        
        {/* En-tête */}
        <div className="document-upload-modal__header">
          <h2>Déposer des documents</h2>
          <button 
            className="document-upload-modal__close"
            onClick={onClose}
            disabled={uploading}
          >
            ×
          </button>
        </div>

        {/* Corps du modal */}
        <div className="document-upload-modal__body">
          
          {/* Zone de sélection de fichiers */}
          <div className="file-selection">
            <div 
              className="file-selection__dropzone"
              onClick={() => fileInputRef.current?.click()}
            >
              <div className="file-selection__icon">📁</div>
              <h3>Sélectionnez vos documents</h3>
              <p>
                Cliquez ici ou glissez-déposez vos fichiers<br/>
                Maximum {limites.max_fichiers} fichiers - {limites.taille_max_mo}MB chacun
              </p>
              <div className="file-selection__formats">
                Formats acceptés: {limites.extensions_autorisees.join(', ').toUpperCase()}
              </div>
            </div>
            
            <input
              ref={fileInputRef}
              type="file"
              multiple
              accept={limites.extensions_autorisees.map(ext => `.${ext}`).join(',')}
              onChange={handleFileSelect}
              style={{ display: 'none' }}
              disabled={uploading}
            />
          </div>

          {/* Liste des fichiers sélectionnés */}
          {files.length > 0 && (
            <div className="files-list">
              <h3>Fichiers sélectionnés ({files.length}/{limites.max_fichiers})</h3>
              
              {files.map((fileObj) => (
                <div key={fileObj.id} className="file-item">
                  
                  {/* En-tête du fichier */}
                  <div className="file-item__header">
                    <div className="file-item__info">
                      <span className="file-item__icon">
                        {documentService.utils.getFileIcon(fileObj.extension)}
                      </span>
                      <div className="file-item__details">
                        <span className="file-item__name">{fileObj.name}</span>
                        <span className="file-item__size">
                          {documentService.utils.formatFileSize(fileObj.size)}
                        </span>
                      </div>
                    </div>
                    <button 
                      className="file-item__remove"
                      onClick={() => removeFile(fileObj.id)}
                      disabled={uploading}
                    >
                      ×
                    </button>
                  </div>

                  {/* Métadonnées du fichier */}
                  <div className="file-item__metadata">
                    
                    {/* Type de document */}
                    <div className="metadata-field">
                      <label>Type de document</label>
                      <select 
                        value={fileObj.type}
                        onChange={(e) => updateFileMetadata(fileObj.id, 'type', e.target.value)}
                        disabled={uploading}
                      >
                        <option value="autre">Autre document</option>
                        <option value="certificat_vie">Certificat de vie</option>
                      </select>
                    </div>

                    {/* Champs spécifiques selon le type */}
                    {fileObj.type === 'certificat_vie' ? (
                      <>
                        <div className="metadata-field">
                          <label>Date d'émission *</label>
                          <input
                            type="date"
                            value={fileObj.dateEmission}
                            onChange={(e) => updateFileMetadata(fileObj.id, 'dateEmission', e.target.value)}
                            disabled={uploading}
                            required
                          />
                        </div>
                        <div className="metadata-field">
                          <label>Autorité d'émission *</label>
                          <input
                            type="text"
                            placeholder="Ex: Mairie de Libreville, Préfecture..."
                            value={fileObj.autoriteEmission}
                            onChange={(e) => updateFileMetadata(fileObj.id, 'autoriteEmission', e.target.value)}
                            disabled={uploading}
                            required
                          />
                        </div>
                      </>
                    ) : (
                      <div className="metadata-field">
                        <label>Description du document *</label>
                        <input
                          type="text"
                          placeholder="Précisez le type de document..."
                          value={fileObj.description}
                          onChange={(e) => updateFileMetadata(fileObj.id, 'description', e.target.value)}
                          disabled={uploading}
                          required
                        />
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}

          {/* Erreurs */}
          {errors.length > 0 && (
            <div className="upload-errors">
              <h4>Erreurs détectées:</h4>
              <ul>
                {errors.map((error, index) => (
                  <li key={index}>{error}</li>
                ))}
              </ul>
            </div>
          )}

        </div>

        {/* Pied du modal */}
        <div className="document-upload-modal__footer">
          <button 
            className="btn btn--secondary"
            onClick={onClose}
            disabled={uploading}
          >
            Annuler
          </button>
          <button 
            className="btn btn--primary"
            onClick={handleUpload}
            disabled={uploading || files.length === 0}
          >
            {uploading ? (
              <>
                <span className="spinner"></span>
                Envoi en cours...
              </>
            ) : (
              `Déposer ${files.length} document${files.length > 1 ? 's' : ''}`
            )}
          </button>
        </div>

      </div>
    </div>
  );
};

export default DocumentUpload;