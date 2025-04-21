<?php
class ReviewController {
    private $db;
    private $review;
    private $booking;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->review = new Review($this->db);
        $this->booking = new Booking($this->db);
    }

    // Protéger les routes
    private function authGuard() {
        if(!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page";
            header("Location: index.php?page=login");
            exit();
        }
    }
    
    // Créer un avis
    public function create() {
        $this->authGuard();
        
        if(!isset($_GET['booking_id']) || !isset($_GET['recipient_id'])) {
            header("Location: index.php?page=my-bookings");
            exit();
        }
        
        $booking_id = $_GET['booking_id'];
        $recipient_id = $_GET['recipient_id'];
        
        // Vérifier que l'utilisateur peut laisser un avis
        $this->review->booking_id = $booking_id;
        $this->review->author_id = $_SESSION['user_id'];
        
        if(!$this->review->canReview()) {
            $_SESSION['error'] = "Vous ne pouvez pas laisser un avis pour cette réservation";
            header("Location: index.php?page=my-bookings");
            exit();
        }
        
        // Traitement du formulaire
        if($_SERVER["REQUEST_METHOD"] == "POST") {
            $errors = [];
            
            if(!isset($_POST['rating']) || !is_numeric($_POST['rating']) || $_POST['rating'] < 1 || $_POST['rating'] > 5) {
                $errors[] = "La note doit être entre 1 et 5";
            }
            
            // Si pas d'erreurs, créer l'avis
            if(empty($errors)) {
                $this->review->booking_id = $booking_id;
                $this->review->author_id = $_SESSION['user_id'];
                $this->review->recipient_id = $recipient_id;
                $this->review->rating = $_POST['rating'];
                $this->review->comment = $_POST['comment'] ?? null;
                
                if($this->review->create()) {
                    $_SESSION['success'] = "Avis créé avec succès";
                    header("Location: index.php?page=my-bookings");
                    exit();
                } else {
                    $errors[] = "Une erreur est survenue. Veuillez réessayer.";
                }
            }
        }
        
        // Obtenir les détails de la réservation
        $this->booking->id = $booking_id;
        $booking_details = $this->booking->readOne();
        
        // Afficher la vue
        include "views/reviews/create.php";
    }

    // Laisser un avis
    public function leaveReview() {
        $this->authGuard();
        
        if (!isset($_GET['ride_id'])) {
            header("Location: index.php?page=my-bookings");
            exit();
        }
        
        $ride_id = $_GET['ride_id'];
        $this->review->ride_id = $ride_id;
        $this->review->author_id = $_SESSION['user_id'];
        
        // Vérifier que l'utilisateur peut laisser un avis
        if (!$this->review->canReview()) {
            $_SESSION['error'] = "Vous ne pouvez pas laisser un avis pour ce trajet";
            header("Location: index.php?page=my-bookings");
            exit();
        }
        
        // Traitement du formulaire
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $errors = [];
            
            if (!isset($_POST['rating']) || !is_numeric($_POST['rating']) || $_POST['rating'] < 1 || $_POST['rating'] > 5) {
                $errors[] = "La note doit être entre 1 et 5";
            }
            
            // Si pas d'erreurs, créer l'avis
            if (empty($errors)) {
                $this->review->recipient_id = $_POST['recipient_id'] ?? null;
                $this->review->rating = $_POST['rating'];
                $this->review->comment = $_POST['comment'] ?? null;
                
                if ($this->review->create()) {
                    $_SESSION['success'] = "Avis créé avec succès";
                    header("Location: index.php?page=my-bookings");
                    exit();
                } else {
                    $errors[] = "Une erreur est survenue. Veuillez réessayer.";
                }
            }
        }
        
        // Afficher la vue
        include "views/reviews/create.php";
    }
    
    // Mes avis reçus
    public function myReviews() {
        $this->authGuard();
        
        // Récupérer les avis reçus par l'utilisateur
        $this->review->recipient_id = $_SESSION['user_id'];
        $reviews = $this->review->readUserReviews();
        
        // Récupérer la note moyenne
        $rating_data = $this->review->getUserRating();
        
        // Afficher la vue
        include "views/reviews/my_reviews.php";
    }
}
