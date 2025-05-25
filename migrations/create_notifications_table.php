<?php
require_once 'config/database.php';

// Créer la table des notifications
$database = new Database();
$db = $database->getConnection();

$query = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    related_id INT,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

try {
    $db->exec($query);
    echo "Table 'notifications' créée avec succès.\n";
} catch(PDOException $e) {
    echo "Erreur lors de la création de la table: " . $e->getMessage() . "\n";
}
?>
