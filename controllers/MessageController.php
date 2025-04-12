<?php
class MessageController {
    private $db;
    private $messageModel;
    private $conversationModel;

    public function __construct($db) {
        $this->db = $db;
        $this->messageModel = new Message($db);
        $this->conversationModel = new Conversation($db);
    }

    public function getConversations($user_id) {
        $conversations = $this->conversationModel->getUserConversations($user_id);
        return ['success' => true, 'conversations' => $conversations];
    }

    public function getMessages($user_id, $conversation_id, $page = 1, $limit = 20) {
        $permissions = $this->checkPermissions($user_id, $conversation_id);
        if (!$permissions['success'] || !$permissions['permissions']['can_read']) {
            throw new Exception('Permission refusée');
        }
        $offset = ($page - 1) * $limit;
        $messages = $this->messageModel->getConversationMessages($conversation_id, $offset, $limit);
        $attachments = $this->messageModel->getConversationAttachments($conversation_id);
        $this->messageModel->markAsRead($user_id, $conversation_id);
        return ['success' => true, 'messages' => $messages, 'attachments' => $attachments];
    }

    public function sendMessage($sender_id, $conversation_id, $content, $files) {
        $permissions = $this->checkPermissions($sender_id, $conversation_id);
        if (!$permissions['success'] || !$permissions['permissions']['can_write']) {
            throw new Exception('Permission refusée');
        }
        $message_id = $this->messageModel->createMessage($conversation_id, $sender_id, $content);
        $attachments = $this->handleFileUploads($message_id, $files);
        return ['success' => true, 'message_id' => $message_id, 'attachments' => $attachments];
    }

    public function searchUsers($query) {
        $users = $this->messageModel->searchUsers($query, $_SESSION['user_id']);
        return ['success' => true, 'users' => $users];
    }

    public function createConversation($user1_id, $user2_id) {
        $conversation = $this->conversationModel->getOrCreateConversation($user1_id, $user2_id);
        return ['success' => true, 'conversation_id' => $conversation['id'], 'is_new_conversation' => !$conversation['last_message_at']];
    }

    public function checkPermissions($user_id, $conversation_id) {
        $permissions = $this->messageModel->getPermissions($user_id, $conversation_id);
        return ['success' => true, 'permissions' => $permissions];
    }

    public function addReaction($user_id, $message_id, $reaction) {
        $this->messageModel->addReaction($message_id, $user_id, $reaction);
        return ['success' => true];
    }

    public function startCall($user_id, $conversation_id, $call_type) {
        $call_id = $this->messageModel->startCall($conversation_id, $user_id, $call_type);
        return ['success' => true, 'call_id' => $call_id];
    }

    public function endCall($call_id) {
        $this->messageModel->endCall($call_id);
        return ['success' => true];
    }

    public function getAttachment($user_id, $attachment_id) {
        $attachment = $this->messageModel->getAttachment($attachment_id);
        if (!$attachment) {
            throw new Exception('Fichier non trouvé');
        }
        $permissions = $this->checkPermissions($user_id, $attachment['conversation_id']);
        if (!$permissions['success'] || !$permissions['permissions']['can_read']) {
            throw new Exception('Permission refusée');
        }
        header('Content-Type: ' . $attachment['file_type']);
        header('Content-Length: ' . $attachment['file_size']);
        readfile($attachment['file_path']);
        exit;
    }

    private function handleFileUploads($message_id, $files) {
        $attachments = [];
        if (!empty($files)) {
            foreach ($files['name'] as $i => $name) {
                $tmp_name = $files['tmp_name'][$i];
                $path = 'uploads/' . uniqid() . '-' . basename($name);
                $type = $files['type'][$i];
                $size = $files['size'][$i];
                if (!is_dir('uploads')) mkdir('uploads', 0777, true);
                move_uploaded_file($tmp_name, $path);
                $this->messageModel->addAttachment($message_id, $path, $type, $size);
                $attachments[] = ['file_path' => $path, 'file_type' => $this->simplifyFileType($type)];
            }
        }
        return $attachments;
    }

    private function simplifyFileType($mime) {
        if (strpos($mime, 'image') !== false) return 'image';
        if (strpos($mime, 'video') !== false) return 'video';
        return 'file';
    }
}