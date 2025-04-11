<?php
// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

// Récupération des informations de l'utilisateur connecté
$currentUser = $this->user->findById($_SESSION['user_id']);
if (!$currentUser) {
    $_SESSION['error'] = "Utilisateur non trouvé";
    header('Location: index.php?page=login');
    exit;
}

// Récupération des conversations via le contrôleur
$conversationsResult = $this->getConversations($_SESSION['user_id']);
$conversations = $conversationsResult['success'] ? $conversationsResult['conversations'] : [];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Ride Genius</title>
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="assets/css/chat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.socket.io/4.8.1/socket.io.min.js"></script>
</head>
<body>
    <div class="chat-container">
        <!-- Sidebar des conversations -->
        <div class="chat-sidebar">
            <div class="chat-header">
                <h2>Messages</h2>
                <div class="search-box">
                    <input type="text" id="searchUsers" placeholder="Rechercher dans Messenger">
                    <i class="fas fa-search"></i>
                    <div class="search-results">
                        <!-- Les résultats de recherche seront affichés ici -->
                    </div>
                </div>
            </div>
            <div class="conversations-list">
                <?php foreach ($conversations as $conv): ?>
                    <div class="conversation-item" data-user-id="<?php echo $conv['other_user_id']; ?>">
                        <div class="user-info">
                            <img src="<?php echo $conv['profile_image'] ?? 'assets/images/default-avatar.png'; ?>" alt="Avatar" class="avatar">
                            <div class="user-details">
                                <h4><?php echo htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']); ?></h4>
                                <p class="last-message">
                                    <?php 
                                    if ($conv['last_message']) {
                                        echo htmlspecialchars(mb_strimwidth($conv['last_message'], 0, 30, "..."));
                                    } else {
                                        echo 'Démarrez une conversation';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                        <div class="conversation-meta">
                            <?php if ($conv['last_message_at']): ?>
                                <span class="time"><?php echo date('H:i', strtotime($conv['last_message_at'])); ?></span>
                            <?php endif; ?>
                            <?php if ($conv['unread_count'] > 0): ?>
                                <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Zone de chat principale -->
        <div class="chat-main">
            <div class="selected-user-info">
                <div class="user-info">
                    <img src="assets/images/default-avatar.png" alt="Avatar" class="avatar">
                    <h3>Sélectionnez une conversation</h3>
                </div>
                <div class="chat-actions">
                    <button class="chat-action-btn" id="audioCallBtn" title="Appel audio">
                        <i class="fas fa-phone"></i>
                    </button>
                    <button class="chat-action-btn" id="videoCallBtn" title="Appel vidéo">
                        <i class="fas fa-video"></i>
                    </button>
                    <button class="chat-action-btn" id="infoBtn" title="Informations">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <!-- Les messages seront chargés ici -->
            </div>
            
            <div class="chat-input">
                <form id="messageForm" class="message-form">
                    <div class="message-attachments" id="messageAttachments">
                        <!-- Les fichiers attachés seront affichés ici -->
                    </div>
                    <div class="input-group">
                        <button type="button" class="attach-btn" id="attachButton">
                            <i class="fas fa-paperclip"></i>
                    </button>
                        <input type="text" id="messageInput" placeholder="Écrivez votre message..." autocomplete="off">
                        <input type="file" id="fileInput" style="display: none" multiple>
                    <button type="submit">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                    </div>
                </form>
            </div>

            <!-- Menu contextuel pour les réactions -->
            <div id="reactionMenu" class="reaction-menu" style="display: none;">
                <div class="reaction-list">
                    <span class="reaction-item" data-emoji="👍">👍</span>
                    <span class="reaction-item" data-emoji="❤️">❤️</span>
                    <span class="reaction-item" data-emoji="😂">😂</span>
                    <span class="reaction-item" data-emoji="😮">😮</span>
                    <span class="reaction-item" data-emoji="😢">😢</span>
                    <span class="reaction-item" data-emoji="😡">😡</span>
                </div>
            </div>
        </div>

        <!-- Zone des médias -->
        <div class="media-attachments">
            <h4>Médias, fichiers et liens</h4>
            <div class="media-grid" id="mediaGrid">
                <!-- Les médias seront chargés ici -->
            </div>
        </div>
    </div>

    <!-- Modal pour les appels -->
    <div id="callModal" class="call-modal" style="display: none;">
        <div class="call-content">
            <div class="call-header">
                <img src="" alt="Avatar" class="avatar" id="callAvatar">
                <h3 id="callUserName"></h3>
                <p id="callStatus"></p>
            </div>
            <div class="call-actions">
                <button class="call-action-btn decline" id="declineCall">
                    <i class="fas fa-phone-slash"></i>
                </button>
                <button class="call-action-btn accept" id="acceptCall">
                    <i class="fas fa-phone"></i>
                </button>
            </div>
            <video id="remoteVideo" autoplay style="display: none;"></video>
            <video id="localVideo" autoplay muted style="display: none;"></video>
        </div>
    </div>

    <script src="assets/js/chat-file-handler.js"></script>
    <script src="assets/js/chat-user-search.js"></script>
    <script>
        // Configuration
        const SOCKET_SERVER_URL = 'http://localhost:3000';
        const WS_RECONNECT_DELAY = 5000;
        const WS_MAX_RECONNECT_ATTEMPTS = 5;

        // Variables globales
        let socket = null;
        let wsConnected = false;
        let wsReconnectAttempts = 0;
        let selectedUserId = null;
        let isTyping = false;
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        console.log('ID utilisateur connecté:', currentUserId); // Log pour debug

        // Initialisation des gestionnaires
        const fileHandler = new ChatFileHandler({
            socket: socket,
            maxFileSize: 10 * 1024 * 1024 // 10MB
        });

        const userSearch = new ChatUserSearch({
            onUserSelect: (user) => {
                startConversation(user);
            }
        });

        // Initialisation de la connexion Socket.IO
        function initWebSocket() {
            try {
                console.log('Tentative de connexion au serveur Socket.IO...');
                socket = io(SOCKET_SERVER_URL, {
                    transports: ['websocket'],
                    reconnection: true,
                    reconnectionDelay: WS_RECONNECT_DELAY,
                    reconnectionAttempts: WS_MAX_RECONNECT_ATTEMPTS
                });

                // Événement de connexion
                socket.on('connect', function() {
                    console.log('✅ Connecté au serveur Socket.IO');
                    wsReconnectAttempts = 0;
                    wsConnected = true;
                    
                    // Ré-authentifier l'utilisateur
                    socket.emit('auth', {
                        userId: currentUserId
                    });
                    
                    showNotification('Connecté au chat', 'success');
                });

                // Événement de déconnexion
                socket.on('disconnect', function() {
                    console.log('❌ Déconnecté du serveur Socket.IO');
                    wsConnected = false;
                    showNotification('Déconnecté du chat', 'error');
                });

                // Gestion des erreurs
                socket.on('error', function(error) {
                    console.error('❌ Erreur Socket.IO:', error);
                    wsConnected = false;
                    showNotification('Erreur de connexion au chat', 'error');
                });

                // Réception des messages existants
                socket.on('loadMessages', function(messages) {
                    console.log('📚 Messages existants reçus:', messages);
                    messages.forEach(message => appendMessage(message));
                    scrollToBottom();
                });

                // Réception d'un nouveau message
                socket.on('receiveMessage', function(message) {
                    console.log('📨 Nouveau message reçu:', message);
                    appendMessage(message);
                    if (message.senderId !== currentUserId) {
                        playNotificationSound();
                    }
                });

                // Confirmation d'envoi de message
                socket.on('messageSent', function(response) {
                    console.log('✅ Message envoyé avec succès:', response);
                });

                // Mettre à jour la référence du socket dans le gestionnaire de fichiers
                if (fileHandler) {
                    fileHandler.socket = socket;
                }

            } catch (error) {
                console.error('Erreur lors de l\'initialisation de WebSocket:', error);
            }
        }

        // Démarrer une nouvelle conversation
        function startConversation(user) {
            // Vérifier si une conversation existe déjà
            const existingConversation = $(`.conversation-item[data-user-id="${user.id}"]`);
            
            if (existingConversation.length) {
                existingConversation.click();
            } else {
                // Créer une nouvelle conversation
                $.ajax({
                    url: 'message_api.php',
                    method: 'POST',
                    data: {
                        action: 'createConversation',
                        user_id: user.id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Mettre à jour l'interface sans recharger
                            updateChatInterface(user);
                            // Ajouter la conversation à la liste
                            addConversationToList(user);
                            showNotification('Conversation créée avec succès', 'success');
                        } else {
                            showNotification(response.message || 'Erreur lors de la création de la conversation', 'error');
                        }
                    },
                    error: function() {
                        showNotification('Erreur de connexion au serveur', 'error');
                    }
                });
            }
        }

        // Mettre à jour l'interface du chat
        function updateChatInterface(user) {
            selectedUserId = user.id;
            
            // Mettre à jour les informations de l'utilisateur en haut
            $('.selected-user-info .user-info img').attr('src', user.profile_image || 'assets/images/default-avatar.png');
            $('.selected-user-info .user-info h3').text(user.first_name + ' ' + user.last_name);
            
            // Vider la zone de messages
            $('#chatMessages').empty();
            
            // Activer la zone de saisie
            $('#messageInput').prop('disabled', false);
            $('#messageForm').show();
        }

        // Ajouter une conversation à la liste
        function addConversationToList(user) {
            const conversationHtml = `
                <div class="conversation-item" data-user-id="${user.id}">
                    <div class="user-info">
                        <img src="${user.profile_image || 'assets/images/default-avatar.png'}" alt="Avatar" class="avatar">
                        <div class="user-details">
                            <h4>${user.first_name} ${user.last_name}</h4>
                            <p class="last-message">Démarrez une conversation</p>
                        </div>
                    </div>
                </div>`;
            
            $('.conversations-list').prepend(conversationHtml);
            $(`.conversation-item[data-user-id="${user.id}"]`).click();
        }

        // Ajouter un message à l'interface
        function appendMessage(message) {
            console.log('Ajout du message:', message);
            // Vérifier que le message a un sender_id ou un senderId
            const senderId = message.sender_id || message.senderId;
            // Vérifier si le message a été envoyé par l'utilisateur actuel
            const isSent = senderId == currentUserId; // Utiliser l'égalité non stricte pour gérer les différents types
            console.log(`Message de ${senderId}, utilisateur actuel: ${currentUserId}, isSent: ${isSent}`);
            
            const timestamp = message.timestamp || message.created_at || new Date();
            const content = message.content || message.message;
            const messageHtml = `
                <div class="message ${isSent ? 'sent' : 'received'}" data-sender="${senderId}">
                    <div class="message-content">
                        ${content}
                    </div>
                    <span class="message-time">${formatTime(timestamp)}</span>
                </div>`;
            
            $('#chatMessages').append(messageHtml);
            scrollToBottom();
        }

        // Gérer l'envoi des messages
        function sendMessage(messageContent) {
            if (!messageContent || !selectedUserId) return false;

            // Préparer les données du message
            const messageData = {
                receiver_id: selectedUserId,
                message: messageContent
            };

            // Envoyer via Socket.IO
            if (socket && socket.connected) {
                console.log('🚀 Envoi du message via Socket.IO:', messageData);
                socket.emit('sendMessage', messageData);
                
                // Afficher immédiatement le message dans l'interface
                appendMessage({
                    content: messageContent,
                    sender_id: currentUserId,
                    created_at: new Date().toISOString()
                });
                
                // Mettre à jour le dernier message dans la liste
                updateLastMessage(selectedUserId, messageContent);
                return true;
            } else {
                console.error('❌ Socket.IO non connecté');
                showNotification('Erreur : non connecté au serveur de chat', 'error');
                return false;
            }
        }

        // Gestionnaire de soumission du formulaire
        $('#messageForm').on('submit', function(e) {
            e.preventDefault();
            
            const input = $('#messageInput');
            const message = input.val().trim();
            
            if (sendMessage(message)) {
                input.val('');
                input.focus();
            }
        });

        // Mettre à jour le dernier message dans la liste des conversations
        function updateLastMessage(userId, message) {
            const conversationItem = $(`.conversation-item[data-user-id="${userId}"]`);
            if (conversationItem.length) {
                conversationItem.find('.last-message').text(message.length > 30 ? message.substring(0, 27) + '...' : message);
                // Déplacer la conversation en haut de la liste
                conversationItem.prependTo('.conversations-list');
            }
        }

        // Formater l'heure
        function formatTime(timestamp) {
            if (!timestamp) return '';
            
            // Si c'est déjà un objet Date
            if (timestamp instanceof Date) {
                return timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
            
            // Si c'est une chaîne ISO ou un timestamp Unix
            const date = new Date(timestamp);
            if (isNaN(date.getTime())) {
                return '';
            }
            
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        // Faire défiler jusqu'au dernier message
        function scrollToBottom() {
            const chatMessages = $('#chatMessages');
            chatMessages.scrollTop(chatMessages[0].scrollHeight);
        }

        // Notifications
        function showNotification(message, type = 'info') {
            Swal.fire({
                text: message,
                icon: type,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }

        // Jouer le son de notification
        function playNotificationSound() {
            try {
                const audio = new Audio('/assets/sounds/notification.mp3');
                const playPromise = audio.play();
                
                if (playPromise !== undefined) {
                    playPromise.catch(error => {
                        console.log('Info: Lecture du son impossible:', error.message);
                    });
                }
            } catch (error) {
                console.log('Info: Son de notification non disponible');
            }
        }

        // Initialisation
        $(document).ready(function() {
            initWebSocket();
            
            // Gérer le clic sur une conversation existante
            $('.conversation-item').on('click', function() {
                const userId = $(this).data('user-id');
                const userName = $(this).find('.user-details h4').text();
                const userImage = $(this).find('.avatar').attr('src');
                
                updateChatInterface({
                    id: userId,
                    first_name: userName.split(' ')[0],
                    last_name: userName.split(' ')[1] || '',
                    profile_image: userImage
                });
                
                // Charger les messages existants
                loadMessages(userId);
            });
        });

        // Charger les messages d'une conversation
        function loadMessages(userId) {
            console.log('Chargement des messages pour la conversation avec l\'utilisateur:', userId);
            $.ajax({
                url: 'message_api.php',
                method: 'GET',
                data: {
                    action: 'getMessages',
                    user_id: userId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        console.log('Messages récupérés avec succès:', response.messages);
                        $('#chatMessages').empty();
                        response.messages.forEach(message => {
                            appendMessage(message);
                        });
                        scrollToBottom();
                    } else {
                        console.error('Erreur lors du chargement des messages:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erreur AJAX lors du chargement des messages:', error);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            initWebSocket();

            // Appliquer les clics et l'état initial
            function bindConversationClickEvents() {
                document.querySelectorAll('.conversation-item').forEach(item => {
                    item.addEventListener('click', function () {
                        const userId = this.getAttribute('data-user-id');
                        localStorage.setItem('activeConversationId', userId);

                        // Marquer comme actif
                        document.querySelectorAll('.conversation-item').forEach(conv => {
                            conv.classList.remove('active');
                        });
                        this.classList.add('active');

                        // Récupération des infos
                        const userName = this.querySelector('.user-details h4').textContent;
                        const userImage = this.querySelector('.avatar').src;

                        updateChatInterface({
                            id: userId,
                            first_name: userName.split(' ')[0],
                            last_name: userName.split(' ')[1] || '',
                            profile_image: userImage
                        });

                        loadMessages(userId);
                    });
                });
            }

            // Rétablir la conversation active
            const activeConversationId = localStorage.getItem('activeConversationId');
            if (activeConversationId) {
                const activeItem = document.querySelector(`.conversation-item[data-user-id="${activeConversationId}"]`);
                if (activeItem) {
                    activeItem.classList.add('active');
                    const name = activeItem.querySelector('.user-details h4').textContent;
                    const img = activeItem.querySelector('.avatar').src;

                    updateChatInterface({
                        id: activeConversationId,
                        first_name: name.split(' ')[0],
                        last_name: name.split(' ')[1] || '',
                        profile_image: img
                    });

                    loadMessages(activeConversationId);
                }
            }

            // Activer les clics
            bindConversationClickEvents();
        });
    </script>
</body>
</html>
