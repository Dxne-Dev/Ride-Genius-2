<?php
session_start();
require_once '../../models/Message.php';
require_once '../../models/User.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$message = new Message();
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'send':
        $receiver_id = $_POST['receiver_id'] ?? null;
        $content = $_POST['content'] ?? '';
        
        if ($receiver_id && $content) {
            $result = $message->create($_SESSION['user_id'], $receiver_id, $content);
            echo json_encode(['success' => $result !== false]);
        } else {
            echo json_encode(['error' => 'Missing parameters']);
        }
        break;

    case 'load':
        $other_user_id = $_POST['other_user_id'] ?? null;
        
        if ($other_user_id) {
            $messages = $message->getConversation($_SESSION['user_id'], $other_user_id);
            echo json_encode(['success' => true, 'messages' => $messages]);
        } else {
            echo json_encode(['error' => 'Missing parameters']);
        }
        break;

    case 'uploadFiles':
        $receiver_id = $_POST['receiver_id'] ?? null;
        $uploadedFiles = $_FILES['files'] ?? null;
        $response = ['success' => false, 'files' => []];
        
        if ($receiver_id && $uploadedFiles) {
            $uploadDir = '../../uploads/messages/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($uploadedFiles['tmp_name'] as $key => $tmp_name) {
                $fileName = $uploadedFiles['name'][$key];
                $fileType = $uploadedFiles['type'][$key];
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid() . '_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($tmp_name, $targetPath)) {
                    $type = strpos($fileType, 'image/') === 0 ? 'image' : 
                           (strpos($fileType, 'video/') === 0 ? 'video' : 'file');
                    
                    $url = '/uploads/messages/' . $newFileName;
                    $content = json_encode(['url' => $url, 'type' => $type]);
                    
                    if ($message->create($_SESSION['user_id'], $receiver_id, $content, $type)) {
                        $response['files'][] = [
                            'url' => $url,
                            'type' => $type
                        ];
                        $response['success'] = true;
                    }
                }
            }
        }
        
        echo json_encode($response);
        break;

    case 'addReaction':
        $message_id = $_POST['message_id'] ?? null;
        $reaction = $_POST['reaction'] ?? null;
        
        if ($message_id && $reaction) {
            $result = $message->addReaction($message_id, $_SESSION['user_id'], $reaction);
            echo json_encode(['success' => $result !== false]);
        } else {
            echo json_encode(['error' => 'Missing parameters']);
        }
        break;

    case 'markAsRead':
        $other_user_id = $_POST['other_user_id'] ?? null;
        
        if ($other_user_id) {
            $result = $message->markAsRead($_SESSION['user_id'], $other_user_id);
            echo json_encode(['success' => $result !== false]);
        } else {
            echo json_encode(['error' => 'Missing parameters']);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>
