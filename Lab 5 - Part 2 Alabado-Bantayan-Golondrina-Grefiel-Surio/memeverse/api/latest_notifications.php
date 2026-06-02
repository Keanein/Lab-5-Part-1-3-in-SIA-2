<?php
// api/latest_notifications.php - PHASE 9 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['notifications' => [], 'unread_count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get unread count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_count = (int)$stmt->fetchColumn();

// Get latest 5 notifications with actor details
$stmt = $pdo->prepare("
    SELECT n.*, u.username, u.avatar, u.nickname,
           p.title as post_title,
           c.comment_text as comment_preview,
           (SELECT vote_value FROM votes WHERE post_id = n.source_id AND user_id = n.actor_id LIMIT 1) as vote_value
    FROM notifications n
    JOIN users u ON n.actor_id = u.id
    LEFT JOIN posts p ON n.source_id = p.id AND n.type IN ('comment', 'vote')
    LEFT JOIN comments c ON n.source_id = c.id AND n.type = 'reply'
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$raw = $stmt->fetchAll();

$notifications = [];

foreach ($raw as $n) {
    $item = [
        'id' => $n['id'],
        'type' => $n['type'],
        'is_read' => (bool)$n['is_read'],
        'created_at' => $n['created_at'],
        'username' => $n['nickname'] ?: $n['username'],
        'avatar' => getUserAvatar($n, 36),
        'time' => timeAgo($n['created_at']),
        'link' => '#',
        'icon' => 'bell',
        'message' => 'interacted with you'
    ];
    
    switch ($n['type']) {
        case 'message':
            $item['message'] = 'sent you a message';
            $item['link'] = "messages.php?with=" . $n['actor_id'];
            $item['icon'] = 'envelope';
            break;
        case 'vote':
            if ($n['vote_value'] == 1) {
                $item['message'] = 'upvoted your post';
                $item['icon'] = 'arrow-up-circle';
            } elseif ($n['vote_value'] == -1) {
                $item['message'] = 'downvoted your post';
                $item['icon'] = 'arrow-down-circle';
            } else {
                $item['message'] = 'voted on your post';
                $item['icon'] = 'arrow-up';
            }
            $item['link'] = "post.php?id=" . $n['source_id'];
            break;
        case 'comment':
            $item['message'] = 'commented on your post';
            $item['link'] = "post.php?id=" . $n['source_id'];
            $item['icon'] = 'chat-dots';
            break;
        case 'reply':
            $item['message'] = 'replied to your comment';
            $item['link'] = "post.php?id=" . $n['source_id'];
            $item['icon'] = 'reply';
            break;
        case 'follow':
            $item['message'] = 'started following you';
            $item['link'] = "profile.php?id=" . $n['actor_id'];
            $item['icon'] = 'person-plus';
            break;
    }
    
    $notifications[] = $item;
}

echo json_encode([
    'notifications' => $notifications,
    'unread_count' => $unread_count
]);
exit;
?>