<?php
// reset_password.php - PHASE 3 PROFESSOR'S VERSION
require_once 'includes/header.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    redirect('index.php');
}

// Verify token exists and is valid
$stmt = $pdo->prepare("
    SELECT id FROM password_resets
    WHERE token = ? AND used = 0 AND expires_at > NOW()
");
$stmt->execute([$token]);

if (!$stmt->fetch()) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Invalid or expired reset link.</div></div>";
    require_once 'includes/footer.php';
    exit;
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Set New Password</h4>
                </div>
                <div class="card-body">
                    <div id="message" class="alert d-none"></div>
                    <form id="reset-form">
                        <input type="hidden" id="token" value="<?= htmlspecialchars($token) ?>">
                        <div class="mb-3">
                            <input type="password" id="password" class="form-control" placeholder="New Password" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" id="confirm" class="form-control" placeholder="Confirm Password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <a href="login.php">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('reset-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm').value;
    const token = document.getElementById('token').value;
    const messageDiv = document.getElementById('message');
    
    if (password !== confirm) {
        messageDiv.classList.remove('d-none');
        messageDiv.className = 'alert alert-danger';
        messageDiv.innerHTML = 'Passwords do not match.';
        return;
    }
    
    if (password.length < 6) {
        messageDiv.classList.remove('d-none');
        messageDiv.className = 'alert alert-danger';
        messageDiv.innerHTML = 'Password must be at least 6 characters.';
        return;
    }
    
    try {
        const res = await fetch('api/reset_password.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({token: token, password: password})
        });
        const data = await res.json();
        
        messageDiv.classList.remove('d-none');
        if (data.success) {
            messageDiv.className = 'alert alert-success';
            messageDiv.innerHTML = 'Password reset successful! Redirecting to login...';
            setTimeout(() => window.location.href = 'login.php', 2000);
        } else {
            messageDiv.className = 'alert alert-danger';
            messageDiv.innerHTML = data.error || 'Reset failed.';
        }
    } catch (err) {
        messageDiv.classList.remove('d-none');
        messageDiv.className = 'alert alert-danger';
        messageDiv.innerHTML = 'Network error.';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>