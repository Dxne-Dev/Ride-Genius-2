<?php
class Message {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Crée un nouveau message
    public function create($sender_id, $receiver_id, $message) {
        try {
            $sql = "INSERT INTO messages (sender_id, receiver_id, message) 
                    VALUES (:sender_id, :receiver_id, :message)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':sender_id' => $sender_id,
                ':receiver_id' => $receiver_id,
                ':message' => $message
            ]);

            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Erreur lors de la création du message: " . $e->getMessage());
            return false;
        }
    }

    // Récupère un message par son ID
    public function findById($id) {
        try {
            $sql = "SELECT m.*, 
                    u1.first_name as sender_first_name, 
                    u1.last_name as sender_last_name,
                    u2.first_name as receiver_first_name, 
                    u2.last_name as receiver_last_name
                    FROM messages m
                    JOIN users u1 ON m.sender_id = u1.id
                    JOIN users u2 ON m.receiver_id = u2.id
                    WHERE m.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du message: " . $e->getMessage());
            return false;
        }
    }

    // Récupère les messages entre deux utilisateurs
    public function getMessagesBetweenUsers($user1_id, $user2_id) {
        try {
            $sql = "SELECT m.*, 
                    u1.first_name as sender_first_name, 
                    u1.last_name as sender_last_name,
                    u2.first_name as receiver_first_name, 
                    u2.last_name as receiver_last_name
                    FROM messages m
                    JOIN users u1 ON m.sender_id = u1.id
                    JOIN users u2 ON m.receiver_id = u2.id
                    WHERE (m.sender_id = :user1_id AND m.receiver_id = :user2_id)
                    OR (m.sender_id = :user2_id AND m.receiver_id = :user1_id)
                    ORDER BY m.created_at ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user1_id' => $user1_id,
                ':user2_id' => $user2_id
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des messages: " . $e->getMessage());
            return false;
        }
    }

    // Marque les messages comme lus
    public function markAsRead($receiver_id, $sender_id) {
        try {
            $sql = "UPDATE messages 
                    SET read_at = CURRENT_TIMESTAMP 
                    WHERE receiver_id = :receiver_id 
                    AND sender_id = :sender_id 
                    AND read_at IS NULL";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':receiver_id' => $receiver_id,
                ':sender_id' => $sender_id
            ]);
        } catch (Exception $e) {
            error_log("Erreur lors du marquage des messages comme lus: " . $e->getMessage());
            return false;
        }
    }

    // Récupère le nombre de messages non lus
    public function getUnreadCount($user_id) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM messages 
                    WHERE receiver_id = :user_id 
                    AND read_at IS NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $user_id]);

            return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du nombre de messages non lus: " . $e->getMessage());
            return 0;
        }
    }

    // Récupère les conversations d'un utilisateur
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

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération des conversations: " . $e->getMessage());
            return [];
        }
    }

    // Supprime un message
    public function delete($id, $user_id) {
        try {
            $sql = "DELETE FROM messages 
                    WHERE id = :id 
                    AND (sender_id = :user_id OR receiver_id = :user_id)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':user_id' => $user_id
            ]);
        } catch (Exception $e) {
            error_log("Erreur lors de la suppression du message: " . $e->getMessage());
            return false;
        }
    }
} 