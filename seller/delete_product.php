<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only sellers may access
require_role('seller');
$user = current_user();
$pdo = get_db();

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get product ID from request
$data = json_decode(file_get_contents('php://input'), true);
$productId = (int)($data['product_id'] ?? 0);

if (empty($productId)) {
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
    exit;
}

try {
    // Check if the product belongs to this seller
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE id = ? AND seller_id = ?');
    $stmt->execute([$productId, $user['id']]);
    $productCount = (int)$stmt->fetchColumn();
    
    if ($productCount === 0) {
        echo json_encode(['success' => false, 'error' => 'Product not found or unauthorized']);
        exit;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Delete product images first
    $stmt = $pdo->prepare('DELETE FROM product_images WHERE product_id = ?');
    $stmt->execute([$productId]);
    
    // Delete product from favorites
    $stmt = $pdo->prepare('DELETE FROM favorites WHERE product_id = ?');
    $stmt->execute([$productId]);
    
    // Delete product from cart items
    $stmt = $pdo->prepare('DELETE FROM cart_items WHERE product_id = ?');
    $stmt->execute([$productId]);
    
    // Delete product from order items
    $stmt = $pdo->prepare('DELETE FROM order_items WHERE product_id = ?');
    $stmt->execute([$productId]);
    
    // Finally delete the product itself
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollback();
    error_log('Product deletion error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to delete product']);
    exit;
}
?>