<?php
// includes/sidebar.php - PHASE 4 PROFESSOR'S VERSION (WITH TRENDING JAVASCRIPT)

// Fetch categories from database
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$current_slug = $_GET['slug'] ?? '';
?>
<!-- Categories Card -->
<div class="sidebar-card">
    <div class="sidebar-title">
        <i class="bi bi-compass"></i> EXPLORE
    </div>
    
    <a href="<?= SITE_URL ?>" class="category-link <?= empty($current_slug) ? 'active' : '' ?>">
        <span class="emoji">🏠</span> All Memes
    </a>
    
    <div class="category-divider"></div>
    
    <a href="<?= SITE_URL ?>trending.php" class="category-link">
        <span class="emoji">🔥</span> Trending
    </a>
    
    <a href="<?= SITE_URL ?>latest.php" class="category-link">
        <span class="emoji">🕒</span> Latest
    </a>
    
    <div class="category-divider"></div>
    
    <?php foreach ($categories as $cat): ?>
        <a href="<?= SITE_URL ?>category.php?slug=<?= $cat['slug'] ?>"
           class="category-link <?= $current_slug === $cat['slug'] ? 'active' : '' ?>">
            <span class="emoji"><?= getCategoryEmoji($cat['slug']) ?></span>
            <?= escape($cat['name']) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Quick Links Card -->
<div class="sidebar-card">
    <div class="sidebar-title">
        <i class="bi bi-stars"></i> QUICK LINKS
    </div>
    
    <?php if (isLoggedIn()): ?>
        <a href="<?= SITE_URL ?>upload.php" class="category-link">
            <span class="emoji">📤</span> Upload Meme
        </a>
        <a href="<?= SITE_URL ?>profile.php" class="category-link">
            <span class="emoji">👤</span> My Profile
        </a>
        <a href="<?= SITE_URL ?>messages.php" class="category-link">
            <span class="emoji">💬</span> Messages
        </a>
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
            <a href="<?= SITE_URL ?>admin/index.php" class="category-link text-warning">
                <span class="emoji">👑</span> Admin Panel
            </a>
        <?php endif; ?>
    <?php else: ?>
        <a href="<?= SITE_URL ?>login.php" class="category-link">
            <span class="emoji">🔑</span> Login
        </a>
        <a href="<?= SITE_URL ?>register.php" class="category-link">
            <span class="emoji">📝</span> Register
        </a>
    <?php endif; ?>
</div>

<!-- Trending Widget (populated by JavaScript) -->
<div class="sidebar-card">
    <div class="sidebar-title">
        <i class="bi bi-fire"></i> TRENDING 🔥
    </div>
    <div id="trending-container" class="category-list">
        <div class="text-center py-2 text-muted">Loading...</div>
    </div>
</div>

<style>
.category-divider {
    height: 1px;
    background: var(--border-color);
    margin: 0.5rem 0;
}
</style>

<!-- Trending Widget JavaScript -->
<script>
// Make sure siteBase is available (from footer.php)
const trendingSiteBase = typeof siteBase !== 'undefined' ? siteBase : '<?= SITE_URL ?>';

async function loadTrending() {
    const container = document.getElementById('trending-container');
    if (!container) return;
    
    try {
        const response = await fetch(trendingSiteBase + 'api/trending.php');
        const posts = await response.json();
        
        if (!posts || posts.length === 0) {
            container.innerHTML = '<div class="text-center py-2 text-muted">No trending posts yet</div>';
            return;
        }
        
        let html = '';
        posts.forEach((post, index) => {
            const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '🔥';
            html += `
                <a href="post.php?id=${post.id}" class="category-link">
                    <span class="emoji">${medal}</span>
                    <span>${escapeHtmlTrending(post.title || 'Meme')}</span>
                    <span class="ms-auto small">+${post.vote_score}</span>
                </a>
            `;
        });
        container.innerHTML = html;
    } catch (error) {
        console.error('Trending error:', error);
        container.innerHTML = '<div class="text-center py-2 text-muted">Failed to load trending</div>';
    }
}

function escapeHtmlTrending(text) {
    if (!text) return '';
    return text.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Load trending when page is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadTrending);
} else {
    loadTrending();
}
</script>