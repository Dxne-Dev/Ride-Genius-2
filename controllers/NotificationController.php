<?php
require_once 'services/NotificationService.php';

class NotificationController {
    private $db;
    private $notificationService;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->notificationService = new NotificationService($this->db);
    }

    // Protéger les routes
    private function authGuard() {
        if(!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page";
            header("Location: index.php?page=login");
            exit();
        }
    }
    
    // Afficher toutes les notifications
    public function index() {
        $this->authGuard();
        
        $notifications = $this->notificationService->getUnreadNotifications($_SESSION['user_id']);
        include "views/notifications/index.php";
    }
    
    // Marquer une notification comme lue
    public function markAsRead() {
        $this->authGuard();
        
        if(!isset($_GET['id'])) {
            header("Location: index.php?page=notifications");
            exit();
        }
        
        $notification_id = $_GET['id'];
        if($this->notificationService->markAsRead($notification_id)) {
            $_SESSION['success'] = "Notification marquée comme lue";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour de la notification";
        }
        
        // Redirection vers la page liée à la notification si spécifiée
        if(isset($_GET['redirect'])) {
            header("Location: " . $_GET['redirect']);
        } else {
            header("Location: index.php?page=notifications");
        }
        exit();
    }
    
    // Récupérer les notifications non lues (pour AJAX)
    public function getUnread() {
        $this->authGuard();
        
        $notifications = $this->notificationService->getUnreadNotifications($_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode([
            'count' => count($notifications),
            'notifications' => $notifications
        ]);
        exit;
    }
}
