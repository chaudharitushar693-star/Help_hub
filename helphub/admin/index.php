<?php
$pageTitle = "Admin Control Center | HELPHUB";
require_once __DIR__ . '/../includes/header.php';
require_admin();
$pdo = get_pdo();
?>

<main class="section">
  <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:28px; flex-wrap:wrap; gap:16px;">
    <div>
      <span class="eyebrow" style="color:var(--danger);">ADMINISTRATIVE CONSOLE</span>
      <h1 style="font-size:32px; font-family:var(--font-heading);">Platform Moderation & Metrics</h1>
      <p style="color:var(--text-muted);">Manage registered members, monitor reports, and verify community safety.</p>
    </div>
    <div style="display:flex; gap:10px;">
      <a href="<?= BASE_URL ?>dashboard/index.php" class="btn btn-outline btn-sm">User Dashboard</a>
      <button class="btn btn-primary btn-sm" onclick="loadAdminMetrics()">↻ Refresh Live Stats</button>
    </div>
  </div>

  <!-- ADMIN METRICS TILES -->
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:16px; margin-bottom:36px;">
    <div class="card" style="padding:20px;">
      <span style="font-size:12.5px; font-weight:700; color:var(--text-muted);">Total Users</span>
      <b style="font-size:26px; color:var(--primary); margin:4px 0;" id="mTotalUsers">-</b>
      <small style="color:var(--text-muted);">Registered accounts</small>
    </div>
    <div class="card" style="padding:20px;">
      <span style="font-size:12.5px; font-weight:700; color:var(--text-muted);">Help Requests</span>
      <b style="font-size:26px; color:var(--warning); margin:4px 0;" id="mHelpRequests">-</b>
      <small style="color:var(--text-muted);">All-time posted</small>
    </div>
    <div class="card" style="padding:20px;">
      <span style="font-size:12.5px; font-weight:700; color:var(--text-muted);">Completed Helps</span>
      <b style="font-size:26px; color:var(--success); margin:4px 0;" id="mCompletedHelps">-</b>
      <small style="color:var(--text-muted);">Resolved interactions</small>
    </div>
    <div class="card" style="padding:20px;">
      <span style="font-size:12.5px; font-weight:700; color:var(--text-muted);">Messages Sent</span>
      <b style="font-size:26px; color:#8b5cf6; margin:4px 0;" id="mMessagesCount">-</b>
      <small style="color:var(--text-muted);">Private chat volume</small>
    </div>
    <div class="card" style="padding:20px;">
      <span style="font-size:12.5px; font-weight:700; color:var(--text-muted);">Pending Reports</span>
      <b style="font-size:26px; color:var(--danger); margin:4px 0;" id="mPendingReports">-</b>
      <small style="color:var(--text-muted);">Requires moderation</small>
    </div>
    <div class="card" style="padding:20px;">
      <span style="font-size:12.5px; font-weight:700; color:var(--text-muted);">Emergency Alerts</span>
      <b style="font-size:26px; color:#dc2626; margin:4px 0;" id="mEmergencyRequests">-</b>
      <small style="color:var(--text-muted);">Urgent high priority</small>
    </div>
  </div>

  <!-- ADMIN TABS -->
  <div style="display:flex; border-bottom:1px solid var(--border-color); margin-bottom:24px; gap:8px;">
    <button class="btn btn-soft btn-sm active" id="tabUsersBtn" onclick="switchAdminTab('users')">👥 User Accounts</button>
    <button class="btn btn-outline btn-sm" id="tabReportsBtn" onclick="switchAdminTab('reports')">⚠️ Safety Reports</button>
    <button class="btn btn-outline btn-sm" id="tabCategoriesBtn" onclick="switchAdminTab('categories')">📂 Categories Analytics</button>
  </div>

  <!-- TAB 1: USERS MANAGEMENT -->
  <div id="adminTabUsers">
    <div class="card" style="padding:24px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
        <h3 style="font-size:18px;">Registered Members</h3>
        <input type="text" id="adminUserSearch" placeholder="Search user by name, email, or city..." style="padding:8px 14px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); width:280px; font-size:13px;">
      </div>

      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:13.5px; text-align:left;">
          <thead>
            <tr style="border-bottom:2px solid var(--border-color); color:var(--text-muted);">
              <th style="padding:10px;">ID</th>
              <th style="padding:10px;">User</th>
              <th style="padding:10px;">Role</th>
              <th style="padding:10px;">Status</th>
              <th style="padding:10px;">City</th>
              <th style="padding:10px;">Reputation</th>
              <th style="padding:10px; text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody id="adminUsersTableBody">
            <tr><td colspan="7" style="padding:20px; text-align:center;">Loading users...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TAB 2: REPORTS MODERATION -->
  <div id="adminTabReports" class="hidden">
    <div class="card" style="padding:24px;">
      <h3 style="font-size:18px; margin-bottom:18px;">Community Moderation Reports</h3>
      <div id="adminReportsContainer">
        <p style="padding:20px; text-align:center; color:var(--text-muted);">Loading reports...</p>
      </div>
    </div>
  </div>

  <!-- TAB 3: CATEGORIES DISTRIBUTION -->
  <div id="adminTabCategories" class="hidden">
    <div class="card" style="padding:24px;">
      <h3 style="font-size:18px; margin-bottom:18px;">Category Distribution & Requests Volume</h3>
      <div id="adminCategoriesList" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px;">
        <!-- Populated via metrics API -->
      </div>
    </div>
  </div>
</main>

