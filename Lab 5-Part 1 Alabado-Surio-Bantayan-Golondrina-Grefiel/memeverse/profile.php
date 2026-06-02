<?php
    require_once 'includes/header.php';

    $user_id = isset($_GET['id']) ? (int)$_GET['id'] : (isLoggedIn() ? $_SESSION['user_id'] : 0);
    
    if ($user_id <= 0) {
        redirect('login.php');
    }

    $stmt = $conn->prepare("SELECT id, username, nickname, bio, profile_pic, created_at FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $userResult = $stmt->get_result();
    
    if ($userResult->num_rows === 0) {
        redirect('index.php');
    }

    $user = $userResult->fetch_assoc();
    $stmt->close();
?>
<!-- Profile Header (Instagram style) -->
<div class="profile-header-instagram">
    <div class="profile-header-row">
        <div class="profile-avatar">
            <?php if ($user['profile_pic']): ?>
                <img src="<?php echo BASE_URL . '/' . $user['profile_pic']; ?>" alt="Avatar">
            <?php else: ?>
                <div class="default-avatar"><i class="bi bi-person"></i></div>
            <?php endif; ?>
        </div>

        <div class="profile-info">
            <div class="profile-name-row">
                <h2 class="profile-username"><?php echo htmlspecialchars($user['nickname'] ?? $user['username']);?></h2>
                <?php if ($user['id'] == ($_SESSION['user_id']?? 0)): ?>
                    <button class="btn btn-outline-light btn-sm edit-profile-btn" data-bs-toggle="modal" data-bs-target="#editProfileModal">Edit Profile</button>
                <?php endif; ?>
            </div>
            <div class="profile-stats">
                <div class="stat">
                    <span class="stat-number" id="postCount">0</span>
                    <span class="stat-label">posts</span>
                </div>
                <div class="stat">
                    <span class="stat-number">0</span>
                    <span class="stat-label">followers</span>
                </div>
                <div class="stat">
                    <span class="stat-number">0</span>
                    <span class="stat-label">following</span>
                </div>
            </div>

            <?php if ($user['bio']): ?>
                <div class="profile-bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></div>
            <?php endif; ?>
            <div class="profile-joined">Joined <?php echo date('F Y', strtotime($user['created_at'])); ?></div>
        </div>
    </div>
</div>

<!-- Posts Grid -->
<h3 class="posts-title">Posts</h3>
<div id="user-posts" class="posts-grid-instagram"></div>
<div id="loading-spinner" class="text-center my-5" style="display: none;">
    <div class="spinner-border text-pastel-purple" role="status"></div>
</div>
<div id="end-message" class="text-center my-4 text-muted" style="display: none;">
    No more posts
</div>

<!-- Edit Profile Modal (same as before) -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content form-card">
            <div class="modal-header border-0">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editProfileForm">
                    <div class="mb-3">
                        <label class="form-label">Nickname</label>
                        <input type="text" class="form-control" id="editNickname" name="nickname" placeholder="Your display name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bio</label>
                        <textarea class="form-control" id="editBio" name="bio" rows="3" placeholder="Tell us about yourself..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" id="avatarFile" accept="image/*">
                        <div class="form-text">Max 2MB,JPG/PNG/GIF</div>
                        <div id="avatarPreview" class="mt-2 text-center" style="display: none;">
                            <img id="avatarPreviewImg" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveProfileBtn">Save Changes</button>
            </div>

        </div>
    </div>
</div>

<script>
    const userId = <?php echo $user_id; ?>;
    let page = 1;
    let loading = false;
    let hasMore = true;
    const container = document.getElementById('user-posts');
    const spinner = document.getElementById('loading-spinner');
    const endMessage = document.getElementById('end-message');
    
    async function loadUserPosts() {
    if (loading || !hasMore) return;
        loading = true;
        spinner.style.display = 'block';
        
        try {
            const response = await fetch(`<?php echo BASE_URL;?>/api/posts.php?page=${page}&limit=12&user_id=${userId}`);
            const data = await response.json();
            
            if (data.error) throw new Error(data.error);
            
            if (data.posts && data.posts.length > 0) {
                renderPosts(data.posts);
                page++;
                hasMore = data.pagination.has_more;
            } else {
                hasMore = false;
                if (page === 1) container.innerHTML = '<p class="text-muted text-center">No posts yet.</p>';
            }

            if (!hasMore) endMessage.style.display = 'block';
        } catch (error) {
            console.error('Error loading posts:', error);
            container.innerHTML = '<div class="alert alert-danger">Failed to load posts.</div>';
        } finally {
            loading = false;
            spinner.style.display = 'none';
        }
    }

    function renderPosts(posts) {
        posts.forEach(post => {
            const card = document.createElement('div');
            card.className = 'grid-item';
            const categoryIcon =
            getCategoryIcon(post.category.slug);
            card.innerHTML = `
                <a href="<?php echo BASE_URL;?>/post.php?id=${post.id}" class="grid-link">
                    <div class="grid-image">
                        <img src="${post.image_path}" alt="${post.title || 'Meme'}">
                    </div>
                    <div class="grid-overlay">
                        <div class="grid-stats">
                            <span><i class="bi bi-arrow-up"></i>${post.upvotes}</span>
                            <span><i class="bi bi-chat"></i>${post.comments}</span>
                        </div>
                    </div>
                </a>`;
        container.appendChild(card);
        });
    }

    function getCategoryIcon(slug) {
        const icons = {
            funny: 'bi-emoji-laughing',
            animals: 'bi-paw',
            music: 'bi-music-note-beamed',
            tv: 'bi-tv',
            games: 'bi-controller',
            movie: 'bi-film',
            sport: 'bi-trophy',
            science: 'bi-flask',
            history: 'bi-book'
        };
        
        return icons[slug] || 'bi-tag';
    }

    window.addEventListener('scroll', () => {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
            loadUserPosts();
        }
    });
    
    loadUserPosts();

    // Fetch post count
    fetch(`<?php echo BASE_URL;?>/api/posts.php?user_id=${userId}&limit=1&page=1`)
    .then(res => res.json())
    .then(data => {
        document.getElementById('postCount').textContent = data.pagination.total_posts || 0;
    });

    // Edit profile modal logic (unchanged)
    const editModal = document.getElementById('editProfileModal');
    editModal.addEventListener('show.bs.modal', async () => {
        const response = await fetch(`<?php echo BASE_URL;?>/api/profile.php?id=${userId}`);
        const data = await response.json();
        if (data.user) {
            document.getElementById('editNickname').value = data.user.nickname || '';
            document.getElementById('editBio').value = data.user.bio || '';
            
            if (data.user.profile_pic) {
                document.getElementById('avatarPreviewImg').src = data.user.profile_pic;
                document.getElementById('avatarPreview').style.display = 'block';
            } else {
                document.getElementById('avatarPreview').style.dis
                play = 'none';
            }
        }
    });
    
    document.getElementById('saveProfileBtn').addEventListener('click', async () => {
        const nickname = document.getElementById('editNickname').value;
        const bio = document.getElementById('editBio').value;
        const avatarFile = document.getElementById('avatarFile').files[0];
        const profileResponse = await fetch('<?php echo BASE_URL;?>/api/profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nickname, bio })
        });
        
        const profileResult = await profileResponse.json();
        if (!profileResult.success) {
            alert(profileResult.error || 'Failed to update profile');
            return;
        }

        if (avatarFile) {
            const formData = new FormData();
            formData.append('avatar', avatarFile);
            const avatarResponse = await fetch('<?php echo BASE_URL; ?>/api/upload_avatar.php', {
                method: 'POST',
                body: formData
            });

            const avatarResult = await avatarResponse.json();
            if (avatarResult.success) {
                const avatarDiv = document.querySelector('.profile-avatar');
                avatarDiv.innerHTML = `<img src="${avatarResult.avatar_url}?t=${Date.now()}" alt="Avatar">`;
            } else {
            alert(avatarResult.error || 'Avatar upload failed');
            }
        }

        location.reload();
    });

    document.getElementById('avatarFile').addEventListener('change ', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                document.getElementById('avatarPreviewImg').src = ev.target.result;
                document.getElementById('avatarPreview').style.display = 'block';
            };
            
            reader.readAsDataURL(file);
        } else {
            document.getElementById('avatarPreview').style.display = 'none';
        }
    });
    
</script>
<?php require_once 'includes/footer.php'; ?>

