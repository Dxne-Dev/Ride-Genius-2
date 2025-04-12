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

    public function getMessages($user_id, $conversation_id, $page, $limit) {
        try {
            $offset = ($page - 1) * $limit;
            $sql = "
                SELECT m.id, m.sender_id, m.content, m.created_at, m.is_read,
                       (SELECT GROUP_CONCAT(r.reaction) FROM reactions r WHERE r.message_id = m.id) AS reactions
                FROM messages m
                WHERE m.conversation_id = ?
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$conversation_id, $limit, $offset]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sql_attachments = "
                SELECT a.id, a.file_path, a.file_type
                FROM attachments a
                JOIN messages m ON m.id = a.message_id
                WHERE m.conversation_id = ?
            ";
            $stmt = $this->db->prepare($sql_attachments);
            $stmt->execute([$conversation_id]);
            $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'messages' => $messages, 'attachments' => $attachments];
        } catch (PDOException $e) {
            error_log("Erreur getMessages: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function sendMessage($user_id, $conversation_id, $content, $attachments) {
        try {
            $sql = "INSERT INTO messages (conversation_id, sender_id, content, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$conversation_id, $user_id, $content]);
            $message_id = $this->db->lastInsertId();

            foreach ($attachments as $attachment) {
                $sql = "INSERT INTO attachments (message_id, file_path, file_type, file_name) VALUES (?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$message_id, $attachment['file_path'], $attachment['file_type'], $attachment['file_name']]);
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
            $sql = "SELECT id FROM conversations WHERE (user_id = ? AND other_user_id = ?) OR (user_id = ? AND other_user_id = ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                return ['success' => true, 'conversation_id' => $existing['id'], 'is_new_conversation' => false];
            }

            $sql = "INSERT INTO conversations (user_id, other_user_id, created_at) VALUES (?, ?, NOW())";
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
            $sql = "SELECT user_id, other_user_id FROM conversations WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$conversation_id]);
            $conv = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conv) {
                return ['success' => false, 'message' => 'Conversation introuvable'];
            }

            $can_read = $can_write = in_array($user_id, [$conv['user_id'], $conv['other_user_id']]);
            return ['success' => true, 'permissions' => ['can_read' => $can_read, 'can_write' => $can_write]];
        } catch (PDOException $e) {
            error_log("Erreur checkPermissions: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function addReaction($user_id, $message_id, $reaction) {
        try {
            $sql = "INSERT INTO reactions (message_id, user_id, reaction, created_at) VALUES (?, ?, ?, NOW())";
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
            $sql = "INSERT INTO calls (conversation_id, caller_id, call_type, start_time) VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$conversation_id, $user_id, $call_type]);
            $call_id = $this->db->lastInsertId();
            return ['success' => true, 'call_id' => $call_id];
        } catch (PDOException $e) {
            error_log("Erreur startCall: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function endCall($call_id) {
        try {
            $sql = "UPDATE calls SET end_time = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$call_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Erreur endCall: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAttachment($user_id, $attachment_id) {
        try {
            $sql = "
                SELECT a.file_path, a.file_type, a.file_name
                FROM attachments a
                JOIN messages m ON m.id = a.message_id
                JOIN conversations c ON c.id = m.conversation_id
                WHERE a.id = ? AND (c.user_id = ? OR c.other_user_id = ?)
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