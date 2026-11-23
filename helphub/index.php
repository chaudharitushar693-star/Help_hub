<?php
$pageTitle = "HELPHUB | People Helping People • Connect. Help. Make a Difference.";
require_once __DIR__ . '/includes/header.php';
$pdo = get_pdo();

// Fetch summary metrics
$stats = [
    'users' => 1280,
    'requests' => 340,
    'helpers' => 450,
    'connections' => 980
];

if ($pdo) {
    try {
        $stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['requests'] = (int)$pdo->query("SELECT COUNT(*) FROM help_requests")->fetchColumn();
        $stats['helpers'] = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM help_offers")->fetchColumn();
        $stats['connections'] = (int)$pdo->query("SELECT COUNT(*) FROM help_requests WHERE status = 'completed'")->fetchColumn();
    } catch (Exception $e) {}
}

// Fetch categories
$categories = [];
if ($pdo) {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC LIMIT 10")->fetchAll();
}
?>

<main>
  <!-- HERO SECTION -->
  <section class="hero">
    <div class="hero-content">
      <span class="eyebrow">COMMUNITY • MUTUAL AID • SAFETY</span>
      <h1>People Helping People — <span>Connect & Make a Difference.</span></h1>
      <p>Find nearby neighbors with the right skills, offer your expertise, and build a safer, resilient neighborhood community.</p>

      <div class="hero-actions">
        <a href="<?= BASE_URL ?>requests/view.php" class="btn btn-primary btn-lg">🔍 Find Help</a>
        <a href="<?= BASE_URL ?>offers/create.php" class="btn btn-outline btn-lg">🤝 Offer Help</a>
        <a href="<?= BASE_URL ?>requests/create.php?urgency=emergency" class="btn btn-emergency btn-lg">🆘 Emergency Request</a>
        <a href="<?= BASE_URL ?>presentation.html" target="_blank" class="btn btn-outline btn-lg" style="border-color:#38bdf8; color:#38bdf8;">🎓 Presentation (12 Slides)</a>
      </div>

      <div class="live-badge">
        <span>Verified Local Community Network • Over <?= number_format($stats['connections']) ?> Helps Delivered</span>
      </div>
    </div>

    <div class="hero-card">
      <h3 style="font-size:20px; margin-bottom:6px;">Live Community Activity</h3>
      <p style="color:var(--text-muted); font-size:13.5px;">Real-time network impact across cities</p>

      <div class="stats-grid">
        <div class="stat-box">
          <b><?= number_format($stats['users'] ?: 1280) ?>+</b>
          <small>Active Members</small>
        </div>
        <div class="stat-box">
          <b><?= number_format($stats['requests'] ?: 340) ?></b>
          <small>Help Requests</small>
        </div>
        <div class="stat-box">
          <b><?= number_format($stats['helpers'] ?: 450) ?></b>
          <small>Verified Helpers</small>
        </div>
        <div class="stat-box">
          <b>98.4%</b>
          <small>Satisfaction Rate</small>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="section" style="background:var(--bg-card); border-bottom:1px solid var(--border-color);">
    <div class="section-head" style="text-align:center; display:block; margin-bottom:45px;">
      <span class="eyebrow">STEP-BY-STEP PROCESS</span>
      <h2>How HelpHub Works</h2>
      <p>Safe, transparent, and direct peer-to-peer assistance in 5 simple steps.</p>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:24px; text-align:center;">
      <div style="padding:20px;">
        <div style="width:54px; height:54px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:grid; place-items:center; font-size:22px; margin:0 auto 16px; font-weight:800;">1</div>
        <h4 style="font-size:16px; margin-bottom:8px;">Create a Request</h4>
        <p style="color:var(--text-muted); font-size:13.5px;">Specify what help you need, required date, urgency level, and skills.</p>
      </div>

      <div style="padding:20px;">
        <div style="width:54px; height:54px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:grid; place-items:center; font-size:22px; margin:0 auto 16px; font-weight:800;">2</div>
        <h4 style="font-size:16px; margin-bottom:8px;">Smart Matching</h4>
        <p style="color:var(--text-muted); font-size:13.5px;">Our algorithm automatically surfaces top rated helpers nearby with match scores.</p>
      </div>

      <div style="padding:20px;">
        <div style="width:54px; height:54px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:grid; place-items:center; font-size:22px; margin:0 auto 16px; font-weight:800;">3</div>
        <h4 style="font-size:16px; margin-bottom:8px;">Connect via Chat</h4>
        <p style="color:var(--text-muted); font-size:13.5px;">Coordinate logistics through instant private messaging safely.</p>
      </div>

      <div style="padding:20px;">
        <div style="width:54px; height:54px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:grid; place-items:center; font-size:22px; margin:0 auto 16px; font-weight:800;">4</div>
        <h4 style="font-size:16px; margin-bottom:8px;">Get the Help</h4>
        <p style="color:var(--text-muted); font-size:13.5px;">Mark the request accepted and in-progress until successfully completed.</p>
      </div>

      <div style="padding:20px;">
        <div style="width:54px; height:54px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:grid; place-items:center; font-size:22px; margin:0 auto 16px; font-weight:800;">5</div>
        <h4 style="font-size:16px; margin-bottom:8px;">Rate & Review</h4>
        <p style="color:var(--text-muted); font-size:13.5px;">Leave feedback and build verified community trust badges and points.</p>
      </div>
    </div>
  </section>

  <!-- POPULAR CATEGORIES -->
  <section class="section">
    <div class="section-head">
      <div>
        <span class="eyebrow">BROWSE SECTORS</span>
        <h2>Explore Help Categories</h2>
      </div>
      <a href="<?= BASE_URL ?>requests/view.php" class="btn btn-outline btn-sm">View All Categories →</a>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px;">
      <?php if (!empty($categories)): ?>
        <?php foreach ($categories as $cat): ?>
          <a href="<?= BASE_URL ?>requests/view.php?category_id=<?= $cat['id'] ?>" class="card" style="padding:20px; cursor:pointer;">
            <div style="font-size:32px; margin-bottom:12px;"><?= $cat['icon'] ?></div>
            <h4 style="font-size:16px; margin-bottom:6px;"><?= htmlspecialchars($cat['name']) ?></h4>
            <p style="color:var(--text-muted); font-size:13px;"><?= htmlspecialchars($cat['description']) ?></p>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <p>Categories loading...</p>
      <?php endif; ?>
    </div>
  </section>

  <!-- RECENT HELP REQUESTS (LIVE CARDS) -->
  <section class="section" style="background:var(--bg-card); border-top:1px solid var(--border-color); border-bottom:1px solid var(--border-color);">
    <div class="section-head">
      <div>
        <span class="eyebrow">URGENT & ACTIVE</span>
        <h2>Recent Help Requests</h2>
      </div>
      <a href="<?= BASE_URL ?>requests/create.php" class="btn btn-primary btn-sm">+ Post a Help Request</a>
    </div>

    <div class="cards-grid" id="homepageRecentRequests">
      <!-- Loaded dynamically or rendered from backend -->
      <div style="text-align:center; padding:40px; grid-column:1/-1;">
        <p style="color:var(--text-muted);">Fetching live community requests...</p>
      </div>
    </div>
  </section>

  <!-- INTERACTIVE COMMUNITY MAP VIEW -->
  <section class="section">
    <div class="section-head">
      <div>
        <span class="eyebrow">GEOGRAPHICAL VIEW</span>
        <h2>Community Aid Map</h2>
        <p>Visualize help requests and active helpers in your surrounding neighborhood.</p>
      </div>
      <div style="display:flex; gap:12px; align-items:center; font-size:13px; font-weight:700;">
        <span>🟢 Available Helper</span>
        <span>🔴 Help Needed</span>
        <span>🟡 Urgent</span>
      </div>
    </div>

    <div id="mapContainer"></div>
  </section>

  <!-- CALL TO ACTION BANNER -->
  <section style="background:linear-gradient(135deg, var(--primary), #a855f7); color:#fff; padding:75px 5%; text-align:center;">
    <h2 style="font-size:36px; font-family:var(--font-heading); margin-bottom:14px;">Ready to Make a Difference in Your City?</h2>
    <p style="font-size:18px; max-width:620px; margin:0 auto 28px; opacity:0.95;">
      Whether you can offer an hour of math tutoring, troubleshoot a student's laptop, or need urgent medicine delivery, HelpHub connects you immediately.
    </p>
    <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
      <a href="<?= BASE_URL ?>auth/register.php" class="btn btn-lg" style="background:#fff; color:var(--primary); font-weight:800;">Join HelpHub Today →</a>
      <a href="<?= BASE_URL ?>requests/view.php" class="btn btn-outline btn-lg" style="background:rgba(255,255,255,0.15); color:#fff; border-color:rgba(255,255,255,0.4);">Browse Help Board</a>
    </div>
  </section>
