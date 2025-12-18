<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only sellers can subscribe to plans
require_role('seller');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$planType = trim($_POST['plan_type'] ?? '');

// Validate plan type
$validPlans = ['basic', 'premium', 'pro'];
if (!in_array($planType, $validPlans)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid plan type']);
    exit;
}

try {
    $pdo = get_db();
    
    // Check if user has an active subscription
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM subscriptions WHERE user_id = ? AND status = "active" AND end_date >= NOW()');
    $stmt->execute([$user['id']]);
    $subscriptionCount = (int)$stmt->fetchColumn();
    
    // Check if user has already made a subscription this month
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM subscriptions WHERE user_id = ? AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())');
    $stmt->execute([$user['id']]);
    $monthlySubscriptionCount = (int)$stmt->fetchColumn();
    
    // For new accounts (no previous subscriptions), allow subscription
    if ($monthlySubscriptionCount == 0) {
        // This is a new account or first subscription this month, allow it
    } else if ($subscriptionCount > 0) {
        // User has an active subscription
        http_response_code(400);
        echo json_encode(['error' => 'You can only make one subscription per month. Please wait until next month to subscribe again.']);
        exit;
    }
    
    // Check if user already has an active subscription
    $stmt = $pdo->prepare('SELECT * FROM subscriptions WHERE user_id = ? AND status = "active" ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$user['id']]);
    $existingSubscription = $stmt->fetch();
    
    // If upgrading from free to paid plan, deactivate current subscription
    if ($existingSubscription && $existingSubscription['plan_type'] === 'basic' && $planType !== 'basic') {
        $updateStmt = $pdo->prepare('UPDATE subscriptions SET status = "expired" WHERE id = ?');
        $updateStmt->execute([$existingSubscription['id']]);
    }
    
    // For basic plan, just create/update subscription
    if ($planType === 'basic') {
        // Prevent downgrading from paid plans to free plan
        if ($existingSubscription && $existingSubscription['plan_type'] !== 'basic' && $existingSubscription['status'] === 'active') {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot downgrade from paid plan to free plan while active subscription exists']);
            exit;
        }
        
        // Create or update subscription
        // Free accounts get 3 months of access
        $endDate = date('Y-m-d H:i:s', strtotime('+3 months'));
        
        $insertStmt = $pdo->prepare('
            INSERT INTO subscriptions (user_id, plan_type, status, start_date, end_date, created_at) 
            VALUES (?, ?, ?, NOW(), ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            plan_type = VALUES(plan_type), 
            status = VALUES(status), 
            start_date = VALUES(start_date), 
            end_date = VALUES(end_date),
            updated_at = NOW()
        ');
        $insertStmt->execute([
            $user['id'], 
            $planType, 
            'active', 
            $endDate
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Successfully subscribed to Basic plan']);
        exit;
    } else {
        // For paid plans, we need to process payment
        // In a real implementation, this would integrate with a payment gateway
        // For now, we'll simulate the process
        
        // Create a pending subscription
        $endDate = date('Y-m-d H:i:s', strtotime('+1 month'));
        
        $insertStmt = $pdo->prepare('
            INSERT INTO subscriptions (user_id, plan_type, status, start_date, end_date, created_at) 
            VALUES (?, ?, ?, NOW(), ?, NOW())
        ');
        $insertStmt->execute([
            $user['id'], 
            $planType, 
            'pending', 
            $endDate
        ]);
        
        $subscriptionId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Redirecting to payment...', 
            'redirect' => '/ecommerce_farmers_fishers/seller/payment.php?subscription=' . $subscriptionId
        ]);
        exit;
    }
} catch (Exception $e) {
    error_log('Subscription error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error processing subscription']);
    exit;
}