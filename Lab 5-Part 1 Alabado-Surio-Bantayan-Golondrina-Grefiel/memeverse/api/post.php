<?php
    require_once dirname(__DIR__) . '/includes/config.php';
    require_once dirname(__DIR__) . '/includes/functions.php';

    $post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($post_id <= 0) {
        jsonResponse(['error' => 'Invalid post ID'], 400);
    }

    $sql = "SELECT p.id, p.title, p.description, p.image_path,
                   p.created_at,
                   u.id as user_id, 
                   u.username, 
                   u.profile_pic,
                   c.id as category_id, 
                   c.name as category_name,
                   c.slug as category_slug,
                   COALESCE(up.upvotes, 0) as upvotes,
                   COALESCE(down.downvotes, 0) as downvotes
                   FROM posts p JOIN users u ON p.user_id = u.id JOIN categories c ON p.category_id = c.id 
                   LEFT JOIN (SELECT post_id, COUNT(*) as upvotes FROM votes WHERE vote = 1 GROUP BY post_id) up ON p.id = up.post_id
                   LEFT JOIN (SELECT post_id, COUNT(*) as downvotes FROM votes WHERE vote = -1 GROUP BY post_id) down ON p.id = down.post_id WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
  
    if ($result->num_rows === 0) {
        jsonResponse(['error' => 'Post not found'], 404);
    }
    
    $post = $result->fetch_assoc();
    $imagePath = $post['image_path'];
    
    if (strpos($imagePath, '/') === 0) $imagePath = substr($imagePath, 1);
    
    $fullImageUrl = BASE_URL . '/' . $imagePath;
    $userAvatar = $post['profile_pic'] ? BASE_URL . '/' .
    $post['profile_pic'] : null;
    $user_vote = 0;
    
    if (isLoggedIn()) {
        $voteStmt = $conn->prepare("SELECT vote FROM votes WHERE
        user_id = ? AND post_id = ?");
        $voteStmt->bind_param('ii', $_SESSION['user_id'],
        $post_id);
        $voteStmt->execute();
        $voteResult = $voteStmt->get_result();
        
        if ($voteRow = $voteResult->fetch_assoc()) {
            $user_vote = (int)$voteRow['vote'];
        }
        
        $voteStmt->close();
    }

    $stmt->close();
    $conn->close();

    jsonResponse(['post' => [
                  'id' => $post['id'],
                  'title' => $post['title'],
                  'description' => $post['description'],
                  'image_path' => $fullImageUrl,
                  'created_at' => $post['created_at'],
                  'user' => [
                  'id' => $post['user_id'],
                  'username' => $post['username'],
                  'profile_pic' => $userAvatar
    ],

    'category' => ['id' => $post['category_id'],
                   'name' => $post['category_name'],
                   'slug' => $post['category_slug']
    ],
    'upvotes' => (int)$post['upvotes'],
    'downvotes' => (int)$post['downvotes']
    ],
    'user_vote' => $user_vote
    ]);
?>