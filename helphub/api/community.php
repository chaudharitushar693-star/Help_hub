<?php
// ============================================================
// HELPHUB 2.0 PRO - Social Community Feed API
// ============================================================

require_once __DIR__ . '/../includes/functions.php';

$pdo = get_pdo();
$action = $_GET['action'] ?? 'posts';
$currentUserId = !empty($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
$body = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

// 1. LIST COMMUNITY POSTS
if ($action === 'posts') {
    $category = trim($_GET['category'] ?? '');
    $sql = "
        SELECT p.*, u.full_name AS author_name, u.username AS author_username,
               u.avatar AS author_avatar, u.city AS author_city, u.role AS author_role,
               (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS user_liked
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE 1=1
    ";
    $params = [$currentUserId];

    if ($category && $category !== 'All') {
        $sql .= " AND p.category = ?";
        $params[] = $category;
    }

    $sql .= " ORDER BY p.created_at DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    json_response(true, 'Posts retrieved', ['posts' => $posts]);
}

// 2. CREATE A COMMUNITY POST
if ($action === 'create_post') {
    require_login(true);
    $title = trim($body['title'] ?? '');
    $content = trim($body['content'] ?? '');
    $cat = trim($body['category'] ?? 'Success Story');

    if (!$title || !$content) {
        json_response(false, 'Post title and description content are required.', [], 422);
    }

    $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, category) VALUES (?, ?, ?, ?)");
    $stmt->execute([$currentUserId, $title, $content, $cat]);
    $newId = (int)$pdo->lastInsertId();

    award_reputation($pdo, $currentUserId, 5, 'Shared community post');

    json_response(true, 'Community story shared successfully!', ['id' => $newId]);
}

// 3. LIKE / UNLIKE POST
if ($action === 'like') {
    require_login(true);
    $postId = (int)($body['post_id'] ?? 0);
    if (!$postId) {
        json_response(false, 'Post ID required', [], 422);
    }

    $check = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $check->execute([$postId, $currentUserId]);
    $existing = $check->fetch();

    if ($existing) {
        $pdo->prepare("DELETE FROM likes WHERE id = ?")->execute([$existing['id']]);
        $pdo->prepare("UPDATE posts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?")->execute([$postId]);
        $liked = false;
    } else {
        $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)")->execute([$postId, $currentUserId]);
        $pdo->prepare("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?")->execute([$postId]);
        $liked = true;
    }

    $newCount = (int)$pdo->prepare("SELECT likes_count FROM posts WHERE id = ?")->execute([$postId]);
    $stmt = $pdo->prepare("SELECT likes_count FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $count = (int)$stmt->fetchColumn();

    json_response(true, $liked ? 'Post liked' : 'Post unliked', ['liked' => $liked, 'likes_count' => $count]);
}

// 4. ADD COMMENT TO POST
if ($action === 'comment') {
    require_login(true);
    $postId = (int)($body['post_id'] ?? 0);
    $commentText = trim($body['content'] ?? '');

    if (!$postId || !$commentText) {
        json_response(false, 'Comment content is required.', [], 422);
    }

    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$postId, $currentUserId, $commentText]);

    $pdo->prepare("UPDATE posts SET comments_count = comments_count + 1 WHERE id = ?")->execute([$postId]);

    json_response(true, 'Comment added successfully', [
        'author' => $_SESSION['user']['full_name'],
        'content' => $commentText,
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

// 5. FETCH POST COMMENTS
if ($action === 'get_comments') {
    $postId = (int)($_GET['post_id'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name AS user_name, u.avatar AS user_avatar
        FROM comments c
        JOIN users u ON u.id = c.user_id
        WHERE c.post_id = ?
        ORDER BY c.id ASC
    ");
    $stmt->execute([$postId]);
    json_response(true, 'Comments loaded', ['comments' => $stmt->fetchAll()]);
}

json_response(false, 'Unknown community action', [], 400);
