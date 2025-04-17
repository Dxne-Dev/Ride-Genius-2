<?php
class UserController {
    private $db;
    private $user;
    private $review;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
        $this->review = new Review($this->db);
    }

    // Protéger les routes
    private function authGuard() {
        if(!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page";
            header("Location: index.php?page=login");
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
    
    // Profil de l'utilisateur
    public function profile() {
        $this->authGuard();
        
        $this->user->id = $_SESSION['user_id'];
        $this->user->readOne();
          // Rendre l'objet user accessible à la vue(toujours rendre l'objet accessible à la vue lorsqu'on rencontre les erreurs du genre variable non definie)
            $user = $this->user;
        
      // Obtenir les avis reçus
    $this->review->recipient_id = $_SESSION['user_id'];
    $reviews = $this->review->readUserReviews();
    
    // Obtenir la note moyenne
    $rating_data = $this->review->getUserRating();
    
    // Afficher la vue
    include "views/users/profil.php";
    }
    
    // Éditer le profil
    public function editProfile() {
        $this->authGuard();
        
        $this->user->id = $_SESSION['user_id'];
        $this->user->readOne();
        $user = $this->user;
        
        // Traitement du formulaire
        if($_SERVER["REQUEST_METHOD"] == "POST") {
            $errors = [];
            
            if(empty($_POST['first_name'])) {
                $errors[] = "Le prénom est requis";
            }
            
            if(empty($_POST['last_name'])) {
                $errors[] = "Le nom est requis";
            }
            
            // Si pas d'erreurs, mettre à jour l'utilisateur
            if(empty($errors)) {
                $this->user->first_name = $_POST['first_name'];
                $this->user->last_name = $_POST['last_name'];
                $this->user->phone = $_POST['phone'] ?? null;
                $this->user->role = $_SESSION['user_role']; // Ne pas changer le rôle
                
                if($this->user->update()) {
                    $_SESSION['user_name'] = $this->user->first_name . ' ' . $this->user->last_name;
                    $_SESSION['success'] = "Profil mis à jour avec succès";
                    header("Location: index.php?page=profile");
                    exit();
                } else {
                    $errors[] = "Une erreur est survenue. Veuillez réessayer.";
                }
            }
        }
        
        // Afficher la vue
        include "views/users/edit_profile.php";
    }
    
    // Changer le mot de passe
    public function changePassword() {
        $this->authGuard();

        // Charger les données de l'utilisateur AVANT la vérification
    $this->user->id = $_SESSION['user_id'];
    $this->user->readOne(); // <-- Ajout crucial
        
        // Traitement du formulaire
        if($_SERVER["REQUEST_METHOD"] == "POST") {
            $errors = [];
            
            if(empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_password'])) {
                $errors[] = "Tous les champs sont requis";
            }
            
            if($_POST['new_password'] !== $_POST['confirm_password']) {
                $errors[] = "Les nouveaux mots de passe ne correspondent pas";
            }
            
            if(strlen($_POST['new_password']) < 6) {
                $errors[] = "Le nouveau mot de passe doit contenir au moins 6 caractères";
            }
            
            // Vérifier l'ancien mot de passe
            $user_data = $this->user->login($this->user->email, $_POST['current_password']); // <-- Correction ici
        
        if(!$user_data) {
            $errors[] = "Mot de passe actuel incorrect";
        }
            
            // Si pas d'erreurs, mettre à jour le mot de passe
            if(empty($errors)) {
                $this->user->id = $_SESSION['user_id'];
                $this->user->password = $_POST['new_password'];
                
                if($this->user->updatePassword()) {
                    $_SESSION['success'] = "Mot de passe mis à jour avec succès";
                    header("Location: index.php?page=profile");
                    exit();
                } else {
                    $errors[] = "Une erreur est survenue. Veuillez réessayer.";
                }
            }
        }
        
        // Afficher la vue
        include "views/users/change_password.php";
    }
    
    // Dashboard admin
    public function adminDashboard() {
        $this->adminGuard();
        
        // Statistiques de base
        $user_count_query = "SELECT COUNT(*) as total FROM users";
        $ride_count_query = "SELECT COUNT(*) as total FROM rides";
        $booking_count_query = "SELECT COUNT(*) as total FROM bookings";
        
        $user_stmt = $this->db->prepare($user_count_query);
        $user_stmt->execute();
        $user_count = $user_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $ride_stmt = $this->db->prepare($ride_count_query);
        $ride_stmt->execute();
        $ride_count = $ride_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $booking_stmt = $this->db->prepare($booking_count_query);
        $booking_stmt->execute();
        $booking_count = $booking_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Derniers utilisateurs inscrits
        $recent_users_query = "SELECT * FROM users ORDER BY created_at DESC LIMIT 5";
        $recent_users_stmt = $this->db->prepare($recent_users_query);
        $recent_users_stmt->execute();
        
        // Derniers trajets créés
        $recent_rides_query = "SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as driver_name 
                              FROM rides r
                              LEFT JOIN users u ON r.driver_id = u.id
                              ORDER BY r.created_at DESC LIMIT 5";
        $recent_rides_stmt = $this->db->prepare($recent_rides_query);
        $recent_rides_stmt->execute();
        
        // Afficher la vue
        include "views/admin/dashboard.php";
    }
    
    // Liste des utilisateurs (admin)
    public function adminUsers() {
        $this->adminGuard();
        
        // Lire tous les utilisateurs
        $stmt = $this->user->read();
        
        // Traitement des actions
        if(isset($_GET['action']) && isset($_GET['id'])) {
            $id = $_GET['id'];
            $action = $_GET['action'];
            
            // Supprimer un utilisateur
            if($action === 'delete' && $id != $_SESSION['user_id']) {
                $this->user->id = $id;
                if($this->user->delete()) {
                    $_SESSION['success'] = "Utilisateur supprimé avec succès";
                } else {
                    $_SESSION['error'] = "Erreur lors de la suppression de l'utilisateur";
                }
                header("Location: index.php?page=admin-users");
                exit();
            }
        }
        
        // Afficher la vue
        include "views/admin/users.php";
    }

    // Profil d'un utilisateur spécifique (pour l'admin)
    public function userProfile() {
        $this->adminGuard();
        
        if(!isset($_GET['id'])) {
            $_SESSION['error'] = "ID utilisateur non spécifié";
            header("Location: index.php?page=admin-users");
            exit();
        }
        
        $this->user->id = $_GET['id'];
        if(!$this->user->readOne()) {
            $_SESSION['error'] = "Utilisateur non trouvé";
            header("Location: index.php?page=admin-users");
            exit();
        }
        
        // Rendre l'objet user accessible à la vue
        $user = $this->user;
        
        // Obtenir les avis reçus
        $this->review->recipient_id = $this->user->id;
        $reviews = $this->review->readUserReviews();
        
        // Obtenir la note moyenne
        $rating_data = $this->review->getUserRating();
        
        // Afficher la vue
        include "views/users/profil.php";
    }
}
