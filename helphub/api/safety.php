<?php
// ============================================================
// HELPHUB 2.0 PRO - Safety, Trust & Moderation API
// ============================================================

require_once __DIR__ . '/../includes/functions.php';

require_login(true);
$pdo = get_pdo();
$currentUserId = (int)$_SESSION['user']['id'];
$action = $_GET['action'] ?? '';
$body = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

// 1. REPORT USER / REQUEST / POST
if ($action === 'report') {
    $itemType = trim($body['item_type'] ?? 'user');
    $itemId = (int)($body['item_id'] ?? 0);
    $reportedUserId = !empty($body['reported_user_id']) ? (int)$body['reported_user_id'] : null;
    $reason = trim($body['reason'] ?? 'Inappropriate behavior');
    $details = trim($body['details'] ?? '');

    if (!$itemId || !in_array($itemType, ['user', 'request', 'post', 'message'])) {
        json_response(false, 'Invalid report parameters.', [], 422);
    }

    $stmt = $pdo->prepare("INSERT INTO reports (reporter_id, reported_user_id, item_type, item_id, reason, details) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$currentUserId, $reportedUserId, $itemType, $itemId, $reason, $details]);

    json_response(true, 'Report submitted. Our moderation team will investigate promptly.');
}

// 2. BLOCK USER
if ($action === 'block') {
    $targetId = (int)($body['target_user_id'] ?? 0);
    if (!$targetId || $targetId === $currentUserId) {
        json_response(false, 'Invalid user to block.', [], 422);
    }

    $stmt = $pdo->prepare("INSERT IGNORE INTO blocked_users (blocker_id, blocked_id) VALUES (?, ?)");
    $stmt->execute([$currentUserId, $targetId]);

    json_response(true, 'User has been blocked. They can no longer initiate chat with you.');
}

// 3. UNBLOCK USER
if ($action === 'unblock') {
    $targetId = (int)($body['target_user_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
    $stmt->execute([$currentUserId, $targetId]);

    json_response(true, 'User unblocked.');
}

json_response(false, 'Unknown safety action.', [], 400);
