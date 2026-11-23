<?php
// ============================================================
// HELPHUB 2.0 PRO - Smart Matching Engine API
// ============================================================

require_once __DIR__ . '/../includes/functions.php';

$pdo = get_pdo();
$requestId = (int)($_GET['request_id'] ?? 0);

if (!$requestId) {
    json_response(false, 'Request ID required.', [], 422);
}

$reqStmt = $pdo->prepare("SELECT * FROM help_requests WHERE id = ?");
$reqStmt->execute([$requestId]);
$req = $reqStmt->fetch();

if (!$req) {
    json_response(false, 'Help request not found.', [], 404);
}

$helpersStmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.username, u.avatar, u.city, u.skills, u.rating, u.reputation_points, u.helps_given,
           u.latitude, u.longitude, o.id AS offer_id, o.title AS offer_title, o.category_id
    FROM users u
    LEFT JOIN help_offers o ON o.user_id = u.id AND o.status = 'active'
    WHERE u.id != ? AND u.status = 'active'
");
$helpersStmt->execute([$req['user_id']]);
$helpers = $helpersStmt->fetchAll();

$ranked = [];
foreach ($helpers as $h) {
    $calc = calculate_match_score($req, $h);
    $ranked[] = array_merge($h, [
        'match_score' => $calc['score'],
        'match_label' => $calc['label'],
        'distance_km' => $calc['distance_km']
    ]);
}

usort($ranked, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
json_response(true, 'Matches calculated', ['matches' => array_slice($ranked, 0, 5)]);
