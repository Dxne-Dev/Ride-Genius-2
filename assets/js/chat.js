// Établir la connexion WebSocket
const wsProtocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
const wsUrl = `${wsProtocol}//${window.location.hostname}:3000/chat`;
let ws = null;
let reconnectAttempts = 0;
const maxReconnectAttempts = 5;

// File d'attente pour les messages pendant la déconnexion
let messageQueue = [];

// Fonction pour établir la connexion WebSocket
function connectWebSocket() {
    try {
        ws = new WebSocket(wsUrl);

        ws.onopen = () => {
            console.log('Connecté au serveur WebSocket');
            // Authentifier l'utilisateur
            ws.send(JSON.stringify({
                type: 'auth',
                userId: currentUserId
            }));

            // Envoyer les messages en attente
            while (messageQueue.length > 0) {
                const message = messageQueue.shift();
                ws.send(JSON.stringify(message));
            }
        };

        ws.onmessage = (event) => {
            console.log('Message reçu:', event.data);
            const data = JSON.parse(event.data);
            if (data.type === 'message') {
                displayMessage(data);
            }
        };

        ws.onclose = () => {
            console.log('Déconnecté du serveur WebSocket');
            if (reconnectAttempts < maxReconnectAttempts) {
                setTimeout(() => {
                    reconnectAttempts++;
                    connectWebSocket();
                }, 3000); // Attendre 3 secondes avant de reconnecter
            }
        };

        ws.onerror = (error) => {
            console.error('Erreur WebSocket:', error);
        };
    } catch (error) {
        console.error('Erreur lors de la connexion:', error);
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
            type: 'message',
            senderId: currentUserId,
            receiverId: receiverId,
            content: content,
            timestamp: new Date().toISOString()
        };

        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify(messageData));
        } else {
            messageQueue.push(messageData);
            // Tenter de reconnecter si déconnecté
            if (!ws || ws.readyState === WebSocket.CLOSED) {
                connectWebSocket();
            }
        }

        // Afficher le message immédiatement
        displayMessage({
            ...messageData,
            id: Date.now() // ID temporaire
        });

        input.value = '';
        input.focus();
    }
});

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    connectWebSocket();
    loadExistingMessages();
}); 