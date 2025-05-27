<?php
class Wallet {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupère le solde actuel de l'utilisateur
     * @param int $userId ID de l'utilisateur
     * @return float Solde actuel
     */
    public function getBalance($userId) {
        // Vérifier si le wallet existe, sinon le créer
        $this->ensureWalletExists($userId);
        
        $query = "SELECT balance FROM wallets WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? floatval($result['balance']) : 0;
    }

    /**
     * S'assure qu'un wallet existe pour l'utilisateur
     * @param int $userId ID de l'utilisateur
     * @return bool True si le wallet existe ou a été créé
     */
    private function ensureWalletExists($userId) {
        $query = "SELECT id FROM wallets WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        
        if (!$stmt->fetch()) {
            return $this->createWallet($userId);
        }
        
        return true;
    }

    /**
     * Crée un wallet pour un utilisateur
     * @param int $userId ID de l'utilisateur
     * @return bool Succès de l'opération
     */
    public function createWallet($userId) {
        try {
            $query = "INSERT INTO wallets (user_id, balance, created_at) VALUES (?, 0, NOW())";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Erreur lors de la création du wallet: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ajoute directement un montant au solde de l'utilisateur (sans transaction imbriquée)
     * @param int $userId ID de l'utilisateur
     * @param float $amount Montant à ajouter
     * @return bool Succès de l'opération
     */
    public function addToBalance($userId, $amount) {
        // Vérifier si le wallet existe, sinon le créer
        $this->ensureWalletExists($userId);
        
        // Mettre à jour le solde
        $query = "UPDATE wallets SET balance = balance + ? WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$amount, $userId]);
    }

    /**
     * Soustrait directement un montant au solde de l'utilisateur (sans transaction imbriquée)
     * @param int $userId ID de l'utilisateur
     * @param float $amount Montant à soustraire
     * @return bool Succès de l'opération
     */
    public function substractFromBalance($userId, $amount) {
        try {
            // Convertir les paramètres pour s'assurer qu'ils sont du bon type
            $userId = intval($userId);
            $amount = floatval($amount);
            
            error_log("substractFromBalance - userId: $userId, amount: $amount");
            
            // Vérifier que les valeurs sont valides
            if ($userId <= 0 || $amount <= 0) {
                error_log("substractFromBalance - Valeurs invalides: userId=$userId, amount=$amount");
                return false;
            }
            
            // Vérifier si le wallet existe, sinon le créer
            if (!$this->ensureWalletExists($userId)) {
                error_log("substractFromBalance - Wallet inexistant pour l'utilisateur $userId");
                return false;
            }
            
            // Vérifier le solde avec une requête directe pour éviter les problèmes de cache
            $query = "SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                error_log("substractFromBalance - Solde introuvable pour l'utilisateur $userId");
                return false;
            }
            
            $currentBalance = floatval($result['balance']);
            error_log("substractFromBalance - Solde actuel: $currentBalance");
            
            if ($currentBalance < $amount) {
                error_log("substractFromBalance - Solde insuffisant: $currentBalance < $amount");
                return false;
            }
            
            // Mettre à jour le solde avec verrouillage explicite pour éviter les problèmes de concurrence
            $newBalance = $currentBalance - $amount;
            $query = "UPDATE wallets SET balance = ? WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$newBalance, $userId]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("substractFromBalance - Erreur SQL: " . json_encode($errorInfo));
                return false;
            }
            
            if ($stmt->rowCount() === 0) {
                error_log("substractFromBalance - Aucune ligne mise à jour");
                return false;
            }
            
            error_log("substractFromBalance - Mise à jour réussie, nouveau solde: $newBalance");
            return true;
        } catch (Exception $e) {
            error_log("substractFromBalance - Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Enregistre une transaction dans l'historique
     * @param int $userId ID de l'utilisateur
     * @param string $type Type de transaction (credit/debit)
     * @param float $amount Montant de la transaction
     * @param string $description Description de la transaction
     * @return bool Succès de l'opération
     */
    public function logTransaction($userId, $type, $amount, $description = '') {
        // Récupérer le solde actuel
        $balance = $this->getBalance($userId);
        
        // Enregistrer la transaction
        $query = "INSERT INTO wallet_transactions (user_id, type, amount, description, balance_after, payment_method, created_at) 
                  VALUES (?, ?, ?, ?, ?, 'system', NOW())";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$userId, $type, $amount, $description, $balance]);
    }

    /**
     * Vérifie si une transaction KKiaPay est valide
     * @param string $transactionId ID de la transaction KKiaPay
     * @return bool True si la transaction est valide
     */
    private function verifyKkiaPayTransaction($transactionId) {
        // Charger la configuration KKiaPay
        $config = require __DIR__ . '/../config/kkiapay.php';
        
        // Créer une instance de l'API KKiaPay
        $kkiaPay = new KkiaPayAPI($config['private_key'], $config['sandbox']);
        
        // Vérifier la transaction
        return $kkiaPay->isTransactionValid($transactionId);
    }

    /**
     * Ajoute des fonds au wallet de l'utilisateur via KKiaPay
     * @param int $userId ID de l'utilisateur
     * @param float $amount Montant à ajouter
     * @param string $transactionId ID de la transaction KKiaPay
     * @return bool Succès de l'opération
     */
    public function addFundsViaKkiaPay($userId, $amount, $transactionId) {
        try {
            // Vérifier la transaction KKiaPay
            if (!$this->verifyKkiaPayTransaction($transactionId)) {
                throw new Exception('Transaction KKiaPay invalide');
            }

            $this->db->beginTransaction();

            // Mettre à jour le solde
            $query = "UPDATE wallets SET balance = balance + ? WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$amount, $userId]);

            // Récupérer le nouveau solde
            $newBalance = $this->getBalance($userId);

            // Enregistrer la transaction
            $query = "INSERT INTO wallet_transactions (user_id, type, amount, description, balance_after, payment_method, created_at) 
                      VALUES (?, 'credit', ?, ?, ?, 'kkiapay', NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $amount, "Dépôt via KKiaPay - Transaction ID: $transactionId", $newBalance]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de l'ajout de fonds via KKiaPay: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retire des fonds du wallet de l'utilisateur via KKiaPay
     * @param int $userId ID de l'utilisateur
     * @param float $amount Montant à retirer
     * @param string $transactionId ID de la transaction KKiaPay
     * @return bool Succès de l'opération
     */
    public function withdrawFundsViaKkiaPay($userId, $amount, $transactionId) {
        try {
            // Vérifier la transaction KKiaPay
            if (!$this->verifyKkiaPayTransaction($transactionId)) {
                throw new Exception('Transaction KKiaPay invalide');
            }

            $this->db->beginTransaction();

            // Vérifier le solde
            $currentBalance = $this->getBalance($userId);
            if ($currentBalance < $amount) {
                throw new Exception('Solde insuffisant');
            }

            // Mettre à jour le solde
            $query = "UPDATE wallets SET balance = balance - ? WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$amount, $userId]);

            // Récupérer le nouveau solde
            $newBalance = $this->getBalance($userId);

            // Enregistrer la transaction
            $query = "INSERT INTO wallet_transactions (user_id, type, amount, description, balance_after, payment_method, created_at) 
                      VALUES (?, 'debit', ?, ?, ?, 'kkiapay', NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $amount, "Retrait via KKiaPay - Transaction ID: $transactionId", $newBalance]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors du retrait de fonds via KKiaPay: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les transactions de l'utilisateur
     * @param int $userId ID de l'utilisateur
     * @param int $limit Limite de transactions à récupérer
     * @return array Transactions de l'utilisateur
     */
    public function getTransactions($userId, $limit = 10) {
        // Convertir la limite en entier pour éviter les problèmes de syntaxe SQL
        $limit = (int)$limit;
        $query = "SELECT * FROM wallet_transactions 
                 WHERE user_id = ? 
                 ORDER BY created_at DESC, id DESC 
                 LIMIT $limit";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les dépenses mensuelles de l'utilisateur
     * @param int $userId ID de l'utilisateur
     * @return float Dépenses mensuelles
     */
    public function getMonthlyExpenses($userId) {
        $query = "SELECT SUM(amount) as total FROM wallet_transactions 
                 WHERE user_id = ? AND type = 'debit' 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? floatval($result['total']) : 0;
    }

    /**
     * Récupère les revenus mensuels de l'utilisateur
     * @param int $userId ID de l'utilisateur
     * @return float Revenus mensuels
     */
    public function getMonthlyIncome($userId) {
        $query = "SELECT SUM(amount) as total FROM wallet_transactions 
                 WHERE user_id = ? AND type = 'credit' 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? floatval($result['total']) : 0;
    }

    /**
     * Réinitialise le solde du wallet de l'utilisateur
     * @param int $userId ID de l'utilisateur
     * @param float $amount Montant à réinitialiser
     * @return bool Succès de l'opération
     */
    public function resetBalance($userId, $amount = 100) {
        try {
            $this->db->beginTransaction();

            // Mettre à jour le solde
            $query = "UPDATE wallets SET balance = ? WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$amount, $userId]);

            // Enregistrer la transaction de réinitialisation
            $query = "INSERT INTO wallet_transactions (user_id, type, amount, description, balance_after) 
                     VALUES (?, 'credit', ?, 'Réinitialisation du solde (Mode démonstration)', ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $amount, $amount]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Ajoute des fonds au wallet de l'utilisateur (hors KKiaPay)
     * @param int $userId ID de l'utilisateur
     * @param float $amount Montant à ajouter
     * @param string $description Description de la transaction
     * @param string|null $transactionId ID de la transaction
     * @return bool Succès de l'opération
     */
    public function addFunds($userId, $amount, $description = 'Dépôt de fonds', $transactionId = null) {
        try {
            $this->db->beginTransaction();
            $this->ensureWalletExists($userId);
            // Créditer le solde
            $query = "UPDATE wallets SET balance = balance + ? WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$amount, $userId]);
            // Récupérer le nouveau solde
            $newBalance = $this->getBalance($userId);
            // Enregistrer la transaction
            if ($transactionId !== null) {
                $query = "INSERT INTO wallet_transactions (user_id, type, amount, description, balance_after, payment_method, transaction_id, created_at) VALUES (?, 'credit', ?, ?, ?, 'kkiapay', ?, NOW())";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$userId, $amount, $description, $newBalance, $transactionId]);
            } else {
                $query = "INSERT INTO wallet_transactions (user_id, type, amount, description, balance_after, payment_method, created_at) VALUES (?, 'credit', ?, ?, ?, 'system', NOW())";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$userId, $amount, $description, $newBalance]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de l'ajout de fonds: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retire des fonds du wallet de l'utilisateur (hors KKiaPay)
     * @param int $userId ID de l'utilisateur
     * @param float $amount Montant à retirer
     * @param string $description Description de la transaction
     * @return bool Succès de l'opération
     */
    public function withdrawFunds($userId, $amount, $description = 'Retrait de fonds') {
        try {
            $this->db->beginTransaction();
            $this->ensureWalletExists($userId);
            // Vérifier le solde
            $currentBalance = $this->getBalance($userId);
            if ($currentBalance < $amount) {
                throw new Exception('Solde insuffisant');
            }
            // Débiter le solde
            $query = "UPDATE wallets SET balance = balance - ? WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$amount, $userId]);
            // Récupérer le nouveau solde
            $newBalance = $this->getBalance($userId);
            // Enregistrer la transaction
            $query = "INSERT INTO wallet_transactions (user_id, type, amount, description, balance_after, payment_method, created_at) VALUES (?, 'debit', ?, ?, ?, 'system', NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $amount, $description, $newBalance]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors du retrait de fonds: " . $e->getMessage());
            return false;
        }
    }
}