<?php
// ============================================================
// HELPHUB 2.0 PRO - Notifications API Endpoint
// ============================================================

require_once __DIR__ . '/../includes/functions.php';

require_login(true);
$pdo = get_pdo();
$userId = (int)$_SESSION['user']['id'];
$action = $_GET['action'] ?? '';

if ($action === 'read_all') {
    $stmt = $pdo->prepare("UPDATE notifications SET read_status = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
    json_response(true, 'All notifications marked as read.');
}

// Fetch list and unread count
$stmt = $pdo->prepare("
    SELECT n.*, u.full_name AS sender_name, u.avatar AS sender_avatar
    FROM notifications n
    LEFT JOIN users u ON u.id = n.sender_id
    WHERE n.user_id = ?
    ORDER BY n.id DESC
    LIMIT 50
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$unreadCountStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_status = 0");
$unreadCountStmt->execute([$userId]);
$unreadCount = (int)$unreadCountStmt->fetchColumn();

json_response(true, 'Notifications loaded', [
    'notifications' => $notifications,
    'unread_count' => $unreadCount
]);
