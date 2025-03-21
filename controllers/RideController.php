<?php
class RideController {
    private $db;
    private $ride;
    private $booking;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->ride = new Ride($this->db);
        $this->booking = new Booking($this->db);
    }

    // Protéger les routes
    private function authGuard() {
        if(!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page";
            header("Location: index.php?page=login");
            exit();
        }
    }
    
    // Protéger les routes conducteur
    private function driverGuard() {
        $this->authGuard();
        
        if($_SESSION['user_role'] !== 'conducteur' && $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = "Vous devez être conducteur pour accéder à cette page";
            header("Location: index.php");
            exit();
        }
    }
    
    // Protéger les routes admin
    private function adminGuard() {
        $this->authGuard();
        
        if($_SESSION['user_role'] !== 'admin') {
            $_SESSION['error'] = "Vous n'avez pas les droits pour accéder à cette page";
            header("Location: index.php");
            exit();
        }
    }
    
    // Liste des trajets
    public function index() {
        // Lire tous les trajets
        $stmt = $this->ride->read();
        
        // Afficher la vue
        include "views/rides/index.php";
    }
    
    // Détails d'un trajet
    public function show() {
        if(!isset($_GET['id'])) {
            header("Location: index.php?page=rides");
            exit();
        }
        
        $this->ride->id = $_GET['id'];
        
        // Obtenir les détails du trajet
        if(!$this->ride->readOne()) {
            $_SESSION['error'] = "Trajet introuvable";
            header("Location: index.php?page=rides");
            exit();
        }
        
        // Vérifier si l'utilisateur a déjà réservé ce trajet
        $has_booked = false;
        if(isset($_SESSION['user_id'])) {
            $this->booking->ride_id = $this->ride->id;
            $this->booking->passenger_id = $_SESSION['user_id'];
            $has_booked = $this->booking->checkExistingBooking();
        }
        
        // Définir la variable $ride pour la vue
        $ride = $this->ride;
        
        // Afficher la vue
        include "views/rides/show.php";
    }
    
    
    // Créer un trajet
    public function create() {
        $this->driverGuard();
        
        // Traitement du formulaire
        if($_SERVER["REQUEST_METHOD"] == "POST") {
            $errors = [];
            
            // Validation des données
            if(empty($_POST['departure'])) {
                $errors[] = "Le lieu de départ est requis";
            }
            
            if(empty($_POST['destination'])) {
                $errors[] = "La destination est requise";
            }
            
            if(empty($_POST['departure_time'])) {
                $errors[] = "La date et l'heure de départ sont requises";
            } else {
                // Vérifier que la date est dans le futur
                $departure_time = new DateTime($_POST['departure_time']);
                $now = new DateTime();
                
                if($departure_time < $now) {
                    $errors[] = "La date de départ doit être dans le futur";
                }
            }
            
            if(empty($_POST['available_seats']) || !is_numeric($_POST['available_seats']) || $_POST['available_seats'] <= 0) {
                $errors[] = "Le nombre de places doit être un nombre positif";
            }
            
            if(empty($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] <= 0) {
                $errors[] = "Le prix doit être un nombre positif";
            }
            
            // Si pas d'erreurs, créer le trajet
            if(empty($errors)) {
                $this->ride->driver_id = $_SESSION['user_id'];
                $this->ride->departure = $_POST['departure'];
                $this->ride->destination = $_POST['destination'];
                $this->ride->departure_time = $_POST['departure_time'];
                $this->ride->available_seats = $_POST['available_seats'];
                $this->ride->price = $_POST['price'];
                $this->ride->description = $_POST['description'] ?? null;
                $this->ride->status = 'active';
                
                if($this->ride->create()) {
                    $_SESSION['success'] = "Trajet créé avec succès";
                    header("Location: index.php?page=my-rides");
                    exit();
                } else {
                    $errors[] = "Une erreur est survenue. Veuillez réessayer.";
                }
            }
        }
        
        // Afficher la vue
        include "views/rides/create.php";
    }
    
    // Modifier un trajet
    public function edit() {
        $this->driverGuard();
        
        if(!isset($_GET['id'])) {
            header("Location: index.php?page=my-rides");
            exit();
        }
        
        $this->ride->id = $_GET['id'];
        $this->ride->driver_id = $_SESSION['user_id'];
        
        // Obtenir les détails du trajet
        if(!$this->ride->readOne()) {
            $_SESSION['error'] = "Trajet introuvable ou vous n'êtes pas le conducteur de ce trajet";
            header("Location: index.php?page=my-rides");
            exit();
        }
        
        // Traitement du formulaire
        if($_SERVER["REQUEST_METHOD"] == "POST") {
            $errors = [];
            
            // Validation des données
            if(empty($_POST['departure'])) {
                $errors[] = "Le lieu de départ est requis";
            }
            
            if(empty($_POST['destination'])) {
                $errors[] = "La destination est requise";
            }
            
            if(empty($_POST['departure_time'])) {
                $errors[] = "La date et l'heure de départ sont requises";
            }
            
            if(empty($_POST['available_seats']) || !is_numeric($_POST['available_seats']) || $_POST['available_seats'] <= 0) {
                $errors[] = "Le nombre de places doit être un nombre positif";
            }
            
            if(empty($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] <= 0) {
                $errors[] = "Le prix doit être un nombre positif";
            }
            
            // Si pas d'erreurs, mettre à jour le trajet
            if(empty($errors)) {
                $this->ride->departure = $_POST['departure'];
                $this->ride->destination = $_POST['destination'];
                $this->ride->departure_time = $_POST['departure_time'];
                $this->ride->available_seats = $_POST['available_seats'];
                $this->ride->price = $_POST['price'];
                $this->ride->description = $_POST['description'] ?? null;
                $this->ride->status = $_POST['status'];
                
                if($this->ride->update()) {
                    $_SESSION['success'] = "Trajet mis à jour avec succès";
                    header("Location: index.php?page=my-rides");
                    exit();
                } else {
                    $errors[] = "Une erreur est survenue. Veuillez réessayer.";
                }
            }
        }

        $ride = $this->ride;
        
        // Afficher la vue
        include "views/rides/edit.php";
    }
    
    // Supprimer un trajet
    public function delete() {
        $this->driverGuard();
        
        if(!isset($_GET['id'])) {
            header("Location: index.php?page=my-rides");
            exit();
        }
        
        $this->ride->id = $_GET['id'];
        $this->ride->driver_id = $_SESSION['user_id'];
        
        if($this->ride->delete()) {
            $_SESSION['success'] = "Trajet supprimé avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression du trajet";
        }
        
        header("Location: index.php?page=my-rides");
        exit();
    }
    
    // Mes trajets (conducteur)
    public function myRides() {
        $this->driverGuard();
        
        $this->ride->driver_id = $_SESSION['user_id'];
        $stmt = $this->ride->getDriverRides();
        
        // Afficher la vue
        include "views/rides/my_rides.php";
    }
    
    // Rechercher des trajets
    public function search() {
        $results = [];
        $searched = false;
        
        if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['departure']) && isset($_GET['destination']) && isset($_GET['date'])) {
            $departure = $_GET['departure'];
            $destination = $_GET['destination'];
            $date = $_GET['date'];
            
            if(!empty($departure) && !empty($destination) && !empty($date)) {
                $searched = true;
                $stmt = $this->ride->search($departure, $destination, $date);
                $results = $stmt;
            }
        }
        
        // Afficher la vue
        include "views/rides/search.php";
    }
    
    // Liste des trajets (admin)
    public function adminRides() {
        $this->adminGuard();
        
        // Lire tous les trajets
        $stmt = $this->ride->read();
        
        // Traitement des actions
        if(isset($_GET['action']) && isset($_GET['id'])) {
            $id = $_GET['id'];
            $action = $_GET['action'];
            
            // Supprimer un trajet
            if($action === 'delete') {
                $this->ride->id = $id;
                // L'admin peut supprimer n'importe quel trajet sans vérifier le driver_id
                $query = "DELETE FROM rides WHERE id = ?";
                $delete_stmt = $this->db->prepare($query);
                $delete_stmt->bindParam(1, $id);
                
                if($delete_stmt->execute()) {
                    $_SESSION['success'] = "Trajet supprimé avec succès";
                } else {
                    $_SESSION['error'] = "Erreur lors de la suppression du trajet";
                }
                header("Location: index.php?page=admin-rides");
                exit();
            }
        }
        
        // Afficher la vue
        include "views/admin/rides.php";
    }
}
