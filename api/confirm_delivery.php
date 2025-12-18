<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Ensure the user is authenticated
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

$user = get_user();
$pdo = get_db();

// Get the input data
$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'] ?? null;

if (!$orderId) {
    echo json_encode(['success' => false, 'error' => 'Invalid input.']);
    exit;
}

// Verify that the order belongs to this buyer
$stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $user['id']]);
if ($stmt->fetchColumn() === false) {
    echo json_encode(['success' => false, 'error' => 'Order not found.']);
    exit;
}

// Update the delivery confirmation status
try {
    $stmt = $pdo->prepare("
        UPDATE order_deliveries 
        SET buyer_confirmed_at = NOW()
        WHERE order_id = ?
    ");
    $stmt->execute([$orderId]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Error confirming delivery: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}