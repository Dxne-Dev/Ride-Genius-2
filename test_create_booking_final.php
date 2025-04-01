<?php
require 'config/database.php';

try {
    $db = (new Database())->getConnection();

    // 1. Récupérer l'ID du passager
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => 'passager@test.com']);
    $passenger_id = $stmt->fetchColumn();

    // 2. Récupérer le dernier trajet créé
    $ride_id = $db->query("SELECT id FROM rides ORDER BY id DESC LIMIT 1")->fetchColumn();

    if (!$passenger_id || !$ride_id) {
        throw new Exception("Données manquantes pour créer la réservation");
    }

    // 3. Créer la réservation
    $stmt = $db->prepare("INSERT INTO bookings 
        (passenger_id, ride_id, status, created_at)
        VALUES (:passenger_id, :ride_id, 'confirmed', NOW())");
    
    $stmt->execute([
        ':passenger_id' => $passenger_id,
        ':ride_id' => $ride_id
    ]);

    echo "Réservation créée avec succès :\n";
    echo "- Passager ID: $passenger_id\n";
    echo "- Trajet ID: $ride_id\n";

} catch (PDOException $e) {
    echo "Erreur PDO: " . $e->getMessage();
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
