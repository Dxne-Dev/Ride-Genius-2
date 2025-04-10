<?php
class Message {
    private $conn;
    private $table = 'messages';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($sender_id, $receiver_id, $content, $type = 'text', $conversation_id = null) {
        try {
            if (!$this->conn) {
                error_log("Error: Database connection is null in Message::create");
                return false;
            }

            $query = "INSERT INTO " . $this->table . " 
                    (sender_id, receiver_id, content, type, is_read, created_at, conversation_id) 
                    VALUES (:sender_id, :receiver_id, :content, :type, 0, NOW(), :conversation_id)";

            $stmt = $this->conn->prepare($query);

            // Validation des paramètres
            if (!$sender_id || !$receiver_id || !$content) {
                error_log("Error: Missing required parameters in Message::create");
                return false;
            }

            // Bind parameters
            $stmt->bindParam(':sender_id', $sender_id, PDO::PARAM_INT);
            $stmt->bindParam(':receiver_id', $receiver_id, PDO::PARAM_INT);
            $stmt->bindParam(':content', $content, PDO::PARAM_STR);
            $stmt->bindParam(':type', $type, PDO::PARAM_STR);
            $stmt->bindParam(':conversation_id', $conversation_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $message_id = $this->conn->lastInsertId();
                error_log("Success: Message created with ID " . $message_id);
                return $message_id;
            }

            error_log("Error: Failed to execute message creation query");
            return false;
        } catch (PDOException $e) {
            error_log("Database Error in Message::create: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("General Error in Message::create: " . $e->getMessage());
            return false;
        }
    }

    public function getConversation($user_id, $other_user_id) {
        try {
            $query = "SELECT m.*, 
                    CONCAT(u.first_name, ' ', u.last_name) as sender_name,
                    u.id as sender_id,
                    m.created_at,
                    m.is_read
                    FROM " . $this->table . " m 
                    JOIN users u ON m.sender_id = u.id 
                    WHERE (m.sender_id = :user_id AND m.receiver_id = :other_user_id) 
                    OR (m.sender_id = :other_user_id AND m.receiver_id = :user_id) 
                    ORDER BY m.created_at ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':other_user_id', $other_user_id, PDO::PARAM_INT);
            $stmt->execute();

            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format each message
            foreach ($messages as &$message) {
                $message['created_at'] = date('Y-m-d H:i:s', strtotime($message['created_at']));
                $message['is_read'] = (bool)$message['is_read'];
                $message['sender'] = [
                    'id' => $message['sender_id'],
                    'name' => $message['sender_name']
                ];
                
                // Remove redundant fields
                unset($message['sender_id']);
                unset($message['sender_name']);
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
                        u.id, 
                        CONCAT(u.first_name, ' ', u.last_name) as name,
                        m.content, 
                        m.type, 
                        m.created_at,
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
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format each conversation
            foreach ($conversations as &$conv) {
                $conv['created_at'] = date('Y-m-d H:i:s', strtotime($conv['created_at']));
                $conv['unread_count'] = (int)$conv['unread_count'];
            }

            return $conversations;
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

    public function getMessageById($id) {
        try {
            $query = "SELECT m.id, m.content, m.type, m.is_read, m.created_at, 
                    CONCAT(u.first_name, ' ', u.last_name) as sender_name,
                    u.id as sender_id
                    FROM " . $this->table . " m 
                    JOIN users u ON m.sender_id = u.id 
                    WHERE m.id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $message = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($message) {
                // Format the message data
                $message['created_at'] = date('Y-m-d H:i:s', strtotime($message['created_at']));
                $message['is_read'] = (bool)$message['is_read'];
                $message['sender'] = [
                    'id' => $message['sender_id'],
                    'name' => $message['sender_name']
                ];
                
                // Remove redundant fields
                unset($message['sender_id']);
                unset($message['sender_name']);
            }

            return $message;
        } catch (PDOException $e) {
            error_log("Error getting message by ID: " . $e->getMessage());
            return false;
        }
    }

    public function attachFile($messageId, $filePath, $fileType, $fileName, $fileSize) {
        try {
            $query = "INSERT INTO message_attachments 
                    (message_id, file_name, file_type, file_size, file_path) 
                    VALUES (:message_id, :file_name, :file_type, :file_size, :file_path)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':message_id', $messageId);
            $stmt->bindParam(':file_name', $fileName);
            $stmt->bindParam(':file_type', $fileType);
            $stmt->bindParam(':file_size', $fileSize);
            $stmt->bindParam(':file_path', $filePath);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error attaching file to message: " . $e->getMessage());
            return false;
        }
    }

    public function getMessageFiles($messageId) {
        try {
            $query = "SELECT * FROM message_attachments WHERE message_id = :message_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':message_id', $messageId);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting message files: " . $e->getMessage());
            return [];
        }
    }
} 