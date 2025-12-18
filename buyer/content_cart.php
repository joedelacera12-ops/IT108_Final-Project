<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/redirect_handler.php';
require_once __DIR__ . '/../includes/error_handler.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    // This part is for standalone access. Since we are in an AJAX context,
    // the parent page should handle login status.
    // We can show an error message if accessed directly without a session.
    echo '<div class="alert alert-danger">You must be logged in to view your cart.</div>';
    return;
}

// Only buyers may access
require_role('buyer');
$user = current_user();
$pdo = get_db();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_quantity':
            if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
                $product_id = (int)$_POST['product_id'];
                $quantity = (int)$_POST['quantity'];
                
                if ($quantity > 0) {
                    if (!isset($_SESSION['cart'])) {
                        $_SESSION['cart'] = [];
                    }
                    
                    try {
                        $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = ? AND status = ?');
                        $stmt->execute([$product_id, 'published']);
                        $product = $stmt->fetch();
                        
                        if ($product && $product['stock'] >= $quantity) {
                            $_SESSION['cart'][$product_id] = $quantity;
                            $_SESSION['cart_message'] = 'Cart updated successfully.';
                        } else {
                            $_SESSION['cart_message'] = 'Insufficient stock for this product.';
                        }
                    } catch (Exception $e) {
                        $_SESSION['cart_message'] = 'Error updating cart.';
                    }
                } else {
                    if (isset($_SESSION['cart'][$product_id])) {
                        unset($_SESSION['cart'][$product_id]);
                        $_SESSION['cart_message'] = 'Item removed from cart.';
                    }
                }
            }
            break;
            
        case 'remove_item':
            if (isset($_POST['product_id'])) {
                $product_id = (int)$_POST['product_id'];
                if (isset($_SESSION['cart'][$product_id])) {
                    unset($_SESSION['cart'][$product_id]);
                    $_SESSION['cart_message'] = 'Item removed from cart.';
                }
            }
            break;
            
        case 'checkout':
            if (empty($_SESSION['cart'])) {
                $_SESSION['cart_message'] = 'Your cart is empty.';
                break;
            }

            try {
                $pdo->beginTransaction();
                $subtotal = 0;
                $items = [];

                foreach ($_SESSION['cart'] as $product_id => $quantity) {
                    $stmt = $pdo->prepare('SELECT p.*, u.id as seller_id FROM products p JOIN users u ON p.seller_id = u.id WHERE p.id = ? AND p.status = ?');
                    $stmt->execute([$product_id, 'published']);
                    $product = $stmt->fetch();

                    if ($product && $product['stock'] >= $quantity) {
                        $item_total = $product['price'] * $quantity;
                        $subtotal += $item_total;
                        $items[] = [
                            'product_id' => $product_id,
                            'product_name' => $product['name'],
                            'quantity' => $quantity,
                            'unit_price' => $product['price'],
                            'subtotal' => $item_total,
                            'seller_id' => $product['seller_id']
                        ];
                    }
                }

                if (!empty($items)) {
                    $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
                    $total = $subtotal; // Shipping fee is 0

                    $orderStmt = $pdo->prepare('INSERT INTO orders (user_id, order_number, subtotal, total, payment_status, status) VALUES (?, ?, ?, ?, ?, ?)');
                    $orderStmt->execute([$user['id'], $order_number, $subtotal, $total, 'pending', 'pending']);
                    $orderId = $pdo->lastInsertId();

                    foreach ($items as $item) {
                        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, seller_id, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)');
                        $itemStmt->execute([$orderId, $item['product_id'], $item['seller_id'], $item['quantity'], $item['unit_price'], $item['subtotal']]);
                        $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
                        $stockStmt->execute([$item['quantity'], $item['product_id']]);
                    }
                    
                    $pdo->commit();
                    unset($_SESSION['cart']);

                    // Instead of redirecting, we'll send a JSON response
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'redirectUrl' => '/ecommerce_farmers_fishers/buyer/payment.php?order=' . $orderId]);
                    exit;
                } else {
                    $_SESSION['cart_message'] = 'Some items in your cart are no longer available.';
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Order placement error: ' . $e->getMessage());
                $_SESSION['cart_message'] = 'Error placing order. Please try again.';
            }
            break;
    }
    
    // After a POST action, we need to reload the cart content.
    // A full page redirect is not ideal in an AJAX context.
    // The JS should handle reloading the content.
}

