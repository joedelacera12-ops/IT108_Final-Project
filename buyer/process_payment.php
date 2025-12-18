<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/error_handler.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_role('buyer');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$orderId = $_POST['order_id'] ?? null;
$paymentMethod = $_POST['payment_method'] ?? null;
$amount = $_POST['amount'] ?? null;

// Validate inputs
if (!$orderId || !$paymentMethod || !$amount) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $pdo = get_db();
    
    // Get order details
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
    $stmt->execute([$orderId, $user['id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }
    
    // Check if order is already paid
    if ($order['payment_status'] === 'paid') {
        echo json_encode(['success' => true, 'message' => 'Order already paid']);
        exit;
    }
    
    // Log payment attempt for debugging
    error_log('Processing payment for order ' . $orderId . ' by user ' . $user['id'] . ' using ' . $paymentMethod);
    
    // Simulate payment processing (in a real implementation, this would integrate with a payment gateway)
    // For demonstration purposes, we'll assume payment is always successful
    
    // Generate a transaction ID
    $transactionId = 'TXN-' . strtoupper(uniqid());
    
    // Update order payment status
    $updateStmt = $pdo->prepare('UPDATE orders SET payment_method = ?, payment_status = ?, status = ? WHERE id = ?');
    $updateResult = $updateStmt->execute([$paymentMethod, 'paid', 'processing', $orderId]);
    
    if (!$updateResult) {
        throw new Exception('Failed to update order status');
    }
    
    // Record payment in payment_history table
    $paymentStmt = $pdo->prepare('
        INSERT INTO payment_history (user_id, order_id, subscription_id, amount, currency, payment_method, transaction_id, status, processed_at, created_at) 
        VALUES (?, ?, NULL, ?, "PHP", ?, ?, "completed", NOW(), NOW())
    ');
    $paymentResult = $paymentStmt->execute([
        $user['id'],
        $orderId,
        $amount,
        $paymentMethod,
        $transactionId
    ]);
    
    if (!$paymentResult) {
        throw new Exception('Failed to record payment');
    }
    
    // Update product sales count
    $itemsStmt = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll();

    foreach ($items as $item) {
        $productStmt = $pdo->prepare('UPDATE products SET sales_count = sales_count + ? WHERE id = ?');
        $productStmt->execute([$item['quantity'], $item['product_id']]);
    }

    // Check for automatic favorites - if buyer has purchased from a seller 5 or more times, add to favorites
    foreach ($items as $item) {
        // Get the seller ID for this product
        $productSellerStmt = $pdo->prepare('SELECT seller_id FROM products WHERE id = ?');
        $productSellerStmt->execute([$item['product_id']]);
        $product = $productSellerStmt->fetch();
        
        if ($product) {
            $sellerId = $product['seller_id'];
            
            // Count how many delivered orders this buyer has with this seller
            $countStmt = $pdo->prepare('
                SELECT COUNT(*) 
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN products p ON oi.product_id = p.id 
                WHERE o.user_id = ? AND p.seller_id = ? AND o.status = ?
            ');
            $countStmt->execute([$user['id'], $sellerId, 'delivered']);
            $purchaseCount = $countStmt->fetchColumn();
            
            // If this is the 5th purchase (or more), add seller to favorites (if not already favorited)
            if ($purchaseCount >= 5) {
                // Check if already favorited
                $favCheckStmt = $pdo->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ? AND seller_id = ?');
                $favCheckStmt->execute([$user['id'], $sellerId]);
                
                if ($favCheckStmt->fetchColumn() == 0) {
                    // Add to favorites
                    $favStmt = $pdo->prepare('INSERT INTO favorites (user_id, seller_id, created_at) VALUES (?, ?, NOW())');
                    $favStmt->execute([$user['id'], $sellerId]);
                }
            }
        }
    }

    // Log successful payment
    error_log('Payment successful for order ' . $orderId . ' with transaction ID ' . $transactionId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment successful',
        'transaction_id' => $transactionId
    ]);
    exit;
} catch (Exception $e) {
    error_log('Payment processing error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    http_response_code(500);
    echo json_encode(['error' => 'Server error processing payment: ' . $e->getMessage()]);
    exit;
}