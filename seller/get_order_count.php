<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only sellers can get their order count
require_role('seller');
$user = current_user();

header('Content-Type: application/json');

try {
    $pdo = get_db();
    
    // Count orders for this seller
    $stmt = $pdo->prepare('SELECT COUNT(DISTINCT o.id) FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE oi.seller_id = ?');
    $stmt->execute([(int)$user['id']]);
    $count = (int)$stmt->fetchColumn();
    
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
} catch (Exception $e) {
    error_log("Order count query failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
    exit;
}
?>