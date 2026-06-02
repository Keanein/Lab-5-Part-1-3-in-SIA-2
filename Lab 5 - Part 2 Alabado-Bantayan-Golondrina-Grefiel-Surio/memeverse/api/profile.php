<?php
require_once '../includes/functions.php';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    jsonResponse(['error' => 'Invalid user'], 400);
}

try {
    // ALIAS USED HERE: avatar AS profile_pic
    $stmt = $pdo->prepare("SELECT id, username, nickname, bio, avatar AS profile_pic, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonResponse(['error' => 'User not found'], 404);
    }

    $postStmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $postStmt->execute([$user_id]);
    $postCount = $postStmt->fetchColumn();

    jsonResponse([
        'success' => true,
        'user' => $user,
        'post_count' => (int)$postCount
    ]);

} catch (PDOException $e) {
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
?>