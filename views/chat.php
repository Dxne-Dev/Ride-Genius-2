<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Vérifier si receiver_id est présent dans l'URL
$receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : null;
if (!$receiver_id) {
    header('Location: /messages.php');
    exit;
}

// Inclure la base de données et le modèle utilisateur pour obtenir les informations du destinataire
require_once '../config/Database.php';
require_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

// Récupérer les informations du destinataire
$receiver = $userModel->findById($receiver_id);
if (!$receiver) {
    header('Location: /messages.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat avec <?php echo htmlspecialchars($receiver['first_name'] . ' ' . $receiver['last_name']); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <a href="/messages.php" class="back-button">←</a>
            <h2><?php echo htmlspecialchars($receiver['first_name'] . ' ' . $receiver['last_name']); ?></h2>
        </div>

        <div class="chat-messages">
            <!-- Les messages seront ajoutés ici dynamiquement -->
        </div>

        <form id="message-form" class="chat-input">
            <input type="text" id="message-input" placeholder="Tapez votre message..." autocomplete="off">
            <button type="submit">Envoyer</button>
        </form>
    </div>

    <!-- Variables JavaScript nécessaires -->
    <script>
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        const receiverId = <?php echo json_encode($receiver_id); ?>;
        const receiverName = <?php echo json_encode($receiver['first_name'] . ' ' . $receiver['last_name']); ?>;
    </script>

    <!-- Fichiers JavaScript -->
    <script src="/assets/js/chat.js"></script>

    <style>
        .chat-container {
            max-width: 800px;
            margin: 0 auto;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 1rem;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-button {
            text-decoration: none;
            font-size: 1.5rem;
            color: #212529;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .message {
            max-width: 70%;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            margin: 0.25rem 0;
        }

        .message.sent {
            align-self: flex-end;
            background-color: #007bff;
            color: white;
        }

        .message.received {
            align-self: flex-start;
            background-color: #e9ecef;
            color: #212529;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.8;
            margin-top: 0.25rem;
        }

        .chat-input {
            padding: 1rem;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            display: flex;
            gap: 0.5rem;
        }

        .chat-input input {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            outline: none;
        }

        .chat-input button {
            padding: 0.5rem 1rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
        }

        .chat-input button:hover {
            background: #0056b3;
        }
    </style>
</body>
</html> 