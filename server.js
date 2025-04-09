const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');

// Initialiser l'application Express
const app = express();
const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: '*', // Autoriser toutes les origines (à restreindre en production)
        methods: ['GET', 'POST']
    }
});

// Middleware pour gérer les requêtes CORS
app.use(cors());

// Stockage temporaire des messages et utilisateurs connectés
let messages = [];
let connectedUsers = new Map();

// Gérer les connexions Socket.IO
io.on('connection', (socket) => {
    console.log('✅ Nouvelle connexion Socket.IO :', socket.id);

    // Debug: Lister tous les événements reçus
    const originalOn = socket.on;
    socket.on = function(event, handler) {
        console.log('🎯 Event listener ajouté pour:', event);
        return originalOn.call(this, event, (...args) => {
            console.log(`📡 Event "${event}" reçu avec les données:`, args);
            return handler.apply(this, args);
        });
    };

    // Authentification de l'utilisateur
    socket.on('auth', (data) => {
        console.log('🔐 Authentification de l\'utilisateur:', {
            userId: data.userId,
            socketId: socket.id
        });
        socket.userId = data.userId;
        connectedUsers.set(data.userId, socket.id);
        
        // Envoyer les messages existants à l'utilisateur
        socket.emit('loadMessages', messages);
        console.log('📚 Messages existants envoyés à l\'utilisateur');
    });

    // Écouter les messages envoyés par le client
    socket.on('sendMessage', (data) => {
        console.log('📨 Message reçu du client:', {
            senderId: socket.userId,
            receiverId: data.receiver_id,
            content: data.message,
            rawData: data, // Log des données brutes pour debug
            file: data.file_path ? {
                path: data.file_path,
                type: data.file_type
            } : null
        });

        const message = {
            type: 'message',
            senderId: socket.userId,
            receiverId: data.receiver_id,
            content: data.message,
            file_path: data.file_path || null,
            file_type: data.file_type || 'text',
            timestamp: new Date()
        };

        // Ajouter le message à la liste
        messages.push(message);

        // Envoyer le message au destinataire
        const receiverSocketId = connectedUsers.get(data.receiver_id);
        if (receiverSocketId) {
            io.to(receiverSocketId).emit('receiveMessage', message);
        }

        // Confirmer la réception au sender
        socket.emit('messageSent', {
            success: true,
            message: message
        });
    });

    // Gérer les appels
    socket.on('call-offer', (data) => {
        const receiverSocketId = connectedUsers.get(data.target);
        if (receiverSocketId) {
            io.to(receiverSocketId).emit('call-offer', {
                offer: data.offer,
                caller: socket.userId,
                isVideo: data.isVideo
            });
        }
    });

    // Gérer la déconnexion
    socket.on('disconnect', () => {
        console.log('Un utilisateur s\'est déconnecté :', socket.id);
        if (socket.userId) {
            connectedUsers.delete(socket.userId);
        }
    });
});

// Servir les fichiers statiques
app.use(express.static('views/messages'));
app.use(express.static('views/auth'));

// Démarrer le serveur
const PORT = 3000;
server.listen(PORT, () => {
    console.log(`Serveur Socket.IO en cours d'exécution sur le port ${PORT}`);
});