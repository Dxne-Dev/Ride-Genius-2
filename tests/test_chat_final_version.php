<?php
/**
 * Version finale et complète du test de messagerie
 * - Tous les problèmes résolus
 * - Code propre et documenté
 */

// 1. Initialisation sécurisée
class TestEnvironment {
    public static function init() {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => true,
            'use_strict_mode' => true
        ]);
        session_regenerate_id(true);

        $_SESSION = [
            'user_id' => 23,
            'jwt_token' => 'test_token_'.bin2hex(random_bytes(16))
        ];
        $_GET = ['receiver_id' => 24];
    }
}

TestEnvironment::init();

// 2. Mock de base de données complet
class DatabaseMock {
    private $params = [];
    private $executed = false;
    private $lastQuery = '';

    public function getConnection() {
        return $this;
    }

    public function prepare($query) {
        $this->lastQuery = $query;
        $that = $this; // Référence pour la closure
        
        return new class($that) {
            private $parent;
            private $boundParams = [];

            public function __construct($parent) {
                $this->parent = $parent;
            }

            public function bindParam($param, $value) {
                $this->boundParams[$param] = $value;
                $this->parent->params[$param] = $value;
                return true;
            }

            public function execute() {
                $this->parent->executed = true;
                return true;
            }

            public function fetch() {
                if (!$this->parent->executed) {
                    throw new Exception("Execute must be called before fetch");
                }
                return [
                    'first_name' => 'Test',
                    'last_name' => 'Conducteur',
                    'contact_id' => 24,
                    'last_message_date' => date('Y-m-d H:i:s')
                ];
            }
        };
    }
}

$database = new DatabaseMock();
require 'views/messages/chat.php';
?>

<script>
// 3. Configuration et mocks avec validation stricte
class ChatTestFramework {
    static getSocketConfig() {
        return {
            reconnection: true,
            reconnectionAttempts: 5,
            reconnectionDelay: 1000,
            query: {
                token: '<?= $_SESSION['jwt_token'] ?>',
                user_id: <?= $_SESSION['user_id'] ?>
            }
        };
    }

    static createSocketMock() {
        return new class {
            constructor() {
                this.events = new Map();
                this.emittedEvents = [];
            }

            on(event, callback) {
                if (typeof event !== 'string' || typeof callback !== 'function') {
                    throw new TypeError('Invalid event parameters');
                }
                this.events.set(event, callback);
                return this;
            }

            emit(event, data) {
                this.emittedEvents.push({event, data});
                console.debug(`[SocketMock] Emitted ${event}`, data);
                return this;
            }

            trigger(event, data) {
                if (this.events.has(event)) {
                    this.events.get(event)(data);
                }
            }
        };
    }

    static async runTests() {
        const tests = {
            'Message Rendering': async () => {
                const testData = [{
                    sender_id: 23,
                    message: 'Test message',
                    first_name: 'Test',
                    created_at: new Date().toISOString()
                }];

                renderMessages(testData);
                
                const messages = document.querySelectorAll('.message');
                if (messages.length !== testData.length) {
                    throw new Error(`Expected ${testData.length} messages, got ${messages.length}`);
                }
                return true;
            },
            'Message Sending': async () => {
                return new Promise((resolve) => {
                    const form = document.getElementById('chat-form');
                    const handler = async (e) => {
                        e.preventDefault();
                        try {
                            const response = await fetch('message_api.php?action=test');
                            resolve(response.ok);
                        } catch (error) {
                            console.error('Error:', error);
                            resolve(false);
                        }
                    };
                    form.addEventListener('submit', handler, {once: true});
                    form.dispatchEvent(new Event('submit'));
                });
            }
        };

        console.log('Starting chat system tests...');
        let passed = 0;
        
        for (const [name, test] of Object.entries(tests)) {
            try {
                const result = await test();
                console.log(`${result ? '✅' : '❌'} ${name}`);
                passed += result ? 1 : 0;
            } catch (e) {
                console.error(`❌ ${name} - ${e.message}`);
            }
        }

        console.log(`\nFinal result: ${passed}/${Object.keys(tests).length} tests passed`);
        return passed === Object.keys(tests).length;
    }
}

// Initialisation des tests
document.addEventListener('DOMContentLoaded', () => {
    window.socket = ChatTestFramework.createSocketMock();
    
    setTimeout(async () => {
        try {
            await ChatTestFramework.runTests();
        } catch (error) {
            console.error('Test suite failed:', error);
        }
    }, 100);
});
</script>
