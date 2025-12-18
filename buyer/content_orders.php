<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only buyers may access
require_role('buyer');
$user = current_user();
$pdo = get_db();

// Get buyer orders - retrieve ALL orders for this buyer
$orders = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            o.id AS order_id, 
            o.order_number, 
            o.total, 
            o.status, 
            o.created_at, 
            o.payment_status, 
            od.delivered_at AS buyer_confirmed_at,
            p.name AS product_name,
            pi.image_path AS product_image,
            oi.quantity,
            oi.unit_price AS item_price
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN order_deliveries od ON o.delivery_id = od.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC, o.id DESC
    ");
    $stmt->execute([$user['id']]);
    $order_items = $stmt->fetchAll();

    $orders = [];
    foreach ($order_items as $item) {
        $orders[$item['order_id']]['order_number'] = $item['order_number'];
        $orders[$item['order_id']]['total'] = $item['total'];
        $orders[$item['order_id']]['status'] = $item['status'];
        $orders[$item['order_id']]['created_at'] = $item['created_at'];
        $orders[$item['order_id']]['payment_status'] = $item['payment_status'];
        $orders[$item['order_id']]['buyer_confirmed_at'] = $item['buyer_confirmed_at'];
        $orders[$item['order_id']]['items'][] = $item;
    }
    
    // Sort orders by created_at descending (newest first)
    uasort($orders, function($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });
} catch (Exception $e) {
    error_log("Buyer orders query failed: " . $e->getMessage());
    $orders = [];
}
?>

<div class="row">
    <div class="col-12">
        <h3 class="section-title">My Order History</h3>
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">No Orders Found</div>
        <?php else: ?>
            <div class="recent-orders-table">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="ordersTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order_id => $order): ?>
                                <?php foreach ($order['items'] as $index => $item): ?>
                                    <tr>
                                        <td class="align-middle">
                                            <?php if (!empty($item['product_image'])): ?>
                                                <img src="/ecommerce_farmers_fishers/<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="img-fluid" style="max-width: 100px;">
                                            <?php else: ?>
                                                <div class="bg-light border d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                                    <span class="text-muted">No Image</span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td class="align-middle">₱<?php echo number_format($item['item_price'], 2); ?></td>
                                        <?php if ($index === 0): ?>
                                            <td rowspan="<?php echo count($order['items']); ?>" class="align-middle"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                            <td rowspan="<?php echo count($order['items']); ?>" class="align-middle font-weight-bold">₱<?php echo number_format($order['total'], 2); ?></td>
                                            <td rowspan="<?php echo count($order['items']); ?>" class="align-middle">
                                                <span class="badge badge-status bg-<?php 
                                                    echo $order['status'] === 'delivered' ? 'success' : 
                                                         ($order['status'] === 'processing' ? 'warning' : 
                                                         ($order['status'] === 'shipped' ? 'info' : 
                                                         ($order['status'] === 'pending' ? 'secondary' : 
                                                         ($order['status'] === 'cancelled' ? 'danger' : 'dark')))); 
                                                ?>">
                                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                                </span>
                                            </td>
                                            <td rowspan="<?php echo count($order['items']); ?>" class="align-middle">
                                                <span class="badge badge-status bg-<?php 
                                                    echo $order['payment_status'] === 'paid' ? 'success' : 'danger'; 
                                                ?>">
                                                    <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                                                </span>
                                            </td>
                                            <td rowspan="<?php echo count($order['items']); ?>" class="text-center align-middle">
                                                <a href="/ecommerce_farmers_fishers/buyer/order_details.php?id=<?php echo $order_id; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">View Details</a>
                                                <?php if ($order['status'] === 'delivered' && !$order['buyer_confirmed_at']): ?>
                                                    <button class="btn btn-sm btn-outline-success rounded-pill px-3 confirm-delivery" data-order="<?php echo $order_id; ?>">Confirm Delivery</button>
                                                <?php elseif ($order['status'] === 'delivered' && $order['buyer_confirmed_at']): ?>
                                                    <a href="/ecommerce_farmers_fishers/buyer/rating_review.php?order_id=<?php echo $order_id; ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">Rate Order</a>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#ordersTable').DataTable();

    document.querySelectorAll('.confirm-delivery').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.order;
            if (!confirm('Are you sure you want to confirm the delivery of this order?')) {
                return;
            }

            fetch('/ecommerce_farmers_fishers/api/confirm_delivery.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the content to show the updated status
                    loadContent('orders');
                } else {
                    alert('Failed to confirm delivery: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while confirming the delivery.');
            });
        });
    });
});
</script>