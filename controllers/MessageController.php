<?php
class MessageController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    private function encryptMessage($message) {
        $key = 'your-secret-key';
        return openssl_encrypt($message, 'AES-256-CBC', $key, 0, substr($key, 0, 16));
    }

    private function decryptMessage($encryptedMessage) {
        $key = 'your-secret-key';
        return openssl_decrypt($encryptedMessage, 'AES-256-CBC', $key, 0, substr($key, 0, 16));
    }

    public function sendMessage($receiver_id, $message, $file = null, $file_type = 'text') {
        $query = "INSERT INTO messages (sender_id, receiver_id, message, file_path, file_type) 
                  VALUES (:sender_id, :receiver_id, :message, :file_path, :file_type)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':sender_id', $_SESSION['user_id']);
        $stmt->bindParam(':receiver_id', $receiver_id);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':file_path', $file);
        $stmt->bindParam(':file_type', $file_type);
        return $stmt->execute();
    }

    public function getMessages($receiver_id) {
        $query = "SELECT m.*, u.first_name, u.last_name FROM messages m
                  JOIN users u ON m.sender_id = u.id
                  WHERE (m.sender_id = :user_id AND m.receiver_id = :receiver_id)
                     OR (m.sender_id = :receiver_id AND m.receiver_id = :user_id)
                  ORDER BY m.created_at ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':receiver_id', $receiver_id);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($messages as &$row) {
            $row['message'] = $this->decryptMessage($row['message']);
        }

        return $messages;
    }

    public function index() {
        include "views/messages/chat.php";
    }
}