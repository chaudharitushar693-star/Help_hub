<?php
$pageTitle = "Browse Help Offers | HELPHUB";
require_once __DIR__ . '/../includes/header.php';
$pdo = get_pdo();
$categories = $pdo ? $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll() : [];
?>

<main class="section">
  <div class="section-head">
    <div>
      <span class="eyebrow">COMMUNITY EXPERTISE</span>
      <h1>Browse Verified Help Offers</h1>
      <p>Discover skilled community members ready to provide tutoring, tech repair, emergency aid, and transit assistance.</p>
    </div>
    <a href="<?= BASE_URL ?>offers/create.php" class="btn btn-primary">+ Offer Your Help</a>
  </div>

  <!-- TOOLBAR -->
  <div class="card" style="padding:20px; margin-bottom:28px;">
    <div style="display:grid; grid-template-columns:2fr 1fr; gap:14px;">
      <input type="text" id="offerSearchInput" placeholder="Search by skill, title, or city..." style="padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);">
      
      <select id="offerCategoryFilter" style="padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>"><?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="cards-grid" id="offersResultsList">
    <div style="text-align:center; padding:40px; grid-column:1/-1;">
      <p style="color:var(--text-muted);">Loading available help offers...</p>
    </div>
  </div>
</main>

<script>
async function loadOffers() {
  const q = $('#offerSearchInput').value.trim();
  const cat = $('#offerCategoryFilter').value;
  const params = new URLSearchParams();
  if (q) params.set('q', q);
  if (cat) params.set('category_id', cat);

  try {
    const res = await apiFetch(`<?= BASE_URL ?>api/offers.php?${params.toString()}`);
    const container = $('#offersResultsList');
    if (!res.data || !res.data.offers || res.data.offers.length === 0) {
      container.innerHTML = '<div style="text-align:center; padding:40px; grid-column:1/-1;">No matching help offers found.</div>';
      return;
    }

    container.innerHTML = res.data.offers.map(o => `
      <article class="card">
        <div>
          <div class="card-top">
            <span class="pill" style="background:var(--primary-light); color:var(--primary); font-weight:800;">${o.category_name}</span>
            <span style="font-size:12px; color:var(--text-muted);">📍 ${o.location}</span>
          </div>
          <h3>${o.title}</h3>
          <p class="desc">${o.description ? o.description.substring(0, 115) + '...' : ''}</p>
          <div style="margin-bottom:14px; font-size:12.5px; color:var(--text-muted);">
            <span>🕒 Availability: <strong>${o.availability || 'Flexible'}</strong></span>
          </div>
        </div>
        <div>
          <div class="card-meta">
            <span>👤 ${o.helper_name}</span>
            <span>⭐ ${Number(o.helper_rating || 5).toFixed(1)}/5 (${o.helps_given || 0} helps)</span>
          </div>
          <div class="card-footer">
            <a href="<?= BASE_URL ?>profile/profile.php?id=${o.user_id}" class="btn btn-outline btn-sm">Profile</a>
            <button class="btn btn-primary btn-sm" onclick="openChatWithUser(${o.user_id}, '${o.helper_name}')">💬 Message</button>
          </div>
        </div>
      </article>
    `).join('');
  } catch (e) {
    $('#offersResultsList').innerHTML = `<p style="color:var(--danger); grid-column:1/-1; text-align:center;">${e.message || 'Error loading offers'}</p>`;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  loadOffers();
  $('#offerSearchInput').addEventListener('input', () => {
    clearTimeout(window.offerSearchTimeout);
    window.offerSearchTimeout = setTimeout(loadOffers, 300);
  });
  $('#offerCategoryFilter').addEventListener('change', loadOffers);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
