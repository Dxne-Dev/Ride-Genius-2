<?php
require_once 'models/Review.php';
require_once 'models/Booking.php';
require_once 'models/User.php';

class ReviewService {
    private $db;
    private $review;
    private $booking;
    
    public function __construct($db) {
        $this->db = $db;
        $this->review = new Review($db);
        $this->booking = new Booking($db);
    }
    
    public function canUserReview($userId, $bookingId) {
        // Vérifier si l'utilisateur est un passager
        $user = new User($this->db);
        $user->id = $userId;
        $user->readOne();
        
        if ($user->role !== 'passager') {
            return false; // Seuls les passagers peuvent laisser des avis
        }

        // Vérifier si la réservation existe et est marquée comme terminée
        $query = "SELECT b.*, b.updated_at as completed_at 
                  FROM bookings b 
                  WHERE b.id = :booking_id 
                  AND b.passenger_id = :user_id 
                  AND b.status = 'completed'";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":booking_id", $bookingId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            return false; // La réservation n'existe pas ou n'est pas terminée
        }
        
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Vérifier si la période d'évaluation de 7 jours est dépassée
        $completedDate = new DateTime($booking['completed_at']);
        $currentDate = new DateTime();
        $daysDifference = $currentDate->diff($completedDate)->days;
        
        if ($daysDifference > 7) {
            return false; // La période d'évaluation de 7 jours est dépassée
        }
        
        // Vérifier si l'utilisateur a déjà laissé un avis pour cette réservation
        $query = "SELECT * FROM reviews WHERE author_id = :user_id AND booking_id = :booking_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->bindParam(":booking_id", $bookingId);
        $stmt->execute();
        
        return $stmt->rowCount() === 0; // Retourne true si aucun avis n'a été laissé pour cette réservation
    }
    
    public function createReview($userId, $recipientId, $rating, $comment, $bookingId = null) {
        $this->review->author_id = $userId;
        $this->review->recipient_id = $recipientId;
        $this->review->rating = $rating;
        $this->review->comment = $comment;
        
        if ($bookingId) {
            $this->review->booking_id = $bookingId;
        }
        
        return $this->review->create();
    }
    
    public function getUserReviews($userId) {
        $this->review->recipient_id = $userId;
        return $this->review->readUserReviews();
    }
    
    public function getUserRating($userId) {
        $this->review->recipient_id = $userId;
        return $this->review->getUserRating();
    }
    
    public function getUserInfo($userId) {
        $user = new User($this->db);
        $user->id = $userId;
        $user->readOne();
        return $user;
    }
} 