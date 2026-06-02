<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');

try {
    // FIXED: Changed from MySQLi ($conn) to the Guidebook's standard PDO ($pdo) to prevent fatal crashes
    $stmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'categories' => $categories]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database execution vector failed.']);
}
?>