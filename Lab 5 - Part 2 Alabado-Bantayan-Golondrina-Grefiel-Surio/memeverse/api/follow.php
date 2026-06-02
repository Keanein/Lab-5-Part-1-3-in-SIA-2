<?php
// api/follow.php - PHASE 5 PROFESSOR'S VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login first']);
    exit;
}

$user_id = $_SESSION['user_id'];
$target_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$target_id || $target_id == $user_id) {
    echo json_encode(['error' => 'Invalid user']);
    exit;
}

if ($action === 'follow') {
    // Check if already following
    $stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$user_id, $target_id]);
    
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $target_id]);
        
        // Create notification for the followed user
        createNotification($pdo, $target_id, 'follow', $target_id, $user_id);
        
        echo json_encode(['success' => true, 'action' => 'follow']);
    } else {
        echo json_encode(['error' => 'Already following']);
    }
    
} elseif ($action === 'unfollow') {
    $stmt = $pdo->prepare("DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$user_id, $target_id]);
    echo json_encode(['success' => true, 'action' => 'unfollow']);
    
} else {
    echo json_encode(['error' => 'Invalid action']);
}
exit;
?>