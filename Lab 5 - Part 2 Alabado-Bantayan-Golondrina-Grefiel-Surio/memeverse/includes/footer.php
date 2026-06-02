<?php
// includes/footer.php - PHASE 4 PROFESSOR'S VERSION
?>
        </div> <!-- Close row -->
    </div> <!-- Close container -->
</main>

<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-brand">
                🎭 MemeVerse
            </div>
            <div class="footer-links">
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
                <a href="privacy.php">Privacy</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> MemeVerse -- Where memes come to life.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>assets/js/main.js"></script>
<script src="<?= SITE_URL ?>assets/js/auth.js"></script>

<!-- Dark Mode Toggle Script -->
<script>
(function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (!darkModeToggle) return;
    
    function applyTheme(theme) {
        if (theme === 'dark') {
            document.body.classList.add('dark');
            darkModeToggle.innerHTML = '<i class="bi bi-sun-fill"></i>';
        } else {
            document.body.classList.remove('dark');
            darkModeToggle.innerHTML = '<i class="bi bi-moon-stars-fill"></i>';
        }
        localStorage.setItem('memeverse_theme', theme);
    }
    
    const savedTheme = localStorage.getItem('memeverse_theme');
    applyTheme(savedTheme === 'dark' ? 'dark' : 'light');
    
    darkModeToggle.onclick = () => {
        const isDark = document.body.classList.contains('dark');
        applyTheme(isDark ? 'light' : 'dark');
    };
})();
</script>

<!-- Live Search Script -->
<script>
const siteBase = '<?= SITE_URL ?>';
const searchInput = document.getElementById('navbar-search-input');
const searchResults = document.getElementById('navbar-search-results');
let searchTimeout;

if (searchInput) {
    function doSearch() {
        const query = searchInput.value.trim();
        if (query.length < 2) {
            searchResults.classList.remove('show');
            return;
        }
        
        searchResults.classList.add('show');
        searchResults.innerHTML = '<div class="search-loading">Searching...</div>';
        
        fetch(siteBase + 'api/search.php?q=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => displaySearchResults(data, query))
            .catch(err => {
                searchResults.innerHTML = '<div class="search-empty">Network error</div>';
            });
    }
    
    function displaySearchResults(data, query) {
        let html = '';
        
        if (data.posts && data.posts.length) {
            html += '<div class="search-section"><div class="search-section-title">Memes</div>';
            data.posts.forEach(post => {
                html += `
                    <a href="post.php?id=${post.id}" class="search-result-item">
                        <img src="${siteBase}${post.image_path}" class="search-result-img">
                        <div class="search-result-info">
                            <div class="search-result-title">${escapeHtml(post.title || 'Untitled')}</div>
                            <div class="search-result-meta">by ${escapeHtml(post.username)}</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        if (data.users && data.users.length) {
            html += '<div class="search-section"><div class="search-section-title">Users</div>';
            data.users.forEach(user => {
                html += `
                    <a href="profile.php?id=${user.id}" class="search-result-item">
                        <img src="${siteBase}avatars/${user.avatar || 'default.png'}" class="search-result-avatar">
                        <div class="search-result-info">
                            <div class="search-result-title">${escapeHtml(user.username)}</div>
                        </div>
                    </a>
                `;
            });
            html += '</div>';
        }
        
        if (!html) {
            html = '<div class="search-empty">No results for "' + escapeHtml(query) + '"</div>';
        }
        searchResults.innerHTML = html;
    }
    
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(doSearch, 300);
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}
</script>

<!-- Polling for Notifications & Messages -->
<script>
const isUserLoggedIn = <?= json_encode(isset($current_user) && $current_user); ?>;

if (isUserLoggedIn) {
    // Poll for notifications every 3 seconds
    setInterval(async () => {
        try {
            const res = await fetch(siteBase + 'api/unread_notifications.php');
            const data = await res.json();
            const badge = document.getElementById('unread-notification-count');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.textContent = '';
                    badge.style.display = 'none';
                }
            }
        } catch (err) { console.error(err); }
    }, 3000);
    
    // Poll for messages every 3 seconds
    setInterval(async () => {
        try {
            const res = await fetch(siteBase + 'api/unread_messages.php');
            const data = await res.json();
            const badge = document.getElementById('unread-message-count');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.textContent = '';
                    badge.style.display = 'none';
                }
            }
        } catch (err) { console.error(err); }
    }, 3000);
}
</script>

<!-- Settings Modal Handlers -->
<script>
// Change email handler
document.getElementById('changeEmailForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new URLSearchParams(new FormData(this));
    try {
        const res = await fetch(siteBase + 'api/update_email.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert('Email updated successfully');
            bootstrap.Modal.getInstance(document.getElementById('settingsModal')).hide();
        } else {
            alert(data.error || 'Update failed');
        }
    } catch (err) {
        alert('Network error');
    }
});

// Change password handler
document.getElementById('changePasswordForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const newPass = document.querySelector('#changePasswordForm [name="new_password"]').value;
    const confirmPass = document.querySelector('#changePasswordForm [name="confirm_password"]').value;
    
    if (newPass !== confirmPass) {
        alert('New passwords do not match');
        return;
    }
    
    const formData = new URLSearchParams(new FormData(this));
    try {
        const res = await fetch(siteBase + 'api/update_password.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert('Password updated successfully');
            bootstrap.Modal.getInstance(document.getElementById('settingsModal')).hide();
        } else {
            alert(data.error || 'Update failed');
        }
    } catch (err) {
        alert('Network error');
    }
});
</script>

</body>
</html>