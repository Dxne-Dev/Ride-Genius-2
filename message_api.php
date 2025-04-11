<?php
session_start();
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Message.php';
require_once 'models/Conversation.php';

header('Content-Type: application/json');

// Vérification de l'authentification - accepte soit POST sender_id ou user_id, soit session
$current_user_id = $_POST['sender_id'] ?? $_SESSION['user_id'] ?? null;
if (!$current_user_id) {
    echo json_encode([
        'success' => false,
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
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'getMessages':
                // Accepter user_id ou receiver_id pour compatibilité
                $other_user_id = $_GET['user_id'] ?? $_GET['receiver_id'] ?? null;
                
                if (!$other_user_id) {
                    throw new Exception('ID utilisateur manquant');
                }
                
                $messages = $messageModel->getConversation($current_user_id, $other_user_id);
                
                // Marquer les messages comme lus
                $messageModel->markAsRead($current_user_id, $other_user_id);
                
                echo json_encode([
                    'success' => true,
                    'status' => 'success',
                    'messages' => $messages
                ]);
                break;
                
            case 'getUserInfo':
                $other_user_id = $_GET['user_id'] ?? $_GET['receiver_id'] ?? null;
                
                if (!$other_user_id) {
                    throw new Exception('ID utilisateur manquant');
                }
                
                $user = $userModel->findById($other_user_id);
                
                if (!$user) {
                    throw new Exception('Utilisateur non trouvé');
                }
                
                echo json_encode([
                    'success' => true,
                    'status' => 'success',
                    'user' => [
                        'id' => $user['id'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'email' => $user['email'],
                        'profile_image' => $user['profile_image'] ?? 'assets/images/default-avatar.png'
                    ]
                ]);
                break;
                
            case 'getConversations':
                try {
                    $conversationsResult = $conversationModel->getUserConversations($current_user_id);
                    echo json_encode([
                        'success' => true,
                        'status' => 'success',
                        'conversations' => $conversationsResult
                    ]);
                } catch (Exception $e) {
                    error_log('Erreur lors de la récupération des conversations: ' . $e->getMessage());
                    echo json_encode([
                        'success' => false,
                        'status' => 'error',
                        'message' => 'Erreur lors de la récupération des conversations'
                    ]);
                }
                break;
                
            default:
                throw new Exception('Action invalide');
        }
    }

    // Traitement des requêtes POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'sendMessage':
                // Accepter user_id ou receiver_id pour compatibilité
                $receiver_id = $_POST['user_id'] ?? $_POST['receiver_id'] ?? null;
                $content = $_POST['message'] ?? '';
                
                if (!$receiver_id) {
                    throw new Exception('ID utilisateur manquant');
                }

                $sender_id = $current_user_id;
                $type = 'text';
                
                // Gérer les fichiers uploadés
                $uploadedFiles = [];
                if (!empty($_FILES['files'])) {
                    $uploadDir = __DIR__ . '/uploads/messages/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                            $fileName = $_FILES['files']['name'][$key];
                            $fileType = $_FILES['files']['type'][$key];
                            $fileSize = $_FILES['files']['size'][$key];
                            
                            // Générer un nom de fichier unique
                            $uniqueName = uniqid() . '_' . $fileName;
                            $targetPath = $uploadDir . $uniqueName;
                            
                            if (move_uploaded_file($tmp_name, $targetPath)) {
                                $uploadedFiles[] = [
                                    'name' => $fileName,
                                    'path' => 'uploads/messages/' . $uniqueName,
                                    'type' => $fileType,
                                    'size' => $fileSize
                                ];
                            }
                        }
                    }
                }
                
                // Obtenir ou créer la conversation
                $conversationId = $conversationModel->getOrCreateConversationId($sender_id, $receiver_id);
                
                // Créer le message
                $messageId = $messageModel->create($sender_id, $receiver_id, $content, $type, $conversationId);
                
                if (!$messageId) {
                    throw new Exception('Erreur lors de la création du message');
                }
                
                // Si des fichiers ont été uploadés, les associer au message
                if (!empty($uploadedFiles)) {
                    foreach ($uploadedFiles as $file) {
                        $messageModel->attachFile($messageId, $file['path'], $file['type'], $file['name'], $file['size']);
                    }
                }
                
                // Mettre à jour le timestamp du dernier message
                $conversationModel->updateLastMessageTime($conversationId);
                
                echo json_encode([
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Message envoyé',
                    'message_id' => $messageId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'files' => $uploadedFiles
                ]);
                break;
                
            case 'createConversation':
                // Log the attempt to create or retrieve a conversation
                error_log("Tentative de création de conversation entre $current_user_id et $receiver_id");
                
                // Accepter user_id ou receiver_id pour compatibilité
                $receiver_id = $_POST['user_id'] ?? $_POST['receiver_id'] ?? null;
                
                if (!$receiver_id) {
                    throw new Exception('ID utilisateur manquant');
                }
                
                $sender_id = $current_user_id;
                
                // Vérifier si la conversation existe déjà avant de la créer
                $query = "SELECT id FROM conversations 
                         WHERE (user1_id = ? AND user2_id = ?) 
                         OR (user1_id = ? AND user2_id = ?)";
                
                $stmt = $db->prepare($query);
                $stmt->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);
                
                $isNewConversation = false;
                
                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $conversationId = $row['id'];
                    $conversationStatus = 'existing';
                    error_log("Conversation existante récupérée: $conversationId");
                } else {
                    // Créer une nouvelle conversation
                    $conversationId = $conversationModel->getOrCreateConversationId($sender_id, $receiver_id);
                    $conversationStatus = 'new';
                    $isNewConversation = true;
                    error_log("Nouvelle conversation créée: $conversationId");
                }
                
                // Récupérer les infos de l'utilisateur
                $user = $userModel->findById($receiver_id);
                
                if (!$user) {
                    throw new Exception('Utilisateur non trouvé');
                }
                
                echo json_encode([
                    'success' => true,
                    'status' => 'success',
                    'conversation_id' => $conversationId,
                    'is_new_conversation' => $isNewConversation,
                    'conversation_status' => $conversationStatus,
                    'user' => [
                        'id' => $user['id'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'profile_image' => $user['profile_image'] ?? 'assets/images/default-avatar.png'
                    ]
                ]);
                break;

            case 'getConversations':
                try {
                    $conversationsResult = $conversationModel->getUserConversations($current_user_id);
                    echo json_encode([
                        'success' => true,
                        'status' => 'success',
                        'conversations' => $conversationsResult
                    ]);
                } catch (Exception $e) {
                    error_log('Erreur lors de la récupération des conversations: ' . $e->getMessage());
                    echo json_encode([
                        'success' => false,
                        'status' => 'error',
                        'message' => 'Erreur lors de la récupération des conversations'
                    ]);
                }
                break;

            case 'markAsRead':
                $other_user_id = $_POST['other_user_id'] ?? $_POST['user_id'] ?? $_POST['receiver_id'] ?? null;
                
                if (!$other_user_id) {
                    throw new Exception('ID utilisateur manquant');
                }

                $result = $messageModel->markAsRead($current_user_id, $other_user_id);
                echo json_encode([
                    'success' => true,
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
        'success' => false,
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>