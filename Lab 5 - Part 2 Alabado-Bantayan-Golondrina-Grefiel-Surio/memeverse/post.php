<?php
// post.php - PHASE 7 PROFESSOR'S VERSION (FIXED AVATAR SIZES)

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$post_id) {
    redirect('index.php');
}

// Get post details
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.avatar, u.nickname, u.id as user_id,
           c.name as category_name, c.slug as category_slug, c.id as category_id
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    redirect('index.php');
}

// Get vote score and user vote
$post['vote_score'] = getVoteScore($pdo, $post_id);
$post['user_vote'] = getUserVote($pdo, $post_id, $_SESSION['user_id'] ?? 0);
$post['comment_count'] = getCommentCount($pdo, $post_id);

// Get all comments for this post
$stmt = $pdo->prepare("
    SELECT c.*, u.username, u.avatar, u.nickname
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$post_id]);
$all_comments = $stmt->fetchAll();

// Build nested comment tree
function buildCommentTree($comments) {
    $tree = [];
    $ref = [];
    
    foreach ($comments as $comment) {
        $comment['replies'] = [];
        $ref[$comment['id']] = $comment;
    }
    
    foreach ($ref as $id => $comment) {
        if ($comment['parent_id'] == 0 || $comment['parent_id'] === null) {
            $tree[$id] = &$ref[$id];
        } else {
            if (isset($ref[$comment['parent_id']])) {
                $ref[$comment['parent_id']]['replies'][] = &$ref[$id];
            }
        }
    }
    
    return $tree;
}

