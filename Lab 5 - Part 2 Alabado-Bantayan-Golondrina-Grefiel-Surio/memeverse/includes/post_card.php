<?php
// includes/post_card.php - PHASE 4 PROFESSOR'S VERSION
// Reusable meme card component - used everywhere (home, category, profile, search)
?>

<div class="post-card" data-post-id="<?= $post['id'] ?>">
    <div class="post-header">
        <img src="<?= getUserAvatar($post, 48) ?>" class="post-avatar" alt="Avatar">
        <div class="post-user-info">
            <a href="profile.php?id=<?= $post['user_id'] ?>" class="post-username">
                <?= escape($post['nickname'] ?: $post['username']) ?>
            </a>
            <div class="post-time"><?= timeAgo($post['created_at']) ?></div>
        </div>
        <span class="category-badge">
            <span><?= getCategoryEmoji($post['category_slug']) ?></span>
            <?= escape($post['category_name']) ?>
        </span>
    </div>

    <a href="post.php?id=<?= $post['id'] ?>">
        <img src="<?= SITE_URL . $post['image_path'] ?>" class="post-image" alt="Meme">
    </a>

    <div class="post-body">
        <h3 class="post-title"><?= escape($post['title'] ?: 'Untitled') ?></h3>
        
        <?php if (!empty($post['description'])): ?>
            <p class="post-description"><?= escape(substr($post['description'], 0, 150)) ?></p>
        <?php endif; ?>

        <div class="post-actions">
            <div class="vote-group">
                <button class="vote-btn upvote <?= ($post['user_vote'] ?? 0) === 1 ? 'active' : '' ?>"
                        data-post-id="<?= $post['id'] ?>" data-vote="up">
                    <i class="bi bi-arrow-up"></i>
                </button>
                <span class="vote-count" id="vote-<?= $post['id'] ?>"><?= $post['vote_score'] ?? 0 ?></span>
                <button class="vote-btn downvote <?= ($post['user_vote'] ?? 0) === -1 ? 'active' : '' ?>"
                        data-post-id="<?= $post['id'] ?>" data-vote="down">
                    <i class="bi bi-arrow-down"></i>
                </button>
            </div>
            <a href="post.php?id=<?= $post['id'] ?>" class="comment-btn">
                <i class="bi bi-chat"></i> <span><?= $post['comment_count'] ?? 0 ?></span>
            </a>
        </div>
    </div>
</div>