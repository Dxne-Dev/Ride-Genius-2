<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Supprimer les tokens expirés
    $stmt = $pdo->prepare("DELETE FROM api_tokens WHERE expires_at < NOW()");
    $stmt->execute();
    
    $deleted = $stmt->rowCount();
    echo "Nettoyage terminé. $deleted tokens expirés supprimés.\n";
    
} catch (PDOException $e) {
    error_log("Erreur nettoyage tokens: " . $e->getMessage());
    echo "Erreur lors du nettoyage des tokens.\n";
} 