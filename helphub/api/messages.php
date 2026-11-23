<?php
// ============================================================
// HELPHUB 2.0 PRO - Private Messaging Engine (Real-Time AJAX)
// ============================================================

require_once __DIR__ . '/../includes/functions.php';

require_login(true);
$pdo = get_pdo();
$currentUserId = (int)$_SESSION['user']['id'];
$action = $_GET['action'] ?? '';
$body = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

// 1. LIST USER'S CONVERSATIONS
if ($action === 'conversations') {
    $stmt = $pdo->prepare("
        SELECT c.id AS conversation_id, c.request_id, c.updated_at,
               u.id AS other_user_id, u.full_name AS other_user_name,
               u.username AS other_username, u.avatar AS other_avatar,
               u.city AS other_city, u.rating AS other_rating,
               (
                   SELECT message FROM messages m 
                   WHERE m.conversation_id = c.id AND m.is_deleted = 0 
                   ORDER BY m.id DESC LIMIT 1
               ) AS last_message,
               (
                   SELECT created_at FROM messages m 
                   WHERE m.conversation_id = c.id AND m.is_deleted = 0 
                   ORDER BY m.id DESC LIMIT 1
               ) AS last_message_time,
               (
                   SELECT COUNT(*) FROM messages m 
                   WHERE m.conversation_id = c.id AND m.receiver_id = ? AND m.read_status = 0
               ) AS unread_count
        FROM conversations c
        JOIN conversation_members cm1 ON cm1.conversation_id = c.id AND cm1.user_id = ?
        JOIN conversation_members cm2 ON cm2.conversation_id = c.id AND cm2.user_id != ?
        JOIN users u ON u.id = cm2.user_id
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute([$currentUserId, $currentUserId, $currentUserId]);
    $convs = $stmt->fetchAll();

    json_response(true, 'Conversations fetched', ['conversations' => $convs]);
}

// 2. FETCH MESSAGE HISTORY FOR CONVERSATION
if ($action === 'history') {
    $convId = (int)($_GET['conversation_id'] ?? 0);
    if (!$convId) {
        json_response(false, 'Conversation ID required.', [], 422);
    }

    // Verify membership
    $check = $pdo->prepare("SELECT id FROM conversation_members WHERE conversation_id = ? AND user_id = ?");
    $check->execute([$convId, $currentUserId]);
    if (!$check->fetch()) {
        json_response(false, 'Unauthorized access to conversation.', [], 403);
    }

    // Mark unread messages as read
    $mark = $pdo->prepare("UPDATE messages SET read_status = 1 WHERE conversation_id = ? AND receiver_id = ? AND read_status = 0");
    $mark->execute([$convId, $currentUserId]);

    // Fetch conversation recipient details
    $otherStmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.username, u.avatar, u.city, u.skills, u.rating
        FROM conversation_members cm
        JOIN users u ON u.id = cm.user_id
        WHERE cm.conversation_id = ? AND cm.user_id != ?
    ");
    $otherStmt->execute([$convId, $currentUserId]);
    $otherUser = $otherStmt->fetch();

    // Fetch messages
    $msgStmt = $pdo->prepare("
        SELECT id, conversation_id, sender_id, receiver_id, message, attachment, read_status, is_deleted, created_at
        FROM messages
        WHERE conversation_id = ?
        ORDER BY id ASC
        LIMIT 200
    ");
    $msgStmt->execute([$convId]);
    $messages = $msgStmt->fetchAll();

    json_response(true, 'Messages loaded', [
        'conversation_id' => $convId,
        'other_user' => $otherUser,
        'messages' => $messages
    ]);
}

// 3. SEND A MESSAGE (AJAX without refresh)
if ($action === 'send') {
    $convId = (int)($body['conversation_id'] ?? 0);
    $recipientId = (int)($body['receiver_id'] ?? 0);
    $messageText = trim($body['message'] ?? '');

    if (!$convId || !$messageText) {
        json_response(false, 'Message text and conversation ID are required.', [], 422);
    }

    // Check if receiver has blocked or is blocked by current user
    if ($recipientId) {
        $blockCheck = $pdo->prepare("SELECT id FROM blocked_users WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)");
        $blockCheck->execute([$currentUserId, $recipientId, $recipientId, $currentUserId]);
        if ($blockCheck->fetch()) {
            json_response(false, 'Messaging is unavailable due to privacy/block settings.', [], 403);
        }
    } else {
        // Find recipient from conversation members
        $findRecipient = $pdo->prepare("SELECT user_id FROM conversation_members WHERE conversation_id = ? AND user_id != ?");
        $findRecipient->execute([$convId, $currentUserId]);
        $recipientId = (int)$findRecipient->fetchColumn();
    }

    // Insert message
    $insert = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, receiver_id, message, read_status) VALUES (?, ?, ?, ?, 0)");
    $insert->execute([$convId, $currentUserId, $recipientId, $messageText]);
    $msgId = (int)$pdo->lastInsertId();

    // Update conversation timestamp
    $pdo->prepare("UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$convId]);

    // Send notification to recipient
    add_notification(
        $pdo,
        $recipientId,
        $currentUserId,
        'message',
        "New message from {$_SESSION['user']['full_name']}",
        mb_strimwidth($messageText, 0, 80, '...'),
        "messages/index.php?conv={$convId}"
    );

    json_response(true, 'Message sent successfully', [
        'id' => $msgId,
        'conversation_id' => $convId,
        'sender_id' => $currentUserId,
        'receiver_id' => $recipientId,
        'message' => $messageText,
        'created_at' => date('Y-m-d H:i:s'),
        'read_status' => 0
    ]);
}

// 4. START OR GET CONVERSATION WITH USER
if ($action === 'start') {
    $targetUserId = (int)($body['target_user_id'] ?? $_GET['target_user_id'] ?? 0);
    $requestId = !empty($body['request_id']) ? (int)$body['request_id'] : null;

    if (!$targetUserId || $targetUserId === $currentUserId) {
        json_response(false, 'Cannot start conversation with yourself or invalid recipient.', [], 422);
    }

    // Check if an existing 1-on-1 conversation exists
    $findStmt = $pdo->prepare("
        SELECT cm1.conversation_id
        FROM conversation_members cm1
        JOIN conversation_members cm2 ON cm1.conversation_id = cm2.conversation_id
        WHERE cm1.user_id = ? AND cm2.user_id = ?
        LIMIT 1
    ");
    $findStmt->execute([$currentUserId, $targetUserId]);
    $existing = $findStmt->fetchColumn();

    if ($existing) {
        json_response(true, 'Existing conversation found', ['conversation_id' => (int)$existing]);
    }

    // Create new conversation
    $pdo->prepare("INSERT INTO conversations (request_id) VALUES (?)")->execute([$requestId]);
    $newConvId = (int)$pdo->lastInsertId();

    $addMembers = $pdo->prepare("INSERT INTO conversation_members (conversation_id, user_id) VALUES (?, ?), (?, ?)");
    $addMembers->execute([$newConvId, $currentUserId, $newConvId, $targetUserId]);

    // If an initial greeting message is passed
    $initialMsg = trim($body['initial_message'] ?? '');
    if ($initialMsg) {
        $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)")
            ->execute([$newConvId, $currentUserId, $targetUserId, $initialMsg]);
    }

    json_response(true, 'New conversation started', ['conversation_id' => $newConvId]);
}

// 5. DELETE OWN MESSAGE
if ($action === 'delete') {
    $msgId = (int)($body['message_id'] ?? 0);
    $del = $pdo->prepare("UPDATE messages SET is_deleted = 1, message = '[Message deleted by sender]' WHERE id = ? AND sender_id = ?");
    $del->execute([$msgId, $currentUserId]);
    json_response(true, 'Message deleted');
}

json_response(false, 'Unknown message action.', [], 400);
