<?php
// Ignorer l'avertissement XDebug en développement
putenv('RATCHET_DISABLE_XDEBUG_WARN=1');

require 'vendor/autoload.php';
require_once 'config/Database.php';
require_once 'models/Message.php';
require_once 'models/User.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface {
    protected $clients;
    protected $userConnections;
    protected $database;
    protected $messageModel;
    protected $userModel;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        
        // Initialiser la connexion à la base de données
        $database = new Database();
        $this->database = $database->getConnection();
        $this->messageModel = new Message($this->database);
        $this->userModel = new User($this->database);

        echo "Base de données connectée avec succès\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nouvelle connexion! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg);
        
        if ($data->type === 'auth') {
            // Authentification de l'utilisateur
            $this->userConnections[$data->userId] = $from;
            echo "Utilisateur {$data->userId} authentifié\n";
            return;
        }

        if ($data->type === 'message') {
            try {
                // Sauvegarder le message dans la base de données
                $messageId = $this->messageModel->create([
                    'sender_id' => $data->senderId,
                    'receiver_id' => $data->receiverId,
                    'content' => $data->content,
                    'type' => 'text'
                ]);

                if ($messageId) {
                    // Préparer le message à envoyer
                    $messageData = [
                        'id' => $messageId,
                        'type' => 'message',
                        'senderId' => $data->senderId,
                        'receiverId' => $data->receiverId,
                        'content' => $data->content,
                        'timestamp' => date('Y-m-d H:i:s')
                    ];

                    // Envoyer au destinataire s'il est connecté
                    if (isset($this->userConnections[$data->receiverId])) {
                        $this->userConnections[$data->receiverId]->send(json_encode($messageData));
                    }

                    // Confirmer à l'expéditeur
                    $from->send(json_encode([
                        'type' => 'confirmation',
                        'messageId' => $messageId,
                        'status' => 'sent'
                    ]));
                }
            } catch (Exception $e) {
                echo "Erreur: " . $e->getMessage() . "\n";
                $from->send(json_encode([
                    'type' => 'error',
                    'message' => 'Erreur lors de l\'envoi du message'
                ]));
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // Retirer l'utilisateur des connexions actives
        foreach ($this->userConnections as $userId => $connection) {
            if ($connection === $conn) {
                unset($this->userConnections[$userId]);
                break;
            }
        }
        
        echo "Connexion {$conn->resourceId} fermée\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Erreur: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Lancer le serveur WebSocket
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    3000
);

echo "Serveur WebSocket démarré sur le port 3000\n";
$server->run();
