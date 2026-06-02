<?php
// api/login.php - VERIFY THIS EXISTS
define('API_ACCESS', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// IMPORTANT: This should be 'login' not 'username' or 'identity'
$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, username, password, is_admin, banned FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }

    if ($user['banned']) {
        echo json_encode(['error' => 'Your account has been banned. Contact support.']);
        exit;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['is_admin'] = (int)$user['is_admin'];
    
    session_regenerate_id(true);

    echo json_encode(['success' => true]);
    exit;

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['error' => 'Internal server error']);
    exit;
}
?>