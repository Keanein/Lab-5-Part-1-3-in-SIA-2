<?php
// api/search.php - PHASE 10 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$posts = [];
$users = [];
$categories = [];

if ($q && strlen($q) >= 2) {
    $searchTerm = "%$q%";
    
    // Search posts (title, description, or username)
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.image_path, p.created_at,
               u.username, u.nickname,
               (SELECT COALESCE(SUM(vote_value), 0) FROM votes WHERE post_id = p.id) as vote_score,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.title LIKE ? OR p.description LIKE ? OR u.username LIKE ?
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $posts = $stmt->fetchAll();
    
    // Search users
    $stmt = $pdo->prepare("
        SELECT id, username, nickname, avatar, bio
        FROM users
        WHERE username LIKE ? OR nickname LIKE ?
        LIMIT 10
    ");
    $stmt->execute([$searchTerm, $searchTerm]);
    $users = $stmt->fetchAll();
    
    // Search categories
    $stmt = $pdo->prepare("
        SELECT id, name, slug
        FROM categories
        WHERE name LIKE ?
        LIMIT 5
    ");
    $stmt->execute([$searchTerm]);
    $categories = $stmt->fetchAll();
}

echo json_encode([
    'posts' => $posts,
    'users' => $users,
    'categories' => $categories
]);
exit;
?>