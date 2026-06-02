<?php
// api/delete_conversation.php - PHASE 8 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$other_user_id = (int)($data['user_id'] ?? 0);

if (!$other_user_id) {
    echo json_encode(['error' => 'Invalid user']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Delete all messages between the two users
$stmt = $pdo->prepare("
    DELETE FROM messages
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
");

$stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);

echo json_encode(['success' => true]);
exit;
?>