<?php
// api/reset_password.php - PHASE 3 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$input = json_decode(file_get_contents('php://input'), true);

$token = $input['token'] ?? '';
$password = $input['password'] ?? '';

if (empty($token) || strlen($password) < 6) {
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// Find valid token
$stmt = $pdo->prepare("
    SELECT id, user_id FROM password_resets
    WHERE token = ? AND used = 0 AND expires_at > NOW()
");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

// Update password
$hashed = password_hash($password, PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $reset['user_id']]);

// Mark token as used
$pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?")->execute([$reset['id']]);

echo json_encode(['success' => true]);
exit;
?>