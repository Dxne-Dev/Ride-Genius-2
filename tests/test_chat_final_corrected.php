<?php
/**
 * Test final corrigé de la messagerie
 */

// Initialisation sécurisée de la session
session_start();
$_SESSION = [
    'user_id' => 23,
    'jwt_token' => 'test_token_'.bin2hex(random_bytes(8))
];
$_GET = ['receiver_id' => 24];

// Mock amélioré de la base de données
class MockDatabase {
    public function getConnection() {
        return $this;
    }
    public function prepare($query) {
        return new class {
            private $data = [
                'first_name' => 'Test',
                'last_name' => 'Conducteur'
            ];
            public function bindParam() {}
            public function execute() {}
            public function fetch() {
                return $this->data;
            }
        };
    }
}
$database = new MockDatabase();

require 'views/messages/chat.php';
?>
<script>
// Configuration corrigée de Socket.IO
const socketConfig = {
    reconnection: true,
    reconnectionAttempts: 5,
    reconnectionDelay: 1000,
    query: { token: '<?= $_SESSION['jwt_token'] ?>' }
};

// Mock complet des sockets
class SocketMock {
    constructor() {
        this.listeners = {};
    }
    
    on(event, callback) {
        this.listeners[event] = callback;
        return this;
    }
    
    emit(event, data) {
        console.log(`[Mock] Event "${event}" emitted with data:`, data);
        return this;
    }
    
    connect() {
        setTimeout(() => {
            if(this.listeners.connect) this.listeners.connect();
        }, 100);
    }
}

// Tests complets
async function runTests() {
    try {
        // Test de rendu des messages
        const testMessages = [{
            sender_id: 23,
            message: 'Test message',
            first_name: 'Test',
            created_at: new Date().toISOString()
        }];
        
        renderMessages(testMessages);
        
        const messages = document.querySelectorAll('.message');
        if(messages.length !== testMessages.length) {
            throw new Error('Le nombre de messages affichés ne correspond pas');
        }
        
        console.log('✅ Test de rendu des messages réussi');
        
        // Test d'envoi de message
        const sendResult = await testMessageSending();
        if(!sendResult) {
            throw new Error('L\'envoi de message a échoué');
        }
        
        console.log('✅ Test d\'envoi de message réussi');
        console.log('\nTous les tests ont réussi !');
        return true;
    } catch (error) {
        console.error('❌ Erreur dans les tests:', error.message);
        return false;
    }
}

function testMessageSending() {
    return new Promise((resolve) => {
        const form = document.getElementById('chat-form');
        const originalSubmit = form.onsubmit;
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            try {
                const response = await fetch('message_api.php?action=test');
                resolve(response.ok);
            } catch (error) {
                console.error('Erreur:', error);
                resolve(false);
            }
        }, { once: true });
        
        form.dispatchEvent(new Event('submit'));
    });
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.socket = new SocketMock();
    socket.connect();
    setTimeout(runTests, 200);
});
</script>
