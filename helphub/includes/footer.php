<!-- GLOBAL FOOTER -->
<footer style="background:var(--bg-card); border-top:1px solid var(--border-color); padding:50px 5% 30px; margin-top:60px;">
  <div style="max-width:1300px; margin:0 auto; display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:40px; margin-bottom:40px;">
    <div>
      <div class="brand" style="margin-bottom:12px;">
        <div class="brand-icon">❤</div>
        <span>HELP<span>HUB</span></span>
      </div>
      <p style="color:var(--text-muted); font-size:14px; line-height:1.6;">
        <strong>“Connect. Help. Make a Difference.”</strong><br>
        A social-help network bridging individuals facing emergencies, tutoring needs, hardware repairs, and community volunteer projects.
      </p>
    </div>

    <div>
      <h4 style="font-size:16px; margin-bottom:14px;">Quick Links</h4>
      <div style="display:flex; flex-direction:column; gap:8px; font-size:14px; color:var(--text-muted);">
        <a href="<?= BASE_URL ?>requests/view.php">Find Help Requests</a>
        <a href="<?= BASE_URL ?>offers/view.php">Browse Help Offers</a>
        <a href="<?= BASE_URL ?>community/index.php">Community Stories</a>
        <a href="<?= BASE_URL ?>requests/create.php?urgency=emergency">Emergency Assistance</a>
      </div>
    </div>

    <div>
      <h4 style="font-size:16px; margin-bottom:14px;">Safety & Trust</h4>
      <div style="display:flex; flex-direction:column; gap:8px; font-size:14px; color:var(--text-muted);">
        <a href="#safety">Community Guidelines</a>
        <a href="#privacy">Privacy & Approximate Location</a>
        <a href="#reputation">Reputation & Badges</a>
        <a href="<?= BASE_URL ?>admin/">Administrator Portal</a>
      </div>
    </div>

    <div>
      <h4 style="font-size:16px; margin-bottom:14px;">Emergency Disclaimer</h4>
      <p style="font-size:13px; color:var(--text-muted); line-height:1.5;">
        For critical medical, police, or life-threatening emergencies, please immediately dial national emergency authorities (112 / 108). HelpHub complements community aid.
      </p>
    </div>
  </div>

  <div style="text-align:center; border-top:1px solid var(--border-color); padding-top:20px; font-size:13px; color:var(--text-muted);">
    © <?= date('Y') ?> HELPHUB Platform • Built with PHP 8, MySQL & Vanilla JS • Production Full-Stack Edition
  </div>
</footer>

<!-- MOBILE BOTTOM NAVIGATION -->
<nav class="bottom-nav">
  <a href="<?= BASE_URL ?>index.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">
    <span style="font-size:18px;">🏠</span>
    <span>Home</span>
  </a>
  <a href="<?= BASE_URL ?>requests/view.php">
    <span style="font-size:18px;">🔎</span>
    <span>Find</span>
  </a>
  <a href="<?= BASE_URL ?>requests/create.php" style="background:var(--primary); color:#fff; width:38px; height:38px; border-radius:50%; display:grid; place-items:center; margin-top:-14px; box-shadow:0 4px 10px var(--primary-glow);">
    <span style="font-size:20px; color:#fff;">+</span>
  </a>
  <a href="<?= BASE_URL ?>messages/index.php">
    <span style="font-size:18px;">💬</span>
    <span>Chat</span>
  </a>
  <a href="<?= BASE_URL ?>dashboard/index.php">
    <span style="font-size:18px;">👤</span>
    <span>Me</span>
  </a>
</nav>

<!-- REVIEW & RATING MODAL -->
<div class="modal hidden" id="reviewModal">
  <div class="modal-box">
    <button class="close-btn" data-close>×</button>
    <h3 style="font-size:22px; margin-bottom:8px;">Rate & Review Experience</h3>
    <p style="color:var(--text-muted); font-size:14px; margin-bottom:16px;">
      Share your feedback for <strong id="revieweeTargetName">Member</strong>. Your review updates their reputation score and helps keep the community safe.
    </p>

    <form id="reviewForm">
      <input type="hidden" id="reviewRequestId" name="request_id">
      <input type="hidden" id="reviewRevieweeId" name="reviewee_id">

      <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Rating (1 to 5 Stars)</label>
      <select name="rating" id="reviewRatingSelect" style="width:100%; padding:10px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); margin-bottom:14px;" required>
        <option value="5">⭐⭐⭐⭐⭐ 5 Stars - Exceptional Help</option>
        <option value="4">⭐⭐⭐⭐ 4 Stars - Very Good</option>
        <option value="3">⭐⭐⭐ 3 Stars - Average</option>
        <option value="2">⭐⭐ 2 Stars - Subpar</option>
        <option value="1">⭐ 1 Star - Unsatisfactory</option>
      </select>

      <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Your Written Review</label>
      <textarea name="review_text" placeholder="Describe how they helped you or how the coordination went..." style="width:100%; min-height:100px; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); margin-bottom:16px;" required></textarea>

      <button type="submit" class="btn btn-primary" style="width:100%;">Submit Official Rating</button>
    </form>
  </div>
</div>

<!-- LEAFLET MAP SCRIPT -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>

<script>
// Handle review modal submission
if ($('#reviewForm')) {
  $('#reviewForm').onsubmit = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());
    try {
      const res = await apiFetch('<?= BASE_URL ?>api/reviews.php', {
        method: 'POST',
        body: JSON.stringify(payload)
      });
      showToast(res.message || 'Review submitted successfully!', 'success');
      closeModal('#reviewModal');
      setTimeout(() => location.reload(), 1000);
    } catch (err) {
      showToast(err.message || 'Failed to submit review', 'error');
    }
  };
}
</script>
</body>
</html>
