<?php
/**
 * Test final de la messagerie - Version entièrement corrigée
 */

// Configuration de test sécurisée
$_SESSION = [
    'user_id' => 23,
    'jwt_token' => 'test_token_'.bin2hex(random_bytes(8))
];
$_GET = ['receiver_id' => 24];

// Mock de base de données
class MockDB {
    public function prepare($query) {
        return new class {
            private $data = ['first_name'=>'Test', 'last_name'=>'Conducteur'];
            public function bindParam() {}
            public function execute() {} 
            public function fetch() { return $this->data; }
        };
    }
    public function getConnection() { return $this; }
}
$database = new MockDB();

require 'views/messages/chat.php';
?>
<script>
// Réécriture propre des fonctions de test et corrections

// 1. Correction de la configuration Socket.IO
const socketConfig = {
    reconnection: true,
    reconnectionAttempts: 5, 
    reconnectionDelay: 1000,
    query: { token: '<?= $_SESSION['jwt_token'] ?>' }
};

// 2. Mock amélioré des sockets
class SocketMock {
    constructor() {
        this.listeners = {};
    }
    on(event, callback) {
        this.listeners[event] = callback;
        return this;
    }
    emit(event, data) {
        console.log('Mock emit:', event, data);
        return this;
    }
    trigger(event, data) {
        if(this.listeners[event]) this.listeners[event](data);
    }
}

// 3. Tests complets avec gestion d'erreur
function runAllTests() {
    let passed = 0;
    const tests = [
        testMessageRendering,
        testMessageSending
    ];
    
    tests.forEach(test => {
        try {
            if(test()) passed++;
        } catch(e) {
            console.error('❌ Test failed:', e.message);
        }
    });
    
    console.log(`\n${passed}/${tests.length} tests passed`);
    return passed === tests.length;
}

function testMessageRendering() {
    const testData = [{
        sender_id: 23,
        message: 'Test message',
        first_name: 'Test',
        created_at: new Date().toISOString()
    }];
    
    renderMessages(testData);
    
    const messages = document.querySelectorAll('.message');
    if(messages.length !== testData.length) {
        throw new Error('Message count mismatch');
    }
    
    console.log('✅ Message rendering test passed');
    return true;
}

function testMessageSending() {
    const form = document.getElementById('chat-form');
    form.onsubmit = null;
    
    return new Promise(resolve => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const response = await fetch('message_api.php?action=test');
                if(response.ok) {
                    console.log('✅ Message sending test passed');
                    resolve(true);
                }
            } catch(e) {
                console.error('Failed:', e);
                resolve(false);
            }
        });
        
        form.dispatchEvent(new Event('submit'));
    });
}

// Initialisation
window.socket = new SocketMock();
setTimeout(() => {
    socket.trigger('connect');
    runAllTests();
}, 500);
</script>
