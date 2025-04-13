<?php
class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    // Fonction utilitaire pour détecter si c'est une requête AJAX
    private function isAjaxRequest() {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    // Fonction utilitaire pour envoyer une réponse JSON
    private function sendJsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    // Envoyer l'email de vérification
    private function sendVerificationEmail($email, $code) {
        require_once 'vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Configuration du serveur
            $mail->SMTPDebug = 2; // Activer le debug pour le test
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ridegenius244@gmail.com';
            $mail->Password = 'jkjp eloo wvox lqwr';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Expéditeur et destinataire
            $mail->setFrom('ridegenius244@gmail.com', 'Ride Genius');
            $mail->addAddress($email);
            $mail->addReplyTo('ridegenius244@gmail.com', 'Ride Genius');

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = 'Vérification de votre compte Ride Genius';
            
            $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?page=verify-email&code=" . $code;
            
            $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
                    .footer { margin-top: 20px; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>Bienvenue sur Ride Genius !</h2>
                    <p>Merci de vous être inscrit. Pour activer votre compte, veuillez cliquer sur le bouton ci-dessous :</p>
                    <p style='text-align: center;'>
                        <a href='{$verification_link}' class='button'>Vérifier mon email</a>
                    </p>
                    <p>Ou copiez ce lien dans votre navigateur :</p>
                    <p>{$verification_link}</p>
                    <p>Ce lien expirera dans 24 heures.</p>
                    <p>Si vous n'avez pas créé de compte, vous pouvez ignorer cet email.</p>
                    <div class='footer'>
                        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                    </div>
                </div>
            </body>
            </html>";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email: {$mail->ErrorInfo}");
            return false;
        }
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
                    // Envoyer l'email de vérification
                    if($this->sendVerificationEmail($this->user->email, $this->user->email_verification_code)) {
                        $_SESSION['success'] = "Inscription réussie ! Veuillez vérifier votre email pour activer votre compte.";
                        // Utiliser JavaScript pour la redirection
                        echo "<script>window.location.href = 'index.php?page=login';</script>";
                        exit();
                    } else {
                        $errors[] = "Une erreur est survenue lors de l'envoi de l'email de vérification.";
                    }
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse([
                        'success' => false,
                        'message' => "Veuillez remplir tous les champs"
                    ], 400);
                }
                $_SESSION['error'] = "Veuillez remplir tous les champs";
                include "views/auth/login.php";
                return;
            }

            $user_data = $this->user->login($email, $password);
            
            if ($user_data) {
                try {
                    require_once __DIR__ . '/../auth.php';
                    $token = generate_token($user_data['id']);
                    
                    $_SESSION['user_id'] = $user_data['id'];
                    $_SESSION['api_token'] = $token;

                    if ($this->isAjaxRequest()) {
                        $this->sendJsonResponse([
                            'success' => true,
                            'message' => "Connexion réussie",
                            'token' => $token,
                            'user_id' => $user_data['id']
                        ]);
                    }
                    
                    $_SESSION['success'] = "Connexion réussie";
                    header("Location: index.php");
                    exit;
                } catch (Exception $e) {
                    error_log("Erreur génération token: " . $e->getMessage());
                    if ($this->isAjaxRequest()) {
                        $this->sendJsonResponse([
                            'success' => false,
                            'message' => "Erreur lors de la connexion. Veuillez réessayer."
                        ], 500);
                    }
                    $_SESSION['error'] = "Erreur lors de la connexion. Veuillez réessayer.";
                }
            } else {
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse([
                        'success' => false,
                        'message' => "Email ou mot de passe incorrect"
                    ], 401);
                }
                $_SESSION['error'] = "Email ou mot de passe incorrect";
            }
        }
        
        if ($this->isAjaxRequest()) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => "Méthode non autorisée"
            ], 405);
        }
        
        include "views/auth/login.php";
    }

    // Vérification de l'email
    public function verifyEmail() {
        if(isset($_GET['code'])) {
            $code = $_GET['code'];
            error_log("Tentative de vérification de l'email avec le code: " . $code);
            
            if($user_data = $this->user->verifyEmailCode($code)) {
                error_log("Code de vérification valide trouvé pour l'utilisateur ID: " . $user_data['id']);
                if($this->user->markAsVerified($user_data['id'])) {
                    error_log("Email marqué comme vérifié pour l'utilisateur ID: " . $user_data['id']);
                    $_SESSION['success'] = "Votre email a été vérifié avec succès ! Vous pouvez maintenant vous connecter.";
                    // Utiliser JavaScript pour la redirection
                    echo "<script>window.location.href = 'index.php?page=login';</script>";
                    exit();
                } else {
                    error_log("Erreur lors de la vérification de l'email pour l'utilisateur ID: " . $user_data['id']);
                    $_SESSION['error'] = "Une erreur est survenue lors de la vérification de votre email.";
                }
            } else {
                error_log("Code de vérification invalide ou expiré: " . $code);
                $_SESSION['error'] = "Le lien de vérification est invalide ou a expiré.";
            }
        }
        
        include "views/auth/verify_email.php";
    }

    // Renvoyer le lien de vérification
    public function resendVerification() {
        if($_SERVER["REQUEST_METHOD"] == "POST") {
            $errors = [];
            
            if(empty($_POST['email'])) {
                $errors[] = "L'email est requis";
            } elseif(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Format d'email invalide";
            } else {
                $this->user->email = $_POST['email'];
                
                if($this->user->resendVerificationCode()) {
                    if($this->sendVerificationEmail($this->user->email, $this->user->email_verification_code)) {
                        $_SESSION['success'] = "Un nouveau lien de vérification a été envoyé à votre adresse email.";
                    } else {
                        $errors[] = "Une erreur est survenue lors de l'envoi de l'email.";
                    }
                } else {
                    $errors[] = "Une erreur est survenue. Veuillez réessayer.";
                }
            }
        }
        
        include "views/auth/resend-verification.php";
    }
    
    // Déconnexion
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            try {
                $stmt = $this->db->prepare("DELETE FROM api_tokens WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse([
                        'success' => true,
                        'message' => "Déconnexion réussie"
                    ]);
                }
            } catch (PDOException $e) {
                error_log("Erreur suppression token: " . $e->getMessage());
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse([
                        'success' => false,
                        'message' => "Erreur lors de la déconnexion"
                    ], 500);
                }
            }
        }

        session_destroy();
        
        if (!$this->isAjaxRequest()) {
            header("Location: index.php?page=login");
            exit;
        }
    }
}
