<?php
require_once '../config/database.php';
require_once '../controllers/MessageController.php';
require_once '../auth.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Vérifier le token et obtenir l'ID utilisateur
$user_id = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifier que l'ID de la pièce jointe est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de pièce jointe manquant ou invalide']);
    exit;
}

$attachment_id = intval($_GET['id']);

try {
    $db = (new Database())->getConnection();
    $messageController = new MessageController($db);
    
    // Récupérer les informations de la pièce jointe
    $result = $messageController->getAttachment($user_id, $attachment_id);
    
    if (!$result['success']) {
        http_response_code(404);
        echo json_encode($result);
        exit;
    }
    
    $attachment = $result['attachment'];
    
    // Vérifier que le fichier existe
    if (!file_exists($attachment['file_path'])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Fichier non trouvé']);
        exit;
    }
    
    // Servir le fichier de manière sécurisée
    header('Content-Type: ' . $attachment['file_type']);
    header('Content-Disposition: attachment; filename="' . $attachment['file_name'] . '"');
    header('Content-Length: ' . $attachment['file_size']);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=0, must-revalidate');
    
    readfile($attachment['file_path']);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    error_log($e->getMessage());
    exit;
} 