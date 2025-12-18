<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only sellers can process payments
require_role('seller');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$subscriptionId = $_POST['subscription_id'] ?? null;
$paymentMethod = $_POST['payment_method'] ?? null;
$amount = $_POST['amount'] ?? null;

// Validate inputs
if (!$subscriptionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing subscription ID']);
    exit;
}

if (!$paymentMethod) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing payment method']);
    exit;
}

if (!$amount) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing amount']);
    exit;
}

try {
    $pdo = get_db();
    
    // Check if user has an active subscription
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM subscriptions WHERE user_id = ? AND status = "active" AND end_date >= NOW()');
    $stmt->execute([$user['id']]);
    $subscriptionCount = (int)$stmt->fetchColumn();
    
    if ($subscriptionCount > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'You can only make one subscription per month. Please wait until next month to subscribe again.']);
        exit;
    }
    
    // Get subscription details
    $stmt = $pdo->prepare('SELECT * FROM subscriptions WHERE id = ? AND user_id = ?');
    $stmt->execute([$subscriptionId, $user['id']]);
    $subscription = $stmt->fetch();
    
    if (!$subscription) {
        http_response_code(404);
        echo json_encode(['error' => 'Subscription not found']);
        exit;
    }
    
    // Simulate payment processing (in a real implementation, this would integrate with a payment gateway)
    // For demonstration purposes, we'll assume payment is always successful
    
    // Generate a transaction ID
    $transactionId = 'TXN-' . strtoupper(uniqid());
    
    // Update subscription status to active
    $updateStmt = $pdo->prepare('UPDATE subscriptions SET status = ?, updated_at = NOW() WHERE id = ?');
    $updateStmt->execute(['active', $subscriptionId]);
    
    // Record payment in payment_history table
    $paymentStmt = $pdo->prepare('
        INSERT INTO payment_history (user_id, subscription_id, amount, currency, payment_method, transaction_id, status, processed_at, created_at) 
        VALUES (?, ?, ?, "PHP", ?, ?, "completed", NOW(), NOW())
    ');
    $paymentStmt->execute([
        $user['id'],
        $subscriptionId,
        $amount,
        $paymentMethod,
        $transactionId
    ]);
    
    // Update user's plan in users table (optional, for quick access)
    $userUpdateStmt = $pdo->prepare('UPDATE users SET meta = JSON_SET(COALESCE(meta, "{}"), "$.subscription_plan", ?) WHERE id = ?');
    $userUpdateStmt->execute([$subscription['plan_type'], $user['id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment successful',
        'transaction_id' => $transactionId
    ]);
    exit;
} catch (Exception $e) {
    error_log('Payment processing error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error processing payment']);
    exit;
}