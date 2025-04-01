<?php
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
        $this->messageController = new MessageController();
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
        
        if ($data['type'] === 'sendMessage') {
            // Enregistrer le message en base de données
            $this->messageController->sendMessage(
                $data['receiver_id'], 
                $data['message'],
                $data['file_path'] ?? null,
                $data['file_type'] ?? 'text'
            );
            
            // Récupérer les messages mis à jour
            $messages = $this->messageController->getMessages($data['receiver_id']);
            
            // Diffuser à tous les clients concernés
            foreach ($this->clients as $client) {
                $client->send(json_encode([
                    'type' => 'updateMessages',
                    'messages' => $messages
                ]));
            }
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
