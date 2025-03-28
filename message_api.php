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

    // Gestion des fichiers uploadés
    if (!empty($_FILES['file']['name'])) {
        $uploadDir = __DIR__ . '/uploads/';
        $fileName = uniqid() . '_' . basename($_FILES['file']['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
            $file = 'uploads/' . $fileName;
            $file_type = explode('/', $_FILES['file']['type'])[0]; // Détermine le type de fichier (image, video, audio)
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors du téléchargement du fichier.']);
            exit();
        }
    }

    $success = $messageController->sendMessage($receiver_id, $message, $file, $file_type);
    echo json_encode(['success' => $success]);
}