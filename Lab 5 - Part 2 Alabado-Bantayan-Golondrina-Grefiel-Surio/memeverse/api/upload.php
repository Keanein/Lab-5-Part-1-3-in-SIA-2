<?php
// api/upload.php - FIXED VERSION
define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

if ($category_id <= 0) {
    echo json_encode(['error' => 'Please select a category']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Please select an image to upload']);
    exit;
}

$file = $_FILES['image'];

// Check file size (5MB max)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['error' => 'File must be less than 5MB']);
    exit;
}

// Validate image type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowedTypes)) {
    echo json_encode(['error' => 'Only JPG, PNG, GIF, and WEBP images are allowed']);
    exit;
}

// Create uploads directory if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '_' . time() . '.' . $ext;
$destination = UPLOAD_DIR . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['error' => 'Failed to save uploaded file']);
    exit;
}

$image_path = 'uploads/' . $filename;

try {
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, category_id, title, description, image_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $category_id, $title, $description, $image_path]);
    
    echo json_encode(['success' => true, 'post_id' => $pdo->lastInsertId()]);
    exit;
} catch (PDOException $e) {
    // Delete the uploaded file if database insert fails
    if (file_exists($destination)) {
        unlink($destination);
    }
    error_log("Upload error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error. Please try again.']);
    exit;
}
?>