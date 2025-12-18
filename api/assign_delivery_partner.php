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

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['order_id'] ?? null;
$partner_id = $input['partner_id'] ?? null;

// Validate input
if (!$order_id || !$partner_id) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

try {
    // Check if the order belongs to this seller and is in approved status
    $stmt = $pdo->prepare('
        SELECT o.id, o.status
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id = ? AND oi.seller_id = ? AND o.status = ?
        LIMIT 1
    ');
    $stmt->execute([$order_id, $user['id'], 'approved']);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found, unauthorized, or not in approved status']);
        exit;
    }
    
    // Check if delivery partner exists and is active
    $stmt = $pdo->prepare('SELECT id, name FROM delivery_partners WHERE id = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$partner_id]);
    $partner = $stmt->fetch();
    
    if (!$partner) {
        echo json_encode(['success' => false, 'error' => 'Invalid delivery partner']);
        exit;
    }
    
    // Create or update delivery record
    $pdo->beginTransaction();
    
    // Check if order already has a delivery record
    $stmt = $pdo->prepare('SELECT delivery_id FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$order_id]);
    $delivery_id = $stmt->fetchColumn();
    
    if ($delivery_id) {
        // Update existing delivery record
        $stmt = $pdo->prepare('
            UPDATE order_deliveries 
            SET delivery_partner_id = ?, status = ?, assigned_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ');
        $stmt->execute([$partner_id, 'assigned', $delivery_id]);
    } else {
        // Create new delivery record
        $stmt = $pdo->prepare('
            INSERT INTO order_deliveries (order_id, delivery_partner_id, status, assigned_at) 
            VALUES (?, ?, ?, NOW())
        ');
        $stmt->execute([$order_id, $partner_id, 'assigned']);
        $delivery_id = $pdo->lastInsertId();
        
        // Update order with delivery_id
        $stmt = $pdo->prepare('UPDATE orders SET delivery_id = ? WHERE id = ?');
        $stmt->execute([$delivery_id, $order_id]);
    }
    
    // Update order status to ready_to_ship
    $stmt = $pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute(['ready_to_ship', $order_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Delivery partner assigned successfully. Order status updated to Ready to Ship.'
    ]);
} catch (Exception $e) {
    $pdo->rollback();
    error_log("Error assigning delivery partner: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to assign delivery partner']);
}
?>