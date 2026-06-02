<?php
// api/update_profile.php - PHASE 5 PROFESSOR'S VERSION

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
$nickname = trim($_POST['nickname'] ?? '');
$bio = trim($_POST['bio'] ?? '');

// Update nickname and bio
$stmt = $pdo->prepare("UPDATE users SET nickname = ?, bio = ? WHERE id = ?");
$stmt->execute([$nickname, $bio, $user_id]);

// Handle avatar upload
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['avatar'];
    
    // Validate image using finfo_open()
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed)) {
        echo json_encode(['error' => 'Only JPG, PNG, GIF, WEBP allowed']);
        exit;
    }
    
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['error' => 'Avatar must be < 2MB']);
        exit;
    }
    
    // Ensure avatars directory exists
    if (!is_dir(AVATAR_DIR)) {
        mkdir(AVATAR_DIR, 0755, true);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $destination = AVATAR_DIR . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Delete old avatar
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $old = $stmt->fetch();
        if ($old['avatar'] && file_exists(AVATAR_DIR . $old['avatar'])) {
            unlink(AVATAR_DIR . $old['avatar']);
        }
        
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([$filename, $user_id]);
    }
}

echo json_encode(['success' => true]);
exit;
?>