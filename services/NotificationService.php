<?php
class NotificationService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Envoie une notification au passager lorsque sa réservation est marquée comme terminée
     * 
     * @param int $bookingId ID de la réservation
     * @return bool Succès de l'opération
     */
    public function sendCompletionNotification($bookingId) {
        // Récupérer les informations de la réservation
        $query = "SELECT b.*, 
                  CONCAT(p.first_name, ' ', p.last_name) as passenger_name,
                  p.email as passenger_email,
                  CONCAT(d.first_name, ' ', d.last_name) as driver_name,
                  r.departure, r.destination, r.departure_time
                  FROM bookings b
                  JOIN users p ON b.passenger_id = p.id
                  JOIN rides r ON b.ride_id = r.id
                  JOIN users d ON r.driver_id = d.id
                  WHERE b.id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            return false;
        }
        
        // Enregistrer la notification dans la base de données
        $insertQuery = "INSERT INTO notifications (user_id, type, message, related_id, is_read, created_at)
                        VALUES (:user_id, :type, :message, :related_id, 0, NOW())";
        
        $stmt = $this->db->prepare($insertQuery);
        $message = "Votre trajet de {$booking['departure']} à {$booking['destination']} avec {$booking['driver_name']} est terminé. Vous pouvez maintenant laisser un avis ! Vous avez 7 jours pour évaluer ce trajet.";
        
        $params = [
            ':user_id' => $booking['passenger_id'],
            ':type' => 'booking_completed',
            ':message' => $message,
            ':related_id' => $bookingId
        ];
        
        $success = $stmt->execute($params);
        
        // Envoyer un email (si implémenté)
        // $this->sendEmail($booking['passenger_email'], "Évaluez votre trajet", $message);
        
        return $success;
    }
    
    /**
     * Récupère les notifications non lues d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Notifications non lues
     */
    public function getUnreadNotifications($userId) {
        $query = "SELECT * FROM notifications 
                  WHERE user_id = ? AND is_read = 0 
                  ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Marque une notification comme lue
     * 
     * @param int $notificationId ID de la notification
     * @return bool Succès de l'opération
     */
    public function markAsRead($notificationId) {
        $query = "UPDATE notifications SET is_read = 1 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$notificationId]);
    }
}
