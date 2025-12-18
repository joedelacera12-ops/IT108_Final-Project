<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('seller');
$user = current_user();
$pdo = get_db();

// Fetch analytics data
$total_sales = $pdo->prepare('SELECT SUM(total) FROM orders WHERE seller_id = ? AND status = \'delivered\'');
$total_sales->execute([$user['id']]);
$total_sales = $total_sales->fetchColumn();

$total_orders = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE seller_id = ?');
$total_orders->execute([$user['id']]);
$total_orders = $total_orders->fetchColumn();

$top_products = $pdo->prepare('SELECT p.name, SUM(oi.quantity) as total_quantity FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE p.seller_id = ? GROUP BY p.name ORDER BY total_quantity DESC LIMIT 5');
$top_products->execute([$user['id']]);
$top_products = $top_products->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <h1 class="mt-4">Analytics Dashboard</h1>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Sales</h5>
                        <p class="card-text">$<?php echo number_format($total_sales, 2); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <p class="card-text"><?php echo $total_orders; ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Sales Over Time</h5>
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Top Products</h5>
                        <ul class="list-group">
                            <?php foreach ($top_products as $product): ?>
                                <li class="list-group-item"><?php echo $product['name']; ?> (<?php echo $product['total_quantity']; ?> sold)</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Chart.js implementation for sales chart
        const salesChartCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesChartCtx, {
            type: 'line',
            data: {
                labels: [], // Add labels for time periods
                datasets: [{
                    label: 'Sales',
                    data: [], // Add sales data
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>