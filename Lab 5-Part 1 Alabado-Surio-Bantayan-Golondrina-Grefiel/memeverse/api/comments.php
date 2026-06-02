<?php
    require_once dirname(__DIR__) . '/includes/config.php';
    require_once dirname(__DIR__) . '/includes/functions.php';
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
        
        if ($post_id <= 0) {
            jsonResponse(['error' => 'Invalid post ID'], 400);
        }
        
        $stmt = $conn->prepare("
        SELECT c.id, c.content, c.created_at, u.id as user_id,
        u.username
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
        ");
        
        $stmt->bind_param('i', $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = [
                'id' => $row['id'],
                'content' => $row['content'],
                'created_at' => $row['created_at'],
                'user_id' => $row['user_id'],
                'username' => $row['username']
            ];
        }

        $stmt->close();
        $conn->close();
        jsonResponse(['comments' => $comments]);
    } else if ($method === 'POST') {
        if (!isLoggedIn()) {
            jsonResponse(['error' => 'Authentication required'], 401);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            jsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $post_id = isset($input['post_id']) ? (int)$input['post_id'] : 0;
        $content = isset($input['content']) ? trim($input['content']) : '';
        
        if ($post_id <= 0 || empty($content)) {
            jsonResponse(['error' => 'Post ID and content
            required'], 400);
        }

        // Check post exists
        $checkStmt = $conn->prepare("SELECT id FROM posts WHERE id
        = ?");
        $checkStmt->bind_param('i', $post_id);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows === 0) {
            jsonResponse(['error' => 'Post not found'], 404);
        }

        $checkStmt->close();
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO comments (user_id,
        post_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $user_id, $post_id, $content);
        
        if ($stmt->execute()) {
        $comment_id = $stmt->insert_id;
        $stmt->close();
        $conn->close();
        
        jsonResponse(['success' => true, 'message' => 'Comment added', 'comment_id' => $comment_id], 201);
        
        } else {
            jsonResponse(['error' => 'Failed to add comment'], 500);
        }

    } else {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }
?>