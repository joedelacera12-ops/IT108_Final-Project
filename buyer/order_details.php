<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    redirect_to('/ecommerce_farmers_fishers/php/login.php');
}

require_role('buyer');
$user = current_user();
$pdo = get_db();

// Get order ID from URL
$order_id = $_GET['id'] ?? 0;

if (!$order_id) {
    redirect_to('/ecommerce_farmers_fishers/buyer/dashboard.php?tab=orders');
}

// Fetch order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*
        FROM orders o
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user['id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        redirect_to('/ecommerce_farmers_fishers/buyer/dashboard.php?tab=orders');
    }
} catch (Exception $e) {
    redirect_to('/ecommerce_farmers_fishers/buyer/dashboard.php?tab=orders');
}

// Fetch order items
try {
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, pi.image_path as product_image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
} catch (Exception $e) {
    $items = [];
}

// Fetch delivery information
$deliveryInfo = null;
if ($order['delivery_id']) {
    try {
        $stmt = $pdo->prepare("
            SELECT od.*, dp.name as delivery_partner_name
            FROM order_deliveries od
            LEFT JOIN delivery_partners dp ON od.delivery_partner_id = dp.id
            WHERE od.id = ?
        ");
        $stmt->execute([$order['delivery_id']]);
        $deliveryInfo = $stmt->fetch();
    } catch (Exception $e) {
        $deliveryInfo = null;
    }
}

// Check if already rated
$alreadyRated = false;
if ($order['status'] === 'delivered') {
    try {
        $stmt = $pdo->prepare("SELECT id FROM seller_ratings WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $alreadyRated = $stmt->fetch() ? true : false;
    } catch (Exception $e) {
        // Ignore error
    }
}

// Function to generate order tracking timeline
function generateOrderTimeline($order, $deliveryInfo) {
    $steps = [];
    
    // Order placed
    $steps[] = [
        'title' => 'Order Placed',
        'description' => 'Your order has been placed successfully',
        'status' => 'completed',
        'icon' => 'fa-check',
        'date' => $order['created_at']
    ];
    
    // Processing
    $steps[] = [
        'title' => 'Processing',
        'description' => 'Seller is preparing your order',
        'status' => $order['status'] === 'processing' || $order['status'] === 'shipped' || $order['status'] === 'delivered' ? 'completed' : 'pending',
        'icon' => $order['status'] === 'processing' || $order['status'] === 'shipped' || $order['status'] === 'delivered' ? 'fa-check' : 'fa-hourglass-half',
        'date' => $order['status'] === 'processing' || $order['status'] === 'shipped' || $order['status'] === 'delivered' ? $order['created_at'] : null
    ];
    
    // Shipped
    $steps[] = [
        'title' => 'Shipped',
        'description' => 'Order has been shipped from seller',
        'status' => $order['status'] === 'shipped' || $order['status'] === 'delivered' ? 'completed' : 'pending',
        'icon' => $order['status'] === 'shipped' || $order['status'] === 'delivered' ? 'fa-truck' : 'fa-box',
        'date' => $order['status'] === 'shipped' || $order['status'] === 'delivered' ? $order['shipped_at'] : null
    ];
    
    // Out for delivery (if delivery info exists)
    if ($deliveryInfo) {
        $steps[] = [
            'title' => 'Out for Delivery',
            'description' => 'Order is on its way to you',
            'status' => $deliveryInfo['status'] === 'out_for_delivery' || $deliveryInfo['status'] === 'delivered' ? 'completed' : 'pending',
            'icon' => $deliveryInfo['status'] === 'out_for_delivery' || $deliveryInfo['status'] === 'delivered' ? 'fa-shipping-fast' : 'fa-box',
            'date' => $deliveryInfo['out_for_delivery_at'] ?? null
        ];
    }
    
    // Delivered
    $steps[] = [
        'title' => 'Delivered',
        'description' => 'Order has been delivered to you',
        'status' => $order['status'] === 'delivered' ? 'completed' : 'pending',
        'icon' => $order['status'] === 'delivered' ? 'fa-home' : 'fa-box',
        'date' => $order['status'] === 'delivered' ? $order['delivered_at'] : null
    ];
    
    return $steps;
}

$timelineSteps = generateOrderTimeline($order, $deliveryInfo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - AgriSea Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/ecommerce_farmers_fishers/assets/css/enhanced_modern_with_tracking.css">
</head>
<body>
    <div class="main-content">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm" style="padding: 0.75rem 1rem;">
            <div class="container-fluid">
                <a class="navbar-brand" href="/ecommerce_farmers_fishers/buyer/dashboard.php"><strong class="text-success">AgriSea</strong> Marketplace</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a href="/ecommerce_farmers_fishers/buyer/dashboard.php?tab=marketplace" class="nav-link">Marketplace</a></li>
                        <li class="nav-item"><a href="/ecommerce_farmers_fishers/buyer/dashboard.php" class="nav-link">Dashboard</a></li>
                        <li class="nav-item position-relative">
                            <a href="/ecommerce_farmers_fishers/buyer/cart.php" class="nav-link">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="cart-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">0</span>
                            </a>
                        </li>
                        <li class="nav-item"><a href="/ecommerce_farmers_fishers/logout.php" class="nav-link">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <div class="row">
                <div class="col-12">
                    <h3>Order #<?php echo htmlspecialchars($order['order_number']); ?></h3>
                    
                    <!-- Order Tracking Timeline -->
                    <div class="order-timeline">
                        <?php foreach ($timelineSteps as $index => $step): ?>
                        <div class="timeline-step">
                            <div class="step-content">
                                <div class="step-icon <?php echo $step['status']; ?>">
                                    <i class="fas <?php echo $step['icon']; ?>"></i>
                                </div>
                                <div class="step-details">
                                    <div class="step-title"><?php echo htmlspecialchars($step['title']); ?></div>
                                    <div class="step-description"><?php echo htmlspecialchars($step['description']); ?></div>
                                    <?php if ($step['date']): ?>
                                        <div class="step-date"><?php echo date('M j, Y g:i A', strtotime($step['date'])); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Order Information</h5>
                            <p>
                                <strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?><br>
                                <strong>Order Status:</strong> 
                                <span class="badge bg-<?php 
                                    echo $order['status'] === 'delivered' ? 'success' : 
                                         ($order['status'] === 'processing' ? 'warning' : 
                                         ($order['status'] === 'shipped' ? 'info' : 'secondary')); 
                                ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </span><br>
                                <strong>Payment Status:</strong> 
                                <span class="badge bg-<?php 
                                    echo $order['payment_status'] === 'paid' ? 'success' : 'danger'; 
                                ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                                </span><br>
                                <?php if ($order['payment_method']): ?>
                                    <strong>Payment Method:</strong> <?php echo ucfirst(htmlspecialchars($order['payment_method'])); ?><br>
                                <?php endif; ?>
                            </p>
                            <?php if ($order['status'] === 'shipped'): ?>
                                <button id="markReceivedBtn" class="btn btn-success" onclick="markAsReceived(<?php echo (int)$order['id']; ?>)">
                                    <i class="fas fa-check-circle me-2"></i>Mark as Received
                                </button>
                                <div id="markReceivedMsg" class="mt-2"></div>
                            <?php elseif ($order['status'] === 'delivered' && !$alreadyRated): ?>
                                <a href="/ecommerce_farmers_fishers/buyer/rating_review.php?order_id=<?php echo (int)$order['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-star me-2"></i>Rate This Order
                                </a>
                            <?php elseif ($order['status'] === 'delivered' && $alreadyRated): ?>
                                <span class="badge bg-success">Already Rated</span>
                            <?php endif; ?>

                            <?php if ($order['payment_status'] === 'pending'): ?>
                                <a href="/ecommerce_farmers_fishers/buyer/payment.php?order_id=<?php echo (int)$order['id']; ?>" class="btn btn-warning mt-2">
                                    <i class="fas fa-credit-card me-2"></i>Pay Now
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h5>Shipping Address</h5>
                            <p>
                                <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?><br>
                                <?php echo htmlspecialchars(($order['street_address'] ?? '') . ', ' . ($order['city'] ?? '') . ', ' . ($order['province'] ?? '')); ?><br>
                                <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?>
                            </p>
                            
                            <?php if ($deliveryInfo && $deliveryInfo['delivery_partner_name']): ?>
                            <h5>Delivery Information</h5>
                            <p>
                                <strong>Delivery Partner:</strong> <?php echo htmlspecialchars($deliveryInfo['delivery_partner_name']); ?><br>
                                <?php if ($deliveryInfo['tracking_number']): ?>
                                    <strong>Tracking Number:</strong> <?php echo htmlspecialchars($deliveryInfo['tracking_number']); ?><br>
                                <?php endif; ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h5>Order Items</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Product Name</th>
                                    <th>Quantity</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($item['product_image'])): ?>
                                            <img src="/ecommerce_farmers_fishers/<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="img-fluid" style="max-width: 80px;">
                                        <?php else: ?>
                                            <div class="bg-light border d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                <span class="text-muted small">No Image</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td class="text-end">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td class="text-end">₱<?php echo number_format($item['total'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">₱<?php echo number_format($order['subtotal'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Shipping:</strong></td>
                                    <td class="text-end">₱<?php echo number_format($order['shipping'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Tax:</strong></td>
                                    <td class="text-end">₱<?php echo number_format($order['tax'], 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>₱<?php echo number_format($order['total'], 2); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="/ecommerce_farmers_fishers/buyer/dashboard.php?tab=orders" class="btn btn-primary">Back to Orders</a>
                        <a href="/ecommerce_farmers_fishers/buyer/dashboard.php" class="btn btn-success">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function markAsReceived(orderId) {
        if (!confirm('Are you sure you want to mark this order as received?')) {
            return;
        }
        
        const btn = document.getElementById('markReceivedBtn');
        const msg = document.getElementById('markReceivedMsg');
        
        // Disable button and show loading
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        
        fetch('/ecommerce_farmers_fishers/api/mark_order_received.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                msg.innerHTML = '<div class="alert alert-success">Order marked as received successfully!</div>';
                // Reload page after 2 seconds to show updated status
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                msg.innerHTML = '<div class="alert alert-danger">Error: ' + (data.error || 'Failed to mark order as received') + '</div>';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Mark as Received';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            msg.innerHTML = '<div class="alert alert-danger">Error: Failed to mark order as received</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Mark as Received';
        });
    }
    
    // Update cart count on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateCartCount();
    });
    
    function updateCartCount() {
        fetch('/ecommerce_farmers_fishers/api/cart_count.php')
            .then(response => response.json())
            .then(data => {
                const cartCountElement = document.querySelector('.cart-count');
                if (data.count > 0) {
                    cartCountElement.textContent = data.count;
                    cartCountElement.style.display = 'inline-block';
                } else {
                    cartCountElement.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error updating cart count:', error);
            });
    }
    </script>
</body>
</html>