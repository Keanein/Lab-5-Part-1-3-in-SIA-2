<?php
// admin/api/check_admin.php - PHASE 11 PROFESSOR'S VERSION

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify admin privileges using database check
if (!isLoggedIn() || !isAdmin($pdo)) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['error' => 'Access Denied: Admin privileges required.']);
        exit;
    } else {
        redirect('../../access_denied.php');
    }
}
?>