<?php
require_once 'models/User.php';
require_once 'models/Message.php';

class MessageController {
    private $db;
    private $message;
    private $user;

    public function __construct($db) {
        $this->db = $db;
        $this->message = new Message($this->db);
        $this->user = new User($this->db);
    }

    // Protéger les routes
    private function authGuard() {
        if(!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page";
            header("Location: index.php?page=login");
            exit();
        }
    }

    // Recherche d'utilisateurs
    public function searchUsers($query) {
        try {
            $sql = "SELECT id, first_name, last_name, email, role, is_verified 
                    FROM users 
                    WHERE (first_name LIKE :query 
                    OR last_name LIKE :query 
                    OR email LIKE :query)
                    AND is_verified = 1
                    AND id != :user_id
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $searchQuery = "%{$query}%";
            $stmt->execute([
                ':query' => $searchQuery,
                ':user_id' => $_SESSION['user_id']
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la recherche d'utilisateurs: " . $e->getMessage());
            return [];
        }
    }

    // Envoi d'un message
    public function sendMessage($sender_id, $receiver_id, $message) {
        try {
            // Vérifier que les deux utilisateurs sont vérifiés
            $sender = $this->user->findById($sender_id);
            $receiver = $this->user->findById($receiver_id);

            if (!$sender['is_verified'] || !$receiver['is_verified']) {
                return ['success' => false, 'message' => 'Les deux utilisateurs doivent être vérifiés'];
            }

            // Créer le message
            $message_id = $this->message->create($sender_id, $receiver_id, $message);
            if (!$message_id) {
                return ['success' => false, 'message' => 'Erreur lors de l\'envoi du message'];
            }

            // Mettre à jour la conversation
            $this->updateConversation($sender_id, $receiver_id);

            return [
                'success' => true,
                'message_id' => $message_id,
                'created_at' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("Erreur lors de l'envoi du message: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'envoi du message'];
        }
    }

    // Récupération des messages entre deux utilisateurs
    public function getMessages($user_id, $other_user_id) {
        try {
            $messages = $this->message->getMessagesBetweenUsers($user_id, $other_user_id);
            
            // Marquer les messages comme lus
            $this->message->markAsRead($user_id, $other_user_id);

            return [
                'success' => true,
                'messages' => $messages
            ];
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des messages: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération des messages'];
        }
    }

    // Mise à jour de la conversation
    private function updateConversation($user1_id, $user2_id) {
        try {
            $sql = "INSERT INTO conversations (user1_id, user2_id, last_message_at) 
                    VALUES (:user1_id, :user2_id, CURRENT_TIMESTAMP)
                    ON DUPLICATE KEY UPDATE last_message_at = CURRENT_TIMESTAMP";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':user1_id' => $user1_id,
                ':user2_id' => $user2_id
            ]);
        } catch (Exception $e) {
            error_log("Erreur lors de la mise à jour de la conversation: " . $e->getMessage());
            return false;
        }
    }

    // Récupération des conversations d'un utilisateur
    public function getConversations($user_id) {
        try {
            $sql = "SELECT c.*, 
                    CASE 
                        WHEN c.user1_id = :user_id THEN c.user2_id 
                        ELSE c.user1_id 
                    END as other_user_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    (SELECT message FROM messages 
                     WHERE (sender_id = c.user1_id AND receiver_id = c.user2_id)
                     OR (sender_id = c.user2_id AND receiver_id = c.user1_id)
                     ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT COUNT(*) FROM messages 
                     WHERE receiver_id = :user_id 
                     AND sender_id = CASE 
                        WHEN c.user1_id = :user_id THEN c.user2_id 
                        ELSE c.user1_id 
                     END
                     AND read_at IS NULL) as unread_count
                    FROM conversations c
                    JOIN users u ON u.id = CASE 
                        WHEN c.user1_id = :user_id THEN c.user2_id 
                        ELSE c.user1_id 
                    END
                    WHERE c.user1_id = :user_id OR c.user2_id = :user_id
                    ORDER BY c.last_message_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $user_id]);

            return [
                'success' => true,
                'conversations' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des conversations: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération des conversations'];
        }
    }

    // Affichage de la page de chat
    public function index() {
        $this->authGuard();
        
        // Récupérer les conversations de l'utilisateur
        $conversationsResult = $this->getConversations($_SESSION['user_id']);
        $conversations = $conversationsResult['success'] ? $conversationsResult['conversations'] : [];
        
        // Récupérer les informations de l'utilisateur connecté
        $currentUser = $this->user->findById($_SESSION['user_id']);
        
        // Passer les données à la vue
        require_once 'views/messages/chat.php';
    }
}
?>