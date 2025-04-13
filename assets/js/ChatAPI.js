class ChatAPI {
    constructor(apiEndpoint, socketUrl) {
        this.apiEndpoint = apiEndpoint;
        this.socketUrl = socketUrl;
        this.socket = null;
        this.messageQueue = [];
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 2000;
        this.token = window.USER_TOKEN;
        this.userId = window.USER_ID;
        console.log('ChatAPI initialisé avec:', { 
            apiEndpoint, 
            socketUrl,
            hasToken: !!this.token,
            hasUserId: !!this.userId
        });
    }

    connectSocket(userData, onConnect, onDisconnect, onMessage) {
        console.log('Tentative de connexion socket avec:', userData);
        
        try {
            this.socket = io(this.socketUrl, {
                transports: ['websocket'],
                reconnection: true,
                reconnectionDelay: this.reconnectDelay,
                reconnectionAttempts: this.maxReconnectAttempts
            });

            this.socket.on('connect', () => {
                console.log('Socket connecté, envoi des données utilisateur');
                this.socket.emit('auth', {
                    userId: userData.userId,
                    token: userData.token
                }, (response) => {
                    if (response.success) {
                        this.token = userData.token;
                        this.reconnectAttempts = 0;
                        onConnect();
                        this.flushMessageQueue();
                    } else {
                        console.error('Erreur d\'authentification:', response.message);
                        onDisconnect();
                    }
                });
            });

            this.socket.on('disconnect', (reason) => {
                console.log('Socket déconnecté, raison:', reason);
                onDisconnect();
            });

            this.socket.on('receiveMessage', (message) => {
                console.log('Message reçu:', message);
                onMessage(message);
            });

            this.socket.on('connect_error', (error) => {
                console.error('Erreur connexion socket:', error);
                this.reconnectAttempts++;
                if (this.reconnectAttempts >= this.maxReconnectAttempts) {
                    console.error('Nombre maximum de tentatives atteint');
                    onDisconnect();
                }
            });

            this.socket.on('error', (error) => {
                console.error('Erreur socket:', error);
            });

        } catch (error) {
            console.error('Erreur création socket:', error);
        }
    }

    async apiRequest(method, endpoint, data = null) {
        if (!this.token || !this.userId) {
            throw new Error('Token ou ID utilisateur manquant');
        }

        console.log('API Request:', { method, endpoint, data });
        try {
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };

            let url = `${this.apiEndpoint}${endpoint}`;

            if (method === 'POST' && (!data || !data.action)) {
                console.error('Tentative de requête POST sans action:', { method, endpoint, data });
                throw new Error('Action non spécifiée dans la requête POST');
            }

            if (method === 'GET') {
                const separator = url.includes('?') ? '&' : '?';
                url += `${separator}token=${this.token}&sender_id=${this.userId}`;
            } else if (data) {
                options.body = JSON.stringify({
                    ...data,
                    token: this.token,
                    sender_id: this.userId
                });
            }

            console.log('Requête complète:', { url, options });

            const response = await fetch(url, options);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            console.log('Réponse API:', result);
            return result;
        } catch (error) {
            console.error('Erreur API:', error);
            throw error;
        }
    }

    emitMessage(message) {
        console.log('Tentative d\'émission du message:', message);
        if (this.socket && this.socket.connected) {
            console.log('Socket connecté, envoi immédiat');
            this.socket.emit('sendMessage', message);
        } else {
            console.log('Socket non connecté, mise en file d\'attente');
            this.messageQueue.push(message);
        }
    }

    socketRequest(event, data, callback = () => {}) {
        if (this.socket && this.socket.connected) {
            console.log(`Envoi de l'événement ${event}:`, data);
            this.socket.emit(event, data, callback);
        } else {
            console.log(`Socket non connecté, événement ${event} ignoré`);
        }
    }

    flushMessageQueue() {
        console.log('Vidage de la file d\'attente:', this.messageQueue.length, 'messages');
        if (this.socket && this.socket.connected) {
            while (this.messageQueue.length > 0) {
                const message = this.messageQueue.shift();
                console.log('Envoi du message en attente:', message);
                this.emitMessage(message);
            }
        }
    }

    disconnect() {
        if (this.socket) {
            console.log('Déconnexion du socket');
            this.socket.disconnect();
        }
    }
}