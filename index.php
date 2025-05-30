<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Inclure les configurations
require_once "config/database.php";
require_once "auth.php";

// Inclure les modèles
require_once "models/User.php";
require_once "models/Ride.php";
require_once "models/Booking.php";
require_once "models/Review.php";
require_once "models/Wallet.php";
require_once "models/Subscription.php";
require_once 'models/Message.php';
require_once 'models/Conversation.php';

// Inclure les services
require_once "services/ReviewService.php";

// Inclure les contrôleurs
require_once "controllers/AuthController.php";
require_once "controllers/UserController.php";
require_once "controllers/RideController.php";
require_once "controllers/BookingController.php";
require_once "controllers/ReviewController.php";
require_once "controllers/NotificationController.php";

// URL de base
$base_url = '/ride_genius';

// Page par défaut
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Router simple
switch($page) {
    // Pages publiques
    case 'register':
    case 'login':
    case 'verify-email':
    case 'resend-verification':
        $auth = new AuthController();
        // Mapper les pages aux méthodes correspondantes
        $methodMap = [
            'verify-email' => 'verifyEmail',
            'resend-verification' => 'resendVerification',
            'register' => 'register',
            'login' => 'login'
        ];

        if (isset($methodMap[$page])) {
            $method = $methodMap[$page];
            $auth->$method();
        } else {
            throw new Exception("Page non trouvée : $page");
        }
        break;
    
    // Pages protégées
    case 'profile':
    case 'edit-profile':
    case 'change-password':
    case 'rides':
    case 'ride-details':
    case 'create-ride':
    case 'edit-ride':
    case 'delete-ride':
    case 'my-rides':
    case 'search-rides':
    case 'book-ride':
    case 'my-bookings':
    case 'ride-bookings':
    case 'booking-details':
    case 'update-booking-status':
    case 'create-review':
    case 'my-reviews':
    case 'admin-dashboard':
    case 'admin-users':
    case 'admin-rides':
    case 'admin-reviews':
    case 'toggle-hide-review':
    case 'delete-review':
    case 'block-author':
    case 'messages':
    case 'wallet':
    case 'logout':
    case 'user-profile':
        // Pages de profil
        if ($page == 'profile') {
            $user = new UserController();
            $user->profile();
        } else if ($page == 'user-profile') {
            $user = new UserController();
            $user->userProfile();
        } else if ($page == 'edit-profile') {
            $user = new UserController();
            $user->editProfile();
        } else if ($page == 'change-password') {
            $user = new UserController();
            $user->changePassword();
        }
        // Pages de trajets
        if ($page == 'rides' || $page == 'ride-details' || $page == 'create-ride' || $page == 'edit-ride' || $page == 'delete-ride' || $page == 'my-rides' || $page == 'search-rides') {
            $ride = new RideController();
            if ($page == 'rides') {
                $ride->index();
            } else if ($page == 'ride-details') {
                $ride->show();
            } else if ($page == 'create-ride') {
                $ride->create();
            } else if ($page == 'edit-ride') {
                $ride->edit();
            } else if ($page == 'delete-ride') {
                $ride->delete();
            } else if ($page == 'my-rides') {
                $ride->myRides();
            } else if ($page == 'search-rides') {
                $ride->search();
            }
        }
        // Pages de réservations
        if ($page == 'book-ride') {
            $booking = new BookingController();
            $booking->create();
        } else if ($page == 'my-bookings') {
            $booking = new BookingController();
            $booking->myBookings();
        } else if ($page == 'ride-bookings') {
            $booking = new BookingController();
            $booking->rideBookings();
        } else if ($page == 'booking-details') {
            $booking = new BookingController();
            $booking->show();
        } else if ($page == 'update-booking-status') {
            $booking = new BookingController();
            $booking->updateStatus();
        }
        // Pages d'avis
        else if ($page == 'create-review' || $page == 'leave-review') {
            $review = new ReviewController();
            $review->create();
        } else if ($page == 'my-reviews') {
            $review = new ReviewController();
            $review->myReviews();
        } else if ($page == 'admin-reviews') {
            $review = new ReviewController();
            $review->adminReviews();
        } else if ($page == 'toggle-hide-review') {
            $review = new ReviewController();
            $review->toggleHide();
        } else if ($page == 'delete-review') {
            $review = new ReviewController();
            $review->deleteReview();
        } else if ($page == 'block-author') {
            $review = new ReviewController();
            $review->blockAuthor();
        }
        // Administration
        if ($page == 'admin-dashboard') {
            $user = new UserController();
            $user->adminDashboard();
        } else if ($page == 'admin-users') {
            $user = new UserController();
            $user->adminUsers();
        } else if ($page == 'admin-rides') {
            $ride = new RideController();
            $ride->adminRides();
        }
        // Page de messagerie
        if ($page == 'messages') {
            require_once 'controllers/MessageController.php';
            $database = new Database();
            $db = $database->getConnection();
            $message = new MessageController($db);
            $message->index();
        }
        // Page de wallet
        if ($page == 'wallet') {
            include "views/wallet/wallet.php";
        }
        // Page de déconnexion
        if ($page == 'logout') {
            $auth = new AuthController();
            $auth->logout();
        }
        
        // Pages de notifications
        if ($page == 'notifications') {
            $notification = new NotificationController();
            $notification->index();
        } else if ($page == 'mark-notification-read') {
            $notification = new NotificationController();
            $notification->markAsRead();
        } else if ($page == 'get-unread-notifications') {
            $notification = new NotificationController();
            $notification->getUnread();
        }
        break;
    case 'subscription':
        require_once "controllers/SubscriptionController.php";
        $database = new Database();
        $db = $database->getConnection();
        $subscription = new SubscriptionController($db);
        $subscription->showDetails();
        break;
    case 'subscribe':
        require_once "controllers/SubscriptionController.php";
        $database = new Database();
        $db = $database->getConnection();
        $subscription = new SubscriptionController($db);
        $subscription->subscribe();
        break;
    case 'subscription-details':
        require_once "controllers/SubscriptionController.php";
        $database = new Database();
        $db = $database->getConnection();
        $subscription = new SubscriptionController($db);
        $subscription->showDetails();
        break;
    case 'cancel-subscription':
        require_once "controllers/SubscriptionController.php";
        $database = new Database();
        $db = $database->getConnection();
        $subscription = new SubscriptionController($db);
        $subscription->cancel();
        break;
    case 'toggle-auto-renew':
        require_once "controllers/SubscriptionController.php";
        $database = new Database();
        $db = $database->getConnection();
        $subscription = new SubscriptionController($db);
        $subscription->toggleAutoRenew();
        break;
    // Page d'accueil par défaut
    default:
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $db = $database->getConnection();
        
        // Initialisation du modèle Subscription
        $subscription = new Subscription($db);
        
        // Vérifier si l'utilisateur est connecté et a un abonnement actif
        $hasActiveSubscription = false;
        if (isset($_SESSION['user_id'])) {
            $hasActiveSubscription = $subscription->hasActiveSubscription($_SESSION['user_id']);
        }
        
        include "views/home.php";
        break;
}

ob_end_flush();