<?php
$pageTitle = "User Profile | HELPHUB";
require_once __DIR__ . '/../includes/header.php';
$pdo = get_pdo();
$targetId = (int)($_GET['id'] ?? (is_logged_in() ? $_SESSION['user']['id'] : 0));

if (!$targetId) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$targetId]);
$profileUser = $stmt->fetch();

if (!$profileUser) {
    echo "<main class='section'><h2>User not found.</h2></main>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch user's reviews
$reviewsStmt = $pdo->prepare("
    SELECT r.*, u.full_name AS reviewer_name, u.avatar AS reviewer_avatar
    FROM reviews r
    JOIN users u ON u.id = r.reviewer_id
    WHERE r.reviewee_id = ?
    ORDER BY r.id DESC
");
$reviewsStmt->execute([$targetId]);
$reviews = $reviewsStmt->fetchAll();

// Fetch user's active offers
$offersStmt = $pdo->prepare("SELECT * FROM help_offers WHERE user_id = ? AND status = 'active'");
$offersStmt->execute([$targetId]);
$offers = $offersStmt->fetchAll();

$isMe = is_logged_in() && ($_SESSION['user']['id'] == $targetId);
?>

<main class="section">
  <!-- PROFILE HEADER CARD -->
  <div class="card" style="padding:36px; margin-bottom:30px; position:relative;">
    <div style="display:flex; gap:26px; align-items:center; flex-wrap:wrap;">
      <div class="avatar avatar-lg" style="width:96px; height:96px; font-size:36px;">
        <?= strtoupper($profileUser['full_name'][0]) ?>
      </div>

      <div style="flex:1; min-width:260px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:4px; flex-wrap:wrap;">
          <h1 style="font-size:28px; font-family:var(--font-heading);"><?= htmlspecialchars($profileUser['full_name']) ?></h1>
          <span class="reputation-badge">🏆 <?= (int)$profileUser['reputation_points'] ?> Reputation</span>
          <span class="pill" style="background:var(--success-light); color:#065f46;">⭐ <?= number_format($profileUser['rating'], 1) ?>/5 (<?= (int)$profileUser['total_reviews'] ?> reviews)</span>
        </div>

        <p style="color:var(--text-muted); font-size:14px; margin-bottom:12px;">
          @<?= htmlspecialchars($profileUser['username']) ?> • 📍 <?= htmlspecialchars($profileUser['city']) ?>, <?= htmlspecialchars($profileUser['state']) ?>
        </p>

        <p style="font-size:15px; line-height:1.6; max-width:700px; margin-bottom:16px;">
          <?= htmlspecialchars($profileUser['bio'] ?: 'Community volunteer and neighbor ready to connect and help.') ?>
        </p>

        <?php if (!empty($profileUser['skills'])): ?>
          <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <strong style="font-size:13px; color:var(--text-muted);">Skills:</strong>
            <?php foreach (explode(',', $profileUser['skills']) as $sk): ?>
              <span class="pill" style="background:var(--primary-light); color:var(--primary); font-size:12px;"><?= htmlspecialchars(trim($sk)) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- PROFILE ACTION BUTTONS -->
      <div style="display:flex; flex-direction:column; gap:10px; min-width:180px;">
        <?php if (!$isMe): ?>
          <button class="btn btn-primary" onclick="openChatWithUser(<?= $profileUser['id'] ?>, '<?= htmlspecialchars($profileUser['full_name']) ?>')">💬 Message</button>
          <button class="btn btn-outline btn-sm" onclick="reportUser(<?= $profileUser['id'] ?>)">⚠️ Report Member</button>
          <button class="btn btn-outline btn-sm" onclick="blockUser(<?= $profileUser['id'] ?>)">🚫 Block User</button>
        <?php else: ?>
          <a href="<?= BASE_URL ?>dashboard/index.php" class="btn btn-outline">Go to Dashboard</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- STATS TILES -->
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:18px; margin-bottom:34px;">
    <div class="card" style="padding:20px; text-align:center;">
      <b style="font-size:26px; color:var(--success);"><?= (int)$profileUser['helps_given'] ?></b>
      <p style="color:var(--text-muted); font-size:13px;">Community Helps Given</p>
    </div>
    <div class="card" style="padding:20px; text-align:center;">
      <b style="font-size:26px; color:var(--primary);"><?= (int)$profileUser['helps_received'] ?></b>
      <p style="color:var(--text-muted); font-size:13px;">Helps Received</p>
    </div>
    <div class="card" style="padding:20px; text-align:center;">
      <b style="font-size:26px; color:#8b5cf6;">⭐ <?= number_format($profileUser['rating'], 1) ?></b>
      <p style="color:var(--text-muted); font-size:13px;">Community Trust Score</p>
    </div>
    <div class="card" style="padding:20px; text-align:center;">
      <b style="font-size:26px; color:var(--warning);"><?= count($offers) ?></b>
      <p style="color:var(--text-muted); font-size:13px;">Active Help Offers</p>
    </div>
  </div>

  <!-- 2 COLUMNS: REVIEWS & ACTIVE OFFERS -->
  <div style="display:grid; grid-template-columns:1.8fr 1.2fr; gap:30px;">
    <!-- REVIEWS -->
    <div>
      <div class="section-head" style="margin-bottom:18px;">
        <h3>Community Ratings & Reviews (<?= count($reviews) ?>)</h3>
      </div>

      <?php if (empty($reviews)): ?>
        <div class="card" style="padding:30px; text-align:center;">
          <p style="color:var(--text-muted);">No reviews written yet. Complete a help request to earn your first verified review!</p>
        </div>
      <?php else: ?>
        <div style="display:grid; gap:14px;">
          <?php foreach ($reviews as $rev): ?>
            <div class="card" style="padding:20px;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <div class="user-mini">
                  <div class="avatar avatar-sm"><?= strtoupper($rev['reviewer_name'][0]) ?></div>
                  <div>
                    <strong style="font-size:14px;"><?= htmlspecialchars($rev['reviewer_name']) ?></strong>
                    <span style="font-size:12px; color:var(--text-muted);">• <?= time_ago($rev['created_at']) ?></span>
                  </div>
                </div>
                <span style="color:#f59e0b; font-size:14px;">
                  <?= str_repeat('★', (int)$rev['rating']) ?><?= str_repeat('☆', 5 - (int)$rev['rating']) ?>
                </span>
              </div>
              <p style="font-size:14px; line-height:1.6;"><?= htmlspecialchars($rev['review_text']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ACTIVE OFFERS -->
    <div>
      <div class="section-head" style="margin-bottom:18px;">
        <h3>Published Help Offers</h3>
      </div>

      <?php if (empty($offers)): ?>
        <div class="card" style="padding:24px; text-align:center;">
          <p style="color:var(--text-muted); font-size:13.5px;">No active public offers right now.</p>
        </div>
      <?php else: ?>
        <div style="display:grid; gap:14px;">
          <?php foreach ($offers as $off): ?>
            <div class="card" style="padding:18px;">
              <span class="pill" style="background:var(--primary-light); color:var(--primary); font-size:11px; margin-bottom:6px;">OFFER</span>
              <h4 style="font-size:15px; margin-bottom:6px;"><?= htmlspecialchars($off['title']) ?></h4>
              <p style="font-size:13px; color:var(--text-muted); margin-bottom:10px;"><?= htmlspecialchars($off['description']) ?></p>
              <small style="color:var(--text-muted); font-size:12px;">🕒 Availability: <?= htmlspecialchars($off['availability']) ?></small>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
function reportUser(targetId) {
  const reason = prompt('State reason for reporting user (Spam, Harassment, Fake details):');
  if (reason) {
    apiFetch('<?= BASE_URL ?>api/safety.php?action=report', {
      method: 'POST',
      body: JSON.stringify({ item_type: 'user', item_id: targetId, reason: reason })
    }).then(res => showToast(res.message, 'success'))
      .catch(e => showToast(e.message, 'error'));
  }
}

function blockUser(targetId) {
  if (confirm('Block this user? They will not be able to message you.')) {
    apiFetch('<?= BASE_URL ?>api/safety.php?action=block', {
      method: 'POST',
      body: JSON.stringify({ target_user_id: targetId })
    }).then(res => {
      showToast(res.message, 'info');
      setTimeout(() => location.reload(), 800);
    }).catch(e => showToast(e.message, 'error'));
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
