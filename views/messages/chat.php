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

// Récupération des conversations
$conversations = $this->message->getConversations($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie - Ride Genius</title>
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar des conversations -->
            <div class="col-md-4 col-lg-3 chat-sidebar">
                <div class="chat-header">
                    <h2>Messages</h2>
                    <div class="search-box">
                        <input type="text" id="searchUsers" placeholder="Rechercher un utilisateur...">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
                <div class="conversations-list">
                    <?php foreach ($conversations as $conv): ?>
                        <div class="conversation-item" data-user-id="<?php echo $conv['other_user_id']; ?>">
                            <div class="user-info">
                                <img src="assets/images/default-avatar.png" alt="Avatar" class="avatar">
                                <div class="user-details">
                                    <h4><?php echo htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']); ?></h4>
                                    <p class="last-message"><?php echo htmlspecialchars($conv['last_message'] ?? 'Aucun message'); ?></p>
                                </div>
                            </div>
                            <div class="conversation-meta">
                                <span class="time"><?php echo date('H:i', strtotime($conv['last_message_at'])); ?></span>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Zone de chat principale -->
            <div class="col-md-8 col-lg-9 chat-main">
                <div class="chat-header">
                    <div class="selected-user-info">
                        <img src="<?php echo $currentUser['profile_image'] ?? 'assets/images/default-avatar.png'; ?>" alt="Avatar" class="avatar">
                        <h3>Sélectionnez une conversation</h3>
                    </div>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <!-- Les messages seront chargés ici -->
                </div>
                <div class="chat-input">
                    <form id="messageForm">
                        <input type="text" id="messageInput" placeholder="Écrivez votre message...">
                        <button type="submit">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialisation de la connexion WebSocket
        const ws = new WebSocket('ws://localhost:3000');
        let currentUserId = <?php echo $_SESSION['user_id']; ?>;
        let selectedUserId = null;

        // Gestion des événements WebSocket
        ws.onopen = function() {
            console.log('Connecté au serveur WebSocket');
            ws.send(JSON.stringify({
                type: 'auth',
                userId: currentUserId
            }));
        };

        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if (data.type === 'message' && data.senderId === selectedUserId) {
                appendMessage(data);
            }
        };

        ws.onerror = function(error) {
            console.error('Erreur WebSocket:', error);
            showNotification('Erreur de connexion au chat', 'error');
        };

        ws.onclose = function() {
            console.log('Déconnecté du serveur WebSocket');
            // Tentative de reconnexion après 5 secondes
            setTimeout(() => {
                window.location.reload();
            }, 5000);
        };

        // Sélection d'une conversation
        $('.conversation-item').click(function() {
            selectedUserId = $(this).data('user-id');
            loadMessages(selectedUserId);
            
            // Mise à jour de l'interface
            $('.conversation-item').removeClass('active');
            $(this).addClass('active');
            
            // Mise à jour de l'en-tête
            const userName = $(this).find('h4').text();
            $('.selected-user-info h3').text(userName);
        });

        // Chargement des messages
        function loadMessages(userId) {
            $.get('message_api.php', {
                action: 'getMessages',
                user_id: userId
            }, function(response) {
                if (response.success) {
                    $('#chatMessages').empty();
                    response.messages.forEach(message => {
                        appendMessage(message);
                    });
                }
            });
        }

        // Envoi d'un message
        $('#messageForm').submit(function(e) {
            e.preventDefault();
            if (!selectedUserId) return;

            const message = $('#messageInput').val().trim();
            if (!message) return;

            $.post('message_api.php', {
                action: 'sendMessage',
                receiver_id: selectedUserId,
                message: message
            }, function(response) {
                if (response.success) {
                    $('#messageInput').val('');
                    appendMessage(response.message);
                }
            });
        });

        // Recherche d'utilisateurs
        $('#searchUsers').on('input', function() {
            const query = $(this).val().trim();
            if (query.length < 2) return;

            $.get('message_api.php', {
                action: 'searchUsers',
                query: query
            }, function(response) {
                if (response.success) {
                    // Afficher les résultats de recherche
                    // À implémenter selon vos besoins
                }
            });
        });

        // Fonctions utilitaires
        function appendMessage(message) {
            const isCurrentUser = message.sender_id === currentUserId;
            const messageHtml = `
                <div class="message ${isCurrentUser ? 'sent' : 'received'}">
                    <div class="message-content">
                        <p>${message.content}</p>
                        <span class="message-time">${message.created_at}</span>
                    </div>
                </div>
            `;
            $('#chatMessages').append(messageHtml);
            $('#chatMessages').scrollTop($('#chatMessages')[0].scrollHeight);
        }

        function showNotification(message, type = 'info') {
            const notification = $('<div>')
                .addClass(`notification ${type}`)
                .text(message)
                .appendTo('body');

            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 3000);
        }
    </script>
</body>
</html>
