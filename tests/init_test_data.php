<?php
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Wallet.php';

class TestDataInitializer {
    private $db;
    private $userModel;
    private $walletModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->userModel = new User($this->db);
        $this->walletModel = new Wallet($this->db);
    }

    public function initialize() {
        echo "<h2>Initialisation des données de test</h2>";

        // Créer un passager de test
        $passengerData = [
            'email' => 'passenger@test.com',
            'password' => password_hash('test123', PASSWORD_DEFAULT),
            'first_name' => 'Test',
            'last_name' => 'Passenger',
            'role' => 'passenger'
        ];
        $passengerId = $this->userModel->create($passengerData);
        echo "✅ Passager créé (ID: $passengerId)<br>";

        // Créer un conducteur gratuit
        $freeDriverData = [
            'email' => 'freedriver@test.com',
            'password' => password_hash('test123', PASSWORD_DEFAULT),
            'first_name' => 'Free',
            'last_name' => 'Driver',
            'role' => 'driver',
            'subscription_type' => 'free'
        ];
        $freeDriverId = $this->userModel->create($freeDriverData);
        echo "✅ Conducteur gratuit créé (ID: $freeDriverId)<br>";

        // Créer un conducteur Premium
        $premiumDriverData = [
            'email' => 'premiumdriver@test.com',
            'password' => password_hash('test123', PASSWORD_DEFAULT),
            'first_name' => 'Premium',
            'last_name' => 'Driver',
            'role' => 'driver',
            'subscription_type' => 'premium'
        ];
        $premiumDriverId = $this->userModel->create($premiumDriverData);
        echo "✅ Conducteur Premium créé (ID: $premiumDriverId)<br>";

        // Initialiser les portefeuilles
        $this->walletModel->addFunds($passengerId, 200.00, 'Initialisation test');
        echo "✅ Portefeuille passager initialisé (200€)<br>";

        $this->walletModel->addFunds($freeDriverId, 100.00, 'Initialisation test');
        echo "✅ Portefeuille conducteur gratuit initialisé (100€)<br>";

        $this->walletModel->addFunds($premiumDriverId, 100.00, 'Initialisation test');
        echo "✅ Portefeuille conducteur Premium initialisé (100€)<br>";

        echo "<br>Données de test initialisées avec succès !<br>";
        echo "Vous pouvez maintenant exécuter les tests de réservation.<br>";
    }
}

// Exécuter l'initialisation
$initializer = new TestDataInitializer();
$initializer->initialize(); 