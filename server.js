const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const axios = require('axios');

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: '*' } });
const connectedUsers = new Map();

// Fonction pour faire les requêtes API avec le token
async function makeApiRequest(method, endpoint, data = {}, token) {
    try {
        const config = {
            method,
            url: `http://ride-genius/message_api.php${endpoint}`,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        };

        if (method === 'get') {
            config.params = { ...data, token };
        } else {
            config.data = { ...data, token };
        }

        const response = await axios(config);
        return response.data;
    } catch (error) {
        console.error('Erreur API:', error.response?.data || error.message);
        throw error;
    }
}

io.on('connection', (socket) => {
    console.log('Nouvelle connexion:', socket.id);

    socket.on('auth', async (data, callback) => {
        if (!data.userId || !data.token) {
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Données d\'authentification manquantes' });
            }
            return;
        }

        try {
            // Vérifier le token avec l'API
            const response = await makeApiRequest('get', '?action=verifyToken', { 
                user_id: data.userId 
            }, data.token);

            if (response.success) {
                socket.userId = data.userId;
                socket.token = data.token;
                connectedUsers.set(data.userId.toString(), socket.id);
                console.log('Utilisateur authentifié:', data.userId);
                
                if (typeof callback === 'function') {
                    callback({ success: true });
                }
            } else {
                if (typeof callback === 'function') {
                    callback({ success: false, message: 'Token invalide' });
                }
            }
        } catch (error) {
            console.error('Erreur d\'authentification:', error);
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Erreur d\'authentification' });
            }
        }
    });

    socket.on('sendMessage', async (data, callback) => {
        if (!socket.token || !socket.userId) {
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Non authentifié' });
            }
            return;
        }

        if (!data.conversation_id || !data.content) {
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Données manquantes' });
            }
            return;
        }

        try {
            const response = await makeApiRequest('post', '', {
                action: 'sendMessage',
                sender_id: socket.userId,
                conversation_id: data.conversation_id,
                content: data.content,
                attachments: data.attachments || []
            }, socket.token);

            if (response.success) {
                const message = {
                    id: response.message_id,
                    conversation_id: data.conversation_id,
                    sender_id: socket.userId,
                    content: data.content,
                    created_at: new Date().toISOString(),
                    attachments: response.attachments || []
                };

                // Récupérer la conversation pour trouver le destinataire
                const convResponse = await makeApiRequest('get', '?action=getConversation', {
                    conversation_id: data.conversation_id,
                    sender_id: socket.userId
                }, socket.token);

                if (convResponse.success) {
                    const conv = convResponse.conversation;
                    const receiverId = conv.user1_id == socket.userId ? conv.user2_id : conv.user1_id;
                    const receiverSocket = connectedUsers.get(receiverId.toString());
                    if (receiverSocket) {
                        io.to(receiverSocket).emit('receiveMessage', message);
                    }
                }

                if (typeof callback === 'function') {
                    callback({ success: true, message_id: message.id });
                }
            } else {
                if (typeof callback === 'function') {
                    callback({ success: false, message: response.message });
                }
            }
        } catch (error) {
            console.error('Erreur d\'envoi:', error);
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Erreur serveur' });
            }
        }
    });

    socket.on('sendReaction', async (data, callback) => {
        if (!socket.token || !socket.userId) {
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Non authentifié' });
            }
            return;
        }

        if (!data.message_id || !data.reaction || !data.conversation_id) {
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Données manquantes' });
            }
            return;
        }

        try {
            const reaction = {
                message_id: data.message_id,
                reaction: data.reaction,
                conversation_id: data.conversation_id,
                user_id: socket.userId
            };

            const response = await makeApiRequest('post', '', {
                action: 'addReaction',
                message_id: data.message_id,
                reaction: data.reaction,
                sender_id: socket.userId
            }, socket.token);

            if (response.success) {
                const convResponse = await makeApiRequest('get', '?action=getConversation', {
                    conversation_id: data.conversation_id,
                    sender_id: socket.userId
                }, socket.token);

                if (convResponse.success) {
                    const conv = convResponse.conversation;
                    const receiverId = conv.user1_id == socket.userId ? conv.user2_id : conv.user1_id;
                    const receiverSocket = connectedUsers.get(receiverId.toString());
                    if (receiverSocket) {
                        io.to(receiverSocket).emit('receiveReaction', reaction);
                    }
                }

                if (typeof callback === 'function') {
                    callback({ success: true });
                }
            } else {
                if (typeof callback === 'function') {
                    callback({ success: false, message: response.message });
                }
            }
        } catch (error) {
            console.error('Erreur de réaction:', error);
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Erreur serveur' });
            }
        }
    });

    socket.on('disconnect', () => {
        if (socket.userId) {
            connectedUsers.delete(socket.userId.toString());
            console.log('Utilisateur déconnecté:', socket.userId);
        }
    });

    socket.on('startCall', async (data, callback) => {
        if (!socket.token || !socket.userId) {
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Non authentifié' });
            }
            return;
        }

        if (!data.conversation_id || !data.call_type) {
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Données manquantes' });
            }
            return;
        }

        try {
            // Vérifier si l'utilisateur est déjà en appel
            const activeCall = Array.from(connectedUsers.entries()).find(([userId, socketId]) => {
                const userSocket = io.sockets.sockets.get(socketId);
                return userSocket?.isCalling;
            });

            if (activeCall) {
                if (typeof callback === 'function') {
                    callback({ success: false, message: 'Un appel est déjà en cours' });
                }
                return;
            }

            const response = await makeApiRequest('post', '', {
                action: 'startCall',
                conversation_id: data.conversation_id,
                call_type: data.call_type,
                sender_id: socket.userId
            }, socket.token);

            if (response && response.success) {
                // Récupérer la conversation pour trouver le destinataire
                const convResponse = await makeApiRequest('get', '?action=getConversation', {
                    conversation_id: data.conversation_id,
                    sender_id: socket.userId
                }, socket.token);

                if (convResponse && convResponse.success) {
                    const conv = convResponse.conversation;
                    const receiverId = conv.user1_id == socket.userId ? conv.user2_id : conv.user1_id;
                    const receiverSocket = connectedUsers.get(receiverId.toString());
                    
                    if (receiverSocket) {
                        // Marquer l'appelant comme étant en appel
                        socket.isCalling = true;
                        
                        // Envoyer la notification d'appel au destinataire
                        io.to(receiverSocket).emit('incomingCall', {
                            call_id: response.call_id,
                            caller_id: socket.userId,
                            conversation_id: data.conversation_id,
                            type: data.call_type,
                            caller_name: conv.user1_id == socket.userId ? conv.user2_name : conv.user1_name,
                            caller_avatar: conv.user1_id == socket.userId ? conv.user2_avatar : conv.user1_avatar
                        });
                    }
                }

                if (typeof callback === 'function') {
                    callback({ success: true, call_id: response.call_id });
                }
            } else {
                if (typeof callback === 'function') {
                    callback({ success: false, message: response?.message || 'Erreur serveur' });
                }
            }
        } catch (error) {
            console.error('Erreur startCall:', error);
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Erreur serveur' });
            }
        }
    });

    socket.on('answerCall', async (data, callback) => {
        if (!socket.token || !socket.userId) {
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Non authentifié' });
            }
            return;
        }

        if (!data.call_id || !data.answer) {
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Données manquantes' });
            }
            return;
        }

        try {
            const response = await makeApiRequest('post', '', {
                action: 'answerCall',
                call_id: data.call_id,
                answer: data.answer,
                user_id: socket.userId
            }, socket.token);

            if (response.success) {
                // Notifier l'appelant de la réponse
                const callerSocket = connectedUsers.get(response.caller_id.toString());
                if (callerSocket) {
                    io.to(callerSocket).emit('callAnswered', {
                        call_id: data.call_id,
                        answer: data.answer
                    });
                }

                if (typeof callback === 'function') {
                    callback({ success: true });
                }
            } else {
                if (typeof callback === 'function') {
                    callback({ success: false, message: response.message });
                }
            }
        } catch (error) {
            console.error('Erreur answerCall:', error);
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Erreur serveur' });
            }
        }
    });

    socket.on('endCall', async (data, callback) => {
        if (!socket.token || !socket.userId) {
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Non authentifié' });
            }
            return;
        }

        if (!data.call_id) {
            if (typeof callback === 'function') {
                callback({ success: false, message: 'ID d\'appel manquant' });
            }
            return;
        }

        try {
            const response = await makeApiRequest('post', '', {
                action: 'endCall',
                call_id: data.call_id,
                sender_id: socket.userId
            }, socket.token);

            if (response.success) {
                socket.isCalling = false; // Réinitialisation du statut d'appel
                // Notifier l'autre participant
                const otherUserId = response.caller_id == socket.userId ? response.receiver_id : response.caller_id;
                const otherSocket = connectedUsers.get(otherUserId.toString());
                if (otherSocket) {
                    io.to(otherSocket).emit('callEnded', {
                        call_id: data.call_id
                    });
                }

                if (typeof callback === 'function') {
                    callback({ success: true });
                }
            } else {
                if (typeof callback === 'function') {
                    callback({ success: false, message: response.message });
                }
            }
        } catch (error) {
            console.error('Erreur endCall:', error);
            if (typeof callback === 'function') {
                callback({ success: false, message: 'Erreur serveur' });
            }
        }
    });
});

server.listen(3000, () => console.log('Serveur WebSocket démarré sur le port 3000'));