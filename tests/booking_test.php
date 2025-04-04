<?php
require_once '../config/database.php';
require_once '../models/Booking.php';
require_once '../models/Commission.php';
require_once '../models/BookingTransaction.php';
require_once '../models/Wallet.php';
require_once '../controllers/BookingController.php';

class BookingTest {
    private $db;
    private $bookingController;
    private $walletModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->bookingController = new BookingController();
        $this->walletModel = new Wallet($this->db);
    }

    public function runTests() {
        echo "<h2>Tests du système de réservation</h2>";

        // Test 1: Création d'une réservation avec un conducteur gratuit
        $this->testFreeDriverBooking();

        // Test 2: Création d'une réservation avec un conducteur Premium
        $this->testPremiumDriverBooking();

        // Test 3: Annulation d'une réservation
        $this->testBookingCancellation();

        // Test 4: Complétion d'une réservation
        $this->testBookingCompletion();
    }

    private function testFreeDriverBooking() {
        echo "<h3>Test 1: Réservation avec conducteur gratuit</h3>";
        
        // Données de test
        $data = [
            'passenger_id' => 1, // ID d'un passager existant
            'driver_id' => 2,    // ID d'un conducteur gratuit
            'amount' => 50.00,   // Montant du trajet
            'pickup_location' => 'Paris',
            'dropoff_location' => 'Lyon',
            'pickup_time' => '2024-03-20 10:00:00'
        ];

        // Vérifier le solde initial du passager
        $initialBalance = $this->walletModel->getBalance($data['passenger_id']);
        echo "Solde initial du passager: " . $initialBalance . "€<br>";

        // Créer la réservation
        $result = $this->bookingController->createBooking($data);
        
        if ($result['success']) {
            echo "✅ Réservation créée avec succès<br>";
            echo "ID de la réservation: " . $result['booking_id'] . "<br>";
            echo "Commission: " . $result['commission']['amount'] . "€ (taux: " . ($result['commission']['rate'] * 100) . "%)<br>";
            
            // Vérifier le nouveau solde du passager
            $newBalance = $this->walletModel->getBalance($data['passenger_id']);
            echo "Nouveau solde du passager: " . $newBalance . "€<br>";
        } else {
            echo "❌ Erreur lors de la création de la réservation: " . $result['message'] . "<br>";
        }
    }

    private function testPremiumDriverBooking() {
        echo "<h3>Test 2: Réservation avec conducteur Premium</h3>";
        
        // Données de test
        $data = [
            'passenger_id' => 1, // ID d'un passager existant
            'driver_id' => 3,    // ID d'un conducteur Premium
            'amount' => 50.00,   // Montant du trajet
            'pickup_location' => 'Paris',
            'dropoff_location' => 'Lyon',
            'pickup_time' => '2024-03-20 10:00:00'
        ];

        // Vérifier le solde initial du passager
        $initialBalance = $this->walletModel->getBalance($data['passenger_id']);
        echo "Solde initial du passager: " . $initialBalance . "€<br>";

        // Créer la réservation
        $result = $this->bookingController->createBooking($data);
        
        if ($result['success']) {
            echo "✅ Réservation créée avec succès<br>";
            echo "ID de la réservation: " . $result['booking_id'] . "<br>";
            echo "Commission: " . $result['commission']['amount'] . "€ (taux: " . ($result['commission']['rate'] * 100) . "%)<br>";
            
            // Vérifier le nouveau solde du passager
            $newBalance = $this->walletModel->getBalance($data['passenger_id']);
            echo "Nouveau solde du passager: " . $newBalance . "€<br>";
        } else {
            echo "❌ Erreur lors de la création de la réservation: " . $result['message'] . "<br>";
        }
    }

    private function testBookingCancellation() {
        echo "<h3>Test 3: Annulation d'une réservation</h3>";
        
        // ID d'une réservation existante
        $bookingId = 1;
        $userId = 1; // ID du passager

        // Vérifier le solde initial du passager
        $initialBalance = $this->walletModel->getBalance($userId);
        echo "Solde initial du passager: " . $initialBalance . "€<br>";

        // Annuler la réservation
        $result = $this->bookingController->cancelBooking($bookingId, $userId);
        
        if ($result['success']) {
            echo "✅ Réservation annulée avec succès<br>";
            
            // Vérifier le nouveau solde du passager
            $newBalance = $this->walletModel->getBalance($userId);
            echo "Nouveau solde du passager: " . $newBalance . "€<br>";
        } else {
            echo "❌ Erreur lors de l'annulation de la réservation: " . $result['message'] . "<br>";
        }
    }

    private function testBookingCompletion() {
        echo "<h3>Test 4: Complétion d'une réservation</h3>";
        
        // ID d'une réservation existante
        $bookingId = 2;

        // Compléter la réservation
        $result = $this->bookingController->completeBooking($bookingId);
        
        if ($result['success']) {
            echo "✅ Réservation complétée avec succès<br>";
            
            // Récupérer les détails de la transaction
            $transaction = $this->bookingController->transactionModel->getTransactionByBooking($bookingId);
            echo "Montant transféré au conducteur: " . $transaction['amount'] . "€<br>";
        } else {
            echo "❌ Erreur lors de la complétion de la réservation: " . $result['message'] . "<br>";
        }
    }
}

// Exécuter les tests
$test = new BookingTest();
$test->runTests(); 