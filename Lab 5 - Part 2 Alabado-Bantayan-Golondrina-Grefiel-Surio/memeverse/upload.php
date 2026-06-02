<?php
// upload.php - COMPLETE FIXED VERSION
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div class="row g-4">
    <!-- Left Sidebar -->
    <div class="col-lg-3">
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="col-lg-6">
        <div class="profile-header mb-4">
            <div class="d-flex align-items-center gap-3">
                <div style="font-size: 3rem;">📤</div>
                <div>
                    <h2 class="mb-0">Upload a Meme</h2>
                    <p class="text-muted mb-0">Share your humor with the world!</p>
                </div>
            </div>
        </div>

        <div class="post-card">
            <div class="post-body">
                <div id="message" class="alert d-none"></div>
                
                <form id="uploadForm" enctype="multipart/form-data">
                    <!-- Image Upload -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Select Image</label>
                        <input type="file" name="image" id="imageInput" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" required>
                        <small class="text-muted">Max 5MB. Allowed: JPG, PNG, GIF, WEBP</small>
                    </div>

                    <!-- Image Preview -->
                    <div id="imagePreview" class="mb-4 text-center" style="display: none;">
                        <img id="previewImg" class="img-fluid rounded" style="max-height: 300px;">
                    </div>

                    <!-- Title -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Title (Optional)</label>
                        <input type="text" name="title" class="form-control" placeholder="Give your meme a catchy title">
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Tell us about this meme..."></textarea>
                    </div>

                    <!-- Category Dropdown -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= getCategoryEmoji($cat['slug']) ?> <?= escape($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-create w-100" id="submitBtn">
                        <i class="bi bi-cloud-upload"></i> Upload Meme
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Sidebar - Tips -->
    <div class="col-lg-3">
        <div class="sidebar-card">
            <div class="sidebar-title">
                <i class="bi bi-info-circle"></i> UPLOAD TIPS
            </div>
            <div class="category-list">
                <div class="category-link" style="cursor: default;">
                    <span class="emoji">🎯</span> Use high-quality images
                </div>
                <div class="category-link" style="cursor: default;">
                    <span class="emoji">🏷️</span> Choose the right category
                </div>
                <div class="category-link" style="cursor: default;">
                    <span class="emoji">📝</span> Add a funny title
                </div>
                <div class="category-link" style="cursor: default;">
                    <span class="emoji">❤️</span> Good memes get more votes!
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Image preview
document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            previewImg.src = ev.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});

// Form submission
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = document.getElementById('submitBtn');
    const messageDiv = document.getElementById('message');
    const fileInput = document.getElementById('imageInput');
    const category = document.querySelector('select[name="category_id"]').value;
    
    // Validate file
    if (!fileInput.files.length) {
        messageDiv.className = 'alert alert-danger';
        messageDiv.innerHTML = 'Please select an image to upload';
        messageDiv.classList.remove('d-none');
        return;
    }
    
    // Validate category
    if (!category) {
        messageDiv.className = 'alert alert-danger';
        messageDiv.innerHTML = 'Please select a category';
        messageDiv.classList.remove('d-none');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Uploading...';
    messageDiv.classList.add('d-none');
    
    try {
        const response = await fetch(siteBase + 'api/upload.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            messageDiv.className = 'alert alert-success';
            messageDiv.innerHTML = 'Meme uploaded successfully! Redirecting...';
            messageDiv.classList.remove('d-none');
            setTimeout(() => {
                window.location.href = siteBase + 'index.php';
            }, 1500);
        } else {
            messageDiv.className = 'alert alert-danger';
            messageDiv.innerHTML = data.error || 'Upload failed. Please try again.';
            messageDiv.classList.remove('d-none');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload Meme';
        }
    } catch (error) {
        messageDiv.className = 'alert alert-danger';
        messageDiv.innerHTML = 'Network error. Please try again.';
        messageDiv.classList.remove('d-none');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-cloud-upload"></i> Upload Meme';
        console.error('Upload error:', error);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>