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
                $this->booking->status = 'pending'; // Statut par défaut
                if($this->booking->create()) {
                    $_SESSION['success'] = "Réservation créée avec succès";
                    header("Location: index.php?page=my-bookings");
                    exit();
                } else {
                    $errors[] = "Une erreur est survenue. Veuillez réessayer.";
                }
            }
        }
        
        // Passer la variable $ride à la vue
        $ride = $this->ride;
        include "views/bookings/create.php";
    }

    // Mes réservations
    public function myBookings() {
        $this->authGuard();
        $this->booking->passenger_id = $_SESSION['user_id'];
        $stmt = $this->booking->readPassengerBookings();
        include "views/bookings/my-bookings.php";
    }

    // Détails d'une réservation
    public function show() {
        if (!isset($_GET['id'])) {
            header("Location: index.php?page=my-bookings");
            exit();
        }

        $this->booking->id = $_GET['id'];
        $booking_details = $this->booking->readOne();
        if (!$booking_details) {
            $_SESSION['error'] = "Réservation introuvable";
            header("Location: index.php?page=my-bookings");
            exit();
        }

        $ride_id = $booking_details['ride_id'];
        $this->ride->id = $ride_id;
        if (!$this->ride->readOne()) {
            $_SESSION['error'] = "Trajet introuvable";
            header("Location: index.php?page=my-bookings");
            exit();
        }
        
        // Définir $has_booked en fonction du statut de la réservation
        $has_booked = ($booking_details['status'] !== 'cancelled'); 
        $ride = $this->ride;
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
        include "views/bookings/ride_bookings.php";
    }

    // Mise à jour du statut d'une réservation (fusion de updateStatus() et markAsCompleted())
    public function updateStatus() {
        $this->authGuard();
        if(!isset($_GET['id']) || !isset($_GET['status'])) {
            header("Location: index.php");
            exit();
        }

        $booking_id = $_GET['id'];
        $newStatus = $_GET['status'];
        $return_url = $_GET['return'] ?? 'my-bookings';

        // Liste des statuts autorisés pour la réservation
        $valid_statuses = ['accepted', 'rejected', 'cancelled', 'completed'];
        if(!in_array($newStatus, $valid_statuses)) {
            $_SESSION['error'] = "Statut invalide";
            header("Location: index.php?page=$return_url");
            exit();
        }

        $this->booking->id = $booking_id;
        $booking_details = $this->booking->readOne();
        if(!$booking_details) {
            $_SESSION['error'] = "Réservation introuvable";
            header("Location: index.php?page=$return_url");
            exit();
        }

        $user_id = $_SESSION['user_id'];
        $user_role = $_SESSION['user_role'];
        $isPassenger = ($booking_details['passenger_id'] == $user_id);
        $isDriver = ($booking_details['driver_id'] == $user_id);

        // Cas pour le Passager : il peut uniquement annuler sa réservation (si elle est en attente)
        if($user_role == 'passager' && $isPassenger) {
            if($newStatus !== 'cancelled' || $booking_details['status'] != 'pending') {
                $_SESSION['error'] = "Action non autorisée pour le passager.";
                header("Location: index.php?page=my-bookings");
                exit();
            }
        }
        // Cas pour le Conducteur : il peut accepter, rejeter, terminer ou annuler (sur son trajet)
        elseif($user_role == 'conducteur' && $isDriver) {
            $allowedStatuses = ['accepted', 'rejected', 'completed', 'cancelled'];
            if(!in_array($newStatus, $allowedStatuses)) {
                $_SESSION['error'] = "Statut non autorisé.";
                header("Location: index.php?page=my-rides");
                exit();
            }
        }
        // L'admin peut tout faire
        elseif($user_role == 'admin') {
            // Pas de restriction supplémentaire
        }
        else {
            $_SESSION['error'] = "Vous n'avez pas la permission de modifier cette réservation";
            header("Location: index.php?page=$return_url");
            exit();
        }

        $this->booking->status = $newStatus;
        if($this->booking->updateStatus()) {
            $_SESSION['success'] = "Statut de la réservation mis à jour avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour du statut";
        }

        header("Location: index.php?page=$return_url");
        exit();
    }

    // Suppression d'une réservation (inchangée)
    public function delete() {
        $this->authGuard();
        if(isset($_POST['booking_id']) && isset($_SESSION['user_id'])) {
            $booking_id = $_POST['booking_id'];
            $booking_details = $this->booking->getBookingDetails($booking_id);
            if($booking_details['passenger_id'] == $_SESSION['user_id'] || $booking_details['driver_id'] == $_SESSION['user_id']) {
                $this->booking->id = $booking_id;
                if($this->booking->delete()) {
                    $_SESSION['success'] = "Réservation supprimée avec succès";
                } else {
                    $_SESSION['error'] = "Erreur lors de la suppression de la réservation";
                }
            } else {
                $_SESSION['error'] = "Vous n'avez pas la permission de supprimer cette réservation";
            }
            header("Location: index.php?page=my-rides");
            exit();
        }
    }
}
