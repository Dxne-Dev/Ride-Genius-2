<?php
session_start();
require_once '../config/Database.php';
require_once '../models/Message.php';
require_once '../models/User.php';
require_once '../models/Conversation.php';

header('Content-Type: application/json');

// Vérification de l'authentification - accepte soit POST soit session
$user_id = $_POST['sender_id'] ?? $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Non authentifié'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $messageModel = new Message($db);
    $userModel = new User($db);
    $conversationModel = new Conversation($db);

    // Traitement des requêtes GET
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['receiver_id'])) {
            $messages = $messageModel->getConversation($user_id, $_GET['receiver_id']);
            echo json_encode([
                'status' => 'success',
                'messages' => $messages
            ]);
            exit;
        }
        
        echo json_encode([
            'status' => 'error',
            'message' => 'ID du destinataire manquant'
        ]);
        exit;
    }

    // Traitement des requêtes POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'sendMessage':
                $receiver_id = $_POST['receiver_id'] ?? null;
                $content = $_POST['message'] ?? '';
                
                if (!$receiver_id || !$content) {
                    throw new Exception('Paramètres manquants');
                }

                // Décomposer les données en arguments individuels
                $sender_id = $user_id;
                $receiver_id = $receiver_id;
                $content = $content;
                $type = 'text';

                // Obtenir ou créer la conversation d'abord
                $conversationId = $conversationModel->getOrCreateConversationId($sender_id, $receiver_id);
                
                // Créer le message avec le conversation_id
                $messageId = $messageModel->create($sender_id, $receiver_id, $content, $type, $conversationId);

                if ($messageId) {
                    // Mettre à jour le timestamp du dernier message
                    $conversationModel->updateLastMessageTime($conversationId);

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Message envoyé',
                        'message_id' => $messageId,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    throw new Exception('Erreur lors de l\'envoi du message');
                }
                break;

            case 'uploadFiles':
                $receiver_id = $_POST['receiver_id'] ?? null;
                $uploadedFiles = $_FILES['files'] ?? null;
                
                if (!$receiver_id || !$uploadedFiles) {
                    throw new Exception('Paramètres manquants');
                }

                $uploadDir = '../uploads/messages/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $response = ['status' => 'success', 'files' => []];

                foreach ($uploadedFiles['tmp_name'] as $key => $tmp_name) {
                    $fileName = $uploadedFiles['name'][$key];
                    $fileType = $uploadedFiles['type'][$key];
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $newFileName = uniqid() . '_' . time() . '.' . $extension;
                    $targetPath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($tmp_name, $targetPath)) {
                        $type = strpos($fileType, 'image/') === 0 ? 'image' : 
                               (strpos($fileType, 'video/') === 0 ? 'video' : 'file');
                        
                        $url = '/uploads/messages/' . $newFileName;
                        $content = json_encode(['url' => $url, 'type' => $type]);
                        
                        // Décomposer les données en arguments individuels
                        $sender_id = $user_id;
                        $receiver_id = $receiver_id;
                        $content = $content;
                        $type = $type;

                        // Obtenir ou créer la conversation d'abord
                        $conversationId = $conversationModel->getOrCreateConversationId($sender_id, $receiver_id);
                        
                        // Créer le message avec le conversation_id
                        $messageId = $messageModel->create($sender_id, $receiver_id, $content, $type, $conversationId);

                        if ($messageId) {
                            // Mettre à jour le timestamp du dernier message
                            $conversationModel->updateLastMessageTime($conversationId);

                            $response['files'][] = [
                                'url' => $url,
                                'type' => $type,
                                'message_id' => $messageId
                            ];
                        }
                    }
                }

                echo json_encode($response);
                break;

            case 'addReaction':
                $message_id = $_POST['message_id'] ?? null;
                $reaction = $_POST['reaction'] ?? null;
                
                if (!$message_id || !$reaction) {
                    throw new Exception('Paramètres manquants');
                }

                $result = $messageModel->addReaction($message_id, $user_id, $reaction);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Réaction ajoutée'
                ]);
                break;

            case 'markAsRead':
                $other_user_id = $_POST['other_user_id'] ?? null;
                
                if (!$other_user_id) {
                    throw new Exception('ID utilisateur manquant');
                }

                $result = $messageModel->markAsRead($user_id, $other_user_id);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Messages marqués comme lus'
                ]);
                break;

            default:
                throw new Exception('Action invalide');
        }
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 