<?php
$pageTitle = "Find Help Requests | HELPHUB";
require_once __DIR__ . '/../includes/header.php';
$pdo = get_pdo();
$requestId = (int)($_GET['id'] ?? 0);
$categories = $pdo ? $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll() : [];
?>

<main class="section">
<?php if ($requestId > 0): ?>
  <!-- SINGLE REQUEST VIEW WITH SMART MATCHES & STATUS CONTROLS -->
  <div id="singleRequestContainer">
    <p style="color:var(--text-muted); text-align:center; padding:40px;">Loading request details & matching helpers...</p>
  </div>

  <script>
  async function loadSingleRequest() {
    try {
      const res = await apiFetch(`<?= BASE_URL ?>api/requests.php?id=<?= $requestId ?>`);
      const r = res.data.request;
      const matches = res.data.matches || [];
      const container = $('#singleRequestContainer');
      const isOwner = currentUser && (currentUser.id == r.user_id);
      const isHelper = currentUser && (currentUser.id == r.accepted_helper_id);

      container.innerHTML = `
        <div style="display:grid; grid-template-columns:2fr 1fr; gap:30px;">
          <!-- LEFT: REQUEST DETAILS -->
          <div>
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px;">
              <div>
                <a href="<?= BASE_URL ?>requests/view.php" style="font-size:13px; color:var(--primary); font-weight:700;">← Back to Help Board</a>
                <h1 style="font-size:28px; font-family:var(--font-heading); margin-top:8px;">${r.title}</h1>
              </div>
              <div style="display:flex; gap:8px;">
                <span class="pill urgency-${r.urgency}">${(r.urgency).toUpperCase()}</span>
                <span class="pill" style="background:var(--bg-card-subtle);">${(r.status).toUpperCase()}</span>
              </div>
            </div>

            <div class="card" style="padding:28px; margin-bottom:24px;">
              <div style="display:flex; gap:20px; font-size:13.5px; color:var(--text-muted); border-bottom:1px solid var(--border-color); padding-bottom:14px; margin-bottom:18px;">
                <span>📂 <strong>${r.category_name}</strong></span>
                <span>📍 <strong>${r.location}</strong></span>
                <span>📅 Required by: <strong>${r.required_date || 'Flexible'}</strong></span>
              </div>

              <h4 style="font-size:16px; margin-bottom:8px;">Situation Description</h4>
              <p style="font-size:15px; line-height:1.7; margin-bottom:20px; white-space:pre-line;">${r.description}</p>

              ${r.skills_required ? `
                <div style="margin-bottom:20px; background:var(--bg-card-subtle); padding:14px; border-radius:var(--radius-md);">
                  <strong style="font-size:13.5px; display:block; margin-bottom:4px;">Skills / Resources Needed:</strong>
                  <span style="font-size:14px; color:var(--primary);">${r.skills_required}</span>
                </div>
              ` : ''}

              <!-- WORKFLOW ACTIONS -->
              <div style="display:flex; gap:12px; flex-wrap:wrap; border-top:1px solid var(--border-color); padding-top:18px;">
                ${!isOwner ? `
                  <button class="btn btn-primary" onclick="openChatWithUser(${r.user_id}, '${r.requester_name}', ${r.id})">💬 Contact Requester</button>
                ` : `
                  ${r.status === 'open' || r.status === 'in_discussion' ? `
                    <button class="btn btn-outline btn-sm" onclick="updateRequestStatus(${r.id}, 'in_progress')">Mark In Progress</button>
                  ` : ''}
                  ${r.status !== 'completed' ? `
                    <button class="btn btn-primary btn-sm" onclick="updateRequestStatus(${r.id}, 'completed')">✓ Mark Completed & Rate Helper</button>
                    <button class="btn btn-danger btn-sm" onclick="updateRequestStatus(${r.id}, 'cancelled')">Cancel Request</button>
                  ` : `
                    <span class="pill" style="background:var(--success-light); color:#065f46; font-size:14px;">✅ Request Completed</span>
                  `}
                `}
              </div>
            </div>

            <!-- SMART MATCHES SECTION -->
            <div class="section-head" style="margin-bottom:16px;">
              <div>
                <h3>Top Matched Helpers for this Request</h3>
                <p style="color:var(--text-muted); font-size:13.5px;">Helpers in our network matching the required skills and location.</p>
              </div>
            </div>

            <div style="display:grid; gap:14px;">
              ${matches.length === 0 ? '<p style="color:var(--text-muted);">Scanning for matching helpers...</p>' : matches.map(m => `
                <div class="card" style="padding:18px; flex-direction:row; align-items:center; justify-content:space-between;">
                  <div class="user-mini">
                    <div class="avatar">${m.full_name ? m.full_name[0] : 'U'}</div>
                    <div>
                      <div style="display:flex; align-items:center; gap:8px;">
                        <b style="font-size:15px;">${m.full_name}</b>
                        <span class="match-badge">${m.match_label}</span>
                      </div>
                      <p style="font-size:12.5px; color:var(--text-muted);">
                        ⭐ ${Number(m.rating).toFixed(1)}/5 • 📍 ${m.city} ${m.distance_km ? `(${m.distance_km} km away)` : ''} • ${m.helps_given || 0} helps delivered
                      </p>
                      <small style="color:var(--primary); font-size:12px;">Skills: ${m.skills || 'Community Volunteer'}</small>
                    </div>
                  </div>
                  <div style="display:flex; gap:8px;">
                    <a href="<?= BASE_URL ?>profile/profile.php?id=${m.id}" class="btn btn-outline btn-sm">Profile</a>
                    <button class="btn btn-primary btn-sm" onclick="openChatWithUser(${m.id}, '${m.full_name}', ${r.id})">💬 Contact</button>
                    ${isOwner && r.status !== 'completed' ? `
                      <button class="btn btn-soft btn-sm" onclick="updateRequestStatus(${r.id}, 'accepted', ${m.id})">Select Helper</button>
                    ` : ''}
                  </div>
                </div>
              `).join('')}
            </div>
          </div>

          <!-- RIGHT: REQUESTER PROFILE CARD -->
          <div>
            <div class="card" style="padding:24px; text-align:center;">
              <div class="avatar avatar-lg" style="margin:0 auto 12px;">${r.requester_name ? r.requester_name[0] : 'U'}</div>
              <h3 style="font-size:18px;">${r.requester_name}</h3>
              <p style="color:var(--text-muted); font-size:13px; margin-bottom:12px;">@${r.requester_username}</p>

              <div style="display:flex; justify-content:center; gap:8px; margin-bottom:16px;">
                <span class="reputation-badge">⭐ ${r.requester_rating}/5</span>
                <span class="pill" style="background:var(--primary-light); color:var(--primary);">${r.requester_city}</span>
              </div>

              <a href="<?= BASE_URL ?>profile/profile.php?id=${r.user_id}" class="btn btn-outline btn-sm" style="width:100%;">View Public Profile</a>
            </div>
          </div>
        </div>
      `;
    } catch (e) {
      $('#singleRequestContainer').innerHTML = `<p style="color:var(--danger); text-align:center;">${e.message || 'Error loading request'}</p>`;
    }
  }
  document.addEventListener('DOMContentLoaded', loadSingleRequest);
  </script>

