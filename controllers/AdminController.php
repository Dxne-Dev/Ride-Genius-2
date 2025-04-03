<?php
require_once 'models/User.php';
require_once 'models/Ride.php';
require_once 'models/Booking.php';

class AdminController {
    private $db;
    private $user;
    private $ride;
    private $booking;

    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
        $this->ride = new Ride($db);
        $this->booking = new Booking($db);
    }

    // Vérifie si l'utilisateur est un administrateur
    private function isAdmin() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        $user = $this->user->findById($_SESSION['user_id']);
        return $user && $user['role'] === 'admin';
    }

    // Tableau de bord administrateur
    public function index() {
        if (!$this->isAdmin()) {
            header('Location: index.php?page=login');
            exit;
        }

        // Récupérer les statistiques
        $stats = [
            'total_users' => $this->user->countAll(),
            'total_rides' => $this->ride->countAll(),
            'total_bookings' => $this->booking->countAll(),
            'pending_rides' => $this->ride->countByStatus('pending'),
            'active_rides' => $this->ride->countByStatus('active'),
            'completed_rides' => $this->ride->countByStatus('completed')
        ];

        require_once 'views/admin/dashboard.php';
    }

    // Gestion des utilisateurs
    public function users() {
        if (!$this->isAdmin()) {
            header('Location: index.php?page=login');
            exit;
        }

        $users = $this->user->findAll();
        require_once 'views/admin/users.php';
    }

    // Gestion des trajets
    public function rides() {
        if (!$this->isAdmin()) {
            header('Location: index.php?page=login');
            exit;
        }

        $rides = $this->ride->findAll();
        require_once 'views/admin/rides.php';
    }

    // Mise à jour du statut d'un utilisateur
    public function updateUserStatus() {
        if (!$this->isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['user_id']) || !isset($data['status'])) {
            echo json_encode(['success' => false, 'message' => 'Données manquantes']);
            exit;
        }

        $result = $this->user->updateStatus($data['user_id'], $data['status']);
        echo json_encode(['success' => $result]);
    }

    // Mise à jour du statut d'un trajet
    public function updateRideStatus() {
        if (!$this->isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['ride_id']) || !isset($data['status'])) {
            echo json_encode(['success' => false, 'message' => 'Données manquantes']);
            exit;
        }

        $result = $this->ride->updateStatus($data['ride_id'], $data['status']);
        echo json_encode(['success' => $result]);
    }

    // Suppression d'un utilisateur
    public function deleteUser() {
        if (!$this->isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
            exit;
        }

        $result = $this->user->delete($data['user_id']);
        echo json_encode(['success' => $result]);
    }

    // Suppression d'un trajet
    public function deleteRide() {
        if (!$this->isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['ride_id'])) {
            echo json_encode(['success' => false, 'message' => 'ID trajet manquant']);
            exit;
        }

        $result = $this->ride->delete($data['ride_id']);
        echo json_encode(['success' => $result]);
    }
} 