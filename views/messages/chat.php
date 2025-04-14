<?php
//
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Ride Genius</title>
    <link rel="stylesheet" href="/assets/css/custom.css">
    <link rel="stylesheet" href="/assets/css/chat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.socket.io/4.8.1/socket.io.min.js"></script>
    <script src="/assets/js/ChatAPI.js"></script>
    <script src="/assets/js/ChatManager.js"></script>
    <style>
        @media (max-width: 768px) {
            .chat-sidebar { display: none; }
            .chat-sidebar.active { display: block; position: absolute; width: 100%; z-index: 10; }
            .chat-main { width: 100%; }
            .swipe-indicator { display: block; position: absolute; left: 10px; top: 50%; }
        }
        .offline-indicator { color: red; font-size: 12px; display: none; }
        .media-grid img, .media-grid video { max-width: 100px; cursor: pointer; }
        .media-grid a { display: block; margin: 5px 0; }
        .message-reactions { font-size: 14px; margin-top: 5px; }
        .no-results { padding: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-sidebar">
            <div class="chat-header">
                <h2>Messages</h2>
                <div class="search-box">
                    <input type="text" id="searchUsers" placeholder="Rechercher dans Messenger">
                    <i class="fas fa-search"></i>
                    <div class="search-results"></div>
                </div>
                <span class="offline-indicator">Hors ligne</span>
            </div>
            <div class="conversations-list"></div>
        </div>
        <div class="chat-main">
            <div class="selected-user-info">
                <div class="user-info">
                    <i class="fas fa-bars swipe-indicator" style="display: none;"></i>
                    <img src="/assets/images/default-avatar.png" alt="Avatar" class="avatar">
                    <h3>Sélectionnez une conversation</h3>
                </div>
                <div class="chat-actions">
                    <button class="chat-action-btn" id="audioCallBtn" title="Appel audio"><i class="fas fa-phone"></i></button>
                    <button class="chat-action-btn" id="videoCallBtn" title="Appel vidéo"><i class="fas fa-video"></i></button>
                    <button class="chat-action-btn" id="infoBtn" title="Informations"><i class="fas fa-info-circle"></i></button>
                </div>
            </div>
            <div class="chat-messages" id="chatMessages"></div>
            <div class="chat-input">
                <form id="messageForm" class="message-form" onsubmit="return false;">
                    <div class="message-attachments" id="messageAttachments"></div>
                    <div class="input-group">
                        <button type="button" class="attach-btn" id="attachButton"><i class="fas fa-paperclip"></i></button>
                        <input type="text" id="messageInput" placeholder="Écrivez votre message..." autocomplete="off">
                        <input type="file" id="fileInput" name="files[]" style="display: none" multiple accept="image/*,video/*,.pdf,.doc,.docx,.txt">
                        <button type="submit" id="sendButton"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </form>
            </div>
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
        <div class="media-attachments">
            <h4>Médias, fichiers et liens</h4>
            <div class="media-grid" id="mediaGrid"></div>
        </div>
    </div>
    <div id="callModal" class="call-modal" style="display: none;">
        <div class="call-content">
            <div class="call-header">
                <img src="/assets/images/default-avatar.png" alt="Avatar" class="avatar" id="callAvatar">
                <h3 id="callUserName"></h3>
                <p id="callStatus"></p>
            </div>
            <div class="call-actions">
                <button class="call-action-btn decline" id="declineCall"><i class="fas fa-phone-slash"></i></button>
                <button class="call-action-btn accept" id="acceptCall"><i class="fas fa-phone"></i></button>
            </div>
            <video id="remoteVideo" autoplay style="display: none;"></video>
            <video id="localVideo" autoplay muted style="display: none;"></video>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const currentUserId = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
            const userToken = <?php echo json_encode($_SESSION['api_token'] ?? null); ?>;
            
            if (!currentUserId || !userToken) {
                console.error('Utilisateur non connecté ou token manquant');
                window.location.href = 'index.php?page=login';
                return;
            }

            window.SOCKET_SERVER_URL = 'http://localhost:3000';
            window.USER_TOKEN = userToken;
            window.USER_ID = currentUserId;
            
            const chat = new ChatManager(currentUserId);
        });
    </script>
</body>
</html>