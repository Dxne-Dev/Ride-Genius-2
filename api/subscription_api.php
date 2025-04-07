<?php
session_start();
require_once '../config/database.php';
require_once '../models/Subscription.php';
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
        
        if ($planType !== 'eco') {
            $balance = $wallet->getBalance($userId);
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
            if ($planType !== 'eco') {
                $wallet->withdrawFunds($userId, $planDetails['price'], 'Abonnement ' . strtoupper($planType));
            }
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

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Action non reconnue'
        ]);
        break;
} 