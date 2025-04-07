<?php
/**
 * Fichier de test corrigé pour la messagerie
 * Corrections ciblées sans modifier le code fonctionnel existant
 */

// Initialisation sécurisée des variables de test
$current_user_id = 23;
$receiver_id = 24; 
$jwt_token = 'valid_test_jwt_'.bin2hex(random_bytes(16));
$driver_name = 'Test Conducteur';

// Mock de base de données sécurisé
class TestDB {
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
$db = new TestDB();

require 'views/messages/chat.php';
?>
<script>
// Configuration corrigée de Socket.IO
const socketConfig = {
    reconnection: true,
    reconnectionAttempts: 5,
    reconnectionDelay: 1000,
    query: { token: '<?= $jwt_token ?>' }
};

// Mock sécurisé de Socket.IO
function createSocketMock() {
    return {
        on: function(event, callback) {
            if (event === 'connect') {
                setTimeout(() => callback(), 100);
            }
            return this;
        },
        emit: function() { return this; }
    };
}

// Tests unitaires améliorés
function runTests() {
    try {
        // Test 1: Vérification du rendu des messages
        const testMessages = [{
            sender_id: 23,
            message: 'Message test',
            first_name: 'Testeur',
            created_at: new Date().toISOString()
        }];
        
        renderMessages(testMessages);
        
        const messages = document.querySelectorAll('.message');
        if (messages.length !== testMessages.length) {
            throw new Error('Nombre de messages incorrect');
        }

        console.log('✅ Tests passés avec succès');
        return true;
    } catch (error) {
        console.error('❌ Test échoué:', error);
        return false;
    }
}

// Initialisation sécurisée
document.addEventListener('DOMContentLoaded', () => {
    window.socket = createSocketMock();
    runTests();
});
</script>
