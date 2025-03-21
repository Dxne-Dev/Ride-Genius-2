<?php
class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    // Inscription
    public function register() {
        // Vérifier si l'utilisateur est déjà connecté
        if(isset($_SESSION['user_id'])) {
            header("Location: index.php");
            exit();
        }
        
        // Traitement du formulaire
        if($_SERVER["REQUEST_METHOD"] == "POST") {
            // Validation des données
            $errors = [];
            
            if(empty($_POST['first_name'])) {
                $errors[] = "Le prénom est requis";
            }
            
            if(empty($_POST['last_name'])) {
                $errors[] = "Le nom est requis";
            }
            
            if(empty($_POST['email'])) {
                $errors[] = "L'email est requis";
            } elseif(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Format d'email invalide";
            } else {
                // Vérifier si l'email existe déjà
                $this->user->email = $_POST['email'];
                if($this->user->emailExists()) {
                    $errors[] = "Cet email est déjà utilisé";
                }
            }
            
            if(empty($_POST['password'])) {
                $errors[] = "Le mot de passe est requis";
            } elseif(strlen($_POST['password']) < 6) {
                $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
            }
            
            if($_POST['password'] !== $_POST['confirm_password']) {
                $errors[] = "Les mots de passe ne correspondent pas";
            }
            
            if(empty($_POST['role'])) {
                $errors[] = "Le rôle est requis";
            }
            
            // Si pas d'erreurs, créer l'utilisateur
            if(empty($errors)) {
                $this->user->first_name = $_POST['first_name'];
                $this->user->last_name = $_POST['last_name'];
                $this->user->email = $_POST['email'];
                $this->user->password = $_POST['password'];
                $this->user->phone = $_POST['phone'] ?? null;
                $this->user->role = $_POST['role'];
                
                if($this->user->create()) {
                    $_SESSION['success'] = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                    header("Location: index.php?page=login");
                    exit();
                } else {
                    $errors[] = "Une erreur est survenue. Veuillez réessayer.";
                }
            }
        }
        
        // Afficher la vue
        include "views/auth/register.php";
    }
    
    // Connexion
    public function login() {
        // Vérifier si l'utilisateur est déjà connecté
        if(isset($_SESSION['user_id'])) {
            header("Location: index.php");
            exit();
        }
        
        // Traitement du formulaire
        if($_SERVER["REQUEST_METHOD"] == "POST") {
            $errors = [];
            
            if(empty($_POST['email']) || empty($_POST['password'])) {
                $errors[] = "Veuillez remplir tous les champs";
            } else {
                $email = $_POST['email'];
                $password = $_POST['password'];
                
                $user_data = $this->user->login($email, $password);
                
                if($user_data) {
                    $_SESSION['user_id'] = $user_data['id'];
                    $_SESSION['user_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
                    $_SESSION['user_role'] = $user_data['role'];
                    
                    $_SESSION['success'] = "Connexion réussie !";
                    
                    // Redirection selon le rôle
                    if($user_data['role'] === 'admin') {
                        header("Location: index.php?page=admin-dashboard");
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                } else {
                    $errors[] = "Email ou mot de passe incorrect";
                }
            }
        }
        
        // Afficher la vue
        include "views/auth/login.php";
    }
    
    // Déconnexion
    public function logout() {
        // Détruire la session
        session_destroy();
        
        // Rediriger vers la page de connexion
        header("Location: index.php?page=login");
        exit();
    }
}
