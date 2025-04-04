<?php
require_once '../config/database.php';
require_once '../controllers/BookingController.php';

header('Content-Type: application/json');
session_start();

$bookingController = new BookingController($db);

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }

        $data = [
            'passenger_id' => $_SESSION['user_id'],
            'driver_id' => $_POST['driver_id'],
            'amount' => $_POST['amount'],
            'pickup_location' => $_POST['pickup_location'],
            'dropoff_location' => $_POST['dropoff_location'],
            'pickup_time' => $_POST['pickup_time']
        ];

        $result = $bookingController->createBooking($data);
        echo json_encode($result);
        break;

    case 'cancel':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }

        $bookingId = $_POST['booking_id'];
        $result = $bookingController->cancelBooking($bookingId, $_SESSION['user_id']);
        echo json_encode($result);
        break;

    case 'complete':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }

        $bookingId = $_POST['booking_id'];
        $result = $bookingController->completeBooking($bookingId);
        echo json_encode($result);
        break;

    case 'get_transactions':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Non connecté']);
            exit;
        }

        $type = $_POST['type'] ?? 'passenger'; // 'passenger' ou 'driver'
        $transactions = $type === 'passenger' 
            ? $bookingController->transactionModel->getPassengerTransactions($_SESSION['user_id'])
            : $bookingController->transactionModel->getDriverTransactions($_SESSION['user_id']);
        
        echo json_encode(['success' => true, 'transactions' => $transactions]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        break;
} 