<?php
require_once 'config/database.php';
require_once 'controllers/MessageController.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$messageController = new MessageController($db);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'searchUsers':
        $query = $_GET['query'] ?? '';
        if (strlen($query) >= 2) {
            $users = $messageController->searchUsers($query);
            echo json_encode(['success' => true, 'users' => $users]);
        } else {
            echo json_encode(['success' => false, 'message' => 'La recherche doit contenir au moins 2 caractères']);
        }
        break;

    case 'sendMessage':
        $data = json_decode(file_get_contents('php://input'), true);
        $receiver_id = $data['receiver_id'] ?? null;
        $message = $data['message'] ?? '';

        if ($receiver_id && $message) {
            $result = $messageController->sendMessage($_SESSION['user_id'], $receiver_id, $message);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Données invalides']);
        }
        break;

    case 'getMessages':
        $other_user_id = $_GET['user_id'] ?? null;
        if ($other_user_id) {
            $result = $messageController->getMessages($_SESSION['user_id'], $other_user_id);
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
        echo json_encode(['success' => false, 'message' => 'Action non valide']);
}
?>