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
        $query = "SELECT balance FROM wallets WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return floatval($result['balance']);
        }
        
        // Si l'utilisateur n'a pas de wallet, on en crée un
        $this->createWallet($userId);
        return 0.00;
    }

    /**
     * Crée un wallet pour un utilisateur
     * @param int $userId ID de l'utilisateur
     * @return bool Succès de l'opération
     */
    private function createWallet($userId) {
        $query = "INSERT INTO wallets (user_id, balance, created_at) VALUES (:user_id, 0.00, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }

    /**
     * Ajoute des fonds au wallet de l'utilisateur
     * @param int $userId ID de l'utilisateur
     * @param float $amount Montant à ajouter
     * @param string $paymentMethod Méthode de paiement
     * @param string $description Description de la transaction
     * @return bool Succès de l'opération
     */
    public function addFunds($userId, $amount, $paymentMethod, $description) {
        try {
            $this->db->beginTransaction();
            
            // Récupération du solde actuel
            $currentBalance = $this->getBalance($userId);
            $newBalance = $currentBalance + $amount;
            
            // Mise à jour du solde
            $updateQuery = "UPDATE wallets SET balance = :balance WHERE user_id = :user_id";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindParam(':balance', $newBalance);
            $updateStmt->bindParam(':user_id', $userId);
            $updateStmt->execute();
            
            // Enregistrement de la transaction
            $transactionQuery = "INSERT INTO wallet_transactions (user_id, type, amount, balance_after, payment_method, description, created_at) 
                                VALUES (:user_id, 'credit', :amount, :balance_after, :payment_method, :description, NOW())";
            $transactionStmt = $this->db->prepare($transactionQuery);
            $transactionStmt->bindParam(':user_id', $userId);
            $transactionStmt->bindParam(':amount', $amount);
            $transactionStmt->bindParam(':balance_after', $newBalance);
            $transactionStmt->bindParam(':payment_method', $paymentMethod);
            $transactionStmt->bindParam(':description', $description);
            $transactionStmt->execute();
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de l'ajout de fonds: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retire des fonds du wallet de l'utilisateur
     * @param int $userId ID de l'utilisateur
     * @param float $amount Montant à retirer
     * @param string $withdrawMethod Méthode de retrait
     * @param string $description Description de la transaction
     * @return bool Succès de l'opération
     */
    public function withdrawFunds($userId, $amount, $withdrawMethod, $description) {
        try {
            $this->db->beginTransaction();
            
            // Récupération du solde actuel
            $currentBalance = $this->getBalance($userId);
            
            // Vérification du solde
            if ($currentBalance < $amount) {
                $this->db->rollBack();
                return false;
            }
            
            $newBalance = $currentBalance - $amount;
            
            // Mise à jour du solde
            $updateQuery = "UPDATE wallets SET balance = :balance WHERE user_id = :user_id";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindParam(':balance', $newBalance);
            $updateStmt->bindParam(':user_id', $userId);
            $updateStmt->execute();
            
            // Enregistrement de la transaction
            $transactionQuery = "INSERT INTO wallet_transactions (user_id, type, amount, balance_after, payment_method, description, created_at) 
                                VALUES (:user_id, 'debit', :amount, :balance_after, :payment_method, :description, NOW())";
            $transactionStmt = $this->db->prepare($transactionQuery);
            $transactionStmt->bindParam(':user_id', $userId);
            $transactionStmt->bindParam(':amount', $amount);
            $transactionStmt->bindParam(':balance_after', $newBalance);
            $transactionStmt->bindParam(':payment_method', $withdrawMethod);
            $transactionStmt->bindParam(':description', $description);
            $transactionStmt->execute();
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors du retrait de fonds: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les transactions de l'utilisateur
     * @param int $userId ID de l'utilisateur
     * @return array Transactions de l'utilisateur
     */
    public function getTransactions($userId) {
        $query = "SELECT * FROM wallet_transactions WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 50";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les dépenses mensuelles de l'utilisateur
     * @param int $userId ID de l'utilisateur
     * @return float Dépenses mensuelles
     */
    public function getMonthlyExpenses($userId) {
        $query = "SELECT SUM(amount) as total FROM wallet_transactions 
                  WHERE user_id = :user_id AND type = 'debit' 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ? floatval($result['total']) : 0.00;
    }

    /**
     * Récupère les revenus mensuels de l'utilisateur
     * @param int $userId ID de l'utilisateur
     * @return float Revenus mensuels
     */
    public function getMonthlyIncome($userId) {
        $query = "SELECT SUM(amount) as total FROM wallet_transactions 
                  WHERE user_id = :user_id AND type = 'credit' 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ? floatval($result['total']) : 0.00;
    }
} 