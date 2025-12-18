<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only buyers may access
require_role('buyer');
$user = current_user();
$pdo = get_db();

// Get buyer orders
$orders = [];
try {
    $stmt = $pdo->prepare("SELECT id, order_number, total, status, created_at, payment_status FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Buyer orders query failed: " . $e->getMessage());
    $orders = [];
}
?>

<div class="dashboard-header text-center">
    <div class="container">
        <h1 class="display-5 fw-bold mb-3">Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
        <p class="lead mb-0">Manage your orders and shopping experience</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-4">
        <div class="feature-card text-center p-4 h-100 d-flex flex-column justify-content-center align-items-center">
            <div class="feature-icon cart-icon-bg mb-3">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h5 class="fw-bold mb-2">Shopping Cart</h5>
            <p class="text-muted small mb-3">View and manage your cart</p>
            <a href="#" class="btn btn-success btn-feature mt-auto" onclick="loadContent('cart'); return false;">View Cart</a>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="feature-card text-center p-4 h-100 d-flex flex-column justify-content-center align-items-center">
            <div class="feature-icon orders-icon-bg mb-3">
                <i class="fas fa-box-open"></i>
            </div>
            <h5 class="fw-bold mb-2">My Orders</h5>
            <p class="text-muted small mb-3">Track your order history</p>
            <a href="#" class="btn btn-primary btn-feature mt-auto" onclick="loadContent('orders'); return false;">View Orders</a>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="feature-card text-center p-4 h-100 d-flex flex-column justify-content-center align-items-center">
            <div class="feature-icon market-icon-bg mb-3">
                <i class="fas fa-store"></i>
            </div>
            <h5 class="fw-bold mb-2">Marketplace</h5>
            <p class="text-muted small mb-3">Browse and shop products</p>
            <a href="#" class="btn btn-info btn-feature mt-auto" onclick="loadContent('marketplace'); return false;">Shop Now</a>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="feature-card text-center p-4 h-100 d-flex flex-column justify-content-center align-items-center">
            <div class="feature-icon fav-icon-bg mb-3">
                <i class="fas fa-heart"></i>
            </div>
            <h5 class="fw-bold mb-2">Favorites</h5>
            <p class="text-muted small mb-3">View your favorite products</p>
            <a href="#" class="btn btn-danger btn-feature mt-auto" onclick="loadContent('favorites'); return false;">View Favorites</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <h3 class="section-title">Recent Activity</h3>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="stat-card p-4 h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success text-white me-3">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold"><?php echo count($orders); ?></h5>
                            <p class="text-muted mb-0 small">Total Orders</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card p-4 h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary text-white me-3">
                            <i class="fas fa-box"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">
                                <?php 
                                    $deliveredOrders = array_filter($orders, function($order) { 
                                        return $order['status'] === 'delivered'; 
                                    });
                                    echo count($deliveredOrders);
                                ?>
                            </h5>
                            <p class="text-muted mb-0 small">Delivered</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card p-4 h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info text-white me-3">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">₱<?php 
                                $totalSpent = array_sum(array_column($orders, 'total'));
                                echo number_format($totalSpent, 2);
                            ?></h5>
                            <p class="text-muted mb-0 small">Total Spent</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>