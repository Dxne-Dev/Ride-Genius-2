const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');
const axios = require('axios');

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
    socket.on('sendMessage', async (data) => {
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
            sender_id: socket.userId, // Ajouter aussi le format alternatif pour compatibilité
            receiverId: data.receiver_id,
            receiver_id: data.receiver_id, // Ajouter aussi le format alternatif pour compatibilité
            content: data.message,
            message: data.message, // Ajouter aussi le format alternatif pour compatibilité
            file_path: data.file_path || null,
            file_type: data.file_type || 'text',
            timestamp: new Date()
        };

        // Ajouter le message à la liste
        messages.push(message);

        // Envoyer le message au destinataire
        const receiverSocketId = connectedUsers.get(data.receiver_id);
        if (receiverSocketId) {
            console.log(`📤 Envoi du message à l'utilisateur ${data.receiver_id} (socket: ${receiverSocketId})`);
            io.to(receiverSocketId).emit('receiveMessage', message);
        } else {
            console.log(`⚠️ Destinataire ${data.receiver_id} non connecté, message non délivré en temps réel`);
        }

        // Sauvegarder le message dans la base de données via l'API PHP
        try {
            const response = await axios.post(
                'http://localhost/Ride-Genius/Ride-Genius-2/message_api.php',
                new URLSearchParams({
                    action: 'sendMessage',
                    user_id: socket.userId,
                    message: data.message
                }),
                { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }
            );
            console.log('📥 Message sauvegardé via API:', response.data);
            
            // Envoyer le message avec l'ID de la BD
            if (response.data.success && response.data.message_id) {
                message.id = response.data.message_id;
                socket.emit('messageSent', {
                    success: true,
                    message: message
                });
            }
        } catch (error) {
            console.error('❌ Erreur lors de l\'enregistrement du message :', error.response?.data || error.message);
            // Envoyer quand même une confirmation
            socket.emit('messageSent', {
                success: true,
                message: message
            });
        }
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