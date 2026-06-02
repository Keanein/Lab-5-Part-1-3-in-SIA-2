<?php
// admin/index.php - PHASE 11 PROFESSOR'S VERSION (FIXED)

require_once __DIR__ . '/api/check_admin.php';

try {
    // Collect system counts 
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $totalComments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    $totalVisits = $pdo->query("SELECT COUNT(*) FROM visits")->fetchColumn();

    // Daily visits for chart (last 7 days)
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM visits
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute();
    $dailyVisits = $stmt->fetchAll();
    $visitLabels = array_column($dailyVisits, 'date');
    $visitData = array_column($dailyVisits, 'count');

    // User growth (last 30 days)
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM users
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute();
    $userGrowth = $stmt->fetchAll();
    $userGrowthLabels = array_column($userGrowth, 'date');
    $userGrowthData = array_column($userGrowth, 'count');

    // Pull users log stack 
    $users = $pdo->query("SELECT id, username, nickname, email, banned, is_admin, created_at FROM users ORDER BY created_at DESC LIMIT 100")->fetchAll();

    // Pull categories
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

    // Pull comments log stack 
    $comments = $pdo->query("SELECT c.*, u.username, p.title as post_title FROM comments c JOIN users u ON c.user_id = u.id JOIN posts p ON c.post_id = p.id ORDER BY c.created_at DESC LIMIT 50")->fetchAll();

    // Pull pending reported items (FIXED: use reported_post_id)
    $reports = $pdo->query("
        SELECT r.*, u.username as reporter, p.title as post_title 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        LEFT JOIN posts p ON r.reported_post_id = p.id 
        WHERE r.status = 'pending' 
        ORDER BY r.created_at DESC
    ")->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MemeVerse Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #0d1117; color: #c9d1d9; font-family: system-ui, -apple-system, sans-serif; }
        .card-stat { background: linear-gradient(135deg, #21262d, #161b22); border: 1px solid #30363d; border-radius: 12px; }
        .nav-tabs .nav-link { color: #8b949e; border: none; font-weight: 600; }
        .nav-tabs .nav-link.active { color: #ff7b72; background-color: transparent; border-bottom: 3px solid #ff7b72; }
        .table-dark-custom { background-color: #161b22; color: #c9d1d9; border: 1px solid #30363d; }
        .table-dark-custom th { background-color: #21262d; border-color: #30363d; color: #f0f6fc; }
        .table-dark-custom td { border-color: #30363d; }
        .terminal-box { background-color: #010409; border: 1px solid #30363d; font-family: monospace; color: #58a6ff; max-height: 300px; overflow-y: auto; }
        .btn-icon { background: none; border: none; padding: 0.25rem; margin: 0 0.25rem; cursor: pointer; color: #8b949e; }
        .btn-icon:hover { color: #ff7b72; }
        .modal-content { background-color: #161b22; color: #c9d1d9; border: 1px solid #30363d; }
        .modal-header, .modal-footer { border-color: #30363d; }
        .form-control, .form-select { background-color: #0d1117; border-color: #30363d; color: #c9d1d9; }
        .form-control:focus, .form-select:focus { background-color: #0d1117; border-color: #ff7b72; color: #c9d1d9; }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <header class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary">
        <div>
            <h2 class="fw-bold text-white mb-0"><i class="bi bi-shield-lock-fill text-danger me-2"></i>MemeVerse Command Bridge</h2>
            <small class="text-muted font-monospace">System Admin Mode // Active Node Token Grid</small>
        </div>
        <a href="../index.php" class="btn btn-outline-light btn-sm rounded-pill px-4"><i class="bi bi-box-arrow-left me-1"></i> Return to Main Grid Feed</a>
    </header>

    <!-- Stats Cards -->
    <section class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card-stat p-3 shadow-sm d-flex align-items-center justify-content-between">
                <div><h6 class="text-muted text-uppercase small font-monospace">Registered Creators</h6><h3 class="fw-bold text-white mb-0"><?= number_format($totalUsers) ?></h3></div>
                <div class="fs-2 text-primary"><i class="bi bi-people"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stat p-3 shadow-sm d-flex align-items-center justify-content-between">
                <div><h6 class="text-muted text-uppercase small font-monospace">Meme Records</h6><h3 class="fw-bold text-white mb-0"><?= number_format($totalPosts) ?></h3></div>
                <div class="fs-2 text-success"><i class="bi bi-images"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stat p-3 shadow-sm d-flex align-items-center justify-content-between">
                <div><h6 class="text-muted text-uppercase small font-monospace">Thread Discussion Lines</h6><h3 class="fw-bold text-white mb-0"><?= number_format($totalComments) ?></h3></div>
                <div class="fs-2 text-warning"><i class="bi bi-chat-square-text"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-stat p-3 shadow-sm d-flex align-items-center justify-content-between">
                <div><h6 class="text-muted text-uppercase small font-monospace">Traffic Fingerprints</h6><h3 class="fw-bold text-white mb-0"><?= number_format($totalVisits) ?></h3></div>
                <div class="fs-2 text-danger"><i class="bi bi-activity"></i></div>
            </div>
        </div>
    </section>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card-stat p-3 shadow-sm">
                <h5 class="fw-bold text-white small text-uppercase font-monospace mb-3"><i class="bi bi-graph-up text-danger me-2"></i>Daily Visits (Last 7 Days)</h5>
                <div style="position: relative; height:200px; width:100%">
                    <canvas id="visitsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-stat p-3 shadow-sm">
                <h5 class="fw-bold text-white small text-uppercase font-monospace mb-3"><i class="bi bi-person-plus text-success me-2"></i>User Growth (Last 30 Days)</h5>
                <div style="position: relative; height:200px; width:100%">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Tabs -->
    <ul class="nav nav-tabs mb-4 border-secondary" id="adminTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-users" type="button">Users</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button">Categories</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-comments" type="button">Comments</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-reports" type="button">Reports (<?= count($reports) ?>)</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-system" type="button">System</button></li>
    </ul>

    <div class="tab-content" id="adminTabsContent">
        
        <!-- ========== USERS TAB ========== -->
        <div class="tab-pane fade show active" id="tab-users" role="tabpanel">
            <div class="card-stat p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-white mb-0"><i class="bi bi-people-fill me-2"></i>User Management</h5>
                    <button class="btn btn-sm btn-outline-info" id="exportUsersBtn"><i class="bi bi-download"></i> Export CSV</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark-custom table-hover align-middle">
                        <thead>
                            <tr><th>ID</th><th>Username</th><th>Nickname</th><th>Email</th><th>Admin</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr id="user-row-<?= $user['id'] ?>">
                                <td class="font-monospace text-muted small">#<?= $user['id'] ?></td>
                                <td class="fw-bold text-white"><?= escape($user['username']) ?></td>
                                <td><?= escape($user['nickname'] ?? '-') ?></td>
                                <td class="small"><?= escape($user['email']) ?></td>
                                <td>
                                    <span class="badge <?= $user['is_admin'] ? 'bg-danger' : 'bg-secondary' ?>">
                                        <?= $user['is_admin'] ? 'Admin' : 'User' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $user['banned'] ? 'bg-warning text-dark' : 'bg-success' ?> id-badge-<?= $user['id'] ?>">
                                        <?= $user['banned'] ? 'Banned' : 'Active' ?>
                                    </span>
                                </td>
                                <td class="small font-monospace"><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <button class="btn-icon edit-user" data-id="<?= $user['id'] ?>" data-username="<?= escape($user['username']) ?>" data-email="<?= escape($user['email']) ?>" data-nickname="<?= escape($user['nickname'] ?? '') ?>" data-admin="<?= $user['is_admin'] ?>"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="btn-icon toggle-ban-btn" data-id="<?= $user['id'] ?>"><i class="bi bi-<?= $user['banned'] ? 'unlock' : 'lock' ?>-fill"></i></button>
                                    <button class="btn-icon delete-user" data-id="<?= $user['id'] ?>"><i class="bi bi-trash-fill text-danger"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========== CATEGORIES TAB ========== -->
        <div class="tab-pane fade" id="tab-categories" role="tabpanel">
            <div class="card-stat p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-white mb-0"><i class="bi bi-tags-fill me-2"></i>Category Management</h5>
                    <button class="btn btn-sm btn-primary" id="addCategoryBtn"><i class="bi bi-plus-lg"></i> Add Category</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark-custom table-hover align-middle">
                        <thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr id="cat-row-<?= $cat['id'] ?>">
                                <td><?= $cat['id'] ?></td>
                                <td class="cat-name"><?= escape($cat['name']) ?></td>
                                <td class="cat-slug font-monospace"><?= escape($cat['slug']) ?></td>
                                <td>
                                    <button class="btn-icon edit-category" data-id="<?= $cat['id'] ?>" data-name="<?= escape($cat['name']) ?>" data-slug="<?= escape($cat['slug']) ?>"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="btn-icon delete-category" data-id="<?= $cat['id'] ?>"><i class="bi bi-trash-fill text-danger"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========== COMMENTS TAB ========== -->
        <div class="tab-pane fade" id="tab-comments" role="tabpanel">
            <div class="card-stat p-3">
                <h5 class="fw-bold text-white mb-3"><i class="bi bi-chat-dots-fill me-2"></i>Comment Moderation</h5>
                <div class="table-responsive">
                    <table class="table table-dark-custom table-hover align-middle">
                        <thead><tr><th>Comment</th><th>Author</th><th>Post</th><th>Created</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($comments as $comment): ?>
                            <tr id="comment-row-<?= $comment['id'] ?>">
                                <td class="small" style="max-width: 300px;"><?= escape(substr($comment['comment_text'], 0, 80)) ?>...</td>
                                <td class="font-monospace small text-info">@<?= escape($comment['username']) ?></td>
                                <td class="small text-truncate" style="max-width: 150px;"><?= escape($comment['post_title'] ?? 'Unknown') ?></td>
                                <td class="small font-monospace"><?= timeAgo($comment['created_at']) ?></td>
                                <td>
                                    <button class="btn-icon delete-comment-btn" data-id="<?= $comment['id'] ?>"><i class="bi bi-trash-fill text-danger"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========== REPORTS TAB ========== -->
        <div class="tab-pane fade" id="tab-reports" role="tabpanel">
            <div class="card-stat p-3">
                <h5 class="fw-bold text-white mb-3"><i class="bi bi-flag-fill me-2"></i>Reports Queue</h5>
                <?php if (empty($reports)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-shield-check fs-1 text-success"></i>
                        <p class="mt-2 mb-0">All clear! No pending reports.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark-custom table-hover align-middle">
                            <thead><tr><th>Reported Content</th><th>Reason</th><th>Reporter</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                <tr id="report-row-<?= $report['id'] ?>">
                                    <td><a href="../post.php?id=<?= $report['reported_post_id'] ?>" target="_blank" class="text-info">Post #<?= $report['reported_post_id'] ?></a></td>
                                    <td class="small"><?= escape($report['reason']) ?></td>
                                    <td class="font-monospace small">@<?= escape($report['reporter']) ?></td>
                                    <td><button class="btn btn-sm btn-success resolve-report-btn" data-id="<?= $report['id'] ?>"><i class="bi bi-check2"></i> Resolve</button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ========== SYSTEM TAB ========== -->
        <div class="tab-pane fade" id="tab-system" role="tabpanel">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card-stat p-3 h-100">
                        <h5 class="fw-bold text-white border-bottom border-secondary pb-2"><i class="bi bi-cpu-fill text-info me-2"></i>Environment</h5>
                        <ul class="list-unstyled font-monospace small pt-2 mb-0">
                            <li><strong>PHP Version:</strong> <?= phpversion(); ?></li>
                            <li><strong>MySQL Version:</strong> <?= $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); ?></li>
                            <li><strong>Server Software:</strong> <?= escape($_SERVER['SERVER_SOFTWARE'] ?? 'Apache') ?></li>
                            <li><strong>Upload Limit:</strong> <?= ini_get('upload_max_filesize'); ?></li>
                            <li><strong>Memory Limit:</strong> <?= ini_get('memory_limit'); ?></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card-stat p-3 h-100">
                        <h5 class="fw-bold text-white border-bottom border-secondary pb-2"><i class="bi bi-hdd-network-fill text-warning me-2"></i>Storage</h5>
                        <ul class="list-unstyled font-monospace small pt-2 mb-0">
                            <li><strong>Total Space:</strong> <?= round(@disk_total_space(__DIR__) / (1024 * 1024 * 1024), 2); ?> GB</li>
                            <li><strong>Free Space:</strong> <?= round(@disk_free_space(__DIR__) / (1024 * 1024 * 1024), 2); ?> GB</li>
                        </ul>
                        <hr class="border-secondary">
                        <h5 class="fw-bold text-white mb-2"><i class="bi bi-key-fill me-2"></i>Admin Security</h5>
                        <div class="mb-3">
                            <input type="password" id="adminCurrentPassword" class="form-control mb-2" placeholder="Current Password">
                            <input type="password" id="adminNewPassword" class="form-control mb-2" placeholder="New Password">
                            <input type="password" id="adminConfirmPassword" class="form-control mb-2" placeholder="Confirm Password">
                            <button class="btn btn-primary w-100" id="changeAdminPasswordBtn">Change Admin Password</button>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card-stat p-3">
                        <h5 class="fw-bold text-white mb-2"><i class="bi bi-terminal-fill text-danger me-2"></i>Error Log Preview</h5>
                        <div class="terminal-box p-3 rounded">
                            <?php 
                            $logPath = __DIR__ . '/../error_log.txt';
                            if (file_exists($logPath) && is_readable($logPath)) {
                                $lines = file($logPath);
                                $lastLines = array_slice($lines, -15);
                                echo nl2br(escape(implode("", $lastLines)));
                            } else {
                                echo "No error logs found.";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-fill"></i> Edit User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_user_id">
                <div class="mb-3"><label>Username</label><input type="text" id="edit_username" class="form-control"></div>
                <div class="mb-3"><label>Email</label><input type="email" id="edit_email" class="form-control"></div>
                <div class="mb-3"><label>Nickname</label><input type="text" id="edit_nickname" class="form-control"></div>
                <div class="mb-3">
                    <label>Admin Status</label>
                    <select id="edit_is_admin" class="form-select">
                        <option value="0">User</option>
                        <option value="1">Admin</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveUserBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-lg"></i> Add Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label>Name</label><input type="text" id="new_cat_name" class="form-control" placeholder="e.g., Funny"></div>
                <div class="mb-3"><label>Slug</label><input type="text" id="new_cat_slug" class="form-control" placeholder="e.g., funny"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitAddCategory">Add Category</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-fill"></i> Edit Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_cat_id">
                <div class="mb-3"><label>Name</label><input type="text" id="edit_cat_name" class="form-control"></div>
                <div class="mb-3"><label>Slug</label><input type="text" id="edit_cat_slug" class="form-control"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitEditCategory">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const siteBase = '<?= SITE_URL ?>';

    // ========== CHARTS ==========
    // Daily Visits Chart (Line Chart)
    const visitsCtx = document.getElementById('visitsChart').getContext('2d');
    new Chart(visitsCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($visitLabels) ?>,
            datasets: [{
                label: 'Visits',
                data: <?= json_encode($visitData) ?>,
                borderColor: '#ff6b6b',
                backgroundColor: 'rgba(255,107,107,0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });

    // User Growth Chart (Line Chart)
    const growthCtx = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($userGrowthLabels) ?>,
            datasets: [{
                label: 'New Users',
                data: <?= json_encode($userGrowthData) ?>,
                borderColor: '#2ea44f',
                backgroundColor: 'rgba(46,164,79,0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });

    // ========== USER MANAGEMENT ==========
    const editUserModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    
    document.querySelectorAll('.edit-user').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_user_id').value = btn.dataset.id;
            document.getElementById('edit_username').value = btn.dataset.username;
            document.getElementById('edit_email').value = btn.dataset.email;
            document.getElementById('edit_nickname').value = btn.dataset.nickname;
            document.getElementById('edit_is_admin').value = btn.dataset.admin;
            editUserModal.show();
        });
    });

    document.getElementById('saveUserBtn').addEventListener('click', async () => {
        const data = {
            id: document.getElementById('edit_user_id').value,
            username: document.getElementById('edit_username').value,
            email: document.getElementById('edit_email').value,
            nickname: document.getElementById('edit_nickname').value,
            is_admin: document.getElementById('edit_is_admin').value
        };
        try {
            const res = await fetch(siteBase + 'admin/api/edit_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) location.reload();
            else alert(result.error || 'Update failed');
        } catch (err) { alert('Network error'); }
    });

    // Ban/Unban Toggle
    document.querySelectorAll('.toggle-ban-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const userId = btn.dataset.id;
            if (!confirm('Toggle ban status for this user?')) return;
            try {
                const res = await fetch(siteBase + 'admin/api/ban_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: userId })
                });
                const data = await res.json();
                if (data.success) location.reload();
                else alert(data.error || 'Failed');
            } catch (err) { alert('Network error'); }
        });
    });

    // Delete User
    document.querySelectorAll('.delete-user').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Delete this user? All their posts and comments will be removed.')) return;
            const userId = btn.dataset.id;
            try {
                const res = await fetch(siteBase + 'admin/api/delete_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: userId })
                });
                const data = await res.json();
                if (data.success) location.reload();
                else alert(data.error || 'Delete failed');
            } catch (err) { alert('Network error'); }
        });
    });

    // Export Users CSV
    document.getElementById('exportUsersBtn')?.addEventListener('click', () => {
        window.location.href = siteBase + 'admin/api/export_users.php';
    });

    // ========== CATEGORY MANAGEMENT ==========
    const addCatModal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
    const editCatModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));

    document.getElementById('addCategoryBtn')?.addEventListener('click', () => addCatModal.show());

    document.getElementById('submitAddCategory')?.addEventListener('click', async () => {
        const name = document.getElementById('new_cat_name').value.trim();
        const slug = document.getElementById('new_cat_slug').value.trim().toLowerCase().replace(/\s+/g, '-');
        if (!name || !slug) { alert('Name and slug required'); return; }
        try {
            const res = await fetch(siteBase + 'admin/api/add_category.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, slug })
            });
            const data = await res.json();
            if (data.success) location.reload();
            else alert(data.error || 'Failed');
        } catch (err) { alert('Network error'); }
    });

    document.querySelectorAll('.edit-category').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_cat_id').value = btn.dataset.id;
            document.getElementById('edit_cat_name').value = btn.dataset.name;
            document.getElementById('edit_cat_slug').value = btn.dataset.slug;
            editCatModal.show();
        });
    });

    document.getElementById('submitEditCategory')?.addEventListener('click', async () => {
        const id = document.getElementById('edit_cat_id').value;
        const name = document.getElementById('edit_cat_name').value.trim();
        const slug = document.getElementById('edit_cat_slug').value.trim().toLowerCase().replace(/\s+/g, '-');
        if (!name || !slug) { alert('Name and slug required'); return; }
        try {
            const res = await fetch(siteBase + 'admin/api/edit_category.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, name, slug })
            });
            const data = await res.json();
            if (data.success) location.reload();
            else alert(data.error || 'Failed');
        } catch (err) { alert('Network error'); }
    });

    document.querySelectorAll('.delete-category').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Delete this category? Posts will become uncategorized.')) return;
            const id = btn.dataset.id;
            try {
                const res = await fetch(siteBase + 'admin/api/delete_category.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.success) location.reload();
                else alert(data.error || 'Failed');
            } catch (err) { alert('Network error'); }
        });
    });

    // ========== COMMENT MANAGEMENT ==========
    document.querySelectorAll('.delete-comment-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Delete this comment?')) return;
            const id = btn.dataset.id;
            try {
                const res = await fetch(siteBase + 'admin/api/delete_comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.success) document.getElementById(`comment-row-${id}`).remove();
                else alert(data.error || 'Failed');
            } catch (err) { alert('Network error'); }
        });
    });

    // ========== REPORT MANAGEMENT ==========
    document.querySelectorAll('.resolve-report-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            try {
                const res = await fetch(siteBase + 'admin/api/resolve_report.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.success) document.getElementById(`report-row-${id}`).remove();
                else alert(data.error || 'Failed');
            } catch (err) { alert('Network error'); }
        });
    });

    // ========== ADMIN PASSWORD CHANGE ==========
    document.getElementById('changeAdminPasswordBtn')?.addEventListener('click', async () => {
        const current = document.getElementById('adminCurrentPassword').value;
        const newPass = document.getElementById('adminNewPassword').value;
        const confirm = document.getElementById('adminConfirmPassword').value;
        if (newPass !== confirm) { alert('New passwords do not match'); return; }
        if (newPass.length < 6) { alert('Password must be at least 6 characters'); return; }
        try {
            const res = await fetch(siteBase + 'admin/api/change_admin_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ current, new_password: newPass })
            });
            const data = await res.json();
            if (data.success) {
                alert('Password updated successfully');
                document.getElementById('adminCurrentPassword').value = '';
                document.getElementById('adminNewPassword').value = '';
                document.getElementById('adminConfirmPassword').value = '';
            } else { alert(data.error || 'Update failed'); }
        } catch (err) { alert('Network error'); }
    });
});
</script>
</body>
</html>