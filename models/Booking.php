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
        // Vérifier les places disponibles
        $check_query = "SELECT available_seats FROM rides WHERE id = ? LIMIT 1";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(1, $this->ride_id);
        $check_stmt->execute();
        $ride = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$ride || $ride['available_seats'] < $this->seats) {
            return false;
        }
        
        // Transaction pour créer la réservation et mettre à jour les places
        $this->conn->beginTransaction();
        
        try {
            // Insérer la réservation
            $query = "INSERT INTO " . $this->table . "
                    SET
                        ride_id = :ride_id,
                        passenger_id = :passenger_id,
                        seats = :seats,
                        status = :status";

            $stmt = $this->conn->prepare($query);

            // Nettoyage des données
            $this->ride_id = htmlspecialchars(strip_tags($this->ride_id));
            $this->passenger_id = htmlspecialchars(strip_tags($this->passenger_id));
            $this->seats = htmlspecialchars(strip_tags($this->seats));
            $this->status = htmlspecialchars(strip_tags($this->status));

            // Binding des paramètres
            $stmt->bindParam(":ride_id", $this->ride_id);
            $stmt->bindParam(":passenger_id", $this->passenger_id);
            $stmt->bindParam(":seats", $this->seats);
            $stmt->bindParam(":status", $this->status);

            $stmt->execute();
            
            // Mettre à jour les places disponibles
            $update_query = "UPDATE rides SET available_seats = available_seats - :seats WHERE id = :ride_id";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(":seats", $this->seats);
            $update_stmt->bindParam(":ride_id", $this->ride_id);
            $update_stmt->execute();
            
            $this->conn->commit();
            return true;
            
        } catch(Exception $e) {
            $this->conn->rollBack();
            return false;
        }
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
        // Si on annule la réservation, remettre les places disponibles
        if($this->status == 'cancelled' || $this->status == 'rejected') {
            // Transaction
            $this->conn->beginTransaction();
            
            try {
                $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                
                $this->status = htmlspecialchars(strip_tags($this->status));
                $this->id = htmlspecialchars(strip_tags($this->id));
                
                $stmt->bindParam(':status', $this->status);
                $stmt->bindParam(':id', $this->id);
                
                $stmt->execute();
                
                // Récupérer les informations de la réservation
                $get_booking = "SELECT ride_id, seats FROM " . $this->table . " WHERE id = :id";
                $get_stmt = $this->conn->prepare($get_booking);
                $get_stmt->bindParam(':id', $this->id);
                $get_stmt->execute();
                
                $booking = $get_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Mettre à jour les places disponibles
                $update_query = "UPDATE rides SET available_seats = available_seats + :seats WHERE id = :ride_id";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(":seats", $booking['seats']);
                $update_stmt->bindParam(":ride_id", $booking['ride_id']);
                $update_stmt->execute();
                
                $this->conn->commit();
                return true;
                
            } catch(Exception $e) {
                $this->conn->rollBack();
                return false;
            }
        } else {
            $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            
            $this->status = htmlspecialchars(strip_tags($this->status));
            $this->id = htmlspecialchars(strip_tags($this->id));
            
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':id', $this->id);
            
            if($stmt->execute()) {
                return true;
            }
            
            return false;
        }
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
}
