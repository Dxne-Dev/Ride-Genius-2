<?php
class Message {
    private $conn;
    private $table = 'messages';

    public function __construct() {
        require_once dirname(__DIR__) . '/config/database.php';
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($sender_id, $receiver_id, $content, $type = 'text') {
        try {
            $query = "INSERT INTO " . $this->table . " 
                    (sender_id, receiver_id, content, type, created_at) 
                    VALUES (:sender_id, :receiver_id, :content, :type, NOW())";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':sender_id', $sender_id);
            $stmt->bindParam(':receiver_id', $receiver_id);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':type', $type);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating message: " . $e->getMessage());
            return false;
        }
    }

    public function getConversation($user_id, $other_user_id) {
        try {
            $query = "SELECT m.*, u.username as sender_name, u.profile_image as sender_image 
                    FROM " . $this->table . " m 
                    JOIN users u ON m.sender_id = u.id 
                    WHERE (m.sender_id = :user_id AND m.receiver_id = :other_user_id) 
                    OR (m.sender_id = :other_user_id AND m.receiver_id = :user_id) 
                    ORDER BY m.created_at ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':other_user_id', $other_user_id);
            $stmt->execute();

            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get reactions for each message
            foreach ($messages as &$message) {
                $message['reactions'] = $this->getMessageReactions($message['id']);
            }

            return $messages;
        } catch (PDOException $e) {
            error_log("Error getting conversation: " . $e->getMessage());
            return false;
        }
    }

    public function getConversations($user_id) {
        try {
            $query = "SELECT 
                        u.id, u.username, u.profile_image,
                        m.content, m.type, m.created_at,
                        (SELECT COUNT(*) FROM messages 
                         WHERE receiver_id = :user_id 
                         AND sender_id = u.id 
                         AND is_read = 0) as unread_count
                    FROM (
                        SELECT DISTINCT 
                            CASE 
                                WHEN sender_id = :user_id THEN receiver_id
                                ELSE sender_id 
                            END as other_user_id,
                            MAX(created_at) as last_message_time
                        FROM messages 
                        WHERE sender_id = :user_id OR receiver_id = :user_id
                        GROUP BY other_user_id
                    ) as latest_msgs
                    JOIN users u ON u.id = latest_msgs.other_user_id
                    LEFT JOIN messages m ON (
                        (m.sender_id = :user_id AND m.receiver_id = u.id) OR
                        (m.sender_id = u.id AND m.receiver_id = :user_id)
                    ) AND m.created_at = latest_msgs.last_message_time
                    ORDER BY m.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting conversations: " . $e->getMessage());
            return false;
        }
    }

    public function markAsRead($user_id, $other_user_id) {
        try {
            $query = "UPDATE " . $this->table . " 
                    SET is_read = 1 
                    WHERE receiver_id = :user_id 
                    AND sender_id = :other_user_id 
                    AND is_read = 0";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':other_user_id', $other_user_id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error marking messages as read: " . $e->getMessage());
            return false;
        }
    }

    public function addReaction($message_id, $user_id, $reaction) {
        try {
            // First, check if user already reacted to this message
            $query = "SELECT id FROM message_reactions 
                    WHERE message_id = :message_id 
                    AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':message_id', $message_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Update existing reaction
                $query = "UPDATE message_reactions 
                        SET reaction = :reaction 
                        WHERE message_id = :message_id 
                        AND user_id = :user_id";
            } else {
                // Add new reaction
                $query = "INSERT INTO message_reactions 
                        (message_id, user_id, reaction) 
                        VALUES (:message_id, :user_id, :reaction)";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':message_id', $message_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':reaction', $reaction);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error adding reaction: " . $e->getMessage());
            return false;
        }
    }

    private function getMessageReactions($message_id) {
        try {
            $query = "SELECT r.reaction, COUNT(*) as count, GROUP_CONCAT(u.username) as users
                    FROM message_reactions r
                    JOIN users u ON r.user_id = u.id
                    WHERE r.message_id = :message_id
                    GROUP BY r.reaction";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':message_id', $message_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting message reactions: " . $e->getMessage());
            return [];
        }
    }

    public function delete($id) {
        try {
            // First, delete associated reactions
            $query = "DELETE FROM message_reactions WHERE message_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // Then delete the message
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting message: " . $e->getMessage());
            return false;
        }
    }
} 