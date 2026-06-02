<?php
// admin/api/add_category.php - PHASE 11 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/check_admin.php';

$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$slug = trim($data['slug'] ?? '');

if (empty($name) || empty($slug)) {
    echo json_encode(['error' => 'Name and slug are required']);
    exit;
}

// Check if slug already exists
$stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
$stmt->execute([$slug]);

if ($stmt->fetch()) {
    echo json_encode(['error' => 'Slug already exists']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
$stmt->execute([$name, $slug]);

echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
exit;
?>