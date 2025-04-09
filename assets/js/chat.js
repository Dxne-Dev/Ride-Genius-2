// Initialiser la connexion Socket.IO
let socket = null;

// File d'attente pour les messages pendant la déconnexion
let messageQueue = [];

// Fonction pour établir la connexion Socket.IO
function connectSocket() {
    try {
        // Connexion au serveur Socket.IO
        socket = io('http://localhost:3000', {
            reconnection: true,
            reconnectionAttempts: 5,
            reconnectionDelay: 3000
        });

        // Gestion de la connexion
        socket.on('connect', () => {
            console.log('✅ Connecté au serveur Socket.IO');
            
            // Authentifier l'utilisateur
            socket.emit('auth', {
                userId: currentUserId
            });

            // Envoyer les messages en attente
            while (messageQueue.length > 0) {
                const message = messageQueue.shift();
                socket.emit('sendMessage', message);
            }
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
        });

        // Confirmation d'envoi de message
        socket.on('messageSent', (response) => {
            console.log('✅ Message envoyé avec succès:', response);
        });

        // Gestion de la déconnexion
        socket.on('disconnect', () => {
            console.log('❌ Déconnecté du serveur Socket.IO');
        });

        socket.on('error', (error) => {
            console.error('❌ Erreur Socket.IO:', error);
        });

    } catch (error) {
        console.error('❌ Erreur lors de la connexion:', error);
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

    // Jouer le son de notification pour les messages reçus
    if (!isSent) {
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
    try {
        const response = await fetch(`/api/messages.php?receiver_id=${receiverId}`);
        const data = await response.json();
        
        if (data.status === 'success' && Array.isArray(data.messages)) {
            data.messages.forEach(message => displayMessage(message));
            scrollToBottom();
        }
    } catch (error) {
        console.error('Erreur lors du chargement des messages:', error);
    }
}

// Gestionnaire de soumission du formulaire
document.getElementById('message-form').addEventListener('submit', (e) => {
    e.preventDefault();
    
    const input = document.getElementById('message-input');
    const content = input.value.trim();
    
    if (content) {
        const messageData = {
            receiver_id: receiverId,
            message: content
        };

        if (socket && socket.connected) {
            console.log('🚀 Envoi du message:', messageData);
            socket.emit('sendMessage', messageData);
        } else {
            console.log('⏳ Message mis en file d\'attente:', messageData);
            messageQueue.push(messageData);
            // Tenter de reconnecter si déconnecté
            if (!socket || !socket.connected) {
                connectSocket();
            }
        }

        // Afficher le message immédiatement côté client
        displayMessage({
            type: 'message',
            senderId: currentUserId,
            receiverId: receiverId,
            content: content,
            timestamp: new Date()
        });

        input.value = '';
        input.focus();
    }
});

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    connectSocket();
    loadExistingMessages();
}); 