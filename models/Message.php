<?php
class Message {
    private $conn;
    private $table_name = 'messages';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getConversationMessages($conversation_id, $offset = 0, $limit = 20) {
        $query = "SELECT m.id, m.sender_id, m.receiver_id, m.content, m.created_at,
                         (SELECT GROUP_CONCAT(reaction, '') FROM message_reactions WHERE message_id = m.id) as reactions
                  FROM {$this->table_name} m
                  WHERE m.conversation_id = :conversation_id AND m.deleted_at IS NULL
                  ORDER BY m.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':conversation_id', $conversation_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($messages as &$msg) {
            $msg['reactions'] = $msg['reactions'] ? explode(',', $msg['reactions']) : [];
        }
        return $messages;
    }

    public function getConversationAttachments($conversation_id) {
        $query = "SELECT ma.file_path, ma.file_type
                  FROM message_attachments ma
                  JOIN messages m ON m.id = ma.message_id
                  WHERE m.conversation_id = :conversation_id AND m.deleted_at IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':conversation_id' => $conversation_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createMessage($conversation_id, $sender_id, $content) {
        $query = "INSERT INTO {$this->table_name} (conversation_id, sender_id, receiver_id, content, created_at) 
                  SELECT :conversation_id, :sender_id, 
                         CASE WHEN user1_id = :sender_id THEN user2_id ELSE user1_id END, 
                         :content, NOW()
                  FROM conversations WHERE id = :conversation_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':conversation_id' => $conversation_id,
            ':sender_id' => $sender_id,
            ':content' => $content
        ]);
        $this->updateConversationTimestamp($conversation_id);
        return $this->conn->lastInsertId();
    }

    public function addAttachment($message_id, $file_path, $file_type, $file_size) {
        $query = "INSERT INTO message_attachments (message_id, file_path, file_type, file_size, created_at) 
                  VALUES (:message_id, :file_path, :file_type, :file_size, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':message_id' => $message_id,
            ':file_path' => $file_path,
            ':file_type' => $this->simplifyFileType($file_type),
            ':file_size' => $file_size
        ]);
    }

    public function getAttachment($attachment_id) {
        $query = "SELECT ma.id, ma.file_path, ma.file_type, ma.file_size, m.conversation_id
                  FROM message_attachments ma
                  JOIN messages m ON m.id = ma.message_id
                  WHERE ma.id = :attachment_id AND m.deleted_at IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':attachment_id' => $attachment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markAsRead($user_id, $conversation_id) {
        $query = "UPDATE {$this->table_name} 
                  SET is_read = 1 
                  WHERE conversation_id = :conversation_id 
                  AND receiver_id = :user_id 
                  AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':conversation_id' => $conversation_id, ':user_id' => $user_id]);
    }

    public function searchUsers($query, $current_user_id) {
        $query = "SELECT id, first_name, last_name, COALESCE(profile_image, 'assets/images/default-avatar.png') as profile_image 
                  FROM users 
                  WHERE (first_name LIKE :query OR last_name LIKE :query) 
                  AND id != :user_id 
                  LIMIT 10";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':query' => "%$query%", ':user_id' => $current_user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPermissions($user_id, $conversation_id) {
        $query = "SELECT can_write, can_read 
                  FROM message_permissions 
                  WHERE user_id = :user_id 
                  AND conversation_id = :conversation_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id, ':conversation_id' => $conversation_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['can_write' => true, 'can_read' => true];
    }

    public function addReaction($message_id, $user_id, $reaction) {
        $query = "INSERT INTO message_reactions (message_id, user_id, reaction, created_at) 
                  VALUES (:message_id, :user_id, :reaction, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':message_id' => $message_id,
            ':user_id' => $user_id,
            ':reaction' => $reaction
        ]);
    }

    public function startCall($conversation_id, $caller_id, $call_type) {
        $query = "INSERT INTO message_calls (conversation_id, caller_id, receiver_id, call_type, status, created_at) 
                  SELECT :conversation_id, :caller_id, 
                         CASE WHEN user1_id = :caller_id THEN user2_id ELSE user1_id END, 
                         :call_type, 'pending', NOW()
                  FROM conversations WHERE id = :conversation_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':conversation_id' => $conversation_id,
            ':caller_id' => $caller_id,
            ':call_type' => $call_type
        ]);
        return $this->conn->lastInsertId();
    }

    public function endCall($call_id) {
        $query = "UPDATE message_calls 
                  SET status = 'ended', end_time = NOW() 
                  WHERE id = :call_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':call_id' => $call_id]);
    }

    private function updateConversationTimestamp($conversation_id) {
        $query = "UPDATE conversations SET last_message_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $conversation_id]);
    }

    private function simplifyFileType($mime) {
        if (strpos($mime, 'image') !== false) return 'image';
        if (strpos($mime, 'video') !== false) return 'video';
        return 'file';
    }
}