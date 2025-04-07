<?php
require 'config/database.php';

try {
    $db = (new Database())->getConnection();

    // Récupérer l'id du conducteur
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => 'conducteur@test.com']);
    $driver_id = $stmt->fetchColumn();

    if (!$driver_id) {
        throw new Exception("Conducteur test non trouvé");
    }

    // Créer un trajet avec requête préparée
    $stmt = $db->prepare("INSERT INTO rides 
        (driver_id, departure, destination, departure_time, available_seats, price, status) 
        VALUES (:driver_id, :departure, :destination, :departure_time, :seats, :price, 'active')");
    
    $stmt->execute([
        ':driver_id' => $driver_id,
        ':departure' => 'Paris',
        ':destination' => 'Lyon',
        ':departure_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
        ':seats' => 3,
        ':price' => 30
    ]);

    echo "Trajet créé avec succès pour le conducteur ID: $driver_id";
} catch (PDOException $e) {
    echo "Erreur PDO: " . $e->getMessage();
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
