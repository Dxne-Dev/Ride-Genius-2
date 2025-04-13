<?php
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

// Inclure les contrôleurs
require_once "controllers/AuthController.php";
require_once "controllers/UserController.php";
require_once "controllers/RideController.php";
require_once "controllers/BookingController.php";
require_once "controllers/ReviewController.php";

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
        $auth->$page();
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
    case 'messages':
    case 'wallet':
    case 'logout':
        // Pages de profil
        if ($page == 'profile') {
            $user = new UserController();
            $user->profile();
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
        if ($page == 'create-review') {
            $review = new ReviewController();
            $review->create();
        } else if ($page == 'my-reviews') {
            $review = new ReviewController();
            $review->myReviews();
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