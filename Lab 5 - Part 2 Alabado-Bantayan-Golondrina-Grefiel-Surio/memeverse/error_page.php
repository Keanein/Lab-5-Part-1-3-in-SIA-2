<?php
// error_page.php - PHASE 12 PROFESSOR'S VERSION
$error_title = $error_title ?? 'Something Went Wrong';
$error_message = $error_message ?? 'An unexpected error occurred. Our team has been notified.';
$error_code = $error_code ?? 500;
http_response_code($error_code);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($error_title) ?> - MemeVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #121214; color: #e1e1e6; font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .error-card { max-width: 500px; padding: 2rem; background: #202024; border-radius: 12px; text-align: center; border: 1px solid #29292e; }
        .error-icon { font-size: 4rem; color: #ff6b6b; margin-bottom: 1rem; }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .error-code { font-size: 0.85rem; color: #8b949e; margin-bottom: 1rem; }
        .btn-home { background: #ff6b6b; border: none; color: white; padding: 0.5rem 1.5rem; border-radius: 6px; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon"><i class="bi bi-bug"></i></div>
        <h1><?= htmlspecialchars($error_title) ?></h1>
        <div class="error-code">Error <?= $error_code ?></div>
        <p class="text-muted"><?= htmlspecialchars($error_message) ?></p>
        <a href="index.php" class="btn-home"><i class="bi bi-house-door-fill me-2"></i>Back to Home</a>
    </div>
</body>
</html>