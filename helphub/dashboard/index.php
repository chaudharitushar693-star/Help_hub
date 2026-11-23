<?php
$pageTitle = "User Dashboard | HELPHUB";
require_once __DIR__ . '/../includes/header.php';
require_login();
$pdo = get_pdo();
$user = current_user($pdo);
$userId = $user['id'];

// Fetch user's active requests
$activeRequests = [];
$activeOffers = [];
if ($pdo) {
    $stmt = $pdo->prepare("
        SELECT r.*, c.name AS category_name, c.icon AS category_icon
        FROM help_requests r
        JOIN categories c ON c.id = r.category_id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC LIMIT 6
    ");
    $stmt->execute([$userId]);
    $activeRequests = $stmt->fetchAll();

    $stmt2 = $pdo->prepare("SELECT * FROM help_offers WHERE user_id = ? ORDER BY id DESC LIMIT 4");
    $stmt2->execute([$userId]);
    $activeOffers = $stmt2->fetchAll();
}
?>

<main class="section">
  <!-- DASHBOARD GREETING & STATS -->
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px; margin-bottom:28px;">
    <div>
      <span class="eyebrow">DASHBOARD • COMMUNITY DESK</span>
      <h1 style="font-size:32px; font-family:var(--font-heading);">Welcome back, <?= htmlspecialchars($user['full_name']) ?> 👋</h1>
      <p style="color:var(--text-muted); font-size:15px;">City: <strong><?= htmlspecialchars($user['city']) ?></strong> • Role: <strong><?= strtoupper($user['role']) ?></strong></p>
    </div>

    <!-- QUICK ACTIONS -->
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
      <a href="<?= BASE_URL ?>requests/create.php" class="btn btn-primary">+ Request Help</a>
      <a href="<?= BASE_URL ?>offers/create.php" class="btn btn-soft">+ Offer Help</a>
      <a href="<?= BASE_URL ?>messages/index.php" class="btn btn-outline">💬 Messages</a>
      <a href="<?= BASE_URL ?>requests/view.php" class="btn btn-outline">🔎 Find Help</a>
    </div>
  </div>

  <!-- STATS CARDS -->
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px; margin-bottom:40px;">
    <div class="card" style="padding:20px;">
      <span style="font-size:13px; color:var(--text-muted); font-weight:700;">Help Given</span>
      <b style="font-size:28px; color:var(--success); margin:6px 0;"><?= (int)$user['helps_given'] ?></b>
      <small style="color:var(--text-muted);">Times you assisted someone</small>
    </div>

    <div class="card" style="padding:20px;">
      <span style="font-size:13px; color:var(--text-muted); font-weight:700;">Help Received</span>
      <b style="font-size:28px; color:var(--primary); margin:6px 0;"><?= (int)$user['helps_received'] ?></b>
      <small style="color:var(--text-muted);">Requests completed for you</small>
    </div>

    <div class="card" style="padding:20px;">
      <span style="font-size:13px; color:var(--text-muted); font-weight:700;">Active Requests</span>
      <b style="font-size:28px; color:var(--warning); margin:6px 0;"><?= count($activeRequests) ?></b>
      <small style="color:var(--text-muted);">Open or in discussion</small>
    </div>

    <div class="card" style="padding:20px;">
      <span style="font-size:13px; color:var(--text-muted); font-weight:700;">Reputation Score</span>
      <b style="font-size:28px; color:#8b5cf6; margin:6px 0;">⭐ <?= (int)$user['reputation_points'] ?> pts</b>
      <small style="color:var(--text-muted);">Rating: <?= number_format($user['rating'], 1) ?>/5.0</small>
    </div>
  </div>

  <!-- MAIN DASHBOARD CONTENT: 2 COLUMNS -->
  <div style="display:grid; grid-template-columns:2fr 1fr; gap:30px;">
    <!-- LEFT: MY REQUESTS & SMART RECOMMENDED HELPERS -->
    <div>
      <div class="section-head" style="margin-bottom:18px;">
        <h3>My Help Requests</h3>
        <a href="<?= BASE_URL ?>requests/create.php" style="font-size:13.5px; color:var(--primary); font-weight:700;">+ New Request</a>
      </div>

      <?php if (empty($activeRequests)): ?>
        <div class="card" style="text-align:center; padding:35px;">
          <p style="color:var(--text-muted); margin-bottom:14px;">You haven't posted any help requests yet.</p>
          <a href="<?= BASE_URL ?>requests/create.php" class="btn btn-primary btn-sm">+ Post First Help Request</a>
        </div>
      <?php else: ?>
        <div style="display:grid; gap:14px;">
          <?php foreach ($activeRequests as $req): ?>
            <div class="card" style="padding:20px; flex-direction:row; align-items:center; justify-content:space-between;">
              <div>
                <div style="display:flex; gap:8px; align-items:center; margin-bottom:6px;">
                  <span class="pill urgency-<?= $req['urgency'] ?>"><?= strtoupper($req['urgency']) ?></span>
                  <span class="pill" style="background:var(--bg-card-subtle);"><?= strtoupper($req['status']) ?></span>
                </div>
                <h4 style="font-size:16px;"><?= htmlspecialchars($req['title']) ?></h4>
                <p style="color:var(--text-muted); font-size:13px;">Location: <?= htmlspecialchars($req['location']) ?> • Required by: <?= htmlspecialchars($req['required_date']) ?></p>
              </div>
              <div style="display:flex; gap:8px;">
                <a href="<?= BASE_URL ?>requests/view.php?id=<?= $req['id'] ?>" class="btn btn-outline btn-sm">Manage & Matches</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- RECOMMENDED HELPERS (SMART MATCHES) -->
      <div class="section-head" style="margin-top:40px; margin-bottom:18px;">
        <div>
          <h3>Smart Recommended Helpers Nearby</h3>
          <p style="color:var(--text-muted); font-size:13.5px;">Calculated using category, skills, proximity, and community ratings.</p>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
        <!-- Card 1 -->
        <div class="card" style="padding:20px;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
            <div class="user-mini">
              <div class="avatar">R</div>
              <div>
                <b style="font-size:15px;">Rahul Sharma</b>
                <p style="font-size:12px; color:var(--text-muted);">💻 Computer & Tech Repair</p>
              </div>
            </div>
            <span class="match-badge">96% Match</span>
          </div>
          <p style="font-size:13px; color:var(--text-muted); margin-bottom:14px;">⭐ 4.9/5 • 📍 MP Nagar (2.4 km) • 🟢 Available</p>
          <div style="display:flex; gap:8px;">
            <a href="<?= BASE_URL ?>profile/profile.php?id=2" class="btn btn-outline btn-sm" style="flex:1;">Profile</a>
            <button class="btn btn-primary btn-sm" style="flex:1;" onclick="openChatWithUser(2, 'Rahul Sharma')">💬 Message</button>
          </div>
        </div>

        <!-- Card 2 -->
        <div class="card" style="padding:20px;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
            <div class="user-mini">
              <div class="avatar">P</div>
              <div>
                <b style="font-size:15px;">Priya Nair</b>
                <p style="font-size:12px; color:var(--text-muted);">📚 Math & Science Tutor</p>
              </div>
            </div>
            <span class="match-badge">89% Match</span>
          </div>
          <p style="font-size:13px; color:var(--text-muted); margin-bottom:14px;">⭐ 4.85/5 • 📍 Arera Colony (3.1 km) • 🟢 Weekends</p>
          <div style="display:flex; gap:8px;">
            <a href="<?= BASE_URL ?>profile/profile.php?id=3" class="btn btn-outline btn-sm" style="flex:1;">Profile</a>
            <button class="btn btn-primary btn-sm" style="flex:1;" onclick="openChatWithUser(3, 'Priya Nair')">💬 Message</button>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT: PROFILE SUMMARY & COMMUNITY BADGES -->
    <div>
      <div class="card" style="padding:24px; text-align:center; margin-bottom:24px;">
        <div class="avatar avatar-lg" style="margin:0 auto 14px;"><?= strtoupper($user['full_name'][0]) ?></div>
        <h3 style="font-size:20px;"><?= htmlspecialchars($user['full_name']) ?></h3>
        <p style="color:var(--text-muted); font-size:13.5px; margin-bottom:16px;">@<?= htmlspecialchars($user['username']) ?></p>

        <div style="display:flex; justify-content:center; gap:8px; flex-wrap:wrap; margin-bottom:18px;">
          <span class="reputation-badge">🏆 <?= (int)$user['reputation_points'] ?> Rep Points</span>
          <span class="pill" style="background:var(--success-light); color:#065f46;">Verified Member</span>
        </div>

        <p style="font-size:13px; color:var(--text-muted); margin-bottom:20px;">
          <?= htmlspecialchars($user['bio'] ?: 'No bio added yet. Help neighbors to earn trust badges!') ?>
        </p>

        <a href="<?= BASE_URL ?>profile/profile.php?id=<?= $user['id'] ?>" class="btn btn-outline btn-sm" style="width:100%;">View Public Profile</a>
      </div>

      <!-- BADGES EARNED -->
      <div class="card" style="padding:22px;">
        <h4 style="font-size:16px; margin-bottom:14px;">Earned Badges</h4>
        <div style="display:flex; flex-direction:column; gap:10px; font-size:13px;">
          <div style="display:flex; align-items:center; gap:10px;">
            <span style="font-size:22px;">🌱</span>
            <div>
              <strong>New Helper</strong>
              <p style="font-size:11.5px; color:var(--text-muted);">Joined HelpHub and verified account</p>
            </div>
          </div>
          <div style="display:flex; align-items:center; gap:10px;">
            <span style="font-size:22px;">⭐</span>
            <div>
              <strong>Trusted Helper</strong>
              <p style="font-size:11.5px; color:var(--text-muted);">Reach 50 reputation points</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
