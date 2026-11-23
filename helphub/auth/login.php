<?php
$pageTitle = "Login to Your Account | HELPHUB";
require_once __DIR__ . '/../includes/header.php';
if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'dashboard/index.php');
    exit;
}
?>

<main class="section" style="max-width:480px; margin-top:20px;">
  <div class="card" style="padding:36px;">
    <div style="text-align:center; margin-bottom:24px;">
      <div class="brand-icon" style="margin:0 auto 12px; width:48px; height:48px; font-size:22px;">❤</div>
      <h1 style="font-size:24px; font-family:var(--font-heading);">Welcome Back</h1>
      <p style="color:var(--text-muted); font-size:14px;">Sign in to your HelpHub account</p>
    </div>

    <form id="pageLoginForm">
      <div style="margin-bottom:16px;">
        <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Email or Username</label>
        <input name="email" placeholder="you@domain.com or username" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required autofocus>
      </div>

      <div style="margin-bottom:20px;">
        <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px;">Password</label>
        <input type="password" name="password" placeholder="••••••••" style="width:100%; padding:12px; border-radius:var(--radius-md); border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-main);" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; padding:12px; font-size:15px; margin-bottom:18px;">Sign In →</button>
    </form>

    <div style="background:var(--bg-card-subtle); padding:14px; border-radius:var(--radius-md); font-size:12.5px; color:var(--text-muted); text-align:center; margin-bottom:20px;">
      <strong>Demo Credentials:</strong><br>
      Admin: <code>admin@helphub.org</code> / <code>admin123</code><br>
      Member: <code>rahul@example.com</code> / <code>user123</code>
    </div>

    <p style="text-align:center; font-size:14px; color:var(--text-muted);">
      Don't have an account? <a href="<?= BASE_URL ?>auth/register.php" style="color:var(--primary); font-weight:700;">Create one here</a>
    </p>
  </div>
</main>

<script>
$('#pageLoginForm').onsubmit = async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = Object.fromEntries(fd.entries());

  try {
    const res = await apiFetch('<?= BASE_URL ?>api/auth.php?action=login', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
    showToast(`Welcome back, ${res.data.user.full_name}!`, 'success');
    setTimeout(() => {
      location.href = res.data.user.role === 'admin' ? '<?= BASE_URL ?>admin/' : '<?= BASE_URL ?>dashboard/index.php';
    }, 600);
  } catch (err) {
    showToast(err.message || 'Login failed', 'error');
  }
};
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
