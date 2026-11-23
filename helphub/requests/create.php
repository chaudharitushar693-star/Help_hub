<?php
$pageTitle = "Post a Help Request | HELPHUB";
require_once __DIR__ . '/../includes/header.php';
require_login();
$pdo = get_pdo();
$categories = $pdo ? $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll() : [];
$presetUrgency = $_GET['urgency'] ?? 'normal';
?>

<main class="section" style="max-width:750px;">
  <div style="margin-bottom:28px;">
    <span class="eyebrow">COMMUNITY SUPPORT • REQUEST</span>
    <h1 style="font-size:32px; font-family:var(--font-heading);">Post a Help Request</h1>
    <p style="color:var(--text-muted);">Fill in the details below. Our smart matching system will automatically notify top suited helpers nearby.</p>
  </div>

  <div class="card" style="padding:34px;">
    <form id="createRequestForm">
      <div style="margin-bottom:18px;">
        <label style="display:block; font-size:13.5px; font-weight:700; margin-bottom:6px;">Request Title *</label>
        <input name="title" placeholder="e.g. Need urgent pickup of heart medication from pharmacy" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:18px;">
        <div>
          <label style="display:block; font-size:13.5px; font-weight:700; margin-bottom:6px;">Category *</label>
          <select name="category_id" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label style="display:block; font-size:13.5px; font-weight:700; margin-bottom:6px;">Urgency Level *</label>
          <select name="urgency" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
            <option value="normal" <?= $presetUrgency === 'normal' ? 'selected' : '' ?>>Normal (Within a few days)</option>
            <option value="important" <?= $presetUrgency === 'important' ? 'selected' : '' ?>>Important (Within 24-48 hours)</option>
            <option value="urgent" <?= $presetUrgency === 'urgent' ? 'selected' : '' ?>>Urgent (Needed today)</option>
            <option value="emergency" <?= $presetUrgency === 'emergency' ? 'selected' : '' ?>>🆘 Emergency (Immediate Crisis)</option>
          </select>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:18px;">
        <div>
          <label style="display:block; font-size:13.5px; font-weight:700; margin-bottom:6px;">Location / Area *</label>
          <input name="location" value="<?= htmlspecialchars($_SESSION['user']['city'] ?? 'Bhopal') ?>" placeholder="e.g. Arera Colony, Bhopal" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
        </div>

        <div>
          <label style="display:block; font-size:13.5px; font-weight:700; margin-bottom:6px;">Date Needed *</label>
          <input type="date" name="required_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
        </div>
      </div>

      <div style="margin-bottom:18px;">
        <label style="display:block; font-size:13.5px; font-weight:700; margin-bottom:6px;">Skills or Equipment Required</label>
        <input name="skills_required" placeholder="e.g. Two-wheeler vehicle, Laptop Hardware repair, Class 10 Math knowledge" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);">
      </div>

      <div style="margin-bottom:24px;">
        <label style="display:block; font-size:13.5px; font-weight:700; margin-bottom:6px;">Detailed Description *</label>
        <textarea name="description" rows="5" placeholder="Explain the exact situation clearly. What happened? How can a helper assist you best?" style="width:100%; padding:14px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">Publish Help Request →</button>
    </form>
  </div>
</main>

<script>
$('#createRequestForm').onsubmit = async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = Object.fromEntries(fd.entries());

  try {
    const res = await apiFetch('<?= BASE_URL ?>api/requests.php', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
    showToast(res.message || 'Help request created!', 'success');
    setTimeout(() => {
      location.href = `<?= BASE_URL ?>requests/view.php?id=${res.data.id}`;
    }, 800);
  } catch (err) {
    showToast(err.message || 'Error posting request', 'error');
  }
};
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
