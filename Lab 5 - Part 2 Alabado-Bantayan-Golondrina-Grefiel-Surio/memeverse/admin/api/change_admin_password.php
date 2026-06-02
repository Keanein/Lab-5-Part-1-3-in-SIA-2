<?php
// admin/api/change_admin_password.php - PHASE 11 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/check_admin.php';

$data = json_decode(file_get_contents('php://input'), true);

$current = $data['current'] ?? '';
$new = $data['new_password'] ?? '';

if (empty($current) || strlen($new) < 6) {
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Verify current password
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || !password_verify($current, $user['password'])) {
    echo json_encode(['error' => 'Current password is incorrect']);
    exit;
}

// Hash new password and update
$hashed = password_hash($new, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->execute([$hashed, $user_id]);

echo json_encode(['success' => true]);
exit;
?>