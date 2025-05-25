<?php
require_once 'services/ReviewService.php';

class ReviewController {
    private $db;
    private $reviewService;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->reviewService = new ReviewService($this->db);
    }

    // Protéger les routes
    private function authGuard() {
        if(!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page";
            header("Location: index.php?page=login");
            exit();
        }
    }
    
    // Gérer les erreurs de manière cohérente
    private function handleError($message, $redirect = 'my-bookings') {
        $_SESSION['error'] = $message;
        header("Location: index.php?page=$redirect");
        exit();
    }
    
    // Créer un avis
    public function create() {
        $this->authGuard();
        
        // Déterminer si on crée un avis à partir d'une réservation ou d'un trajet
        if (isset($_GET['booking_id']) || isset($_POST['booking_id'])) {
            $booking_id = $_GET['booking_id'] ?? $_POST['booking_id'];
            $recipient_id = $_GET['recipient_id'] ?? $_POST['recipient_id'];
            
            // Vérifier que l'utilisateur peut laisser un avis
            if (!$this->reviewService->canUserReview($_SESSION['user_id'], $booking_id)) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    echo json_encode(['success' => false, 'message' => "Vous ne pouvez pas laisser un avis pour cette réservation"]);
                    exit;
                } else {
                    $this->handleError("Vous ne pouvez pas laisser un avis pour cette réservation");
                }
            }
            
            // Obtenir les détails de la réservation
            $booking = new Booking($this->db);
            $booking->id = $booking_id;
            $booking_details = $booking->readOne();
        } elseif (isset($_GET['ride_id'])) {
            $ride_id = $_GET['ride_id'];
            
            // Vérifier que l'utilisateur peut laisser un avis
            if (!$this->reviewService->canUserReview($_SESSION['user_id'], $ride_id)) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    echo json_encode(['success' => false, 'message' => "Vous ne pouvez pas laisser un avis pour ce trajet"]);
                    exit;
                } else {
                    $this->handleError("Vous ne pouvez pas laisser un avis pour ce trajet");
                }
            }
        } else {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => "Paramètres manquants pour créer un avis"]);
                exit;
            } else {
                $this->handleError("Paramètres manquants pour créer un avis");
            }
        }
        
        // Traitement du formulaire
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $errors = [];
            
            if (!isset($_POST['rating']) || !is_numeric($_POST['rating']) || $_POST['rating'] < 1 || $_POST['rating'] > 5) {
                $errors[] = "La note doit être entre 1 et 5";
            }
            
            // Si pas d'erreurs, créer l'avis
            if (empty($errors)) {
                $recipient_id = isset($recipient_id) ? $recipient_id : ($_POST['recipient_id'] ?? null);
                $booking_id = isset($booking_id) ? $booking_id : null;
                
                if ($this->reviewService->createReview(
                    $_SESSION['user_id'],
                    $recipient_id,
                    $_POST['rating'],
                    $_POST['comment'] ?? null,
                    $booking_id
                )) {
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                        echo json_encode(['success' => true, 'message' => "Avis créé avec succès"]);
                        exit;
                    } else {
                        $_SESSION['success'] = "Avis créé avec succès";
                        header("Location: index.php?page=my-bookings");
                        exit();
                    }
                } else {
                    $errors[] = "Une erreur est survenue. Veuillez réessayer.";
                }
            }
            
            // Si erreurs et requête AJAX
            if (!empty($errors) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => implode(", ", $errors)]);
                exit;
            }
        }
        
        // Afficher la vue
        include "views/reviews/create.php";
    }
    
    // Mes avis
    public function myReviews() {
        $this->authGuard();
        
        // Rediriger les passagers vers la page d'avis conducteur
        if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'passager') {
            $this->driverReviews();
            return;
        }
        
        // Récupérer les avis reçus par l'utilisateur
        $reviews = $this->reviewService->getUserReviews($_SESSION['user_id']);
        
        // Récupérer la note moyenne
        $rating_data = $this->reviewService->getUserRating($_SESSION['user_id']);
        
        // Récupérer les informations de l'utilisateur
        $user = $this->reviewService->getUserInfo($_SESSION['user_id']);
        
        // Afficher la vue
        include "views/reviews/my_reviews.php";
    }
    
    // Avis conducteur (pour les passagers)
    public function driverReviews() {
        $this->authGuard();
        
        // Vérifier que l'utilisateur est un passager
        if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'passager') {
            $_SESSION['error'] = "Cette page est réservée aux passagers";
            header("Location: index.php?page=profile");
            exit();
        }
        
        include "views/reviews/driver_reviews.php";
    }
}
