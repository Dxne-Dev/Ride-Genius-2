<?php
class BookingController {
    private $db;
    private $booking;
    private $ride;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->booking = new Booking($this->db);
        $this->ride = new Ride($this->db);
    }

    // Protéger les routes
    private function authGuard() {
        if(!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page";
            header("Location: index.php?page=login");
            exit();
        }
    }
    
    // Créer une réservation
    public function create() {
        $this->authGuard();
        
        if(!isset($_GET['ride_id'])) {
            header("Location: index.php?page=rides");
            exit();
        }
        
        $ride_id = $_GET['ride_id'];
        $this->ride->id = $ride_id;
        
        // Vérifier que le trajet existe
        if(!$this->ride->readOne()) {
            $_SESSION['error'] = "Trajet introuvable";
            header("Location: index.php?page=rides");
            exit();
        }
        
        // Vérifier que l'utilisateur n'est pas le conducteur
        if($this->ride->driver_id == $_SESSION['user_id']) {
            $_SESSION['error'] = "Vous ne pouvez pas réserver votre propre trajet";
            header("Location: index.php?page=ride-details&id=$ride_id");
            exit();
        }
        
        // Vérifier si l'utilisateur a déjà réservé ce trajet
        $this->booking->ride_id = $ride_id;
        $this->booking->passenger_id = $_SESSION['user_id'];
        
        if($this->booking->checkExistingBooking()) {
            $_SESSION['error'] = "Vous avez déjà réservé ce trajet";
            header("Location: index.php?page=ride-details&id=$ride_id");
            exit();
        }
        
        // Traitement du formulaire
        if($_SERVER["REQUEST_METHOD"] == "POST") {
            $errors = [];
            
            if(empty($_POST['seats']) || !is_numeric($_POST['seats']) || $_POST['seats'] <= 0) {
                $errors[] = "Le nombre de places doit être un nombre positif";
            } elseif($_POST['seats'] > $this->ride->available_seats) {
                $errors[] = "Il n'y a pas assez de places disponibles";
            }
            
            // Si pas d'erreurs, créer la réservation
            if(empty($errors)) {
                $this->booking->ride_id = $ride_id;
                $this->booking->passenger_id = $_SESSION['user_id'];
                $this->booking->seats = $_POST['seats'];
                $this->booking->status = 'pending'; // Status set to pending when a booking is created

                
                if($this->booking->create()) {
                    $_SESSION['success'] = "Réservation créée avec succès";
                    header("Location: index.php?page=my-bookings");
                    exit();
                } else {
                    $errors[] = "Une erreur est survenue. Veuillez réessayer.";
                }
            }
        }
        
        // **Passer la variable $ride à la vue**
        $ride = $this->ride;
        
        include "views/bookings/create.php";
    }
    
    
    // Mes réservations
    public function myBookings() {
        $this->authGuard();
        
        $this->booking->passenger_id = $_SESSION['user_id'];
        $stmt = $this->booking->readPassengerBookings();
        
        // Afficher la vue
        include "views/bookings/my-bookings.php";
    }
    
    // Détails d'une réservation
    public function show() {
        if (!isset($_GET['id'])) {
            header("Location: index.php?page=my-bookings");
            exit();
        }
        
        // Récupérer les détails de la réservation à partir de son ID
        $this->booking->id = $_GET['id'];
        $booking_details = $this->booking->readOne();
        
        if (!$booking_details) {
            $_SESSION['error'] = "Réservation introuvable";
            header("Location: index.php?page=my-bookings");
            exit();
        }
        
        // Utiliser le ride_id contenu dans la réservation pour récupérer les détails du trajet
        $ride_id = $booking_details['ride_id'];
        $this->ride->id = $ride_id;
        
        if (!$this->ride->readOne()) {
            $_SESSION['error'] = "Trajet introuvable";
            header("Location: index.php?page=my-bookings");
            exit();
        }
        
        // On définit $has_booked en fonction du statut de la réservation
$has_booked = ($booking_details['status'] !== 'cancelled'); 

// Définir les variables pour la vue
$ride = $this->ride;
// On peut également passer $booking_details à la vue si besoin
include "views/rides/show.php";
    }
    
    
    
    // Réservations pour un trajet (conducteur)
    public function rideBookings() {
        $this->authGuard();
        
        if(!isset($_GET['ride_id'])) {
            header("Location: index.php?page=my-rides");
            exit();
        }
        
        $ride_id = $_GET['ride_id'];
        $this->ride->id = $ride_id;
        
        // Vérifier que le trajet existe et appartient au conducteur
        $this->ride->driver_id = $_SESSION['user_id'];
        if(!$this->ride->readOne() && $_SESSION['user_role'] != 'admin') {
            $_SESSION['error'] = "Trajet introuvable ou vous n'êtes pas le conducteur de ce trajet";
            header("Location: index.php?page=my-rides");
            exit();
        }
        
        $this->booking->ride_id = $ride_id;
        $stmt = $this->booking->readRideBookings();
        
        // Afficher la vue
        include "views/bookings/ride_bookings.php";
    }
    
    // Mettre à jour le statut d'une réservation
    public function updateStatus() {
        $this->authGuard();
        
        if(!isset($_GET['id']) || !isset($_GET['status'])) {
            header("Location: index.php");
            exit();
        }
        
        $id = $_GET['id'];
        $status = $_GET['status'];
        $return_url = $_GET['return'] ?? 'my-bookings';
        
        // Vérifier que le statut est valide et correspond aux statuts définis

        $valid_statuses = ['accepted', 'rejected', 'cancelled', 'completed']; // Added 'completed' to valid statuses

        if(!in_array($status, $valid_statuses)) {
            $_SESSION['error'] = "Statut invalide";
            header("Location: index.php?page=$return_url");
            exit();
        }
        
        $this->booking->id = $id;
        $booking_details = $this->booking->readOne();
        
        if(!$booking_details) {
            $_SESSION['error'] = "Réservation introuvable";
            header("Location: index.php?page=$return_url");
            exit();
        }
        
        // Vérifier les permissions
        $can_update = false;
        
        // Le conducteur peut accepter ou rejeter
        if(($status == 'accepted' || $status == 'rejected') && $booking_details['driver_id'] == $_SESSION['user_id']) {
            $can_update = true;
        }
        
        // Le passager peut annuler sa réservation
        if($status == 'cancelled' && $booking_details['passenger_id'] == $_SESSION['user_id']) {
            $can_update = true;
        }
        
        // L'admin peut tout faire
        if($_SESSION['user_role'] == 'admin') {
            $can_update = true;
        }
        
        if(!$can_update) {
            $_SESSION['error'] = "Vous n'avez pas la permission de mettre à jour cette réservation";
            header("Location: index.php?page=$return_url");
            exit();
        }
        
        // Mettre à jour le statut de la réservation en fonction des permissions
        // Mettre à jour le statut de la réservation

        $this->booking->id = $id;
        $this->booking->status = $status;
        
        if($this->booking->updateStatus()) {
            $_SESSION['success'] = "Statut de la réservation mis à jour avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour du statut";
        }
        
        header("Location: index.php?page=$return_url");
        exit();
    }

        // Marquer une réservation comme terminée après le trajet

    public function markAsCompleted() {
        $this->authGuard();
        
        if (!isset($_GET['id'])) {
            header("Location: index.php?page=my-rides");
            exit();
        }
        
        $booking_id = $_GET['id'];
        $this->booking->id = $booking_id;
        
        // Vérifier que la réservation existe et appartient au conducteur
        $booking_details = $this->booking->readOne();
        if (!$booking_details || $booking_details['driver_id'] != $_SESSION['user_id']) {
            $_SESSION['error'] = "Réservation introuvable ou vous n'êtes pas le conducteur de cette réservation";
            header("Location: index.php?page=my-rides");
            exit();
        }
        
        // Mettre à jour le statut de la réservation
        $this->booking->status = 'completed';
        if ($this->booking->updateStatus()) {
            $_SESSION['success'] = "Réservation marquée comme terminée avec succès";
        } else {
            $_SESSION['error'] = "Une erreur est survenue. Veuillez réessayer.";
        }
        
        header("Location: index.php?page=ride-bookings&ride_id=" . $booking_details['ride_id']);
        exit();
    }
}
