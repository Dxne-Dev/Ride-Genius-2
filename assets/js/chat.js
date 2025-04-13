// Variables globales
let socket = null;
let chatFileHandler = null;
let messageQueue = [];
let currentUserId = null;
let selectedUserId = null;
let wsReconnectAttempts = 0;
let wsConnected = false;

// Fonction pour sauvegarder les conversations dans le localStorage
function saveToLocalStorage(conversations) {
    if (conversations && Array.isArray(conversations)) {
        localStorage.setItem('conversations', JSON.stringify(conversations));
        console.log('Conversations sauvegardées dans localStorage:', conversations);
    }
}

// Fonction pour récupérer les conversations du localStorage
function getFromLocalStorage() {
    const storedData = localStorage.getItem('conversations');
    return storedData ? JSON.parse(storedData) : null;
}

// Fonction pour charger les conversations depuis l'API ou le localStorage
async function loadConversations() {
    try {
        // 1. Tentative API
        const response = await fetch('/api/get-conversations');
        if (response.ok) {
            const data = await response.json();
            if (data.success && Array.isArray(data.conversations)) {
                saveToLocalStorage(data.conversations); // Cache local
                renderConversations(data.conversations);
                return;
            }
        }
    } catch (error) { 
        console.error("Erreur lors du chargement des conversations depuis l'API:", error);
    }

    // 2. Fallback: LocalStorage ou données PHP inline
    const fallbackData = getFromLocalStorage() || (typeof PHP_CONVERSATIONS !== 'undefined' ? PHP_CONVERSATIONS : []);
    if (fallbackData && Array.isArray(fallbackData)) {
        console.log('Utilisation des conversations depuis le cache local:', fallbackData);
        renderConversations(fallbackData);
    } else {
        console.warn('Aucune donnée de conversation disponible');
    }
}

