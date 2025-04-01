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

// Stockage temporaire des messages (à remplacer par une base de données)
let messages = [];

// Gérer les connexions Socket.IO
io.on('connection', (socket) => {
    console.log('Un utilisateur est connecté :', socket.id);

    // Envoyer les messages existants au nouvel utilisateur
    socket.emit('loadMessages', messages);

    // Écouter les messages envoyés par le client
    socket.on('sendMessage', (data) => {
        const message = {
            sender_id: data.sender_id,
            receiver_id: data.receiver_id,
            message: data.message,
            file_path: data.file_path || null,
            file_type: data.file_type || 'text',
            timestamp: new Date()
        };

        // Ajouter le message à la liste
        messages.push(message);

        // Diffuser le message à tous les utilisateurs connectés
        io.emit('receiveMessage', message);
    });

    // Gérer la déconnexion
    socket.on('disconnect', () => {
        console.log('Un utilisateur s\'est déconnecté :', socket.id);
    });
});

app.use(express.static('views/messages')); // Serve static files from the messages directory
app.use(express.static('views/auth')); // Serve static files from the auth directory
const PORT = 3000;
server.listen(PORT, () => {
    console.log(`Serveur Socket.IO en cours d'exécution sur le port ${PORT}`);
});