<?php
class Subscription {
    private $conn;
    private $table_name = "subscriptions";

    // Propriétés de l'abonnement
    public $id;
    public $user_id;
    public $plan_type; // 'eco', 'pro', 'business'
    public $start_date;
    public $end_date;
    public $status; // 'active', 'cancelled', 'expired'
    public $price;
    public $auto_renew;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Crée un nouvel abonnement
     * @return bool
     */
    public function create($data) {
        // Vérifier si l'utilisateur a déjà un abonnement actif
        if ($this->hasActiveSubscription($data['user_id'])) {
            return false;
        }

        $query = "INSERT INTO subscriptions (user_id, plan_type, start_date, end_date, status, price, auto_renew) 
                  VALUES (:user_id, :plan_type, :start_date, :end_date, :status, :price, :auto_renew)";
        
        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':plan_type' => $data['plan_type'],
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':status' => $data['status'],
            ':price' => $data['price'],
            ':auto_renew' => $data['auto_renew']
        ]);
    }

    /**
     * Récupère l'abonnement actif d'un utilisateur
     * @param int $user_id
     * @return mixed
     */
    public function getActiveSubscription($user_id) {
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE user_id = :user_id AND status = 'active'
                ORDER BY end_date DESC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si un utilisateur a un abonnement actif
     * @param int $user_id ID de l'utilisateur
     * @return bool
     */
    public function hasActiveSubscription($user_id) {
        $query = "SELECT COUNT(*) as count FROM subscriptions 
                  WHERE user_id = :user_id 
                  AND status = 'active' 
                  AND end_date > NOW()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }

    /**
     * Annule un abonnement
     * @param int $subscription_id
     * @return bool
     */
    public function cancelSubscription($subscription_id) {
        $query = "UPDATE " . $this->table_name . "
                SET status = 'cancelled', auto_renew = 0
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $subscription_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Met à jour le statut d'un abonnement
     * @param int $subscription_id
     * @param string $status
     * @return bool
     */
    public function updateStatus($subscription_id, $status) {
        $query = "UPDATE " . $this->table_name . "
                SET status = :status
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $subscription_id);
        $stmt->bindParam(":status", $status);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Récupère les détails d'un plan d'abonnement
     * @param string $plan_type
     * @return array
     */
    public function getPlanDetails($plan_type) {
        $plans = [
            'eco' => [
                'name' => 'Eco',
                'price' => 0,
                'description' => 'Plan de base',
                'features' => [
                    'Commission de 15% par trajet',
                    'Accès aux fonctionnalités de base',
                    'Support par email'
                ],
                'duration' => 30 // jours
            ],
            'protrajet' => [
                'name' => 'ProTrajet',
                'price' => 10000, // Prix mis à jour à 10 000 FCFA
                'description' => 'Plan professionnel',
                'features' => [
                    'Commission réduite à 10%',
                    'Support prioritaire',
                    'Statistiques avancées',
                    'Badge Pro sur votre profil'
                ],
                'duration' => 30 // jours
            ],
            'businesstrajet' => [
                'name' => 'BusinessTrajet',
                'price' => 20000, // Prix corrigé à 20 000 FCFA
                'description' => 'Plan business',
                'features' => [
                    'Commission minimale de 5%',
                    'Support VIP 24/7',
                    'Tableau de bord personnalisé',
                    'Badge Business Elite',
                    'Accès prioritaire aux nouvelles fonctionnalités'
                ],
                'duration' => 30 // jours
            ]
        ];

        return isset($plans[$plan_type]) ? $plans[$plan_type] : null;
    }

    /**
     * Met à jour le statut de renouvellement automatique
     * @param int $subscription_id
     * @param bool $auto_renew
     * @return bool
     */
    public function updateAutoRenew($subscription_id, $auto_renew) {
        $query = "UPDATE " . $this->table_name . "
                SET auto_renew = :auto_renew
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $subscription_id);
        $stmt->bindParam(":auto_renew", $auto_renew, PDO::PARAM_BOOL);

        return $stmt->execute();
    }
}