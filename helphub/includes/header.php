<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
$currentUser = current_user(get_pdo());
$pageTitle = $pageTitle ?? 'HELPHUB | Connect. Help. Make a Difference.';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="HelpHub - A full-stack social-help platform where people connect, offer skills, and resolve emergencies together.">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
  <!-- OpenStreetMap / Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
</head>
<body>

<!-- EMERGENCY TOP STRIP -->
<div class="emergency-banner">
  <span>🚨 <strong>Need Immediate Emergency Assistance?</strong> Post an urgent request or call local emergency responders.</span>
  <a href="<?= BASE_URL ?>requests/create.php?urgency=emergency">🆘 Emergency Help →</a>
</div>

<!-- NAVBAR -->
<header class="topbar">
  <a class="brand" href="<?= BASE_URL ?>index.php">
    <div class="brand-icon">❤</div>
    <span>HELP<span>HUB</span></span>
  </a>

  <nav class="nav-links">
    <a href="<?= BASE_URL ?>index.php">Home</a>
    <a href="<?= BASE_URL ?>requests/view.php">Find Help</a>
    <a href="<?= BASE_URL ?>offers/view.php">Offer Help</a>
    <a href="<?= BASE_URL ?>community/index.php">Community</a>
    <a href="<?= BASE_URL ?>messages/index.php">Messages</a>
    <a href="<?= BASE_URL ?>dashboard/index.php">Dashboard</a>
    <a href="<?= BASE_URL ?>presentation.html" target="_blank" style="color:var(--primary-color, #2563eb); font-weight:700;">🎓 College PPT</a>
  </nav>

  <div class="nav-actions" id="navActions">
    <button class="icon-btn" id="themeToggleBtn" title="Toggle Dark/Light Mode">☾</button>
    <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-outline btn-sm">Login</a>
    <a href="<?= BASE_URL ?>auth/register.php" class="btn btn-primary btn-sm">Register</a>
  </div>
</header>
