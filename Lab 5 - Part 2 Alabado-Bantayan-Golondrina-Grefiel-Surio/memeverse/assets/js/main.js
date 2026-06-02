// assets/js/main.js - PHASE 4 PROFESSOR'S VERSION

// ========== MOBILE SIDEBAR ==========
function initMobileSidebar() {
    const hamburger = document.getElementById('hamburgerMenu');
    const sidebar = document.getElementById('mobileSidebar');
    const closeBtn = document.getElementById('closeSidebar');
    const overlay = document.getElementById('mobileSidebarOverlay');

    if (!hamburger || !sidebar || !closeBtn || !overlay) return;

    function openSidebar() {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    hamburger.addEventListener('click', openSidebar);
    closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    // Close sidebar when clicking a link
    sidebar.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', closeSidebar);
    });
}

// ========== GLOBAL VOTE HANDLER ==========
document.addEventListener('click', async function(e) {
    const voteBtn = e.target.closest('.vote-btn');
    if (!voteBtn) return;

    const postId = voteBtn.dataset.postId;
    const vote = voteBtn.dataset.vote;

    try {
        const formData = new URLSearchParams();
        formData.append('post_id', postId);
        formData.append('vote', vote);

        const response = await fetch(siteBase + 'api/vote.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            const voteCount = document.querySelector(`#vote-${postId}`);
            if (voteCount) voteCount.textContent = data.new_score;

            const upBtn = document.querySelector(`.vote-btn.upvote[data-post-id="${postId}"]`);
            const downBtn = document.querySelector(`.vote-btn.downvote[data-post-id="${postId}"]`);

            if (vote === 'up') {
                upBtn.classList.add('active');
                downBtn.classList.remove('active');
            } else {
                downBtn.classList.add('active');
                upBtn.classList.remove('active');
            }
        }
    } catch (error) {
        console.error('Vote error:', error);
    }
});

// ========== REPORT HANDLER ==========
document.addEventListener('click', async function(e) {
    const btn = e.target.closest('.report-btn');
    if (!btn) return;

    const type = btn.dataset.type;
    const id = btn.dataset.id;
    const reason = prompt('Why are you reporting this?');
    if (!reason) return;

    try {
        const res = await fetch(siteBase + 'api/report.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type, id, reason })
        });
        const data = await res.json();
        if (data.success) {
            alert('Report submitted. Thank you!');
        } else {
            alert(data.error || 'Failed to submit report');
        }
    } catch (err) {
        alert('Network error');
    }
});

// ========== INITIALIZE ==========
document.addEventListener('DOMContentLoaded', function() {
    initMobileSidebar();
});