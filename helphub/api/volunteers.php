<?php
require __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id, name, area, skills, status, created_at FROM volunteers ORDER BY created_at DESC");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty(trim($body['name'] ?? '')) || empty(trim($body['email'] ?? ''))) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO volunteers (name, email, area, skills, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([
        trim($body['name']),
        trim($body['email']),
        trim($body['area'] ?? ''),
        trim($body['skills'] ?? '')
    ]);

    echo json_encode(['success' => true, 'message' => 'Volunteer application submitted successfully! Our coordinator will contact you shortly.']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
