<?php
// Désactiver l'affichage des erreurs PHP
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Définir le gestionnaire d'erreurs personnalisé
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Erreur PHP: $errstr dans $errfile à la ligne $errline");
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erreur serveur interne"
    ]);
    exit();
});

// Définir le gestionnaire d'exceptions non attrapées
set_exception_handler(function($e) {
    error_log("Exception non attrapée: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erreur serveur interne"
    ]);
    exit();
});

// S'assurer que la réponse est toujours en JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Gérer les requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    session_start();

    require_once 'config/database.php';
    require_once 'models/Message.php';
    require_once 'models/Conversation.php';
    require_once 'controllers/MessageController.php';
    require_once 'auth.php';

    $db = new Database();
    $pdo = $db->getConnection();
    $controller = new MessageController($pdo);

    // Lire les données JSON ou paramètres
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $_REQUEST['action'] ?? '';
    
    // Log pour le débogage
    error_log("Action reçue: " . $action);
    error_log("Données reçues: " . json_encode($input));
    
    // Récupérer le token depuis les différentes sources possibles
    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? $_REQUEST['token'] ?? $input['token'] ?? null;
    $user_id = $_REQUEST['sender_id'] ?? $input['sender_id'] ?? null;

    // Nettoyer le token s'il est au format "Bearer <token>"
    if ($token && strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }

    // Vérifier l'authentification pour toutes les actions sauf verifyToken
    if ($action !== 'verifyToken') {
        if (!$token || !$user_id) {
            throw new Exception("Token d'authentification manquant", 401);
        }

        if (!verify_token($user_id, $token)) {
            throw new Exception("Token invalide ou expiré", 401);
        }
    }

    $result = null;

    // Vérifier que l'action est valide
    if (empty($action)) {
        throw new Exception("Action non spécifiée", 400);
    }

    switch ($action) {
        case 'getConversations':
            $result = $controller->getConversations($user_id);
            break;

        case 'getConversation':
            $conversation_id = $input['conversation_id'] ?? $_GET['conversation_id'] ?? null;
            if (!$conversation_id) {
                throw new Exception('ID de conversation requis', 400);
            }
            $result = $controller->getConversation($user_id, $conversation_id);
            break;

        case 'getMessages':
            $conversation_id = $input['conversation_id'] ?? $_GET['conversation_id'] ?? null;
            $page = intval($input['page'] ?? $_GET['page'] ?? 1);
            $limit = intval($input['limit'] ?? $_GET['limit'] ?? 20);
            
            if (!$conversation_id) {
                throw new Exception('ID de conversation requis', 400);
            }
            
            $result = $controller->getMessages($conversation_id, $page, $limit);
            break;

        case 'markMessagesAsRead':
            $conversation_id = $input['conversation_id'] ?? null;
            if (!$conversation_id) {
                throw new Exception('ID de conversation requis', 400);
            }
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE messages 
                    SET is_read = 1 
                    WHERE conversation_id = ? 
                    AND receiver_id = ? 
                    AND is_read = 0
                ");
                $stmt->execute([$conversation_id, $user_id]);
                
                $result = [
                    'success' => true,
                    'message' => 'Messages marqués comme lus'
                ];
            } catch (PDOException $e) {
                error_log("Erreur markMessagesAsRead: " . $e->getMessage());
                throw new Exception('Erreur lors du marquage des messages', 500);
            }
            break;

        case 'sendMessage':
            $conversation_id = $input['conversation_id'] ?? null;
            $content = $input['content'] ?? '';
            $attachments = [];

            if (isset($_FILES['files'])) {
                foreach ($_FILES['files']['name'] as $i => $name) {
                    if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES['files']['tmp_name'][$i];
                        $file_path = 'uploads/Attachments/' . uniqid() . '-' . basename($name);
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            $attachments[] = [
                                'file_path' => $file_path,
                                'file_type' => mime_content_type($file_path),
                                'file_name' => $name
                            ];
                        }
                    }
                }
            } else {
                $attachments = $input['attachments'] ?? [];
            }

            if (!$conversation_id) {
                throw new Exception('ID de conversation requis', 400);
            }

            $result = $controller->sendMessage($user_id, $conversation_id, $content, $attachments);
            break;

        case 'searchUsers':
            $query = $input['query'] ?? $_GET['query'] ?? '';
            if (empty($query)) {
                throw new Exception('Terme de recherche requis', 400);
            }
            $result = $controller->searchUsers($query);
            break;

        case 'createConversation':
            $other_user_id = $input['user_id'] ?? null;
            if (!$other_user_id) {
                throw new Exception('ID utilisateur requis', 400);
            }
            $result = $controller->createConversation($user_id, $other_user_id);
            break;

        case 'checkPermissions':
            $conversation_id = $input['conversation_id'] ?? $_GET['conversation_id'] ?? null;
            if (!$conversation_id) {
                throw new Exception('ID de conversation requis', 400);
            }
            $result = $controller->checkPermissions($user_id, $conversation_id);
            break;

        case 'addReaction':
            $message_id = $input['message_id'] ?? null;
            $reaction = $input['reaction'] ?? '';
            if (!$message_id || !$reaction) {
                throw new Exception('Données de réaction manquantes', 400);
            }
            $result = $controller->addReaction($user_id, $message_id, $reaction);
            break;

        case 'startCall':
            $conversation_id = $input['conversation_id'] ?? null;
            $call_type = $input['call_type'] ?? '';
            if (!$conversation_id || !$call_type) {
                throw new Exception('Données d\'appel manquantes', 400);
            }
            $result = $controller->startCall($user_id, $conversation_id, $call_type);
            break;

        case 'endCall':
            $call_id = $input['call_id'] ?? null;
            if (!$call_id) {
                throw new Exception('ID d\'appel requis', 400);
            }
            $result = $controller->endCall($call_id);
            break;

        case 'getAttachment':
            $attachment_id = $input['attachment_id'] ?? $_GET['attachment_id'] ?? null;
            if (!$attachment_id) {
                throw new Exception('ID de fichier requis', 400);
            }
            $result = $controller->getAttachment($user_id, $attachment_id);
            break;

        case 'verifyToken':
            if (!isset($_REQUEST['user_id']) || !isset($_REQUEST['token'])) {
                throw new Exception("Données d'authentification manquantes", 400);
            }

            $result = [
                "success" => verify_token($_REQUEST['user_id'], $_REQUEST['token']),
                "message" => verify_token($_REQUEST['user_id'], $_REQUEST['token']) ? "Token valide" : "Token invalide"
            ];
            break;

        default:
            throw new Exception('Action non reconnue', 400);
    }

    if ($result === null) {
        throw new Exception('Aucun résultat retourné', 500);
    }

    echo json_encode($result);

} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erreur de base de données"
    ]);
} catch (Exception $e) {
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    error_log("Erreur API: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}