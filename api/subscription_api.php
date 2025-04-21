<?php
session_start();
require_once '../config/database.php';
require_once '../models/Subscription.php';
require_once '../models/Wallet.php';

header('Content-Type: application/json');

// Ajout de logs pour déboguer les actions et la session
error_log("Action reçue : " . json_encode($_POST['action']));
error_log("Session utilisateur : " . json_encode($_SESSION));
error_log("Request Data : " . json_encode($_POST)); // Log the entire POST data

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    $action = $_POST['action'] ?? '';
    if ($action === 'subscribe') {
        echo json_encode([
            'success' => false,
            'message' => 'Vous devez être connecté pour souscrire à un abonnement'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Non autorisé'
        ]);
    }
    exit();
}

$userId = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();
$subscription = new Subscription($db);
$wallet = new Wallet($db);

// Récupération de l'action demandée
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'getActiveSubscription':
        $activeSubscription = $subscription->getActiveSubscription($userId);
        echo json_encode([
            'success' => true,
            'subscription' => $activeSubscription
        ]);
        break;

    case 'subscribe':
        $planType = $_POST['plan_type'] ?? '';
        $autoRenew = $_POST['auto_renew'] ?? 0;

        // Vérifier si l'utilisateur est un conducteur
        if ($_SESSION['user_role'] !== 'conducteur') {
            echo json_encode([
                'success' => false,
                'message' => 'Seuls les conducteurs peuvent souscrire à un abonnement'
            ]);
            break;
        }

        if (!in_array($planType, ['eco', 'pro', 'business'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Type de plan invalide'
            ]);
            break;
        }

        if ($subscription->hasActiveSubscription($userId)) {
            echo json_encode([
                'success' => false,
                'message' => 'Vous avez déjà un abonnement actif'
            ]);
            break;
        }

        $planDetails = $subscription->getPlanDetails($planType);
        if (!$planDetails) {
            echo json_encode([
                'success' => false,
                'message' => 'Type de plan invalide'
            ]);
            break;
        }

        // Vérifier le solde du wallet
        $balance = $wallet->getBalance($userId);
        if ($balance < 200) {
            echo json_encode([
                'success' => false,
                'message' => 'Vous devez avoir au moins 200 FCFA dans votre wallet pour souscrire à un abonnement',
                'redirect' => 'wallet'
            ]);
            break;
        }

        if ($planType !== 'eco') {
            if ($balance < $planDetails['price']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Solde insuffisant pour souscrire à cet abonnement'
                ]);
                break;
            }

            $description = "Abonnement " . $planDetails['name'] . " - " . $planType;
            $withdrawResult = $wallet->withdrawFunds($userId, $planDetails['price'], $description);

            if (!$withdrawResult) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur lors du paiement'
                ]);
                break;
            }
        }

        $subscriptionData = [
            'user_id' => $userId,
            'plan_type' => $planType,
            'start_date' => date('Y-m-d H:i:s'),
            'end_date' => date('Y-m-d H:i:s', strtotime('+' . $planDetails['duration'] . ' days')),
            'status' => 'active',
            'price' => $planDetails['price'],
            'auto_renew' => $autoRenew
        ];

        if ($subscription->create($subscriptionData)) {
            echo json_encode([
                'success' => true,
                'message' => 'Abonnement souscrit avec succès',
                'subscription' => [
                    'plan_type' => $planType,
                    'start_date' => $subscriptionData['start_date'],
                    'end_date' => $subscriptionData['end_date'],
                    'price' => $subscriptionData['price']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'abonnement'
            ]);
        }
        break;

    case 'cancelSubscription':
        $activeSubscription = $subscription->getActiveSubscription($userId);
        
        if (!$activeSubscription) {
            echo json_encode([
                'success' => false,
                'message' => 'Aucun abonnement actif trouvé'
            ]);
            break;
        }
        
        if ($subscription->cancelSubscription($activeSubscription['id'])) {
            echo json_encode([
                'success' => true,
                'message' => 'Abonnement annulé avec succès'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de l\'abonnement'
            ]);
        }
        break;

    case 'updateAutoRenew':
        $autoRenew = $_POST['auto_renew'] ?? 0;
        $activeSubscription = $subscription->getActiveSubscription($userId);
        
        if (!$activeSubscription) {
            echo json_encode([
                'success' => false,
                'message' => 'Aucun abonnement actif trouvé'
            ]);
            break;
        }
        
        $query = "UPDATE subscriptions SET auto_renew = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$autoRenew, $activeSubscription['id']])) {
            echo json_encode([
                'success' => true,
                'message' => 'Renouvellement automatique mis à jour avec succès'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du renouvellement automatique'
            ]);
        }
        break;

    case 'getPlanDetails':
        $planType = $_POST['plan_type'] ?? '';
        
        if (!in_array($planType, ['eco', 'pro', 'business'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Type de plan invalide'
            ]);
            break;
        }
        
        $planDetails = $subscription->getPlanDetails($planType);
        
        if ($planDetails) {
            echo json_encode([
                'success' => true,
                'plan' => $planDetails
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Type de plan invalide'
            ]);
        }
        break;

    case 'checkAuth':
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => true,
                'message' => 'Utilisateur connecté',
                'user_id' => $_SESSION['user_id']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Utilisateur non connecté'
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