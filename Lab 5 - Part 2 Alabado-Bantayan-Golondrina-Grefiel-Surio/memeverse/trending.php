<?php
// trending.php - PHASE 5 PROFESSOR'S VERSION
// Shows trending memes from the last 7 days based on vote score

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

// 1. Structural Pagination Parameters - Extract boundary limits for infinite scroll compatibility
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 2. Trending Extraction Query - Pull top memes from last 7 days by aggregated vote score
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.avatar, u.nickname, u.id as user_id,
           c.name as category_name, c.slug as category_slug,
           (SELECT COALESCE(SUM(vote_value), 0) FROM votes WHERE post_id = p.id) as vote_score,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN categories c ON p.category_id = c.id
    WHERE p.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY vote_score DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

// 3. Hydrate each post array with the current user's vote interaction value
foreach ($posts as &$post) {
    $post['user_vote'] = getUserVote($pdo, $post['id'], $_SESSION['user_id'] ?? 0);
}

// 4. Total Count Extraction - Used for pagination boundary calculations
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
$countStmt->execute();
$total_posts = $countStmt->fetchColumn();
$has_more = ($offset + $limit) < $total_posts;
?>

<div class="row g-4">
    <!-- Left Sidebar Navigation Grid -->
    <div class="col-lg-3">
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <!-- Main Content Feed Column -->
    <div class="col-lg-6">
        <!-- Trending Header Section with Visual Indicator -->
        <div class="profile-header mb-4">
            <div class="d-flex align-items-center gap-3">
                <div style="font-size: 3rem;">🔥</div>
                <div>
                    <h2 class="mb-0">Trending Memes</h2>
                    <p class="text-muted mb-0">Most upvoted memes from the last 7 days</p>
                </div>
            </div>
        </div>

        <?php if (empty($posts)): ?>
            <!-- Empty State Renderer - No trending content available -->
            <div class="empty-state">
                <i class="bi bi-fire"></i>
                <h5>No trending memes yet</h5>
                <p>Check back later for popular memes! 🔥</p>
                <?php if (isLoggedIn()): ?>
                    <a href="upload.php" class="btn-create">Upload a Meme</a>
                <?php else: ?>
                    <a href="login.php" class="btn-create">Login to Post</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Primary Posts Container - Renders the initial trending payload -->
            <div id="posts-container">
                <?php foreach ($posts as $post): ?>
                    <?php include 'includes/post_card.php'; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Infinite Scroll Trigger - Load more button for additional trending content -->
            <?php if ($has_more): ?>
                <div class="load-more-wrapper">
                    <button class="load-more-btn" id="load-more-btn" data-page="2" data-type="trending">
                        <i class="bi bi-arrow-repeat"></i> Load More Trending Memes
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Right Sidebar - Information Panel -->
    <div class="col-lg-3">
        <div class="sidebar-card">
            <div class="sidebar-title">
                <i class="bi bi-info-circle"></i> ABOUT TRENDING
            </div>
            <div class="category-list">
                <div class="category-link" style="cursor: default;">
                    <span class="emoji">🔥</span> Top memes from last 7 days
                </div>
                <div class="category-link" style="cursor: default;">
                    <span class="emoji">📊</span> Sorted by vote score
                </div>
                <div class="category-link" style="cursor: default;">
                    <span class="emoji">❤️</span> Upvote to make memes trend!
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Infinite Scroll Client-Side Implementation -->
<script>
let currentPage = 2;
let loading = false;

const loadMoreBtn = document.getElementById('load-more-btn');
if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', async function() {
        if (loading) return;
        loading = true;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading...';

        try {
            // Fetch next batch of trending posts via API endpoint
            const response = await fetch(`${siteBase}api/trending_posts.php?page=${currentPage}&limit=10`);
            const posts = await response.json();

            if (posts.length > 0) {
                const container = document.getElementById('posts-container');
                posts.forEach(post => {
                    container.insertAdjacentHTML('beforeend', createPostHTML(post));
                });
                currentPage++;
                if (posts.length < 10) this.style.display = 'none';
                else {
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-arrow-repeat"></i> Load More Trending Memes';
                }
            } else {
                this.style.display = 'none';
            }
        } catch (error) {
            console.error('Infinite scroll pagination error:', error);
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-arrow-repeat"></i> Retry Operation';
        } finally {
            loading = false;
        }
    });
}

/**
 * Constructs HTML markup for a single post card dynamically
 * @param {Object} post - Post data object from API response
 * @returns {string} HTML string representation of a meme post card
 */
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

/**
 * HTML escaping utility to prevent XSS injection vectors
 */
function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

/**
 * Category emoji mapping utility - Returns visual icon for category slugs
 */
function getCategoryEmoji(slug) {
    const emojis = {
        'funny': '😂', 'animals': '🐾', 'music': '🎵', 'movies': '🎬',
        'gaming': '🎮', 'food': '🍕', 'travel': '✈️', 'awesome': '✨'
    };
    return emojis[slug] || '🏷️';
}

/**
 * Relative timestamp generator - Converts ISO dates to human-readable strings
 */
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
</script>

<?php require_once 'includes/footer.php'; ?>