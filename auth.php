<?php
require_once __DIR__ . '/config/database.php';

// Initialiser la connexion PDO
$database = new Database();
$pdo = $database->getConnection();

function verify_token($user_id, $token) {
    global $pdo;
    try {
        // Purger les tokens expirés
        $pdo->exec("DELETE FROM api_tokens WHERE expires_at < NOW()");
        
        // Vérifier le token
        $stmt = $pdo->prepare("SELECT * FROM api_tokens WHERE user_id = ? AND token = ? AND expires_at > NOW()");
        $stmt->execute([$user_id, $token]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Erreur vérification token: " . $e->getMessage());
        return false;
    }
}

function require_auth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['api_token'])) {
        header('Location: index.php?page=login');
        exit();
    }
    
    // Vérifier si le token est valide
    if (!verify_token($_SESSION['user_id'], $_SESSION['api_token'])) {
        // Si le token est invalide, détruire la session et rediriger vers la page de connexion
        session_destroy();
        header('Location: index.php?page=login');
        exit();
    }
}

function generate_token(int $user_id): string {
    global $pdo;

    if (!$pdo) {
        throw new Exception('Connexion PDO non disponible');
    }

    try {
        // Supprimer les anciens tokens de l'utilisateur
        $stmt = $pdo->prepare("DELETE FROM api_tokens WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Générer un nouveau token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Insérer le nouveau token
        $stmt = $pdo->prepare("INSERT INTO api_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $token, $expires]);

        return $token;
    } catch (PDOException $e) {
        error_log("Erreur génération token: " . $e->getMessage());
        throw new Exception("Erreur lors de la génération du token");
    }
} 