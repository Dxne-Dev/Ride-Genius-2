<?php
class SubscriptionController {
    private $db;
    private $subscription;
    private $wallet;
    private $user;

    public function __construct($db) {
        $this->db = $db;
        $this->subscription = new Subscription($db);
        $this->wallet = new Wallet($db);
        $this->user = new User($db);
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    private function authGuard() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
    }

    /**
     * Affiche la page de détails de l'abonnement
     */
    public function showDetails() {
        $this->authGuard();
        
        // Récupérer l'abonnement actif
        $activeSubscription = $this->subscription->getActiveSubscription($_SESSION['user_id']);
        
        // Récupérer le solde du wallet
        $walletBalance = $this->wallet->getBalance($_SESSION['user_id']);
        
        // Inclure la vue
        include 'views/subscription/details.php';
    }

    /**
     * Traite la souscription à un plan
     */
    public function subscribe() {
        $this->authGuard();
        
        // Vérifier si un plan est spécifié
        if (!isset($_GET['plan'])) {
            $_SESSION['error'] = "Veuillez sélectionner un plan d'abonnement.";
            header('Location: index.php');
            exit;
        }

        $plan = $_GET['plan'];
        $planDetails = $this->subscription->getPlanDetails($plan);

        if (!$planDetails) {
            $_SESSION['error'] = "Le plan d'abonnement sélectionné n'est pas valide.";
            header('Location: index.php');
            exit;
        }

        // Vérifier si l'utilisateur a déjà un abonnement actif
        if ($this->subscription->hasActiveSubscription($_SESSION['user_id'])) {
            $_SESSION['error'] = "Vous avez déjà un abonnement actif. Veuillez annuler votre abonnement actuel avant d'en souscrire un nouveau.";
            header('Location: index.php?page=subscription-details');
            exit;
        }

        // Vérifier le solde du wallet
        $walletBalance = $this->wallet->getBalance($_SESSION['user_id']);
        if ($walletBalance < $planDetails['price']) {
            $_SESSION['error'] = "Solde insuffisant. Veuillez recharger votre portefeuille pour souscrire à ce plan.";
            header('Location: index.php?page=wallet');
            exit;
        }

        try {
            $this->db->beginTransaction();

            // Déduire le montant du wallet
            if (!$this->wallet->substractFromBalance($_SESSION['user_id'], $planDetails['price'])) {
                throw new Exception("Le paiement n'a pas pu être effectué. Veuillez réessayer.");
            }

            // Créer l'abonnement
            $subscriptionData = [
                'user_id' => $_SESSION['user_id'],
                'plan_type' => $plan,
                'start_date' => date('Y-m-d H:i:s'),
                'end_date' => date('Y-m-d H:i:s', strtotime('+' . $planDetails['duration'] . ' days')),
                'status' => 'active',
                'price' => $planDetails['price'],
                'auto_renew' => true
            ];

            if (!$this->subscription->create($subscriptionData)) {
                throw new Exception("L'abonnement n'a pas pu être créé. Veuillez réessayer.");
            }

            // Enregistrer la transaction
            $this->wallet->logTransaction(
                $_SESSION['user_id'],
                'debit',
                $planDetails['price'],
                'Souscription au plan ' . $planDetails['name']
            );

            $this->db->commit();
            $_SESSION['success'] = "Félicitations ! Votre abonnement au plan " . $planDetails['name'] . " a été activé avec succès.";
            header('Location: index.php?page=subscription-details');
            exit;

        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['error'] = "Une erreur est survenue lors de la souscription : " . $e->getMessage();
            header('Location: index.php');
            exit;
        }
    }

    /**
     * Annule l'abonnement actif
     */
    public function cancel() {
        $this->authGuard();
        
        $activeSubscription = $this->subscription->getActiveSubscription($_SESSION['user_id']);
        
        if (!$activeSubscription) {
            $_SESSION['error'] = "Vous n'avez pas d'abonnement actif à annuler.";
            header('Location: index.php?page=subscription-details');
            exit;
        }

        if ($this->subscription->cancelSubscription($activeSubscription['id'])) {
            $_SESSION['success'] = "Votre abonnement a été annulé avec succès. Vous conserverez les avantages jusqu'à la fin de la période payée.";
        } else {
            $_SESSION['error'] = "Une erreur est survenue lors de l'annulation de votre abonnement. Veuillez réessayer.";
        }

        header('Location: index.php?page=subscription-details');
        exit;
    }

    /**
     * Active ou désactive le renouvellement automatique
     */
    public function toggleAutoRenew() {
        $this->authGuard();
        
        $activeSubscription = $this->subscription->getActiveSubscription($_SESSION['user_id']);
        
        if (!$activeSubscription) {
            $_SESSION['error'] = "Vous n'avez pas d'abonnement actif.";
            header('Location: index.php?page=subscription-details');
            exit;
        }

        $newAutoRenewStatus = !$activeSubscription['auto_renew'];
        
        if ($this->subscription->updateAutoRenew($activeSubscription['id'], $newAutoRenewStatus)) {
            $_SESSION['success'] = $newAutoRenewStatus 
                ? "Le renouvellement automatique a été activé pour votre abonnement." 
                : "Le renouvellement automatique a été désactivé pour votre abonnement.";
        } else {
            $_SESSION['error'] = "Une erreur est survenue lors de la modification du renouvellement automatique. Veuillez réessayer.";
        }

        header('Location: index.php?page=subscription-details');
        exit;
    }
} 