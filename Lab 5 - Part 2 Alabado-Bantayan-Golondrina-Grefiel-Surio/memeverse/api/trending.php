<?php
// api/trending.php - PHASE 5 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Get top 5 posts by vote score from the last 7 days
$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.image_path, p.created_at,
           (SELECT COALESCE(SUM(vote_value), 0) FROM votes WHERE post_id = p.id) as vote_score,
           u.username
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY vote_score DESC
    LIMIT 5
");

$stmt->execute();
$trending = $stmt->fetchAll();

echo json_encode($trending);
exit;
?>