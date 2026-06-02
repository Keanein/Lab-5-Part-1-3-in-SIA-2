<?php
// api/vote.php - PHASE 6 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Must be logged in to vote
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login first']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$vote = isset($_POST['vote']) ? $_POST['vote'] : '';

if (!$post_id || !in_array($vote, ['up', 'down'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$vote_value = ($vote === 'up') ? 1 : -1;
$user_id = $_SESSION['user_id'];

// Get post owner
$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    echo json_encode(['error' => 'Post not found']);
    exit;
}

// Check if user already voted
$stmt = $pdo->prepare("SELECT id, vote_value FROM votes WHERE post_id = ? AND user_id = ?");
$stmt->execute([$post_id, $user_id]);
$existing = $stmt->fetch();

$voted = false; // whether a vote was added/changed (for notification)

if ($existing) {
    if ($existing['vote_value'] == $vote_value) {
        // Same vote → delete (toggle off)
        $pdo->prepare("DELETE FROM votes WHERE id = ?")->execute([$existing['id']]);
        $voted = false;
    } else {
        // Opposite vote → update
        $pdo->prepare("UPDATE votes SET vote_value = ? WHERE id = ?")->execute([$vote_value, $existing['id']]);
        $voted = true;
    }
} else {
    // No vote → insert
    $pdo->prepare("INSERT INTO votes (post_id, user_id, vote_value) VALUES (?, ?, ?)")->execute([$post_id, $user_id, $vote_value]);
    $voted = true;
}

// Create notification for post owner (only if not self-vote and vote was added/changed)
if ($voted && $post['user_id'] != $user_id) {
    // Check for duplicate notification in last hour
    $stmt = $pdo->prepare("
        SELECT id FROM notifications
        WHERE user_id = ? AND type = 'vote' AND source_id = ? AND actor_id = ?
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$post['user_id'], $post_id, $user_id]);
    
    if (!$stmt->fetch()) {
        $notify = $pdo->prepare("INSERT INTO notifications (user_id, type, source_id, actor_id) VALUES (?, 'vote', ?, ?)");
        $notify->execute([$post['user_id'], $post_id, $user_id]);
    }
}

// Get new vote score
$new_score = getVoteScore($pdo, $post_id);

echo json_encode(['success' => true, 'new_score' => $new_score]);
exit;
?>