<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Wallet.php';

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
        if (!isset($_POST['amount']) || !isset($_POST['payment_method'])) {
            echo json_encode(['success' => false, 'message' => 'Données manquantes']);
            exit;
        }

        $amount = floatval($_POST['amount']);
        $paymentMethod = $_POST['payment_method'];
        $userId = $_SESSION['user_id'];

        // Validation du montant
        if ($amount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Le montant doit être supérieur à zéro']);
            exit;
        }

        // Ajout des fonds
        $result = $wallet->addFunds($userId, $amount, $paymentMethod, 'Dépôt de fonds via ' . $paymentMethod);

        if ($result) {
            $_SESSION['success'] = 'Fonds ajoutés avec succès';
            echo json_encode(['success' => true, 'message' => 'Fonds ajoutés avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout des fonds']);
        }
        break;

    case 'withdrawFunds':
        // Vérification des données
        if (!isset($_POST['amount']) || !isset($_POST['withdraw_method'])) {
            echo json_encode(['success' => false, 'message' => 'Données manquantes']);
            exit;
        }

        $amount = floatval($_POST['amount']);
        $withdrawMethod = $_POST['withdraw_method'];
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

        // Retrait des fonds
        $result = $wallet->withdrawFunds($userId, $amount, $withdrawMethod, 'Retrait de fonds via ' . $withdrawMethod);

        if ($result) {
            $_SESSION['success'] = 'Retrait effectué avec succès';
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