</main>

<script>
// Load recent requests dynamically
async function loadHomeRequests() {
  try {
    const res = await apiFetch('api/requests.php');
    const container = $('#homepageRecentRequests');
    if (!container) return;

    if (!res.data || !res.data.requests || res.data.requests.length === 0) {
      container.innerHTML = '<div style="text-align:center; padding:30px; grid-column:1/-1;">No requests posted yet.</div>';
      return;
    }

    container.innerHTML = res.data.requests.slice(0, 6).map(r => {
      const urgencyClass = `urgency-${r.urgency || 'normal'}`;
      return `
        <article class="card">
          <div>
            <div class="card-top">
              <span class="pill ${urgencyClass}">${(r.urgency || 'Normal').toUpperCase()}</span>
              <span style="font-size:12px; color:var(--text-muted);">📍 ${r.location || r.requester_city}</span>
            </div>
            <h3>${r.title}</h3>
            <p class="desc">${r.description ? r.description.substring(0, 110) + '...' : ''}</p>
          </div>
          <div>
            <div class="card-meta">
              <span>👤 ${r.requester_name || 'Community Member'}</span>
              <span>⭐ ${r.requester_rating || '5.0'}</span>
            </div>
            <div class="card-footer">
              <a href="requests/view.php?id=${r.id}" class="btn btn-outline btn-sm">View Details</a>
              <button class="btn btn-primary btn-sm" onclick="openChatWithUser(${r.user_id}, '${r.requester_name}', ${r.id})">💬 Contact</button>
            </div>
          </div>
        </article>
      `;
    }).join('');
  } catch (e) {}
}

