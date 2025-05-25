<?php
// Activer l'affichage des erreurs PHP pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Créer un fichier de log pour le débogage
function debug_log($message) {
    file_put_contents('debug_attachment.log', date('Y-m-d H:i:s') . " - {$message}\n", FILE_APPEND);
}

debug_log("Début de la requête: " . $_SERVER['REQUEST_URI']);

// Définir le gestionnaire d'erreurs personnalisé
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Erreur PHP: $errstr dans $errfile à la ligne $errline");
    http_response_code(500);
    exit("Erreur serveur interne");
});

// Définir le gestionnaire d'exceptions non attrapées
set_exception_handler(function($e) {
    error_log("Exception non attrapée: " . $e->getMessage());
    http_response_code(500);
    exit("Erreur serveur interne");
});

try {
    session_start();
    
    require_once 'config/database.php';
    require_once 'models/Message.php';
    
    // Vérifier si l'ID de la pièce jointe est fourni
    if (!isset($_GET['attachment_id']) || empty($_GET['attachment_id'])) {
        http_response_code(400);
        exit("ID de pièce jointe manquant");
    }
    
    $attachment_id = $_GET['attachment_id'];
    
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        exit("Utilisateur non connecté");
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Connexion à la base de données
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Récupérer les informations de la pièce jointe
    $messageModel = new Message($pdo);
    $attachment = $messageModel->getAttachment($attachment_id);
    
    debug_log("Recherche de la pièce jointe ID: {$attachment_id}");
    
    if (!$attachment) {
        debug_log("Pièce jointe introuvable pour ID: {$attachment_id}");
        http_response_code(404);
        exit("Pièce jointe introuvable");
    }
    
    debug_log("Pièce jointe trouvée: " . json_encode($attachment));
    
    // Vérifier si l'utilisateur a accès à cette conversation
    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
    $stmt->execute([$attachment['conversation_id'], $user_id, $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        exit("Accès refusé");
    }
    
    // Vérifier si le fichier existe
    $file_path = $attachment['file_path'];
    debug_log("Chemin du fichier: {$file_path}");
    
    if (!file_exists($file_path)) {
        debug_log("ERREUR: Fichier introuvable: {$file_path}");
        
        // Essayer de trouver le fichier avec un chemin relatif
        $alt_path = __DIR__ . '/' . $file_path;
        debug_log("Essai avec chemin alternatif: {$alt_path}");
        
        if (file_exists($alt_path)) {
            debug_log("Fichier trouvé avec chemin alternatif");
            $file_path = $alt_path;
        } else {
            debug_log("Fichier également introuvable avec chemin alternatif");
            http_response_code(404);
            exit("Fichier introuvable");
        }
    }
    
    // Déterminer le type MIME
    $mime_type = mime_content_type($file_path);
    debug_log("Type MIME: {$mime_type}");
    
    // Définir les en-têtes appropriés
    header("Content-Type: {$mime_type}");
    header("Content-Length: " . filesize($file_path));
    header("Content-Disposition: inline; filename=\"" . basename($file_path) . "\"");
    
    debug_log("Envoi du fichier: {$file_path} (taille: " . filesize($file_path) . " octets)");
    
    // Lire et envoyer le fichier
    readfile($file_path);
    debug_log("Fichier envoyé avec succès");
    exit;
    
} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    http_response_code(500);
    exit("Erreur de base de données");
} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    http_response_code(500);
    exit("Erreur serveur interne");
}
