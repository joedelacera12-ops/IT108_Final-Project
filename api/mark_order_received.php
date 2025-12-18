<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_role('buyer');
$user = current_user();
$pdo = get_db();

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['order_id'] ?? null;

// Validate input
if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

try {
    // Check if the order belongs to this buyer and is in shipped status
    $stmt = $pdo->prepare('SELECT o.*, p.seller_id FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE o.id = ? AND o.user_id = ? AND o.status = ? LIMIT 1');
    $stmt->execute([$order_id, $user['id'], 'shipped']);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found or not eligible to be marked as received']);
        exit;
    }
    
    // Update order status to delivered
    $updateStmt = $pdo->prepare('UPDATE orders SET status = ?, actual_delivery = NOW(), updated_at = NOW() WHERE id = ?');
    $updateStmt->execute(['delivered', $order_id]);
    
    // Send notification to seller
    require_once __DIR__ . '/../includes/notification_system.php';
    $notif = new NotificationSystem();
    $notif->createDeliveryStatusNotification($order['seller_id'], $order_id, 'received');
    
    // Check if we should add seller to favorites (5 or more purchases from same seller)
    $sellerId = $order['seller_id'];
    
    // Count how many orders this buyer has with this seller
    $countStmt = $pdo->prepare('
        SELECT COUNT(*) 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        JOIN products p ON oi.product_id = p.id 
        WHERE o.user_id = ? AND p.seller_id = ? AND o.status = ?
    ');
    $countStmt->execute([$user['id'], $sellerId, 'delivered']);
    $purchaseCount = $countStmt->fetchColumn();
    
    // If 5 or more purchases, add seller to favorites (if not already favorited)
    $favorited = false;
    if ($purchaseCount >= 5) {
        // Check if already favorited
        $favCheckStmt = $pdo->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ? AND seller_id = ?');
        $favCheckStmt->execute([$user['id'], $sellerId]);
        
        if ($favCheckStmt->fetchColumn() == 0) {
            // Add to favorites
            $favStmt = $pdo->prepare('INSERT INTO favorites (user_id, seller_id, created_at) VALUES (?, ?, NOW())');
            $favStmt->execute([$user['id'], $sellerId]);
            $favorited = true;
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Order marked as received successfully', 'favorited' => $favorited]);
} catch (Exception $e) {
    error_log("Mark order received failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to mark order as received']);
}
?>