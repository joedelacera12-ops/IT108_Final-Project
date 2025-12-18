<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only buyers may rate
require_role('buyer');
$user = current_user();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$order_id = intval($_POST['order_id'] ?? 0);
$seller_id = intval($_POST['seller_id'] ?? 0);
$rating = intval($_POST['rating'] ?? 0);
$review = trim($_POST['review'] ?? '');

if ($order_id <= 0 || $seller_id <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Verify order belongs to buyer and is delivered
try {
    $stmt = $pdo->prepare('SELECT id, status, buyer_id, seller_id FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
} catch (Exception $e) {
    $order = false;
}

if (!$order || (int)$order['buyer_id'] !== (int)$user['id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Order not found or access denied']);
    exit;
}

if ($order['status'] !== 'delivered') {
    http_response_code(400);
    echo json_encode(['error' => 'Order not delivered yet']);
    exit;
}

// Prevent duplicate rating for same order/seller
try {
    $check = $pdo->prepare('SELECT COUNT(*) FROM seller_ratings WHERE order_id = ? LIMIT 1');
    $check->execute([$order_id]);
    if ((int)$check->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Order already rated']);
        exit;
    }

    $ins = $pdo->prepare('INSERT INTO seller_ratings (seller_id, buyer_id, order_id, rating, review) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$seller_id, $user['id'], $order_id, $rating, $review]);
    
    // Send notification to seller
    require_once __DIR__ . '/../includes/notification_system.php';
    $notif = new NotificationSystem();
    $notif->createProductReviewNotification($seller_id, $order_id, 'Product', $rating);
    
    // Update seller's average rating
    try {
        // Calculate new average rating for seller
        $avgStmt = $pdo->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM seller_ratings WHERE seller_id = ?');
        $avgStmt->execute([$seller_id]);
        $ratingData = $avgStmt->fetch();
        
        if ($ratingData) {
            // Update seller's rating in users table (if we add this column later)
            // For now, we'll just log it
            error_log("Seller $seller_id new average rating: " . $ratingData['avg_rating'] . " from " . $ratingData['total_ratings'] . " ratings");
        }
    } catch (Exception $e) {
        // Ignore errors in rating calculation
        error_log("Error calculating seller rating: " . $e->getMessage());
    }
    
    echo json_encode(['success' => true, 'message' => 'Thank you for your rating!']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}
?>