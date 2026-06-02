<?php
// admin/api/resolve_report.php - PHASE 11 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/check_admin.php';

$data = json_decode(file_get_contents('php://input'), true);

$id = (int)($data['id'] ?? 0);

if (!$id) {
    echo json_encode(['error' => 'Invalid report ID']);
    exit;
}

// Update report status to 'resolved'
$stmt = $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(['success' => true]);
exit;
?>