// Leaflet Map Initialization
function initMap() {
  const mapEl = $('#mapContainer');
  if (!mapEl || typeof L === 'undefined') return;

  const map = L.map('mapContainer').setView([23.2599, 77.4126], 12); // Centered on Bhopal / MP area

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  // Sample markers
  const markers = [
    { lat: 23.2180, lon: 77.4270, type: 'emergency', title: 'Urgent Medicines Needed', desc: 'Arera Colony - Blood pressure medicines needed urgently.' },
    { lat: 23.2384, lon: 77.4320, type: 'helper', title: 'Rahul Sharma (Computer Repair)', desc: 'MP Nagar - Available for free student laptop troubleshooting.' },
    { lat: 23.2156, lon: 77.4089, type: 'helper', title: 'Priya Nair (Math Tutor)', desc: 'Arera Colony - Offering weekend science and math coaching.' },
    { lat: 23.1750, lon: 77.4190, type: 'urgent', title: 'Monthly Ration Kit Needed', desc: 'Kolar Road - Grocery support needed for construction worker family.' }
  ];

  markers.forEach(m => {
    const color = m.type === 'emergency' ? 'red' : (m.type === 'urgent' ? 'orange' : 'green');
    const marker = L.circleMarker([m.lat, m.lon], {
      radius: 9,
      fillColor: color,
      color: '#ffffff',
      weight: 2,
      opacity: 1,
      fillOpacity: 0.85
    }).addTo(map);

    marker.bindPopup(`
      <div style="font-family:sans-serif; min-width:180px;">
        <strong style="font-size:14px; display:block; margin-bottom:4px;">${m.title}</strong>
        <p style="font-size:12px; margin:0 0 6px; color:#555;">${m.desc}</p>
        <span style="font-size:11px; font-weight:bold; color:${color}; text-transform:uppercase;">${m.type}</span>
      </div>
    `);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  loadHomeRequests();
  setTimeout(initMap, 500);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
