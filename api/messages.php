<?php
session_start();
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Non authentifié'
    ]);
    exit;
}

// Vérifier si receiver_id est présent
if (!isset($_GET['receiver_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ID du destinataire manquant'
    ]);
    exit;
}

require_once '../config/Database.php';
require_once '../models/Message.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $messageModel = new Message($db);

    $messages = $messageModel->getConversation($_SESSION['user_id'], $_GET['receiver_id']);

    echo json_encode([
        'status' => 'success',
        'messages' => $messages
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur lors du chargement des messages: ' . $e->getMessage()
    ]);
} 