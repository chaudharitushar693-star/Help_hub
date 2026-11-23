<?php
// ============================================================
// HELPHUB 2.0 PRO - Authentication API Endpoint
// ============================================================

require_once __DIR__ . '/../includes/functions.php';

$pdo = get_pdo();
$action = $_GET['action'] ?? '';
$body = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

if ($action === 'register') {
    $fullName = trim($body['full_name'] ?? '');
    $username = strtolower(trim($body['username'] ?? ''));
    $email = strtolower(trim($body['email'] ?? ''));
    $phone = trim($body['phone'] ?? '');
    $password = $body['password'] ?? '';
    $city = trim($body['city'] ?? 'Bhopal');
    $skills = trim($body['skills'] ?? '');
    $bio = trim($body['bio'] ?? '');

    if (!$fullName || !$username || !$email || strlen($password) < 6) {
        json_response(false, 'Please provide your full name, username, valid email, and a password with at least 6 characters.', [], 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(false, 'Please enter a valid email address.', [], 422);
    }

    if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
        json_response(false, 'Username must be 3-30 characters long and contain only letters, numbers, and underscores.', [], 422);
    }

    // Check duplicate email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_response(false, 'This email address is already registered.', [], 409);
    }

    // Check duplicate username
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        json_response(false, 'This username is already taken. Please choose another.', [], 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = (str_contains($email, 'admin') || $username === 'admin') ? 'admin' : 'user';

    $insert = $pdo->prepare("INSERT INTO users (full_name, username, email, phone, password, city, skills, bio, role, reputation_points, rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 25, 5.00)");
    $insert->execute([$fullName, $username, $email, $phone, $hash, $city, $skills, $bio, $role]);
    $newId = (int)$pdo->lastInsertId();

    // Award starter badge
    $pdo->prepare("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?, 1)")->execute([$newId]);

    // Send welcome notification
    add_notification($pdo, $newId, 1, 'system', 'Welcome to HelpHub!', 'Thank you for joining our community. Connect, help, and make a difference!', 'dashboard/index.php');

    // Automatically log user in
    $fetchUser = $pdo->prepare("SELECT id, full_name, username, email, phone, city, state, avatar, skills, bio, role, reputation_points, rating, total_reviews, helps_given, helps_received FROM users WHERE id = ?");
    $fetchUser->execute([$newId]);
    $user = $fetchUser->fetch();
    $_SESSION['user'] = $user;

    json_response(true, 'Registration successful! Welcome to HelpHub.', ['user' => $user]);
}

if ($action === 'login') {
    $identifier = trim($body['email'] ?? $body['username'] ?? $body['identifier'] ?? '');
    $password = $body['password'] ?? '';

    if (!$identifier || !$password) {
        json_response(false, 'Please provide your email/username and password.', [], 422);
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([strtolower($identifier), strtolower($identifier)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        json_response(false, 'Invalid credentials. Please verify your email/username and password.', [], 401);
    }

    if ($user['status'] !== 'active') {
        json_response(false, 'Your account has been restricted. Please contact platform support.', [], 403);
    }

    unset($user['password']);
    $_SESSION['user'] = $user;

    json_response(true, 'Welcome back, ' . $user['full_name'] . '!', ['user' => $user]);
}

if ($action === 'me') {
    $user = current_user($pdo);
    json_response(true, 'Current session', ['user' => $user]);
}

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    json_response(true, 'Logged out successfully.');
}

if ($action === 'update_profile') {
    require_login(true);
    $userId = $_SESSION['user']['id'];

    $fullName = trim($body['full_name'] ?? '');
    $phone = trim($body['phone'] ?? '');
    $city = trim($body['city'] ?? '');
    $skills = trim($body['skills'] ?? '');
    $bio = trim($body['bio'] ?? '');

    if (!$fullName || !$city) {
        json_response(false, 'Full name and city are required.', [], 422);
    }

    $update = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, city = ?, skills = ?, bio = ? WHERE id = ?");
    $update->execute([$fullName, $phone, $city, $skills, $bio, $userId]);

    $fresh = current_user($pdo);
    json_response(true, 'Profile updated successfully!', ['user' => $fresh]);
}

json_response(false, 'Unknown authentication action.', [], 404);
