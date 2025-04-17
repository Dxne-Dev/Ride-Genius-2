<?php
class Ride {
    private $conn;
    private $table = "rides";

    // propriétés
    public $id;
    public $driver_id;
    public $departure;
    public $destination;
    public $departure_time;
    public $available_seats;
    public $price;
    public $description;
    public $status;
    public $created_at;
    
    // propriétés jointes
    public $driver_name;
    public $driver_email;
    public $driver_phone;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer un nouveau trajet
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                SET
                    driver_id = :driver_id,
                    departure = :departure,
                    destination = :destination,
                    departure_time = :departure_time,
                    available_seats = :available_seats,
                    price = :price,
                    description = :description,
                    status = :status";

        $stmt = $this->conn->prepare($query);

        // Nettoyage des données
        $this->driver_id = htmlspecialchars(strip_tags($this->driver_id));
        $this->departure = htmlspecialchars(strip_tags($this->departure));
        $this->destination = htmlspecialchars(strip_tags($this->destination));
        $this->departure_time = htmlspecialchars(strip_tags($this->departure_time));
        $this->available_seats = htmlspecialchars(strip_tags($this->available_seats));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->status = htmlspecialchars(strip_tags($this->status));

        // Binding des paramètres
        $stmt->bindParam(":driver_id", $this->driver_id);
        $stmt->bindParam(":departure", $this->departure);
        $stmt->bindParam(":destination", $this->destination);
        $stmt->bindParam(":departure_time", $this->departure_time);
        $stmt->bindParam(":available_seats", $this->available_seats);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    // Lire tous les trajets
    public function read($limit = null) {
        $query = "SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as driver_name 
                  FROM " . $this->table . " r
                  LEFT JOIN users u ON r.driver_id = u.id
                  ORDER BY r.departure_time ASC";
                  
        if ($limit !== null) {
            $query .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Rechercher des trajets
    public function search($departure, $destination, $date) {
        $query = "SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as driver_name 
                  FROM " . $this->table . " r
                  LEFT JOIN users u ON r.driver_id = u.id
                  WHERE r.departure LIKE ? AND r.destination LIKE ? 
                  AND DATE(r.departure_time) = ? AND r.status = 'active'
                  ORDER BY r.departure_time ASC";
        
        $stmt = $this->conn->prepare($query);
        
        $departure = "%{$departure}%";
        $destination = "%{$destination}%";
        
        $stmt->bindParam(1, $departure);
        $stmt->bindParam(2, $destination);
        $stmt->bindParam(3, $date);
        
        $stmt->execute();
        
        return $stmt;
    }
    
    // Lire un trajet
    public function readOne() {
        $query ="SELECT 
                r.*,
                CONCAT(u.first_name, ' ', u.last_name) as driver_name,
                u.email as driver_email,
                u.phone as driver_phone
              FROM rides r
              LEFT JOIN users u ON r.driver_id = u.id
              WHERE r.id = ?
              LIMIT 1";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id = $row['id'];
            $this->driver_id = $row['driver_id'];
            $this->departure = $row['departure'];
            $this->destination = $row['destination'];
            $this->departure_time = $row['departure_time'];
            $this->available_seats = $row['available_seats'];
            $this->price = $row['price'];
            $this->description = $row['description'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->driver_name = $row['driver_name'];
            $this->driver_email = $row['driver_email'];
            $this->driver_phone = $row['driver_phone'];
            
            return true;
        }
        
        return false;
    }
    
    // Mise à jour d'un trajet
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET
                    departure = :departure,
                    destination = :destination,
                    departure_time = :departure_time,
                    available_seats = :available_seats,
                    price = :price,
                    description = :description,
                    status = :status
                WHERE
                    id = :id AND driver_id = :driver_id";
                    
        $stmt = $this->conn->prepare($query);
        
        // Nettoyage des données
        $this->departure = htmlspecialchars(strip_tags($this->departure));
        $this->destination = htmlspecialchars(strip_tags($this->destination));
        $this->departure_time = htmlspecialchars(strip_tags($this->departure_time));
        $this->available_seats = htmlspecialchars(strip_tags($this->available_seats));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->driver_id = htmlspecialchars(strip_tags($this->driver_id));
        
        // Binding des paramètres
        $stmt->bindParam(':departure', $this->departure);
        $stmt->bindParam(':destination', $this->destination);
        $stmt->bindParam(':departure_time', $this->departure_time);
        $stmt->bindParam(':available_seats', $this->available_seats);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':driver_id', $this->driver_id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Mettre à jour les places disponibles
    public function updateAvailableSeats($seats) {
        $query = "UPDATE " . $this->table . "
                SET available_seats = available_seats - :seats
                WHERE id = :id AND available_seats >= :seats";
                
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':seats', $seats);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute() && $stmt->rowCount() > 0) {
            return true;
        }
        
        return false;
    }
    
    // Supprimer un trajet
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = ? AND driver_id = ?";
        $stmt = $this->conn->prepare($query);
        
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->driver_id = htmlspecialchars(strip_tags($this->driver_id));
        
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $this->driver_id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Obtenir les trajets d'un conducteur
    public function getDriverRides() {
        $query = "SELECT * FROM " . $this->table . " WHERE driver_id = ? ORDER BY departure_time ASC";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(1, $this->driver_id);
        $stmt->execute();
        
        return $stmt;
    }

    // Mettre à jour le nombre de places disponibles
    public function updateSeats() {
        $query = "UPDATE " . $this->table . "
                SET available_seats = :available_seats
                WHERE id = :id";
                
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':available_seats', $this->available_seats);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    public function countByStatus($status) {
        $query = "SELECT COUNT(*) as total FROM rides WHERE status = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$status]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    // Compter le nombre total de trajets
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
