<?php
// ============================================================
// HELPHUB 2.0 PRO - Rating & Reviews API Endpoint
// ============================================================

require_once __DIR__ . '/../includes/functions.php';

require_login(true);
$pdo = get_pdo();
$reviewerId = (int)$_SESSION['user']['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $userId = (int)($_GET['user_id'] ?? 0);
    if (!$userId) {
        json_response(false, 'User ID required', [], 422);
    }

    $stmt = $pdo->prepare("
        SELECT r.*, u.full_name AS reviewer_name, u.avatar AS reviewer_avatar,
               req.title AS request_title
        FROM reviews r
        JOIN users u ON u.id = r.reviewer_id
        LEFT JOIN help_requests req ON req.id = r.request_id
        WHERE r.reviewee_id = ?
        ORDER BY r.id DESC
    ");
    $stmt->execute([$userId]);
    $reviews = $stmt->fetchAll();

    json_response(true, 'Reviews loaded', ['reviews' => $reviews]);
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

    $requestId = (int)($body['request_id'] ?? 0);
    $revieweeId = (int)($body['reviewee_id'] ?? 0);
    $rating = (int)($body['rating'] ?? 5);
    $reviewText = trim($body['review_text'] ?? '');

    if (!$requestId || !$revieweeId || $rating < 1 || $rating > 5 || strlen($reviewText) < 4) {
        json_response(false, 'Please provide a valid rating (1-5) and feedback description.', [], 422);
    }

    if ($reviewerId === $revieweeId) {
        json_response(false, 'You cannot review yourself.', [], 422);
    }

    // Verify request is completed and that user was a participant
    $reqStmt = $pdo->prepare("SELECT user_id, accepted_helper_id, status FROM help_requests WHERE id = ?");
    $reqStmt->execute([$requestId]);
    $request = $reqStmt->fetch();

    if (!$request) {
        json_response(false, 'Associated help request was not found.', [], 404);
    }

    $isRequester = ($request['user_id'] == $reviewerId && $request['accepted_helper_id'] == $revieweeId);
    $isHelper = ($request['accepted_helper_id'] == $reviewerId && $request['user_id'] == $revieweeId);

    if (!$isRequester && !$isHelper) {
        json_response(false, 'Only participants of this completed help request can leave a review.', [], 403);
    }

    // Check duplicate review
    $dup = $pdo->prepare("SELECT id FROM reviews WHERE request_id = ? AND reviewer_id = ? AND reviewee_id = ?");
    $dup->execute([$requestId, $reviewerId, $revieweeId]);
    if ($dup->fetch()) {
        json_response(false, 'You have already submitted a review for this completed request.', [], 409);
    }

    // Insert review
    $stmt = $pdo->prepare("INSERT INTO reviews (request_id, reviewer_id, reviewee_id, rating, review_text) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$requestId, $reviewerId, $revieweeId, $rating, $reviewText]);

    // Update target user's aggregate rating and total_reviews
    $agg = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as cnt FROM reviews WHERE reviewee_id = ?");
    $agg->execute([$revieweeId]);
    $stats = $agg->fetch();

    $newRating = round((float)$stats['avg_rating'], 2);
    $totalReviews = (int)$stats['cnt'];

    $updateUser = $pdo->prepare("UPDATE users SET rating = ?, total_reviews = ? WHERE id = ?");
    $updateUser->execute([$newRating, $totalReviews, $revieweeId]);

    // Award +5 reputation points for helpful review
    award_reputation($pdo, $revieweeId, 5, 'Received helpful community review');

    // Send notification to reviewee
    add_notification(
        $pdo,
        $revieweeId,
        $reviewerId,
        'review',
        "New {$rating}-Star Review Received! ⭐",
        "{$_SESSION['user']['full_name']} reviewed you: \"" . mb_strimwidth($reviewText, 0, 80, '...') . "\"",
        "profile/profile.php?id={$revieweeId}"
    );

    json_response(true, 'Thank you! Your rating and feedback have been recorded.', [
        'new_rating' => $newRating,
        'total_reviews' => $totalReviews
    ]);
}

json_response(false, 'Method not allowed', [], 405);