// Get cart items for display
$cart_items = [];
$total = 0;
if (!empty($_SESSION['cart'])) {
    $placeholders = rtrim(str_repeat('?,', count($_SESSION['cart'])), ',');
    $product_ids = array_keys($_SESSION['cart']);
    
    try {
        $stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name, pi.image_path FROM products p JOIN users u ON p.seller_id = u.id LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 WHERE p.id IN ($placeholders) AND p.status = ?");
        $params = array_merge($product_ids, ['published']);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $quantity = $_SESSION['cart'][$product['id']];
            if ($product['stock'] >= $quantity) {
                $item_total = $product['price'] * $quantity;
                $total += $item_total;
                $cart_items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'item_total' => $item_total
                ];
            } else {
                // Handle case where stock is insufficient
                $_SESSION['cart_message'] = "Insufficient stock for " . htmlspecialchars($product['name']) . ".";
                // Optionally remove or adjust quantity
                unset($_SESSION['cart'][$product['id']]);
            }
        }
    } catch (Exception $e) {
        error_log("Cart loading error: " . $e->getMessage());
        $cart_items = [];
        $_SESSION['cart_message'] = "Error loading cart items.";
    }
}

$cart_message = $_SESSION['cart_message'] ?? '';
unset($_SESSION['cart_message']);
?>
<div class="cart-header text-center">
    <div class="container">
        <h1 class="display-5 fw-bold mb-3">Your Shopping Cart</h1>
        <p class="lead mb-0">Review and manage your items before checkout</p>
    </div>
</div>

<div class="container-fluid p-4" id="cart-content-container">
    <?php if ($cart_message): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($cart_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
            <h3 class="mb-3">Your cart is empty</h3>
            <p class="text-muted mb-4">Looks like you haven't added any items yet.</p>
            <a href="#" onclick="loadContent('marketplace'); return false;" class="btn btn-success btn-lg rounded-pill px-4 py-2">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-table mb-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="/ecommerce_farmers_fishers/<?php echo htmlspecialchars($item['product']['image_path'] ?? 'assets/images/product-placeholder.png'); ?>" alt="<?php echo htmlspecialchars($item['product']['name']); ?>" class="product-img me-3">
                                        <div>
                                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['product']['name']); ?></h6>
                                            <small class="text-muted">Seller: <?php echo htmlspecialchars($item['product']['first_name'] . ' ' . $item['product']['last_name']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-bold">₱<?php echo number_format($item['product']['price'], 2); ?></td>
                                <td>
                                    <form method="POST" class="cart-action-form d-inline">
                                        <input type="hidden" name="action" value="update_quantity">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                        <div class="input-group quantity-input">
                                            <input type="number" name="quantity" class="form-control form-control-sm text-center" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['product']['stock']; ?>">
                                            <button class="btn btn-outline-secondary btn-sm" type="submit">Update</button>
                                        </div>
                                    </form>
                                </td>
                                <td class="fw-bold">₱<?php echo number_format($item['item_total'], 2); ?></td>
                                <td class="text-center">
                                    <form method="POST" class="cart-action-form d-inline">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mb-4 mb-md-0">
                <a href="#" onclick="loadContent('marketplace'); return false;" class="btn btn-secondary btn-action">
                    <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                </a>
            </div>
            <div class="col-md-4">
                <div class="cart-summary">
                    <h4 class="fw-bold mb-3">Order Summary</h4>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span>₱<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping Fee</span>
                        <span>₱0.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="cart-total">Total</span>
                        <span class="cart-total">₱<?php echo number_format($total, 2); ?></span>
                    </div>
                    <form method="POST" id="checkout-form">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn btn-success w-100 rounded-pill py-2 fw-bold">
                            Proceed to Checkout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    function handleCartFormSubmit(form) {
        const formData = new FormData(form);
        const action = formData.get('action');
        
        let submitButton = form.querySelector('button[type="submit"]');
        let originalButtonText = submitButton.innerHTML;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        submitButton.disabled = true;

        $.ajax({
            type: 'POST',
            url: '/ecommerce_farmers_fishers/buyer/content_cart.php',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json', // Expect a JSON response for checkout
            success: function(response) {
                if (action === 'checkout') {
                    if (response.success && response.redirectUrl) {
                        window.location.href = response.redirectUrl;
                    } else {
                        // Handle checkout error
                        alert(response.error || 'An unknown error occurred during checkout.');
                        submitButton.innerHTML = originalButtonText;
                        submitButton.disabled = false;
                    }
                } else {
                    // For other actions, just reload the cart content
                    loadContent('cart');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // If the response is not JSON, it means it's a regular HTML update
                if (action !== 'checkout') {
                    loadContent('cart'); // Reload content on success/error for update/remove
                } else {
                    alert('An error occurred: ' + textStatus);
                    submitButton.innerHTML = originalButtonText;
                    submitButton.disabled = false;
                }
            }
        });
    }

    // Attach event listeners to cart forms
    $('#cart-content-container').on('submit', '.cart-action-form', function(e) {
        e.preventDefault();
        if (this.querySelector('input[name="action"]').value === 'remove_item') {
            if (confirm('Are you sure you want to remove this item?')) {
                handleCartFormSubmit(this);
            }
        } else {
            handleCartFormSubmit(this);
        }
    });

    $('#cart-content-container').on('submit', '#checkout-form', function(e) {
        e.preventDefault();
        handleCartFormSubmit(this);
    });
});
</script>