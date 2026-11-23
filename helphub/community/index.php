<?php
$pageTitle = "Community Feed & Stories | HELPHUB";
require_once __DIR__ . '/../includes/header.php';
$pdo = get_pdo();
?>

<main class="feed-layout">
  <!-- LEFT: COMMUNITY TOPICS -->
  <aside class="feed-sidebar">
    <div class="card" style="padding:22px; margin-bottom:18px;">
      <h3 style="font-size:16px; margin-bottom:12px;">Community Topics</h3>
      <div style="display:flex; flex-direction:column; gap:8px; font-size:13.5px; font-weight:600;">
        <button onclick="filterFeed('All')" class="btn btn-outline btn-sm" style="justify-content:flex-start;">🌟 All Stories</button>
        <button onclick="filterFeed('Success Story')" class="btn btn-outline btn-sm" style="justify-content:flex-start;">🎉 Success Stories</button>
        <button onclick="filterFeed('Announcement')" class="btn btn-outline btn-sm" style="justify-content:flex-start;">📢 Announcements</button>
        <button onclick="filterFeed('Tips & Advice')" class="btn btn-outline btn-sm" style="justify-content:flex-start;">💡 Tips & Experiences</button>
      </div>
    </div>
  </aside>

  <!-- CENTER: POST CREATOR & STREAM -->
  <section>
    <!-- POST COMPOSER -->
    <?php if (is_logged_in()): ?>
      <div class="card" style="padding:24px; margin-bottom:24px;">
        <h4 style="font-size:16px; margin-bottom:12px;">Share an Update or Impact Story</h4>
        <form id="createPostForm">
          <input name="title" placeholder="Catchy title (e.g. Thanks to Rahul for repairing our school PC!)" style="width:100%; padding:10px 14px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); margin-bottom:12px;" required>
          <textarea name="content" placeholder="Share your experience, helpful advice, or volunteer appreciation..." style="width:100%; min-height:80px; padding:12px 14px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); margin-bottom:14px;" required></textarea>
          
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <select name="category" style="padding:8px 12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card);">
              <option value="Success Story">🎉 Success Story</option>
              <option value="Announcement">📢 Announcement</option>
              <option value="Tips & Advice">💡 Tips & Experiences</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Publish Story</button>
          </div>
        </form>
      </div>
    <?php else: ?>
      <div class="card" style="padding:20px; text-align:center; margin-bottom:24px;">
        <p style="color:var(--text-muted);">Sign in to share your community experiences and tips.</p>
        <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-primary btn-sm" style="margin-top:8px;">Sign In to Post</a>
      </div>
    <?php endif; ?>

    <!-- POSTS STREAM -->
    <div id="postsStream">
      <div style="text-align:center; padding:30px; color:var(--text-muted);">Loading community stories...</div>
    </div>
  </section>

  <!-- RIGHT: COMMUNITY SPOTLIGHT -->
  <aside class="feed-sidebar">
    <div class="card" style="padding:22px;">
      <h3 style="font-size:16px; margin-bottom:12px;">Community Guidelines</h3>
      <ul style="padding-left:18px; font-size:13px; color:var(--text-muted); line-height:1.6;">
        <li style="margin-bottom:8px;">Be respectful and compassionate to all community members.</li>
        <li style="margin-bottom:8px;">Never share private financial account details openly.</li>
        <li style="margin-bottom:8px;">Report suspicious requests or unverified medical campaigns.</li>
        <li>Help whenever you can, even with a small act of kindness.</li>
      </ul>
    </div>
  </aside>
</main>

<script>
let currentFeedCategory = 'All';

