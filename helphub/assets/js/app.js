/**
 * HELPHUB 2.0 PRO - Universal Frontend Application Logic
 * "Connect. Help. Make a Difference."
 */

const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);

// Global State
let currentUser = null;
let activePollingTimer = null;
let activeChatConvId = null;

// ============================================================
// TOAST NOTIFICATION ENGINE
// ============================================================
function showToast(message, type = 'success') {
  let container = $('#toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    document.body.appendChild(container);
  }

  const icons = {
    success: '✅',
    error: '❌',
    warning: '⚠️',
    info: 'ℹ️'
  };

  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.innerHTML = `<span>${icons[type] || '🔔'}</span> <span>${message}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(20px)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

// ============================================================
// UNIVERSAL API CALLER
// ============================================================
async function apiFetch(endpoint, options = {}) {
  try {
    const res = await fetch(endpoint, {
      ...options,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...(options.headers || {})
      }
    });

    const data = await res.json().catch(() => ({ success: false, message: 'Server communication error' }));
    if (!res.ok) {
      throw new Error(data.message || 'Operation failed');
    }
    return data;
  } catch (err) {
    console.warn(`[HelpHub API error: ${endpoint}]`, err.message);
    throw err;
  }
}

// ============================================================
// MODAL CONTROLS
// ============================================================
function openModal(id) {
  const el = $(id);
  if (el) el.classList.remove('hidden');
}

function closeModal(id) {
  const el = $(id);
  if (el) el.classList.add('hidden');
}

// ============================================================
// AUTH & HEADER STATE
// ============================================================
async function initSession() {
  try {
    const res = await apiFetch('api/auth.php?action=me');
    if (res && res.data && res.data.user) {
      currentUser = res.data.user;
      renderAuthenticatedHeader();
      startNotificationPolling();
    } else {
      renderGuestHeader();
    }
  } catch (e) {
    renderGuestHeader();
  }
}

function renderAuthenticatedHeader() {
  const actions = $('#navActions');
  if (!actions) return;

  const isAdmin = currentUser.role === 'admin';
  actions.innerHTML = `
    <button class="icon-btn" id="themeToggleBtn" title="Toggle Dark/Light Mode">☾</button>
    <a href="messages/index.php" class="icon-btn" title="Messages">
      💬
      <span class="badge-counter hidden" id="msgUnreadBadge">0</span>
    </a>
    <div style="position:relative;">
      <button class="icon-btn" id="notifBellBtn" title="Notifications">
        🔔
        <span class="badge-counter hidden" id="notifUnreadBadge">0</span>
      </button>
      <div id="notifDropdown" class="hidden" style="position:absolute; right:0; top:48px; width:320px; background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); box-shadow:var(--shadow-lg); z-index:300; padding:14px; max-height:400px; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; border-bottom:1px solid var(--border-color); padding-bottom:8px;">
          <b style="font-size:13.5px;">Notifications</b>
          <button style="font-size:11.5px; color:var(--primary);" onclick="markAllNotifsRead()">Mark all read</button>
        </div>
        <div id="notifList" style="font-size:13px; color:var(--text-muted);">No new notifications</div>
      </div>
    </div>
    <div style="display:flex; align-items:center; gap:8px;">
      <a href="profile/profile.php?id=${currentUser.id}" class="user-mini" title="My Profile">
        <div class="avatar avatar-sm">${currentUser.full_name ? currentUser.full_name.charAt(0).toUpperCase() : 'U'}</div>
        <span style="font-weight:700; font-size:13.5px;">${currentUser.full_name.split(' ')[0]}</span>
      </a>
      ${isAdmin ? '<a href="admin/" class="btn btn-sm btn-soft">⚡ Admin</a>' : ''}
      <button class="btn btn-sm btn-outline" onclick="handleLogout()">Logout</button>
    </div>
  `;

  bindThemeButton();
  bindNotifDropdown();
}

function renderGuestHeader() {
  const actions = $('#navActions');
  if (!actions) return;

  actions.innerHTML = `
    <button class="icon-btn" id="themeToggleBtn" title="Toggle Dark/Light Mode">☾</button>
    <a href="auth/login.php" class="btn btn-outline btn-sm">Login</a>
    <a href="auth/register.php" class="btn btn-primary btn-sm">Register</a>
  `;
  bindThemeButton();
}

async function handleLogout() {
  try {
    await apiFetch('api/auth.php?action=logout');
  } catch (e) {}
  showToast('Logged out successfully', 'info');
  setTimeout(() => location.href = 'index.php', 400);
}

// ============================================================
// THEME SWITCHER
// ============================================================
function initTheme() {
  const saved = localStorage.getItem('helphub_theme');
  if (saved === 'dark') {
    document.body.classList.add('dark');
  }
}

function bindThemeButton() {
  const btn = $('#themeToggleBtn');
  if (!btn) return;
  const isDark = document.body.classList.contains('dark');
  btn.textContent = isDark ? '☀' : '☾';

  btn.onclick = () => {
    document.body.classList.toggle('dark');
    const nowDark = document.body.classList.contains('dark');
    localStorage.setItem('helphub_theme', nowDark ? 'dark' : 'light');
    btn.textContent = nowDark ? '☀' : '☾';
  };
}

// ============================================================
// NOTIFICATIONS POLLING
// ============================================================
function startNotificationPolling() {
  loadNotifications();
  if (activePollingTimer) clearInterval(activePollingTimer);
  activePollingTimer = setInterval(loadNotifications, 10000);
}

async function loadNotifications() {
  if (!currentUser) return;
  try {
    const res = await apiFetch('api/notifications.php');
    if (res && res.data) {
      const badge = $('#notifUnreadBadge');
      const list = $('#notifList');
      const count = res.data.unread_count || 0;

      if (badge) {
        badge.textContent = count;
        badge.classList.toggle('hidden', count === 0);
      }

      if (list && res.data.notifications) {
        if (res.data.notifications.length === 0) {
          list.innerHTML = '<p style="padding:10px; text-align:center;">No notifications yet.</p>';
        } else {
          list.innerHTML = res.data.notifications.slice(0, 8).map(n => `
            <div style="padding:8px 0; border-bottom:1px solid var(--border-color); ${n.read_status ? 'opacity:0.65;' : 'font-weight:700;'}">
              <a href="${n.link || '#'}" style="display:block;">
                <p style="margin-bottom:2px; color:var(--text-main);">${n.title}</p>
                <small style="color:var(--text-muted); font-size:11.5px;">${n.message}</small>
              </a>
            </div>
          `).join('');
        }
      }
    }
  } catch (e) {}
}

function bindNotifDropdown() {
  const btn = $('#notifBellBtn');
  const dropdown = $('#notifDropdown');
  if (btn && dropdown) {
    btn.onclick = (e) => {
      e.stopPropagation();
      dropdown.classList.toggle('hidden');
    };
    document.addEventListener('click', (e) => {
      if (!dropdown.contains(e.target)) {
        dropdown.classList.add('hidden');
      }
    });
  }
}

async function markAllNotifsRead() {
  try {
    await apiFetch('api/notifications.php?action=read_all', { method: 'POST' });
    const badge = $('#notifUnreadBadge');
    if (badge) badge.classList.add('hidden');
    loadNotifications();
  } catch (e) {}
}

// ============================================================
// INSTANT MESSAGING LAUNCHER
// ============================================================
async function openChatWithUser(targetUserId, targetUserName = 'User', requestId = null) {
  if (!currentUser) {
    showToast('Please sign in to contact this member', 'warning');
    setTimeout(() => location.href = 'auth/login.php', 1000);
    return;
  }

  try {
    const res = await apiFetch('api/messages.php?action=start', {
      method: 'POST',
      body: JSON.stringify({
        target_user_id: targetUserId,
        request_id: requestId,
        initial_message: requestId ? `Hello! I am contacting you regarding your HelpHub request.` : `Hello ${targetUserName}!`
      })
    });

    if (res && res.data && res.data.conversation_id) {
      location.href = `messages/index.php?conv=${res.data.conversation_id}`;
    }
  } catch (err) {
    showToast(err.message || 'Could not open chat', 'error');
  }
}

// ============================================================
// HELP REQUEST ACTIONS
// ============================================================
async function updateRequestStatus(requestId, newStatus, helperId = null) {
  try {
    const res = await apiFetch('api/requests.php?action=update_status', {
      method: 'POST',
      body: JSON.stringify({ request_id: requestId, status: newStatus, helper_id: helperId })
    });
    showToast(res.message || 'Status updated', 'success');
    setTimeout(() => location.reload(), 800);
  } catch (err) {
    showToast(err.message || 'Failed to update request', 'error');
  }
}

// Rating Modal Trigger
function openReviewModal(requestId, revieweeId, revieweeName) {
  const modal = $('#reviewModal');
  if (!modal) return;
  $('#reviewRequestId').value = requestId;
  $('#reviewRevieweeId').value = revieweeId;
  $('#revieweeTargetName').textContent = revieweeName;
  openModal('#reviewModal');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initSession();

  // Close modals on data-close
  $$('[data-close]').forEach(btn => {
    btn.onclick = () => {
      const modal = btn.closest('.modal');
      if (modal) modal.classList.add('hidden');
    };
  });

  // Modal backdrop click
  $$('.modal').forEach(m => {
    m.addEventListener('click', e => {
      if (e.target === m) m.classList.add('hidden');
    });
  });
});
