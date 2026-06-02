<?php
// admin/api/ban_user.php - PHASE 11 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/check_admin.php';

$data = json_decode(file_get_contents('php://input'), true);

$id = (int)($data['id'] ?? 0);

if (!$id) {
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

// Prevent banning yourself
if ($id == $_SESSION['user_id']) {
    echo json_encode(['error' => 'Cannot ban your own account']);
    exit;
}

// Toggle ban status
$stmt = $pdo->prepare("SELECT banned FROM users WHERE id = ?");
$stmt->execute([$id]);
$current = $stmt->fetchColumn();
$new_status = $current ? 0 : 1;

$stmt = $pdo->prepare("UPDATE users SET banned = ? WHERE id = ?");
$stmt->execute([$new_status, $id]);

echo json_encode(['success' => true, 'new_state' => $new_status]);
exit;
?>