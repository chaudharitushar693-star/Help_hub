<?php
// ============================================================
// HELPHUB 2.0 PRO - Help Requests API Endpoint
// ============================================================

require_once __DIR__ . '/../includes/functions.php';

$pdo = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 1. GET SINGLE REQUEST OR LIST
if ($method === 'GET') {
    if (!empty($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("
            SELECT r.*, c.name AS category_name, c.icon AS category_icon,
                   u.full_name AS requester_name, u.username AS requester_username,
                   u.avatar AS requester_avatar, u.city AS requester_city,
                   u.rating AS requester_rating, u.total_reviews AS requester_reviews,
                   u.reputation_points AS requester_reputation,
                   h.full_name AS helper_name, h.username AS helper_username
            FROM help_requests r
            JOIN categories c ON c.id = r.category_id
            JOIN users u ON u.id = r.user_id
            LEFT JOIN users h ON h.id = r.accepted_helper_id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $request = $stmt->fetch();

        if (!$request) {
            json_response(false, 'Help request not found.', [], 404);
        }

        // Fetch top smart matches for this request
        $matchesStmt = $pdo->prepare("
            SELECT u.id, u.full_name, u.username, u.avatar, u.city, u.skills, u.rating, u.reputation_points, u.helps_given,
                   o.id AS offer_id, o.title AS offer_title, o.category_id
            FROM users u
            LEFT JOIN help_offers o ON o.user_id = u.id AND o.status = 'active'
            WHERE u.id != ? AND u.status = 'active'
            LIMIT 15
        ");
        $matchesStmt->execute([$request['user_id']]);
        $potentialHelpers = $matchesStmt->fetchAll();

        $rankedMatches = [];
        foreach ($potentialHelpers as $helper) {
            $calc = calculate_match_score($request, $helper);
            $rankedMatches[] = array_merge($helper, [
                'match_score' => $calc['score'],
                'match_label' => $calc['label'],
                'distance_km' => $calc['distance_km']
            ]);
        }
        usort($rankedMatches, fn($a, $b) => $b['match_score'] <=> $a['match_score']);

        json_response(true, 'Request retrieved', [
            'request' => $request,
            'matches' => array_slice($rankedMatches, 0, 5)
        ]);
    }

    // List Requests
    $q = trim($_GET['q'] ?? '');
    $catId = (int)($_GET['category_id'] ?? 0);
    $urgency = trim($_GET['urgency'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $userId = (int)($_GET['user_id'] ?? 0);
    $sort = trim($_GET['sort'] ?? 'newest');

    $sql = "
        SELECT r.*, c.name AS category_name, c.icon AS category_icon,
               u.full_name AS requester_name, u.username AS requester_username,
               u.avatar AS requester_avatar, u.city AS requester_city,
               u.rating AS requester_rating, u.reputation_points AS requester_reputation
        FROM help_requests r
        JOIN categories c ON c.id = r.category_id
        JOIN users u ON u.id = r.user_id
        WHERE 1=1
    ";
    $params = [];

    if ($userId > 0) {
        $sql .= " AND r.user_id = ?";
        $params[] = $userId;
    }

    if ($catId > 0) {
        $sql .= " AND r.category_id = ?";
        $params[] = $catId;
    }

    if ($urgency) {
        $sql .= " AND r.urgency = ?";
        $params[] = $urgency;
    }

    if ($status) {
        $sql .= " AND r.status = ?";
        $params[] = $status;
    } else if ($userId === 0) {
        // By default show open and active requests on public search
        $sql .= " AND r.status IN ('open', 'in_discussion', 'accepted', 'in_progress')";
    }

    if ($q) {
        $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR r.location LIKE ? OR r.skills_required LIKE ?)";
        $term = "%{$q}%";
        $params = array_merge($params, [$term, $term, $term, $term]);
    }

    if ($sort === 'urgent') {
        $sql .= " ORDER BY FIELD(r.urgency, 'emergency', 'urgent', 'important', 'normal'), r.created_at DESC";
    } else {
        $sql .= " ORDER BY r.created_at DESC";
    }

    $sql .= " LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $list = $stmt->fetchAll();

    json_response(true, 'Help requests fetched', ['requests' => $list, 'count' => count($list)]);
}

// 2. CREATE HELP REQUEST
if ($method === 'POST' && empty($action)) {
    require_login(true);
    $userId = $_SESSION['user']['id'];
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

    $title = trim($body['title'] ?? '');
    $description = trim($body['description'] ?? '');
    $catId = (int)($body['category_id'] ?? 1);
    $location = trim($body['location'] ?? $_SESSION['user']['city'] ?? 'Bhopal');
    $requiredDate = !empty($body['required_date']) ? $body['required_date'] : date('Y-m-d', strtotime('+3 days'));
    $urgency = in_array($body['urgency'] ?? '', ['normal', 'important', 'urgent', 'emergency']) ? $body['urgency'] : 'normal';
    $skills = trim($body['skills_required'] ?? '');
    $additional = trim($body['additional_info'] ?? '');

    if (!$title || !$description || !$location) {
        json_response(false, 'Please fill in all required fields (title, description, location).', [], 422);
    }

    $stmt = $pdo->prepare("
        INSERT INTO help_requests (user_id, title, description, category_id, location, required_date, urgency, skills_required, additional_info, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')
    ");
    $stmt->execute([$userId, $title, $description, $catId, $location, $requiredDate, $urgency, $skills, $additional]);
    $newId = (int)$pdo->lastInsertId();

    // Reward points for initiating community connection
    award_reputation($pdo, $userId, 10, 'Created help request');

    // Notify matching helpers in city
    $notifyStmt = $pdo->prepare("SELECT id FROM users WHERE id != ? AND (city = ? OR skills LIKE ?) LIMIT 5");
    $notifyStmt->execute([$userId, $location, "%{$skills}%"]);
    $matchingUsers = $notifyStmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($matchingUsers as $mUserId) {
        add_notification(
            $pdo,
            $mUserId,
            $userId,
            'nearby_request',
            'New Help Request in Your Area',
            "{$_SESSION['user']['full_name']} posted: \"{$title}\"",
            "requests/view.php?id={$newId}"
        );
    }

    json_response(true, 'Help request posted successfully! Suitable helpers will be matched automatically.', [
        'id' => $newId,
        'title' => $title
    ]);
}

// 3. UPDATE REQUEST STATUS (ACCEPT / IN PROGRESS / COMPLETE / CANCEL)
if ($method === 'POST' && $action === 'update_status') {
    require_login(true);
    $currentUserId = $_SESSION['user']['id'];
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

    $requestId = (int)($body['request_id'] ?? 0);
    $newStatus = trim($body['status'] ?? '');
    $helperId = !empty($body['helper_id']) ? (int)$body['helper_id'] : null;

    if (!$requestId || !in_array($newStatus, ['open', 'in_discussion', 'accepted', 'in_progress', 'completed', 'cancelled'])) {
        json_response(false, 'Invalid request or status.', [], 422);
    }

    $check = $pdo->prepare("SELECT * FROM help_requests WHERE id = ?");
    $check->execute([$requestId]);
    $request = $check->fetch();

    if (!$request) {
        json_response(false, 'Help request not found.', [], 404);
    }

    $isOwner = ($request['user_id'] == $currentUserId);
    $isAdmin = ($_SESSION['user']['role'] === 'admin');
    $isAcceptedHelper = ($request['accepted_helper_id'] == $currentUserId);

    if (!$isOwner && !$isAdmin && !$isAcceptedHelper && $newStatus !== 'in_discussion') {
        json_response(false, 'You do not have permission to alter this request status.', [], 403);
    }

    // Status transition updates
    if ($newStatus === 'accepted' && $helperId) {
        $update = $pdo->prepare("UPDATE help_requests SET status = 'accepted', accepted_helper_id = ? WHERE id = ?");
        $update->execute([$helperId, $requestId]);

        add_notification($pdo, $helperId, $currentUserId, 'help_accepted', 'Help Offer Accepted!', "You were selected as the helper for: {$request['title']}", "requests/view.php?id={$requestId}");
    } elseif ($newStatus === 'completed') {
        $update = $pdo->prepare("UPDATE help_requests SET status = 'completed' WHERE id = ?");
        $update->execute([$requestId]);

        // Award +20 points to Helper and increment helps_given
        $targetHelperId = $request['accepted_helper_id'] ?: $helperId;
        if ($targetHelperId) {
            $pdo->prepare("UPDATE users SET helps_given = helps_given + 1 WHERE id = ?")->execute([$targetHelperId]);
            award_reputation($pdo, $targetHelperId, 20, 'Completed help request');
            add_notification($pdo, $targetHelperId, $request['user_id'], 'help_completed', 'Help Request Completed! ⭐', "Help request completed! Please leave a mutual rating.", "requests/view.php?id={$requestId}&action=rate");
        }

        // Award +10 points to Requester and increment helps_received
        $pdo->prepare("UPDATE users SET helps_received = helps_received + 1 WHERE id = ?")->execute([$request['user_id']]);
        award_reputation($pdo, $request['user_id'], 10, 'Marked request as resolved');
    } else {
        $update = $pdo->prepare("UPDATE help_requests SET status = ? WHERE id = ?");
        $update->execute([$newStatus, $requestId]);
    }

    json_response(true, "Request status updated to {$newStatus}.", ['status' => $newStatus]);
}

json_response(false, 'Invalid API method or action', [], 400);
