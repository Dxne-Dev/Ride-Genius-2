<?php
// Définir le chemin racine du projet
define('ROOT_PATH', dirname(__DIR__));

// Inclure les fichiers nécessaires avec des chemins absolus
require_once ROOT_PATH . '/config/Database.php';
require_once ROOT_PATH . '/models/Conversation.php';

try {
    // Connexion à la base de données
    $database = new Database();
    $db = $database->getConnection();
    
    // Instance du modèle Conversation
    $conversationModel = new Conversation($db);
    
    // 1. Récupérer tous les messages sans conversation_id
    $query = "SELECT DISTINCT sender_id, receiver_id 
              FROM messages 
              WHERE conversation_id IS NULL";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $messagePairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "🔍 " . count($messagePairs) . " paires d'utilisateurs trouvées\n";
    
    // 2. Pour chaque paire d'utilisateurs, créer ou récupérer la conversation
    foreach ($messagePairs as $pair) {
        $conversationId = $conversationModel->getOrCreateConversationId(
            $pair['sender_id'],
            $pair['receiver_id']
        );
        
        // 3. Mettre à jour tous les messages de cette paire d'utilisateurs
        $updateQuery = "UPDATE messages 
                       SET conversation_id = :conversation_id 
                       WHERE ((sender_id = :user1_id AND receiver_id = :user2_id) 
                       OR (sender_id = :user2_id AND receiver_id = :user1_id))
                       AND conversation_id IS NULL";
        
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([
            ':conversation_id' => $conversationId,
            ':user1_id' => $pair['sender_id'],
            ':user2_id' => $pair['receiver_id']
        ]);
        
        $updatedCount = $updateStmt->rowCount();
        echo "✅ Conversation {$conversationId}: {$updatedCount} messages mis à jour\n";
    }
    
    // 4. Vérifier s'il reste des messages sans conversation_id
    $checkQuery = "SELECT COUNT(*) as count FROM messages WHERE conversation_id IS NULL";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute();
    $remainingCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($remainingCount > 0) {
        echo "⚠️ Attention: {$remainingCount} messages n'ont toujours pas de conversation_id\n";
    } else {
        echo "✨ Migration terminée avec succès! Tous les messages ont un conversation_id\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la migration: " . $e->getMessage() . "\n";
} 