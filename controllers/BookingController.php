<?php
require_once 'models/Booking.php';
require_once 'models/Commission.php';
require_once 'models/BookingTransaction.php';
require_once 'models/Wallet.php';
require_once 'models/Subscription.php';

class BookingController {

    private $db;
    private $booking;
    private $ride;
    private $bookingModel;
    private $commissionModel;
    private $transactionModel;
    private $walletModel;
    private $subscriptionModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->booking = new Booking($this->db);
        $this->ride = new Ride($this->db);
        $this->bookingModel = new Booking($this->db);
        $this->commissionModel = new Commission($this->db);
        $this->transactionModel = new BookingTransaction($this->db);
        $this->walletModel = new Wallet($this->db);
        $this->subscriptionModel = new Subscription($this->db);
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

        // Vérifier le solde minimum du wallet
        $balance = $this->walletModel->getBalance($_SESSION['user_id']);
        if ($balance < 200) {
            $_SESSION['error'] = "Vous devez avoir un minimum de 200€ dans votre wallet pour effectuer une réservation";
            header("Location: index.php?page=wallet");
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

            // Vérifier si le solde est suffisant pour le prix total
            $totalPrice = $this->ride->price * $_POST['seats'];
            if ($balance < $totalPrice) {
                $errors[] = "Solde insuffisant. Il vous manque " . ($totalPrice - $balance) . "€ pour effectuer cette réservation. Veuillez recharger votre wallet.";
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
        
        // Convertir l'objet Ride en tableau pour la vue
        $ride_details = (array)$this->ride;
        
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

    public function createBooking($data) {
        try {
            $this->db->beginTransaction();

            // Vérifier le type d'abonnement du conducteur
            $driverSubscription = $this->getDriverSubscriptionType($data['driver_id']);
            
            // Calculer le prix total selon l'abonnement
            if ($driverSubscription === 'eco') {
                // Pour les conducteurs gratuits
                $commission = $this->commissionModel->calculateCommission($data['amount'], 'eco');
                $totalPrice = $data['amount']; // Prix brut
            } elseif ($driverSubscription === 'pro') {
                // Pour les conducteurs ProTrajet
                $commission = $this->commissionModel->calculateCommission($data['amount'], 'pro');
                $totalPrice = $data['amount'] + $commission['amount']; // Prix + commission 2%
            } else {
                // Pour les conducteurs BusinessTrajet
                $totalPrice = $data['amount']; // Prix sans commission
                $commission = ['amount' => 0, 'rate' => 0];
            }

            // Vérifier le solde du passager
            $passengerBalance = $this->walletModel->getBalance($data['passenger_id']);
            if ($passengerBalance < $totalPrice) {
                throw new Exception("Solde insuffisant pour effectuer la réservation");
            }

            // Créer la réservation avec le statut 'accepted' directement
            $data['status'] = 'accepted';
            $bookingId = $this->bookingModel->create($data);

            // Gérer le paiement selon le type d'abonnement
            if ($driverSubscription === 'eco') {
                // Pour les conducteurs gratuits
                // Déduire le montant du passager
                if (!$this->walletModel->withdrawFunds($data['passenger_id'], $totalPrice, "Paiement trajet #$bookingId")) {
                    throw new Exception("Erreur lors du débit du passager");
                }
                // Créditer le conducteur (moins la commission)
                if (!$this->walletModel->addFunds($data['driver_id'], $totalPrice - $commission['amount'], "Revenu trajet #$bookingId")) {
                    throw new Exception("Erreur lors du crédit du conducteur");
                }
                // Créditer l'admin (commission)
                if (!$this->walletModel->addFunds('admin', $commission['amount'], "Commission trajet #$bookingId")) {
                    throw new Exception("Erreur lors du crédit de la commission");
                }
            } elseif ($driverSubscription === 'pro') {
                // Pour les conducteurs ProTrajet
                // Déduire le montant total du passager
                if (!$this->walletModel->withdrawFunds($data['passenger_id'], $totalPrice, "Paiement trajet #$bookingId")) {
                    throw new Exception("Erreur lors du débit du passager");
                }
                // Créditer le conducteur (prix du trajet)
                if (!$this->walletModel->addFunds($data['driver_id'], $data['amount'], "Revenu trajet #$bookingId")) {
                    throw new Exception("Erreur lors du crédit du conducteur");
                }
                // Créditer l'admin (commission)
                if (!$this->walletModel->addFunds('admin', $commission['amount'], "Commission trajet #$bookingId")) {
                    throw new Exception("Erreur lors du crédit de la commission");
                }
            } else {
                // Pour les conducteurs BusinessTrajet
                // Déduire le montant du passager
                if (!$this->walletModel->withdrawFunds($data['passenger_id'], $totalPrice, "Paiement trajet #$bookingId")) {
                    throw new Exception("Erreur lors du débit du passager");
                }
                // Créditer le conducteur (montant total)
                if (!$this->walletModel->addFunds($data['driver_id'], $totalPrice, "Revenu trajet #$bookingId")) {
                    throw new Exception("Erreur lors du crédit du conducteur");
                }
            }

            // Créer l'enregistrement de commission
            $this->commissionModel->createCommission($bookingId, $commission['amount'], $commission['rate']);

            // Créer la transaction avec le statut 'completed' directement
            $transactionData = [
                'booking_id' => $bookingId,
                'amount' => $totalPrice,
                'status' => 'completed',
                'type' => 'booking'
            ];
            $this->transactionModel->create($transactionData);

            $this->db->commit();
            return [
                'success' => true,
                'booking_id' => $bookingId,
                'total_price' => $totalPrice,
                'commission' => $commission
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function getDriverSubscriptionType($driverId) {
        $subscription = $this->subscriptionModel->getActiveSubscription($driverId);
        return $subscription ? $subscription['plan_type'] : 'eco';
    }

    public function cancelBooking($bookingId, $userId) {
        try {
            $this->db->beginTransaction();

            $booking = $this->bookingModel->getById($bookingId);
            if (!$booking) {
                throw new Exception("Réservation non trouvée");
            }

            if ($booking['passenger_id'] != $userId && $booking['driver_id'] != $userId) {
                throw new Exception("Non autorisé à annuler cette réservation");
            }

            // Mettre à jour le statut de la réservation
            if (!$this->bookingModel->updateStatus($bookingId, 'cancelled')) {
                throw new Exception("Erreur lors de la mise à jour du statut de la réservation");
            }

            // Mettre à jour le statut de la transaction
            if (!$this->transactionModel->updateStatus($bookingId, 'cancelled')) {
                throw new Exception("Erreur lors de la mise à jour du statut de la transaction");
            }

            // Rembourser le passager
            if (!$this->walletModel->addFunds($booking['passenger_id'], $booking['amount'], "Remboursement réservation #$bookingId")) {
                throw new Exception("Erreur lors du remboursement du passager");
            }

            // Si le conducteur est en abonnement gratuit, rembourser la commission
            $driverSubscription = $this->getDriverSubscriptionType($booking['driver_id']);
            if ($driverSubscription === 'eco') {
                $commission = $this->commissionModel->getCommissionByBooking($bookingId);
                if (!$this->walletModel->addFunds($booking['driver_id'], $commission['amount'], "Remboursement commission #$bookingId")) {
                    throw new Exception("Erreur lors du remboursement de la commission");
                }
            }

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function completeBooking($bookingId) {
        try {
            $this->db->beginTransaction();

            $booking = $this->bookingModel->getById($bookingId);
            if (!$booking) {
                throw new Exception("Réservation non trouvée");
            }

            // Mettre à jour le statut de la réservation
            if (!$this->bookingModel->updateStatus($bookingId, 'completed')) {
                throw new Exception("Erreur lors de la mise à jour du statut de la réservation");
            }

            // Mettre à jour le statut de la transaction
            if (!$this->transactionModel->updateStatus($bookingId, 'completed')) {
                throw new Exception("Erreur lors de la mise à jour du statut de la transaction");
            }

            // Transférer le montant au conducteur (moins la commission pour les abonnés gratuits)
            $driverSubscription = $this->getDriverSubscriptionType($booking['driver_id']);
            if ($driverSubscription === 'eco') {
                $commission = $this->commissionModel->getCommissionByBooking($bookingId);
                $amountToTransfer = $booking['amount'] - $commission['amount'];
            } else {
                $amountToTransfer = $booking['amount'];
            }

            if (!$this->walletModel->addFunds($booking['driver_id'], $amountToTransfer, "Paiement trajet #$bookingId")) {
                throw new Exception("Erreur lors du transfert des fonds au conducteur");
            }

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
