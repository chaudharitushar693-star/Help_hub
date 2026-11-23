<?php
// ============================================================
// HELPHUB 2.0 PRO - Utility Functions & Business Logic Engine
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function json_response($success, $message = '', $data = [], $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => (bool)$success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function current_user($pdo = null) {
    if (!is_logged_in()) {
        return null;
    }
    if ($pdo && !empty($_SESSION['user']['id'])) {
        $stmt = $pdo->prepare("SELECT id, full_name, username, email, phone, city, state, latitude, longitude, avatar, skills, bio, role, reputation_points, rating, total_reviews, helps_given, helps_received, status FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $fresh = $stmt->fetch();
        if ($fresh) {
            $_SESSION['user'] = $fresh;
            return $fresh;
        }
    }
    return $_SESSION['user'];
}

function require_login($isApi = false) {
    if (!is_logged_in()) {
        if ($isApi || (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
            json_response(false, 'Authentication required. Please sign in.', [], 401);
        }
        header('Location: ' . BASE_URL . 'auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
        exit;
    }
    if (!empty($_SESSION['user']['status']) && $_SESSION['user']['status'] !== 'active') {
        session_destroy();
        if ($isApi) {
            json_response(false, 'Your account has been suspended or restricted.', [], 403);
        }
        header('Location: ' . BASE_URL . 'auth/login.php?error=suspended');
        exit;
    }
}

function require_admin($isApi = false) {
    require_login($isApi);
    if (empty($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
        if ($isApi) {
            json_response(false, 'Administrator privileges required.', [], 403);
        }
        header('Location: ' . BASE_URL . 'index.php?error=forbidden');
        exit;
    }
}

// Geographical distance calculation using Haversine formula (km)
function haversine_distance($lat1, $lon1, $lat2, $lon2) {
    if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
        return null;
    }
    $earthRadius = 6371; // km
    $dLat = deg2rad((float)$lat2 - (float)$lat1);
    $dLon = deg2rad((float)$lon2 - (float)$lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad((float)$lat1)) * cos(deg2rad((float)$lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c, 1);
}

// Smart Help Matching Algorithm
// Computes dynamic suitability score (0-99%) based on category, skills, distance, rating, reputation
function calculate_match_score($request, $helper) {
    $score = 40; // baseline

    // 1. Category Alignment (30 pts)
    $reqCat = (int)($request['category_id'] ?? 0);
    $helperCat = (int)($helper['category_id'] ?? 0);
    if ($reqCat > 0 && $helperCat > 0 && $reqCat === $helperCat) {
        $score += 30;
    }

    // 2. Skill Keywords Overlap (20 pts)
    $reqSkills = strtolower($request['skills_required'] ?? ($request['title'] ?? '') . ' ' . ($request['description'] ?? ''));
    $helperSkills = strtolower($helper['skills'] ?? '');
    if ($reqSkills && $helperSkills) {
        $helperTokens = array_filter(array_map('trim', preg_split('/[,;\s]+/', $helperSkills)));
        $matched = 0;
        foreach ($helperTokens as $token) {
            if (strlen($token) >= 3 && str_contains($reqSkills, $token)) {
                $matched++;
            }
        }
        $score += min(20, $matched * 8);
    }

    // 3. Location Proximity (15 pts)
    $dist = null;
    if (!empty($request['latitude']) && !empty($request['longitude']) &&
        !empty($helper['latitude']) && !empty($helper['longitude'])) {
        $dist = haversine_distance($request['latitude'], $request['longitude'], $helper['latitude'], $helper['longitude']);
        if ($dist <= 5) {
            $score += 15;
        } elseif ($dist <= 15) {
            $score += 10;
        } elseif ($dist <= 30) {
            $score += 5;
        }
    } elseif (!empty($request['location']) && !empty($helper['location'])) {
        if (str_contains(strtolower($request['location']), strtolower($helper['city'] ?? '')) ||
            str_contains(strtolower($helper['location']), strtolower($request['location']))) {
            $score += 10;
        }
    }

    // 4. Rating & Reputation Bonus (10 pts)
    $rating = (float)($helper['rating'] ?? 5.0);
    $score += (int)round(($rating / 5.0) * 6);
    $helpsGiven = (int)($helper['helps_given'] ?? 0);
    if ($helpsGiven >= 5) {
        $score += 4;
    }

    $finalScore = min(98, max(45, (int)$score));
    return [
        'score' => $finalScore,
        'label' => "{$finalScore}% Match",
        'distance_km' => $dist
    ];
}

// Real Notification Dispatcher
function add_notification($pdo, $userId, $senderId, $type, $title, $message, $link = '') {
    if (!$pdo) return false;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, title, message, link) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$userId, $senderId, $type, $title, $message, $link]);
}

// Reputation Point System
function award_reputation($pdo, $userId, $points, $reason = '') {
    if (!$pdo) return;
    $stmt = $pdo->prepare("UPDATE users SET reputation_points = GREATEST(0, reputation_points + ?) WHERE id = ?");
    $stmt->execute([$points, $userId]);
    check_and_award_badges($pdo, $userId);
}

// Badge Qualification Checker
function check_and_award_badges($pdo, $userId) {
    if (!$pdo) return;
    $stmt = $pdo->prepare("SELECT reputation_points FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $pts = (int)$stmt->fetchColumn();

    $badgesStmt = $pdo->prepare("SELECT id FROM badges WHERE min_points <= ?");
    $badgesStmt->execute([$pts]);
    $eligibleBadges = $badgesStmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($eligibleBadges as $badgeId) {
        $insert = $pdo->prepare("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?, ?)");
        $insert->execute([$userId, $badgeId]);
    }
}

// Time Ago Formatter
function time_ago($datetime) {
    $time = strtotime($datetime);
    if (!$time) return 'Just now';
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $time);
}
