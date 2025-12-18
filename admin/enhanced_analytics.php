<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins can access this page
require_role('admin');
$user = current_user();
$pdo = get_db();

// Get sales data by product
try {
    $productSalesStmt = $pdo->prepare("SELECT * FROM v_product_sales ORDER BY total_income DESC LIMIT 20");
    $productSalesStmt->execute();
    $productSales = $productSalesStmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching product sales: " . $e->getMessage());
    $productSales = [];
}

// Get sales data by category
try {
    $categorySalesStmt = $pdo->prepare("SELECT * FROM v_category_sales ORDER BY total_income DESC");
    $categorySalesStmt->execute();
    $categorySales = $categorySalesStmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching category sales: " . $e->getMessage());
    $categorySales = [];
}

// Get monthly sales data
try {
    $monthlySalesStmt = $pdo->prepare("SELECT * FROM v_monthly_sales ORDER BY month DESC LIMIT 12");
    $monthlySalesStmt->execute();
    $monthlySales = array_reverse($monthlySalesStmt->fetchAll());
} catch (Exception $e) {
    error_log("Error fetching monthly sales: " . $e->getMessage());
    $monthlySales = [];
}

// Get top sellers by income
try {
    $topSellersStmt = $pdo->prepare("SELECT * FROM v_top_sellers ORDER BY total_income DESC LIMIT 10");
    $topSellersStmt->execute();
    $topSellers = $topSellersStmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching top sellers: " . $e->getMessage());
    $topSellers = [];
}

// Since this is now included in the dashboard, we don't need the full HTML structure
// Just the content part
?>

<div class="container-fluid p-0">
    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card dashboard-card">
                <div class="card-body">
                    <h6 class="text-muted">Total Revenue</h6>
                    <h3 class="text-primary">
                        ₱<?php 
                            $totalRevenue = array_sum(array_column($categorySales, 'total_income'));
                            echo number_format($totalRevenue, 2);
                        ?>
                    </h3>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card dashboard-card">
                <div class="card-body">
                    <h6 class="text-muted">Total Orders</h6>
                    <h3 class="text-success">
                        <?php 
                            $totalOrders = array_sum(array_column($categorySales, 'total_orders'));
                            echo number_format($totalOrders);
                        ?>
                    </h3>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card dashboard-card">
                <div class="card-body">
                    <h6 class="text-muted">Top Category</h6>
                    <h3 class="text-warning">
                        <?php echo !empty($categorySales) ? htmlspecialchars($categorySales[0]['category_name']) : 'N/A'; ?>
                    </h3>
                    <small class="text-muted">
                        ₱<?php echo !empty($categorySales) ? number_format($categorySales[0]['total_income'], 2) : '0.00'; ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card dashboard-card">
                <div class="card-body">
                    <h6 class="text-muted">Top Product</h6>
                    <h3 class="text-danger">
                        <?php echo !empty($productSales) ? htmlspecialchars($productSales[0]['product_name']) : 'N/A'; ?>
                    </h3>
                    <small class="text-muted">
                        ₱<?php echo !empty($productSales) ? number_format($productSales[0]['total_income'], 2) : '0.00'; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Monthly Revenue Trend</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Revenue by Category</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-star me-2"></i>Top Performing Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Seller</th>
                                    <th>Units Sold</th>
                                    <th>Total Income</th>
                                    <th>Avg. Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productSales as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['seller_name']); ?></td>
                                        <td><?php echo number_format($product['total_quantity']); ?></td>
                                        <td>₱<?php echo number_format($product['total_income'], 2); ?></td>
                                        <td>
                                            <?php if ($product['avg_rating']): ?>
                                                <span class="badge bg-warning">
                                                    <?php echo number_format($product['avg_rating'], 1); ?> 
                                                    <i class="fas fa-star"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">No ratings</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($productSales)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No product sales data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Sellers Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Performing Sellers</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Seller</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Products Listed</th>
                                    <th>Total Income</th>
                                    <th>Total Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topSellers as $seller): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($seller['seller_name']); ?></td>
                                        <td><?php echo htmlspecialchars($seller['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $seller['seller_type'] === 'farmer' ? 'success' : 
                                                     ($seller['seller_type'] === 'fisher' ? 'info' : 'secondary');
                                            ?>">
                                                <?php echo ucfirst(htmlspecialchars($seller['seller_type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($seller['products_listed']); ?></td>
                                        <td>₱<?php echo number_format($seller['total_income'], 2); ?></td>
                                        <td><?php echo number_format($seller['total_orders']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($topSellers)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No seller data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Revenue Chart
    const monthlyLabels = <?php echo json_encode(array_column($monthlySales, 'month')); ?>;
    const monthlyData = <?php echo json_encode(array_column($monthlySales, 'total_income')); ?>;
    
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'Monthly Revenue (₱)',
                data: monthlyData,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Category Revenue Chart
    const categoryLabels = <?php echo json_encode(array_column($categorySales, 'category_name')); ?>;
    const categoryData = <?php echo json_encode(array_column($categorySales, 'total_income')); ?>;
    
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryData,
                backgroundColor: [
                    '#0d6efd', '#6610f2', '#198754', '#ffc107', 
                    '#dc3545', '#0dcaf0', '#20c997', '#fd7e14'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ₱' + context.parsed.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>

<style>
.dashboard-card {
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
}

.stat-card {
    border-left: 4px solid #0d6efd;
}

.chart-container {
    position: relative;
    height: 300px;
}
</style>