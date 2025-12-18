<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('admin');
$pdo = get_db();

// Get category sales data
try {
    $categoryStmt = $pdo->prepare("SELECT * FROM v_category_sales ORDER BY total_income DESC");
    $categoryStmt->execute();
    $categorySales = $categoryStmt ? $categoryStmt->fetchAll() : [];
} catch (Exception $e) {
    error_log("Error fetching category sales: " . $e->getMessage());
    $categorySales = [];
}

// Get top products by income
try {
    $productStmt = $pdo->prepare("SELECT * FROM v_product_sales ORDER BY total_income DESC LIMIT 10");
    $productStmt->execute();
    $topProducts = $productStmt ? $productStmt->fetchAll() : [];
} catch (Exception $e) {
    error_log("Error fetching top products: " . $e->getMessage());
    $topProducts = [];
}

// Get monthly sales trend
try {
    $monthlyStmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(o.created_at, '%Y-%m') as month,
            SUM(o.total) as total_income,
            COUNT(DISTINCT o.id) as total_orders
        FROM orders o
        WHERE o.payment_status = 'paid' AND o.created_at IS NOT NULL
        GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $monthlyStmt->execute();
    $monthlyData = $monthlyStmt ? $monthlyStmt->fetchAll() : [];
    $monthlyData = array_reverse($monthlyData); // Reverse to show chronological order
} catch (Exception $e) {
    error_log("Error fetching monthly sales: " . $e->getMessage());
    $monthlyData = [];
}

// Get top categories
$topCategories = array_slice($categorySales, 0, 5);

// Get seller performance
try {
    // Check if v_seller_performance view exists
    $viewCheck = $pdo->prepare("SHOW TABLES LIKE 'v_seller_performance'");
    $viewCheck->execute();
    if ($viewCheck->rowCount() > 0) {
        $sellerStmt = $pdo->prepare("SELECT seller_id, seller_name, first_name, last_name, seller_type, total_income, total_orders, successfully_delivered_orders, avg_rating, total_ratings FROM v_seller_performance ORDER BY total_income DESC LIMIT 10");
        $sellerStmt->execute();
        $topSellers = $sellerStmt ? $sellerStmt->fetchAll() : [];
    } else {
        // Fallback query if view doesn't exist
        $sellerStmt = $pdo->prepare("
            SELECT 
                u.id as seller_id,
                CONCAT(u.first_name, ' ', u.last_name) as seller_name,
                u.first_name,
                u.last_name,
                u.seller_type,
                COALESCE(SUM(oi.total), 0) as total_income,
                COUNT(DISTINCT o.id) as total_orders,
                COUNT(CASE WHEN o.status = 'delivered' THEN 1 END) as successfully_delivered_orders,
                COALESCE(AVG(sr.rating), 0) as avg_rating,
                COUNT(sr.rating) as total_ratings
            FROM users u
            LEFT JOIN products p ON u.id = p.seller_id
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
            LEFT JOIN seller_ratings sr ON u.id = sr.seller_id
            WHERE u.role_id = (SELECT id FROM user_roles WHERE name = 'seller') AND o.created_at IS NOT NULL
            GROUP BY u.id, u.first_name, u.last_name, u.seller_type
            ORDER BY total_income DESC
            LIMIT 10
        ");
        $sellerStmt->execute();
        $topSellers = $sellerStmt ? $sellerStmt->fetchAll() : [];
    }
} catch (Exception $e) {
    error_log("Error fetching seller performance: " . $e->getMessage());
    $topSellers = [];
}

// Get user growth data
try {
    $userGrowthStmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(CASE WHEN role_id = (SELECT id FROM user_roles WHERE name = 'buyer') THEN 1 END) as buyer_count,
            COUNT(CASE WHEN role_id = (SELECT id FROM user_roles WHERE name = 'seller') THEN 1 END) as seller_count
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) AND created_at IS NOT NULL
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $userGrowthStmt->execute();
    $userGrowthData = $userGrowthStmt ? $userGrowthStmt->fetchAll() : [];
    $userGrowthData = array_reverse($userGrowthData);
} catch (Exception $e) {
    error_log("Error fetching user growth data: " . $e->getMessage());
    $userGrowthData = [];
}

