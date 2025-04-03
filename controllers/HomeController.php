<?php
require_once 'models/Ride.php';
require_once 'models/User.php';

class HomeController {
    private $db;
    private $ride;
    private $user;

    public function __construct($db) {
        $this->db = $db;
        $this->ride = new Ride($db);
        $this->user = new User($db);
    }

    // Page d'accueil
    public function index() {
        // Récupérer les trajets récents
        $recent_rides = $this->ride->findRecent(6);
        
        // Récupérer les statistiques
        $stats = [
            'total_rides' => $this->ride->countAll(),
            'total_users' => $this->user->countAll(),
            'active_rides' => $this->ride->countByStatus('active')
        ];

        require_once 'views/home/index.php';
    }

    // Page À propos
    public function about() {
        require_once 'views/home/about.php';
    }

    // Page Contact
    public function contact() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Traitement du formulaire de contact
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $message = $_POST['message'] ?? '';

            if (empty($name) || empty($email) || empty($message)) {
                $error = "Veuillez remplir tous les champs.";
            } else {
                // Envoyer l'email (à implémenter)
                $success = "Votre message a été envoyé avec succès.";
            }
        }

        require_once 'views/home/contact.php';
    }

    // Page FAQ
    public function faq() {
        require_once 'views/home/faq.php';
    }

    // Page Conditions d'utilisation
    public function terms() {
        require_once 'views/home/terms.php';
    }

    // Page Politique de confidentialité
    public function privacy() {
        require_once 'views/home/privacy.php';
    }
} 