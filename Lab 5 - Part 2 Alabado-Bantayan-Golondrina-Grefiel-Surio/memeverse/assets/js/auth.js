// assets/js/auth.js - PHASE 4 PROFESSOR'S VERSION

// ========== PASSWORD TOGGLE ==========
document.querySelectorAll('.password-toggle').forEach(toggle => {
    toggle.addEventListener('click', function() {
        const input = this.parentElement.querySelector('input');
        if (input) {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        }
    });
});

// ========== PASSWORD STRENGTH METER ==========
const passwordInput = document.getElementById('password');
const strengthDiv = document.getElementById('passwordStrength');

if (passwordInput && strengthDiv) {
    passwordInput.addEventListener('input', function() {
        const val = this.value;
        
        if (val.length === 0) {
            strengthDiv.innerHTML = '';
            return;
        }
        
        let strength = '';
        let color = '';
        
        if (val.length < 6) {
            strength = '⚠️ Weak (minimum 6 characters)';
            color = '#ef476f';
        } else if (val.length < 10) {
            strength = '👍 Medium';
            color = '#ffd166';
        } else {
            strength = '✅ Strong';
            color = '#06d6a0';
        }
        
        if (val.length >= 8 && /[A-Z]/.test(val) && /[0-9]/.test(val)) {
            strength = '💪 Very Strong';
            color = '#06d6a0';
        }
        
        strengthDiv.innerHTML = strength;
        strengthDiv.style.color = color;
    });
}

// ========== PASSWORD MATCH VALIDATION ==========
const confirmInput = document.getElementById('confirmPassword');
const passwordField = document.getElementById('password');

if (confirmInput && passwordField) {
    confirmInput.addEventListener('input', function() {
        if (this.value !== passwordField.value) {
            this.setCustomValidity('Passwords do not match');
            this.style.borderColor = '#ef476f';
        } else {
            this.setCustomValidity('');
            this.style.borderColor = '#06d6a0';
        }
    });
    
    passwordField.addEventListener('input', function() {
        if (confirmInput.value && confirmInput.value !== this.value) {
            confirmInput.setCustomValidity('Passwords do not match');
            confirmInput.style.borderColor = '#ef476f';
        } else if (confirmInput.value) {
            confirmInput.setCustomValidity('');
            confirmInput.style.borderColor = '#06d6a0';
        }
    });
}