$comments_tree = buildCommentTree($all_comments);
$is_owner = (isLoggedIn() && $_SESSION['user_id'] == $post['user_id']);
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div class="row g-4">
    <!-- Left Sidebar -->
    <div class="col-lg-3">
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="col-lg-6">
        <!-- Post Card -->
        <div class="post-card">
            <div class="post-header">
                <img src="<?= getUserAvatar($post, 48) ?>" class="post-avatar" alt="Avatar">
                <div class="post-user-info">
                    <a href="profile.php?id=<?= $post['user_id'] ?>" class="post-username">
                        <?= escape($post['nickname'] ?: $post['username']) ?>
                    </a>
                    <div class="post-time"><?= date('F j, Y \a\t g:i a', strtotime($post['created_at'])) ?></div>
                </div>
                <span class="category-badge">
                    <span><?= getCategoryEmoji($post['category_slug']) ?></span>
                    <?= escape($post['category_name']) ?>
                </span>
            </div>

            <img src="<?= SITE_URL . $post['image_path'] ?>" class="post-image" alt="Meme">

            <div class="post-body">
                <h2 class="post-title"><?= escape($post['title'] ?: 'Untitled') ?></h2>
                
                <?php if ($post['description']): ?>
                    <p class="post-description" style="font-size: 1rem;"><?= nl2br(escape($post['description'])) ?></p>
                <?php endif; ?>

                <div class="post-actions">
                    <div class="vote-group">
                        <button class="vote-btn upvote <?= $post['user_vote'] === 1 ? 'active' : '' ?>"
                                data-post-id="<?= $post['id'] ?>" data-vote="up">
                            <i class="bi bi-arrow-up"></i>
                        </button>
                        <span class="vote-count" id="vote-<?= $post['id'] ?>"><?= $post['vote_score'] ?></span>
                        <button class="vote-btn downvote <?= $post['user_vote'] === -1 ? 'active' : '' ?>"
                                data-post-id="<?= $post['id'] ?>" data-vote="down">
                            <i class="bi bi-arrow-down"></i>
                        </button>
                    </div>
                    
                    <?php if (isLoggedIn() && !$is_owner): ?>
                        <button class="btn btn-sm btn-outline-danger report-btn" data-type="post" data-id="<?= $post['id'] ?>">Report Post</button>
                    <?php endif; ?>
                </div>

                <?php if ($is_owner): ?>
                    <div class="mt-3 d-flex gap-2">
                        <button class="btn-create edit-post-btn"
                                data-post-id="<?= $post['id'] ?>"
                                data-title="<?= escape($post['title']) ?>"
                                data-description="<?= escape($post['description']) ?>"
                                data-category-id="<?= $post['category_id'] ?>"
                                data-image="<?= SITE_URL . $post['image_path'] ?>">
                            <i class="bi bi-pencil"></i> Edit Post
                        </button>
                        <button class="btn-outline delete-post-btn" data-post-id="<?= $post['id'] ?>">
                            <i class="bi bi-trash"></i> Delete Post
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="profile-header mt-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div style="font-size: 2rem;">💬</div>
                <div>
                    <h3 class="mb-0">Comments</h3>
                    <p class="text-muted mb-0">Join the conversation</p>
                </div>
            </div>

            <!-- Add Comment Form -->
            <?php if (isLoggedIn()): ?>
                <div class="mb-4">
                    <div class="d-flex gap-3">
                        <img src="<?= getUserAvatar($current_user, 44) ?>" class="comment-avatar" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover;" alt="Avatar">
                        <div class="flex-grow-1">
                            <textarea id="comment-text" class="form-control" rows="3" placeholder="What are your thoughts? Share your reaction..."></textarea>
                            <div class="mt-2 text-end">
                                <button class="btn-create" id="post-comment-btn">
                                    <i class="bi bi-send"></i> Post Comment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="sidebar-card text-center mb-4">
                    <p class="mb-0">
                        <a href="login.php" class="text-primary fw-bold">Login</a> to join the conversation! 🎭
                    </p>
                </div>
            <?php endif; ?>

            <!-- Comments List with Replies -->
            <div id="comments-container">
                <?php if (empty($comments_tree)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-chat-dots fs-1 text-muted"></i>
                        <p class="text-muted mt-2 mb-0">No comments yet. Be the first!</p>
                    </div>
                <?php else: ?>
                    <?php
                    // Recursive function to render comments and replies
                    function renderComment($comment, $post_id, $current_user, $pdo) {
                        ?>
                        <div class="comment-card" id="comment-<?= $comment['id'] ?>" style="background: var(--bg-primary); border-radius: 20px; padding: 1rem; margin-bottom: 1rem;">
                            <div class="comment-header" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                <img src="<?= getUserAvatar($comment, 36) ?>" class="comment-avatar" alt="Avatar" style="width: 36px !important; height: 36px !important; border-radius: 50%; object-fit: cover;">
                                <div style="flex: 1;">
                                    <a href="profile.php?id=<?= $comment['user_id'] ?>" class="comment-username" style="font-weight: 600; font-size: 0.9rem; text-decoration: none; color: var(--text-primary);">
                                        <?= escape($comment['nickname'] ?: $comment['username']) ?>
                                    </a>
                                    <span class="comment-time" style="font-size: 0.7rem; color: var(--text-muted); margin-left: 0.5rem;"><?= timeAgo($comment['created_at']) ?></span>
                                </div>
                                <?php if ($current_user): ?>
                                    <button class="reply-btn" data-comment-id="<?= $comment['id'] ?>" style="background: transparent; border: 1px solid var(--border-color); padding: 0.25rem 0.75rem; border-radius: 40px; font-size: 0.7rem; cursor: pointer;">
                                        <i class="bi bi-reply"></i> Reply
                                    </button>
                                <?php endif; ?>
                                <?php if ($current_user && ($current_user['id'] == $comment['user_id'] || $current_user['id'] == $post_id)): ?>
                                    <button class="delete-comment-btn btn-sm" data-id="<?= $comment['id'] ?>" title="Delete Comment" style="background: transparent; border: none; color: var(--danger); cursor: pointer;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="comment-text" style="margin-left: 3rem; font-size: 0.9rem; line-height: 1.5;">
                                <?= nl2br(escape($comment['comment_text'])) ?>
                            </div>

                            <!-- Reply Form -->
                            <?php if ($current_user): ?>
                                <div class="reply-form" id="reply-form-<?= $comment['id'] ?>" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                                    <div class="d-flex gap-2 mt-3">
                                        <img src="<?= getUserAvatar($current_user, 32) ?>" class="comment-avatar" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <textarea class="form-control reply-text" rows="2" placeholder="Write a reply..."></textarea>
                                            <div class="mt-2 text-end">
                                                <button class="btn-sm btn-outline cancel-reply" data-comment-id="<?= $comment['id'] ?>" style="background: transparent; border: 1px solid var(--border-color); padding: 0.25rem 0.75rem; border-radius: 40px; font-size: 0.7rem;">Cancel</button>
                                                <button class="btn-sm btn-create submit-reply" data-comment-id="<?= $comment['id'] ?>" data-post-id="<?= $post_id ?>" style="background: var(--primary); color: white; border: none; padding: 0.25rem 0.75rem; border-radius: 40px; font-size: 0.7rem;">Reply</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Replies -->
                            <?php if (!empty($comment['replies'])): ?>
                                <div class="replies-container" style="margin-left: 2rem; padding-left: 1rem; border-left: 2px solid var(--border-color); margin-top: 1rem;">
                                    <?php foreach ($comment['replies'] as $reply): ?>
                                        <?php renderComment($reply, $post_id, $current_user, $pdo); ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    }

                    foreach ($comments_tree as $comment):
                        renderComment($comment, $post['user_id'], $current_user ?? null, $pdo);
                    endforeach;
                    ?>
                <?php endif; ?>
            </div>
        </div>
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

<!-- Edit Post Modal -->
<div class="modal fade" id="editPostModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content edit-modal">
            <div class="modal-header border-0">
                <div class="modal-header-icon"><i class="bi bi-pencil-square"></i></div>
                <h5 class="modal-title">Edit Meme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_post_id">
                <div class="edit-preview mb-4 text-center">
                    <img id="edit_image_preview" src="" class="edit-preview-img">
                </div>
                <div class="form-group mb-3">
                    <label><i class="bi bi-fonts"></i> Title</label>
                    <input type="text" id="edit_title" class="form-control modern-input" maxlength="200">
                </div>
                <div class="form-group mb-3">
                    <label><i class="bi bi-text-paragraph"></i> Description</label>
                    <textarea id="edit_description" class="form-control modern-input" rows="4"></textarea>
                </div>
                <div class="form-group mb-4">
                    <label><i class="bi bi-tags"></i> Category</label>
                    <select id="edit_category" class="form-select modern-select">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= getCategoryEmoji($cat['slug']) ?> <?= escape($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="savePostEditBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Comment Confirmation Modal -->
<div class="modal fade" id="deleteCommentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content confirmation-modal">
            <div class="modal-body text-center py-4">
                <div class="confirmation-icon"><i class="bi bi-trash3"></i></div>
                <h5 class="confirmation-title">Delete Comment?</h5>
                <p class="confirmation-message">Are you sure? This comment will vanish into the meme dimension! 💨</p>
                <div class="confirmation-buttons">
                    <button class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger confirm-delete-comment-btn">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Post Confirmation Modal -->
<div class="modal fade" id="deletePostModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content confirmation-modal">
            <div class="modal-body text-center py-4">
                <div class="confirmation-icon"><i class="bi bi-trash3"></i></div>
                <h5 class="confirmation-title">Delete Meme?</h5>
                <p class="confirmation-message">Are you sure? This meme will vanish forever! 💨<br>No take-backs!</p>
                <div class="confirmation-buttons">
                    <button class="btn btn-cancel" data-bs-dismiss="modal">I changed my mind</button>
                    <button class="btn btn-danger confirm-delete-post-btn">Delete Forever!</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Edit post
document.querySelector('.edit-post-btn')?.addEventListener('click', function() {
    document.getElementById('edit_post_id').value = this.dataset.postId;
    document.getElementById('edit_title').value = this.dataset.title || '';
    document.getElementById('edit_description').value = this.dataset.description || '';
    document.getElementById('edit_category').value = this.dataset.categoryId;
    document.getElementById('edit_image_preview').src = this.dataset.image;
    new bootstrap.Modal(document.getElementById('editPostModal')).show();
});

document.getElementById('savePostEditBtn')?.addEventListener('click', async function() {
    const formData = new URLSearchParams();
    formData.append('post_id', document.getElementById('edit_post_id').value);
    formData.append('title', document.getElementById('edit_title').value);
    formData.append('description', document.getElementById('edit_description').value);
    formData.append('category_id', document.getElementById('edit_category').value);
    
    try {
        const response = await fetch(siteBase + 'api/edit_post.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) location.reload();
        else alert(data.error || 'Failed to update post');
    } catch (error) {
        alert('Network error');
    }
});

// Delete post
let postToDelete = null;
document.querySelector('.delete-post-btn')?.addEventListener('click', function() {
    postToDelete = this;
    new bootstrap.Modal(document.getElementById('deletePostModal')).show();
});

document.querySelector('.confirm-delete-post-btn')?.addEventListener('click', async function() {
    if (!postToDelete) return;
    const formData = new URLSearchParams();
    formData.append('post_id', postToDelete.dataset.postId);
    try {
        const response = await fetch(siteBase + 'api/delete_post.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) window.location.href = siteBase + 'index.php';
        else alert(data.error || 'Delete failed');
    } catch (error) {
        alert('Network error');
    } finally {
        bootstrap.Modal.getInstance(document.getElementById('deletePostModal')).hide();
        postToDelete = null;
    }
});

// Post comment
document.getElementById('post-comment-btn')?.addEventListener('click', async function() {
    const commentText = document.getElementById('comment-text').value.trim();
    if (!commentText) return;
    
    try {
        const response = await fetch(siteBase + 'api/comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: <?= $post_id ?>, comment: commentText })
        });
        const data = await response.json();
        if (data.success) location.reload();
        else alert(data.error || 'Failed to post comment');
    } catch (error) {
        alert('Network error');
    }
});

// Reply functionality
document.querySelectorAll('.reply-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const commentId = this.dataset.commentId;
        document.querySelectorAll('.reply-form').forEach(f => f.style.display = 'none');
        document.getElementById(`reply-form-${commentId}`).style.display = 'block';
    });
});

