<?php
// ============================================================
// HELPHUB 2.0 PRO - Database Configuration (PDO)
// ============================================================

$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('DB_NAME') ?: 'helphub';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_PORT = getenv('DB_PORT') ?: '3306';

try {
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // If running in standalone or fallback environment without live MySQL, handle gracefully
    $pdo = null;
    $db_connection_error = $e->getMessage();
}

function get_pdo() {
    global $pdo, $db_connection_error;
    if ($pdo === null) {
        if (php_sapi_name() !== 'cli' && (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed. Please ensure MySQL is running.',
                'error' => $db_connection_error ?? 'PDO instance is null'
            ]);
            exit;
        }
    }
    return $pdo;
}
