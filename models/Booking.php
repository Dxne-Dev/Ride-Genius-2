<?php
class Booking {
    private $conn;
    private $table = "bookings";

    // propriétés
    public $id;
    public $ride_id;
    public $passenger_id;
    public $seats;
    public $status;
    public $created_at;
    
    // propriétés jointes
    public $passenger_name;
    public $ride_details;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer une nouvelle réservation
    public function create() {
        $sql = "INSERT INTO bookings (ride_id, passenger_id, seats, status) VALUES (:ride_id, :passenger_id, :seats, :status)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':ride_id', $this->ride_id, PDO::PARAM_INT);
        $stmt->bindParam(':passenger_id', $this->passenger_id, PDO::PARAM_INT);
        $stmt->bindParam(':seats', $this->seats, PDO::PARAM_INT);
        $stmt->bindParam(':status', $this->status, PDO::PARAM_STR);
        return $stmt->execute();
    }
    
    // Lire toutes les réservations d'un passager
    public function readPassengerBookings() {
        $query = "SELECT b.*, 
                  CONCAT(r.departure, ' → ', r.destination, ' le ', DATE_FORMAT(r.departure_time, '%d/%m/%Y à %H:%i')) as ride_details
                  FROM " . $this->table . " b
                  LEFT JOIN rides r ON b.ride_id = r.id
                  WHERE b.passenger_id = ?
                  ORDER BY r.departure_time ASC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->passenger_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Lire toutes les réservations pour un trajet
    public function readRideBookings() {
        $query = "SELECT 
                b.*,
                CONCAT(u.first_name, ' ', u.last_name) as passenger_name,
                u.email as passenger_email,
                u.phone as passenger_phone,
                r.departure,
                r.destination
              FROM " . $this->table . " b
              LEFT JOIN users u ON b.passenger_id = u.id
              LEFT JOIN rides r ON b.ride_id = r.id
              WHERE b.ride_id = ?
              ORDER BY b.created_at DESC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->ride_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Lire une réservation
    public function readOne() {
        $query = "SELECT 
                    b.*, 
                    r.departure,
                    r.destination,
                    r.departure_time,
                    r.available_seats,
                    r.price,
                    r.status as ride_status,
                    r.driver_id,
                    u.first_name as passenger_first_name,
                    u.last_name as passenger_last_name,
                    u.email as passenger_email,
                    u.phone as passenger_phone
                  FROM " . $this->table . " b
                  LEFT JOIN rides r ON b.ride_id = r.id
                  LEFT JOIN users u ON b.passenger_id = u.id
                  WHERE b.id = ? 
                  LIMIT 1";
    
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Mettre à jour le statut d'une réservation
    public function updateStatus() {
        $sql = "UPDATE bookings SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $this->status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    // Vérifier si un utilisateur a déjà réservé un trajet
    public function checkExistingBooking() {
        $query = "SELECT * FROM " . $this->table . " WHERE ride_id = ? AND passenger_id = ? AND status NOT IN ('cancelled', 'rejected')";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(1, $this->ride_id);
        $stmt->bindParam(2, $this->passenger_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return true;
        }
        
        return false;
    }

    // Obtenir les détails d'une réservation
    public function getBookingDetails($booking_id) {
        $sql = "SELECT * FROM bookings WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $booking_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Supprimer une réservation
   // Supprimer une réservation
public function delete() {
    $query = "DELETE FROM " . $this->table . " WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    $this->id = htmlspecialchars(strip_tags($this->id));
    $stmt->bindParam(":id", $this->id);
    return $stmt->execute();
}

    public function countByStatus($status) {
        $query = "SELECT COUNT(*) as total FROM bookings WHERE status = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$status]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    // Compter le nombre total de réservations
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Obtenir les statistiques de commission
    public function getCommissionStats() {
        // Calculer le montant total des réservations
        $query = "SELECT 
                    COUNT(*) as total_bookings,
                    SUM(b.seats * r.price) as total_amount,
                    SUM(b.seats * r.price * 0.10) as total_commission
                  FROM " . $this->table . " b
                  JOIN rides r ON b.ride_id = r.id
                  WHERE b.status = 'completed'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculer les statistiques par mois pour le graphique
        $monthlyQuery = "SELECT 
                          DATE_FORMAT(b.created_at, '%Y-%m') as month,
                          COUNT(*) as bookings,
                          SUM(b.seats * r.price) as amount,
                          SUM(b.seats * r.price * 0.10) as commission
                        FROM " . $this->table . " b
                        JOIN rides r ON b.ride_id = r.id
                        WHERE b.status = 'completed'
                        GROUP BY DATE_FORMAT(b.created_at, '%Y-%m')
                        ORDER BY month DESC
                        LIMIT 12";
        
        $monthlyStmt = $this->conn->prepare($monthlyQuery);
        $monthlyStmt->execute();
        $monthlyData = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'total' => $result,
            'monthly' => $monthlyData
        ];
    }
}