<?php else: ?>
  <!-- FIND HELP BOARD (DISCOVERY WITH ADVANCED SEARCH & FILTERS) -->
  <div class="section-head">
    <div>
      <span class="eyebrow">DISCOVERY & ASSISTANCE</span>
      <h1>Find Community Help Requests</h1>
      <p>Search active requests from neighbors who need urgent support, tutoring, equipment, or essential supplies.</p>
    </div>
    <a href="<?= BASE_URL ?>requests/create.php" class="btn btn-primary">+ Post Help Request</a>
  </div>

  <!-- SEARCH & FILTER TOOLBAR -->
  <div class="card" style="padding:20px; margin-bottom:28px;">
    <div style="display:grid; grid-template-columns:1.8fr 1fr 1fr 1fr; gap:12px;">
      <input type="text" id="findSearchInput" placeholder="Search by keywords, location, or skills..." style="padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);">
      
      <select id="findCategoryFilter" style="padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>"><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <select id="findUrgencyFilter" style="padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);">
        <option value="">All Urgencies</option>
        <option value="emergency">🆘 Emergency Only</option>
        <option value="urgent">Urgent</option>
        <option value="important">Important</option>
        <option value="normal">Normal</option>
      </select>

      <select id="findSortFilter" style="padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);">
        <option value="newest">Sort: Newest First</option>
        <option value="urgent">Sort: Urgency First</option>
      </select>
    </div>
  </div>

  <!-- REQUESTS LIST -->
  <div class="cards-grid" id="requestsResultsList">
    <div style="text-align:center; padding:40px; grid-column:1/-1;">
      <p style="color:var(--text-muted);">Loading help requests...</p>
    </div>
  </div>

  <script>
  async function loadHelpBoard() {
    const q = $('#findSearchInput').value.trim();
    const cat = $('#findCategoryFilter').value;
    const urgency = $('#findUrgencyFilter').value;
    const sort = $('#findSortFilter').value;

    const params = new URLSearchParams();
    if (q) params.set('q', q);
    if (cat) params.set('category_id', cat);
    if (urgency) params.set('urgency', urgency);
    if (sort) params.set('sort', sort);

    try {
      const res = await apiFetch(`<?= BASE_URL ?>api/requests.php?${params.toString()}`);
      const container = $('#requestsResultsList');
      if (!res.data || !res.data.requests || res.data.requests.length === 0) {
        container.innerHTML = `
          <div style="text-align:center; padding:50px; grid-column:1/-1;">
            <h3>No matching requests found</h3>
            <p style="color:var(--text-muted); margin-top:6px;">Try adjusting your search terms or filters.</p>
          </div>
        `;
        return;
      }

      container.innerHTML = res.data.requests.map(r => `
        <article class="card">
          <div>
            <div class="card-top">
              <span class="pill urgency-${r.urgency || 'normal'}">${(r.urgency || 'Normal').toUpperCase()}</span>
              <span style="font-size:12px; color:var(--text-muted);">📍 ${r.location}</span>
            </div>
            <h3>${r.title}</h3>
            <p class="desc">${r.description ? r.description.substring(0, 115) + '...' : ''}</p>
          </div>
          <div>
            <div class="card-meta">
              <span>👤 ${r.requester_name || 'Member'}</span>
              <span>⭐ ${Number(r.requester_rating || 5).toFixed(1)}</span>
            </div>
            <div class="card-footer">
              <a href="<?= BASE_URL ?>requests/view.php?id=${r.id}" class="btn btn-outline btn-sm">View Details</a>
              <button class="btn btn-primary btn-sm" onclick="openChatWithUser(${r.user_id}, '${r.requester_name}', ${r.id})">💬 Contact</button>
            </div>
          </div>
        </article>
      `).join('');
    } catch (e) {
      $('#requestsResultsList').innerHTML = `<p style="color:var(--danger); grid-column:1/-1; text-align:center;">${e.message || 'Error loading requests'}</p>`;
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    loadHelpBoard();
    $('#findSearchInput').addEventListener('input', () => {
      clearTimeout(window.searchTimeout);
      window.searchTimeout = setTimeout(loadHelpBoard, 300);
    });
    $('#findCategoryFilter').addEventListener('change', loadHelpBoard);
    $('#findUrgencyFilter').addEventListener('change', loadHelpBoard);
    $('#findSortFilter').addEventListener('change', loadHelpBoard);
  });
  </script>
<?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
