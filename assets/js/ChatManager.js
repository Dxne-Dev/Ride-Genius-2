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
        this.init();
    }

    init() {
        if (!this.userId) {
            this.showNotification('Utilisateur non identifié', 'error');
            window.location.href = 'index.php?page=login';
            return;
        }
        this.setupEventListeners();
        this.setupNetworkStatus();
        this.connectSocket();
        this.loadConversations();
        this.restoreActiveConversation();
    }

    connectSocket() {
        this.api.connectSocket(
            { userId: this.userId },
            () => {
                this.showNotification('Connecté au chat', 'success');
                this.updateNetworkStatus(true);
                this.flushOfflineMessages();
            },
            () => {
                this.showNotification('Déconnecté du chat', 'warning');
                this.updateNetworkStatus(false);
            },
            (message) => {
                if (message.conversation_id === this.selectedConversationId) {
                    this.displayMessage(message);
                    this.addToMediaGrid(message.attachments || []);
                }
                this.loadConversations();
            },
            (reaction) => {
                if (reaction.conversation_id === this.selectedConversationId) {
                    this.updateMessageReaction(reaction);
                }
            }
        );
    }

    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('messageForm');
            const searchInput = document.getElementById('searchUsers');
            const fileInput = document.getElementById('fileInput');
            const attachButton = document.getElementById('attachButton');
            const audioCallBtn = document.getElementById('audioCallBtn');
            const videoCallBtn = document.getElementById('videoCallBtn');
            const chatMessages = document.getElementById('chatMessages');
            const swipeIndicator = document.querySelector('.swipe-indicator');
            const reactionMenu = document.getElementById('reactionMenu');

            if (form) form.addEventListener('submit', (e) => this.handleSubmit(e));
            if (searchInput) searchInput.addEventListener('input', debounce(() => this.handleSearch(), 300));
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
        });
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
        this.selectedUserId = userId;
        this.selectedConversationId = conversationId;
        localStorage.setItem('activeConversationId', conversationId);
        this.messagePage = 1;
        this.messages = [];
        const userInfo = document.querySelector('.selected-user-info .user-info');
        const convItem = document.querySelector(`.conversation-item[data-user-id="${userId}"]`);
        if (convItem && userInfo) {
            const name = convItem.querySelector('h4')?.textContent || 'Utilisateur';
            const img = convItem.querySelector('.avatar')?.src || 'assets/images/default-avatar.png';
            userInfo.querySelector('img').src = img;
            userInfo.querySelector('h3').textContent = name;
        }
        document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
        convItem?.classList.add('active');
        await this.checkPermissions();
        await this.loadMessages();
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
        } catch (error) {
            console.error('Erreur checkPermissions:', error);
        }
    }

    async loadMessages() {
        if (!this.selectedConversationId) return;
        const container = document.getElementById('chatMessages');
        if (!container) return;
        container.innerHTML = '<div class="loading">Chargement...</div>';
        try {
            const response = await this.api.apiRequest('GET', `?action=getMessages&conversation_id=${this.selectedConversationId}&page=${this.messagePage}&limit=${this.messagesPerPage}`);
            if (response.success) {
                this.messages = response.messages.reverse();
                container.innerHTML = '';
                this.messages.forEach(msg => this.displayMessage(msg));
                this.addToMediaGrid(response.attachments || []);
                this.scrollToBottom();
            } else {
                throw new Error(response.message || 'Erreur API');
            }
        } catch (error) {
            console.error('Erreur loadMessages:', error);
            this.showNotification('Erreur de chargement des messages', 'error');
            container.innerHTML = '<div class="no-messages">Aucun message</div>';
        }
    }

    handleScroll() {
        const container = document.getElementById('chatMessages');
        if (container?.scrollTop === 0 && this.messages.length >= this.messagesPerPage) {
            this.messagePage++;
            this.loadMessages();
        }
    }

    displayMessage(message) {
        const container = document.getElementById('chatMessages');
        if (!container || document.querySelector(`[data-message-id="${message.id}"]`)) return;
        const isSent = message.sender_id === this.userId;
        const msgElement = document.createElement('div');
        msgElement.className = `message ${isSent ? 'sent' : 'received'}`;
        msgElement.dataset.messageId = message.id;
        msgElement.innerHTML = `
            <div class="message-content">${message.content}</div>
            <span class="message-time">${new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
            <div class="message-reactions">${this.renderReactions(message.reactions || [])}</div>
        `;
        container.appendChild(msgElement);
        this.scrollToBottom();
        if (!isSent) this.playNotificationSound();
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

    async addReaction(emoji) {
        try {
            const response = await this.api.apiRequest('POST', '', {
                action: 'addReaction',
                message_id: this.currentMessageId,
                reaction: emoji
            });
            if (response.success) {
                this.api.socketRequest('sendReaction', {
                    message_id: this.currentMessageId,
                    reaction: emoji,
                    conversation_id: this.selectedConversationId,
                    user_id: this.userId
                });
            }
        } catch (error) {
            console.error('Erreur addReaction:', error);
            this.showNotification('Erreur lors de l\'ajout de la réaction', 'error');
        }
    }

    updateMessageReaction(reaction) {
        const message = document.querySelector(`[data-message-id="${reaction.message_id}"]`);
        if (message) {
            const reactions = message.querySelector('.message-reactions');
            if (reactions) reactions.innerHTML += `<span>${reaction.reaction}</span>`;
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
        const content = input?.value.trim() || '';
        if (!content && !this.attachedFiles.length) return;
        const messageData = { conversation_id: this.selectedConversationId, content };
        try {
            if (!this.isOnline) {
                this.storeOfflineMessage(messageData);
                this.showNotification('Vous êtes hors ligne, message en attente', 'warning');
                return;
            }
            if (this.attachedFiles.length) {
                await this.sendFiles(messageData);
            } else {
                const response = await this.api.socketRequest('sendMessage', messageData);
                if (response.success) {
                    this.displayMessage({ ...messageData, id: response.message_id, sender_id: this.userId, created_at: new Date() });
                } else {
                    throw new Error(response.message || 'Erreur envoi message');
                }
            }
            input.value = '';
            this.attachedFiles = [];
            this.updateAttachmentsPreview();
        } catch (error) {
            console.error('Erreur handleSubmit:', error);
            this.api.queueMessage(messageData);
            this.showNotification('Erreur d\'envoi du message', 'error');
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

    async sendFiles(messageData) {
        const formData = new FormData();
        formData.append('action', 'sendMessage');
        formData.append('conversation_id', messageData.conversation_id);
        formData.append('content', messageData.content);
        this.attachedFiles.forEach((file, i) => formData.append(`files[${i}]`, file));
        const response = await this.api.apiRequest('POST', '', formData);
        if (response.success) {
            await this.api.socketRequest('sendMessage', { ...messageData, attachments: response.attachments });
            this.addToMediaGrid(response.attachments);
            return true;
        }
        throw new Error(response.message || 'Erreur envoi fichiers');
    }

    async handleSearch() {
        const query = document.getElementById('searchUsers')?.value.trim() || '';
        if (query.length < 2) {
            this.renderSearchResults([]);
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
            this.renderSearchResults([]);
        }
    }

    renderSearchResults(users) {
        const results = document.querySelector('.search-results');
        if (!results) return;
        results.innerHTML = users.length ? users.map(user => `
            <div class="search-result" data-user-id="${user.id}">
                <img src="${user.profile_image || 'assets/images/default-avatar.png'}" alt="Avatar">
                <span>${user.first_name} ${user.last_name}</span>
            </div>
        `).join('') : '<div class="no-results">Aucun utilisateur trouvé</div>';
        results.querySelectorAll('.search-result').forEach(el => {
            el.addEventListener('click', () => this.startConversation({
                id: el.dataset.userId,
                first_name: el.querySelector('span')?.textContent.split(' ')[0] || '',
                last_name: el.querySelector('span')?.textContent.split(' ')[1] || ''
            }));
        });
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
            this.showNotification('Sélectionnez une conversation', 'warning');
            return;
        }
        const modal = document.getElementById('callModal');
        const status = document.getElementById('callStatus');
        const acceptBtn = document.getElementById('acceptCall');
        const declineBtn = document.getElementById('declineCall');
        const userInfo = document.querySelector('.selected-user-info .user-info');
        if (!modal || !status || !acceptBtn || !declineBtn || !userInfo) return;

        const avatar = userInfo.querySelector('img')?.src || 'assets/images/default-avatar.png';
        const name = userInfo.querySelector('h3')?.textContent || 'Utilisateur';
        document.getElementById('callAvatar').src = avatar;
        document.getElementById('callUserName').textContent = name;
        status.textContent = `Appel ${type} en cours...`;
        modal.style.display = 'block';

        try {
            const response = await this.api.apiRequest('POST', '', {
                action: 'startCall',
                conversation_id: this.selectedConversationId,
                call_type: type
            });
            if (response.success) {
                acceptBtn.addEventListener('click', () => {
                    status.textContent = `Connecté en ${type}`;
                    if (type === 'video') {
                        document.getElementById('remoteVideo').style.display = 'block';
                        document.getElementById('localVideo').style.display = 'block';
                    }
                }, { once: true });
            } else {
                throw new Error(response.message || 'Erreur démarrage appel');
            }
        } catch (error) {
            console.error('Erreur startCall:', error);
            this.showNotification('Erreur lors du démarrage de l\'appel', 'error');
            modal.style.display = 'none';
        }

        declineBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            this.api.apiRequest('POST', '', {
                action: 'endCall',
                call_id: response?.call_id
            }).catch(err => console.error('Erreur endCall:', err));
        }, { once: true });
    }

    restoreActiveConversation() {
        const activeId = localStorage.getItem('activeConversationId');
        if (activeId) {
            const convItem = document.querySelector(`.conversation-item[data-conversation-id="${activeId}"]`);
            if (convItem) {
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

    playNotificationSound() {
        const audio = new Audio('/assets/sounds/notification.mp3');
        audio.play().catch(err => console.log('Son non joué:', err));
    }
}

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}