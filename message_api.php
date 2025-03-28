<?php
require_once 'controllers/MessageController.php';

$messageController = new MessageController();

if ($_GET['action'] === 'getMessages') {
    echo json_encode($messageController->getMessages($_GET['receiver_id']));
} elseif ($_GET['action'] === 'sendMessage') {
    $receiver_id = $_POST['receiver_id'];
    $message = $_POST['message'] ?? '';
    $file = null;
    $file_type = 'text';

    if (!empty($_FILES['file']['name'])) {
        $file = 'uploads/' . uniqid() . '_' . basename($_FILES['file']['name']);
        move_uploaded_file($_FILES['file']['tmp_name'], $file);
        $file_type = explode('/', $_FILES['file']['type'])[0];
    }

    $success = $messageController->sendMessage($receiver_id, $message, $file, $file_type);
    echo json_encode(['success' => $success]);
}