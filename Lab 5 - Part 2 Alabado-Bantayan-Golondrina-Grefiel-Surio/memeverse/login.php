<?php
// login.php - FIXED VERSION
require_once __DIR__ . '/includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MemeVerse - Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/style.css">
    <style>
        body { background: #121214; color: #e1e1e6; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .auth-card { width: 100%; max-width: 450px; background: #202024; border: 1px solid #29292e; border-radius: 8px; }
        .form-control { background: #121214; border: 1px solid #29292e; color: #fff; }
        .form-control:focus { background: #121214; border-color: #ff6b6b; color: #fff; box-shadow: none; }
    </style>
</head>
<body>

<div class="card auth-card shadow p-4">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-danger"><i class="bi bi-incognito"></i> MemeVerse</h2>
        <p class="text-muted small">Enter your credentials to continue</p>
    </div>

    <div id="alertBox" class="alert d-none" role="alert"></div>

    <form id="loginForm">
        <div class="mb-3">
            <label class="form-label small text-uppercase">Username or Email</label>
            <input type="text" name="login" id="login" class="form-control form-control-sm" required>
        </div>
        <div class="mb-4">
            <label class="form-label small text-uppercase">Password</label>
            <input type="password" name="password" id="password" class="form-control form-control-sm" required>
        </div>
        <div class="mb-3 text-end">
            <a href="forgot_password.php" class="text-danger small text-decoration-none">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-danger w-100 fw-bold mb-3">Login</button>
    </form>

    <div class="text-center mt-2">
        <span class="small text-muted">Don't have an account? <a href="register.php" class="text-danger text-decoration-none">Sign Up</a></span>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const alertBox = document.getElementById('alertBox');
    const login = document.getElementById('login').value.trim();
    const password = document.getElementById('password').value;
    
    alertBox.className = 'alert d-none';
    
    // Client-side validation
    if (!login || !password) {
        alertBox.className = 'alert alert-danger d-block';
        alertBox.textContent = 'All fields are required';
        return;
    }
    
    const formData = new URLSearchParams();
    formData.append('login', login);
    formData.append('password', password);
    
    try {
        const response = await fetch('<?= SITE_URL ?>/api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alertBox.className = 'alert alert-success d-block';
            alertBox.textContent = 'Login successful! Redirecting...';
            setTimeout(() => {
                window.location.href = '<?= SITE_URL ?>index.php';
            }, 1000);
        } else {
            alertBox.className = 'alert alert-danger d-block';
            alertBox.textContent = data.error || 'Invalid username or password';
        }
    } catch (error) {
        alertBox.className = 'alert alert-danger d-block';
        alertBox.textContent = 'Network error. Please try again.';
        console.error('Login error:', error);
    }
});
</script>

</body>
</html>