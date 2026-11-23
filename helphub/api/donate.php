<?php
require __DIR__ . '/config.php';
session_start();

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$amount = floatval($body['amount'] ?? 0);

if ($amount < 1) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please provide a valid donation amount.']);
    exit;
}

$ref = 'DEMO-' . strtoupper(bin2hex(random_bytes(4)));
$userId = $_SESSION['user']['id'] ?? null;

$stmt = $pdo->prepare("INSERT INTO donations (user_id, amount, payment_reference, status) VALUES (?, ?, ?, 'paid')");
$stmt->execute([$userId, $amount, $ref]);

echo json_encode([
    'success' => true,
    'reference' => $ref,
    'amount' => $amount,
    'message' => 'Donation recorded successfully. Thank you for supporting the community!'
]);
