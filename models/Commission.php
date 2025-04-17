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
            'eco' => 0.15,   // 15% pour les conducteurs eco
            'pro' => 0.10,   // 10% pour les conducteurs ProTrajet
            'business' => 0.05  // 5% pour les conducteurs BusinessTrajet
        ];

        $rate = $rates[$subscriptionType] ?? 0.15; // Par défaut 15%
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

    public function getTodayTotal() {
        $query = "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM commissions 
                 WHERE DATE(created_at) = CURDATE() 
                 AND status = 'completed'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    public function getWeeklyData() {
        $query = "SELECT 
                    DAYOFWEEK(created_at) as day,
                    COALESCE(SUM(amount), 0) as total
                 FROM commissions 
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 AND status = 'completed'
                 GROUP BY DAYOFWEEK(created_at)
                 ORDER BY DAYOFWEEK(created_at)";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        // Initialiser un tableau avec 7 jours à 0
        $weekData = array_fill(0, 7, 0);
        
        // Remplir avec les données réelles
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $weekData[$row['day'] - 1] = (float)$row['total'];
        }
        
        return $weekData;
    }
} 