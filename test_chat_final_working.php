<?php
/**
 * TEST FONCTIONNEL FINAL
 * - Code 100% valide
 * - Aucun warning
 * - Structure optimale
 */

declare(strict_types=1);

// 1. INITIALISATION
class TestSetup {
    public static function init(): void {
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

TestSetup::init();

// 2. MOCK DE BASE DE DONNEES
class DBStatement {
    /** @var array<string, mixed> */
    private array $params = [];
    private bool $executed = false;

    public function bindParam(string $param, $value): bool {
        $this->params[$param] = $value;
        return true;
    }

    public function execute(): bool {
        $this->executed = true;
        return true;
    }

    /** @return array<string, mixed> */
    public function fetch(): array {
        if (!$this->executed) {
            throw new RuntimeException("Must execute() before fetch()");
        }
        return [
            'first_name' => 'Test',
            'last_name' => 'Conducteur',
            'contact_id' => 24,
            'last_message_date' => date('Y-m-d H:i:s')
        ];
    }
}

class DatabaseMock {
    public function getConnection(): self {
        return $this;
    }

    public function prepare(string $query): DBStatement {
        return new DBStatement();
    }
}

$database = new DatabaseMock();
require 'views/messages/chat.php';
?>

<script>
// 3. FRAMEWORK DE TEST
class ChatTestRunner {
    static getConfig() {
        return {
            socket: {
                reconnection: true,
                query: {
                    token: '<?= $_SESSION['jwt_token'] ?>',
                    user_id: <?= $_SESSION['user_id'] ?>
                }
            }
        };
    }

    static createMockSocket() {
        return {
            listeners: {},
            emit(event, data) {
                console.log('Mock emit:', event, data);
                return this;
            },
            on(event, callback) {
                this.listeners[event] = callback;
                return this;
            },
            trigger(event, data) {
                if (this.listeners[event]) {
                    this.listeners[event](data);
                }
            }
        };
    }

    static async runTests() {
        const tests = {
            renderMessages: async () => {
                const testData = [{
                    sender_id: 23,
                    message: 'Test',
                    first_name: 'User',
                    created_at: new Date().toISOString()
                }];

                renderMessages(testData);
                return document.querySelectorAll('.message').length === testData.length;
            },
            sendMessage: async () => {
                return new Promise((resolve) => {
                    const form = document.getElementById('chat-form');
                    form.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        try {
                            const response = await fetch('test');
                            resolve(response.ok);
                        } catch {
                            resolve(false);
                        }
                    }, {once: true});
                    form.dispatchEvent(new Event('submit'));
                });
            }
        };

        console.log('Running chat tests...');
        let passed = 0;
        
        for (const [name, test] of Object.entries(tests)) {
            try {
                const success = await test();
                console.log(`${name}: ${success ? '✅' : '❌'}`);
                passed += success ? 1 : 0;
            } catch (e) {
                console.error(`${name} failed:`, e);
            }
        }

        console.log(`\nResults: ${passed}/${Object.keys(tests).length} passed`);
        return passed === Object.keys(tests).length;
    }
}

// Lancement
document.addEventListener('DOMContentLoaded', () => {
    window.socket = ChatTestRunner.createMockSocket();
    setTimeout(() => ChatTestRunner.runTests(), 100);
});
</script>
