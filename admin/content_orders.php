<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins may access
require_role('admin');
$user = current_user();
$pdo = get_db();

// Get all orders with buyer and seller information
$orders = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            buyer.first_name as buyer_first_name,
            buyer.last_name as buyer_last_name,
            buyer.email as buyer_email,
            seller.first_name as seller_first_name,
            seller.last_name as seller_last_name,
            seller.email as seller_email
        FROM orders o
        JOIN users buyer ON o.buyer_id = buyer.id
        JOIN users seller ON o.seller_id = seller.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $orders = [];
}
?>

<h3>Orders</h3>

<?php if (empty($orders)): ?>
    <p>No orders found.</p>
<?php else: ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Buyer</th>
                <th>Seller</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['id']); ?></td>
                    <td>
                        <?php echo htmlspecialchars(($order['buyer_first_name'] ?? '') . ' ' . ($order['buyer_last_name'] ?? '')); ?>
                        <br>
                        <small><?php echo htmlspecialchars($order['buyer_email'] ?? ''); ?></small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars(($order['seller_first_name'] ?? '') . ' ' . ($order['seller_last_name'] ?? '')); ?>
                        <br>
                        <small><?php echo htmlspecialchars($order['seller_email'] ?? ''); ?></small>
                    </td>
                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td>
                        <?php 
                        $status = $order['status'] ?? 'pending';
                        echo ucfirst($status);
                        ?>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>