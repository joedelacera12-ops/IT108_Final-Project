<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only sellers can get their product count
require_role('seller');
$user = current_user();

header('Content-Type: application/json');

try {
    $pdo = get_db();
    
    // Get product count for this seller
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE seller_id = ?');
    $stmt->execute([(int)$user['id']]);
    $count = (int)$stmt->fetchColumn();
    
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
} catch (Exception $e) {
    error_log("Error getting product count: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
    exit;
}
?>