<?php
session_start();
require_once 'config/database.php';
require_once 'controllers/MessageController.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$messageController = new MessageController($db);

// Router API - Accepte les actions en GET et POST
$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'searchUsers':
        echo json_encode($messageController->handleUserSearch());
        break;

    case 'getMessages':
        $other_user_id = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
        if ($other_user_id) {
            echo json_encode($messageController->getMessages($_SESSION['user_id'], $other_user_id));
        } else {
            echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
        }
        break;

    case 'sendMessage':
        $receiver_id = $_POST['receiver_id'] ?? null;
        $message = $_POST['message'] ?? null;
        if ($receiver_id && $message) {
            echo json_encode($messageController->sendMessage($_SESSION['user_id'], $receiver_id, $message));
        } else {
            echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
        }
        break;

    case 'createConversation':
        $other_user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;
        if ($other_user_id) {
            // Initialiser une conversation vide
            $result = $messageController->createConversation($_SESSION['user_id'], $other_user_id);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
        }
        break;

    case 'getConversations':
        $result = $messageController->getConversations($_SESSION['user_id']);
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        break;
}
?>