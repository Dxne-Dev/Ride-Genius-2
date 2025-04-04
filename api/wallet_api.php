<?php
session_start();
require_once '../config/database.php';
require_once '../models/Wallet.php';

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Non autorisé'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];
$wallet = new Wallet($db);

// Récupération de l'action demandée
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'getBalance':
        $balance = $wallet->getBalance($userId);
        echo json_encode([
            'success' => true,
            'balance' => $balance
        ]);
        break;

    case 'getTransactions':
        $transactions = $wallet->getTransactions($userId);
        echo json_encode([
            'success' => true,
            'transactions' => $transactions
        ]);
        break;

    case 'addFunds':
        // Validation des données
        if (!isset($_POST['amount']) || !isset($_POST['paymentMethod'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Données manquantes'
            ]);
            exit();
        }

        $amount = floatval($_POST['amount']);
        $paymentMethod = $_POST['paymentMethod'];
        $description = $_POST['description'] ?? '';

        // Validation du montant
        if ($amount <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Le montant doit être supérieur à 0'
            ]);
            exit();
        }

        // Ajout des fonds
        if ($wallet->addFunds($userId, $amount, $paymentMethod, $description)) {
            echo json_encode([
                'success' => true,
                'message' => 'Fonds ajoutés avec succès'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout des fonds'
            ]);
        }
        break;

    case 'withdrawFunds':
        // Validation des données
        if (!isset($_POST['amount']) || !isset($_POST['withdrawMethod'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Données manquantes'
            ]);
            exit();
        }

        $amount = floatval($_POST['amount']);
        $withdrawMethod = $_POST['withdrawMethod'];
        $description = $_POST['description'] ?? '';

        // Validation du montant
        if ($amount <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Le montant doit être supérieur à 0'
            ]);
            exit();
        }

        // Vérification du solde
        $currentBalance = $wallet->getBalance($userId);
        if ($amount > $currentBalance) {
            echo json_encode([
                'success' => false,
                'message' => 'Solde insuffisant'
            ]);
            exit();
        }

        // Retrait des fonds
        if ($wallet->withdrawFunds($userId, $amount, $withdrawMethod, $description)) {
            echo json_encode([
                'success' => true,
                'message' => 'Retrait effectué avec succès'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du retrait des fonds'
            ]);
        }
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Action non reconnue'
        ]);
        break;
} 