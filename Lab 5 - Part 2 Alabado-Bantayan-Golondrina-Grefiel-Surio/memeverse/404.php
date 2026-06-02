<?php
// 404.php - PHASE 12 PROFESSOR'S VERSION
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 - Page Not Found - MemeVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #121214; color: #e1e1e6; font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .error-card { max-width: 500px; padding: 2rem; background: #202024; border-radius: 12px; text-align: center; border: 1px solid #29292e; }
        .error-icon { font-size: 4rem; color: #ff6b6b; margin-bottom: 1rem; }
        h1 { font-size: 3rem; font-weight: 700; margin-bottom: 0.5rem; }
        .btn-home { background: #ff6b6b; border: none; color: white; padding: 0.5rem 1.5rem; border-radius: 6px; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon"><i class="bi bi-compass"></i></div>
        <h1>404</h1>
        <p class="text-muted">The page you're looking for doesn't exist or has been moved.</p>
        <a href="index.php" class="btn-home"><i class="bi bi-house-door-fill me-2"></i>Back to Home</a>
    </div>
</body>
</html>