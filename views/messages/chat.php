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
        const SOCKET_SERVER_URL = 'ws://localhost:3000';
        const WS_RECONNECT_DELAY = 5000;
        const WS_MAX_RECONNECT_ATTEMPTS = 5;

        // Variables globales
        let socket;
        let wsConnected = false;
        let wsReconnectAttempts = 0;
        let selectedUserId = null;
        let isTyping = false;
        const currentUserId = <?php echo $_SESSION['user_id']; ?>;

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

        // Initialisation de la connexion WebSocket
        function initWebSocket() {
            try {
                console.log('Tentative de connexion au serveur Socket.IO...');
                socket = io(SOCKET_SERVER_URL, {
                    transports: ['websocket'],
                    reconnection: true,
                    reconnectionDelay: WS_RECONNECT_DELAY,
                    reconnectionAttempts: WS_MAX_RECONNECT_ATTEMPTS
                });

                socket.on('connect', function() {
                    console.log('Connecté au serveur Socket.IO');
                    wsReconnectAttempts = 0;
                    wsConnected = true;
                    socket.emit('auth', {
                        userId: currentUserId
                    });
                    showNotification('Connecté au chat', 'success');
                });

                socket.on('disconnect', function() {
                    console.log('Déconnecté du serveur Socket.IO');
                    wsConnected = false;
                    showNotification('Déconnecté du chat', 'error');
                });

                socket.on('error', function(error) {
                    console.error('Erreur Socket.IO:', error);
                    wsConnected = false;
                    showNotification('Erreur de connexion au chat', 'error');
                });

                socket.on('receiveMessage', function(data) {
                    handleWebSocketMessage(data);
                });

                // Mettre à jour la référence du socket dans le gestionnaire de fichiers
                fileHandler.socket = socket;
            } catch (error) {
                console.error('Erreur lors de l\'initialisation de Socket.IO:', error);
                wsConnected = false;
                showNotification('Impossible de se connecter au chat', 'error');
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
                            showNotification('Conversation créée avec succès', 'success');
                            setTimeout(() => location.reload(), 1000);
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

        // Initialisation
        $(document).ready(function() {
            initWebSocket();
        });
    </script>
</body>
</html>
