<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins may access
require_role('admin');
$user = current_user();
$pdo = get_db();

// Get sales statistics
$salesData = [];
$totalSales = 0;
$totalOrders = 0;
$completedOrders = 0;

try {
    // Total sales amount
    $stmt = $pdo->prepare("SELECT SUM(total_amount) as total_sales FROM orders WHERE status = 'delivered'");
    $stmt->execute();
    $totalSales = $stmt->fetch()['total_sales'] ?? 0;
    
    // Total orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_orders FROM orders");
    $stmt->execute();
    $totalOrders = $stmt->fetch()['total_orders'] ?? 0;
    
    // Completed orders
    $stmt = $pdo->prepare("SELECT COUNT(*) as completed_orders FROM orders WHERE status = 'delivered'");
    $stmt->execute();
    $completedOrders = $stmt->fetch()['completed_orders'] ?? 0;
    
} catch (Exception $e) {
    error_log("Error fetching sales data: " . $e->getMessage());
}

// Get user statistics
$totalUsers = 0;
$activeSellers = 0;
$activeBuyers = 0;

try {
    // Total users
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users WHERE role != 'admin'");
    $stmt->execute();
    $totalUsers = $stmt->fetch()['total_users'] ?? 0;
    
    // Active sellers
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_sellers FROM users WHERE role = 'seller' AND status = 'approved'");
    $stmt->execute();
    $activeSellers = $stmt->fetch()['active_sellers'] ?? 0;
    
    // Active buyers
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_buyers FROM users WHERE role = 'buyer' AND status = 'approved'");
    $stmt->execute();
    $activeBuyers = $stmt->fetch()['active_buyers'] ?? 0;
    
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
}

// Get top selling products
$topProducts = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.name,
            p.price,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as total_revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status = 'delivered'
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 10
    ");
    $stmt->execute();
    $topProducts = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching top products: " . $e->getMessage());
}
?>

<h3>Reports</h3>

<h4>Summary</h4>
<ul>
    <li>Total Sales: ₱<?php echo number_format($totalSales, 2); ?></li>
    <li>Total Orders: <?php echo number_format($totalOrders); ?></li>
    <li>Completed Orders: <?php echo number_format($completedOrders); ?></li>
    <li>Total Users: <?php echo number_format($totalUsers); ?></li>
</ul>

<h4>Top Selling Products</h4>
<?php if (empty($topProducts)): ?>
    <p>No sales data available.</p>
<?php else: ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Price</th>
                <th>Units Sold</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topProducts as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td>₱<?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo number_format($product['total_sold']); ?></td>
                    <td>₱<?php echo number_format($product['total_revenue'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>