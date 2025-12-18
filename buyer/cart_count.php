<?php
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Allow guests to check cart count

try {
    // Get cart count from session
    $cart_count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $cart_count = array_sum($_SESSION['cart']);
    }
    
    echo json_encode(['success' => true, 'count' => $cart_count]);
} catch (Exception $e) {
    error_log('Error getting cart count: ' . $e->getMessage());
    echo json_encode(['success' => false, 'count' => 0, 'error' => 'Error retrieving cart count']);
}