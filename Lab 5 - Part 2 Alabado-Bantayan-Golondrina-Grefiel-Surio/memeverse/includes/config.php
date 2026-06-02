<?php
// includes/config.php - PHASE 13 PRODUCTION HARDENED ARCHITECTURE

// Start session with security hardening
if (session_status() === PHP_SESSION_NONE) {
    // Phase 12/13 Hardening: Enforce secure cookie attributes
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Only enable secure flag if HTTPS is being used
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

// 1. Global Exception/Error Masking Configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);         // Suppress error display to users
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error_log.txt');

// 2. Database Configuration Parameters
define('DB_HOST', 'localhost');
define('DB_NAME', 'memeverse');
define('DB_USER', 'root');
define('DB_PASS', 'password');

// 3. Auto-detect Site URL (from professor's Phase 2)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$uri = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

define('SITE_URL', $protocol . '://' . $host . $uri . '/');
define('BASE_URL', $protocol . '://' . $host . $uri . '/');

// 4. Upload Storage Configurations
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('AVATAR_DIR', __DIR__ . '/../avatars/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('DEFAULT_AVATAR', SITE_URL . 'avatars/default.png');

// 5. Email Gateway Parameters
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@memeverse.com');
define('SMTP_FROM_NAME', 'MemeVerse');

// 6. Initialize Hardened PDO Connection Object
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    
    if (file_exists(__DIR__ . '/error_handlers.php')) {
        require_once __DIR__ . '/error_handlers.php';
        handleDatabaseError($e);
    } else {
        die("An infrastructure failure intercepted content rendering processes.");
    }
}

// Custom logging function for application-level errors (from professor's Phase 2)
function logError($msg, $ctx = []) {
    $log = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    if (!empty($ctx)) {
        $log .= ' | ' . json_encode($ctx);
    }
    error_log($log . PHP_EOL, 3, __DIR__ . '/../error_log.txt');
}
?>