<?php
// api/register.php - FIXED VERSION

define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Only allow HTTP POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Extract POST data
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$nickname = trim($_POST['nickname'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

// Validation - Check required fields
if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

// Check if passwords match
if ($password !== $confirm) {
    echo json_encode(['error' => 'Passwords do not match']);
    exit;
}

// Check password length
if (strlen($password) < 6) {
    echo json_encode(['error' => 'Password must be at least 6 characters']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

// Validate username (only letters, numbers, underscores)
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['error' => 'Username can only contain letters, numbers, and underscores']);
    exit;
}

try {
    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Username or email already taken']);
        exit;
    }
    
    // Hash password and insert user
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    // Use nickname if provided, otherwise use username
    $finalNickname = !empty($nickname) ? $nickname : $username;
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, nickname, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$username, $email, $hashed, $finalNickname]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully!'
    ]);
    exit;
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error. Please try again later.']);
    exit;
}
?>