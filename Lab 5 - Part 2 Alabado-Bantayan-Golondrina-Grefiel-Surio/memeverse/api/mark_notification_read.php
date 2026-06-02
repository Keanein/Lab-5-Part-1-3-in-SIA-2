<?php
// api/mark_notification_read.php - PHASE 9 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$notification_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$notification_id) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->execute([$notification_id, $_SESSION['user_id']]);

echo json_encode(['success' => true]);
exit;
?>