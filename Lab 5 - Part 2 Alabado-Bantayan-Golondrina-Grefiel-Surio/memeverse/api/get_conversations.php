<?php
// api/get_conversations.php - PHASE 8 PROFESSOR'S VERSION

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

$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.username, u.nickname, u.avatar,
           (SELECT message FROM messages
            WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)
            ORDER BY created_at DESC LIMIT 1) as last_msg,
           (SELECT created_at FROM messages
            WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)
            ORDER BY created_at DESC LIMIT 1) as last_time,
           (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread
    FROM messages m
    JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
    GROUP BY u.id
    ORDER BY last_time DESC
");

$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll();

foreach ($conversations as &$c) {
    $c['avatar_url'] = getUserAvatar($c, 48);
    $c['last_msg'] = $c['last_msg'] ?? 'No messages yet';
    $c['last_time'] = $c['last_time'] ? timeAgo($c['last_time']) : '';
}

echo json_encode(['success' => true, 'conversations' => $conversations]);
exit;
?>