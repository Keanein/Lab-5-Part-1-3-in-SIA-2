<?php
// api/send_message.php - FIXED VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$receiver_id) {
    echo json_encode(['error' => 'Invalid receiver']);
    exit;
}

if (empty($message)) {
    echo json_encode(['error' => 'Message cannot be empty']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $receiver_id, $message]);
    $msg_id = $pdo->lastInsertId();
    
    $notify = $pdo->prepare("INSERT INTO notifications (user_id, type, source_id, actor_id) VALUES (?, 'message', ?, ?)");
    $notify->execute([$receiver_id, $msg_id, $_SESSION['user_id']]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true]);
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Send message error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to send message']);
    exit;
}
?>