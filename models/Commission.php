<?php
class Commission {
    private $db;
    private $table = 'commissions';

    public function __construct($db) {
        $this->db = $db;
    }

    public function calculateCommission($amount, $subscriptionType) {
        $rates = [
            'free' => 0.10,    // 10%
            'basic' => 0.08,   // 8%
            'premium' => 0.05  // 5%
        ];

        $rate = $rates[$subscriptionType] ?? $rates['free'];
        return $amount * $rate;
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