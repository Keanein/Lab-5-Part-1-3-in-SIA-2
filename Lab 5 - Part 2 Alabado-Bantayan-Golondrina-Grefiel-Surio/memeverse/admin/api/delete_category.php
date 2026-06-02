<?php
// admin/api/delete_category.php - PHASE 11 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/check_admin.php';

$data = json_decode(file_get_contents('php://input'), true);

$id = (int)($data['id'] ?? 0);

if (!$id) {
    echo json_encode(['error' => 'Invalid category ID']);
    exit;
}

// Set posts in this category to NULL (uncategorized)
$stmt = $pdo->prepare("UPDATE posts SET category_id = NULL WHERE category_id = ?");
$stmt->execute([$id]);

// Delete the category
$stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(['success' => true]);
exit;
?>