<script>
async function loadAdminMetrics() {
  try {
    const res = await apiFetch('<?= BASE_URL ?>api/admin.php?action=metrics');
    const m = res.data.metrics;
    $('#mTotalUsers').textContent = m.total_users;
    $('#mHelpRequests').textContent = m.help_requests;
    $('#mCompletedHelps').textContent = m.completed_helps;
    $('#mMessagesCount').textContent = m.messages_count;
    $('#mPendingReports').textContent = m.pending_reports;
    $('#mEmergencyRequests').textContent = m.emergency_requests;

    const catList = $('#adminCategoriesList');
    if (catList && res.data.categories) {
      catList.innerHTML = res.data.categories.map(c => `
        <div style="background:var(--bg-card-subtle); padding:16px; border-radius:var(--radius-md); border:1px solid var(--border-color);">
          <strong style="font-size:15px; display:block; margin-bottom:4px;">${c.name}</strong>
          <span style="font-size:13px; color:var(--primary); font-weight:700;">${c.request_count} requests</span>
        </div>
      `).join('');
    }
  } catch (e) {}
}

async function loadAdminUsers() {
  const q = $('#adminUserSearch').value.trim();
  try {
    const res = await apiFetch(`<?= BASE_URL ?>api/admin.php?action=users&q=${encodeURIComponent(q)}`);
    const tbody = $('#adminUsersTableBody');
    const users = res.data.users || [];

    tbody.innerHTML = users.map(u => `
      <tr style="border-bottom:1px solid var(--border-color);">
        <td style="padding:10px;">#${u.id}</td>
        <td style="padding:10px;">
          <strong>${u.full_name}</strong><br>
          <small style="color:var(--text-muted);">${u.email}</small>
        </td>
        <td style="padding:10px;"><span class="pill" style="font-size:11px;">${u.role.toUpperCase()}</span></td>
        <td style="padding:10px;">
          <span class="pill ${u.status === 'active' ? 'urgency-normal' : 'urgency-emergency'}">${u.status}</span>
        </td>
        <td style="padding:10px;">${u.city}</td>
        <td style="padding:10px;">⭐ ${u.reputation_points} pts</td>
        <td style="padding:10px; text-align:right;">
          ${u.status === 'active' ? `
            <button class="btn btn-danger btn-sm" onclick="setUserStatus(${u.id}, 'suspended')">Suspend</button>
          ` : `
            <button class="btn btn-outline btn-sm" onclick="setUserStatus(${u.id}, 'active')">Activate</button>
          `}
        </td>
      </tr>
    `).join('');
  } catch (e) {}
}

async function setUserStatus(userId, status) {
  if (confirm(`Change user #${userId} status to ${status}?`)) {
    try {
      const res = await apiFetch('<?= BASE_URL ?>api/admin.php?action=set_user_status', {
        method: 'POST',
        body: JSON.stringify({ user_id: userId, status: status })
      });
      showToast(res.message, 'success');
      loadAdminUsers();
      loadAdminMetrics();
    } catch (err) {
      showToast(err.message, 'error');
    }
  }
}

async function loadAdminReports() {
  try {
    const res = await apiFetch('<?= BASE_URL ?>api/admin.php?action=reports');
    const container = $('#adminReportsContainer');
    const reports = res.data.reports || [];

    if (reports.length === 0) {
      container.innerHTML = '<p style="padding:20px; text-align:center; color:var(--text-muted);">No reports to review.</p>';
      return;
    }

    container.innerHTML = reports.map(r => `
      <div style="padding:14px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
        <div>
          <span class="pill urgency-emergency" style="font-size:11px;">REPORT #${r.id}</span>
          <strong style="margin-left:8px;">Reason: ${r.reason}</strong>
          <p style="font-size:13px; color:var(--text-muted); margin-top:4px;">
            Target: <strong>${r.item_type} #${r.item_id}</strong> • Reporter: ${r.reporter_name}
          </p>
        </div>
        <div style="display:flex; gap:6px;">
          <button class="btn btn-outline btn-sm" onclick="resolveReport(${r.id}, 'resolved')">✓ Resolve</button>
          <button class="btn btn-soft btn-sm" onclick="resolveReport(${r.id}, 'dismissed')">Dismiss</button>
        </div>
      </div>
    `).join('');
  } catch (e) {}
}

async function resolveReport(repId, status) {
  try {
    const res = await apiFetch('<?= BASE_URL ?>api/admin.php?action=resolve_report', {
      method: 'POST',
      body: JSON.stringify({ report_id: repId, status: status })
    });
    showToast(res.message, 'success');
    loadAdminReports();
    loadAdminMetrics();
  } catch (e) {
    showToast(e.message, 'error');
  }
}

function switchAdminTab(tab) {
  $('#adminTabUsers').classList.toggle('hidden', tab !== 'users');
  $('#adminTabReports').classList.toggle('hidden', tab !== 'reports');
  $('#adminTabCategories').classList.toggle('hidden', tab !== 'categories');

  $('#tabUsersBtn').className = `btn btn-sm ${tab === 'users' ? 'btn-soft' : 'btn-outline'}`;
  $('#tabReportsBtn').className = `btn btn-sm ${tab === 'reports' ? 'btn-soft' : 'btn-outline'}`;
  $('#tabCategoriesBtn').className = `btn btn-sm ${tab === 'categories' ? 'btn-soft' : 'btn-outline'}`;

  if (tab === 'reports') loadAdminReports();
}

document.addEventListener('DOMContentLoaded', () => {
  loadAdminMetrics();
  loadAdminUsers();
  $('#adminUserSearch').addEventListener('input', () => {
    clearTimeout(window.adminSearchTimeout);
    window.adminSearchTimeout = setTimeout(loadAdminUsers, 300);
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
