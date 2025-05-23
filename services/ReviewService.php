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
    
    public function canUserReview($userId, $rideId) {
        // Vérifier si l'utilisateur est un passager
        $user = new User($this->db);
        $user->id = $userId;
        $user->readOne();
        
        if ($user->type !== 'passenger') {
            return false; // Seuls les passagers peuvent laisser des avis
        }

        // Vérifier si l'utilisateur a réservé ce trajet et s'il a payé
        $query = "SELECT b.*, p.status as payment_status 
                  FROM bookings b 
                  LEFT JOIN payments p ON b.id = p.booking_id 
                  WHERE b.ride_id = :ride_id 
                  AND b.passenger_id = :user_id 
                  AND b.status = 'completed'
                  AND p.status = 'completed'";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":ride_id", $rideId);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
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