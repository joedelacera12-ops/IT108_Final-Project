<?php
require_once '../includes/db.php';

// Seller Performance Metrics
$seller_performance = [];

try {
    // Try to use the view first
    $stmt = $pdo->prepare("
        SELECT 
            seller_id,
            seller_name as first_name,
            '' as last_name,
            total_orders,
            total_income as total_sales,
            avg_rating as average_rating
        FROM v_seller_performance
        ORDER BY total_income DESC
        LIMIT 10
    ");
    $stmt->execute();
    $seller_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If view doesn't exist, use direct query
    $stmt = $pdo->prepare("
        SELECT 
            u.id AS seller_id,
            u.first_name,
            u.last_name,
            COUNT(DISTINCT o.id) AS total_orders,
            COALESCE(SUM(o.total), 0) AS total_sales,
            COALESCE(AVG(sr.rating), 0) AS average_rating
        FROM users u
        LEFT JOIN products p ON u.id = p.seller_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'delivered' AND o.payment_status = 'paid'
        LEFT JOIN seller_ratings sr ON u.id = sr.seller_id
        WHERE u.role_id = (SELECT id FROM user_roles WHERE name = 'seller')
        GROUP BY u.id, u.first_name, u.last_name
        ORDER BY total_sales DESC
        LIMIT 10
    ");
    $stmt->execute();
    $seller_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Delivery Partner Performance Metrics
$delivery_partner_performance = [];

try {
    // Try to use the view first
    $stmt = $pdo->prepare("
        SELECT 
            delivery_partner_id,
            delivery_partner_name as first_name,
            '' as last_name,
            completed_deliveries as total_deliveries,
            avg_delivery_days as avg_delivery_time_days
        FROM v_delivery_partner_performance
        ORDER BY completed_deliveries DESC
        LIMIT 10
    ");
    $stmt->execute();
    $delivery_partner_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If view doesn't exist, use direct query
    $stmt = $pdo->prepare("
        SELECT 
            dp.id AS delivery_partner_id,
            dp.name AS first_name,
            '' AS last_name,
            COUNT(od.id) AS total_deliveries,
            COALESCE(AVG(DATEDIFF(od.delivered_at, od.assigned_at)), 0) AS avg_delivery_time_days
        FROM delivery_partners dp
        LEFT JOIN order_deliveries od ON dp.id = od.delivery_partner_id AND od.status = 'delivered'
        GROUP BY dp.id, dp.name
        ORDER BY total_deliveries DESC
        LIMIT 10
    ");
    $stmt->execute();
    $delivery_partner_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Analytics</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Seller and Delivery Partner Performance</li>
    </ol>

    <!-- Seller Performance -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users me-1"></i>
            Seller Performance
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Seller</th>
                        <th>Total Orders</th>
                        <th>Total Sales</th>
                        <th>Average Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($seller_performance as $seller): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($seller['total_orders']); ?></td>
                            <td>₱<?php echo htmlspecialchars(number_format($seller['total_sales'], 2)); ?></td>
                            <td>
                                <?php if ($seller['average_rating']):
                                    echo number_format($seller['average_rating'], 2) . ' / 5';
                                else:
                                    echo 'No ratings yet';
                                endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delivery Partner Performance -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-truck me-1"></i>
            Delivery Partner Performance
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Delivery Partner</th>
                        <th>Total Deliveries</th>
                        <th>Avg. Delivery Time (Days)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($delivery_partner_performance as $partner): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($partner['first_name'] . ' ' . $partner['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($partner['total_deliveries']); ?></td>
                            <td><?php echo htmlspecialchars(number_format($partner['avg_delivery_time_days'], 1)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>