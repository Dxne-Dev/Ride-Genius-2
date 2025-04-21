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
        $this->review->ride_id = $rideId;
        $this->review->author_id = $userId;
        return $this->review->canReview();
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