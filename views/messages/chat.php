<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 2) . '/config/database.php';

$database = new Database(); 
$db = $database->getConnection();

$current_user_id = $_SESSION['user_id'] ?? null;
$receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;
$jwt_token = $_SESSION['jwt_token'] ?? '';

// Récupération du nom du conducteur
$driver_name = 'Conducteur inconnu';
if ($current_user_id) {
    $query = "SELECT u.first_name, u.last_name 
              FROM users u
              JOIN rides r ON u.id = r.driver_id
              JOIN bookings b ON r.id = b.ride_id
              WHERE b.passenger_id = :passenger_id
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':passenger_id', $current_user_id);
    $stmt->execute();
    if ($driver = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $driver_name = htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chat Application</title>
  <style>
    /* Style principal */
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background-color: #0b141a;
      color: #e9edef;
      margin: 0;
      height: 100vh;
    }

    #chat-container {
      display: flex;
      height: 100vh;
      max-width: 1400px;
      margin: 0 auto;
    }

    /* Sidebar */
    #conversations-list {
      width: 350px;
      background: #111b21;
      border-right: 1px solid #2a3942;
      display: flex;
      flex-direction: column;
    }

    #conversations-list h3 {
      padding: 15px;
      margin: 0;
      color: #d1d7db;
      background: #202c33;
      font-size: 1.1rem;
    }

    #conversation-list {
      flex: 1;
      overflow-y: auto;
      padding: 5px;
    }

    .conversation-item {
      display: flex;
      align-items: center;
      padding: 12px;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.2s;
    }

    .conversation-item:hover {
      background: #2a3942;
    }

    .active-conversation {
      background: #2d3b44;
    }

    .default-avatar {
      width: 49px;
      height: 49px;
      border-radius: 50%;
      background: #54656f;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      font-weight: 500;
      margin-right: 15px;
    }

    /* Zone de chat */
    #chat-window {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: #0b141a;
    }

    #chat-header {
      padding: 15px 20px;
      background: #202c33;
      display: flex;
      align-items: center;
      border-bottom: 1px solid #2a3942;
    }

    #chat-title {
      margin: 0;
      font-size: 1.1rem;
      color: #d1d7db;
    }

    #messages-container {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
      background: #0b141a url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" opacity="0.05"><pattern id="pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="%23ffffff"/></pattern><rect x="0" y="0" width="100%" height="100%" fill="url(%23pattern)"/></svg>');
    }

    .message {
      max-width: 65%;
      padding: 8px 12px;
      margin-bottom: 10px;
      border-radius: 7.5px;
      font-size: 0.95rem;
      line-height: 1.4;
      position: relative;
    }

    .message.sent {
      background: #005c4b;
      margin-left: auto;
      border-bottom-right-radius: 0;
    }

    .message.received {
      background: #202c33;
      margin-right: auto;
      border-bottom-left-radius: 0;
    }

    .message small {
      display: block;
      font-size: 0.75rem;
      color: #ffffff99;
      margin-top: 4px;
    }

    /* Formulaire */
    #chat-form {
      padding: 10px 15px;
      background: #202c33;
      display: flex;
      align-items: center;
    }

    #message-input {
      flex: 1;
      padding: 12px 15px;
      border-radius: 8px;
      border: none;
      background: #2a3942;
      color: #e9edef;
      font-size: 1rem;
    }

    #chat-form button {
      background: #00a884;
      color: white;
      border: none;
      border-radius: 8px;
      padding: 10px 20px;
      margin-left: 10px;
      font-weight: 500;
      cursor: pointer;
    }
  </style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script src="https://cdn.socket.io/4.0.0/socket.io.min.js"></script>
