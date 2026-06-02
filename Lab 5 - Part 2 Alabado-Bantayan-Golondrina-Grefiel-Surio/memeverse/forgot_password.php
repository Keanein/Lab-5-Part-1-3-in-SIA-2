<?php
// forgot_password.php - PHASE 3 PROFESSOR'S VERSION
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Reset Password</h4>
                </div>
                <div class="card-body">
                    <div id="message" class="alert d-none"></div>
                    <form id="forgot-form">
                        <div class="mb-3">
                            <input type="email" id="email" class="form-control" placeholder="Your email address" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
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
document.getElementById('forgot-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const messageDiv = document.getElementById('message');
    
    try {
        const res = await fetch('api/forgot_password.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({email: email})
        });
        const data = await res.json();
        
        messageDiv.classList.remove('d-none');
        if (data.success) {
            messageDiv.className = 'alert alert-success';
            messageDiv.innerHTML = 'If the email exists, we\'ve sent a reset link.';
        } else {
            messageDiv.className = 'alert alert-danger';
            messageDiv.innerHTML = data.error || 'Something went wrong.';
        }
    } catch (err) {
        messageDiv.classList.remove('d-none');
        messageDiv.className = 'alert alert-danger';
        messageDiv.innerHTML = 'Network error.';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>