<?php
// Définir le chemin racine du projet
define('ROOT_PATH', dirname(__DIR__));

// Inclure les fichiers nécessaires avec des chemins absolus
require_once ROOT_PATH . '/config/Database.php';

try {
    // Connexion à la base de données
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 Correction des problèmes de conversations\n";
    echo "--------------------------------\n";
    
    // 1. Supprimer les conversations sans messages
    $query = "DELETE FROM conversations c 
              WHERE NOT EXISTS (
                  SELECT 1 FROM messages m 
                  WHERE m.conversation_id = c.id
              )";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $deletedCount = $stmt->rowCount();
    
    echo "✅ {$deletedCount} conversations sans messages supprimées\n";
    
    // 2. Mettre à jour les timestamps last_message_at pour qu'ils correspondent au dernier message
    $query = "UPDATE conversations c
              SET last_message_at = (
                  SELECT MAX(created_at)
                  FROM messages
                  WHERE conversation_id = c.id
              )
              WHERE EXISTS (
                  SELECT 1 FROM messages m
                  WHERE m.conversation_id = c.id
              )";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $updatedCount = $stmt->rowCount();
    
    echo "✅ {$updatedCount} timestamps last_message_at mis à jour\n";
    
    // 3. Vérifier s'il reste des problèmes
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
    
    if ($inconsistentCount > 0) {
        echo "⚠️ Attention: {$inconsistentCount} conversations ont toujours des timestamps incohérents\n";
    } else {
        echo "✨ Tous les problèmes ont été corrigés!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la correction: " . $e->getMessage() . "\n";
} 