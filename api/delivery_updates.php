<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notification_system.php';

// Only delivery partners may access
require_role('delivery_partner');
$user = current_user();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = intval($data['order_id'] ?? 0);
$status = $data['status'] ?? '';

try {
    if (!$order_id || !$status) {
        throw new Exception('Missing required parameters');
    }

    // Validate status
    $valid_statuses = ['shipped', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status');
    }

    // Check if order belongs to this delivery partner
    $stmt = $pdo->prepare("
        SELECT od.id, o.status as current_order_status, od.status as current_delivery_status, o.order_number, o.seller_id, o.user_id as buyer_id
        FROM orders o
        JOIN order_deliveries od ON o.id = od.order_id
        WHERE o.id = ? AND od.delivery_partner_id = (
            SELECT id FROM delivery_partners WHERE user_id = ? LIMIT 1
        )
    ");
    $stmt->execute([$order_id, $user['id']]);
    $delivery = $stmt->fetch();

    if (!$delivery) {
        throw new Exception('Order not found or not assigned to you');
    }

    // Update order and delivery status
    $pdo->beginTransaction();

    // Update the delivery-specific timestamp
    $timestamp_field = $status . '_at';
    if (in_array($status, ['picked_up', 'in_transit', 'out_for_delivery', 'delivered'])) {
        $stmt = $pdo->prepare("UPDATE order_deliveries SET status = ?, $timestamp_field = NOW(), updated_at = NOW() WHERE order_id = ?");
        $stmt->execute([$status, $order_id]);
    }

    // Update the main order status
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $order_id]);

    // Commit transaction
    $pdo->commit();

    // Create notifications
    $notif = new NotificationSystem();
    $notif->createDeliveryStatusNotification($delivery['buyer_id'], $order_id, $status);
    $notif->createSellerDeliveryStatusNotification($delivery['seller_id'], $order_id, $status);

    if ($status === 'delivered') {
        $notif->createNotification(
            $delivery['buyer_id'],
            'rating_request',
            'Rate Your Purchase',
            "Your order #" . $delivery['order_number'] . " has been delivered. Please rate your experience.",
            $order_id,
            'order'
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Delivery status updated successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>