// Fonction pour afficher les conversations
function renderConversations(conversations) {
    const conversationsList = document.querySelector('.conversations-list');
    if (!conversationsList) {
        console.error('Container des conversations non trouvé');
        return;
    }

    // Vider la liste existante
    conversationsList.innerHTML = '';

    if (!conversations || conversations.length === 0) {
        conversationsList.innerHTML = '<div class="no-conversations">Aucune conversation</div>';
        return;
    }

    // Ajouter chaque conversation à la liste
    conversations.forEach(conv => {
        const conversationItem = document.createElement('div');
        conversationItem.className = 'conversation-item';
        conversationItem.setAttribute('data-user-id', conv.other_user_id || 0);

        const firstName = conv.first_name || 'Utilisateur';
        const lastName = conv.last_name || '';
        const lastMessage = conv.last_message || 'Démarrez une conversation';
        const profileImage = conv.profile_image || 'assets/images/default-avatar.png';
        const lastMessageTime = conv.last_message_at ? new Date(conv.last_message_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
        const unreadCount = conv.unread_count || 0;

        conversationItem.innerHTML = `
            <div class="user-info">
                <img src="${profileImage}" alt="Avatar" class="avatar">
                <div class="user-details">
                    <h4>${firstName} ${lastName}</h4>
                    <p class="last-message">${lastMessage.length > 30 ? lastMessage.substring(0, 30) + '...' : lastMessage}</p>
                </div>
            </div>
            <div class="conversation-meta">
                ${lastMessageTime ? `<span class="time">${lastMessageTime}</span>` : ''}
                ${unreadCount > 0 ? `<span class="unread-badge">${unreadCount}</span>` : ''}
            </div>
        `;

        conversationItem.addEventListener('click', () => {
            selectConversation(conv.other_user_id);
        });

        conversationsList.appendChild(conversationItem);
    });
}

// Fonction pour établir la connexion Socket.IO
function connectSocket() {
    try {
        // Configuration de la connexion Socket.IO
        const socketOptions = {
            transports: ['websocket'],
            reconnection: true,
            reconnectionAttempts: WS_MAX_RECONNECT_ATTEMPTS,
            reconnectionDelay: WS_RECONNECT_DELAY,
            timeout: 10000, // Timeout de 10 secondes
            autoConnect: true,
            forceNew: true
        };

        console.log('Tentative de connexion au serveur Socket.IO...');
        socket = io(SOCKET_SERVER_URL, socketOptions);

        // Gestionnaire de connexion
        socket.on('connect', () => {
            console.log('✅ Connecté au serveur Socket.IO');
            wsReconnectAttempts = 0;
            wsConnected = true;
            
            // Initialiser le gestionnaire de fichiers
            chatFileHandler = new ChatFileHandler({
                socket: socket,
                maxFileSize: 10 * 1024 * 1024 // 10MB
            });
            
            // Authentifier l'utilisateur
            socket.emit('auth', {
                userId: currentUserId
            });

            // Envoyer les messages en attente
            while (messageQueue.length > 0) {
                const message = messageQueue.shift();
                socket.emit('sendMessage', message);
            }

            // Afficher une notification de connexion
            showNotification('Connecté au chat en temps réel', 'success');
        });

        // Gestionnaire de déconnexion
        socket.on('disconnect', (reason) => {
            console.log('❌ Déconnecté du serveur Socket.IO:', reason);
            wsConnected = false;
            
            if (reason === 'io server disconnect') {
                // La déconnexion a été initiée par le serveur, on peut essayer de se reconnecter
                socket.connect();
            }
            
            showNotification('Déconnecté du chat en temps réel', 'warning');
        });

        // Gestionnaire de reconnexion
        socket.on('reconnect_attempt', (attemptNumber) => {
            console.log(`Tentative de reconnexion #${attemptNumber}`);
            wsReconnectAttempts = attemptNumber;
            showNotification(`Tentative de reconnexion #${attemptNumber}`, 'info');
        });

        socket.on('reconnect', (attemptNumber) => {
            console.log(`✅ Reconnecté après ${attemptNumber} tentatives`);
            showNotification('Reconnecté au chat en temps réel', 'success');
        });

        socket.on('reconnect_error', (error) => {
            console.error('❌ Erreur de reconnexion:', error);
            showNotification('Erreur de reconnexion au chat', 'error');
        });

        socket.on('reconnect_failed', () => {
            console.error('❌ Échec de la reconnexion après toutes les tentatives');
            showNotification('Impossible de se reconnecter au chat', 'error');
        });

        // Gestionnaire d'erreurs
        socket.on('error', (error) => {
            console.error('❌ Erreur Socket.IO:', error);
            showNotification('Erreur de connexion au chat', 'error');
        });

        // Réception des messages existants
        socket.on('loadMessages', (messages) => {
            console.log('📚 Messages existants reçus:', messages);
            messages.forEach(message => displayMessage(message));
            scrollToBottom();
        });

        // Réception d'un nouveau message
        socket.on('receiveMessage', (data) => {
            console.log('📨 Message reçu:', data);
            displayMessage(data);
            scrollToBottom();
        });

        // Confirmation d'envoi de message
        socket.on('messageSent', (response) => {
            console.log('✅ Message envoyé avec succès:', response);
        });

        // Gestionnaire de ping/pong pour vérifier la connexion
        socket.on('ping', () => {
            console.log('Ping reçu du serveur');
        });

        socket.on('pong', (latency) => {
            console.log(`Pong envoyé au serveur (latence: ${latency}ms)`);
        });

    } catch (error) {
        console.error('❌ Erreur lors de la connexion:', error);
        showNotification('Erreur lors de la connexion au chat', 'error');
    }
}

// Fonction pour afficher un message
function displayMessage(messageData) {
    const messagesContainer = document.querySelector('.chat-messages');
    if (!messagesContainer) {
        console.error('Container de messages non trouvé');
        return;
    }

    // Éviter les doublons
    if (document.querySelector(`[data-message-id="${messageData.id}"]`)) {
        return;
    }

    const messageElement = document.createElement('div');
    const isSent = messageData.senderId === currentUserId;

    messageElement.className = `message ${isSent ? 'sent' : 'received'}`;
    messageElement.setAttribute('data-message-id', messageData.id);

    const time = new Date(messageData.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    messageElement.innerHTML = `
        <div class="message-content">${messageData.content}</div>
        <div class="message-time">${time}</div>
    `;

    messagesContainer.appendChild(messageElement);
    scrollToBottom();

    // Jouer le son de notification uniquement pour les nouveaux messages reçus
    if (!isSent && !document.querySelector(`[data-message-id="${messageData.id}"]`)) {
        playNotificationSound();
    }
}

// Fonction pour faire défiler vers le bas
function scrollToBottom() {
    const messagesContainer = document.querySelector('.chat-messages');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Fonction pour jouer le son de notification
function playNotificationSound() {
    const audio = new Audio('/assets/sounds/notification.mp3');
    audio.play().catch(error => console.log('Erreur lors de la lecture du son:', error));
}

// Charger les messages existants
async function loadExistingMessages() {
    if (!selectedUserId) {
        console.warn('Aucun utilisateur sélectionné pour charger les messages');
        return;
    }
    
    // Vider les messages précédents
    const messagesContainer = document.querySelector('.chat-messages');
    if (messagesContainer) {
        messagesContainer.innerHTML = '';
    }
    
    // Afficher un indicateur de chargement
    if (messagesContainer) {
        messagesContainer.innerHTML = '<div class="loading-messages">Chargement des messages...</div>';
    }
    
    // Clé pour le stockage local des messages
    const storageKey = `messages_${currentUserId}_${selectedUserId}`;
    
    try {
        // 1. Tentative de chargement depuis l'API
        const response = await fetch(`/api/messages.php?receiver_id=${selectedUserId}`);
        
        if (response.ok) {
            const data = await response.json();
            
            if (data.status === 'success' && Array.isArray(data.messages)) {
                // Vider le conteneur avant d'afficher les messages
                if (messagesContainer) {
                    messagesContainer.innerHTML = '';
                }
                
                // Enregistrer les messages dans le stockage local
                localStorage.setItem(storageKey, JSON.stringify(data.messages));
                
                // Afficher les messages
                data.messages.forEach(message => displayMessage(message));
                scrollToBottom();
                return;
            }
        } else {
            console.error('Erreur lors du chargement des messages depuis l\'API:', response.statusText);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des messages:', error);
    }
    
    // 2. Fallback: Utiliser les messages stockés localement
    try {
        const cachedMessages = localStorage.getItem(storageKey);
        
        if (cachedMessages) {
            const messages = JSON.parse(cachedMessages);
            
            // Vider le conteneur avant d'afficher les messages
            if (messagesContainer) {
                messagesContainer.innerHTML = '';
            }
            
            // Afficher les messages depuis le cache
            console.log('Utilisation des messages depuis le cache local:', messages);
            messages.forEach(message => displayMessage(message));
            scrollToBottom();
            
            // Afficher une notification
            showNotification('Messages chargés depuis le cache local', 'info');
        } else {
            // Aucun message dans le cache
            if (messagesContainer) {
                messagesContainer.innerHTML = '<div class="no-messages">Aucun message. Commencez la conversation!</div>';
            }
        }
    } catch (cacheError) {
        console.error('Erreur lors de la récupération des messages depuis le cache:', cacheError);
        if (messagesContainer) {
            messagesContainer.innerHTML = '<div class="error-messages">Impossible de charger les messages</div>';
        }
    }
}

// Fonction pour envoyer un message
async function sendMessage(content) {
    console.log('Tentative d\'envoi de message:', content);
    
    if (!content && (!chatFileHandler || chatFileHandler.attachedFiles.length === 0)) {
        console.log('Aucun contenu à envoyer');
        return false;
    }

    const messageData = {
        receiver_id: selectedUserId,
        message: content
    };

    try {
        // Envoyer les fichiers s'il y en a
        if (chatFileHandler && chatFileHandler.attachedFiles.length > 0) {
            console.log('Envoi des fichiers...');
            const success = await chatFileHandler.sendFiles(selectedUserId, content);
            if (!success) {
                console.error('Échec de l\'envoi des fichiers');
                showNotification('Échec de l\'envoi des fichiers', 'error');
                return false;
            }
        } else if (socket && socket.connected) {
            console.log('Envoi du message via socket:', messageData);
            
            // Promesse pour attendre la confirmation d'envoi
            const sendPromise = new Promise((resolve, reject) => {
                // Timeout de 5 secondes
                const timeout = setTimeout(() => {
                    reject(new Error('Délai d\'envoi dépassé'));
                }, 5000);
                
                // Gestionnaire de réponse
                socket.once('messageSent', (response) => {
                    clearTimeout(timeout);
                    resolve(response);
                });
                
                // Envoi du message
                socket.emit('sendMessage', messageData);
            });
            
            // Attendre la confirmation avec un délai
            try {
                await sendPromise;
            } catch (socketError) {
                console.error('Erreur lors de l\'envoi via socket:', socketError);
                showNotification('Erreur de communication, message sauvegardé localement', 'warning');
                
                // Sauvegarde dans la file d'attente
                messageQueue.push(messageData);
            }
        } else {
            console.log('Message mis en file d\'attente:', messageData);
            messageQueue.push(messageData);
            showNotification('Message en attente de connexion', 'info');
            
            if (!socket || !socket.connected) {
                connectSocket();
            }
        }

        // Afficher le message immédiatement côté client
        displayMessage({
            type: 'message',
            senderId: currentUserId,
            receiverId: selectedUserId,
            content: content,
            timestamp: new Date()
        });

        return true;
    } catch (error) {
        console.error('Erreur lors de l\'envoi du message:', error);
        showNotification('Erreur lors de l\'envoi du message: ' + error.message, 'error');
        
        // Sauvegarder le message pour retenter plus tard
        messageQueue.push(messageData);
        return false;
    }
}

// Fonction pour vérifier les paramètres d'URL
function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const userParam = urlParams.get('user');
    
    if (userParam) {
        console.log('Utilisateur sélectionné depuis l\'URL:', userParam);
        // Sélectionner l'utilisateur après chargement des conversations
        setTimeout(() => {
            selectConversation(userParam);
        }, 100);
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('Document prêt, initialisation du chat...');
    
    // Récupérer l'ID de l'utilisateur courant
    currentUserId = window.currentUserId;
    if (!currentUserId) {
        console.error('ID utilisateur non trouvé');
        return;
    }

    // Charger les conversations (nouvelle fonction pour persistence)
    loadConversations();
    
    // Vérifier les paramètres d'URL
    checkUrlParameters();
    
    // Gestionnaire de soumission du formulaire
    const messageForm = document.getElementById('messageForm');
    if (messageForm) {
        messageForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('Formulaire soumis');
            
            const input = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');
            
            if (!input || !sendButton) {
                console.error('Éléments du formulaire non trouvés');
                return;
            }
            
            const content = input.value.trim();
            console.log('Contenu du message:', content);
            
            // Désactiver le bouton pendant l'envoi
            sendButton.disabled = true;
            
            try {
                if (await sendMessage(content)) {
                    input.value = '';
                    input.focus();
                }
            } finally {
                // Réactiver le bouton
                sendButton.disabled = false;
            }
        });
    } else {
        console.error('Formulaire non trouvé');
    }

    // Initialiser le chat
    connectSocket();
    loadExistingMessages();
});

// Fonction pour afficher une notification
function showNotification(message, type = 'info') {
    Swal.fire({
        icon: type,
        title: type.charAt(0).toUpperCase() + type.slice(1),
        text: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
}

// Fonction pour démarrer une conversation
function startConversation(userId) {
    selectedUserId = userId;
    // Charger les messages de la conversation
    loadExistingMessages();
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    // Récupérer l'ID de l'utilisateur courant depuis le script inline
    currentUserId = window.currentUserId;
    if (!currentUserId) {
        console.error('ID utilisateur non trouvé');
        return;
    }

    connectSocket();
    loadExistingMessages();
});

// Fonction pour sélectionner une conversation
function selectConversation(userId) {
    if (!userId || userId === selectedUserId) {
        return;
    }
    
    // Mettre à jour l'utilisateur sélectionné
    selectedUserId = userId;
    
    // Mise à jour visuelle des éléments de conversation
    const conversationItems = document.querySelectorAll('.conversation-item');
    conversationItems.forEach(item => {
        if (item.getAttribute('data-user-id') == userId) {
            item.classList.add('active');
            
            // Supprimer le badge non lu
            const unreadBadge = item.querySelector('.unread-badge');
            if (unreadBadge) {
                unreadBadge.remove();
            }
        } else {
            item.classList.remove('active');
        }
    });
    
    // Charger les informations de l'utilisateur
    loadUserInfo(userId);
    
    // Charger les messages
    loadExistingMessages();
    
    // Informer le serveur que l'utilisateur lit cette conversation
    if (socket && socket.connected) {
        socket.emit('readConversation', {
            otherUserId: userId
        });
    }
    
    // Mettre à jour l'URL pour refléter la conversation sélectionnée
    const newUrl = new URL(window.location.href);
    newUrl.searchParams.set('user', userId);
    window.history.pushState({}, '', newUrl);
}

// Fonction pour charger les informations de l'utilisateur
async function loadUserInfo(userId) {
    try {
        // Mise à jour de l'interface
        const selectedUserInfo = document.querySelector('.selected-user-info');
        if (selectedUserInfo) {
            selectedUserInfo.querySelector('h3').textContent = 'Chargement...';
        }
        
        // Récupérer les informations de l'utilisateur
        const response = await fetch(`/api/user-info.php?user_id=${userId}`);
        if (response.ok) {
            const data = await response.json();
            
            if (data.success && data.user) {
                // Mettre à jour l'interface
                if (selectedUserInfo) {
                    const avatar = selectedUserInfo.querySelector('.avatar');
                    const name = selectedUserInfo.querySelector('h3');
                    
                    if (avatar) {
                        avatar.src = data.user.profile_image || 'assets/images/default-avatar.png';
                    }
                    
                    if (name) {
                        name.textContent = `${data.user.first_name} ${data.user.last_name}`;
                    }
                }
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement des informations de l\'utilisateur:', error);
    }
} 