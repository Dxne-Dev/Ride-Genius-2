<?php
require 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$queries = [
    "ALTER TABLE messages ADD INDEX idx_sender_receiver (sender_id, receiver_id)",
    "ALTER TABLE messages ADD INDEX idx_receiver_sender (receiver_id, sender_id)",
    "ALTER TABLE messages ADD INDEX idx_created_at (created_at)",
    "ALTER TABLE bookings ADD INDEX idx_passenger_ride (passenger_id, ride_id)",
    "ALTER TABLE rides ADD INDEX idx_driver (driver_id)"
];

foreach ($queries as $query) {
    try {
        $db->exec($query);
        echo "Succès: $query\n";
    } catch (PDOException $e) {
        echo "Erreur sur $query: " . $e->getMessage() . "\n";
    }
}

echo "Migration des index terminée.\n";
