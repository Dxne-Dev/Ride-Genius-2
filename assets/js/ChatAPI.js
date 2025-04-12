class ChatAPI {
    constructor(baseUrl, socketUrl) {
        this.apiBaseUrl = baseUrl;
        this.socketUrl = socketUrl;
        this.socket = null;
        this.messageQueue = [];
    }

    connectSocket(authData, onConnect, onDisconnect, onMessage, onReaction) {
        this.socket = io(this.socketUrl, {
            transports: ['websocket'],
            reconnection: true,
            reconnectionAttempts: 5,
            reconnectionDelay: 1000,
        });

        this.socket.on('connect', () => {
            this.socket.emit('auth', authData);
            this.flushMessageQueue();
            onConnect();
        });

        this.socket.on('disconnect', onDisconnect);

        this.socket.on('receiveMessage', onMessage);

        this.socket.on('receiveReaction', onReaction);
    }

    async apiRequest(method, endpoint, data = null) {
        console.log('Début apiRequest:', { method, endpoint, data });
        
        const url = `${this.apiBaseUrl}${endpoint}`;
        console.log('URL complète:', url);
        
        const options = {
            method,
            credentials: 'include'
        };
        
        if (data) {
            if (data instanceof FormData) {
                options.body = data;
            } else {
                options.headers = {
                    'Content-Type': 'application/json'
                };
                options.body = JSON.stringify(data);
            }
        }
        
        console.log('Options de la requête:', options);
        
        try {
            const response = await fetch(url, options);
            console.log('Réponse reçue:', response);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('Résultat JSON:', result);
            return result;
        } catch (error) {
            console.error('Erreur apiRequest:', error);
            throw error;
        }
    }

    socketRequest(event, data) {
        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => reject(new Error('Délai dépassé')), 5000);
            this.socket.emit(event, data, (response) => {
                clearTimeout(timeout);
                resolve(response);
            });
        });
    }

    queueMessage(data) {
        this.messageQueue.push(data);
    }

    flushMessageQueue() {
        while (this.messageQueue.length) {
            const msg = this.messageQueue.shift();
            this.socket.emit('sendMessage', msg);
        }
    }
}