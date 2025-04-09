<?php
// Ignorer l'avertissement XDebug en développement
putenv('RATCHET_DISABLE_XDEBUG_WARN=1');

require 'vendor/autoload.php';
require 'config/database.php';
require 'controllers/MessageController.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface {
    protected $clients;
    protected $messageController;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $database = new Database();
        $db = $database->getConnection();
        $this->messageController = new MessageController($db);
    }

    public function onOpen(ConnectionInterface $conn) {
        // Vérifier le token JWT
        $query = $conn->httpRequest->getUri()->getQuery();
        parse_str($query, $params);
        
        try {
            if (empty($params['token']) || !$this->validateToken($params['token'])) {
                $conn->close();
                return;
            }
            
            $this->clients->attach($conn);
            $session = $this->getSessionFromToken($params['token']);
            $conn->userId = $session['user_id'];
            $conn->userRole = $session['user_role'];
        } catch (\Exception $e) {
            $conn->close();
        }
    }

    private function validateToken($token) {
        // Implémentez la validation JWT ici
        // Exemple avec firebase/php-jwt
        try {
            $decoded = \Firebase\JWT\JWT::decode(
                $token,
                new \Firebase\JWT\Key('votre_secret_key', 'HS256')
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getSessionFromToken($token) {
        $decoded = \Firebase\JWT\JWT::decode(
            $token,
            new \Firebase\JWT\Key('votre_secret_key', 'HS256')
        );
        return (array)$decoded;
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!isset($data['type'])) {
            return;
        }

        switch ($data['type']) {
            case 'sendMessage':
                $this->handleSendMessage($from, $data);
                break;
            case 'uploadFile':
                $this->handleFileUpload($from, $data);
                break;
            case 'typing':
                $this->handleTypingStatus($from, $data);
                break;
            case 'markAsRead':
                $this->handleMarkAsRead($from, $data);
                break;
        }
    }

    private function handleSendMessage(ConnectionInterface $from, array $data) {
        try {
            // Enregistrer le message en base de données
            $messageId = $this->messageController->sendMessage(
                $from->userId,
                $data['receiver_id'], 
                $data['message'],
                $data['file_path'] ?? null,
                $data['file_type'] ?? 'text'
            );
            
            if (!$messageId) {
                throw new \Exception("Erreur lors de l'enregistrement du message");
            }

            // Préparer les données du message
            $messageData = [
                'id' => $messageId,
                'type' => 'message',
                'sender_id' => $from->userId,
                'receiver_id' => $data['receiver_id'],
                'content' => $data['message'],
                'file_path' => $data['file_path'] ?? null,
                'file_type' => $data['file_type'] ?? 'text',
                'timestamp' => date('Y-m-d H:i:s')
            ];

            // Envoyer au destinataire
            foreach ($this->clients as $client) {
                if ($client->userId == $data['receiver_id'] || $client === $from) {
                    $client->send(json_encode($messageData));
                }
            }
        } catch (\Exception $e) {
            $from->send(json_encode([
                'type' => 'error',
                'message' => "Erreur lors de l'envoi du message: " . $e->getMessage()
            ]));
        }
    }

    private function handleFileUpload(ConnectionInterface $from, array $data) {
        try {
            $uploadDir = __DIR__ . '/uploads/messages/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileData = base64_decode($data['file_content']);
            $fileName = uniqid() . '_' . $data['file_name'];
            $filePath = $uploadDir . $fileName;
            
            if (file_put_contents($filePath, $fileData)) {
                $messageData = [
                    'type' => 'message',
                    'sender_id' => $from->userId,
                    'receiver_id' => $data['receiver_id'],
                    'content' => $data['message'] ?? '',
                    'file_path' => 'uploads/messages/' . $fileName,
                    'file_type' => $data['file_type'],
                    'timestamp' => date('Y-m-d H:i:s')
                ];

                // Enregistrer le message avec le fichier
                $this->handleSendMessage($from, $messageData);
            } else {
                throw new \Exception("Erreur lors de l'upload du fichier");
            }
        } catch (\Exception $e) {
            $from->send(json_encode([
                'type' => 'error',
                'message' => "Erreur lors de l'upload du fichier: " . $e->getMessage()
            ]));
        }
    }

    private function handleTypingStatus(ConnectionInterface $from, array $data) {
        foreach ($this->clients as $client) {
            if ($client->userId == $data['receiver_id']) {
                $client->send(json_encode([
                    'type' => 'typing',
                    'sender_id' => $from->userId,
                    'is_typing' => $data['is_typing']
                ]));
                break;
            }
        }
    }

    private function handleMarkAsRead(ConnectionInterface $from, array $data) {
        try {
            $this->messageController->markAsRead($from->userId, $data['sender_id']);
            
            // Notifier l'expéditeur que ses messages ont été lus
            foreach ($this->clients as $client) {
                if ($client->userId == $data['sender_id']) {
                    $client->send(json_encode([
                        'type' => 'messagesRead',
                        'reader_id' => $from->userId
                    ]));
                    break;
                }
            }
        } catch (\Exception $e) {
            $from->send(json_encode([
                'type' => 'error',
                'message' => "Erreur lors du marquage des messages comme lus: " . $e->getMessage()
            ]));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = new Ratchet\App('localhost', 3000, '0.0.0.0');
$server->route('/chat', new ChatServer, ['*']);
$server->run();
