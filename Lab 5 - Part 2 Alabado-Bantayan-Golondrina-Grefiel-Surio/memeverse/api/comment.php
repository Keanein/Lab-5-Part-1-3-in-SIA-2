<?php
// api/comment.php - PHASE 7 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Fetch comments for a post
if ($method === 'GET') {
    $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
    
    if (!$post_id) {
        echo json_encode(['error' => 'Invalid post ID']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.avatar, u.nickname
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$post_id]);
        $comments = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'comments' => $comments]);
        exit;
    } catch (PDOException $e) {
        error_log("Comment fetch error: " . $e->getMessage());
        echo json_encode(['error' => 'Failed to fetch comments']);
        exit;
    }
}

// POST - Add a comment or reply
if ($method === 'POST') {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Please login first']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $post_id = isset($input['post_id']) ? (int)$input['post_id'] : 0;
    $comment = trim($input['comment'] ?? '');
    $parent_id = isset($input['parent_id']) ? (int)$input['parent_id'] : 0;
    
    if (!$post_id || empty($comment)) {
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }
    
    // Convert 0 to NULL for top-level comments
    $parent_id_sql = ($parent_id > 0) ? $parent_id : null;
    
    try {
        $pdo->beginTransaction();
        
        // Insert comment
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, parent_id, comment_text) VALUES (?, ?, ?, ?)");
        $stmt->execute([$post_id, $_SESSION['user_id'], $parent_id_sql, $comment]);
        $comment_id = $pdo->lastInsertId();
        
        // Create notifications
        if ($parent_id == 0) {
            // Top-level comment - notify post owner
            $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            $post_owner = $stmt->fetch();
            
            if ($post_owner && $post_owner['user_id'] != $_SESSION['user_id']) {
                // Check for duplicate notification in last hour
                $check = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = 'comment' AND source_id = ? AND actor_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                $check->execute([$post_owner['user_id'], $post_id, $_SESSION['user_id']]);
                
                if (!$check->fetch()) {
                    $notify = $pdo->prepare("INSERT INTO notifications (user_id, type, source_id, actor_id) VALUES (?, 'comment', ?, ?)");
                    $notify->execute([$post_owner['user_id'], $post_id, $_SESSION['user_id']]);
                }
            }
        } else {
            // Reply - notify parent comment owner
            $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
            $stmt->execute([$parent_id]);
            $parent_comment = $stmt->fetch();
            
            if ($parent_comment && $parent_comment['user_id'] != $_SESSION['user_id']) {
                $check = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = 'reply' AND source_id = ? AND actor_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                $check->execute([$parent_comment['user_id'], $comment_id, $_SESSION['user_id']]);
                
                if (!$check->fetch()) {
                    $notify = $pdo->prepare("INSERT INTO notifications (user_id, type, source_id, actor_id) VALUES (?, 'reply', ?, ?)");
                    $notify->execute([$parent_comment['user_id'], $comment_id, $_SESSION['user_id']]);
                }
            }
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'comment_id' => $comment_id]);
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Comment error: " . $e->getMessage());
        echo json_encode(['error' => 'Failed to post comment']);
        exit;
    }
}

// DELETE - Remove a comment (author or post owner only)
if ($method === 'DELETE') {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Please login first']);
        exit;
    }
    
    parse_str(file_get_contents('php://input'), $data);
    $comment_id = isset($data['comment_id']) ? (int)$data['comment_id'] : 0;
    
    if (!$comment_id) {
        echo json_encode(['error' => 'Missing comment ID']);
        exit;
    }
    
    // Check permission: comment author OR post owner
    $stmt = $pdo->prepare("
        SELECT c.user_id, p.user_id as post_owner
        FROM comments c
        JOIN posts p ON c.post_id = p.id
        WHERE c.id = ?
    ");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();
    
    if (!$comment) {
        echo json_encode(['error' => 'Comment not found']);
        exit;
    }
    
    if ($comment['user_id'] != $_SESSION['user_id'] && $comment['post_owner'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied']);
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
exit;
?>