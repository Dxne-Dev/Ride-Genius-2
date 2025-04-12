<?php
require_once 'models/Message.php';
require_once 'models/Conversation.php';
require_once 'models/User.php';

class MessageController {
    private $db;
    private $messageModel;
    private $conversationModel;
    private $user;

    public function __construct($db) {
        $this->db = $db;
        $this->messageModel = new Message($db);
        $this->conversationModel = new Conversation($db);
        $this->user = new User($db);
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }
        $currentUser = $this->user->findById($_SESSION['user_id']);
        if (!$currentUser) {
            session_destroy();
            header('Location: index.php?page=login');
            exit;
        }
        require_once 'views/messages/chat.php';
    }

    public function getConversations($user_id) {
        try {
            $sql = "
                SELECT 
                    c.id, 
                    c.user1_id, 
                    c.user2_id, 
                    CASE 
                        WHEN c.user1_id = ? THEN u2.first_name 
                        ELSE u1.first_name 
                    END AS other_first_name,
                    CASE 
                        WHEN c.user1_id = ? THEN u2.last_name 
                        ELSE u1.last_name 
                    END AS other_last_name,
                    'assets/images/default-avatar.png' AS profile_image,
                    m.content AS last_message, 
                    m.created_at AS last_message_at,
                    (SELECT COUNT(*) 
                     FROM messages m2 
                     WHERE m2.conversation_id = c.id 
                     AND m2.sender_id != ? 
                     AND m2.is_read = 0) AS unread_count
                FROM conversations c
                JOIN users u1 ON u1.id = c.user1_id
                JOIN users u2 ON u2.id = c.user2_id
                LEFT JOIN messages m ON m.id = (
                    SELECT MAX(id) 
                    FROM messages m2 
                    WHERE m2.conversation_id = c.id
                )
                WHERE c.user1_id = ? OR c.user2_id = ?
                ORDER BY m.created_at DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'conversations' => $conversations];
        } catch (PDOException $e) {
            error_log("Erreur getConversations: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération des conversations'];
        }
    }

    public function getMessages($conversation_id, $page, $limit) {
        try {
            error_log("Début getMessages - conversation_id: $conversation_id");
            
            if (!isset($_SESSION['user_id'])) {
                error_log("Erreur: Utilisateur non connecté");
                return ['success' => false, 'message' => 'Utilisateur non connecté'];
            }
            $user_id = $_SESSION['user_id'];
            error_log("User ID: $user_id");

            $sql_check = "SELECT id FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)";
            $stmt = $this->db->prepare($sql_check);
            $stmt->execute([$conversation_id, $user_id, $user_id]);
            if (!$stmt->fetch()) {
                error_log("Erreur: Conversation introuvable ou accès refusé");
                return ['success' => false, 'message' => 'Conversation introuvable ou accès refusé'];
            }

            // Convertir les paramètres en entiers
            $page = (int)$page;
            $limit = (int)$limit;
            $offset = ($page - 1) * $limit;
            
            $sql = "
                SELECT m.id, m.sender_id, m.content, m.created_at, m.is_read,
                       (SELECT GROUP_CONCAT(mr.reaction) FROM message_reactions mr WHERE mr.message_id = m.id) AS reactions
                FROM messages m
                WHERE m.conversation_id = :conversation_id
                ORDER BY m.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':conversation_id', $conversation_id, PDO::PARAM_INT);
            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Si aucun message n'est trouvé, retourner un message approprié
            if (empty($messages)) {
                return ['success' => true, 'messages' => [], 'attachments' => [], 'message' => 'Aucun message pour l\'instant'];
            }

            $sql_attachments = "
                SELECT ma.id, ma.file_path, ma.file_type
                FROM message_attachments ma
                JOIN messages m ON m.id = ma.message_id
                WHERE m.conversation_id = ?
            ";
            $stmt = $this->db->prepare($sql_attachments);
            $stmt->execute([$conversation_id]);
            $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'messages' => $messages, 'attachments' => $attachments];
        } catch (PDOException $e) {
            error_log("Erreur getMessages: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération des messages'];
        }
    }

    public function sendMessage($user_id, $conversation_id, $content, $attachments) {
        try {
            // Récupérer l'autre utilisateur de la conversation
            $sql_conv = "SELECT user1_id, user2_id FROM conversations WHERE id = ?";
            $stmt = $this->db->prepare($sql_conv);
            $stmt->execute([$conversation_id]);
            $conv = $stmt->fetch(PDO::FETCH_ASSOC);
            $receiver_id = ($conv['user1_id'] == $user_id) ? $conv['user2_id'] : $conv['user1_id'];

            $sql = "INSERT INTO messages (conversation_id, sender_id, receiver_id, content, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$conversation_id, $user_id, $receiver_id, $content]);
            $message_id = $this->db->lastInsertId();

            foreach ($attachments as $attachment) {
                $sql = "INSERT INTO message_attachments (message_id, file_path, file_type, file_name, file_size) VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $message_id, 
                    $attachment['file_path'], 
                    $attachment['file_type'], 
                    $attachment['file_name'],
                    $attachment['file_size']
                ]);
            }

            return ['success' => true, 'message_id' => $message_id, 'attachments' => $attachments];
        } catch (PDOException $e) {
            error_log("Erreur sendMessage: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function searchUsers($query) {
        try {
            $sql = "
                SELECT id, first_name, last_name,
                       'assets/images/default-avatar.png' AS profile_image
                FROM users
                WHERE (first_name LIKE ? OR last_name LIKE ?)
                AND id != ?
                LIMIT 10
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(["%$query%", "%$query%", $_SESSION['user_id']]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'users' => $users];
        } catch (PDOException $e) {
            error_log("Erreur searchUsers: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function createConversation($user_id, $other_user_id) {
        try {
            $sql = "SELECT id FROM conversations WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                return ['success' => true, 'conversation_id' => $existing['id'], 'is_new_conversation' => false];
            }

            $sql = "INSERT INTO conversations (user1_id, user2_id, last_message_at) VALUES (?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $other_user_id]);
            $conversation_id = $this->db->lastInsertId();

            return ['success' => true, 'conversation_id' => $conversation_id, 'is_new_conversation' => true];
        } catch (PDOException $e) {
            error_log("Erreur createConversation: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function checkPermissions($user_id, $conversation_id) {
        try {
            $sql = "SELECT user1_id, user2_id FROM conversations WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$conversation_id]);
            $conv = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conv) {
                return ['success' => false, 'message' => 'Conversation introuvable'];
            }

            $can_read = $can_write = in_array($user_id, [$conv['user1_id'], $conv['user2_id']]);
            return ['success' => true, 'permissions' => ['can_read' => $can_read, 'can_write' => $can_write]];
        } catch (PDOException $e) {
            error_log("Erreur checkPermissions: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function addReaction($user_id, $message_id, $reaction) {
        try {
            $sql = "INSERT INTO message_reactions (message_id, user_id, reaction, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$message_id, $user_id, $reaction]);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Erreur addReaction: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function startCall($user_id, $conversation_id, $call_type) {
        try {
            // Récupérer l'autre utilisateur de la conversation
            $sql_conv = "SELECT user1_id, user2_id FROM conversations WHERE id = ?";
            $stmt = $this->db->prepare($sql_conv);
            $stmt->execute([$conversation_id]);
            $conv = $stmt->fetch(PDO::FETCH_ASSOC);
            $receiver_id = ($conv['user1_id'] == $user_id) ? $conv['user2_id'] : $conv['user1_id'];

            $sql = "INSERT INTO message_calls (caller_id, receiver_id, status, type, started_at) VALUES (?, ?, 'missed', ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $receiver_id, $call_type]);
            $call_id = $this->db->lastInsertId();

            return ['success' => true, 'call_id' => $call_id];
        } catch (PDOException $e) {
            error_log("Erreur startCall: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateCallStatus($call_id, $status) {
        try {
            $sql = "UPDATE message_calls SET status = ?, ended_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status, $call_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Erreur updateCallStatus: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function addMessagePermission($user_id, $can_message_user_id) {
        try {
            $sql = "INSERT INTO message_permissions (user_id, can_message_user_id, created_at) VALUES (?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $can_message_user_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Erreur addMessagePermission: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function checkMessagePermission($user_id, $other_user_id) {
        try {
            $sql = "SELECT id FROM message_permissions WHERE (user_id = ? AND can_message_user_id = ?) OR (user_id = ? AND can_message_user_id = ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
            return ['success' => true, 'has_permission' => $stmt->fetch() !== false];
        } catch (PDOException $e) {
            error_log("Erreur checkMessagePermission: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function removeReaction($user_id, $message_id) {
        try {
            $sql = "DELETE FROM message_reactions WHERE message_id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$message_id, $user_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Erreur removeReaction: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAttachment($user_id, $attachment_id) {
        try {
            $sql = "
                SELECT ma.file_path, ma.file_type, ma.file_name, ma.file_size
                FROM message_attachments ma
                JOIN messages m ON m.id = ma.message_id
                JOIN conversations c ON c.id = m.conversation_id
                WHERE ma.id = ? AND (c.user1_id = ? OR c.user2_id = ?)
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$attachment_id, $user_id, $user_id]);
            $attachment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$attachment) {
                return ['success' => false, 'message' => 'Fichier introuvable ou accès refusé'];
            }

            header('Content-Type: ' . $attachment['file_type']);
            header('Content-Disposition: attachment; filename="' . $attachment['file_name'] . '"');
            readfile($attachment['file_path']);
            exit;
        } catch (PDOException $e) {
            error_log("Erreur getAttachment: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getConversation($user_id, $conversation_id) {
        try {
            $sql = "
                SELECT c.id, c.user_id, c.other_user_id,
                       u1.first_name AS user1_first_name, u1.last_name AS user1_last_name,
                       u2.first_name AS user2_first_name, u2.last_name AS user2_last_name
                FROM conversations c
                JOIN users u1 ON u1.id = c.user_id
                JOIN users u2 ON u2.id = c.other_user_id
                WHERE c.id = ? AND (c.user_id = ? OR c.other_user_id = ?)
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$conversation_id, $user_id, $user_id]);
            $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conversation) {
                return ['success' => false, 'message' => 'Conversation introuvable'];
            }

            return ['success' => true, 'conversation' => $conversation];
        } catch (PDOException $e) {
            error_log("Erreur getConversation: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}