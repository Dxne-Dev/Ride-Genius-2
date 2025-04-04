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
     * Ajoute des fonds au wallet de l'utilisateur
     * @param int $userId ID de l'utilisateur
     * @param float $amount Montant à ajouter
     * @param string $description Description de la transaction
     * @return bool Succès de l'opération
     */
    public function addFunds($userId, $amount, $description = '') {
        try {
            $this->db->beginTransaction();

            // Mettre à jour le solde
            $query = "UPDATE wallets SET balance = balance + ? WHERE user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$amount, $userId]);

            // Récupérer le nouveau solde
            $newBalance = $this->getBalance($userId);

            // Enregistrer la transaction
            $query = "INSERT INTO wallet_transactions (user_id, type, amount, description, balance_after) VALUES (?, 'credit', ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $amount, $description, $newBalance]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Retire des fonds du wallet de l'utilisateur
     * @param int $userId ID de l'utilisateur
     * @param float $amount Montant à retirer
     * @param string $description Description de la transaction
     * @return bool Succès de l'opération
     */
    public function withdrawFunds($userId, $amount, $description = '') {
        try {
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
            $query = "INSERT INTO wallet_transactions (user_id, type, amount, description, balance_after) VALUES (?, 'debit', ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $amount, $description, $newBalance]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
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
} 