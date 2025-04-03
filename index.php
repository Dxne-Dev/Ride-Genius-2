<?php
session_start();

// Inclure les configurations
require_once "config/database.php";

// Inclure les modèles
require_once "models/User.php";
require_once "models/Ride.php";
require_once "models/Booking.php";
require_once "models/Review.php";

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
    // Pages d'authentification
    case 'register':
        $auth = new AuthController();
        $auth->register();
        break;
    case 'login':
        $auth = new AuthController();
        $auth->login();
        break;
    case 'logout':
        $auth = new AuthController();
        $auth->logout();
        break;
    case 'verify-email':
        $auth = new AuthController();
        $auth->verifyEmail();
        break;
    case 'resend-verification':
        $auth = new AuthController();
        $auth->resendVerification();
        break;
    
    // Pages de profil
    case 'profile':
        $user = new UserController();
        $user->profile();
        break;
    case 'edit-profile':
        $user = new UserController();
        $user->editProfile();
        break;
    case 'change-password':
        $user = new UserController();
        $user->changePassword();
        break;
    
    // Pages de trajets
    case 'rides':
        $ride = new RideController();
        $ride->index();
        break;
    case 'ride-details':
        $ride = new RideController();
        $ride->show();
        break;
    case 'create-ride':
        $ride = new RideController();
        $ride->create();
        break;
    case 'edit-ride':
        $ride = new RideController();
        $ride->edit();
        break;
    case 'delete-ride':
        $ride = new RideController();
        $ride->delete();
        break;
    case 'my-rides':
        $ride = new RideController();
        $ride->myRides();
        break;
    case 'search-rides':
        $ride = new RideController();
        $ride->search();
        break;
    
    // Pages de réservations
    case 'book-ride':
        $booking = new BookingController();
        $booking->create();
        break;
    case 'my-bookings':
        $booking = new BookingController();
        $booking->myBookings();
        break;
    case 'ride-bookings':
        $booking = new BookingController();
        $booking->rideBookings();
        break;
    case 'booking-details':
        $booking = new BookingController();
        $booking->show();
        break;
    case 'update-booking-status':
        $booking = new BookingController();
        $booking->updateStatus();
        break;

    // Pages d'avis
    case 'create-review':
        $review = new ReviewController();
        $review->create();
        break;
    case 'my-reviews':
        $review = new ReviewController();
        $review->myReviews();
        break;
    
    // Administration
    case 'admin-dashboard':
        $user = new UserController();
        $user->adminDashboard();
        break;
    case 'admin-users':
        $user = new UserController();
        $user->adminUsers();
        break;
    case 'admin-rides':
        $ride = new RideController();
        $ride->adminRides();
        break;
    
    // Page de messagerie
    case 'messages':
        require_once "controllers/MessageController.php";
        $database = new Database();
        $db = $database->getConnection();
        $message = new MessageController($db);
        $message->index();
        break;
    // Page d'accueil par défaut
    default:
        include "views/home.php";
        break;
}