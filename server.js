const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const axios = require('axios');

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: '*' } });
const connectedUsers = new Map();

io.on('connection', (socket) => {
    console.log('Nouvelle connexion:', socket.id);

    socket.on('auth', (data) => {
        if (!data.userId) {
            socket.emit('error', { message: 'ID utilisateur requis' });
            return;
        }
        socket.userId = data.userId;
        connectedUsers.set(data.userId.toString(), socket.id);
        console.log('Utilisateur authentifié:', data.userId);
    });

    socket.on('sendMessage', async (data, callback) => {
        if (!data.conversation_id || !data.content) {
            callback({ success: false, message: 'Données manquantes' });
            return;
        }

        try {
            const response = await axios.post('http://ride-genius/message_api.php', {
                action: 'sendMessage',
                sender_id: socket.userId,
                conversation_id: data.conversation_id,
                content: data.content,
                attachments: data.attachments || []
            }, { headers: { 'Content-Type': 'application/json' } });

            if (response.data.success) {
                const message = {
                    id: response.data.message_id,
                    conversation_id: data.conversation_id,
                    sender_id: socket.userId,
                    content: data.content,
                    created_at: new Date().toISOString(),
                    attachments: response.data.attachments || []
                };

                // Récupérer la conversation pour trouver le destinataire
                const convResponse = await axios.get('http://ride-genius/message_api.php', {
                    params: {
                        action: 'getConversation',
                        conversation_id: data.conversation_id,
                        sender_id: socket.userId
                    }
                });

                if (convResponse.data.success) {
                    const conv = convResponse.data.conversation;
                    const receiverId = conv.user1_id == socket.userId ? conv.user2_id : conv.user1_id;
                    const receiverSocket = connectedUsers.get(receiverId.toString());
                    if (receiverSocket) {
                        io.to(receiverSocket).emit('receiveMessage', message);
                    }
                }

                callback({ success: true, message_id: message.id });
            } else {
                callback({ success: false, message: response.data.message });
            }
        } catch (error) {
            console.error('Erreur d\'envoi:', error);
            callback({ success: false, message: 'Erreur serveur' });
        }
    });

    socket.on('sendReaction', async (data, callback) => {
        if (!data.message_id || !data.reaction || !data.conversation_id) {
            callback({ success: false, message: 'Données manquantes' });
            return;
        }

        try {
            const reaction = {
                message_id: data.message_id,
                reaction: data.reaction,
                conversation_id: data.conversation_id,
                user_id: socket.userId
            };

            const response = await axios.post('http://ride-genius/message_api.php', {
                action: 'addReaction',
                message_id: data.message_id,
                reaction: data.reaction,
                sender_id: socket.userId
            }, { headers: { 'Content-Type': 'application/json' } });

            if (response.data.success) {
                const convResponse = await axios.get('http://ride-genius/message_api.php', {
                    params: {
                        action: 'getConversation',
                        conversation_id: data.conversation_id,
                        sender_id: socket.userId
                    }
                });

                if (convResponse.data.success) {
                    const conv = convResponse.data.conversation;
                    const receiverId = conv.user1_id == socket.userId ? conv.user2_id : conv.user1_id;
                    const receiverSocket = connectedUsers.get(receiverId.toString());
                    if (receiverSocket) {
                        io.to(receiverSocket).emit('receiveReaction', reaction);
                    }
                }

                callback({ success: true });
            } else {
                callback({ success: false, message: response.data.message });
            }
        } catch (error) {
            console.error('Erreur de réaction:', error);
            callback({ success: false, message: 'Erreur serveur' });
        }
    });

    socket.on('disconnect', () => {
        if (socket.userId) {
            connectedUsers.delete(socket.userId.toString());
            console.log('Utilisateur déconnecté:', socket.userId);
        }
    });
});

server.listen(3000, () => console.log('Serveur WebSocket démarré sur le port 3000'));