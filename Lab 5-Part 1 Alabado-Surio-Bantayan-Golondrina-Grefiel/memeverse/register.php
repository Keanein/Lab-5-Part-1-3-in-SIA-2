<?php
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    if (isLoggedIn()) redirect('index.php');
?>

<?php require_once 'includes/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="form-card">
            <h3><i class="bi bi-person-plus"></i> Create account</h3>
            <div id="registerAlert" class="alert d-none"></div>
            <form id="registerForm">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Sign up</button>
            </form>

            <p class="mt-3 text-center">Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
</div>
<script>
    document.getElementById('registerForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const alertDiv = document.getElementById('registerAlert');
        const formData = {
            username: form.username.value,
            email: form.email.value,
            password: form.password.value,
            confirm_password: form.confirm_password.value
        };

        if (formData.password !== formData.confirm_password) {
            alertDiv.classList.remove('d-none', 'alert-success');
            alertDiv.classList.add('alert-danger');
            alertDiv.textContent = 'Passwords do not match';
            return;
        }

        alertDiv.classList.add('d-none');

        try {
            const response = await fetch('<?php echo BASE_URL;?>/api/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const result = await response.json();
            if (response.ok) {
                alertDiv.classList.remove('d-none', 'alert-danger');
                alertDiv.classList.add('alert-success');
                alertDiv.textContent = 'Registration successful! Redirecting...';
                setTimeout(() => window.location.href = '<?php echo BASE_URL; ?>/login.php', 1500);
            } else {
                alertDiv.classList.remove('d-none', 'alert-success');
                alertDiv.classList.add('alert-danger');
                alertDiv.textContent = result.error || 'Registration failed';
            }
        } catch (error) {
            alertDiv.classList.remove('d-none', 'alert-success');
            alertDiv.classList.add('alert-danger');
            alertDiv.textContent = 'Network error';
        }
    });
</script>
