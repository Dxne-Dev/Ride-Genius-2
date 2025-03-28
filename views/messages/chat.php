<?php
// filepath: c:\wamp64\www\Ride-Genius\views\messages\chat.php

// Inclure la classe Database pour accéder à la connexion
require_once dirname(__DIR__, 2) . '/config/database.php';

// Créer une instance de la classe Database
$database = new Database();
$db = $database->getConnection();  // Obtenir la connexion PDO

// Récupérer l'ID du passager connecté
$passenger_id = $_SESSION['user_id'];

// Préparer et exécuter la requête SQL
$query = "
    SELECT u.first_name, u.last_name 
    FROM users u
    JOIN rides r ON u.id = r.driver_id
    JOIN bookings b ON r.id = b.ride_id
    WHERE b.passenger_id = :passenger_id
    LIMIT 1
";
$stmt = $db->prepare($query);
$stmt->bindParam(':passenger_id', $passenger_id);
$stmt->execute();
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

// Si aucun conducteur n'est trouvé, définir un nom par défaut
$driver_name = $driver ? $driver['first_name'] . ' ' . $driver['last_name'] : 'Conducteur inconnu';
?>

<div id="chat-container" style="display: flex; height: 100vh; background: #121b22; color: #e9edef; border-radius: 10px; overflow: hidden;">
    <div id="sidebar" style="width: 30%; background: #202c33; padding: 15px; display: flex; flex-direction: column; gap: 10px;">
        <h3 style="margin: 0; font-size: 1.2rem;">Discussions</h3>
        <div id="search-bar" style="margin-top: 10px;">
            <input type="text" placeholder="Rechercher une conversation..." style="width: 100%; padding: 10px; border: none; border-radius: 10px; background: #2a3942; color: #e9edef;">
        </div>
        <div id="conversation-list" style="flex: 1; overflow-y: auto; margin-top: 10px;">
            <!-- Liste des conversations dynamiques -->
        </div>
    </div>
    <div id="chat-panel" style="flex: 1; display: flex; flex-direction: column;">
        <div id="chat-header" style="background: #202c33; padding: 15px; display: flex; align-items: center; justify-content: space-between;">
            <h3 style="margin: 0; font-size: 1.2rem;"><?= htmlspecialchars($driver_name) ?></h3>
            <span style="color: #8696a0;">En ligne</span>
        </div>
        <div id="messages-container" style="flex: 1; overflow-y: auto; padding: 15px; background: #0b141a;">
            <!-- Messages dynamiques -->
        </div>
        <form id="chat-form" enctype="multipart/form-data" style="display: flex; align-items: center; padding: 10px; background: #202c33; gap: 10px;">
            <textarea id="message-input" placeholder="Écrivez un message..." rows="1" style="flex: 1; padding: 10px; border: none; border-radius: 10px; background: #2a3942; color: #e9edef; resize: none;"></textarea>
            <label for="file-input" style="cursor: pointer; color: #8696a0;">
                <i class="fas fa-paperclip" style="font-size: 1.2rem;"></i>
            </label>
            <input type="file" id="file-input" accept="image/*,video/*,audio/*" hidden>
            <button type="button" id="emoji-button" style="background: none; border: none; font-size: 1.2rem; color: #8696a0; cursor: pointer;">😊</button>
            <button type="submit" style="background: #005c4b; color: #e9edef; border: none; padding: 10px 15px; border-radius: 10px; cursor: pointer;">Envoyer</button>
        </form>
    </div>
</div>

<script src="http://localhost:3000/socket.io/socket.io.js"></script>
<script>
    const messagesContainer = document.getElementById('messages-container');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const fileInput = document.getElementById('file-input');
    const emojiButton = document.getElementById('emoji-button');
    const socket = io('http://localhost:3000');

    async function loadMessages() {
        const response = await fetch('message_api.php?action=getMessages&receiver_id=<?= $_GET['receiver_id'] ?>');
        const messages = await response.json();
        messagesContainer.innerHTML = messages.map(msg => `
            <div class="message ${msg.sender_id === <?= $_SESSION['user_id'] ?> ? 'sent' : 'received'}">
                <strong>${msg.sender_id === <?= $_SESSION['user_id'] ?> ? 'Moi' : msg.first_name}:</strong> 
                ${msg.file_path ? renderMedia(msg.file_path, msg.file_type) : msg.message}
            </div>
        `).join('');
    }

    function renderMedia(filePath, fileType) {
        if (fileType === 'image') {
            return `<img src="${filePath}" alt="Image" style="max-width: 100%; border-radius: 10px;">`;
        } else if (fileType === 'video') {
            return `<video controls style="max-width: 100%; border-radius: 10px;"><source src="${filePath}" type="video/mp4"></video>`;
        } else if (fileType === 'audio') {
            return `<audio controls><source src="${filePath}" type="audio/mpeg"></audio>`;
        }
        return `<a href="${filePath}" target="_blank">Télécharger le fichier</a>`;
    }

    socket.on('loadMessages', (messages) => {
        messagesContainer.innerHTML = messages.map(msg => `
            <div class="message ${msg.sender_id === <?= $_SESSION['user_id'] ?> ? 'sent' : 'received'}">
                <strong>${msg.sender_id === <?= $_SESSION['user_id'] ?> ? 'Moi' : 'Contact'}:</strong> 
                ${msg.file_path ? renderMedia(msg.file_path, msg.file_type) : msg.message}
            </div>
        `).join('');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    });

    socket.on('receiveMessage', (msg) => {
        const messageHTML = `
            <div class="message ${msg.sender_id === <?= $_SESSION['user_id'] ?> ? 'sent' : 'received'}">
                <strong>${msg.sender_id === <?= $_SESSION['user_id'] ?> ? 'Moi' : 'Contact'}:</strong> 
                ${msg.file_path ? renderMedia(msg.file_path, msg.file_type) : msg.message}
            </div>
        `;
        messagesContainer.innerHTML += messageHTML;
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    });

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('receiver_id', <?= $_GET['receiver_id'] ?>);
        formData.append('message', messageInput.value);
        if (fileInput.files[0]) {
            formData.append('file', fileInput.files[0]);
        }
        await fetch('message_api.php?action=sendMessage', {
            method: 'POST',
            body: formData
        });
        const message = {
            sender_id: <?= $_SESSION['user_id'] ?>,
            receiver_id: <?= $_GET['receiver_id'] ?>,
            message: messageInput.value,
            file_path: null, // Ajouter la gestion des fichiers si nécessaire
            file_type: 'text'
        };
        socket.emit('sendMessage', message);
        messageInput.value = '';
        fileInput.value = '';
        loadMessages();
    });

    setInterval(loadMessages, 2000);
    loadMessages();

    emojiButton.addEventListener('click', () => {
        messageInput.value += '😊';
    });
</script>