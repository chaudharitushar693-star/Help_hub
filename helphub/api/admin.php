<?php
// ============================================================
// HELPHUB 2.0 PRO - Admin Control Center API
// ============================================================

require_once __DIR__ . '/../includes/functions.php';

require_admin(true);
$pdo = get_pdo();
$action = $_GET['action'] ?? '';
$body = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

// 1. DASHBOARD METRICS & ANALYTICS
if ($action === 'metrics') {
    $metrics = [
        'total_users' => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'active_users' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
        'help_requests' => (int)$pdo->query("SELECT COUNT(*) FROM help_requests")->fetchColumn(),
        'help_offers' => (int)$pdo->query("SELECT COUNT(*) FROM help_offers")->fetchColumn(),
        'completed_helps' => (int)$pdo->query("SELECT COUNT(*) FROM help_requests WHERE status = 'completed'")->fetchColumn(),
        'messages_count' => (int)$pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
        'pending_reports' => (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn(),
        'emergency_requests' => (int)$pdo->query("SELECT COUNT(*) FROM help_requests WHERE urgency = 'emergency'")->fetchColumn(),
    ];

    // Category distribution
    $catStmt = $pdo->query("
        SELECT c.name, COUNT(r.id) as request_count
        FROM categories c
        LEFT JOIN help_requests r ON r.category_id = c.id
        GROUP BY c.id
    ");
    $categories = $catStmt->fetchAll();

    json_response(true, 'Admin metrics loaded', [
        'metrics' => $metrics,
        'categories' => $categories
    ]);
}

// 2. USERS MANAGEMENT
if ($action === 'users') {
    $q = trim($_GET['q'] ?? '');
    $sql = "SELECT id, full_name, username, email, phone, city, role, status, reputation_points, rating, helps_given, created_at FROM users WHERE 1=1";
    $params = [];
    if ($q) {
        $sql .= " AND (full_name LIKE ? OR username LIKE ? OR email LIKE ? OR city LIKE ?)";
        $term = "%{$q}%";
        $params = [$term, $term, $term, $term];
    }
    $sql .= " ORDER BY id DESC LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    json_response(true, 'Users loaded', ['users' => $stmt->fetchAll()]);
}

// 3. SET USER STATUS (SUSPEND / ACTIVATE / BLOCK)
if ($action === 'set_user_status') {
    $targetId = (int)($body['user_id'] ?? 0);
    $status = trim($body['status'] ?? 'active');

    if (!$targetId || !in_array($status, ['active', 'suspended', 'blocked'])) {
        json_response(false, 'Invalid parameters', [], 422);
    }

    if ($targetId === (int)$_SESSION['user']['id']) {
        json_response(false, 'Cannot change your own administrative status', [], 400);
    }

    $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$status, $targetId]);
    json_response(true, "User status updated to {$status}");
}

// 4. REPORTS LIST
if ($action === 'reports') {
    $status = trim($_GET['status'] ?? '');
    $sql = "
        SELECT rep.*, u.full_name AS reporter_name, u.email AS reporter_email,
               ru.full_name AS reported_name
        FROM reports rep
        JOIN users u ON u.id = rep.reporter_id
        LEFT JOIN users ru ON ru.id = rep.reported_user_id
        WHERE 1=1
    ";
    $params = [];
    if ($status) {
        $sql .= " AND rep.status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY rep.id DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    json_response(true, 'Reports loaded', ['reports' => $stmt->fetchAll()]);
}

// 5. RESOLVE REPORT
if ($action === 'resolve_report') {
    $repId = (int)($body['report_id'] ?? 0);
    $newStatus = trim($body['status'] ?? 'resolved');
    if (!$repId || !in_array($newStatus, ['investigating', 'resolved', 'dismissed'])) {
        json_response(false, 'Invalid report parameters', [], 422);
    }
    $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?")->execute([$newStatus, $repId]);
    json_response(true, "Report marked as {$newStatus}");
}

// 6. DELETE REQUEST
if ($action === 'delete_request') {
    $reqId = (int)($body['request_id'] ?? 0);
    if (!$reqId) {
        json_response(false, 'Request ID required', [], 422);
    }
    $pdo->prepare("DELETE FROM help_requests WHERE id = ?")->execute([$reqId]);
    json_response(true, "Help request deleted by administrator");
}

json_response(false, 'Unknown admin action', [], 400);
