<?php
require_once 'services/ReviewService.php';

class ReviewController {
    // Affichage sécurisé de la page admin-reviews
    public function adminReviews() {
        $this->authGuard();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            $this->handleError("Accès refusé", 'home');
        }
        $database = new Database();
        $db = $database->getConnection();
        $reviewModel = new Review($db);
        // Charger tous les avis, y compris masqués
        $reviews = $reviewModel->readAll(true);
        $reviews_arr = [];
        while($row = $reviews->fetch(PDO::FETCH_ASSOC)) {
            $reviews_arr[] = $row;
        }
        // Pagination
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = 10;
        $total_reviews = count($reviews_arr);
        $total_pages = ceil($total_reviews / $per_page);
        $start = ($page - 1) * $per_page;
        $reviews_page = array_slice($reviews_arr, $start, $per_page);
        include 'views/reviews/admin_reviews.php';
    }

    // ...
    // Action : masquer/afficher un avis
    public function toggleHide() {
        $this->authGuard();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            $this->handleError("Accès refusé", 'admin-reviews');
        }
        $review_id = $_GET['id'] ?? null;
        $hide = isset($_GET['hide']) ? (int)$_GET['hide'] : 1;
        if ($review_id) {
            $review = new Review($this->db);
            $review->setHidden($review_id, $hide);
            $_SESSION['success'] = $hide ? "Avis masqué." : "Avis affiché.";
        }
        header('Location: index.php?page=admin-reviews');
        exit();
    }
    // Action : supprimer un avis
    public function deleteReview() {
        $this->authGuard();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            $this->handleError("Accès refusé", 'admin-reviews');
        }
        $review_id = $_GET['id'] ?? null;
        if ($review_id) {
            $review = new Review($this->db);
            $review->deleteReview($review_id);
            $_SESSION['success'] = "Avis supprimé.";
        }
        header('Location: index.php?page=admin-reviews');
        exit();
    }
    // Action : bloquer/débloquer un passager pour les commentaires
    public function blockAuthor() {
        $this->authGuard();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            $this->handleError("Accès refusé", 'admin-reviews');
        }
        $author_id = $_GET['author_id'] ?? null;
        $until = $_GET['until'] ?? null; // format: Y-m-d H:i:s ou null pour débloquer
        if ($author_id) {
            $review = new Review($this->db);
            $review->blockAuthor($author_id, $until);
            if ($until) {
                $_SESSION['success'] = "Passager bloqué jusqu'au $until.";
            } else {
                $_SESSION['success'] = "Passager débloqué.";
            }
        }
        header('Location: index.php?page=admin-reviews');
        exit();
    }

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
        
        // Vérifier que l'utilisateur est un passager
        if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'passager') {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => "Seuls les passagers peuvent laisser des avis"]);
                exit;
            } else {
                $this->handleError("Seuls les passagers peuvent laisser des avis", "profile");
            }
        }
        // Vérifier si l'utilisateur est bloqué pour les commentaires
        $review = new Review($this->db);
        $blocked_until = $review->isAuthorBlocked($_SESSION['user_id']);
        if ($blocked_until) {
            $msg = "Vous ne pouvez pas laisser d'avis. Votre accès aux commentaires est bloqué jusqu'au " . date('d/m/Y H:i', strtotime($blocked_until)) . ".";
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            } else {
                $this->handleError($msg, "profile");
            }
        }
        
        // Déterminer si on crée un avis à partir d'une réservation
        if (isset($_GET['booking_id']) || isset($_POST['booking_id'])) {
            $booking_id = $_GET['booking_id'] ?? $_POST['booking_id'];
            $recipient_id = $_GET['recipient_id'] ?? $_POST['recipient_id'];
            
            // Vérifier que la réservation est terminée et que l'utilisateur peut laisser un avis
            if (!$this->reviewService->canUserReview($_SESSION['user_id'], $booking_id)) {
                // Récupérer les détails de la réservation pour déterminer la raison exacte
                $booking = new Booking($this->db);
                $booking->id = $booking_id;
                $booking_details = $booking->readOne();
                
                $errorMessage = "Vous ne pouvez pas laisser un avis pour cette réservation.";
                
                if (!$booking_details || $booking_details['status'] !== 'completed') {
                    $errorMessage .= " Assurez-vous que le conducteur a marqué la réservation comme terminée.";
                } else {
                    // Vérifier si la période de 7 jours est dépassée
                    $completedDate = new DateTime($booking_details['updated_at']);
                    $currentDate = new DateTime();
                    $daysDifference = $currentDate->diff($completedDate)->days;
                    
                    if ($daysDifference > 7) {
                        $errorMessage .= " La période d'évaluation de 7 jours après la fin du trajet est dépassée.";
                    } else {
                        // Vérifier si l'utilisateur a déjà laissé un avis
                        $query = "SELECT * FROM reviews WHERE author_id = ? AND booking_id = ?";
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([$_SESSION['user_id'], $booking_id]);
                        
                        if ($stmt->rowCount() > 0) {
                            $errorMessage .= " Vous avez déjà laissé un avis pour cette réservation.";
                        }
                    }
                }
                
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    echo json_encode(['success' => false, 'message' => $errorMessage]);
                    exit;
                } else {
                    $this->handleError($errorMessage, "driver-reviews");
                }
            }
            
            // Obtenir les détails de la réservation
            $booking = new Booking($this->db);
            $booking->id = $booking_id;
            $booking_details = $booking->readOne();
        } else {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'message' => "Paramètres manquants pour créer un avis"]);
                exit;
            } else {
                $this->handleError("Paramètres manquants pour créer un avis", "driver-reviews");
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