// Get payment method statistics
try {
    $paymentMethodStmt = $pdo->prepare("SELECT payment_method, COUNT(*) as count, SUM(amount) as total_amount FROM payment_history WHERE status = 'completed' GROUP BY payment_method ORDER BY total_amount DESC");
    $paymentMethodStmt->execute();
    $paymentMethods = $paymentMethodStmt ? $paymentMethodStmt->fetchAll() : [];
} catch (Exception $e) {
    error_log("Error fetching payment methods: " . $e->getMessage());
    $paymentMethods = [];
}

// Get fast/slow market indicators
try {
    $marketIndicatorsStmt = $pdo->prepare("
        SELECT 
            c.name as category_name,
            COALESCE(AVG(DATEDIFF(o.created_at, p.created_at)), 0) as avg_time_to_sale,
            COUNT(DISTINCT o.id) as order_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH) AND o.created_at IS NOT NULL
        GROUP BY c.id, c.name
        ORDER BY avg_time_to_sale ASC
    ");
    $marketIndicatorsStmt->execute();
    $marketIndicators = $marketIndicatorsStmt ? $marketIndicatorsStmt->fetchAll() : [];
} catch (Exception $e) {
    error_log("Error fetching market indicators: " . $e->getMessage());
    $marketIndicators = [];
}

// Get top selling products by quantity
try {
    $topProductsByQuantityStmt = $pdo->prepare("SELECT * FROM v_product_sales ORDER BY total_quantity_sold DESC LIMIT 10");
    $topProductsByQuantityStmt->execute();
    $topProductsByQuantity = $topProductsByQuantityStmt ? $topProductsByQuantityStmt->fetchAll() : [];
} catch (Exception $e) {
    error_log("Error fetching top products by quantity: " . $e->getMessage());
    $topProductsByQuantity = [];
}

// Get seller type distribution
try {
    $sellerTypeStmt = $pdo->prepare("SELECT seller_type, COUNT(*) as count FROM users WHERE role_id = (SELECT id FROM user_roles WHERE name = 'seller') AND seller_type IS NOT NULL GROUP BY seller_type");
    $sellerTypeStmt->execute();
    $sellerTypes = $sellerTypeStmt ? $sellerTypeStmt->fetchAll() : [];
} catch (Exception $e) {
    error_log("Error fetching seller types: " . $e->getMessage());
    $sellerTypes = [];
}
?>

