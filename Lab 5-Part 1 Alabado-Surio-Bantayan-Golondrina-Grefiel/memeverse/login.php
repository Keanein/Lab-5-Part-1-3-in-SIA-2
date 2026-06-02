<?php
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    if (isLoggedIn()) redirect('index.php');
?>

<?php require_once 'includes/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="form-card">
            <h3><i class="bi bi-box-arrow-in-right"></i>Welcome back</h3>
            <div id="loginAlert" class="alert d-none"></div>
            <form id="loginForm">
                <div class="mb-3">
                    <label class="form-label">Username orEmail</label>
                    <input type="text" class="form-control" name="login" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>    
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            <p class="mt-3 text-center">Don't have an account?<a href="register.php">Register</a></p>
        </div>
    </div>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const alertDiv = document.getElementById('loginAlert');
        const formData = {
            login: form.login.value,
            password: form.password.value
        };

        alertDiv.classList.add('d-none');
        try {
            const response = await fetch('<?php echo BASE_URL;?>/api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const result = await response.json();
            
            if (response.ok) {
            alertDiv.classList.remove('d-none', 'alert-danger');
            alertDiv.classList.add('alert-success');
            alertDiv.textContent = 'Login successful! Redirecting...';
            setTimeout(() => window.location.href = '<?php echo BASE_URL; ?>/index.php', 1000);
            } else {
                alertDiv.classList.remove('d-none', 'alert-success');
                alertDiv.classList.add('alert-danger');
                alertDiv.textContent = result.error || 'Login failed';
                }
        } catch (error) {
            alertDiv.classList.remove('d-none', 'alert-success');
            alertDiv.classList.add('alert-danger');
            alertDiv.textContent = 'Network error';
        }
    });
</script>
<?php require_once 'includes/footer.php'; ?>