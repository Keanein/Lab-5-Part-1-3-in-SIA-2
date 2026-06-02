<?php
// includes/functions.php
require_once __DIR__ . '/config.php';

// ========== AUTHENTICATION ==========

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT id, username, nickname, avatar, bio, email, is_admin, banned FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function isAdmin($pdo) {
    if (!isLoggedIn()) return false;
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (bool) $stmt->fetchColumn();
}

function requireLogin() {
    if (!isLoggedIn()) {
        if (defined('API_ACCESS')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Please login first']);
            exit;
        } else {
            redirect('login.php');
        }
    }
}

// ========== AVATAR ==========

function getUserAvatar($user, $size = 40) {
    if (!empty($user['avatar']) && file_exists(AVATAR_DIR . $user['avatar'])) {
        return SITE_URL . 'avatars/' . $user['avatar'];
    }
    $name = urlencode($user['nickname'] ?? $user['username']);
    return "https://ui-avatars.com/api/?background=ff6b6b&color=fff&bold=true&size={$size}&name={$name}";
}

// ========== VOTING ==========

function getVoteScore($pdo, $post_id) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(vote_value), 0) FROM votes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    return (int) $stmt->fetchColumn();
}

function getUserVote($pdo, $post_id, $user_id) {
    if (!$user_id) return 0;
    $stmt = $pdo->prepare("SELECT vote_value FROM votes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $vote = $stmt->fetch();
    return $vote ? (int) $vote['vote_value'] : 0;
}

// ========== COMMENTS ==========

function getCommentCount($pdo, $post_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
    $stmt->execute([$post_id]);
    return (int) $stmt->fetchColumn();
}

// ========== CATEGORIES ==========

function getCategoryEmoji($slug) {
    $emojis = [
        'funny' => '😂', 'animals' => '🐾', 'music' => '🎵', 'movies' => '🎬',
        'gaming' => '🎮', 'food' => '🍕', 'travel' => '✈️', 'awesome' => '✨'
    ];
    return $emojis[$slug] ?? '🏷️';
}

// ========== TIME ==========

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j', $time);
}

// ========== SECURITY ==========

function escape($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// ========== REDIRECT ==========

function redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . SITE_URL . $url);
        exit;
    } else {
        echo '<script>window.location.href="' . SITE_URL . $url . '";</script>';
        exit;
    }
}

// ========== VISITS (ANALYTICS) ==========

function logVisit($pdo, $page) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO visits (ip, page, user_id) VALUES (?, ?, ?)");
    $stmt->execute([$ip, $page, $user_id]);
}

function getDailyVisits($pdo, $days = 7) {
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM visits
        WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$days]);
    return $stmt->fetchAll();
}

// ========== NOTIFICATIONS ==========

function createNotification($pdo, $user_id, $type, $source_id, $actor_id) {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, source_id, actor_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $type, $source_id, $actor_id]);
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
    }
}

// ========== MESSAGES ==========

function getUnreadMessageCount($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}
?>