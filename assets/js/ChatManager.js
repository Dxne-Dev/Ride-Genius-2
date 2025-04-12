class ChatManager {
    constructor(userId) {
        this.userId = userId;
        this.selectedUserId = null;
        this.selectedConversationId = null;
        this.api = new ChatAPI('/message_api.php', SOCKET_SERVER_URL);
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
                if (message.conversation_id == this.selectedConversationId) {
                    this.displayMessage(message);
                    this.addToMediaGrid(message.attachments || []);
                }
                this.loadConversations();
            },
            (reaction) => {
                if (reaction.conversation_id == this.selectedConversationId) {
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
            if (attachButton) attachButton.addEventListener('click', () => fileInput.click());
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
        indicator.style.display = isOnline ? 'none' : 'block';
    }

    async loadConversations() {
        try {
            const response = await this.api.apiRequest('GET', '?action=getConversations');
            if (response.success) {
                this.renderConversations(response.conversations);
            } else {
                throw new Error(response.message || 'Erreur API');
            }
        } catch (error) {
            this.showNotification('Erreur de chargement des conversations', 'error');
            this.renderConversations(this.getCachedConversations() || []);
        }
    }

    renderConversations(conversations) {
        const list = document.querySelector('.conversations-list');
        if (!list) return;
        list.innerHTML = '';
        if (!Array.isArray(conversations) || !conversations.length) {
            list.innerHTML = '<div class="no-conversations">Aucune conversation</div>';
            return;
        }
        localStorage.setItem('conversations', JSON.stringify(conversations));
        conversations.forEach(conv => {
            const item = document.createElement('div');
            item.className = `conversation-item ${conv.other_user_id == this.selectedUserId ? 'active' : ''}`;
            item.dataset.userId = conv.other_user_id;
            item.dataset.conversationId = conv.id;
            item.innerHTML = `
                <div class="user-info">
                    <img src="${conv.profile_image}" alt="Avatar" class="avatar">
                    <div class="user-details">
                        <h4>${conv.first_name} ${conv.last_name}</h4>
                        <p class="last-message">${conv.last_message.slice(0, 30)}${conv.last_message.length > 30 ? '...' : ''}</p>
                    </div>
                </div>
                <div class="conversation-meta">
                    ${conv.last_message_at ? `<span class="time">${new Date(conv.last_message_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}` : ''}
                    ${conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : ''}
                </div>
            `;
            item.addEventListener('click', () => this.selectConversation(conv.other_user_id, conv.id));
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
        if (convItem) {
            const name = convItem.querySelector('h4').textContent;
            const img = convItem.querySelector('.avatar').src;
            userInfo.querySelector('img').src = img;
            userInfo.querySelector('h3').textContent = name;
        }
        document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
        convItem?.classList.add('active');
        await this.checkPermissions();
        await this.loadMessages();
    }

    async checkPermissions() {
        const response = await this.api.apiRequest('GET', `?action=checkPermissions&conversation_id=${this.selectedConversationId}`);
        if (response.success) {
            const { can_write, can_read } = response.permissions;
            document.getElementById('messageInput').disabled = !can_write;
            document.getElementById('chatMessages').style.display = can_read ? 'block' : 'none';
            if (!can_read) this.showNotification('Vous n\'avez pas la permission de lire cette conversation', 'warning');
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
                throw new Error(response.message);
            }
        } catch (error) {
            this.showNotification('Erreur de chargement des messages', 'error');
            container.innerHTML = '<div class="no-messages">Aucun message</div>';
        }
    }

    handleScroll() {
        const container = document.getElementById('chatMessages');
        if (container.scrollTop === 0 && this.messages.length >= this.messagesPerPage) {
            this.messagePage++;
            this.loadMessages();
        }
    }

    displayMessage(message) {
        const container = document.getElementById('chatMessages');
        if (!container || document.querySelector(`[data-message-id="${message.id}"]`)) return;
        const isSent = message.sender_id == this.userId;
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
        menu.style.display = 'block';
        menu.style.top = `${e.clientY}px`;
        menu.style.left = `${e.clientX}px`;
        document.addEventListener('click', () => menu.style.display = 'none', { once: true });
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
            this.showNotification('Erreur lors de l\'ajout de la réaction', 'error');
        }
    }

    updateMessageReaction(reaction) {
        const message = document.querySelector(`[data-message-id="${reaction.message_id}"]`);
        if (message) {
            const reactions = message.querySelector('.message-reactions');
            reactions.innerHTML += `<span>${reaction.reaction}</span>`;
        }
    }

    addToMediaGrid(attachments) {
        const grid = document.getElementById('mediaGrid');
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
                    element = `<a href="/message_api.php?action=getAttachment&attachment_id=${att.id}" target="_blank">${att.file_path.split('/').pop()}</a>`;
            }
            grid.innerHTML += `<div class="media-item">${element}</div>`;
        });
    }

    async handleSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('messageInput');
        const content = input.value.trim();
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
                }
            }
            input.value = '';
            this.attachedFiles = [];
            this.updateAttachmentsPreview();
        } catch (error) {
            this.api.queueMessage(messageData);
            this.showNotification('Erreur d\'envoi', 'error');
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
        const files = Array.from(e.target.files);
        this.attachedFiles = files.filter(file => file.size <= this.maxFileSize);
        if (this.attachedFiles.length !== files.length) {
            this.showNotification('Certains fichiers dépassent la taille maximale', 'warning');
        }
        this.updateAttachmentsPreview();
    }

    updateAttachmentsPreview() {
        const container = document.getElementById('messageAttachments');
        container.innerHTML = this.attachedFiles.map(file => `
            <div class="attachment-preview">${file.name}</div>
        `).join('');
    }

    async sendFiles(messageData) {
        const formData = new FormData();
        formData.append('action', 'sendMessage');
        formData.append('conversation_id', messageData.conversation_id);
        formData.append('content', messageData.content);
        this.attachedFiles.forEach((file, i) => formData.append(`files[${i}]`, file));
        const response = await this.api.apiRequest('POST', '', formData);
        if (response.success) {
            this.api.socketRequest('sendMessage', { ...messageData, attachments: response.attachments });
            this.addToMediaGrid(response.attachments);
        }
        return response.success;
    }

    async handleSearch() {
        const query = document.getElementById('searchUsers').value.trim();
        if (query.length < 2) return;
        try {
            const response = await this.api.apiRequest('GET', `?action=searchUsers&query=${encodeURIComponent(query)}`);
            if (response.success) this.renderSearchResults(response.users);
            else throw new Error(response.message);
        } catch (error) {
            this.showNotification('Erreur de recherche', 'error');
        }
    }

    renderSearchResults(users) {
        const results = document.querySelector('.search-results');
        if (!results) return;
        results.innerHTML = users.map(user => `
            <div class="search-result" data-user-id="${user.id}">
                <img src="${user.profile_image}" alt="Avatar">
                <span>${user.first_name} ${user.last_name}</span>
            </div>
        `).join('');
        results.querySelectorAll('.search-result').forEach(el => {
            el.addEventListener('click', () => this.startConversation(user));
        });
    }

    async startConversation(user) {
        const response = await this.api.apiRequest('POST', '', {
            action: 'createConversation',
            user_id: user.id
        });
        if (response.success) {
            this.loadConversations();
            this.selectConversation(user.id, response.conversation_id);
            this.showNotification(response.is_new_conversation ? 'Conversation créée' : 'Conversation existante', 'success');
        } else {
            this.showNotification('Erreur lors de la création', 'error');
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
        const avatar = userInfo.querySelector('img').src;
        const name = userInfo.querySelector('h3').textContent;

        document.getElementById('callAvatar').src = avatar;
        document.getElementById('callUserName').textContent = name;
        status.textContent = `Appel ${type} en cours...`;
        modal.style.display = 'block';

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
        }

        declineBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            this.api.apiRequest('POST', '', {
                action: 'endCall',
                call_id: response.call_id
            });
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
        sidebar.classList.toggle('active');
    }

    getCachedConversations() {
        try {
            const data = localStorage.getItem('conversations');
            return data ? JSON.parse(data) : [];
        } catch (error) {
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
        audio.play().catch(() => console.log('Son non joué'));
    }
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}