</head>
<body>
<div id="chat-container">
  <!-- Sidebar -->
  <div id="conversations-list">
    <h3>Conversations</h3>
    <ul id="conversation-list">
      <?php
      if ($current_user_id) {
        $query = "SELECT DISTINCT 
                    CASE WHEN m.sender_id = :user_id THEN m.receiver_id ELSE m.sender_id END as contact_id,
                    u.first_name, u.last_name,
                    MAX(m.created_at) as last_message_date
                  FROM messages m
                  JOIN users u ON u.id = CASE WHEN m.sender_id = :user_id THEN m.receiver_id ELSE m.sender_id END
                  WHERE m.sender_id = :user_id OR m.receiver_id = :user_id
                  GROUP BY contact_id, u.first_name, u.last_name
                  ORDER BY last_message_date DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $current_user_id);
        $stmt->execute();
        
        while ($conv = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $initials = substr($conv['first_name'], 0, 1) . substr($conv['last_name'], 0, 1);
          $activeClass = $conv['contact_id'] == $receiver_id ? 'active-conversation' : '';
          echo '<li class="conversation-item ' . $activeClass . '" onclick="window.location=\'?receiver_id=' . $conv['contact_id'] . '\'">
                  <div class="default-avatar">' . $initials . '</div>
                  <div>
                    <div class="contact-name">' . htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']) . '</div>
                    <small>' . date('H:i', strtotime($conv['last_message_date'])) . '</small>
                  </div>
                </li>';
        }
      }
      ?>
    </ul>
  </div>

  <!-- Zone de discussion -->
  <div id="chat-window">
    <div id="chat-header">
      <h4 id="chat-title"><?= $driver_name ?></h4>
    </div>
    
    <div id="messages-container">
      <!-- Messages chargés dynamiquement -->
    </div>
    
    <form id="chat-form" onsubmit="return false;">
      <input type="text" id="message-input" placeholder="Écrivez un message..." autocomplete="off">
      <button type="button" id="send-button">Envoyer</button>
    </form>
  </div>
</div>

<script>
// Configuration de Socket.IO
const socket = io('http://127.0.0.1:3000', {
  reconnection: true,
  reconnectionAttempts: 5,
  reconnectionDelay: 1000,
  query: { token: '<?= addslashes($jwt_token) ?>' }
});

const messagesContainer = document.getElementById('messages-container');
const chatForm = document.getElementById('chat-form');
const messageInput = document.getElementById('message-input');

// Gestion des événements WebSocket
socket.on('connect', () => console.log('Connecté au serveur WebSocket'));
socket.on('disconnect', () => console.log('Déconnecté du serveur WebSocket'));
socket.on('updateMessages', (data) => renderMessages(data.messages));

// Affichage des messages
function renderMessages(messages) {
  messagesContainer.innerHTML = messages.map(msg => {
    const isSent = msg.sender_id == <?= $current_user_id ?? 'null' ?>;
    return `<div class="message ${isSent ? 'sent' : 'received'}">
              <strong>${isSent ? 'Moi' : msg.first_name}:</strong> ${msg.message}
              <br><small>${msg.created_at || ''}</small>
            </div>`;
  }).join('');
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Envoi de message
document.getElementById('send-button').addEventListener('click', async (e) => {
  if (!messageInput.value.trim()) return;

  try {
    const formData = new FormData(chatForm);
    formData.append('receiver_id', <?= $receiver_id ?>);

    const response = await fetch('/views/messages/message_api.php?action=sendMessage', {
      method: 'POST',
      headers: { 'Authorization': 'Bearer <?= addslashes($jwt_token) ?>' },
      body: formData
    });

    const result = await response.json();
    if (result.success) {
      messageInput.value = '';
      socket.emit('sendMessage', {
        receiver_id: <?= $receiver_id ?>,
        message: formData.get('message'),
        file_path: result.file_path || null,
        file_type: result.file_type || 'text'
      });
    }
  } catch (error) {
    console.error('Erreur:', error);
  }
});

// Chargement initial
loadMessages();
function loadMessages() {
  fetch(`/views/messages/message_api.php?action=getMessages&receiver_id=<?= $receiver_id ?>`)
    .then(response => response.json())
    .then(renderMessages)
    .catch(console.error);
}
</script>
</body>
