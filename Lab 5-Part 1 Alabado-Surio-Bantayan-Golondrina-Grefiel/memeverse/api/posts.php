<?php
    require_once dirname(__DIR__) . '/includes/config.php';
    require_once dirname(__DIR__) . '/includes/functions.php';
    
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1,(int)$_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;
    $where = [];
    $params = [];
    $types = '';
    
    // Category filter
    if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
        $where[] = "p.category_id = ?";
        $params[] = (int)$_GET['category_id'];
        $types .= 'i';
    } elseif (isset($_GET['category_slug'])) {
        $where[] = "c.slug = ?";
        $params[] = $_GET['category_slug'];
        $types .= 's';
    }

    // User filter (profile)
    if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
        $where[] = "p.user_id = ?";
        $params[] = (int)$_GET['user_id'];
        $types .= 'i';
    }

    $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
    $sql = "SELECT p.id, p.title, p.description, p.image_path, p.created_at,
            u.id as user_id, u.username, u.profile_pic,
            c.id as category_id, c.name as category_name,
            c.slug as category_slug,
            COALESCE(up.upvotes, 0) as upvotes,
            COALESCE(down.downvotes, 0) as downvotes,
            COALESCE(com.comment_count, 0) as comment_count FROM posts p JOIN users u ON p.user_id = u.id
            JOIN categories c ON p.category_id = c.id
            LEFT JOIN (SELECT post_id, COUNT(*) as upvotes FROM votes WHERE vote = 1 GROUP BY post_id) up ON p.id = up.post_id
            LEFT JOIN (SELECT post_id, COUNT(*) as downvotes FROM votes WHERE vote = -1 GROUP BY post_id) down ON p.id = down.post_id
            LEFT JOIN (SELECT post_id, COUNT(*) as comment_count FROM comments GROUP BY post_id) com ON p.id = com.post_id $whereClause
            ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
   
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $posts = [];
    
    while ($row = $result->fetch_assoc()) {
        $imagePath = $row['image_path'];
        if (strpos($imagePath, '/') === 0) $imagePath = substr($imagePath, 1);
        $fullImageUrl = BASE_URL . '/' . $imagePath;
        $userAvatar = $row['profile_pic'] ? BASE_URL . '/' .
        $row['profile_pic'] : null;
        $posts[] = ['id' => $row['id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'image_path' => $fullImageUrl,
                    'created_at' => $row['created_at'],
                    'user' => ['id' => $row['user_id'],
                               'username' => $row['username'],
                               'profile_pic' => $userAvatar
                    ],
                    'category' => ['id' => $row['category_id'],
                                   'name' => $row['category_name'],
                                   'slug' => $row['category_slug']
                    ],
                    'upvotes' => (int)$row['upvotes'],
                    'downvotes' => (int)$row['downvotes'],
                    'comments' => (int)$row['comment_count']
                   ];
    }

    // Pagination count
    $countSql = "SELECT COUNT(*) as total FROM posts p JOIN categories c ON p.category_id = c.id $whereClause";
    $countStmt = $conn->prepare($countSql);
    
    if (!empty($params) && count($params) > 2) {
        $countParams = array_slice($params, 0, -2);
        $countTypes = substr($types, 0, -2);
    
        if (!empty($countParams)) {
            $countStmt->bind_param($countTypes, ...$countParams);
        }
    }

    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($total / $limit);
    $stmt->close();
    $countStmt->close();
    $conn->close();
    jsonResponse(['posts' => $posts,
                  'pagination' => [
                  'current_page' => $page,
                  'total_pages' => $totalPages,
                  'total_posts' => $total,
                  'has_more' => $page < $totalPages
            ]
    ]);
?>
