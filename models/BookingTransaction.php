<?php
class BookingTransaction {
    private $db;
    private $table = 'booking_transactions';

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Crée une nouvelle transaction de réservation
     * @param array $data Les données de la transaction
     * @return int|bool L'ID de la transaction ou false en cas d'échec
     */
    public function create($data) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (booking_id, passenger_id, driver_id, amount, commission_amount, status) 
                     VALUES (:booking_id, :passenger_id, :driver_id, :amount, :commission_amount, :status)";
            
            $stmt = $this->db->prepare($query);
            
            // Paramètres requis
            $stmt->bindParam(':booking_id', $data['booking_id']);
            $stmt->bindParam(':amount', $data['amount']);
            $stmt->bindParam(':status', $data['status']);
            
            // Obtenir les IDs du passager et du conducteur à partir de la réservation
            $bookingQuery = "SELECT passenger_id, driver_id FROM bookings WHERE id = ?";
            $bookingStmt = $this->db->prepare($bookingQuery);
            $bookingStmt->execute([$data['booking_id']]);
            $bookingData = $bookingStmt->fetch(PDO::FETCH_ASSOC);
            
            // Paramètres de la réservation
            $passengerId = $bookingData['passenger_id'];
            $driverId = $bookingData['driver_id'];
            $stmt->bindParam(':passenger_id', $passengerId);
            $stmt->bindParam(':driver_id', $driverId);
            
            // Paramètre commission
            $commissionAmount = isset($data['commission_amount']) ? $data['commission_amount'] : 0;
            $stmt->bindParam(':commission_amount', $commissionAmount);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("Erreur de création de transaction: " . $e->getMessage());
            return false;
        }
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