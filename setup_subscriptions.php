<?php
// Inclure la configuration de la base de données
require_once "config/database.php";

// Créer une instance de la classe Database
$database = new Database();
$db = $database->getConnection();

// Lire le fichier SQL
$sql = file_get_contents('sql/subscriptions.sql');

// Exécuter les requêtes SQL
try {
    // Diviser les requêtes par le délimiteur
    $queries = explode(';', $sql);
    
    // Exécuter chaque requête
    foreach ($queries as $query) {
        // Ignorer les requêtes vides
        if (trim($query) == '') {
            continue;
        }
        
        // Exécuter la requête
        $db->exec($query);
    }
    
    echo "Tables et triggers des abonnements créés avec succès.";
} catch (PDOException $e) {
    echo "Erreur lors de l'exécution des requêtes SQL: " . $e->getMessage();
} 