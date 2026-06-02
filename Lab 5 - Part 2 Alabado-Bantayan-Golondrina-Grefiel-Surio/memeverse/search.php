<?php
// search.php - PHASE 10 PROFESSOR'S VERSION

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$posts = [];
$users = [];

if ($query && strlen($query) >= 2) {
    $searchTerm = "%$query%";
    
    // Search posts
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.avatar, u.nickname,
               (SELECT COALESCE(SUM(vote_value), 0) FROM votes WHERE post_id = p.id) as vote_score,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.title LIKE ? OR p.description LIKE ? OR u.username LIKE ?
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $posts = $stmt->fetchAll();
    
    // Search users
    $stmt = $pdo->prepare("
        SELECT id, username, nickname, avatar, bio
        FROM users
        WHERE username LIKE ? OR nickname LIKE ?
        LIMIT 20
    ");
    $stmt->execute([$searchTerm, $searchTerm]);
    $users = $stmt->fetchAll();
}
?>

<div class="row g-4">
    <!-- Left Sidebar -->
    <div class="col-lg-3">
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="col-lg-6">
        <!-- Search Header -->
        <div class="profile-header mb-4">
            <div class="d-flex align-items-center gap-3">
                <div style="font-size: 3rem;">🔍</div>
                <div>
                    <h2 class="mb-0">Search Results</h2>
                    <p class="text-muted mb-0"><?= $query ? "Showing results for: \"" . escape($query) . "\"" : "Enter a search term" ?></p>
                </div>
            </div>
        </div>

        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="search.php">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control form-control-lg"
                               placeholder="Search memes or users..." value="<?= escape($query) ?>" autofocus>
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($query): ?>
            <?php if (strlen($query) < 2): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle"></i> Please enter at least 2 characters to search.
                </div>
            <?php else: ?>
                
                <!-- Posts Results -->
                <h4 class="mb-3">Memes (<?= count($posts) ?>)</h4>
                
                <?php if (empty($posts)): ?>
                    <div class="card mb-4">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-emoji-frown fs-1 text-muted"></i>
                            <p class="mt-2">No memes found for "<?= escape($query) ?>"</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-3 mb-4">
                        <?php foreach ($posts as $post): ?>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <a href="post.php?id=<?= $post['id'] ?>">
                                        <img src="<?= SITE_URL . $post['image_path'] ?>"
                                             class="card-img-top"
                                             style="height: 150px; object-fit: cover;">
                                    </a>
                                    <div class="card-body">
                                        <h6 class="card-title"><?= escape($post['title'] ?: 'Untitled') ?></h6>
                                        <small class="text-muted">
                                            <i class="bi bi-arrow-up"></i> <?= $post['vote_score'] ?>
                                            <i class="bi bi-chat ms-2"></i> <?= $post['comment_count'] ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Users Results -->
                <h4 class="mb-3">Users (<?= count($users) ?>)</h4>
                
                <?php if (empty($users)): ?>
                    <div class="card">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-person-x fs-1 text-muted"></i>
                            <p class="mt-2">No users found for "<?= escape($query) ?>"</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($users as $user): ?>
                            <a href="profile.php?id=<?= $user['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex align-items-center">
                                    <img src="<?= getUserAvatar($user, 40) ?>" class="rounded-circle me-2" width="40" height="40">
                                    <div>
                                        <strong><?= escape($user['nickname'] ?: $user['username']) ?></strong><br>
                                        <small class="text-muted">@<?= escape($user['username']) ?></small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Right Sidebar - Trending -->
    <div class="col-lg-3">
        <div class="sidebar-card">
            <div class="sidebar-title">
                <i class="bi bi-fire"></i> TRENDING 🔥
            </div>
            <div id="trending-container" class="category-list">
                <div class="text-center py-2 text-muted">Loading...</div>
            </div>
        </div>
        
        <div class="sidebar-card mt-3">
            <div class="sidebar-title">
                <i class="bi bi-info-circle"></i> SEARCH TIPS
            </div>
            <div class="category-list">
                <div class="category-link" style="cursor: default;">
                    <span class="emoji">💡</span> Use 2+ characters
                </div>
                <div class="category-link" style="cursor: default;">
                    <span class="emoji">🔍</span> Search by title or username
                </div>
                <div class="category-link" style="cursor: default;">
                    <span class="emoji">🏷️</span> Or browse by category
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load trending
async function loadTrending() {
    try {
        const response = await fetch(siteBase + 'api/trending.php');
        const posts = await response.json();
        const container = document.getElementById('trending-container');
        if (!posts || posts.length === 0) {
            container.innerHTML = '<div class="text-center py-2 text-muted">No trending posts yet</div>';
            return;
        }
        let html = '';
        posts.forEach((post, index) => {
            html += `
                <a href="post.php?id=${post.id}" class="category-link">
                    <span class="emoji">${index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '🔥'}</span>
                    <span>${escapeHtml(post.title || 'Meme')}</span>
                    <span class="ms-auto small">+${post.vote_score}</span>
                </a>
            `;
        });
        container.innerHTML = html;
    } catch (error) {
        console.error('Trending error:', error);
    }
}
loadTrending();

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

<?php require_once 'includes/footer.php'; ?>