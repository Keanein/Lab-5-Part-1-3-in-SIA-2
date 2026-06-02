<?php
    require_once 'includes/header.php';
    
    if (!isLoggedIn()) redirect('login.php');
    $categories = $conn->query("SELECT id, name FROM categories ORDER BY name");
?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="form-card">
            <h3><i class="bi bi-cloud-upload"></i> Share a meme</h3>
            <div id="uploadAlert" class="alert d-none"></div>
            
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Title(optional)</label>
                    <input type="text" class="form-control" name="title" placeholder="Enter a catchy title">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Write a caption..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']);?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Image</label>
                    <input type="file" class="form-control" name="image" accept="image/*" required>
                    <div class="form-text">JPG, PNG, GIF up to 5MB</div>
                </div>
                <div id="previewContainer" class="mb-3 text-center" style="display: none;">
                    <img id="preview" src="#" alt="Preview"style=" max-height: 200px;">
                </div>
                <button type="submit" class="btn btn-primary w-100">Upload</button>
            </form>
        </div>
    </div>
</div>
<script>
    document.getElementById('uploadForm').addEventListener('change', function(e) {
        const file = document.querySelector('input[name="image"]').files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
                document.getElementById('previewContainer').style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('previewContainer').style.display = 'none';
        }
    });

    document.getElementById('uploadForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const alertDiv = document.getElementById('uploadAlert');
        alertDiv.classList.add('d-none');
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Uploading...';
        
        try {
            const response = await fetch('<?php echo BASE_URL;?>/api/upload.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (response.ok) {
                alertDiv.classList.remove('d-none', 'alert-danger');
                alertDiv.classList.add('alert-success');
                alertDiv.textContent = 'Upload successful! Redirecting...';
                setTimeout(() => window.location.href = '<?php echo BASE_URL; ?>/post.php?id=' + result.post_id, 1500);
            } else {
                alertDiv.classList.remove('d-none', 'alert-success');
                alertDiv.classList.add('alert-danger');
                alertDiv.textContent = result.error || 'Upload failed';
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Upload';
            }
        } catch (error) {
            alertDiv.classList.remove('d-none', 'alert-success');
            alertDiv.classList.add('alert-danger');
            alertDiv.textContent = 'Network error';
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Upload';
            }
    });
</script>
<?php require_once 'includes/footer.php'; ?>