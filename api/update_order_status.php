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
$status = $input['status'] ?? null;
$delivery_partner_id = $input['delivery_partner_id'] ?? null;

// Validate input
if (!$order_id || !$status) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

// Validate status - include new delivery statuses
$valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'assigned', 'picked_up', 'in_transit', 'out_for_delivery', 'approved', 'ready_to_ship'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    // Check if the order belongs to this seller by checking order_items
    $stmt = $pdo->prepare('SELECT o.* FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE o.id = ? AND oi.seller_id = ? LIMIT 1');
    $stmt->execute([$order_id, $user['id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found or unauthorized']);
        exit;
    }
    
    $update_fields = ['status = ?', 'updated_at = NOW()'];
    $update_values = [$status];

    if ($delivery_partner_id) {
        // Check if a delivery record already exists
        $stmt = $pdo->prepare('SELECT id FROM order_deliveries WHERE order_id = ?');
        $stmt->execute([$order_id]);
        $delivery_id = $stmt->fetchColumn();

        if ($delivery_id) {
            // Update existing delivery record
            $stmt = $pdo->prepare('UPDATE order_deliveries SET delivery_partner_id = ?, status = "assigned", updated_at = NOW() WHERE id = ?');
            $stmt->execute([$delivery_partner_id, $delivery_id]);
        } else {
            // Create a new delivery record
            $stmt = $pdo->prepare('INSERT INTO order_deliveries (order_id, delivery_partner_id, status) VALUES (?, ?, "assigned")');
            $stmt->execute([$order_id, $delivery_partner_id]);
            $delivery_id = $pdo->lastInsertId();
        }

        // Update the order with the delivery_id
        $update_fields[] = 'delivery_id = ?';
        $update_values[] = $delivery_id;
    }

    $update_values[] = $order_id;

    $sql = 'UPDATE orders SET ' . implode(', ', $update_fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($update_values);
    
    // If we're marking as shipped, create a notification for the buyer
    if ($status === 'shipped') {
        // Include notification system
        require_once __DIR__ . '/../includes/notification_system.php';
        $notif = new NotificationSystem();
        $notif->createDeliveryStatusNotification($order['user_id'], $order_id, 'shipped');
    }

    if ($status === 'ready_to_ship') {
        $stmt = $pdo->prepare('UPDATE order_deliveries SET status = "ready_to_ship", updated_at = NOW() WHERE order_id = ?');
        $stmt->execute([$order_id]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
} catch (Exception $e) {
    error_log("Order status update failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to update order status']);
}
?>