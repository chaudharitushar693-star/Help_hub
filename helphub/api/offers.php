<?php
// ============================================================
// HELPHUB 2.0 PRO - Help Offers API Endpoint
// ============================================================

require_once __DIR__ . '/../includes/functions.php';

$pdo = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    $catId = (int)($_GET['category_id'] ?? 0);
    $userId = (int)($_GET['user_id'] ?? 0);

    $sql = "
        SELECT o.*, c.name AS category_name, c.icon AS category_icon,
               u.full_name AS helper_name, u.username AS helper_username,
               u.avatar AS helper_avatar, u.city AS helper_city,
               u.rating AS helper_rating, u.total_reviews AS helper_reviews,
               u.reputation_points AS helper_reputation, u.helps_given
        FROM help_offers o
        JOIN categories c ON c.id = o.category_id
        JOIN users u ON u.id = o.user_id
        WHERE o.status = 'active'
    ";
    $params = [];

    if ($userId > 0) {
        $sql .= " AND o.user_id = ?";
        $params[] = $userId;
    }

    if ($catId > 0) {
        $sql .= " AND o.category_id = ?";
        $params[] = $catId;
    }

    if ($q) {
        $sql .= " AND (o.title LIKE ? OR o.description LIKE ? OR o.skills LIKE ? OR o.location LIKE ?)";
        $term = "%{$q}%";
        $params = array_merge($params, [$term, $term, $term, $term]);
    }

    $sql .= " ORDER BY o.created_at DESC LIMIT 60";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $offers = $stmt->fetchAll();

    json_response(true, 'Help offers fetched', ['offers' => $offers]);
}

if ($method === 'POST') {
    require_login(true);
    $userId = $_SESSION['user']['id'];
    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

    $title = trim($body['title'] ?? '');
    $description = trim($body['description'] ?? '');
    $catId = (int)($body['category_id'] ?? 1);
    $skills = trim($body['skills'] ?? '');
    $experience = trim($body['experience'] ?? '');
    $location = trim($body['location'] ?? $_SESSION['user']['city'] ?? 'Bhopal');
    $availability = trim($body['availability'] ?? 'Flexible');
    $aboutService = trim($body['about_service'] ?? '');

    if (!$title || !$description || !$skills) {
        json_response(false, 'Please provide title, description, and key skills.', [], 422);
    }

    $stmt = $pdo->prepare("
        INSERT INTO help_offers (user_id, title, description, category_id, skills, experience, location, availability, about_service, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$userId, $title, $description, $catId, $skills, $experience, $location, $availability, $aboutService]);
    $newId = (int)$pdo->lastInsertId();

    award_reputation($pdo, $userId, 15, 'Created a public help offer');

    json_response(true, 'Your help offer has been published to the community!', ['id' => $newId]);
}

json_response(false, 'Method not allowed', [], 405);
