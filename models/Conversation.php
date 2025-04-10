<?php

class Conversation {
    private $conn;
    private $table_name = "conversations";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Récupère ou crée une conversation entre deux utilisateurs
     * @param int $user1_id ID du premier utilisateur
     * @param int $user2_id ID du second utilisateur
     * @return int ID de la conversation
     */
    public function getOrCreateConversationId($user1_id, $user2_id) {
        // Rechercher une conversation existante
        $query = "SELECT id FROM " . $this->table_name . " 
                 WHERE (user1_id = ? AND user2_id = ?) 
                 OR (user1_id = ? AND user2_id = ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id]);
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['id'];
        }
        
        // Créer une nouvelle conversation
        $query = "INSERT INTO " . $this->table_name . " 
                 (user1_id, user2_id, created_at, last_message_at) 
                 VALUES (?, ?, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user1_id, $user2_id]);
        
        return $this->conn->lastInsertId();
    }

    /**
     * Met à jour le timestamp du dernier message
     * @param int $conversation_id ID de la conversation
     */
    public function updateLastMessageTime($conversation_id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET last_message_at = NOW() 
                 WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$conversation_id]);
    }
} 