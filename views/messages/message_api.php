<?php
require_once dirname(__DIR__, 2) . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'sendMessage':
        sendMessage($db);
        break;
    case 'getMessages':
        getMessages($db);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function sendMessage($db) {
    $sender_id = $_POST['sender_id'] ?? null;
    $receiver_id = $_POST['receiver_id'] ?? null;
    $message = $_POST['message'] ?? '';

    if ($sender_id && $receiver_id && $message) {
        $query = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (:sender_id, :receiver_id, :message)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':sender_id', $sender_id);
        $stmt->bindParam(':receiver_id', $receiver_id);
        $stmt->bindParam(':message', $message);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Message could not be sent']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
    }
}

function getMessages($db) {
    $receiver_id = $_GET['receiver_id'] ?? null;

    if ($receiver_id) {
        $query = "SELECT * FROM messages WHERE receiver_id = :receiver_id ORDER BY created_at ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':receiver_id', $receiver_id);
        $stmt->execute();

        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'messages' => $messages]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
    }
}
?>