async function loadFeed() {
  try {
    const res = await apiFetch(`<?= BASE_URL ?>api/community.php?category=${encodeURIComponent(currentFeedCategory)}`);
    const container = $('#postsStream');
    const posts = res.data.posts || [];

    if (posts.length === 0) {
      container.innerHTML = '<div class="card" style="padding:30px; text-align:center; color:var(--text-muted);">No posts in this category yet. Be the first to share!</div>';
      return;
    }

    container.innerHTML = posts.map(p => `
      <article class="post-card">
        <div class="post-header">
          <div class="avatar">${p.author_name ? p.author_name[0] : 'U'}</div>
          <div>
            <div style="display:flex; align-items:center; gap:8px;">
              <strong style="font-size:15px;">${p.author_name}</strong>
              <span class="pill" style="font-size:11px; background:var(--bg-card-subtle);">${p.category}</span>
            </div>
            <small style="color:var(--text-muted); font-size:12px;">@${p.author_username} • ${p.author_city || 'Neighborhood'}</small>
          </div>
        </div>

        <h3 style="font-size:17px; margin-bottom:8px;">${p.title}</h3>
        <p style="font-size:14.5px; line-height:1.6; color:var(--text-main); margin-bottom:14px; white-space:pre-line;">${p.content}</p>

        <div class="post-actions">
          <div class="action-item ${p.user_liked ? 'liked' : ''}" onclick="toggleLike(${p.id})">
            <span>${p.user_liked ? '❤️' : '🤍'}</span>
            <span>${p.likes_count || 0} Likes</span>
          </div>
          <div class="action-item" onclick="toggleComments(${p.id})">
            <span>💬</span>
            <span>${p.comments_count || 0} Comments</span>
          </div>
          <div class="action-item" style="margin-left:auto;" onclick="reportPost(${p.id})">
            <span>⚠️ Report</span>
          </div>
        </div>

        <div id="commentsSection_${p.id}" class="hidden" style="margin-top:16px; border-top:1px solid var(--border-color); padding-top:14px;">
          <div id="commentsList_${p.id}" style="display:grid; gap:8px; font-size:13px; margin-bottom:12px;"></div>
          <div style="display:flex; gap:8px;">
            <input type="text" id="commentInput_${p.id}" placeholder="Write a kind comment..." style="flex:1; padding:8px 12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); font-size:13px;">
            <button class="btn btn-primary btn-sm" onclick="submitComment(${p.id})">Reply</button>
          </div>
        </div>
      </article>
    `).join('');
  } catch (e) {}
}

function filterFeed(cat) {
  currentFeedCategory = cat;
  loadFeed();
}

async function toggleLike(postId) {
  if (!currentUser) return showToast('Please sign in to like stories', 'warning');
  try {
    await apiFetch('<?= BASE_URL ?>api/community.php?action=like', {
      method: 'POST',
      body: JSON.stringify({ post_id: postId })
    });
    loadFeed();
  } catch (e) {}
}

async function toggleComments(postId) {
  const section = $(`#commentsSection_${postId}`);
  if (!section) return;
  const isHidden = section.classList.contains('hidden');
  section.classList.toggle('hidden');

  if (isHidden) {
    try {
      const res = await apiFetch(`<?= BASE_URL ?>api/community.php?action=get_comments&post_id=${postId}`);
      const list = $(`#commentsList_${postId}`);
      const comments = res.data.comments || [];
      list.innerHTML = comments.length === 0 ? '<p style="color:var(--text-muted);">No comments yet.</p>' : comments.map(c => `
        <div style="background:var(--bg-card-subtle); padding:8px 12px; border-radius:var(--radius-sm);">
          <strong>${c.user_name}</strong>: <span>${c.content}</span>
        </div>
      `).join('');
    } catch (e) {}
  }
}

async function submitComment(postId) {
  if (!currentUser) return showToast('Please sign in to comment', 'warning');
  const input = $(`#commentInput_${postId}`);
  const text = input.value.trim();
  if (!text) return;

  try {
    await apiFetch('<?= BASE_URL ?>api/community.php?action=comment', {
      method: 'POST',
      body: JSON.stringify({ post_id: postId, content: text })
    });
    input.value = '';
    toggleComments(postId);
    loadFeed();
  } catch (e) {
    showToast(e.message, 'error');
  }
}

function reportPost(postId) {
  const reason = prompt('Specify reason for reporting this post:');
  if (reason) {
    apiFetch('<?= BASE_URL ?>api/safety.php?action=report', {
      method: 'POST',
      body: JSON.stringify({ item_type: 'post', item_id: postId, reason: reason })
    }).then(res => showToast(res.message, 'success')).catch(e => showToast(e.message, 'error'));
  }
}

if ($('#createPostForm')) {
  $('#createPostForm').onsubmit = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());
    try {
      const res = await apiFetch('<?= BASE_URL ?>api/community.php?action=create_post', {
        method: 'POST',
        body: JSON.stringify(payload)
      });
      showToast(res.message, 'success');
      e.target.reset();
      loadFeed();
    } catch (err) {
      showToast(err.message, 'error');
    }
  };
}

document.addEventListener('DOMContentLoaded', loadFeed);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
