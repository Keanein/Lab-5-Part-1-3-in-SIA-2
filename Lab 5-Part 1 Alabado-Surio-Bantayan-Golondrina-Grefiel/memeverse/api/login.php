<?php
    require_once dirname(__DIR__) . '/includes/config.php';
    require_once dirname(__DIR__) . '/includes/functions.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        jsonResponse(['error' => 'Invalid JSON'], 400);
    }

    $login = trim($input['login'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        jsonResponse(['error' => 'Login and password required'], 400);
    }
    
    $stmt = $conn->prepare("SELECT id, username, email, password
    FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        jsonResponse(['error' => 'Invalid credentials'], 401);
    }

    $user = $result->fetch_assoc();
    
    if (!password_verify($password, $user['password'])) {
        jsonResponse(['error' => 'Invalid credentials'], 401);
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    unset($user['password']);
    jsonResponse(['success' => true, 'message' => 'Login successful', 'user' => $user]);
?>