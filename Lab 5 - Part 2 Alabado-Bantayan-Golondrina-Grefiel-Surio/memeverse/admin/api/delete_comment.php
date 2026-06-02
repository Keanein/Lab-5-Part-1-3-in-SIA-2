<?php
// admin/api/delete_comment.php - PHASE 11 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/check_admin.php';

$data = json_decode(file_get_contents('php://input'), true);

$id = (int)($data['id'] ?? 0);

if (!$id) {
    echo json_encode(['error' => 'Invalid comment ID']);
    exit;
}

// Delete comment (replies will cascade if foreign key is set)
$stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(['success' => true]);
exit;
?>