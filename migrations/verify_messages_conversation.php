<?php
// Définir le chemin racine du projet
define('ROOT_PATH', dirname(__DIR__));

// Inclure les fichiers nécessaires avec des chemins absolus
require_once ROOT_PATH . '/config/Database.php';

try {
    // Connexion à la base de données
    $database = new Database();
    $db = $database->getConnection();
    
    // 1. Vérifier les messages sans conversation_id
    $query = "SELECT COUNT(*) as count FROM messages WHERE conversation_id IS NULL";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $nullCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "📊 Statistiques des messages:\n";
    echo "--------------------------------\n";
    echo "Messages sans conversation_id: {$nullCount}\n";
    
    // 2. Vérifier la cohérence des conversations
    $query = "SELECT 
                c.id as conversation_id,
                c.user1_id,
                c.user2_id,
                COUNT(m.id) as message_count,
                MIN(m.created_at) as first_message,
                MAX(m.created_at) as last_message
              FROM conversations c
              LEFT JOIN messages m ON m.conversation_id = c.id
              GROUP BY c.id";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📊 Statistiques des conversations:\n";
    echo "--------------------------------\n";
    foreach ($conversations as $conv) {
        echo "Conversation {$conv['conversation_id']}:\n";
        echo "  - Utilisateurs: {$conv['user1_id']} et {$conv['user2_id']}\n";
        echo "  - Nombre de messages: {$conv['message_count']}\n";
        echo "  - Premier message: {$conv['first_message']}\n";
        echo "  - Dernier message: {$conv['last_message']}\n";
        echo "--------------------------------\n";
    }
    
    // 3. Vérifier la cohérence des timestamps
    $query = "SELECT COUNT(*) as count 
              FROM conversations c
              WHERE c.last_message_at < (
                  SELECT MAX(created_at) 
                  FROM messages 
                  WHERE conversation_id = c.id
              )";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $inconsistentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "\n📊 Cohérence des timestamps:\n";
    echo "--------------------------------\n";
    echo "Conversations avec timestamp incohérent: {$inconsistentCount}\n";
    
    if ($nullCount == 0 && $inconsistentCount == 0) {
        echo "\n✨ Tout est cohérent! La migration a réussi.\n";
    } else {
        echo "\n⚠️ Quelques problèmes ont été détectés. Veuillez les corriger.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la vérification: " . $e->getMessage() . "\n";
} 