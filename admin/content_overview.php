<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins may access
require_role('admin');
$user = current_user();
$pdo = get_db();

// Resolve role IDs (if user_roles table exists) for seller, buyer, and delivery partner, else fall back to legacy 'role' column
$sellerRoleId = null;
$buyerRoleId = null;
$deliveryPartnerRoleId = null;
try {
  $r = $pdo->prepare('SELECT id, name FROM user_roles WHERE name IN (?, ?, ?)');
  $r->execute(['seller', 'buyer', 'delivery_partner']);
  $rows = $r->fetchAll();
  foreach ($rows as $row) {
    if ($row['name'] === 'seller') $sellerRoleId = $row['id'];
    if ($row['name'] === 'buyer') $buyerRoleId = $row['id'];
    if ($row['name'] === 'delivery_partner') $deliveryPartnerRoleId = $row['id'];
  }
} catch (Exception $e) {
  // ignore - proceed with legacy queries
}

// Get additional statistics for the dashboard - using actual database values only
$totalProducts = 0;
$totalOrders = 0;
$recentOrders = [];
$totalRevenue = 0;
$totalSellers = 0;
$totalBuyers = 0;

try {
  // Get total products
  $totalProductsStmt = $pdo->query("SELECT COUNT(*) FROM products");
  $totalProducts = $totalProductsStmt ? $totalProductsStmt->fetchColumn() : 0;
  
  // Get total orders
  $totalOrdersStmt = $pdo->query("SELECT COUNT(*) FROM orders");
  $totalOrders = $totalOrdersStmt ? $totalOrdersStmt->fetchColumn() : 0;
  
  // Get recent orders
  $recentOrdersStmt = $pdo->query("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.created_at IS NOT NULL ORDER BY o.created_at DESC LIMIT 5");
  $recentOrders = $recentOrdersStmt ? $recentOrdersStmt->fetchAll() : [];
  
  // Get total revenue
  $totalRevenueStmt = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'paid'");
  $totalRevenue = $totalRevenueStmt ? $totalRevenueStmt->fetchColumn() : 0;
  
  // Get total sellers and delivery partners
  if ($sellerRoleId && $deliveryPartnerRoleId) {
    $totalSellersStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id IN (?, ?)");
    $totalSellersStmt->execute([$sellerRoleId, $deliveryPartnerRoleId]);
    $totalSellers = $totalSellersStmt->fetchColumn();
  } else {
    $totalSellersStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('seller', 'delivery_partner')");
    $totalSellers = $totalSellersStmt ? $totalSellersStmt->fetchColumn() : 0;
  }
  
  // Get total buyers
  if ($buyerRoleId) {
    $totalBuyersStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
    $totalBuyersStmt->execute([$buyerRoleId]);
    $totalBuyers = $totalBuyersStmt->fetchColumn();
  } else {
    $totalBuyersStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer'");
    $totalBuyers = $totalBuyersStmt ? $totalBuyersStmt->fetchColumn() : 0;
  }
} catch (Exception $e) {
  error_log("Dashboard stats error: " . $e->getMessage());
  // Use default values of 0
}
?>

<div class="welcome-banner text-center">
  <h1 class="display-5 fw-bold mb-3">Welcome, Admin!</h1>
  <p class="lead mb-0">Manage your Agrisea Marketplace platform</p>
</div>

<div class="row mb-4">
  <div class="col-md-3 mb-4">
    <div class="card h-100 border-success">
      <div class="card-body text-center">
        <h5 class="card-title text-success">Total Products</h5>
        <h2 class="display-6 fw-bold text-success"><?php echo $totalProducts; ?></h2>
        <p class="card-text">Active listings</p>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-4">
    <div class="card h-100 border-info">
      <div class="card-body text-center">
        <h5 class="card-title text-info">Total Orders</h5>
        <h2 class="display-6 fw-bold text-info"><?php echo $totalOrders; ?></h2>
        <p class="card-text">Processed orders</p>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-4">
    <div class="card h-100 border-warning">
      <div class="card-body text-center">
        <h5 class="card-title text-warning">Total Revenue</h5>
        <h2 class="display-6 fw-bold text-warning">₱<?php echo number_format($totalRevenue, 2); ?></h2>
        <p class="card-text">From paid orders</p>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-4">
    <div class="card h-100 border-primary">
      <div class="card-body text-center">
        <h5 class="card-title text-primary">Total Users</h5>
        <h2 class="display-6 fw-bold text-primary"><?php echo ($totalSellers + $totalBuyers); ?></h2>
        <p class="card-text">Registered accounts</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-8 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h5 class="mb-0">Recent Orders</h5>
      </div>
      <div class="card-body">
        <?php if (empty($recentOrders)): ?>
          <p class="text-muted">No recent orders found.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover recent-orders-table">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th>Total</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentOrders as $order): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                    <td>₱<?php echo number_format($order['total'], 2); ?></td>
                    <td>
                      <span class="badge bg-<?php 
                        echo $order['status'] === 'delivered' ? 'success' : 
                             ($order['status'] === 'processing' ? 'warning' : 
                             ($order['status'] === 'shipped' ? 'info' : 'secondary')); 
                      ?>">
                        <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-4 mb-4">
    <div class="card shadow-sm">
      <div class="card-header bg-white">
        <h5 class="mb-0">Quick Actions</h5>
      </div>
      <div class="card-body">
        <div class="quick-actions">
          <div class="action-btn" onclick="loadContent('addProduct'); return false;">
            <i class="fas fa-plus-circle"></i>
            <div>Add Product</div>
          </div>
          <div class="action-btn" onclick="loadContent('accounts'); return false;">
            <i class="fas fa-users"></i>
            <div>Manage Users</div>
          </div>
          <div class="action-btn" onclick="loadContent('analytics'); return false;">
            <i class="fas fa-chart-line"></i>
            <div>Analytics</div>
          </div>
          <div class="action-btn" onclick="loadContent('reports'); return false;">
            <i class="fas fa-file-alt"></i>
            <div>Reports</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>