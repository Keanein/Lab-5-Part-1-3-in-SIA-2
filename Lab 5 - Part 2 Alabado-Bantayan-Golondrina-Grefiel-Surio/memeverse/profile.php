<?php
// profile.php - PHASE 5 PROFESSOR'S VERSION

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : (isLoggedIn() ? $_SESSION['user_id'] : 0);

if (!$user_id) {
    redirect('login.php');
}

// Fetch user data
$stmt = $pdo->prepare("SELECT id, username, nickname, email, bio, avatar, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('index.php');
}

$is_owner = (isLoggedIn() && $_SESSION['user_id'] == $user_id);
$display_name = $user['nickname'] ?: $user['username'];
$avatar_url = getUserAvatar($user, 120);

// Fetch user's posts
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, c.id as category_id,
           (SELECT COALESCE(SUM(vote_value), 0) FROM votes WHERE post_id = p.id) as vote_score,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
    FROM posts p
    JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$user_id]);
$user_posts = $stmt->fetchAll();

// Follower counts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE following_id = ?");
$stmt->execute([$user_id]);
$followers = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
$stmt->execute([$user_id]);
$following = $stmt->fetchColumn();

// Check if current user follows this profile
$is_following = false;
if (isLoggedIn() && $_SESSION['user_id'] != $user_id) {
    $stmt = $pdo->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$_SESSION['user_id'], $user_id]);
    $is_following = (bool)$stmt->fetch();
}

// Get all categories for edit modal
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div class="row g-4">
    <!-- Left Sidebar -->
    <div class="col-lg-3">
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="col-lg-6">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-3 text-center text-md-start">
                    <img src="<?= $avatar_url ?>" class="profile-avatar-large" alt="Avatar">
                </div>
                <div class="col-md-9 text-center text-md-start">
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                        <h2 class="h3 mb-0"><?= escape($display_name) ?></h2>
                        <span class="text-muted">@<?= escape($user['username']) ?></span>
                        
                        <?php if ($is_owner): ?>
                            <button class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="bi bi-pencil"></i> Edit Profile
                            </button>
                        <?php elseif (isLoggedIn()): ?>
                            <button class="btn btn-sm <?= $is_following ? 'btn-outline-danger' : 'btn-primary' ?> rounded-pill follow-btn"
                                    data-user-id="<?= $user_id ?>"
                                    data-action="<?= $is_following ? 'unfollow' : 'follow' ?>">
                                <i class="bi bi-person-<?= $is_following ? 'dash' : 'plus' ?>"></i>
                                <?= $is_following ? 'Unfollow' : 'Follow' ?>
                            </button>
                            <a href="messages.php?with=<?= $user_id ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                <i class="bi bi-envelope"></i> Message
                            </a>
                            <button class="btn btn-sm btn-outline-danger rounded-pill report-user-btn" data-user-id="<?= $user_id ?>">
                                <i class="bi bi-flag"></i> Report
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="profile-stats">
                        <div class="profile-stat">
                            <div class="profile-stat-number"><?= count($user_posts) ?></div>
                            <div class="profile-stat-label">Posts</div>
                        </div>
                        <div class="profile-stat">
                            <div class="profile-stat-number"><?= $followers ?></div>
                            <div class="profile-stat-label">Followers</div>
                        </div>
                        <div class="profile-stat">
                            <div class="profile-stat-number"><?= $following ?></div>
                            <div class="profile-stat-label">Following</div>
                        </div>
                    </div>

                    <?php if ($user['bio']): ?>
                        <div class="profile-bio">
                            <?= nl2br(escape($user['bio'])) ?>
                        </div>
                    <?php endif; ?>

                    <small class="text-muted">
                        <i class="bi bi-calendar3"></i> Joined <?= date('F Y', strtotime($user['created_at'])) ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Posts Gallery -->
        <h5 class="mb-3"><i class="bi bi-grid-3x3-gap-fill"></i> Posts Gallery</h5>
        
        <?php if (empty($user_posts)): ?>
            <div class="empty-state">
                <i class="bi bi-camera"></i>
                <h5>No posts yet</h5>
                <?php if ($is_owner): ?>
                    <a href="upload.php" class="btn-create">Upload Your First Meme</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="posts-grid">
                <?php foreach ($user_posts as $post): ?>
                    <div class="grid-item" data-post-id="<?= $post['id'] ?>">
                        <a href="post.php?id=<?= $post['id'] ?>">
                            <img src="<?= SITE_URL . $post['image_path'] ?>" alt="Post">
                        </a>
                        <div class="grid-overlay">
                            <div class="grid-stats">
                                <span><i class="bi bi-arrow-up"></i> <?= $post['vote_score'] ?></span>
                                <span><i class="bi bi-chat"></i> <?= $post['comment_count'] ?></span>
                            </div>
                        </div>
                        <?php if ($is_owner): ?>
                            <div class="grid-actions">
                                <button class="grid-action-btn edit-post"
                                        data-post-id="<?= $post['id'] ?>"
                                        data-title="<?= escape($post['title']) ?>"
                                        data-description="<?= escape($post['description']) ?>"
                                        data-category-id="<?= $post['category_id'] ?>"
                                        data-image="<?= SITE_URL . $post['image_path'] ?>"
                                        title="Edit Post">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <button class="grid-action-btn delete-post"
                                        data-post-id="<?= $post['id'] ?>"
                                        title="Delete Post">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
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

