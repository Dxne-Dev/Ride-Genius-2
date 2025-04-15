class ChatManager {
    constructor(userId) {
        this.userId = userId;
        this.selectedUserId = null;
        this.selectedConversationId = null;
        this.api = new ChatAPI('/message_api.php', window.SOCKET_SERVER_URL);
        this.maxFileSize = 10 * 1024 * 1024;
        this.attachedFiles = [];
        this.messages = [];
        this.messagePage = 1;
        this.messagesPerPage = 20;
        this.isOnline = navigator.onLine;
        console.log('ChatManager initialisé avec userId:', userId);
        this.init();
    }

    init() {
        if (!this.userId || !window.USER_TOKEN) {
            console.error('Utilisateur non identifié ou token manquant');
            this.showNotification('Session expirée. Veuillez vous reconnecter.', 'error');
            window.location.href = 'index.php?page=login';
            return;
        }
        console.log('Initialisation de ChatManager');
        this.setupEventListeners();
        this.setupNetworkStatus();
        this.connectSocket();
        this.loadConversations();
        this.restoreActiveConversation();
    }

    connectSocket() {
        if (!this.userId || !window.USER_TOKEN) {
            console.error('Utilisateur non identifié ou token manquant');
            this.showNotification('Session expirée. Veuillez vous reconnecter.', 'error');
            window.location.href = 'index.php?page=login';
            return;
        }

        this.api.connectSocket(
            {
                userId: this.userId,
                token: window.USER_TOKEN
            },
            () => {
                console.log('Socket connecté avec succès');
                this.loadConversations();
            },
            () => {
                console.error('Déconnexion du socket');
                this.showNotification('Connexion perdue. Tentative de reconnexion...', 'warning');
            },
            (message) => {
                console.log('Message reçu via socket:', message);
                if (message.conversation_id === this.selectedConversationId) {
                    this.displayMessage(message);
                    this.markMessagesAsRead(message.conversation_id);
                } else {
                    this.updateUnreadCount(message.conversation_id);
                }
                this.loadConversations();
            },
            (reaction) => {
                console.log('Réaction reçue via socket:', reaction);
                this.updateMessageReaction(reaction);
            },
            (callData) => {
                console.log('Appel entrant reçu:', callData);
                if (callData && typeof callData === 'object') {
                    this.handleIncomingCall(callData);
                } else {
                    console.error('Données d\'appel invalides:', callData);
                }
            },
            (callAnswer) => {
                console.log('Réponse à l\'appel reçue:', callAnswer);
                if (callAnswer && callAnswer.answer === 'accepted') {
                    document.getElementById('callStatus').textContent = 'Appel accepté';
                } else {
                    document.getElementById('callModal').style.display = 'none';
                    this.stopRingtone();
                }
            },
            (callEnd) => {
                console.log('Appel terminé:', callEnd);
                document.getElementById('callModal').style.display = 'none';
                this.stopRingtone();
                this.isCalling = false;
            }
        );
    }

    setupEventListeners() {
        console.log('Configuration des écouteurs d\'événements');
        const form = document.getElementById('messageForm');
        console.log('Formulaire trouvé:', form);
        
        if (form) {
            form.addEventListener('submit', (e) => {
                console.log('Événement submit déclenché');
                this.handleSubmit(e);
            });
        } else {
            console.error('Formulaire non trouvé!');
        }

        const searchInput = document.getElementById('searchUsers');
        const fileInput = document.getElementById('fileInput');
        const attachButton = document.getElementById('attachButton');
        const audioCallBtn = document.getElementById('audioCallBtn');
        const videoCallBtn = document.getElementById('videoCallBtn');
        const chatMessages = document.getElementById('chatMessages');
        const swipeIndicator = document.querySelector('.swipe-indicator');
        const reactionMenu = document.getElementById('reactionMenu');

        if (searchInput) {
            searchInput.addEventListener('input', debounce(() => this.handleSearch(), 300));
            
            // Ajouter un gestionnaire d'événements pour fermer les résultats lors d'un clic en dehors
            document.addEventListener('click', (e) => {
                const searchBox = document.querySelector('.search-box');
                const searchResults = document.querySelector('.search-results');
                
                if (searchBox && searchResults && 
                    !searchBox.contains(e.target) && 
                    !searchResults.contains(e.target)) {
                    this.clearSearchResults();
                }
            });
        }
        
        if (fileInput) fileInput.addEventListener('change', (e) => this.handleFileUpload(e));
        if (attachButton) attachButton.addEventListener('click', () => fileInput?.click());
        if (audioCallBtn) audioCallBtn.addEventListener('click', () => this.startCall('audio'));
        if (videoCallBtn) videoCallBtn.addEventListener('click', () => this.startCall('video'));
        if (chatMessages) {
            chatMessages.addEventListener('scroll', () => this.handleScroll());
            chatMessages.addEventListener('contextmenu', (e) => this.showReactionMenu(e));
        }
        if (swipeIndicator && window.innerWidth <= 768) {
            swipeIndicator.style.display = 'block';
            swipeIndicator.addEventListener('click', () => this.toggleSidebar());
        }
        if (reactionMenu) {
            reactionMenu.querySelectorAll('.reaction-item').forEach(item => {
                item.addEventListener('click', () => this.addReaction(item.dataset.emoji));
            });
        }
    }

    setupNetworkStatus() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.updateNetworkStatus(true);
            this.flushOfflineMessages();
        });
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.updateNetworkStatus(false);
        });
    }

    updateNetworkStatus(isOnline) {
        const indicator = document.querySelector('.offline-indicator');
        if (indicator) indicator.style.display = isOnline ? 'none' : 'block';
    }

    async loadConversations() {
        try {
            const response = await this.api.apiRequest('GET', '?action=getConversations');
            if (response.success) {
                this.renderConversations(response.conversations);
                if (!response.conversations.length) {
                    this.showNotification('Aucune conversation à charger pour le moment', 'info');
                }
            } else {
                throw new Error(response.message || 'Erreur API');
            }
        } catch (error) {
            console.error('Erreur chargement conversations:', error);
            this.renderConversations(this.getCachedConversations() || []);
            if (!this.getCachedConversations().length) {
                this.showNotification('Aucune conversation à charger pour le moment', 'info');
            } else {
                this.showNotification('Erreur de chargement des conversations', 'error');
            }
        }
    }

    renderConversations(conversations) {
        const list = document.querySelector('.conversations-list');
        if (!list) return;
        list.innerHTML = '';
        if (!Array.isArray(conversations) || !conversations.length) {
            list.innerHTML = '<div class="no-conversations">Aucune conversation à charger pour le moment</div>';
            return;
        }
        localStorage.setItem('conversations', JSON.stringify(conversations));
        conversations.forEach(conv => {
            const item = document.createElement('div');
            const otherUserId = conv.user1_id === this.userId ? conv.user2_id : conv.user1_id;
            item.className = `conversation-item ${otherUserId === this.selectedUserId ? 'active' : ''}`;
            item.dataset.userId = otherUserId;
            item.dataset.conversationId = conv.id;
            item.innerHTML = `
                <div class="user-info">
                    <img src="${conv.profile_image}" alt="Avatar" class="avatar">
                    <div class="user-details">
                        <h4>${conv.other_first_name} ${conv.other_last_name}</h4>
                        <p class="last-message">${conv.last_message ? conv.last_message.slice(0, 30) + (conv.last_message.length > 30 ? '...' : '') : ''}</p>
                    </div>
                </div>
                <div class="conversation-meta">
                    ${conv.last_message_at ? `<span class="time">${new Date(conv.last_message_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}` : ''}
                    ${conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : ''}
                </div>
            `;
            item.addEventListener('click', () => this.selectConversation(otherUserId, conv.id));
            list.appendChild(item);
        });
    }

    async selectConversation(userId, conversationId) {
        try {
            this.selectedUserId = userId;
            this.selectedConversationId = conversationId;
            localStorage.setItem('activeConversationId', conversationId);
            
            // Réinitialiser le numéro de page et les messages
            this.messagePage = 1;
            this.messages = [];
            
            // Mettre à jour l'interface utilisateur
            document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
            const selectedConv = document.querySelector(`.conversation-item[data-conversation-id="${conversationId}"]`);
            if (selectedConv) {
                selectedConv.classList.add('active');
                const userInfo = document.querySelector('.selected-user-info .user-info');
                if (userInfo) {
                    const name = selectedConv.querySelector('h4')?.textContent || 'Utilisateur';
                    const img = selectedConv.querySelector('.avatar')?.src || 'assets/images/default-avatar.png';
                    userInfo.querySelector('img').src = img;
                    userInfo.querySelector('h3').textContent = name;
                }
            }

            // Charger les détails de la conversation
            const conversationResponse = await this.api.apiRequest('GET', `?action=getConversation&conversation_id=${conversationId}`);
            if (!conversationResponse.success) {
                throw new Error(conversationResponse.message || 'Erreur lors de la récupération de la conversation');
            }

            // Charger les messages
            await this.loadMessages();
            
            // Marquer les messages comme lus
            await this.markMessagesAsRead(conversationId);
            
        } catch (error) {
            console.error('Erreur selectConversation:', error);
            this.showNotification('Erreur lors de la sélection de la conversation', 'error');
        }
    }

    async checkPermissions() {
        try {
            const response = await this.api.apiRequest('GET', `?action=checkPermissions&conversation_id=${this.selectedConversationId}`);
            if (response.success) {
                const { can_write, can_read } = response.permissions;
                const messageInput = document.getElementById('messageInput');
                const chatMessages = document.getElementById('chatMessages');
                if (messageInput) messageInput.disabled = !can_write;
                if (chatMessages) chatMessages.style.display = can_read ? 'block' : 'none';
                if (!can_read) this.showNotification('Vous n\'avez pas la permission de lire cette conversation', 'warning');
            }
            return response;
        } catch (error) {
            console.error('Erreur checkPermissions:', error);
            return { success: false, message: 'Erreur lors de la vérification des permissions' };
        }
    }

    async loadMessages() {
        if (!this.selectedConversationId) {
            this.showNotification('Aucune conversation sélectionnée', 'warning');
            return;
        }

        const container = document.getElementById('chatMessages');
        if (!container) {
            console.error('Élément chatMessages non trouvé');
            return;
        }

        try {
            // Construire l'URL avec les paramètres corrects
            const params = new URLSearchParams({
                action: 'getMessages',
                conversation_id: this.selectedConversationId,
                page: this.messagePage,
                limit: this.messagesPerPage
            });
            
            const response = await this.api.apiRequest('GET', `?${params.toString()}`);
            
            if (response.success) {
                // Si c'est la première page, vider le conteneur et réinitialiser les messages
                if (this.messagePage === 1) {
                    container.innerHTML = '';
                    this.messages = [];
                }

                // Les messages sont dans l'ordre DESC (plus récents en premier)
                const newMessages = response.messages;
                
                // Ajouter les nouveaux messages au début du tableau
                this.messages = [...newMessages, ...this.messages];
                
                // Trier les messages par ID croissant (du plus ancien au plus récent)
                this.messages.sort((a, b) => a.id - b.id);
                
                // Vider le conteneur et réafficher tous les messages dans le bon ordre
                container.innerHTML = '';
                this.messages.forEach(msg => {
                    // Ne pas jouer le son lors du chargement initial des messages
                    this.displayMessage(msg, true);
                });
                
                this.addToMediaGrid(response.attachments || []);
                
                // Si c'est la première page, faire défiler jusqu'au bas
                if (this.messagePage === 1) {
                    this.scrollToBottom();
                }
            } else {
                if (response.message === 'Token d\'authentification manquant' || response.message === 'Token invalide ou expiré') {
                    console.error('Erreur d\'authentification:', response.message);
                    this.showNotification('Session expirée. Veuillez vous reconnecter.', 'error');
                    window.location.href = 'index.php?page=login';
                    return;
                }
                if (response.message === 'Conversation introuvable') {
                    this.showNotification('Cette conversation n\'existe plus', 'error');
                    window.location.reload();
                } else {
                    throw new Error(response.message || 'Erreur lors de la récupération des messages');
                }
            }
        } catch (error) {
            console.error('Erreur loadMessages:', error);
            if (error.message.includes('Token') || error.message.includes('authentification')) {
                this.showNotification('Session expirée. Veuillez vous reconnecter.', 'error');
                window.location.href = 'index.php?page=login';
                return;
            }
            this.showNotification('Erreur de chargement des messages', 'error');
            container.innerHTML = '<div class="no-messages">Erreur lors de la récupération des messages</div>';
        }
    }

    handleScroll() {
        const container = document.getElementById('chatMessages');
        if (!container) return;

        // Si on est en haut du conteneur et qu'il y a plus de messages à charger
        if (container.scrollTop === 0 && this.messages.length >= this.messagesPerPage) {
            this.messagePage++;
            this.loadMessages();
        }
    }

    displayMessage(message, isInitialLoad = false) {
        // Ne pas afficher le message si la conversation n'est pas active
        if (message.conversation_id !== this.selectedConversationId && !isInitialLoad) {
            return;
        }

        // Marquer les messages comme lus si la conversation est active
        if (message.conversation_id === this.selectedConversationId) {
            this.markMessagesAsRead(message.conversation_id);
        }

        const container = document.getElementById('chatMessages');
        if (!container) {
            console.error('Container de messages non trouvé');
            return;
        }

        // Vérifier si le message n'est pas déjà affiché
        const existingMessage = document.querySelector(`[data-message-id="${message.id}"]`);
        if (existingMessage) {
            return;
        }

        const isSent = message.sender_id === this.userId;
        
        // Créer l'élément du message
        const msgElement = document.createElement('div');
        msgElement.className = `message ${isSent ? 'sent' : 'received'}`;
        msgElement.setAttribute('data-message-id', message.id);
        
        // Créer le contenu du message
        const contentElement = document.createElement('div');
        contentElement.className = 'message-content';
        contentElement.textContent = message.content;
        
        // Créer les métadonnées du message
        const metaElement = document.createElement('div');
        metaElement.className = 'message-meta';
        
        // Ajouter l'heure du message
        const timeElement = document.createElement('span');
        timeElement.className = 'message-time';
        timeElement.textContent = new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        metaElement.appendChild(timeElement);
        
        // Ajouter l'état de lecture si c'est un message envoyé
        if (isSent) {
            const readStatus = document.createElement('span');
            readStatus.className = 'message-status';
            readStatus.innerHTML = message.is_read ? '✓✓' : '✓';
            metaElement.appendChild(readStatus);
        }
        
        // Assembler le message
        msgElement.appendChild(contentElement);
        msgElement.appendChild(metaElement);
        
        // Ajouter le message au conteneur
        container.appendChild(msgElement);
        
        // Faire défiler jusqu'au dernier message uniquement pour les nouveaux messages
        if (this.messagePage === 1) {
            this.scrollToBottom();
        }
        
        // Jouer le son de notification uniquement pour les nouveaux messages reçus et non lors du chargement initial
        if (!isSent && !existingMessage && !isInitialLoad) {
            this.playNotificationSound();
        }
        
        console.log('Message affiché:', {
            id: message.id,
            isSent,
            content: message.content,
            sender_id: message.sender_id,
            userId: this.userId
        });

        // Après avoir affiché le message, recharger les conversations
        this.loadConversations().catch(error => {
            console.error('Erreur lors du rechargement des conversations:', error);
        });
    }

    renderReactions(reactions) {
        return reactions.map(r => `<span>${r.reaction}</span>`).join(' ');
    }

    showReactionMenu(e) {
        e.preventDefault();
        const message = e.target.closest('.message');
        if (!message) return;
        this.currentMessageId = message.dataset.messageId;
        const menu = document.getElementById('reactionMenu');
        if (menu) {
            menu.style.display = 'block';
            menu.style.top = `${e.clientY}px`;
            menu.style.left = `${e.clientX}px`;
            document.addEventListener('click', () => menu.style.display = 'none', { once: true });
        }
    }

    updateMessageReaction(reaction) {
        const message = document.querySelector(`[data-message-id="${reaction.message_id}"]`);
        if (message) {
            let reactionsContainer = message.querySelector('.message-reactions');
            
            if (!reactionsContainer) {
                reactionsContainer = document.createElement('div');
                reactionsContainer.className = 'message-reactions';
                message.appendChild(reactionsContainer);
            }

            // Vérifier si la réaction existe déjà
            const existingReaction = reactionsContainer.querySelector(`.reaction[data-reaction="${reaction.reaction}"]`);
            
            if (existingReaction) {
                // Si la réaction existe déjà, la supprimer
                existingReaction.remove();
            } else {
                // Sinon, ajouter la nouvelle réaction
                const reactionElement = document.createElement('span');
                reactionElement.className = 'reaction';
                reactionElement.setAttribute('data-reaction', reaction.reaction);
                reactionElement.textContent = reaction.reaction;
                reactionsContainer.appendChild(reactionElement);
            }

            // Si le conteneur est vide après la suppression, le retirer
            if (reactionsContainer.children.length === 0) {
                reactionsContainer.remove();
            }
        }
    }

    async addReaction(reaction) {
        try {
            if (!this.currentMessageId) {
                console.error('Aucun message sélectionné pour la réaction');
                return;
            }

            // Envoyer la réaction via WebSocket
            this.api.socketRequest('sendReaction', {
                message_id: this.currentMessageId,
                reaction: reaction,
                conversation_id: this.selectedConversationId
            });

            // Mettre à jour l'interface utilisateur localement
            this.updateMessageReaction({
                message_id: this.currentMessageId,
                reaction: reaction
            });
        } catch (error) {
            console.error('Erreur addReaction:', error);
            this.showNotification('Erreur lors de l\'ajout de la réaction', 'error');
        }
    }

    addToMediaGrid(attachments) {
        const grid = document.getElementById('mediaGrid');
        if (!grid) return;
        attachments.forEach(att => {
            let element;
            switch (att.file_type) {
                case 'image':
                    element = `<img src="/message_api.php?action=getAttachment&attachment_id=${att.id}" alt="Media">`;
                    break;
                case 'video':
                    element = `<video src="/message_api.php?action=getAttachment&attachment_id=${att.id}" controls></video>`;
                    break;
                default:
                    element = `<a href="/message_api.php?action=getAttachment&attachment_id=${att.id}" target="_blank">${att.file_path?.split('/').pop() || 'Fichier'}</a>`;
            }
            grid.innerHTML += `<div class="media-item">${element}</div>`;
        });
    }

    async handleSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('messageInput');
        const content = input?.value.trim();
        
        // Vérifications initiales
        if (!content && !this.attachedFiles.length) {
            this.showNotification('Veuillez entrer un message ou joindre un fichier', 'warning');
            return;
        }

        if (!this.selectedConversationId) {
            this.showNotification('Aucune conversation sélectionnée', 'error');
            return;
        }

        // Vérification pour éviter les envois multiples
        if (this.isSubmitting) {
            this.showNotification('Un message est déjà en cours d\'envoi', 'warning');
            return;
        }

        this.isSubmitting = true;

        try {
            // Déclarer tempMessage au début du bloc try
            let tempMessage = {
                id: 'temp_' + Date.now(),
                sender_id: this.userId,
                content,
                created_at: new Date().toISOString(),
                conversation_id: this.selectedConversationId,
                attachments: this.attachedFiles
            };

            // Afficher le message temporaire
            this.displayMessage(tempMessage);
            input.value = '';

            // Envoyer le message via WebSocket uniquement
            this.api.emitMessage({
                id: 'temp_' + Date.now(),
                sender_id: this.userId,
                content,
                created_at: new Date().toISOString(),
                conversation_id: this.selectedConversationId,
                attachments: this.attachedFiles
            });

            // Vider les fichiers joints
            this.attachedFiles = [];
            this.updateAttachmentsPreview();

            // Recharger les conversations pour mettre à jour la liste
            await this.loadConversations();
        } catch (error) {
            console.error('Erreur handleSubmit:', error);
            // En cas d'erreur, retirer le message temporaire
            const tempElement = document.querySelector(`[data-message-id="${tempMessage.id}"]`);
            if (tempElement) {
                tempElement.remove();
            }
            this.showNotification(error.message || 'Erreur lors de l\'envoi du message', 'error');
        } finally {
            this.isSubmitting = false;
        }
    }

    storeOfflineMessage(messageData) {
        const offlineMessages = JSON.parse(localStorage.getItem('offlineMessages') || '[]');
        offlineMessages.push(messageData);
        localStorage.setItem('offlineMessages', JSON.stringify(offlineMessages));
    }

    flushOfflineMessages() {
        const offlineMessages = JSON.parse(localStorage.getItem('offlineMessages') || '[]');
        offlineMessages.forEach(msg => this.api.queueMessage(msg));
        localStorage.removeItem('offlineMessages');
        this.api.flushMessageQueue();
    }

    handleFileUpload(e) {
        const files = Array.from(e.target.files || []);
        this.attachedFiles = files.filter(file => file.size <= this.maxFileSize);
        if (this.attachedFiles.length !== files.length) {
            this.showNotification('Certains fichiers dépassent la taille maximale (10 Mo)', 'warning');
        }
        this.updateAttachmentsPreview();
    }

    updateAttachmentsPreview() {
        const container = document.getElementById('messageAttachments');
        if (container) {
            container.innerHTML = this.attachedFiles.map(file => `
                <div class="attachment-preview">${file.name}</div>
            `).join('');
        }
    }

    async handleSearch() {
        const query = document.getElementById('searchUsers')?.value.trim() || '';
        if (query.length < 2) {
            this.clearSearchResults();
            return;
        }
        try {
            const response = await this.api.apiRequest('GET', `?action=searchUsers&query=${encodeURIComponent(query)}`);
            if (response.success) {
                this.renderSearchResults(response.users);
            } else {
                throw new Error(response.message || 'Erreur recherche');
            }
        } catch (error) {
            console.error('Erreur handleSearch:', error);
            this.showNotification('Erreur lors de la recherche', 'error');
            this.clearSearchResults();
        }
    }

    renderSearchResults(users) {
        const results = document.querySelector('.search-results');
        if (!results) return;
        results.innerHTML = users.length ? users.map(user => `
            <div class="search-result-item" data-user-id="${user.id}">
                <img src="${user.profile_image || 'assets/images/default-avatar.png'}" alt="Avatar" class="avatar">
                <div class="user-info">
                    <div class="user-name">${user.first_name} ${user.last_name}</div>
                </div>
            </div>
        `).join('') : '<div class="no-results">Aucun utilisateur trouvé</div>';
        results.classList.add('show');
        results.querySelectorAll('.search-result-item').forEach(el => {
            el.addEventListener('click', () => this.startConversation({
                id: el.dataset.userId,
                first_name: el.querySelector('.user-name')?.textContent.split(' ')[0] || '',
                last_name: el.querySelector('.user-name')?.textContent.split(' ')[1] || ''
            }));
        });
    }

    clearSearchResults() {
        const results = document.querySelector('.search-results');
        if (results) {
            results.innerHTML = '';
            results.classList.remove('show');
        }
    }

    async startConversation(user) {
        try {
            const response = await this.api.apiRequest('POST', '', {
                action: 'createConversation',
                user_id: user.id
            });
            if (response.success) {
                await this.loadConversations();
                await this.selectConversation(user.id, response.conversation_id);
                this.showNotification(response.is_new_conversation ? 'Conversation créée' : 'Conversation existante', 'success');
            } else {
                throw new Error(response.message || 'Erreur création conversation');
            }
        } catch (error) {
            console.error('Erreur startConversation:', error);
            this.showNotification('Erreur lors de la création de la conversation', 'error');
        }
    }

    async startCall(type) {
        if (!this.selectedConversationId) {
            this.showNotification('Veuillez sélectionner une conversation', 'error');
            return;
        }

        const modal = document.getElementById('callModal');
        if (modal) {
            modal.style.display = 'block';
        }

        this.api.socketRequest('startCall', {
            conversation_id: this.selectedConversationId,
            call_type: type
        }, (response) => {
            if (response && response.success) {
                // Afficher la modale, démarrer la sonnerie, etc.
                this.showNotification('Appel en cours...', 'info');
            } else {
                this.showNotification(response?.message || 'Erreur lors de l\'appel', 'error');
                if (modal) {
                    modal.style.display = 'none';
                }
            }
        });
    }

    handleIncomingCall(callData) {
        if (!callData || typeof callData !== 'object') {
            console.error('Données d\'appel invalides:', callData);
            return;
        }

        if (!this.isCalling) {
            this.isCalling = true;
            const modal = document.getElementById('callModal');
            const status = document.getElementById('callStatus');
            const acceptBtn = document.getElementById('acceptCall');
            const declineBtn = document.getElementById('declineCall');
            
            if (!modal || !status || !acceptBtn || !declineBtn) {
                console.error('Éléments du modal d\'appel non trouvés');
                this.isCalling = false;
                return;
            }

            // Vérifier que les données nécessaires sont présentes
            if (!callData.call_id || !callData.caller_id || !callData.conversation_id || !callData.type) {
                console.error('Données d\'appel incomplètes:', callData);
                this.isCalling = false;
                return;
            }

            document.getElementById('callAvatar').src = callData.caller_avatar || 'assets/images/default-avatar.png';
            document.getElementById('callUserName').textContent = callData.caller_name || 'Utilisateur';
            status.textContent = `Appel ${callData.type} entrant...`;
            modal.style.display = 'block';
            
            // Jouer la sonnerie
            this.ringtone = new Audio('/assets/sounds/ringtone.mp3');
            this.ringtone.loop = true;
            this.ringtone.play().catch(err => console.error('Erreur lecture sonnerie:', err));

            // Gestionnaire d'acceptation d'appel
            acceptBtn.addEventListener('click', () => {
                this.stopRingtone();
                status.textContent = `Connecté en ${callData.type}`;
                if (callData.type === 'video') {
                    document.getElementById('remoteVideo').style.display = 'block';
                    document.getElementById('localVideo').style.display = 'block';
                }
                this.api.socketRequest('answerCall', {
                    call_id: callData.call_id,
                    answer: 'accepted'
                }).catch(err => {
                    console.error('Erreur answerCall:', err);
                    this.showNotification('Erreur lors de l\'acceptation de l\'appel', 'error');
                });
            }, { once: true });

            // Gestionnaire de refus d'appel
            declineBtn.addEventListener('click', () => {
                this.stopRingtone();
                modal.style.display = 'none';
                this.isCalling = false;
                this.api.socketRequest('answerCall', {
                    call_id: callData.call_id,
                    answer: 'rejected'
                }).catch(err => {
                    console.error('Erreur answerCall:', err);
                    this.showNotification('Erreur lors du refus de l\'appel', 'error');
                });
            }, { once: true });

            // Fermeture automatique après 30 secondes si pas de réponse
            setTimeout(() => {
                if (this.isCalling) {
                    this.stopRingtone();
                    modal.style.display = 'none';
                    this.isCalling = false;
                    this.api.socketRequest('answerCall', {
                        call_id: callData.call_id,
                        answer: 'missed'
                    }).catch(err => console.error('Erreur answerCall:', err));
                }
            }, 30000);
        }
    }

    restoreActiveConversation() {
        const activeId = localStorage.getItem('activeConversationId');
        if (activeId) {
            const convItem = document.querySelector(`.conversation-item[data-conversation-id="${activeId}"]`);
            if (convItem) {
                // Vérifier si la conversation est déjà chargée
                if (this.selectedConversationId === activeId) {
                    console.log('Conversation déjà chargée:', activeId);
                    return;
                }
                this.selectConversation(convItem.dataset.userId, activeId);
            }
        }
    }

    toggleSidebar() {
        const sidebar = document.querySelector('.chat-sidebar');
        if (sidebar) sidebar.classList.toggle('active');
    }

    getCachedConversations() {
        try {
            const data = localStorage.getItem('conversations');
            return data ? JSON.parse(data) : [];
        } catch (error) {
            console.error('Erreur getCachedConversations:', error);
            return [];
        }
    }

    scrollToBottom() {
        const container = document.getElementById('chatMessages');
        if (container) container.scrollTop = container.scrollHeight;
    }

    showNotification(message, type) {
        console.log('showNotification:', { message, type, readyState: document.readyState });
        try {
            Swal.fire({
                text: message,
                icon: type,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        } catch (error) {
            console.error('Erreur SweetAlert2:', error);
        }
    }

    async markMessagesAsRead(conversationId) {
        try {
            const response = await this.api.apiRequest('POST', '', {
                action: 'markMessagesAsRead',
                conversation_id: conversationId
            });
            
            if (response.success) {
                console.log('Messages marqués comme lus');
                // Mettre à jour le compteur de messages non lus dans l'interface
                const conversationItem = document.querySelector(`.conversation-item[data-conversation-id="${conversationId}"]`);
                if (conversationItem) {
                    const unreadBadge = conversationItem.querySelector('.unread-badge');
                    if (unreadBadge) {
                        unreadBadge.style.display = 'none';
                    }
                }
            }
        } catch (error) {
            console.error('Erreur lors du marquage des messages comme lus:', error);
            // Ne pas afficher de notification à l'utilisateur pour cette erreur non critique
        }
    }

    updateUnreadCount(conversationId) {
        const conversationElement = document.querySelector(`.conversation-item[data-conversation-id="${conversationId}"]`);
        if (conversationElement) {
            let badge = conversationElement.querySelector('.unread-badge');
            if (!badge) {
                badge = document.createElement('div');
                badge.className = 'unread-badge';
                conversationElement.querySelector('.conversation-meta').appendChild(badge);
            }
            const currentCount = parseInt(badge.textContent || '0');
            badge.textContent = currentCount + 1;
            badge.style.display = 'block';
        }
    }

    playNotificationSound() {
        const audio = new Audio('/assets/sounds/notification.mp3');
        audio.play().catch(err => console.log('Son non joué:', err));
    }

    stopRingtone() {
        const audio = document.getElementById('ringtone');
        if (audio) {
            audio.pause();
            audio.currentTime = 0;
        }
    }

    isConversationOpen(conversationId) {
        return this.selectedConversationId === conversationId;
    }
}

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}