<?php
class BookingTransaction {
    private $db;
    private $table = 'booking_transactions';

    public function __construct($db) {
        $this->db = $db;
    }

    public function createTransaction($bookingId, $passengerId, $driverId, $amount, $commissionAmount) {
        $query = "INSERT INTO " . $this->table . " 
                 (booking_id, passenger_id, driver_id, amount, commission_amount) 
                 VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$bookingId, $passengerId, $driverId, $amount, $commissionAmount]);
    }

    public function getTransactionByBooking($bookingId) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE booking_id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$bookingId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStatus($bookingId, $status) {
        $query = "UPDATE " . $this->table . " 
                 SET status = ? 
                 WHERE booking_id = ?";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$status, $bookingId]);
    }

    public function getDriverTransactions($driverId) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE driver_id = ? 
                 ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPassengerTransactions($passengerId) {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE passenger_id = ? 
                 ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$passengerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 