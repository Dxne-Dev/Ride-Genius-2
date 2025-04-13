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
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth.php';

// Gérer les requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialiser la connexion PDO
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    error_log("Erreur de connexion à la base de données: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erreur de connexion à la base de données"
    ]);
    exit();
}

// Fonction pour vérifier l'authentification de l'API
function check_api_auth() {
    if (!isset($_REQUEST['token']) || !isset($_REQUEST['sender_id'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Token d'authentification manquant"
        ]);
        exit();
    }

    if (!verify_token($_REQUEST['sender_id'], $_REQUEST['token'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Token invalide ou expiré"
        ]);
        exit();
    }

    return true;
}

// Router pour l'API
// Vérification si la clé 'action' est définie
if (!isset($_GET['action'])) {
    error_log("Clé 'action' manquante dans la requête");
    echo json_encode([
        "success" => false,
        "message" => "Action manquante dans la requête"
    ]);
    exit();
}

$action = $_GET['action'] ?? '';

// Ajout de logs pour déboguer l'action reçue
error_log("Action API reçue : " . json_encode($_GET['action']));

try {
    switch ($action) {
        case 'getConversations':
            check_api_auth();
            $stmt = $pdo->prepare("
                SELECT DISTINCT c.*, 
                    CASE 
                        WHEN c.sender_id = :user_id THEN r.first_name || ' ' || r.last_name
                        ELSE s.first_name || ' ' || s.last_name
                    END as other_user_name,
                    CASE 
                        WHEN c.sender_id = :user_id THEN r.id
                        ELSE s.id
                    END as other_user_id
                FROM conversations c
                JOIN users s ON c.sender_id = s.id
                JOIN users r ON c.receiver_id = r.id
                WHERE c.sender_id = :user_id OR c.receiver_id = :user_id
                ORDER BY c.last_message_at DESC
            ");
            $stmt->execute(['user_id' => $_REQUEST['sender_id']]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "conversations" => $conversations
            ]);
            break;

        case 'getMessages':
            check_api_auth();
            $conversation_id = $_GET['conversation_id'] ?? null;
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;

            if (!$conversation_id) {
                throw new Exception("ID de conversation manquant");
            }

            $stmt = $pdo->prepare("
                SELECT m.*, u.first_name, u.last_name
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = :conversation_id
                ORDER BY m.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':conversation_id', $conversation_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "messages" => $messages
            ]);
            break;

        case 'sendMessage':
            check_api_auth();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['conversation_id']) || !isset($data['message'])) {
                throw new Exception("Données manquantes");
            }

            $stmt = $pdo->prepare("
                INSERT INTO messages (conversation_id, sender_id, message, created_at)
                VALUES (:conversation_id, :sender_id, :message, NOW())
            ");
            $stmt->execute([
                'conversation_id' => $data['conversation_id'],
                'sender_id' => $_REQUEST['sender_id'],
                'message' => $data['message']
            ]);

            // Mettre à jour la date du dernier message
            $stmt = $pdo->prepare("
                UPDATE conversations 
                SET last_message_at = NOW() 
                WHERE id = :conversation_id
            ");
            $stmt->execute(['conversation_id' => $data['conversation_id']]);

            echo json_encode([
                "success" => true,
                "message" => "Message envoyé"
            ]);
            break;

        default:
            throw new Exception("Action non reconnue");
    }
} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erreur de base de données"
    ]);
} catch (Exception $e) {
    error_log("Erreur API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}