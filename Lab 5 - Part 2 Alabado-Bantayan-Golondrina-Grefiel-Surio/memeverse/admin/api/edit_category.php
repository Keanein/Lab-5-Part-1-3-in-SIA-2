<?php
// admin/api/edit_category.php - PHASE 11 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/check_admin.php';

$data = json_decode(file_get_contents('php://input'), true);

$id = (int)($data['id'] ?? 0);
$name = trim($data['name'] ?? '');
$slug = trim($data['slug'] ?? '');

if (!$id || empty($name) || empty($slug)) {
    echo json_encode(['error' => 'Missing data']);
    exit;
}

// Check if slug already used by another category
$stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
$stmt->execute([$slug, $id]);

if ($stmt->fetch()) {
    echo json_encode(['error' => 'Slug already exists']);
    exit;
}

$stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
$stmt->execute([$name, $slug, $id]);

echo json_encode(['success' => true]);
exit;
?>