<!-- Edit Profile Modal (Owner Only) -->
<?php if ($is_owner): ?>
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProfileForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <img id="avatarPreview" src="<?= $avatar_url ?>" class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                        <input type="file" name="avatar" id="avatarInput" class="form-control form-control-sm" accept="image/*">
                        <small class="text-muted">Leave empty to keep current avatar</small>
                    </div>
                    <div class="mb-3">
                        <label>Nickname</label>
                        <input type="text" name="nickname" class="form-control" value="<?= escape($user['nickname'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Bio</label>
                        <textarea name="bio" class="form-control" rows="4"><?= escape($user['bio'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Report User Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-flag"></i> Report User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="report-user-id">
                <textarea id="report-reason" class="form-control" rows="3" placeholder="Why are you reporting this user?"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="submit-report-btn">Submit Report</button>
            </div>
        </div>
    </div>
</div>

<script>
// Avatar preview
document.getElementById('avatarInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = ev => document.getElementById('avatarPreview').src = ev.target.result;
        reader.readAsDataURL(file);
    }
});

// Edit profile form
document.getElementById('editProfileForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    try {
        const response = await fetch(siteBase + 'api/update_profile.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) location.reload();
        else alert(data.error || 'Update failed');
    } catch (error) {
        alert('Network error');
    }
});

// Follow/Unfollow
document.querySelectorAll('.follow-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const formData = new URLSearchParams();
        formData.append('user_id', this.dataset.userId);
        formData.append('action', this.dataset.action);
        try {
            const response = await fetch(siteBase + 'api/follow.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) location.reload();
            else alert(data.error || 'Action failed');
        } catch (error) {
            alert('Network error');
        }
    });
});

// Report user
document.querySelectorAll('.report-user-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('report-user-id').value = this.dataset.userId;
        new bootstrap.Modal(document.getElementById('reportModal')).show();
    });
});

document.getElementById('submit-report-btn')?.addEventListener('click', async () => {
    const userId = document.getElementById('report-user-id').value;
    const reason = document.getElementById('report-reason').value.trim();
    if (!reason) return alert('Please provide a reason');
    try {
        const res = await fetch(siteBase + 'api/report.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'user', id: userId, reason })
        });
        const data = await res.json();
        if (data.success) {
            alert('Report submitted');
            bootstrap.Modal.getInstance(document.getElementById('reportModal')).hide();
            document.getElementById('report-reason').value = '';
        } else alert(data.error || 'Failed to submit');
    } catch (err) {
        alert('Network error');
    }
});

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