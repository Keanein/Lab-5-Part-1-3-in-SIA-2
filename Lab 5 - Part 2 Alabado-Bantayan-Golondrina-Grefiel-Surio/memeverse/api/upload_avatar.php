<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// FIXED: Initialized session engine state boundaries to allow avatar owner resolution
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'No valid file uploaded'], 400);
}

$file = $_FILES['avatar'];

// Check file size (2MB max for avatars)
if ($file['size'] > 2 * 1024 * 1024) {
    jsonResponse(['error' => 'Avatar must be less than 2MB'], 400);
}

// Check file type
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowedMimes)) {
    jsonResponse(['error' => 'Only JPG, PNG, and GIF images are allowed'], 400);
}

// Create avatar directory if it doesn't exist
$avatarDir = dirname(__DIR__) . '/assets/uploads/avatars/';
if (!is_dir($avatarDir)) {
    mkdir($avatarDir, 0755, true);
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
$destination = $avatarDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    jsonResponse(['error' => 'Failed to save avatar'], 500);
}

$avatar_path = 'assets/uploads/avatars/' . $filename;

try {
    // FIXED: Converted statement tracking over to the Guidebook's standardized PDO wrapper ($pdo)
    $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
    
    if ($stmt->execute([$avatar_path, $_SESSION['user_id']])) {
        jsonResponse([
            'success' => true,
            'avatar_url' => BASE_URL . '/' . $avatar_path, // UNTOUCHED: Kept exactly as requested
            'message' => 'Avatar updated successfully'
        ]);
    } else {
        // Delete the uploaded file if database update fails
        unlink($destination);
        jsonResponse(['error' => 'Database update failed'], 500);
    }
} catch (PDOException $e) {
    unlink($destination);
    jsonResponse(['error' => 'Database exception trace caught during upload processing.'], 500);
}
?>