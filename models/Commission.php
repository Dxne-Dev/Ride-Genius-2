<?php
class Commission {
    private $db;
    private $table = 'commissions';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Calcule la commission selon le type d'abonnement
     * @param float $amount Montant du trajet
     * @param string $subscriptionType Type d'abonnement (eco, pro, business)
     * @return array ['amount' => montant commission, 'rate' => taux commission]
     */
    public function calculateCommission($amount, $subscriptionType) {
        $rates = [
            'eco' => 0.10,   // 10% pour les conducteurs eco (gratuit)
            'pro' => 0.02,   // 2% pour les conducteurs ProTrajet
            'business' => 0  // 0% pour les conducteurs BusinessTrajet
        ];

        $rate = $rates[$subscriptionType] ?? 0.10; // Par défaut 10%
        $commissionAmount = $amount * $rate;

        return [
            'amount' => $commissionAmount,
            'rate' => $rate * 100
        ];
    }

    public function createCommission($bookingId, $amount, $rate) {
        $query = "INSERT INTO " . $this->table . " 
                 (booking_id, amount, rate) 
                 VALUES (?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$bookingId, $amount, $rate]);
    }

    public function getCommissionByBooking($bookingId) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE booking_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$bookingId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCommissionStatus($bookingId, $status) {
        $query = "UPDATE " . $this->table . " 
                 SET status = ? 
                 WHERE booking_id = ?";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$status, $bookingId]);
    }
} 