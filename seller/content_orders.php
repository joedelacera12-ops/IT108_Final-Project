<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

require_role('seller');
$user = current_user();
$pdo = get_db();

$orders = [];
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT o.*, 
               CONCAT(u.first_name, ' ', u.last_name) as buyer_name
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN users u ON o.user_id = u.id
        WHERE oi.seller_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Seller orders query failed: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">My Orders</h1>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Orders List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="ordersTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Buyer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Payment Status</th>
                            <th>Order Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No orders found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td>₱<?php echo number_format($order['total'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $order['status'] === 'delivered' ? 'success' : 
                                                 ($order['status'] === 'processing' ? 'warning' : 
                                                 ($order['status'] === 'shipped' ? 'info' : 
                                                 ($order['status'] === 'approved' ? 'primary' : 
                                                 ($order['status'] === 'pending' ? 'secondary' : 'dark')))); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($order['status']))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($order['status'] === 'pending' && $order['payment_status'] === 'paid'): ?>
                                            <button class="btn btn-sm btn-success approve-order-btn" data-order-id="<?php echo $order['id']; ?>">Approve</button>
                                            <button class="btn btn-sm btn-danger decline-order-btn" data-order-id="<?php echo $order['id']; ?>">Reject</button>
                                        <?php else: ?>
                                            <span class="text-muted">No actions available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#ordersTable').DataTable();

    document.querySelectorAll('.approve-order-btn').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            if (confirm('Are you sure you want to approve this order?')) {
                updateOrderStatus(orderId, 'approved');
            }
        });
    });

    document.querySelectorAll('.decline-order-btn').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            if (confirm('Are you sure you want to reject this order?')) {
                updateOrderStatus(orderId, 'cancelled');
            }
        });
    });

    function updateOrderStatus(orderId, status) {
        fetch('/api/update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ order_id: orderId, status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Order ' + status.replace('_', ' ') + ' successfully.');
                location.reload();
            } else {
                alert('Failed to update order status: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the order status.');
        });
    }
});
</script>