<?php
require 'config/database.php';
$db = (new Database())->getConnection();

// Récupérer l'id du conducteur
$stmt = $db->query("SELECT id FROM users WHERE email = 'conducteur@test.com' LIMIT 1");
$driver_id = $stmt->fetchColumn();

// Créer un trajet
$db->exec("INSERT INTO rides (driver_id, departure, arrival, date, available_seats, price) 
    VALUES ($driver_id, 'Paris', 'Lyon', NOW(), 3, 30)");

echo "Trajet créé avec succès pour le conducteur ID: $driver_id";
