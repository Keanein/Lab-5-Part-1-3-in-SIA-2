<?php
// notifications.php - PHASE 9 PROFESSOR'S VERSION

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch all notifications
$stmt = $pdo->prepare("
    SELECT n.*, u.username, u.avatar, u.nickname,
           p.title as post_title,
           c.comment_text as comment_preview
    FROM notifications n
    JOIN users u ON n.actor_id = u.id
    LEFT JOIN posts p ON n.source_id = p.id AND n.type IN ('comment', 'vote')
    LEFT JOIN comments c ON n.source_id = c.id AND n.type = 'reply'
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Mark all as read
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h2><i class="bi bi-bell"></i> Notifications</h2>
            <span class="badge bg-primary rounded-pill"><?= count($notifications) ?></span>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="empty-state text-center py-5">
                <i class="bi bi-bell-slash fs-1 text-muted"></i>
                <p class="mt-3">No notifications yet</p>
                <p class="text-muted small">When people interact with your posts, you'll see them here</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $n):
                    $avatar_url = getUserAvatar($n, 48);
                    $username = escape($n['nickname'] ?: $n['username']);
                    $time = timeAgo($n['created_at']);
                    $message = '';
                    $link = '#';
                    $icon = 'bell';
                    
                    switch ($n['type']) {
                        case 'message':
                            $message = 'sent you a message';
                            $link = "messages.php?with=" . $n['actor_id'];
                            $icon = 'envelope';
                            break;
                        case 'vote':
                            $message = 'voted on your post';
                            $link = "post.php?id=" . $n['source_id'];
                            $icon = 'arrow-up';
                            break;
                        case 'comment':
                            $message = 'commented on your post';
                            $link = "post.php?id=" . $n['source_id'];
                            $icon = 'chat-dots';
                            break;
                        case 'reply':
                            $message = 'replied to your comment';
                            $link = "post.php?id=" . $n['source_id'];
                            $icon = 'reply';
                            break;
                        case 'follow':
                            $message = 'started following you';
                            $link = "profile.php?id=" . $n['actor_id'];
                            $icon = 'person-plus';
                            break;
                    }
                ?>
                    <div class="notification-item <?= !$n['is_read'] ? 'unread' : '' ?>">
                        <div class="notification-icon">
                            <i class="bi bi-<?= $icon ?>"></i>
                        </div>
                        <img src="<?= $avatar_url ?>" class="notification-avatar" alt="Avatar">
                        <div class="notification-content">
                            <a href="<?= $link ?>" class="notification-link">
                                <div class="notification-message">
                                    <strong><?= $username ?></strong> <?= $message ?>
                                </div>
                                <div class="notification-time">
                                    <i class="bi bi-clock"></i> <?= $time ?>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.notification-item {
    background: var(--bg-card);
    border-radius: 20px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    border: 1px solid var(--border-color);
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.notification-item:hover {
    background: var(--bg-primary);
}

.notification-item.unread {
    border-left: 3px solid var(--primary);
    background: rgba(255, 107, 107, 0.05);
}

.notification-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.notification-icon {
    width: 40px;
    text-align: center;
    font-size: 1.4rem;
    color: var(--primary);
}

.notification-content {
    flex: 1;
}

.notification-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.notification-message strong {
    color: var(--primary);
}

.notification-time {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}
</style>

<?php require_once 'includes/footer.php'; ?>