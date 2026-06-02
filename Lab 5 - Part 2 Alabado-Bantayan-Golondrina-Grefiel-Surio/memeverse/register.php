<?php
// register.php - FIXED (added confirm password field)
require_once __DIR__ . '/includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MemeVerse - Join the Framework</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/style.css">
    <style>
        body { background: #121214; color: #e1e1e6; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .auth-card { width: 100%; max-width: 450px; background: #202024; border: 1px solid #29292e; border-radius: 8px; }
        .form-control { background: #121214; border: 1px solid #29292e; color: #fff; }
        .form-control:focus { background: #121214; border-color: #ff6b6b; color: #fff; box-shadow: none; }
        .password-strength { font-size: 0.7rem; margin-top: 0.25rem; }
    </style>
</head>
<body>

<div class="card auth-card shadow p-4">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-danger"><i class="bi bi-incognito"></i> MemeVerse</h2>
        <p class="text-muted small">Construct your digital creator portfolio identity</p>
    </div>

    <div id="alertBox" class="alert d-none" role="alert"></div>

    <form id="registerForm">
        <div class="mb-3">
            <label class="form-label small text-uppercase">Username</label>
            <input type="text" name="username" class="form-control form-control-sm" required autocomplete="off">
        </div>
        <div class="mb-3">
            <label class="form-label small text-uppercase">Email Address</label>
            <input type="email" name="email" class="form-control form-control-sm" required>
        </div>
        <div class="mb-3">
            <label class="form-label small text-uppercase">Display Nickname</label>
            <input type="text" name="nickname" class="form-control form-control-sm" placeholder="Optional identifier">
        </div>
        <div class="mb-3">
            <label class="form-label small text-uppercase">Password</label>
            <input type="password" name="password" id="password" class="form-control form-control-sm" required minlength="6">
            <div id="passwordStrength" class="password-strength"></div>
        </div>
        <div class="mb-4">
            <label class="form-label small text-uppercase">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control form-control-sm" required>
        </div>
        <button type="submit" class="btn btn-danger w-100 fw-bold mb-3">Initialize Account</button>
    </form>

    <div class="text-center mt-2">
        <span class="small text-muted">Already configured? <a href="<?= SITE_URL ?>/login.php" class="text-danger text-decoration-none">Sign In</a></span>
    </div>
</div>

<script>
// Password strength meter
const passwordInput = document.getElementById('password');
const strengthDiv = document.getElementById('passwordStrength');

if (passwordInput && strengthDiv) {
    passwordInput.addEventListener('input', function() {
        const val = this.value;
        if (val.length === 0) {
            strengthDiv.innerHTML = '';
            return;
        }
        if (val.length < 6) {
            strengthDiv.innerHTML = '⚠️ Weak (minimum 6 characters)';
            strengthDiv.style.color = '#ef476f';
        } else if (val.length < 10) {
            strengthDiv.innerHTML = '👍 Medium';
            strengthDiv.style.color = '#ffd166';
        } else {
            strengthDiv.innerHTML = '✅ Strong';
            strengthDiv.style.color = '#06d6a0';
        }
    });
}

// Password match validation
const confirmInput = document.getElementById('confirm_password');
const passwordField = document.getElementById('password');

if (confirmInput && passwordField) {
    confirmInput.addEventListener('input', function() {
        if (this.value !== passwordField.value) {
            this.style.borderColor = '#ef476f';
        } else {
            this.style.borderColor = '#06d6a0';
        }
    });
}

// Form submission
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const alertBox = document.getElementById('alertBox');
    const formData = new FormData(this);
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    alertBox.className = 'alert d-none';
    
    // Client-side password match check
    if (password !== confirm) {
        alertBox.className = 'alert alert-danger d-block';
        alertBox.textContent = 'Passwords do not match';
        return;
    }

    try {
        const response = await fetch('<?= SITE_URL ?>/api/register.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (response.ok && data.success) {
            alertBox.className = 'alert alert-success d-block';
            alertBox.textContent = 'Account created successfully! Redirecting to login...';
            this.reset();
            setTimeout(() => { window.location.href = '<?= SITE_URL ?>/login.php'; }, 2000);
        } else {
            alertBox.className = 'alert alert-danger d-block';
            alertBox.textContent = data.error || 'Registration failed. Please try again.';
        }
    } catch (error) {
        alertBox.className = 'alert alert-danger d-block';
        alertBox.textContent = 'Network error. Please check your connection.';
    }
});
</script>
</body>
</html>