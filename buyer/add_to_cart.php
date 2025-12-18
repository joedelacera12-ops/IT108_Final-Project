<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Allow guests to add items to cart
// Authentication will be required at checkout
$user = null;
if (isset($_SESSION['user'])) {
    $user = current_user();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    exit;
}

try {
    $pdo = get_db();
    
    // Check if product exists and is active (published)
    $stmt = $pdo->prepare('SELECT id, name, stock, price FROM products WHERE id = ? AND status = ?');
    $stmt->execute([$product_id, 'published']);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    if ($product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Insufficient stock available']);
        exit;
    }
    
    // Initialize cart in session if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add or update product in cart
    $_SESSION['cart'][$product_id] = $quantity;
    
    // Check if user is logged in
    $response = ['success' => true, 'message' => 'Product added to cart successfully'];
    if (!isset($_SESSION['user'])) {
        $response['login_required'] = true;
        $response['message'] = 'Product added to cart. Please log in to continue shopping.';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Error adding product to cart: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error adding product to cart. Please try again later.']);
}