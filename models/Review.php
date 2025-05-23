<?php
class Review {
    private $conn;
    private $table = "reviews";

    // propriétés
    public $id;
    public $booking_id;
    public $author_id;
    public $recipient_id;
    public $rating;
    public $comment;
    public $created_at;
    
    // propriétés jointes
    public $author_name;
    public $recipient_name;
    public $ride_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer un nouvel avis
    public function create() {
        // Vérifier si un avis existe déjà pour cette réservation par cet auteur
        $check_query = "SELECT * FROM " . $this->table . " WHERE booking_id = ? AND author_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(1, $this->booking_id);
        $check_stmt->bindParam(2, $this->author_id);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            return false; // Un avis existe déjà
        }

        $query = "INSERT INTO " . $this->table . "
                SET
                    booking_id = :booking_id,
                    author_id = :author_id,
                    recipient_id = :recipient_id,
                    rating = :rating,
                    comment = :comment";

        $stmt = $this->conn->prepare($query);

        // Nettoyage des données
        $this->booking_id = htmlspecialchars(strip_tags($this->booking_id));
        $this->author_id = htmlspecialchars(strip_tags($this->author_id));
        $this->recipient_id = htmlspecialchars(strip_tags($this->recipient_id));
        $this->rating = htmlspecialchars(strip_tags($this->rating));
        $this->comment = htmlspecialchars(strip_tags($this->comment));

        // Binding des paramètres
        $stmt->bindParam(":booking_id", $this->booking_id);
        $stmt->bindParam(":author_id", $this->author_id);
        $stmt->bindParam(":recipient_id", $this->recipient_id);
        $stmt->bindParam(":rating", $this->rating);
        $stmt->bindParam(":comment", $this->comment);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    // Lire tous les avis reçus par un utilisateur
    public function readUserReviews() {
        $query = "SELECT r.id, r.rating, r.comment, r.created_at, 
                     CONCAT(u.first_name, ' ', u.last_name) as author_name
                  FROM " . $this->table . " r
                  LEFT JOIN users u ON r.author_id = u.id
                  WHERE r.recipient_id = ?
                  ORDER BY r.created_at DESC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->recipient_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Vérifier si un utilisateur peut laisser un avis
    public function canReview() {
        // Vérifier si l'utilisateur est un passager
        $user_query = "SELECT type FROM users WHERE id = ?";
        $user_stmt = $this->conn->prepare($user_query);
        $user_stmt->bindParam(1, $this->author_id);
        $user_stmt->execute();
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user['type'] !== 'passenger') {
            return false; // Seuls les passagers peuvent laisser des avis
        }

        // Vérifier si l'utilisateur a réservé ce trajet et s'il a payé
        $query = "SELECT b.*, p.status as payment_status 
                  FROM bookings b 
                  LEFT JOIN payments p ON b.id = p.booking_id 
                  WHERE b.ride_id = :ride_id 
                  AND b.passenger_id = :author_id 
                  AND b.status = 'completed'
                  AND p.status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ride_id", $this->ride_id);
        $stmt->bindParam(":author_id", $this->author_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            return false;
        }
        
        // Récupérer l'ID de la réservation
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->booking_id = $booking['id'];
        $this->recipient_id = $booking['driver_id'];
        
        // Vérifier si un avis existe déjà pour cette réservation
        $check_query = "SELECT * FROM " . $this->table . " WHERE booking_id = ? AND author_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(1, $this->booking_id);
        $check_stmt->bindParam(2, $this->author_id);
        $check_stmt->execute();
        
        return $check_stmt->rowCount() == 0; // Peut laisser un avis si aucun n'existe déjà
    }
    
    // Obtenir la note moyenne d'un utilisateur
    public function getUserRating() {
        $query = "SELECT AVG(rating) as average_rating, COUNT(*) as total_reviews 
                  FROM " . $this->table . " 
                  WHERE recipient_id = ?";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->recipient_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lire tous les avis
    public function readAll() {
        $query = "SELECT r.*, 
                         CONCAT(u.first_name, ' ', u.last_name) as author_name,
                         CONCAT(ru.first_name, ' ', ru.last_name) as recipient_name
                  FROM " . $this->table . " r
                  LEFT JOIN users u ON r.author_id = u.id
                  LEFT JOIN users ru ON r.recipient_id = ru.id
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Compter le nombre total d'avis
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
