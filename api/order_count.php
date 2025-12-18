<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_role('seller');
$user = current_user();
$pdo = get_db();

try {
    // Count orders for this seller
    $stmt = $pdo->prepare('SELECT COUNT(DISTINCT o.id) FROM orders o JOIN order_items oi ON oi.order_id = o.id JOIN products p ON oi.product_id = p.id WHERE p.seller_id = ?');
    $stmt->execute([(int)$user['id']]);
    $orderCount = (int)$stmt->fetchColumn();
    
    echo json_encode(['success' => true, 'count' => $orderCount]);
} catch (Exception $e) {
    error_log("Order count API failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to fetch order count']);
}