<?php
// includes/header.php - PHASE 4 PROFESSOR'S VERSION

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Get current user and check banned status
$current_user = getCurrentUser($pdo);
$unread_count = 0;
$notifications = [];

if ($current_user && !empty($current_user['banned'])) {
    session_destroy();
    redirect('login.php?banned=1');
}

// Log visit for analytics
logVisit($pdo, basename($_SERVER['PHP_SELF']));

if ($current_user) {
    // Get unread notification count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$current_user['id']]);
    $unread_count = $stmt->fetchColumn();
    
    // Get latest 5 notifications
    $stmt = $pdo->prepare("
        SELECT n.*, u.username, u.avatar, u.nickname
        FROM notifications n
        JOIN users u ON n.actor_id = u.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$current_user['id']]);
    $notifications = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>MemeVerse - Share the Laughter</title>
    
    <!-- Dark mode preload (prevents flash) -->
    <script>
    (function() {
        const savedTheme = localStorage.getItem('memeverse_theme');
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
            document.body.classList.add('dark');
        }
    })();
    </script>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <button class="hamburger-menu" id="hamburgerMenu">☰</button>
        <a class="navbar-brand" href="<?= SITE_URL ?>">🎭 <span>MemeVerse</span></a>
        
        <div class="search-wrapper">
            <input type="text" id="navbar-search-input" placeholder="Search memes or users...">
            <div id="navbar-search-results"></div>
        </div>
        
        <div class="nav-right">
            <?php if ($current_user): ?>
            <!-- Notifications Dropdown -->
            <div class="dropdown notification-dropdown">
                <button class="nav-icon notification-icon dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-bell"></i>
                    <span id="unread-notification-count" class="notification-badge"><?= $unread_count > 0 ? $unread_count : '' ?></span>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-menu">
                    <div class="notification-header">
                        <h6>Notifications</h6>
                    </div>
                    <div class="notification-list">
                        <?php if (empty($notifications)): ?>
                            <div class="notification-empty">No notifications yet</div>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                                <a href="#" class="notification-item">
                                    <img src="<?= getUserAvatar($n, 36) ?>" class="notification-avatar-sm">
                                    <div class="notification-content">
                                        <div class="notification-message">
                                            <strong><?= escape($n['username']) ?></strong> interacted with you
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="notification-footer">
                        <a href="notifications.php">View all notifications</a>
                    </div>
                </div>
            </div>
            
            <!-- Messages Icon -->
            <a href="messages.php" class="nav-icon position-relative">
                <i class="bi bi-envelope"></i>
                <span id="unread-message-count" class="notification-badge"></span>
            </a>
            
            <!-- Dark Mode Toggle -->
            <button class="nav-icon dark-mode-btn" id="darkModeToggle">
                <i class="bi bi-moon-stars-fill"></i>
            </button>
            
            <!-- User Dropdown -->
            <div class="dropdown">
                <button class="dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="<?= getUserAvatar($current_user, 40) ?>" class="user-avatar">
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> My Profile</a></li>
                    <li><a class="dropdown-item" href="upload.php"><i class="bi bi-cloud-upload"></i> Upload Meme</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal"><i class="bi bi-gear"></i> Settings</a></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
            
            <?php else: ?>
            <a href="login.php" class="btn-login">Login</a>
            <a href="register.php" class="btn-register">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Mobile Sidebar -->
<div class="mobile-sidebar-overlay" id="mobileSidebarOverlay"></div>
<div class="mobile-sidebar" id="mobileSidebar">
    <div class="mobile-sidebar-header">
        <h5>Menu</h5>
        <button class="close-sidebar" id="closeSidebar">✖</button>
    </div>
    <div class="mobile-sidebar-content">
        <?php include 'sidebar.php'; ?>
    </div>
</div>

<main class="main-content">
    <div class="container">
        <div class="row">

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-gear-fill"></i> Account Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#emailTab">Change Email</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#passwordTab">Change Password</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="emailTab">
                        <form id="changeEmailForm">
                            <div class="mb-3">
                                <label>New Email</label>
                                <input type="email" name="new_email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Confirm Email</label>
                                <input type="email" name="confirm_email" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Update Email</button>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="passwordTab">
                        <form id="changePasswordForm">
                            <div class="mb-3">
                                <label>Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>