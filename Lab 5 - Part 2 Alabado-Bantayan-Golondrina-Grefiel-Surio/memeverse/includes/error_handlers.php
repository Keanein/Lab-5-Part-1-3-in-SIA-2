<?php
// includes/error_handlers.php

// Ensure error logging is on
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error_log.txt');

/**
 * Handles database transaction exceptions cleanly
 * @param PDOException $e Exception tracking reference
 * @param string|null $query Optional SQL query that caused the error
 */
function handleDatabaseError($e, $query = null) {
    $message = "[DATABASE] " . $e->getMessage();
    if ($query) $message .= " | Query: " . $query;
    error_log($message);
    
    if (defined('API_ACCESS')) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
        exit;
    }
    
    renderErrorPage('database', 'Database Error', 'Something went wrong with the database. Our memes have been temporarily misplaced.', 500);
}

/**
 * Custom error handler for PHP errors
 */
function memeverseErrorHandler($errno, $errstr, $errfile, $errline) {
    $message = "[ERROR] $errstr in $errfile on line $errline";
    error_log($message);
    
    if (defined('API_ACCESS')) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
        exit;
    }
    
    renderErrorPage('error', 'Application Error', 'Something went wrong. Our team has been notified.', 500);
}

/**
 * Global exception catching interceptor mapping
 * @param Throwable $exception Catches standard and fatal engine faults
 */
function globalExceptionHandler($exception) {
    $message = "[EXCEPTION] " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    error_log($message);
    
    if (defined('API_ACCESS')) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
        exit;
    }
    
    renderErrorPage('parse', 'Unexpected Application Fault', 'An unhandled exception interrupted the execution stack pipeline.', 500);
}

/**
 * Fatal error shutdown handler
 */
function memeverseFatalErrorShutdown() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("[FATAL] " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
        
        if (!defined('API_ACCESS') && !headers_sent()) {
            $error_title = 'Fatal Error';
            $error_message = 'Something catastrophic happened. The memes are crying.';
            $error_code = 500;
            renderErrorPage('fatal', $error_title, $error_message, $error_code);
        }
    }
}

/**
 * Evaluates whether the active client route targets backend API modules
 * @return bool True if requesting JSON payloads, false otherwise
 */
function isApiRequest() {
    return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false || 
           (isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] === 'application/json');
}

/**
 * Displays a formatted structural error template UI
 */
function renderErrorPage($category, $title, $description, $code = 500) {
    http_response_code($code);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>MemeVerse - <?= htmlspecialchars($title) ?></title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <style>
            body { background: #121214; color: #e1e1e6; font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
            .error-card { max-width: 500px; padding: 2.5rem; background: #202024; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.2); text-align: center; border: 1px solid #29292e; }
            .error-icon { font-size: 4rem; color: #ff6b6b; margin-bottom: 1rem; }
            h1 { font-size: 1.75rem; font-weight: 700; margin-bottom: 1rem; color: #fff; }
            p { color: #a8a8b3; line-height: 1.6; margin-bottom: 1.5rem; }
            .btn-home { background: #ff6b6b; border: none; color: white; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-block; }
            .btn-home:hover { background: #fa5252; color: white; }
        </style>
    </head>
    <body>
        <div class="error-card text-center">
            <div class="error-icon">
                <i class="bi bi-<?= $category === 'database' ? 'database-fill-x' : 'bug' ?>"></i>
            </div>
            <h1><?= htmlspecialchars($title) ?></h1>
            <p><?= htmlspecialchars($description) ?></p>
            <a href="<?= SITE_URL ?? '/' ?>" class="btn-home"><i class="bi bi-house-door-fill me-2"></i>Back to Home Feed</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Bind interceptors to global runtime environments
set_error_handler('memeverseErrorHandler');
set_exception_handler('globalExceptionHandler');
register_shutdown_function('memeverseFatalErrorShutdown');
?>