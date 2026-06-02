<?php
require_once '../includes/functions.php';

$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // ALIAS USED HERE: u.avatar AS profile_pic
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.avatar AS profile_pic, 
        c.name as category_name, c.slug as category_slug 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$post_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        jsonResponse(['error' => 'Post not found'], 404);
    }

    $post = [
        'id' => $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'image_path' => $row['image_path'],
        'user' => [
            'id' => $row['user_id'],
            'username' => $row['username'],
            'profile_pic' => $row['profile_pic'] // Matches the alias
        ]
    ];

    jsonResponse(['success' => true, 'post' => $post]);

} catch (PDOException $e) {
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
?>