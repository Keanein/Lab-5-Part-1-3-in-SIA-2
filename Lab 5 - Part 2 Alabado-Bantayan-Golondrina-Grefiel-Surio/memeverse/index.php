<?php
// index.php - PHASE 5 PROFESSOR'S VERSION

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';

// Get initial posts
$limit = 10;
$offset = 0;

$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.avatar, u.nickname, u.id as user_id,
           c.name as category_name, c.slug as category_slug,
           (SELECT COALESCE(SUM(vote_value), 0) FROM votes WHERE post_id = p.id) as vote_score,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$initial_posts = $stmt->fetchAll();

foreach ($initial_posts as &$post) {
    $post['user_vote'] = getUserVote($pdo, $post['id'], $_SESSION['user_id'] ?? 0);
}

$last_post_id = !empty($initial_posts) ? $initial_posts[count($initial_posts) - 1]['id'] : 0;
?>

<div class="row g-4">
    <!-- Left Sidebar -->
    <div class="col-lg-3">
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <!-- Main Feed -->
    <div class="col-lg-6">
        <?php if (empty($initial_posts)): ?>
            <div class="empty-state">
                <i class="bi bi-emoji-smile"></i>
                <h5>No memes yet!</h5>
                <p>Be the first to share something funny! 🎭</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="upload.php" class="btn-create">Upload Meme</a>
                <?php else: ?>
                    <a href="login.php" class="btn-create">Login to Post</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div id="posts-container">
                <?php foreach ($initial_posts as $post): ?>
                    <?php include 'includes/post_card.php'; ?>
                <?php endforeach; ?>
            </div>

            <?php if (count($initial_posts) === $limit): ?>
                <div class="load-more-wrapper">
                    <button class="load-more-btn" id="load-more-btn" data-last-id="<?= $last_post_id ?>">
                        <i class="bi bi-arrow-repeat"></i> Load More Memes
                    </button>
                </div>
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
    </div>
</div>

<script>
let currentPage = 2;
let loading = false;
let loadedPostIds = new Set();

// Store existing post IDs
document.querySelectorAll('.post-card').forEach(card => {
    const postId = card.dataset.postId;
    if (postId) loadedPostIds.add(parseInt(postId));
});

const loadMoreBtn = document.getElementById('load-more-btn');
if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', async function() {
        if (loading) return;
        loading = true;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading...';

        try {
            const lastId = this.dataset.lastId;
            const response = await fetch(`${siteBase}api/posts.php?page=${currentPage}&limit=10&last_id=${lastId}`);
            const posts = await response.json();

            if (posts.length > 0) {
                const container = document.getElementById('posts-container');
                let newPostsCount = 0;

                posts.forEach(post => {
                    if (!loadedPostIds.has(post.id)) {
                        loadedPostIds.add(post.id);
                        container.insertAdjacentHTML('beforeend', createPostHTML(post));
                        newPostsCount++;
                    }
                });

                if (newPostsCount > 0) {
                    currentPage++;
                    if (posts.length > 0) {
                        this.dataset.lastId = posts[posts.length - 1].id;
                    }
                }

                if (posts.length < 10 || newPostsCount === 0) {
                    this.style.display = 'none';
                } else {
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-arrow-repeat"></i> Load More Memes';
                }
            } else {
                this.style.display = 'none';
            }
        } catch (error) {
            console.error('Error:', error);
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-arrow-repeat"></i> Try Again';
        } finally {
            loading = false;
        }
    });
}

function createPostHTML(post) {
    const upActive = post.user_vote === 1 ? 'active' : '';
    const downActive = post.user_vote === -1 ? 'active' : '';
    const avatarUrl = post.avatar ? siteBase + 'avatars/' + post.avatar : `https://ui-avatars.com/api/?background=ff6b6b&color=fff&bold=true&size=48&name=${encodeURIComponent(post.username)}`;
    const categoryEmoji = getCategoryEmoji(post.category_slug);

    return `
        <div class="post-card" data-post-id="${post.id}">
            <div class="post-header">
                <img src="${avatarUrl}" class="post-avatar" alt="Avatar">
                <div class="post-user-info">
                    <a href="profile.php?id=${post.user_id}" class="post-username">${escapeHtml(post.nickname || post.username)}</a>
                    <div class="post-time">${timeAgo(post.created_at)}</div>
                </div>
                <span class="category-badge">
                    <span>${categoryEmoji}</span> ${escapeHtml(post.category_name)}
                </span>
            </div>
            <a href="post.php?id=${post.id}">
                <img src="${siteBase}${post.image_path}" class="post-image" alt="Meme">
            </a>
            <div class="post-body">
                <h3 class="post-title">${escapeHtml(post.title || 'Untitled')}</h3>
                ${post.description ? `<p class="post-description">${escapeHtml(post.description.substring(0, 150))}</p>` : ''}
                <div class="post-actions">
                    <div class="vote-group">
                        <button class="vote-btn upvote ${upActive}" data-post-id="${post.id}" data-vote="up">
                            <i class="bi bi-arrow-up"></i>
                        </button>
                        <span class="vote-count" id="vote-${post.id}">${post.vote_score}</span>
                        <button class="vote-btn downvote ${downActive}" data-post-id="${post.id}" data-vote="down">
                            <i class="bi bi-arrow-down"></i>
                        </button>
                    </div>
                    <a href="post.php?id=${post.id}" class="comment-btn">
                        <i class="bi bi-chat"></i> <span>${post.comment_count}</span>
                    </a>
                </div>
            </div>
        </div>
    `;
}

function getCategoryEmoji(slug) {
    const emojis = {
        'funny': '😂', 'animals': '🐾', 'music': '🎵', 'movies': '🎬',
        'gaming': '🎮', 'food': '🍕', 'travel': '✈️', 'awesome': '✨'
    };
    return emojis[slug] || '🏷️';
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

function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' min ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
    return date.toLocaleDateString();
}

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
        document.getElementById('trending-container').innerHTML = '<div class="text-center py-2 text-muted">Failed to load</div>';
    }
}

loadTrending();
</script>

<?php require_once 'includes/footer.php'; ?>