<div class="container-fluid py-2">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-success"><i class="fas fa-chart-line me-2"></i>Analytics Dashboard</h2>
            <p class="text-muted">Comprehensive insights into marketplace performance</p>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <div class="card stat-card dashboard-card">
                <div class="card-body p-2">
                    <h6 class="text-muted mb-1" style="font-size:0.8rem;">Total Income</h6>
                    <h3 class="text-primary mb-0" style="font-size:1.2rem;">
                        ₱<?php 
                            $totalIncome = array_sum(array_column($categorySales, 'total_income'));
                            echo number_format($totalIncome, 2);
                        ?>
                    </h3>
                    <small class="text-muted" style="font-size:0.7rem;">All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card stat-card dashboard-card">
                <div class="card-body p-2">
                    <h6 class="text-muted mb-1" style="font-size:0.8rem;">Total Orders</h6>
                    <h3 class="text-success mb-0" style="font-size:1.2rem;">
                        <?php 
                            $totalOrders = array_sum(array_column($categorySales, 'total_orders'));
                            echo number_format($totalOrders);
                        ?>
                    </h3>
                    <small class="text-muted" style="font-size:0.7rem;">All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card stat-card dashboard-card">
                <div class="card-body p-2">
                    <h6 class="text-muted mb-1" style="font-size:0.8rem;">Top Category</h6>
                    <h3 class="text-warning mb-0" style="font-size:1.2rem;">
                        <?php echo !empty($topCategories) ? htmlspecialchars($topCategories[0]['category_name'] ?? 'N/A') : 'N/A'; ?>
                    </h3>
                    <small class="text-muted" style="font-size:0.7rem;">
                        ₱<?php echo !empty($topCategories) ? number_format($topCategories[0]['total_income'] ?? 0, 2) : '0.00'; ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card stat-card dashboard-card">
                <div class="card-body p-2">
                    <h6 class="text-muted mb-1" style="font-size:0.8rem;">Top Product</h6>
                    <h3 class="text-danger mb-0" style="font-size:1.2rem;">
                        <?php echo !empty($topProducts) ? htmlspecialchars($topProducts[0]['product_name'] ?? 'N/A') : 'N/A'; ?>
                    </h3>
                    <small class="text-muted" style="font-size:0.7rem;">
                        ₱<?php echo !empty($topProducts) ? number_format($topProducts[0]['total_income'] ?? 0, 2) : '0.00'; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Market Health Indicators -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Market Health Indicators</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-success">
                                    <?php 
                                        // Calculate growth rate
                                        $currentMonthIncome = 0;
                                        $previousMonthIncome = 0;
                                        if (count($monthlyData) >= 2) {
                                            $currentMonthIncome = (float)($monthlyData[count($monthlyData)-1]['total_income'] ?? 0);
                                            $previousMonthIncome = (float)($monthlyData[count($monthlyData)-2]['total_income'] ?? 0);
                                        }
                                        $growthRate = ($previousMonthIncome > 0) ? (($currentMonthIncome - $previousMonthIncome) / $previousMonthIncome) * 100 : 0;
                                        echo ($growthRate >= 0) ? '+' . number_format($growthRate, 1) . '%' : number_format($growthRate, 1) . '%';
                                    ?>
                                </h4>
                                <p class="mb-0 <?php echo ($growthRate >= 0) ? 'text-success' : 'text-danger'; ?>">
                                    <i class="fas fa-<?php echo ($growthRate >= 0) ? 'arrow-up' : 'arrow-down'; ?> me-1"></i>
                                    Monthly Growth
                                </p>
                                <small class="text-muted">Income trend</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-info">
                                    <?php 
                                        // Average order value
                                        $avgOrderValue = ($totalOrders > 0) ? $totalIncome / $totalOrders : 0;
                                        echo '₱' . number_format($avgOrderValue, 2);
                                    ?>
                                </h4>
                                <p class="mb-0 text-info">
                                    <i class="fas fa-shopping-cart me-1"></i>
                                    Avg Order Value
                                </p>
                                <small class="text-muted">Per transaction</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-primary">
                                    <?php 
                                        try {
                                            // Conversion rate (sellers with sales / total sellers)
                                            $sellersStmt = $pdo->prepare("SELECT COUNT(DISTINCT p.seller_id) FROM products p JOIN order_items oi ON p.id = oi.product_id");
                                            $sellersStmt->execute();
                                            $sellersWithSales = $sellersStmt->fetchColumn();
                                            $totalSellersStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = (SELECT id FROM user_roles WHERE name = 'seller')");
                                            $totalSellersStmt->execute();
                                            $totalSellers = $totalSellersStmt->fetchColumn();
                                            $conversionRate = ($totalSellers > 0) ? ($sellersWithSales / $totalSellers) * 100 : 0;
                                            echo number_format($conversionRate, 1) . '%';
                                        } catch (Exception $e) {
                                            // Handle case where order_items table doesn't exist yet
                                            echo '0.0%';
                                        }
                                    ?>
                                </h4>
                                <p class="mb-0 text-primary">
                                    <i class="fas fa-percentage me-1"></i>
                                    Seller Conversion
                                </p>
                                <small class="text-muted">Active sellers</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Ratings and Income Summary -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-star me-2"></i>Product Ratings & Income Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Seller</th>
                                    <th>Income (₱)</th>
                                    <th>Quantity Sold</th>
                                    <th>Avg Rating</th>
                                    <th>Total Ratings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Get top products with ratings
                                try {
                                    $ratedProductsStmt = $pdo->prepare("SELECT 
                                        p.name as product_name,
                                        c.name as category_name,
                                        CONCAT(u.first_name, ' ', u.last_name) as seller_name,
                                        ps.total_income,
                                        ps.total_quantity_sold,
                                        AVG(pr.rating) as avg_rating,
                                        COUNT(pr.rating) as rating_count
                                    FROM v_product_sales ps
                                    JOIN products p ON ps.product_id = p.id
                                    JOIN categories c ON p.category_id = c.id
                                    JOIN users u ON p.seller_id = u.id
                                    LEFT JOIN product_reviews pr ON p.id = pr.product_id
                                    GROUP BY ps.product_id, p.name, c.name, u.first_name, u.last_name, ps.total_income, ps.total_quantity_sold
                                    ORDER BY ps.total_income DESC
                                    LIMIT 10");
                                    $ratedProductsStmt->execute();
                                    $ratedProducts = $ratedProductsStmt->fetchAll();
                                } catch (Exception $e) {
                                    error_log("Error fetching rated products: " . $e->getMessage());
                                    $ratedProducts = [];
                                }
                                
                                foreach ($ratedProducts as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($product['seller_name'] ?? 'N/A'); ?></td>
                                    <td>₱<?php echo number_format($product['total_income'] ?? 0, 2); ?></td>
                                    <td><?php echo number_format($product['total_quantity_sold'] ?? 0); ?></td>
                                    <td>
                                        <?php if (($product['rating_count'] ?? 0) > 0): ?>
                                            <span class="text-warning">
                                                <?php 
                                                $avgRating = round($product['avg_rating'] ?? 0, 1);
                                                for ($i = 1; $i <= 5; $i++): 
                                                    if ($i <= floor($avgRating)): 
                                                        echo '★';
                                                    elseif ($i - 0.5 <= $avgRating): 
                                                        echo '☆';
                                                    else: 
                                                        echo '☆';
                                                    endif;
                                                endfor;
                                                ?>
                                            </span>
                                            (<?php echo $avgRating; ?>)
                                        <?php else: ?>
                                            <span class="text-muted">No ratings</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $product['rating_count'] ?? 0; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Rated Products by Category -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Rated Products by Category</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php 
                        // Get top rated products by category
                        try {
                            $topRatedByCategoryStmt = $pdo->prepare("SELECT 
                                c.name as category_name,
                                p.name as product_name,
                                CONCAT(u.first_name, ' ', u.last_name) as seller_name,
                                AVG(pr.rating) as avg_rating,
                                COUNT(pr.rating) as rating_count,
                                ps.total_income
                            FROM categories c
                            JOIN products p ON c.id = p.category_id
                            JOIN users u ON p.seller_id = u.id
                            JOIN v_product_sales ps ON p.id = ps.product_id
                            LEFT JOIN product_reviews pr ON p.id = pr.product_id
                            WHERE pr.rating IS NOT NULL
                            GROUP BY c.id, c.name, p.id, p.name, u.first_name, u.last_name, ps.total_income
                            ORDER BY c.name, AVG(pr.rating) DESC");
                            $topRatedByCategoryStmt->execute();
                            $topRatedByCategory = $topRatedByCategoryStmt->fetchAll();
                        } catch (Exception $e) {
                            error_log("Error fetching top rated products by category: " . $e->getMessage());
                            $topRatedByCategory = [];
                        }
                        
                        // Group by category
                        $categoryProducts = [];
                        foreach ($topRatedByCategory as $item) {
                            $categoryName = $item['category_name'];
                            if (!isset($categoryProducts[$categoryName])) {
                                $categoryProducts[$categoryName] = [];
                            }
                            $categoryProducts[$categoryName][] = $item;
                        }
                        
                        // Show top 3 products per category
                        foreach ($categoryProducts as $categoryName => $products):
                            if (count($products) > 0): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($categoryName); ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        // Sort by rating and take top 3
                                        usort($products, function($a, $b) {
                                            return ($b['avg_rating'] ?? 0) <=> ($a['avg_rating'] ?? 0);
                                        });
                                        $topProductsInCategory = array_slice($products, 0, 3);
                                        
                                        foreach ($topProductsInCategory as $product):
                                        ?>
                                        <div class="mb-3 pb-3 border-bottom">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($product['product_name'] ?? 'N/A'); ?></h6>
                                            <p class="mb-1 text-muted small">by <?php echo htmlspecialchars($product['seller_name'] ?? 'N/A'); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="text-warning">
                                                        <?php 
                                                        $avgRating = round($product['avg_rating'] ?? 0, 1);
                                                        for ($i = 1; $i <= 5; $i++): 
                                                            if ($i <= floor($avgRating)): 
                                                                echo '★';
                                                            elseif ($i - 0.5 <= $avgRating): 
                                                                echo '☆';
                                                            else: 
                                                                echo '☆';
                                                            endif;
                                                        endfor;
                                                        ?>
                                                    </span>
                                                    <span class="ms-1"><?php echo $avgRating; ?></span>
                                                </div>
                                                <small class="text-muted"><?php echo $product['rating_count'] ?? 0; ?> ratings</small>
                                            </div>
                                            <div class="mt-1">
                                                <span class="badge bg-success">₱<?php echo number_format($product['total_income'] ?? 0, 2); ?></span>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Growth Metrics -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stat-card dashboard-card">
                <div class="card-body">
                    <h6 class="text-muted">Total Buyers</h6>
                    <h3 class="text-info">
                        <?php 
                            $buyerCountStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = (SELECT id FROM user_roles WHERE name = 'buyer')");
                            $buyerCountStmt->execute();
                            $buyerCount = $buyerCountStmt->fetchColumn();
                            echo number_format($buyerCount);
                        ?>
                    </h3>
                    <small class="text-muted">Registered</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card dashboard-card">
                <div class="card-body">
                    <h6 class="text-muted">Total Sellers</h6>
                    <h3 class="text-info">
                        <?php 
                            $sellerCountStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = (SELECT id FROM user_roles WHERE name = 'seller')");
                            $sellerCountStmt->execute();
                            $sellerCount = $sellerCountStmt ? $sellerCountStmt->fetchColumn() : 0;
                            echo number_format($sellerCount);
                        ?>
                    </h3>
                    <small class="text-muted">Registered</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card dashboard-card">
                <div class="card-body">
                    <h6 class="text-muted">Total Products</h6>
                    <h3 class="text-info">
                        <?php 
                            $productCountStmt = $pdo->prepare("SELECT COUNT(*) FROM products");
                            $productCountStmt->execute();
                            $productCount = $productCountStmt ? $productCountStmt->fetchColumn() : 0;
                            echo number_format($productCount);
                        ?>
                    </h3>
                    <small class="text-muted">Listed</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Weekly Performance Summary -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Weekly Performance Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <h4 class="text-primary">₱<?php 
                                    try {
                                        // Get this week's income
                                        $weekStmt = $pdo->prepare("SELECT SUM(oi.subtotal) as weekly_income FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE o.payment_status = 'paid' AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND o.created_at IS NOT NULL");
                                        $weekStmt->execute();
                                        $weeklyIncome = $weekStmt ? ($weekStmt->fetchColumn() ?: 0) : 0;
                                    } catch (Exception $e) {
                                        // Handle case where order_items table doesn't exist yet
                                        $weeklyIncome = 0;
                                    }
                                    echo number_format($weeklyIncome, 2);
                                ?></h4>
                                <p class="text-muted mb-0">This Week</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <h4 class="text-success"><?php 
                                    // Get this week's orders
                                    $weekOrderStmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id) as weekly_orders FROM orders o WHERE o.payment_status = 'paid' AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND o.created_at IS NOT NULL");
                                    $weekOrderStmt->execute();
                                    $weeklyOrders = $weekOrderStmt ? ($weekOrderStmt->fetchColumn() ?: 0) : 0;
                                    echo number_format($weeklyOrders);
                                ?></h4>
                                <p class="text-muted mb-0">Orders This Week</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <h4 class="text-info"><?php 
                                    // Get new sellers this week
                                    $newSellersStmt = $pdo->prepare("SELECT COUNT(*) as new_sellers FROM users u JOIN user_roles ur ON u.role_id = ur.id WHERE ur.name = 'seller' AND u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND u.created_at IS NOT NULL");
                                    $newSellersStmt->execute();
                                    $newSellers = $newSellersStmt ? ($newSellersStmt->fetchColumn() ?: 0) : 0;
                                    echo number_format($newSellers);
                                ?></h4>
                                <p class="text-muted mb-0">New Sellers</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="text-center">
                                <h4 class="text-warning"><?php 
                                    // Get new buyers this week
                                    $newBuyersStmt = $pdo->prepare("SELECT COUNT(*) as new_buyers FROM users u JOIN user_roles ur ON u.role_id = ur.id WHERE ur.name = 'buyer' AND u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND u.created_at IS NOT NULL");
                                    $newBuyersStmt->execute();
                                    $newBuyers = $newBuyersStmt ? ($newBuyersStmt->fetchColumn() ?: 0) : 0;
                                    echo number_format($newBuyers);
                                ?></h4>
                                <p class="text-muted mb-0">New Buyers</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Top Performers -->
    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-star me-2"></i>Top Products by Income</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Income</th>
                                    <th>Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                    <td>₱<?php echo number_format($product['total_income'] ?? 0, 2); ?></td>
                                    <td><?php echo number_format($product['total_orders'] ?? 0); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-sort-amount-down me-2"></i>Top Products by Quantity</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Income</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProductsByQuantity as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($product['total_quantity_sold'] ?? 0); ?></td>
                                    <td>₱<?php echo number_format($product['total_income'] ?? 0, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Sellers by Income</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Seller</th>
                                    <th>Type</th>
                                    <th>Income</th>
                                    <th>Orders</th>
                                    <th>Delivered</th>
                                    <th>Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topSellers as $seller): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($seller['seller_name'] ?? ($seller['first_name'] ?? 'N/A') . ' ' . ($seller['last_name'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($seller['seller_type'] ?? 'N/A')); ?></td>
                                    <td>₱<?php echo number_format($seller['total_income'] ?? 0, 2); ?></td>
                                    <td><?php echo number_format($seller['total_orders'] ?? 0); ?></td>
                                    <td>
                                        <?php 
                                        $delivered = $seller['successfully_delivered_orders'] ?? 0;
                                        $total = $seller['total_orders'] ?? 0;
                                        $rate = ($total > 0) ? ($delivered / $total) * 100 : 0;
                                        echo number_format($delivered) . '/' . number_format($total) . ' (' . number_format($rate, 1) . '%)';
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $avgRating = round($seller['avg_rating'] ?? 0, 1);
                                        $totalRatings = $seller['total_ratings'] ?? 0;
                                        if ($totalRatings > 0):
                                            echo '<span class="text-warning">' . $avgRating . '</span> (' . $totalRatings . ')';
                                        else:
                                            echo '<span class="text-muted">No ratings</span>';
                                        endif;
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
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
    // No charts to initialize
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