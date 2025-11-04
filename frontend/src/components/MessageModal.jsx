import { useState, useEffect, useRef } from 'react';
import messageApi from '../services/messageApi';
import './MessageModal.css';

const MessageModal = ({ onClose, initialUnreadCount }) => {
  const [view, setView] = useState('list'); // 'list', 'conversation', 'new'
  const [conversations, setConversations] = useState([]);
  const [selectedConversation, setSelectedConversation] = useState(null);
  const [messages, setMessages] = useState([]);
  const [templates, setTemplates] = useState([]);
  const [loading, setLoading] = useState(false);
  const [sending, setSending] = useState(false);
  
  // États pour nouveau message
  const [newMessage, setNewMessage] = useState('');
  const [newSubject, setNewSubject] = useState('');
  const [newCategory, setNewCategory] = useState('');
  const [selectedTemplate, setSelectedTemplate] = useState(null);
  const [attachments, setAttachments] = useState([]);
  
  // ✅ NOUVEAU - États pour édition/suppression
  const [editingMessageId, setEditingMessageId] = useState(null);
  const [editingMessageText, setEditingMessageText] = useState('');
  
  const messagesEndRef = useRef(null);
  const fileInputRef = useRef(null);

  // Charger les conversations
  const loadConversations = async () => {
    try {
      setLoading(true);
      const response = await messageApi.getConversations();
      if (response.success) {
        setConversations(response.conversations);
      }
    } catch (error) {
      console.error('Erreur chargement conversations:', error);
    } finally {
      setLoading(false);
    }
  };

  // Charger une conversation
  const loadConversation = async (conversationId) => {
    try {
      setLoading(true);
      const response = await messageApi.getConversation(conversationId);
      if (response.success) {
        setSelectedConversation(response.conversation);
        setMessages(response.messages);
        setView('conversation');
        scrollToBottom();
      }
    } catch (error) {
      console.error('Erreur chargement conversation:', error);
    } finally {
      setLoading(false);
    }
  };

  // Charger les templates
  const loadTemplates = async () => {
    try {
      const response = await messageApi.getTemplates();
      if (response.success) {
        setTemplates(response.templates);
      }
    } catch (error) {
      console.error('Erreur chargement templates:', error);
    }
  };

  // ✅ NOUVEAU - Fonction pour vérifier si on peut éditer (moins de 15min)
  const canEditMessage = (msg) => {
    if (!msg.is_own) return false;
    const messageTime = new Date(msg.created_at.replace(' ', 'T'));
    const now = new Date();
    const diffMinutes = (now - messageTime) / 1000 / 60;
    return diffMinutes <= 15;
  };

  // ✅ NOUVEAU - Éditer un message
  const handleEditMessage = (msg) => {
    setEditingMessageId(msg.id);
    setEditingMessageText(msg.message);
  };

  // ✅ NOUVEAU - Sauvegarder la modification
  const handleSaveEdit = async () => {
    if (!editingMessageText.trim()) return;
    
    try {
      setSending(true);
      const response = await messageApi.updateMessage(editingMessageId, {
        message: editingMessageText,
      });
      
      if (response.success) {
        setMessages(messages.map(m => 
          m.id === editingMessageId 
            ? { 
                ...m, 
                message: response.data.message, 
                is_edited: true,
                edited_at: response.data.edited_at 
              }
            : m
        ));
        setEditingMessageId(null);
        setEditingMessageText('');
      } else {
        alert(response.message || 'Erreur lors de la modification');
      }
    } catch (error) {
      console.error('Erreur modification:', error);
      alert('Erreur lors de la modification du message');
    } finally {
      setSending(false);
    }
  };

  // ✅ NOUVEAU - Annuler l'édition
  const handleCancelEdit = () => {
    setEditingMessageId(null);
    setEditingMessageText('');
  };

  // ✅ NOUVEAU - Supprimer un message
  const handleDeleteMessage = async (messageId) => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) return;
    
    try {
      setSending(true);
      const response = await messageApi.deleteMessage(messageId);
      
      if (response.success) {
        setMessages(messages.filter(m => m.id !== messageId));
      } else {
        alert(response.message || 'Erreur lors de la suppression');
      }
    } catch (error) {
      console.error('Erreur suppression:', error);
      alert('Erreur lors de la suppression du message');
    } finally {
      setSending(false);
    }
  };

  useEffect(() => {
    loadConversations();
    loadTemplates();
  }, []);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    if (view === 'conversation') {
      scrollToBottom();
    }
  }, [messages, view]);

  // Envoyer un message
  const handleSendMessage = async () => {
    if (!newMessage.trim()) return;

    try {
      setSending(true);
      const response = await messageApi.sendMessage(selectedConversation.id, {
        message: newMessage,
        template_code: selectedTemplate?.code,
        attachments: attachments,
      });

      if (response.success) {
        setMessages([...messages, {
          ...response.data,
          is_own: true,
        }]);
        setNewMessage('');
        setSelectedTemplate(null);
        setAttachments([]);
        scrollToBottom();
      }
    } catch (error) {
      console.error('Erreur envoi message:', error);
      alert('Erreur lors de l\'envoi du message');
    } finally {
      setSending(false);
    }
  };

  // Créer une nouvelle conversation
  const handleCreateConversation = async () => {
    if (!newSubject.trim() || !newMessage.trim()) {
      alert('Veuillez remplir le sujet et le message');
      return;
    }

    try {
      setSending(true);
      const response = await messageApi.createConversation({
        sujet: newSubject,
        message: newMessage,
        categorie: newCategory,
        template_code: selectedTemplate?.code,
        attachments: attachments,
      });

      if (response.success) {
        alert('Conversation créée avec succès !');
        setNewSubject('');
        setNewMessage('');
        setNewCategory('');
        setSelectedTemplate(null);
        setAttachments([]);
        setView('list');
        loadConversations();
      }
    } catch (error) {
      console.error('Erreur création conversation:', error);
      alert('Erreur lors de la création de la conversation');
    } finally {
      setSending(false);
    }
  };

  // Utiliser un template
  const handleUseTemplate = (template) => {
    setSelectedTemplate(template);
    setNewMessage(template.contenu);
    if (view === 'new' && !newSubject) {
      setNewSubject(template.titre);
    }
  };

  // Gérer les fichiers
  const handleFileSelect = (e) => {
    const files = Array.from(e.target.files);
    if (files.length + attachments.length > 5) {
      alert('Maximum 5 fichiers autorisés');
      return;
    }
    setAttachments([...attachments, ...files]);
  };

  const removeAttachment = (index) => {
    setAttachments(attachments.filter((_, i) => i !== index));
  };

  // Formater la taille des fichiers
  const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  };

  return (
    <div className="message-modal-overlay" onClick={onClose}>
      <div className="message-modal" onClick={(e) => e.stopPropagation()}>
        
        {/* Header */}
        <div className="message-modal__header">
          <div className="message-modal__header-content">
            {view === 'conversation' && (
              <button 
                className="message-modal__back-btn"
                onClick={() => {
                  setView('list');
                  setSelectedConversation(null);
                  setMessages([]);
                  loadConversations();
                }}
              >
                ← Retour
              </button>
            )}
            <h2 className="message-modal__title">
              {view === 'list' && '💬 Mes Conversations'}
              {view === 'conversation' && `💬 ${selectedConversation?.sujet}`}
              {view === 'new' && '✉️ Nouvelle Conversation'}
            </h2>
          </div>
          <button className="message-modal__close-btn" onClick={onClose}>
            ✕
          </button>
        </div>

        {/* Body */}
        <div className="message-modal__body">
          
          {/* Liste des conversations */}
          {view === 'list' && (
            <div className="message-conversations">
              <button 
                className="message-new-btn"
                onClick={() => setView('new')}
              >
                ✉️ Nouvelle conversation
              </button>

              {loading ? (
                <div className="message-loading">Chargement...</div>
              ) : conversations.length === 0 ? (
                <div className="message-empty">
                  <div className="message-empty__icon">💬</div>
                  <p>Aucune conversation</p>
                  <p className="message-empty__subtitle">
                    Créez une nouvelle conversation pour commencer
                  </p>
                </div>
              ) : (
                <div className="message-conversation-list">
                  {conversations.map((conv) => (
                    <div
                      key={conv.id}
                      className={`message-conversation-item ${conv.unread_count > 0 ? 'message-conversation-item--unread' : ''}`}
                      onClick={() => loadConversation(conv.id)}
                    >
                      <div className="message-conversation-item__header">
                        <div>
                          <span className="message-conversation-item__ticket">
                            🎫 {conv.numero_ticket}
                          </span>
                          <span className="message-conversation-item__subject">
                            {conv.sujet}
                          </span>
                        </div>
                        {conv.unread_count > 0 && (
                          <span className="message-conversation-item__badge">
                            {conv.unread_count}
                          </span>
                        )}
                      </div>
                      
                      <div className="message-conversation-item__meta">
                        <span className={`message-status message-status--${conv.statut_badge.color}`}>
                          {conv.statut_badge.text}
                        </span>
                        <span className="message-conversation-item__date">
                          {conv.dernier_message?.formatted_time || conv.created_at}
                        </span>
                      </div>

                      {conv.dernier_message && (
                        <p className="message-conversation-item__preview">
                          {conv.dernier_message.message}
                        </p>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {/* Vue d'une conversation */}
          {view === 'conversation' && (
            <div className="message-chat">
              {/* Info conversation */}
              <div className="message-chat__info">
                <div className="message-chat__info-item">
                  <strong>Ticket:</strong> {selectedConversation?.numero_ticket}
                </div>
                <div className="message-chat__info-item">
                  <span className={`message-status message-status--${selectedConversation?.statut_badge.color}`}>
                    {selectedConversation?.statut_badge.text}
                  </span>
                  <span className={`message-priority message-priority--${selectedConversation?.priorite_badge.color}`}>
                    {selectedConversation?.priorite_badge.icon} {selectedConversation?.priorite_badge.text}
                  </span>
                </div>
              </div>

              {/* ✅ SECTION MESSAGES MODIFIÉE */}
              <div className="message-chat__messages">
                {messages.map((msg) => (
                  <div 
                    key={msg.id}
                    className={`message-bubble ${msg.is_own ? 'message-bubble--own' : 'message-bubble--other'}`}
                  >
                    {!msg.is_own && (
                      <div className="message-bubble__sender">
                        {msg.sender_type === 'admin' ? 'Administrateur CPPF' : msg.sender_name}
                      </div>
                    )}
                    
                    {/* Mode édition */}
                    {editingMessageId === msg.id ? (
                      <div className="message-bubble__edit">
                        <textarea
                          value={editingMessageText}
                          onChange={(e) => setEditingMessageText(e.target.value)}
                          className="message-edit-textarea"
                          rows="3"
                          disabled={sending}
                        />
                        <div className="message-edit-actions">
                          <button 
                            onClick={handleSaveEdit} 
                            className="btn-save-edit"
                            disabled={sending || !editingMessageText.trim()}
                          >
                            ✓ Enregistrer
                          </button>
                          <button 
                            onClick={handleCancelEdit} 
                            className="btn-cancel-edit"
                            disabled={sending}
                          >
                            ✕ Annuler
                          </button>
                        </div>
                      </div>
                    ) : (
                      <>
                        {/* Contenu du message */}
                        <div className="message-bubble__content">
                          {msg.message}
                          {msg.is_edited && (
                            <span className="message-edited-badge"> (modifié)</span>
                          )}
                        </div>
                        
                        {/* Pièces jointes */}
                        {msg.attachments && msg.attachments.length > 0 && (
                          <div className="message-bubble__attachments">
                            {msg.attachments.map((file, idx) => (
                              <a 
                                key={idx}
                                href={file.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="message-attachment"
                              >
                                <span>{file.icon}</span>
                                <span>{file.name}</span>
                                <span className="message-attachment__size">{file.size}</span>
                              </a>
                            ))}
                          </div>
                        )}
                      </>
                    )}
                    
                    {/* Actions + Time */}
      <div className="message-bubble__footer">
        <div className="message-bubble__time">
          {msg.formatted_time}
          {msg.is_own && (
            <span className="message-status-icon">
              {msg.is_read ? '✓✓ Lu' : '✓ Envoyé'}
            </span>
          )}
        </div>
                      
                      {/* Boutons éditer/supprimer */}
                      {msg.is_own && canEditMessage(msg) && editingMessageId !== msg.id && (
                        <div className="message-bubble__actions">
                          <button 
                            onClick={() => handleEditMessage(msg)}
                            className="message-action-btn"
                            title="Modifier"
                            disabled={sending}
                          >
                            ✏️
                          </button>
                          <button 
                            onClick={() => handleDeleteMessage(msg.id)}
                            className="message-action-btn message-action-btn--delete"
                            title="Supprimer"
                            disabled={sending}
                          >
                            🗑️
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                ))}
                <div ref={messagesEndRef} />
              </div>

              {/* Input message */}
              {selectedConversation?.statut !== 'ferme' && (
                <div className="message-chat__input">
                  {/* Templates rapides */}
                  {templates.length > 0 && (
                    <div className="message-templates-quick">
                      {templates.slice(0, 3).map((template) => (
                        <button
                          key={template.id}
                          className="message-template-quick-btn"
                          onClick={() => handleUseTemplate(template)}
                          title={template.titre}
                        >
                          {template.icon} {template.titre}
                        </button>
                      ))}
                    </div>
                  )}

                  {/* Pièces jointes */}
                  {attachments.length > 0 && (
                    <div className="message-attachments-preview">
                      {attachments.map((file, idx) => (
                        <div key={idx} className="message-attachment-preview">
                          <span>📎 {file.name}</span>
                          <button onClick={() => removeAttachment(idx)}>✕</button>
                        </div>
                      ))}
                    </div>
                  )}

                  <div className="message-input-wrapper">
                    <input
                      type="file"
                      ref={fileInputRef}
                      onChange={handleFileSelect}
                      multiple
                      accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                      style={{ display: 'none' }}
                    />
                    <button
                      className="message-attach-btn"
                      onClick={() => fileInputRef.current?.click()}
                      title="Joindre un fichier"
                    >
                      📎
                    </button>
                    <textarea
                      className="message-input"
                      placeholder="Votre message..."
                      value={newMessage}
                      onChange={(e) => setNewMessage(e.target.value)}
                      onKeyDown={(e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                          e.preventDefault();
                          handleSendMessage();
                        }
                      }}
                      rows={3}
                    />
                    <button
                      className="message-send-btn"
                      onClick={handleSendMessage}
                      disabled={sending || !newMessage.trim()}
                    >
                      {sending ? '⏳' : '📤'}
                    </button>
                  </div>
                </div>
              )}

              {selectedConversation?.statut === 'ferme' && (
                <div className="message-chat__closed">
                  🔒 Cette conversation est fermée
                </div>
              )}
            </div>
          )}

          {/* Nouvelle conversation */}
          {view === 'new' && (
            <div className="message-new">
              <div className="message-new__form">
                
                {/* Templates */}
                {templates.length > 0 && (
                  <div className="message-templates">
                    <label>Questions fréquentes :</label>
                    <div className="message-templates-grid">
                      {templates.map((template) => (
                        <button
                          key={template.id}
                          className={`message-template-btn ${selectedTemplate?.id === template.id ? 'message-template-btn--active' : ''}`}
                          onClick={() => handleUseTemplate(template)}
                        >
                          <span className="message-template-btn__icon">{template.icon}</span>
                          <span className="message-template-btn__text">{template.titre}</span>
                        </button>
                      ))}
                    </div>
                  </div>
                )}

                <div className="message-form-group">
                  <label>Sujet *</label>
                  <input
                    type="text"
                    className="message-input-text"
                    placeholder="Sujet de votre message"
                    value={newSubject}
                    onChange={(e) => setNewSubject(e.target.value)}
                  />
                </div>

                <div className="message-form-group">
                  <label>Catégorie</label>
                  <select
                    className="message-input-select"
                    value={newCategory}
                    onChange={(e) => setNewCategory(e.target.value)}
                  >
                    <option value="">Sélectionner...</option>
                    <option value="question">Question</option>
                    <option value="reclamation">Réclamation</option>
                    <option value="demande">Demande</option>
                    <option value="autre">Autre</option>
                  </select>
                </div>

                <div className="message-form-group">
                  <label>Message *</label>
                  <textarea
                    className="message-input-textarea"
                    placeholder="Décrivez votre demande..."
                    value={newMessage}
                    onChange={(e) => setNewMessage(e.target.value)}
                    rows={6}
                  />
                </div>

                {/* Pièces jointes */}
                <div className="message-form-group">
                  <label>Pièces jointes (max 5 fichiers)</label>
                  <input
                    type="file"
                    ref={fileInputRef}
                    onChange={handleFileSelect}
                    multiple
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                    style={{ display: 'none' }}
                  />
                  <button
                    className="message-attach-file-btn"
                    onClick={() => fileInputRef.current?.click()}
                  >
                    📎 Ajouter des fichiers
                  </button>
                  
                  {attachments.length > 0 && (
                    <div className="message-attachments-list">
                      {attachments.map((file, idx) => (
                        <div key={idx} className="message-attachment-item">
                          <span>📄 {file.name} ({formatFileSize(file.size)})</span>
                          <button onClick={() => removeAttachment(idx)}>✕</button>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                <div className="message-form-actions">
                  <button
                    className="message-btn message-btn--secondary"
                    onClick={() => setView('list')}
                  >
                    Annuler
                  </button>
                  <button
                    className="message-btn message-btn--primary"
                    onClick={handleCreateConversation}
                    disabled={sending || !newSubject.trim() || !newMessage.trim()}
                  >
                    {sending ? 'Envoi...' : 'Envoyer'}
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Notice de confidentialité */}
        <div className="message-modal__privacy-notice">
          <p>
            🔒 <strong>Confidentialité:</strong> Vos conversations sont personnelles et sécurisées. 
            Elles sont uniquement accessibles par vous et les administrateurs du système.
          </p>
        </div>
      </div>
    </div>
  );
};

export default MessageModal;