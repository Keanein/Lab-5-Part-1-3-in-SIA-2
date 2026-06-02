<?php
// api/get_messages.php - PHASE 8 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login']);
    exit;
}

$user_id = $_SESSION['user_id'];
$with = isset($_GET['with']) ? (int)$_GET['with'] : 0;

if (!$with) {
    echo json_encode(['error' => 'Missing user']);
    exit;
}

// Fetch messages between the two users
$stmt = $pdo->prepare("
    SELECT m.*, u.username, u.avatar, u.nickname
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY m.created_at ASC
");

$stmt->execute([$user_id, $with, $with, $user_id]);
$messages = $stmt->fetchAll();

// Mark messages from the other user as read
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")
    ->execute([$with, $user_id]);

foreach ($messages as &$m) {
    $m['avatar_url'] = getUserAvatar($m, 32);
}

echo json_encode(['success' => true, 'messages' => $messages]);
exit;
?>