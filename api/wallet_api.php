<?php
session_start();
require_once '../config/database.php';
require_once '../models/Wallet.php';

header('Content-Type: application/json');

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Non autorisé'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();
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
        $amount = floatval($_POST['amount'] ?? 0);
        $paymentMethod = $_POST['paymentMethod'] ?? '';
        $description = $_POST['description'] ?? 'Dépôt de fonds';
        $transactionId = $_POST['transaction_id'] ?? null; // optionnel pour KKiaPay

        if ($amount <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Le montant doit être supérieur à 0'
            ]);
            break;
        }

        // En mode sandbox, on accepte tous les paiements
        if ($paymentMethod === 'sandbox') {
            $result = $wallet->addFunds($userId, $amount, $description);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Fonds ajoutés avec succès' : 'Erreur lors de l\'ajout des fonds'
            ]);
        } elseif ($paymentMethod === 'kkiapay') {
            $result = $wallet->addFunds($userId, $amount, $description, $transactionId);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Fonds ajoutés avec succès via KKiaPay' : 'Erreur lors du traitement du paiement KKiaPay'
            ]);
        } else {
            // Autres méthodes de paiement
            $result = $wallet->addFunds($userId, $amount, $description);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Fonds ajoutés avec succès' : 'Erreur lors de l\'ajout des fonds'
            ]);
        }
        break;

    case 'withdrawFunds':
        $amount = floatval($_POST['amount'] ?? 0);
        $withdrawMethod = $_POST['withdrawMethod'] ?? '';
        $description = $_POST['description'] ?? 'Retrait de fonds';

        if ($amount <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Le montant doit être supérieur à 0'
            ]);
            break;
        }

        // Vérifier le solde disponible
        $balance = $wallet->getBalance($userId);
        if ($balance < $amount) {
            echo json_encode([
                'success' => false,
                'message' => 'Solde insuffisant'
            ]);
            break;
        }

        // En mode sandbox, on accepte tous les retraits
        if ($withdrawMethod === 'sandbox') {
            $result = $wallet->withdrawFunds($userId, $amount, $description);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Fonds retirés avec succès' : 'Erreur lors du retrait des fonds'
            ]);
        } else {
            // En mode démonstration, on accepte tous les retraits
            $result = $wallet->withdrawFunds($userId, $amount, $description);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Fonds retirés avec succès' : 'Erreur lors du retrait des fonds'
            ]);
        }
        break;

    case 'resetBalance':
        $amount = floatval($_POST['amount'] ?? 100);
        $result = $wallet->resetBalance($userId, $amount);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Solde réinitialisé avec succès' : 'Erreur lors de la réinitialisation du solde'
        ]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Action non reconnue'
        ]);
        break;
} 