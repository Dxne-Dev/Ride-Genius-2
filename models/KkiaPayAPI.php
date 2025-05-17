<?php
class KkiaPayAPI {
    private $privateKey;
    private $sandbox;

    public function __construct($privateKey, $sandbox = false) {
        $this->privateKey = $privateKey;
        $this->sandbox = $sandbox;
    }

    /**
     * Vérifie le statut d'une transaction
     * @param string $transactionId ID de la transaction
     * @return array|false Données de la transaction ou false en cas d'erreur
     */
    public function verifyTransaction($transactionId) {
        $baseUrl = $this->sandbox ? 'https://api-sandbox.kkiapay.me' : 'https://api.kkiapay.me';
        $url = $baseUrl . '/api/v1/transactions/' . $transactionId;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->privateKey,
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Erreur KKiaPay API - Code HTTP: $httpCode, Réponse: $response");
            return false;
        }

        $data = json_decode($response, true);
        if (!$data) {
            error_log("Erreur KKiaPay API - Réponse JSON invalide: $response");
            return false;
        }

        return $data;
    }

    /**
     * Vérifie si une transaction est valide et complétée
     * @param string $transactionId ID de la transaction
     * @return bool True si la transaction est valide et complétée
     */
    public function isTransactionValid($transactionId) {
        $transaction = $this->verifyTransaction($transactionId);
        
        if (!$transaction) {
            return false;
        }

        // Vérifier le statut de la transaction
        return isset($transaction['status']) && $transaction['status'] === 'SUCCESS';
    }
} 