<?php
$pageTitle = "Create an Account | HELPHUB";
require_once __DIR__ . '/../includes/header.php';
if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'dashboard/index.php');
    exit;
}
?>

<main class="section" style="max-width:680px; margin-top:10px;">
  <div class="card" style="padding:36px;">
    <div style="text-align:center; margin-bottom:24px;">
      <div class="brand-icon" style="margin:0 auto 12px; width:48px; height:48px; font-size:22px;">❤</div>
      <h1 style="font-size:26px; font-family:var(--font-heading);">Join HelpHub Community</h1>
      <p style="color:var(--text-muted); font-size:14px;">Connect, help neighbors, and build a verified local reputation</p>
    </div>

    <form id="pageRegisterForm">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
        <div>
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Full Name *</label>
          <input name="full_name" placeholder="e.g. Rahul Sharma" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
        </div>
        <div>
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Username *</label>
          <input name="username" placeholder="e.g. rahul_tech" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
        <div>
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Email Address *</label>
          <input type="email" name="email" placeholder="rahul@example.com" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
        </div>
        <div>
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Phone Number</label>
          <input name="phone" placeholder="+91 98260 00000" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);">
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
        <div>
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Password (min 6 chars) *</label>
          <input type="password" id="regPassword" name="password" minlength="6" placeholder="••••••••" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
        </div>
        <div>
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Confirm Password *</label>
          <input type="password" id="regConfirmPassword" name="confirm_password" minlength="6" placeholder="••••••••" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
        <div>
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">City / Region *</label>
          <input name="city" value="Bhopal" placeholder="e.g. Bhopal" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
        </div>
        <div>
          <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Skills or Services You Offer</label>
          <input name="skills" placeholder="e.g. Teaching, Computer Repair, First Aid" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);">
        </div>
      </div>

      <div style="margin-bottom:24px;">
        <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Short Bio / About You</label>
        <textarea name="bio" rows="3" placeholder="Tell neighbors a bit about your background and how you love to help..." style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);"></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width:100%; margin-bottom:18px;">Create My Account →</button>
    </form>

    <p style="text-align:center; font-size:14px; color:var(--text-muted);">
      Already have an account? <a href="<?= BASE_URL ?>auth/login.php" style="color:var(--primary); font-weight:700;">Sign in here</a>
    </p>
  </div>
</main>

<script>
$('#pageRegisterForm').onsubmit = async (e) => {
  e.preventDefault();
  const pwd = $('#regPassword').value;
  const cpwd = $('#regConfirmPassword').value;

  if (pwd !== cpwd) {
    showToast('Passwords do not match. Please re-enter.', 'warning');
    return;
  }

  const fd = new FormData(e.target);
  const payload = Object.fromEntries(fd.entries());

  try {
    const res = await apiFetch('<?= BASE_URL ?>api/auth.php?action=register', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
    showToast(res.message || 'Account created successfully!', 'success');
    setTimeout(() => {
      location.href = '<?= BASE_URL ?>dashboard/index.php';
    }, 800);
  } catch (err) {
    showToast(err.message || 'Registration failed', 'error');
  }
};
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
