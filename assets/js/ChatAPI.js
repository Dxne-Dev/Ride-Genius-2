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

    async apiRequest(method, url, body = null) {
        const options = { method, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } };
        if (body instanceof FormData) options.body = body;
        else if (body) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
        const response = await fetch(`${this.apiBaseUrl}${url}`, options);
        if (response.status === 401) {
            window.location.href = 'index.php?page=login';
            throw new Error('Session expirée');
        }
        return response.json();
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