document.querySelectorAll('.cancel-reply').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById(`reply-form-${this.dataset.commentId}`).style.display = 'none';
    });
});

document.querySelectorAll('.submit-reply').forEach(btn => {
    btn.addEventListener('click', async function() {
        const parent = this.dataset.commentId;
        const postId = this.dataset.postId;
        const reply = document.querySelector(`#reply-form-${parent} .reply-text`).value.trim();
        if (!reply) return;
        
        try {
            const response = await fetch(siteBase + 'api/comment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ post_id: postId, comment: reply, parent_id: parent })
            });
            const data = await response.json();
            if (data.success) location.reload();
            else alert(data.error || 'Failed to post reply');
        } catch (error) {
            alert('Network error');
        }
    });
});

// Delete comment
let commentToDelete = null;
document.querySelectorAll('.delete-comment-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        commentToDelete = this.dataset.id;
        new bootstrap.Modal(document.getElementById('deleteCommentModal')).show();
    });
});

document.querySelector('.confirm-delete-comment-btn')?.addEventListener('click', async function() {
    if (!commentToDelete) return;
    const formData = new URLSearchParams();
    formData.append('comment_id', commentToDelete);
    try {
        const response = await fetch(siteBase + 'api/comment.php', { method: 'DELETE', body: formData });
        const data = await response.json();
        if (data.success) location.reload();
        else alert(data.error || 'Failed to delete comment');
    } catch (error) {
        alert('Network error');
    } finally {
        bootstrap.Modal.getInstance(document.getElementById('deleteCommentModal')).hide();
        commentToDelete = null;
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