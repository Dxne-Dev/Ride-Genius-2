<?php
session_start();
require_once '../config/database.php';
require_once '../models/Commission.php';

header('Content-Type: application/json');

// Vérification de la session et du rôle admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Non autorisé'
    ]);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$commission = new Commission($db);

// Récupération de l'action demandée
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getWeeklyData':
        $weeklyData = $commission->getWeeklyData();
        echo json_encode([
            'success' => true,
            'data' => $weeklyData
        ]);
        break;

    case 'getTodayTotal':
        $todayTotal = $commission->getTodayTotal();
        echo json_encode([
            'success' => true,
            'data' => $todayTotal
        ]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Action non reconnue'
        ]);
        break;
} 