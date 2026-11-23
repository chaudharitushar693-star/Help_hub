<?php
$pageTitle = "Offer Your Help | HELPHUB";
require_once __DIR__ . '/../includes/header.php';
require_login();
$pdo = get_pdo();
$categories = $pdo ? $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll() : [];
?>

<main class="section" style="max-width:750px;">
  <div style="margin-bottom:28px;">
    <span class="eyebrow">MUTUAL AID • VOLUNTEER</span>
    <h1 style="font-size:32px; font-family:var(--font-heading);">Offer Your Skills & Assistance</h1>
    <p style="color:var(--text-muted);">Share your knowledge, spare time, or tools with neighbors who need support. Earn verified reputation badges.</p>
  </div>

  <div class="card" style="padding:34px;">
    <form id="createOfferForm">
      <div style="margin-bottom:18px;">
        <label style="display:block; font-size:13.5px; font-weight:700; margin-bottom:6px;">Offer Title *</label>
        <input name="title" placeholder="e.g. Free Laptop Hardware Diagnostics & Linux Installation for Students" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
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
          <label style="display:block; font-size:13.5px; font-weight:700; margin-bottom:6px;">Availability *</label>
          <input name="availability" placeholder="e.g. Weekends 10am-2pm, Evenings" value="Weekends & Flexible" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:18px;">
        <div>
          <label style="display:block; font-size:13.5px; font-weight:700; margin-bottom:6px;">Skills You Can Provide *</label>
          <input name="skills" placeholder="e.g. Computer Repair, Software, OS Install" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
        </div>

        <div>
          <label style="display:block; font-size:13.5px; font-weight:700; margin-bottom:6px;">Experience / Background</label>
          <input name="experience" placeholder="e.g. 5+ years software engineer, certified tutor" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);">
        </div>
      </div>

      <div style="margin-bottom:18px;">
        <label style="display:block; font-size:13.5px; font-weight:700; margin-bottom:6px;">Location / Area *</label>
        <input name="location" value="<?= htmlspecialchars($_SESSION['user']['city'] ?? 'Bhopal') ?>" placeholder="e.g. MP Nagar, Bhopal" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
      </div>

      <div style="margin-bottom:24px;">
        <label style="display:block; font-size:13.5px; font-weight:700; margin-bottom:6px;">Offer Description & Terms *</label>
        <textarea name="description" rows="5" placeholder="Detail how you can help, what tools you have, and who is eligible..." style="width:100%; padding:14px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">Publish Help Offer →</button>
    </form>
  </div>
</main>

<script>
$('#createOfferForm').onsubmit = async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = Object.fromEntries(fd.entries());

  try {
    const res = await apiFetch('<?= BASE_URL ?>api/offers.php', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
    showToast(res.message || 'Help offer published!', 'success');
    setTimeout(() => {
      location.href = `<?= BASE_URL ?>offers/view.php`;
    }, 800);
  } catch (err) {
    showToast(err.message || 'Error publishing offer', 'error');
  }
};
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
