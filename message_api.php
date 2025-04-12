<?php
session_start();
header('Content-Type: application/json');

// Lire les données JSON brutes
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_REQUEST['action'] ?? '';

// Vérifier l'utilisateur via les données envoyées (plutôt que la session)
$user_id = $input['sender_id'] ?? $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

require_once 'config/database.php';
require_once 'model/Message.php';
require_once 'model/Conversation.php';
require_once 'controller/MessageController.php';

$db = new Database();
$controller = new MessageController($db->getConnection());

try {
    switch ($action) {
        case 'getConversations':
            $result = $controller->getConversations($user_id);
            break;
        case 'getMessages':
            $conversation_id = $input['conversation_id'] ?? $_GET['conversation_id'] ?? null;
            $page = $input['page'] ?? $_GET['page'] ?? 1;
            $limit = $input['limit'] ?? $_GET['limit'] ?? 20;
            if (!$conversation_id) throw new Exception('Conversation requise');
            $result = $controller->getMessages($user_id, $conversation_id, $page, $limit);
            break;
        case 'sendMessage':
            $conversation_id = $input['conversation_id'] ?? null;
            $content = $input['content'] ?? '';
            $attachments = $input['attachments'] ?? [];
            if (!$conversation_id) throw new Exception('Conversation requise');
            $result = $controller->sendMessage($user_id, $conversation_id, $content, $attachments);
            break;
        case 'searchUsers':
            $query = $input['query'] ?? $_GET['query'] ?? '';
            if (!$query) throw new Exception('Recherche vide');
            $result = $controller->searchUsers($query);
            break;
        case 'createConversation':
            $other_user_id = $input['user_id'] ?? $_POST['user_id'] ?? null;
            if (!$other_user_id) throw new Exception('Utilisateur requis');
            $result = $controller->createConversation($user_id, $other_user_id);
            break;
        case 'checkPermissions':
            $conversation_id = $input['conversation_id'] ?? $_GET['conversation_id'] ?? null;
            if (!$conversation_id) throw new Exception('Conversation requise');
            $result = $controller->checkPermissions($user_id, $conversation_id);
            break;
        case 'addReaction':
            $message_id = $input['message_id'] ?? $_POST['message_id'] ?? null;
            $reaction = $input['reaction'] ?? $_POST['reaction'] ?? '';
            if (!$message_id || !$reaction) throw new Exception('Données manquantes');
            $result = $controller->addReaction($user_id, $message_id, $reaction);
            break;
        case 'startCall':
            $conversation_id = $input['conversation_id'] ?? $_POST['conversation_id'] ?? null;
            $call_type = $input['call_type'] ?? $_POST['call_type'] ?? '';
            if (!$conversation_id || !$call_type) throw new Exception('Données manquantes');
            $result = $controller->startCall($user_id, $conversation_id, $call_type);
            break;
        case 'endCall':
            $call_id = $input['call_id'] ?? $_POST['call_id'] ?? null;
            if (!$call_id) throw new Exception('Appel requis');
            $result = $controller->endCall($call_id);
            break;
        case 'getAttachment':
            $attachment_id = $input['attachment_id'] ?? $_GET['attachment_id'] ?? null;
            if (!$attachment_id) throw new Exception('Fichier requis');
            $result = $controller->getAttachment($user_id, $attachment_id);
            break;
        case 'getConversation':
            $conversation_id = $input['conversation_id'] ?? $_GET['conversation_id'] ?? null;
            if (!$conversation_id) throw new Exception('Conversation requise');
            $result = $controller->getConversation($user_id, $conversation_id);
            break;
        default:
            throw new Exception('Action invalide');
    }
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}