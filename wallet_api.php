<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Wallet.php';
require_once 'models/KkiaPayAPI.php';

// Définir l'en-tête JSON
header('Content-Type: application/json');

try {
    // Initialisation de la base de données
    $database = new Database();
    $db = $database->getConnection();

    // Vérification de la session
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        exit;
    }

    // Initialisation des modèles
    $user = new User($db);
    $wallet = new Wallet($db);

    // Récupération de l'action
    $action = $_POST['action'] ?? '';

    // Traitement des actions
    switch ($action) {
        case 'addFunds':
            // Vérification des données
            if (!isset($_POST['amount']) || !isset($_POST['transaction_id'])) {
                echo json_encode(['success' => false, 'message' => 'Données manquantes']);
                exit;
            }

            $amount = floatval($_POST['amount']);
            $transactionId = $_POST['transaction_id'];
            $userId = $_SESSION['user_id'];

            // Validation du montant
            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Le montant doit être supérieur à zéro']);
                exit;
            }

            // Ajout des fonds via KKiaPay
            $result = $wallet->addFundsViaKkiaPay($userId, $amount, $transactionId);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Dépôt effectué avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors du dépôt des fonds']);
            }
            break;

        case 'withdrawFunds':
            // Vérification des données
            if (!isset($_POST['amount']) || !isset($_POST['transaction_id'])) {
                echo json_encode(['success' => false, 'message' => 'Données manquantes']);
                exit;
            }

            $amount = floatval($_POST['amount']);
            $transactionId = $_POST['transaction_id'];
            $userId = $_SESSION['user_id'];

            // Validation du montant
            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Le montant doit être supérieur à zéro']);
                exit;
            }

            // Vérification du solde
            $balance = $wallet->getBalance($userId);
            if ($amount > $balance) {
                echo json_encode(['success' => false, 'message' => 'Solde insuffisant']);
                exit;
            }

            // Retrait des fonds via KKiaPay
            $result = $wallet->withdrawFundsViaKkiaPay($userId, $amount, $transactionId);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Retrait effectué avec succès']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors du retrait des fonds']);
            }
            break;

        case 'getTransactions':
            $userId = $_SESSION['user_id'];
            $transactions = $wallet->getTransactions($userId);
            echo json_encode(['success' => true, 'transactions' => $transactions]);
            break;

        case 'getBalance':
            $userId = $_SESSION['user_id'];
            $balance = $wallet->getBalance($userId);
            echo json_encode(['success' => true, 'balance' => $balance]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
    exit;
} 