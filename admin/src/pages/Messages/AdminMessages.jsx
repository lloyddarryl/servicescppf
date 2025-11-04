import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import AdminHeader from '../../components/AdminHeader';
import AdminNav from '../../components/AdminNav';
import adminMessageApi from '../../services/adminMessageApi';
import './AdminMessages.css';

const AdminMessages = () => {
  const navigate = useNavigate();
  const [view, setView] = useState('list'); // 'list', 'conversation', 'group'
  const [conversations, setConversations] = useState([]);
  const [selectedConversation, setSelectedConversation] = useState(null);
  const [messages, setMessages] = useState([]);
  const [templates, setTemplates] = useState([]);
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(false);
  const [sending, setSending] = useState(false);

  // ✅ NOUVEAU - États pour édition/suppression
  const [editingMessageId, setEditingMessageId] = useState(null);
  const [editingMessageText, setEditingMessageText] = useState('');

  // Filtres
  const [filters, setFilters] = useState({
    statut: '',
    priorite: '',
    categorie: '',
    search: '',
  });

  // Message
  const [newMessage, setNewMessage] = useState('');
  const [selectedTemplate, setSelectedTemplate] = useState(null);

  // Message groupé
  const [groupMessage, setGroupMessage] = useState({
    destinataires: [],
    sujet: '',
    message: '',
    categorie: '',
    priorite: 'normale',
  });
  const [searchUsers, setSearchUsers] = useState('');
  const [searchResults, setSearchResults] = useState([]);

  // Charger les conversations
  const loadConversations = useCallback(async () => {
    try {
      setLoading(true);
      console.log('🔄 Chargement conversations avec filtres:', filters);
      const response = await adminMessageApi.getConversations(filters);
      console.log('✅ Réponse API conversations:', response);
      if (response.success) {
        setConversations(response.conversations);
        setStats(response.stats);
        console.log(`📊 ${response.conversations.length} conversations chargées`);
      } else {
        console.error('❌ Échec chargement:', response);
      }
    } catch (error) {
      console.error('❌ Erreur chargement conversations:', error);
      console.error('Details:', error.response?.data);
    } finally {
      setLoading(false);
    }
  }, [filters]);

  // Charger une conversation
  const loadConversation = useCallback(async (conversationId) => {
    try {
      setLoading(true);
      const response = await adminMessageApi.getConversation(conversationId);
      if (response.success) {
        setSelectedConversation(response.conversation);
        setMessages(response.messages);
        setView('conversation');
      }
    } catch (error) {
      console.error('Erreur chargement conversation:', error);
    } finally {
      setLoading(false);
    }
  }, []);

  // Charger les templates
  const loadTemplates = useCallback(async () => {
    try {
      const response = await adminMessageApi.getTemplates();
      if (response.success) {
        setTemplates(response.templates);
      }
    } catch (error) {
      console.error('Erreur chargement templates:', error);
    }
  }, []);

  // ✅ NOUVEAU - Fonction pour vérifier si on peut éditer
  const canEditMessage = (msg) => {
    if (!msg.is_admin) return false;
    return true; // Admin peut toujours éditer ses messages
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
      const response = await adminMessageApi.updateMessage(editingMessageId, {
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
      const response = await adminMessageApi.deleteMessage(messageId);
      
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
  }, [loadConversations, loadTemplates]);

  useEffect(() => {
    if (view === 'list') {
      loadConversations();
    }
  }, [view, loadConversations]);

  // Envoyer un message
  const handleSendMessage = async () => {
    if (!newMessage.trim()) return;

    try {
      setSending(true);
      const response = await adminMessageApi.sendMessage(selectedConversation.id, {
        message: newMessage,
        template_code: selectedTemplate?.code,
      });

      if (response.success) {
        setMessages([...messages, {
          ...response.data,
          is_admin: true,
        }]);
        setNewMessage('');
        setSelectedTemplate(null);
      }
    } catch (error) {
      console.error('Erreur envoi message:', error);
      alert('Erreur lors de l\'envoi du message');
    } finally {
      setSending(false);
    }
  };

  // Mettre à jour le statut
  const handleUpdateStatus = async (status) => {
    try {
      const response = await adminMessageApi.updateConversation(selectedConversation.id, {
        statut: status,
      });

      if (response.success) {
        setSelectedConversation({
          ...selectedConversation,
          statut: status,
        });
        alert('Statut mis à jour avec succès');
      }
    } catch (error) {
      console.error('Erreur mise à jour statut:', error);
      alert('Erreur lors de la mise à jour');
    }
  };

  // Rechercher des utilisateurs
  const handleSearchUsers = async (search) => {
    if (search.length < 2) {
      setSearchResults([]);
      return;
    }

    try {
      const response = await adminMessageApi.searchUsers(search);
      if (response.success) {
        setSearchResults(response.users);
      }
    } catch (error) {
      console.error('Erreur recherche utilisateurs:', error);
    }
  };

  // Ajouter destinataire
  const addDestinataire = (user) => {
    if (!groupMessage.destinataires.includes(user.id)) {
      setGroupMessage({
        ...groupMessage,
        destinataires: [...groupMessage.destinataires, user.id],
      });
    }
    setSearchUsers('');
    setSearchResults([]);
  };

  // Retirer destinataire
  const removeDestinataire = (userId) => {
    setGroupMessage({
      ...groupMessage,
      destinataires: groupMessage.destinataires.filter(id => id !== userId),
    });
  };

  // Envoyer message groupé
  const handleSendGroupMessage = async () => {
    if (!groupMessage.sujet || !groupMessage.message || groupMessage.destinataires.length === 0) {
      alert('Veuillez remplir tous les champs et sélectionner au moins un destinataire');
      return;
    }

    try {
      setSending(true);
      const response = await adminMessageApi.createGroupMessage(groupMessage);

      if (response.success) {
        alert(`Message envoyé avec succès à ${groupMessage.destinataires.length} utilisateur(s)`);
        setGroupMessage({
          destinataires: [],
          sujet: '',
          message: '',
          categorie: '',
          priorite: 'normale',
        });
        setView('list');
      }
    } catch (error) {
      console.error('Erreur envoi message groupé:', error);
      alert('Erreur lors de l\'envoi du message groupé');
    } finally {
      setSending(false);
    }
  };

  return (
    <div className="admin-dashboard">
      <AdminHeader 
        title="Messagerie" 
        breadcrumb="Gestion des conversations"
      />
      <AdminNav />

      <main className="admin-main">
        <div className="admin-messages">

          {/* Liste des conversations */}
          {view === 'list' && (
            <>
             {/* En-tête avec stats */}
          {stats && view === 'list' && (
            <div className="admin-messages__stats">
              <div className="stat-card">
                <span className="stat-label">Total</span>
                <span className="stat-value">{stats.total}</span>
              </div>
              <div className="stat-card stat-card--blue">
                <span className="stat-label">Ouverts</span>
                <span className="stat-value">{stats.ouverts}</span>
              </div>
              <div className="stat-card stat-card--yellow">
                <span className="stat-label">En cours</span>
                <span className="stat-value">{stats.en_cours}</span>
              </div>
              <div className="stat-card stat-card--green">
                <span className="stat-label">Résolus</span>
                <span className="stat-value">{stats.resolus}</span>
              </div>
              <div className="stat-card stat-card--red">
                <span className="stat-label">Urgents</span>
                <span className="stat-value">{stats.urgents}</span>
              </div>
              <div className="stat-card stat-card--purple">
                <span className="stat-label">Non lus</span>
                <span className="stat-value">{stats.messages_non_lus}</span>
              </div>
            </div>
          )}

              {/* Actions */}
              <div className="admin-messages__actions">
                <button 
                  className="admin-btn admin-btn--primary"
                  onClick={() => setView('group')}
                >
                  📢 Message groupé
                </button>
              </div>

              {/* Filtres */}
              <div className="admin-messages__filters">
                <input
                  type="text"
                  className="admin-input"
                  placeholder="🔍 Rechercher..."
                  value={filters.search}
                  onChange={(e) => setFilters({...filters, search: e.target.value})}
                />
                <select
                  className="admin-select"
                  value={filters.statut}
                  onChange={(e) => setFilters({...filters, statut: e.target.value})}
                >
                  <option value="">Tous les statuts</option>
                  <option value="en_attente">En attente</option>
                  <option value="en_cours">En cours</option>
                  <option value="resolu">Résolu</option>
                  <option value="ferme">Fermé</option>
                </select>
                <select
                  className="admin-select"
                  value={filters.priorite}
                  onChange={(e) => setFilters({...filters, priorite: e.target.value})}
                >
                  <option value="">Toutes les priorités</option>
                  <option value="basse">Basse</option>
                  <option value="normale">Normale</option>
                  <option value="haute">Haute</option>
                  <option value="urgente">Urgente</option>
                </select>
              </div>

              {/* Conversations */}
              {loading ? (
                <div className="admin-loading">Chargement...</div>
              ) : conversations.length === 0 ? (
                <div className="admin-empty">
                  <div className="admin-empty__icon">💬</div>
                  <p>Aucune conversation</p>
                </div>
              ) : (
                <div className="conversations-grid">
                  {conversations.map((conv) => (
                    <div
                      key={conv.id}
                      className={`conversation-card ${conv.unread_count > 0 ? 'conversation-card--unread' : ''}`}
                      onClick={() => loadConversation(conv.id)}
                    >
                      <div className="conversation-card__header">
                        <span className="conversation-card__ticket">
                          {conv.numero_ticket}
                        </span>
                        {conv.unread_count > 0 && (
                          <span className="conversation-card__badge">
                            {conv.unread_count}
                          </span>
                        )}
                      </div>

                      <h3 className="conversation-card__title">{conv.sujet}</h3>

                      <div className="conversation-card__meta">
                        <div className="conversation-card__status">
                          <span className={`status-badge status-badge--${conv.statut_badge.color}`}>
                            {conv.statut_badge.text}
                          </span>
                          <span className={`priority-badge priority-badge--${conv.priorite_badge.color}`}>
                            {conv.priorite_badge.icon} {conv.priorite_badge.text}
                          </span>
                        </div>
                      </div>

                      {conv.dernier_message && (
                        <p className="conversation-card__preview">
                          {conv.dernier_message.message}
                        </p>
                      )}

                      <div className="conversation-card__footer">
                        👤 {conv.user_name} • 📅 {conv.dernier_message?.formatted_time || conv.created_at}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </>
          )}

          {/* Vue d'une conversation */}
          {view === 'conversation' && selectedConversation && (
            <div className="admin-conversation">
              <div className="admin-conversation__header">
                <button 
                  className="admin-btn admin-btn--back"
                  onClick={() => setView('list')}
                >
                  ← Retour
                </button>
                <h3>{selectedConversation.sujet}</h3>
              </div>

              {/* Info conversation */}
              <div className="admin-conversation__info">
                <div className="info-row">
                  <div className="info-item">
                    <strong>Ticket:</strong> {selectedConversation.numero_ticket}
                  </div>
                  <div className="info-item">
                    <strong>Utilisateur:</strong> {selectedConversation.user_name}
                  </div>
                  <div className="info-item">
                    <strong>Créé:</strong> {selectedConversation.created_at}
                  </div>
                </div>

                <div className="info-actions">
                  <button 
                    className="admin-btn admin-btn--sm admin-btn--secondary"
                    onClick={() => handleUpdateStatus('en_attente')}
                    disabled={selectedConversation.statut === 'en_attente'}
                  >
                    ⏸️ En attente
                  </button>
                  <button 
                    className="admin-btn admin-btn--sm admin-btn--primary"
                    onClick={() => handleUpdateStatus('en_cours')}
                    disabled={selectedConversation.statut === 'en_cours'}
                  >
                    ⏳ En cours
                  </button>
                  <button 
                    className="admin-btn admin-btn--sm admin-btn--success"
                    onClick={() => handleUpdateStatus('resolu')}
                    disabled={selectedConversation.statut === 'resolu'}
                  >
                    ✅ Résolu
                  </button>
                  <button 
                    className="admin-btn admin-btn--sm admin-btn--danger"
                    onClick={() => handleUpdateStatus('ferme')}
                    disabled={selectedConversation.statut === 'ferme'}
                  >
                    🔒 Fermer
                  </button>
                </div>
              </div>

              {/* ✅ SECTION MESSAGES */}
              <div className="admin-conversation__messages">
                {messages.map((msg) => (
                  <div 
                    key={msg.id}
                    className={`admin-message-bubble ${msg.is_admin ? 'admin-message-bubble--admin' : 'admin-message-bubble--user'}`}
                  >
                   {/* Sender name and identifier */}
            {!msg.is_system && (
                <div className="admin-message-bubble__sender">
                    {msg.sender_name}
                    {msg.sender_identifier && (
                        <span className="admin-message-bubble__identifier">
                            {" "}({msg.sender_identifier})
                        </span>
                    )}
                </div>
            )}
                    
                    {/* Mode édition */}
                    {editingMessageId === msg.id ? (
                      <div className="admin-message-bubble__edit">
                        <textarea
                          value={editingMessageText}
                          onChange={(e) => setEditingMessageText(e.target.value)}
                          className="admin-message-edit-textarea"
                          rows="3"
                          disabled={sending}
                        />
                        <div className="admin-message-edit-actions">
                          <button 
                            onClick={handleSaveEdit} 
                            className="admin-btn-save-edit"
                            disabled={sending || !editingMessageText.trim()}
                          >
                            ✓ Enregistrer
                          </button>
                          <button 
                            onClick={handleCancelEdit} 
                            className="admin-btn-cancel-edit"
                            disabled={sending}
                          >
                            ✕ Annuler
                          </button>
                        </div>
                      </div>
                    ) : (
                      <>
                        {/* Contenu du message */}
                        <div className="admin-message-bubble__content">
                          {msg.message}
                          {msg.is_edited && (
                            <span className="admin-message-edited-badge"> (modifié)</span>
                          )}
                        </div>
                        
                        {/* Pièces jointes */}
                        {msg.attachments && msg.attachments.length > 0 && (
                          <div className="admin-message-bubble__attachments">
                            {msg.attachments.map((file, idx) => (
                              <a 
                                key={idx}
                                href={file.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="admin-attachment"
                              >
                                {file.icon} {file.name}
                              </a>
                            ))}
                          </div>
                        )}
                      </>
                    )}
                    
                    {/* Footer avec time + actions */}
<div className="admin-message-bubble__footer">
  <div className="admin-message-bubble__time">
    {msg.formatted_time}
    {/* Indicateur pour TOUS les messages */}
    {msg.is_admin ? (
      // Message de l'admin : montrer si lu par l'utilisateur
      <span className="admin-message-status-icon">
        {msg.is_read ? '✓✓ Lu' : '✓ Envoyé'}
      </span>
    ) : (
      // Message de l'utilisateur : montrer "Lu" si l'admin a lu
      msg.is_read && <span className="admin-message-read-badge">✓ Lu</span>
    )}
  </div>
                      
                      {/* Boutons éditer/supprimer */}
                      {msg.is_admin && canEditMessage(msg) && editingMessageId !== msg.id && (
                        <div className="admin-message-bubble__actions">
                          <button 
                            onClick={() => handleEditMessage(msg)}
                            className="admin-message-action-btn"
                            title="Modifier"
                            disabled={sending}
                          >
                            ✏️
                          </button>
                          <button 
                            onClick={() => handleDeleteMessage(msg.id)}
                            className="admin-message-action-btn admin-message-action-btn--delete"
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
              </div>

              {/* Input réponse */}
              {selectedConversation.statut !== 'ferme' && (
                <div className="admin-conversation__input">
                  {/* Templates rapides */}
                  {templates.length > 0 && (
                    <div className="admin-templates">
                      <label>Réponses rapides:</label>
                      <div className="admin-templates-grid">
                        {templates.map((template) => (
                          <button
                            key={template.id}
                            className="admin-template-btn"
                            onClick={() => {
                              setSelectedTemplate(template);
                              setNewMessage(template.contenu);
                            }}
                          >
                            {template.icon} {template.titre}
                          </button>
                        ))}
                      </div>
                    </div>
                  )}

                  <div className="admin-input-wrapper">
                    <textarea
                      className="admin-textarea"
                      placeholder="Votre réponse..."
                      value={newMessage}
                      onChange={(e) => setNewMessage(e.target.value)}
                      rows={4}
                    />
                    <button
                      className="admin-btn admin-btn--send"
                      onClick={handleSendMessage}
                      disabled={sending || !newMessage.trim()}
                    >
                      {sending ? '⏳ Envoi...' : '📤 Envoyer'}
                    </button>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Message groupé */}
          {view === 'group' && (
            <div className="admin-group-message">
              <div className="admin-group-message__header">
                <button 
                  className="admin-btn admin-btn--back"
                  onClick={() => setView('list')}
                >
                  ← Retour
                </button>
                <h3>📢 Message Groupé</h3>
              </div>

              <div className="admin-group-message__form">
                {/* Recherche utilisateurs */}
                <div className="form-group">
                  <label>Destinataires *</label>
                  <input
                    type="text"
                    className="admin-input"
                    placeholder="Rechercher des utilisateurs..."
                    value={searchUsers}
                    onChange={(e) => {
                      setSearchUsers(e.target.value);
                      handleSearchUsers(e.target.value);
                    }}
                  />
                  {searchResults.length > 0 && (
                    <div className="search-results">
                      {searchResults.map((user) => (
                        <div 
                          key={user.id}
                          className="search-result-item"
                          onClick={() => addDestinataire(user)}
                        >
                          <div>
                            <strong>{user.nom}</strong>
                            <span className="search-result-type">{user.type}</span>
                          </div>
                          <div className="search-result-meta">
                            {user.matricule || user.numero_pension}
                          </div>
                        </div>
                      ))}
                    </div>
                  )}

                  {/* Destinataires sélectionnés */}
                  {groupMessage.destinataires.length > 0 && (
                    <div className="selected-users">
                      <p><strong>{groupMessage.destinataires.length} destinataire(s) sélectionné(s)</strong></p>
                      <div className="selected-users-list">
                        {groupMessage.destinataires.map((userId) => {
                          const user = searchResults.find(u => u.id === userId);
                          return user && (
                            <div key={userId} className="selected-user-tag">
                              <span>{user.nom}</span>
                              <button onClick={() => removeDestinataire(userId)}>✕</button>
                            </div>
                          );
                        })}
                      </div>
                    </div>
                  )}
                </div>

                <div className="form-group">
                  <label>Sujet *</label>
                  <input
                    type="text"
                    className="admin-input"
                    value={groupMessage.sujet}
                    onChange={(e) => setGroupMessage({...groupMessage, sujet: e.target.value})}
                  />
                </div>

                <div className="form-group">
                  <label>Priorité</label>
                  <select
                    className="admin-select"
                    value={groupMessage.priorite}
                    onChange={(e) => setGroupMessage({...groupMessage, priorite: e.target.value})}
                  >
                    <option value="basse">Basse</option>
                    <option value="normale">Normale</option>
                    <option value="haute">Haute</option>
                    <option value="urgente">Urgente</option>
                  </select>
                </div>

                <div className="form-group">
                  <label>Message *</label>
                  <textarea
                    className="admin-textarea"
                    value={groupMessage.message}
                    onChange={(e) => setGroupMessage({...groupMessage, message: e.target.value})}
                    rows={8}
                  />
                </div>

                <div className="form-actions">
                  <button
                    className="admin-btn admin-btn--secondary"
                    onClick={() => setView('list')}
                  >
                    Annuler
                  </button>
                  <button
                    className="admin-btn admin-btn--primary"
                    onClick={handleSendGroupMessage}
                    disabled={sending}
                  >
                    {sending ? '⏳ Envoi...' : '📤 Envoyer à tous'}
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      </main>
    </div>
  );
};

export default AdminMessages;