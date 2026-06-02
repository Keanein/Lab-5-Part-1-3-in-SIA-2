<?php
// api/trending_posts.php - PHASE 5 PROFESSOR'S VERSION
// Paginated API endpoint for infinite scroll on trending page

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Extract pagination boundary parameters from query string
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Execute trending extraction query with time window filter (last 7 days)
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.avatar, u.nickname, u.id as user_id,
           c.name as category_name, c.slug as category_slug,
           (SELECT COALESCE(SUM(vote_value), 0) FROM votes WHERE post_id = p.id) as vote_score,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN categories c ON p.category_id = c.id
    WHERE p.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY vote_score DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

// Hydrate each post with the current user's vote state
foreach ($posts as &$post) {
    $post['user_vote'] = getUserVote($pdo, $post['id'], $_SESSION['user_id'] ?? 0);
}

// Return pure JSON payload for client-side rendering
echo json_encode($posts);
exit;
?>