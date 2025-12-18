<?php
require_once '../database/db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'seller') {
    header('Location: /ecommerce_farmers_fishers/login.php');
    exit;
}

$seller_id = $_SESSION['user_id'];

// Fetch completed orders for the seller
$stmt = $conn->prepare("
    SELECT o.id, o.order_date, o.total, r.rating, r.review
    FROM orders o
    LEFT JOIN ratings r ON o.id = r.order_id
    WHERE o.seller_id = ? AND o.status = 'delivered'
    ORDER BY o.order_date DESC
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$completed_orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Completed Orders</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">View your completed orders and customer ratings</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-shopping-cart me-1"></i>
            Completed Orders
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Order Date</th>
                        <th>Total</th>
                        <th>Rating</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($completed_orders)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No completed orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($completed_orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($order['order_date']))); ?></td>
                                <td>₱<?php echo htmlspecialchars(number_format($order['total'], 2)); ?></td>
                                <td>
                                    <?php if ($order['rating']):
                                        for ($i = 0; $i < 5; $i++) {
                                            if ($i < $order['rating']) {
                                                echo '<i class="fas fa-star text-warning"></i>';
                                            } else {
                                                echo '<i class="far fa-star text-warning"></i>';
                                            }
                                        }
                                    else:
                                        echo 'Not rated';
                                    endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($order['review'] ?? 'No review'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>