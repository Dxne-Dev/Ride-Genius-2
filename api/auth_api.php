<?php
session_start();
header('Content-Type: application/json');

// Vérifier l'action demandée
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'checkAuth':
        // Vérifier si l'utilisateur est connecté
        $isLoggedIn = isset($_SESSION['user_id']);
        
        echo json_encode([
            'success' => true,
            'isLoggedIn' => $isLoggedIn,
            'userId' => $isLoggedIn ? $_SESSION['user_id'] : null
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Action non reconnue'
        ]);
        break;
} 