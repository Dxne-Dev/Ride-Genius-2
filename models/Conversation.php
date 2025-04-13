<?php
class Conversation {
    private $conn;
    private $table_name = 'conversations';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserConversations($user_id) {
        $query = "SELECT c.id, 
                         CASE WHEN c.user1_id = :user_id THEN c.user2_id ELSE c.user1_id END as other_user_id,
                         u.first_name, u.last_name, 
                         COALESCE(u.profile_image, 'assets/images/default-avatar.png') as profile_image,
                         COALESCE(m.content, 'Démarrez une conversation') as last_message,
                         c.last_message_at,
                         (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND receiver_id = :user_id AND is_read = 0) as unread_count
                  FROM {$this->table_name} c
                  JOIN users u ON u.id = CASE WHEN c.user1_id = :user_id THEN c.user2_id ELSE c.user1_id END
                  LEFT JOIN messages m ON m.id = (SELECT id FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1)
                  WHERE c.user1_id = :user_id OR c.user2_id = :user_id
                  ORDER BY c.last_message_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrCreateConversation($user1_id, $user2_id) {
        $query = "SELECT * FROM {$this->table_name} WHERE (user1_id = :user1_id AND user2_id = :user2_id) OR (user1_id = :user2_id AND user2_id = :user1_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user1_id' => $user1_id, ':user2_id' => $user2_id]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$conversation) {
            $query = "INSERT INTO {$this->table_name} (user1_id, user2_id, last_message_at) VALUES (:user1_id, :user2_id, NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':user1_id' => $user1_id, ':user2_id' => $user2_id]);
            $conversation = ['id' => $this->conn->lastInsertId(), 'user1_id' => $user1_id, 'user2_id' => $user2_id];
        }
        return $conversation;
    }
}