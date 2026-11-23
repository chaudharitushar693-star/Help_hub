<?php
// ============================================================
// HELPHUB 2.0 PRO - System Configuration
// ============================================================

if (!defined('HELPHUB_INIT')) {
    define('HELPHUB_INIT', true);
}

// Platform Identity
define('APP_NAME', 'HELPHUB');
define('APP_TAGLINE', 'Connect. Help. Make a Difference.');
define('APP_VERSION', '2.5.0-PRO');

// Detect Base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseDir = preg_replace('#/(api|auth|dashboard|requests|offers|messages|profile|community|admin)(/.*)?$#', '', $scriptDir);
$baseDir = rtrim($baseDir, '/');

define('BASE_URL', $protocol . $host . $baseDir . '/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB

// Session initialization
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}
