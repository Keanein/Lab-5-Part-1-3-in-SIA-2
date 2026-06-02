<?php
// messages.php - PHASE 8 PROFESSOR'S VERSION (FIXED SENDING)

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$with = isset($_GET['with']) ? (int)$_GET['with'] : 0;

// Get all users except current user
$stmt = $pdo->prepare("SELECT id, username, nickname, avatar FROM users WHERE id != ? ORDER BY username ASC");
$stmt->execute([$user_id]);
$users = $stmt->fetchAll();

// Get conversation partner info
$partner = null;
$messages = [];

if ($with > 0) {
    $stmt = $pdo->prepare("SELECT id, username, nickname, avatar FROM users WHERE id = ?");
    $stmt->execute([$with]);
    $partner = $stmt->fetch();
    
    if ($partner) {
        // Get messages between users
        $stmt = $pdo->prepare("
            SELECT m.*, u.username, u.avatar, u.nickname
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$user_id, $with, $with, $user_id]);
        $messages = $stmt->fetchAll();
        
        // Mark messages from partner as read
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?")->execute([$with, $user_id]);
    }
}
?>

<div class="row g-4">
    <!-- Left Sidebar -->
    <div class="col-lg-3">
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="col-lg-9">
        <div class="row g-0">
            <!-- Conversations List -->
            <div class="col-md-4 border-end">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0"><i class="bi bi-chat-dots"></i> Messages</h5>
                </div>
                <div class="conversations-list" style="max-height: 500px; overflow-y: auto;">
                    <?php foreach ($users as $u): ?>
                        <a href="messages.php?with=<?= $u['id'] ?>" 
                           class="conversation-item <?= ($with == $u['id']) ? 'active' : '' ?>">
                            <img src="<?= getUserAvatar($u, 48) ?>" class="rounded-circle" width="48" height="48">
                            <div class="conversation-info">
                                <div class="conversation-name"><?= escape($u['nickname'] ?: $u['username']) ?></div>
                                <div class="conversation-preview">Click to chat</div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    
                    <?php if (empty($users)): ?>
                        <div class="text-center py-4 text-muted">No other users yet</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="col-md-8">
                <?php if (!$partner): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-chat-dots fs-1 text-muted"></i>
                        <p class="mt-3">Select a conversation to start messaging</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center gap-2 p-3 border-bottom">
                        <img src="<?= getUserAvatar($partner, 40) ?>" class="rounded-circle" width="40" height="40">
                        <h5 class="mb-0"><?= escape($partner['nickname'] ?: $partner['username']) ?></h5>
                        <button class="btn btn-sm btn-outline-danger ms-auto" id="deleteConversationBtn">
                            <i class="bi bi-trash"></i> Delete Chat
                        </button>
                    </div>

                    <div id="chatMessages" class="chat-messages" style="height: 450px; overflow-y: auto; padding: 1rem;">
                        <?php foreach ($messages as $msg): ?>
                            <div class="message <?= ($msg['sender_id'] == $user_id) ? 'message-out' : 'message-in' ?>">
                                <div class="message-bubble">
                                    <div class="message-text"><?= escape($msg['message']) ?></div>
                                    <div class="message-time"><?= timeAgo($msg['created_at']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="chat-input-area p-3 border-top">
                        <form id="messageForm" class="d-flex gap-2">
                            <input type="hidden" name="receiver_id" value="<?= $with ?>">
                            <input type="text" name="message" id="messageInput" class="form-control" placeholder="Type a message..." required>
                            <button type="submit" class="btn btn-primary">Send</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const siteBase = '<?= SITE_URL ?>';
const currentUserId = <?= $user_id ?>;
const activePeerId = <?= $with ?>;
let pollInterval = null;

function scrollToBottom() {
    const container = document.getElementById('chatMessages');
    if (container) container.scrollTop = container.scrollHeight;
}
scrollToBottom();

// Send message - FIXED VERSION
document.getElementById('messageForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const receiverId = document.querySelector('input[name="receiver_id"]').value;
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();
    
    if (!message) {
        alert('Please enter a message');
        return;
    }
    
    // Create FormData for URL-encoded submission
    const formData = new URLSearchParams();
    formData.append('receiver_id', receiverId);
    formData.append('message', message);
    
    try {
        const response = await fetch(siteBase + 'api/send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            // Clear input
            messageInput.value = '';
            // Reload to show new message
            location.reload();
        } else {
            alert(data.error || 'Failed to send message');
        }
    } catch (err) {
        console.error('Send error:', err);
        alert('Network error. Please try again.');
    }
});

// Poll for new messages
if (activePeerId) {
    pollInterval = setInterval(async () => {
        try {
            const response = await fetch(siteBase + `api/get_messages.php?with=${activePeerId}`);
            const data = await response.json();
            if (data.success) {
                // Check if new messages arrived
                const container = document.getElementById('chatMessages');
                const currentMsgCount = container.querySelectorAll('.message').length;
                if (data.messages.length > currentMsgCount) {
                    location.reload();
                }
            }
        } catch (err) {
            console.error('Polling error:', err);
        }
    }, 3000);
}

// Delete conversation
document.getElementById('deleteConversationBtn')?.addEventListener('click', async () => {
    if (!confirm('Delete this entire conversation? This cannot be undone.')) return;
    
    try {
        const response = await fetch(siteBase + 'api/delete_conversation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: activePeerId })
        });
        const data = await response.json();
        if (data.success) {
            window.location.href = siteBase + 'messages.php';
        } else {
            alert('Failed to delete conversation');
        }
    } catch (err) {
        alert('Network error');
    }
});
</script>

<style>
.conversation-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    text-decoration: none;
    border-bottom: 1px solid var(--border-color);
    transition: background 0.2s;
}

.conversation-item:hover {
    background: var(--bg-primary);
}

.conversation-item.active {
    background: var(--primary-light);
    border-left: 3px solid var(--primary);
}

.conversation-info {
    flex: 1;
}

.conversation-name {
    font-weight: 600;
    color: var(--text-primary);
}

.conversation-preview {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.chat-messages {
    background: var(--bg-primary);
}

.message {
    display: flex;
    margin-bottom: 1rem;
}

.message-out {
    justify-content: flex-end;
}

.message-in {
    justify-content: flex-start;
}

.message-bubble {
    max-width: 70%;
    padding: 0.5rem 1rem;
    border-radius: 18px;
}

.message-out .message-bubble {
    background: var(--primary);
    color: white;
}

.message-in .message-bubble {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
}

.message-time {
    font-size: 0.65rem;
    margin-top: 0.25rem;
    opacity: 0.7;
    text-align: right;
}

.chat-input-area {
    background: var(--bg-card);
}
</style>

<?php require_once 'includes/footer.php'; ?>