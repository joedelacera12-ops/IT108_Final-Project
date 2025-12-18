<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/messages.php';

require_role('admin');
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

// Get all sellers with their types
if ($sellerRoleId) {
  $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, seller_type FROM users WHERE role_id = ? ORDER BY first_name, last_name");
  $stmt->execute([$sellerRoleId]);
  $sellers = $stmt->fetchAll();
} else {
  $stmt = $pdo->query("SELECT id, first_name, last_name, email, phone, seller_type FROM users WHERE role = 'seller' ORDER BY first_name, last_name");
  $sellers = $stmt->fetchAll();
}

// Get all delivery partners
$deliveryPartners = [];
if ($deliveryPartnerRoleId) {
  $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE role_id = ? ORDER BY first_name, last_name");
  $stmt->execute([$deliveryPartnerRoleId]);
  $deliveryPartners = $stmt->fetchAll();
} else {
  $stmt = $pdo->query("SELECT id, first_name, last_name, email, phone FROM users WHERE role = 'delivery_partner' ORDER BY first_name, last_name");
  $deliveryPartners = $stmt->fetchAll();
}

// Merge sellers and delivery partners for dropdown
$allSellersAndPartners = array_merge($sellers, $deliveryPartners);

// Convert sellers to JSON for JavaScript
$sellersJson = json_encode($sellers);

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

// Seller and Delivery Partner Performance
$sellerPerformance = [];
$deliveryPartnerPerformance = [];
try {
    // Top selling sellers
    $stmt = $pdo->query("
        SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as name, SUM(o.total) as total_revenue
        FROM orders o
        JOIN users u ON o.seller_id = u.id
        WHERE o.payment_status = 'paid'
        GROUP BY u.id, name
        ORDER BY total_revenue DESC
        LIMIT 5
    ");
    $sellerPerformance['top_sellers'] = $stmt->fetchAll();

    // Seller ratings
    $stmt = $pdo->query("
        SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as name, AVG(sr.rating) as avg_rating
        FROM seller_ratings sr
        JOIN users u ON sr.seller_id = u.id
        GROUP BY u.id, name
        ORDER BY avg_rating DESC
        LIMIT 5
    ");
    $sellerPerformance['top_rated_sellers'] = $stmt->fetchAll();

    // Delivery partner performance
    $stmt = $pdo->query("
        SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as name, COUNT(od.id) as total_deliveries
        FROM order_deliveries od
        JOIN users u ON od.delivery_partner_id = u.id
        WHERE od.status = 'delivered'
        GROUP BY u.id, name
        ORDER BY total_deliveries DESC
        LIMIT 5
    ");
    $deliveryPartnerPerformance['top_partners'] = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Performance stats error: " . $e->getMessage());
}


// Handle user deletion/deactivation if requested
if (isset($_POST['action']) && isset($_POST['user_id'])) {
  $action = $_POST['action'];
  $userId = (int)$_POST['user_id'];
  
  // Prevent modification of the main admin account
  $adminEmail = 'admin@agrisea.local';
  $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
  $stmt->execute([$userId]);
  $user = $stmt->fetch();
  
  if ($user && $user['email'] === $adminEmail) {
    $message = "The main admin account cannot be modified.";
    $messageType = "danger";
  } else {
  
  if ($action === 'delete') {
    try {
      // Begin transaction for complete user deletion
      $pdo->beginTransaction();
      
      // Delete related data first
      $pdo->prepare("DELETE FROM user_addresses WHERE user_id = ?")->execute([$userId]);
      $pdo->prepare("DELETE FROM user_security_questions WHERE user_id = ?")->execute([$userId]);
      $pdo->prepare("DELETE FROM business_profiles WHERE user_id = ?")->execute([$userId]);
      $pdo->prepare("DELETE FROM farmer_profiles WHERE user_id = ?")->execute([$userId]);
      $pdo->prepare("DELETE FROM fisher_profiles WHERE user_id = ?")->execute([$userId]);
      $pdo->prepare("DELETE FROM favorites WHERE user_id = ?")->execute([$userId]);
      $pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?")->execute([$userId, $userId]);
      $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$userId]);
      $pdo->prepare("DELETE FROM carts WHERE user_id = ?")->execute([$userId]);
      
      // Delete user last
      $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
      
      $pdo->commit();
      $message = "User deleted successfully.";
      $messageType = "success";
    } catch (Exception $e) {
      $pdo->rollback();
      $message = "Error deleting user: " . $e->getMessage();
      $messageType = "danger";
    }
  } elseif ($action === 'deactivate') {
    try {
      // Deactivate user by setting status to 'suspended'
      // Check if is_active column exists
      $hasIsActive = false;
      try {
        $checkStmt = $pdo->prepare("SELECT is_active FROM users LIMIT 1");
        $checkStmt->execute();
        $hasIsActive = true;
      } catch (Exception $e) {
        // Column doesn't exist
        $hasIsActive = false;
      }
      
      if ($hasIsActive) {
        $pdo->prepare("UPDATE users SET is_active = 0, status = 'suspended' WHERE id = ?")->execute([$userId]);
      } else {
        $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?")->execute([$userId]);
      }
      $message = "User deactivated successfully.";
      $messageType = "success";
    } catch (Exception $e) {
      $message = "Error deactivating user: " . $e->getMessage();
      $messageType = "danger";
    }
  } elseif ($action === 'activate') {
    try {
      // Activate user by setting status back to 'approved'
      // Check if is_active column exists
      $hasIsActive = false;
      try {
        $checkStmt = $pdo->prepare("SELECT is_active FROM users LIMIT 1");
        $checkStmt->execute();
        $hasIsActive = true;
      } catch (Exception $e) {
        // Column doesn't exist
        $hasIsActive = false;
      }
      
      if ($hasIsActive) {
        $pdo->prepare("UPDATE users SET is_active = 1, status = 'approved' WHERE id = ?")->execute([$userId]);
      } else {
        $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ?")->execute([$userId]);
      }
      $message = "User activated successfully.";
      $messageType = "success";
    } catch (Exception $e) {
      $message = "Error activating user: " . $e->getMessage();
      $messageType = "danger";
    }
  } elseif ($action === 'approve') {
    try {
      // Approve seller account - set status to 'approved'
      // Check if is_active column exists
      $hasIsActive = false;
      try {
        $checkStmt = $pdo->prepare("SELECT is_active FROM users LIMIT 1");
        $checkStmt->execute();
        $hasIsActive = true;
      } catch (Exception $e) {
        // Column doesn't exist
        $hasIsActive = false;
      }
      
      if ($hasIsActive) {
        $pdo->prepare("UPDATE users SET status = 'approved', is_active = 1 WHERE id = ?")->execute([$userId]);
      } else {
        $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ?")->execute([$userId]);
      }
      $message = "Seller approved successfully.";
      $messageType = "success";
    } catch (Exception $e) {
      $message = "Error approving seller: " . $e->getMessage();
      $messageType = "danger";
    }
  } elseif ($action === 'reject') {
    try {
      // Reject seller account - set status to 'rejected' and deactivate
      // Check if is_active column exists
      $hasIsActive = false;
      try {
        $checkStmt = $pdo->prepare("SELECT is_active FROM users LIMIT 1");
        $checkStmt->execute();
        $hasIsActive = true;
      } catch (Exception $e) {
        // Column doesn't exist
        $hasIsActive = false;
      }
      
      if ($hasIsActive) {
        $pdo->prepare("UPDATE users SET status = 'rejected', is_active = 0 WHERE id = ?")->execute([$userId]);
      } else {
        $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ?")->execute([$userId]);
      }
      $message = "Seller rejected successfully.";
      $messageType = "success";
    } catch (Exception $e) {
      $message = "Error rejecting seller: " . $e->getMessage();
      $messageType = "danger";
    }
  }
  }
}

// Get filter parameters
$filterRole = $_GET['role'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Get all users for accounts management with proper role resolution and filtering
$allUsers = [];
try {
  // Get all user roles for mapping
  $rolesMap = [];
  $rolesStmt = $pdo->query("SELECT id, name FROM user_roles");
  while ($role = $rolesStmt->fetch()) {
    $rolesMap[$role['id']] = $role['name'];
  }
  
  // Build dynamic query based on filters
  $sql = "SELECT u.*, 
                 ur.name as role_name,
                 u.created_at as registration_date,
                 u.seller_type
          FROM users u 
          LEFT JOIN user_roles ur ON u.role_id = ur.id";
  
  $params = [];
  $conditions = [];
  
  // Add search filter
  if (!empty($searchQuery)) {
    $conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR ur.name LIKE ? OR u.status LIKE ?)";
    $params = array_merge($params, ["%$searchQuery%", "%$searchQuery%", "%$searchQuery%", "%$searchQuery%", "%$searchQuery%", "%$searchQuery%"]);
  }
  
  // Add role filter
  if ($filterRole !== 'all') {
    $conditions[] = "ur.name = ?";
    $params[] = $filterRole;
  }
  
  // Add status filter
  if ($filterStatus !== 'all') {
    if ($filterStatus === 'pending') {
      $conditions[] = "u.status = 'pending'";
    } elseif ($filterStatus === 'active') {
      // Check if is_active column exists
      $hasIsActive = false;
      try {
        $checkStmt = $pdo->prepare("SELECT is_active FROM users LIMIT 1");
        $checkStmt->execute();
        $hasIsActive = true;
      } catch (Exception $e) {
        // Column doesn't exist
        $hasIsActive = false;
      }
      
      if ($hasIsActive) {
        $conditions[] = "u.status = 'approved' AND u.is_active = 1";
      } else {
        $conditions[] = "u.status = 'approved'";
      }
    } elseif ($filterStatus === 'inactive') {
      // Check if is_active column exists
      $hasIsActive = false;
      try {
        $checkStmt = $pdo->prepare("SELECT is_active FROM users LIMIT 1");
        $checkStmt->execute();
        $hasIsActive = true;
      } catch (Exception $e) {
        // Column doesn't exist
        $hasIsActive = false;
      }
      
      if ($hasIsActive) {
        $conditions[] = "(u.status != 'pending' AND u.is_active = 0) OR u.status IN ('rejected', 'suspended')";
      } else {
        $conditions[] = "u.status IN ('rejected', 'suspended')";
      }
    }
  }
  
  // Combine conditions
  if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
  }
  
  $sql .= " ORDER BY u.created_at DESC";
  
  // Execute query
  $usersStmt = $pdo->prepare($sql);
  $usersStmt->execute($params);
  
  $allUsers = $usersStmt ? $usersStmt->fetchAll() : [];
    
    // Debug: Show what we fetched
    /*
    echo "<pre>";
    print_r(array_slice($allUsers, 0, 2)); // Show first 2 users
    echo "</pre>";
    */
  
  // Ensure each user has proper role name
  foreach ($allUsers as &$user) {
    if (empty($user['role_name']) && !empty($user['role'])) {
      // Fallback to legacy role column
      $user['role_name'] = $user['role'];
    } elseif (!empty($user['role_id']) && isset($rolesMap[$user['role_id']])) {
      // Map from role_id
      $user['role_name'] = $rolesMap[$user['role_id']];
    } elseif (empty($user['role_name'])) {
      $user['role_name'] = 'unknown';
    }
  }
  unset($user); // Break the reference
} catch (Exception $e) {
  error_log("Error fetching users: " . $e->getMessage());
  $allUsers = [];
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard - Agrisea Marketplace</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="/ecommerce_farmers_fishers/assets/css/unified_light_theme.css" rel="stylesheet">
  <style>
    /* Admin Dashboard - Unified Light Theme */
    .admin-content {
      background-color: var(--light-gray);
    }
    
    .status-pending{color:orange}
    .status-approved{color:green}
    .status-rejected{color:red}
    
    /* Dashboard visual panels: bound calendar and chart heights */
    .dashboard-visual { display:block; }
    .chart-panel, .calendar-panel { max-height:420px; height:320px; overflow:auto; }
    .chart-panel canvas { width:100% !important; height:100% !important; }
    @media(max-width:991px) { .chart-panel, .calendar-panel { height:260px; max-height:340px; } }
    
    /* Seller dropdown styles */
    .seller-option {
      padding: 8px 12px;
      cursor: pointer;
      border-bottom: 1px solid #eee;
    }
    .seller-option:hover {
      background-color: #E8F5E9;
    }
    .seller-option:last-child {
      border-bottom: none;
    }
    .highlight {
      background-color: #0d6efd;
      color: white;
    }
    
    /* Enhanced dashboard styles */
    .stat-card {
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(76, 175, 80, 0.15);
      transition: all 0.3s ease;
      border: 1px solid #e0e0e0;
      position: relative;
      overflow: hidden;
      background: #ffffff;
      padding: 1.5rem;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 30px rgba(76, 175, 80, 0.25);
      border-color: #d0d0d0;
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 5px;
      height: 100%;
      background: linear-gradient(to bottom, #4CAF50, #45a049);
    }
    
    .stat-card.bg-primary::before {
      background: linear-gradient(to bottom, #4CAF50, #45a049);
    }
    
    .stat-card.bg-success::before {
      background: linear-gradient(to bottom, #4CAF50, #45a049);
    }
    
    .stat-card.bg-warning::before {
      background: linear-gradient(to bottom, #FFC107, #ffa000);
    }
    
    .stat-card.bg-info::before {
      background: linear-gradient(to bottom, #2196F3, #1976D2);
    }
    
    .stat-card.bg-danger::before {
      background: linear-gradient(to bottom, #F44336, #D32F2F);
    }
    
    .icon-wrapper {
      width: 60px;
      height: 60px;
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      margin-bottom: 15px;
      background: #E8F5E9;
      color: #4CAF50;
      opacity: 1; /* Make icons solid */
      font-weight: 900; /* Make icons bold */
    }
    
    .bg-primary-light {
      background-color: rgba(76, 175, 80, 0.1);
      color: #4CAF50;
    }
    
    .bg-success-light {
      background-color: rgba(76, 175, 80, 0.1);
      color: #4CAF50;
    }
    
    .bg-warning-light {
      background-color: rgba(255, 193, 7, 0.1);
      color: #FFC107;
    }
    
    .bg-info-light {
      background-color: rgba(33, 150, 243, 0.1);
      color: #2196F3;
    }
    
    .bg-danger-light {
      background-color: rgba(244, 67, 54, 0.1);
      color: #F44336;
    }
    
    .recent-orders-table th {
      font-weight: 600;
      color: #4CAF50;
      background-color: #E8F5E9;
    }
    
    .activity-timeline {
      position: relative;
      padding-left: 30px;
    }
    
    .activity-timeline::before {
      content: '';
      position: absolute;
      left: 15px;
      top: 0;
      bottom: 0;
      width: 2px;
      background: #C8E6C9;
    }
    
    .activity-item {
      position: relative;
      margin-bottom: 20px;
    }
    
    .activity-item::before {
      content: '';
      position: absolute;
      left: -24px;
      top: 8px;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #4CAF50;
    }
    
    .welcome-banner {
      background: linear-gradient(120deg, #4CAF50, #388E3C);
      color: white;
      border-radius: 15px;
      padding: 30px;
      margin-bottom: 25px;
      box-shadow: 0 4px 20px rgba(76, 175, 80, 0.25);
    }
    
    .quick-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 20px;
    }
    
    .action-btn {
      flex: 1;
      min-width: 120px;
      text-align: center;
      padding: 20px 15px;
      border-radius: 12px;
      background: #ffffff;
      box-shadow: 0 4px 12px rgba(76, 175, 80, 0.1);
      transition: all 0.3s ease;
      border: 1px solid #e0e0e0;
    }
    
    .action-btn:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 16px rgba(76, 175, 80, 0.2);
      border-color: #4CAF50;
      background: #E8F5E9;
    }
    
    .action-btn i {
      font-size: 2rem;
      margin-bottom: 12px;
      display: block;
      color: #4CAF50;
    }
    
    /* Enhanced seller dropdown with filtering */
    .seller-dropdown-container {
      position: relative;
    }
    
    .seller-dropdown-menu {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      z-index: 1000;
      background: white;
      border: 1px solid #e0e0e0;
      border-radius: 0.5rem;
      box-shadow: 0 0.5rem 1rem rgba(76, 175, 80, 0.15);
      max-height: 200px;
      overflow-y: auto;
      display: none;
    }
    
    .seller-dropdown-menu.show {
      display: block;
    }
    
    .seller-dropdown-item {
      padding: 0.75rem 1.25rem;
      cursor: pointer;
      border-bottom: 1px solid #eee;
    }
    
    .seller-dropdown-item:hover {
      background-color: #E8F5E9;
    }
    
    .seller-dropdown-item:last-child {
      border-bottom: none;
    }
    
    .seller-type-badge {
      font-size: 0.75rem;
      padding: 0.25em 0.5em;
      border-radius: 0.25rem;
      background-color: #E8F5E9;
      color: #4CAF50;
    }
    
    /* User status badges */
    .status-badge {
      font-size: 0.75em;
      padding: 0.25em 0.5em;
      border-radius: 0.25rem;
    }
    
    /* Admin-specific enhancements */
    .admin-sidebar {
      background: var(--white);
      box-shadow: 0 0 15px rgba(76, 175, 80, 0.1);
    }
    
    .admin-content {
      background-color: var(--light-gray);
    }
  </style>
</head>
<body>
  <div class="container-fluid p-0" style="background:#f5f5f5; min-height:100vh;">
    <div class="row g-0">
      <!-- Main Content (Full Width since sidebar is removed) -->
      <div class="col-12">
        <!-- Unified Navigation -->
        <?php include __DIR__ . '/../includes/unified_navbar.php';
        ?>

    <?php $currentTab = $_GET['tab'] ?? 'overview'; ?>
    <?php if ($currentTab === 'marketplace'): ?>
      <?php include __DIR__ . '/../includes/marketplace.php'; ?>
    <?php elseif ($currentTab === 'addProduct'): ?>
      <div class="row mt-4">
        <div class="col-12">
          <div class="card p-3 shadow-sm">
            <h5 class="mb-3">Add Product</h5>
            <form id="adminProductForm" onsubmit="return adminHandleProductSubmit(event)">
              <?php include __DIR__ . '/add-product-form-fields.php'; ?>
              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="button" class="btn btn-outline-secondary btn-lg me-2" onclick="adminSaveDraft()">
                  <i class="fas fa-save me-2"></i>Save Draft
                </button>
                <button type="submit" class="btn btn-success btn-lg">
                  <i class="fas fa-plus me-2"></i>List Product
                </button>
              </div>
              <div class="mt-3">
                <span id="adminProductMsg" class="text-success"></span>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <script>
      // Category options based on product type
      const categories = {
        farmer: {
          'dairy': 'Dairy Products',
          'poultry': 'Poultry & Eggs',
          'herbs': 'Herbs & Spices',
          'other': 'Other'
        },
        fisher: {
          'seafood': 'Seafood',
          'fresh_fish': 'Fresh Fish',
          'shellfish': 'Shellfish',
          'crustaceans': 'Crustaceans',
          'seaweed': 'Seaweed',
          'processed_seafood': 'Processed Seafood',
          'other': 'Other'
        }
      };
      
      // Store sellers and delivery partners data from PHP
      const sellersData = <?php echo json_encode($allSellersAndPartners); ?>;
      
      // Initialize seller search functionality
      document.addEventListener('DOMContentLoaded', function() {
        const productTypeSelect = document.getElementById('productType');
        const sellerSearch = document.getElementById('sellerSearch');
        const sellerDropdown = document.getElementById('sellerDropdown');
        const sellerIdInput = document.getElementById('seller_id');
        const sellerEmail = document.getElementById('sellerEmail');
        const sellerPhone = document.getElementById('sellerPhone');
        
        let currentIndex = -1;
        let filteredSellers = [];
        
        // Filter sellers based on product type
        productTypeSelect.addEventListener('change', function() {
          const selectedType = this.value;
          sellerSearch.value = '';
          sellerIdInput.value = '';
          sellerEmail.value = '';
          sellerPhone.value = '';
          sellerDropdown.style.display = 'none';
          currentIndex = -1;
          
          // Filter sellers based on product type
          if (selectedType === 'farmer') {
            filteredSellers = sellersData.filter(seller => seller.seller_type === 'farmer');
          } else if (selectedType === 'fisher') {
            filteredSellers = sellersData.filter(seller => seller.seller_type === 'fisher');
          } else {
            filteredSellers = sellersData;
          }
        });
        
        // Filter sellers based on search input
        sellerSearch.addEventListener('input', function() {
          const searchTerm = this.value.toLowerCase();
          const selectedType = productTypeSelect.value;
          
          if (searchTerm.length === 0) {
            sellerDropdown.style.display = 'none';
            currentIndex = -1;
            return;
          }
          
          // Filter sellers based on both product type and search term
          let sellersToShow = filteredSellers.length > 0 ? filteredSellers : sellersData;
          
          const searchFilteredSellers = sellersToShow.filter(seller => 
            seller.first_name.toLowerCase().includes(searchTerm) ||
            seller.last_name.toLowerCase().includes(searchTerm) ||
            seller.email.toLowerCase().includes(searchTerm)
          );
          
          if (searchFilteredSellers.length === 0) {
            sellerDropdown.innerHTML = '<div class="seller-option">No sellers found</div>';
            sellerDropdown.style.display = 'block';
            currentIndex = -1;
            return;
          }
          
          let dropdownHTML = '';
          searchFilteredSellers.forEach((seller, index) => {
            const sellerType = seller.seller_type || 'unknown';
            const typeBadge = sellerType === 'farmer' ? 
              '<span class="badge bg-success ms-2">Farmer</span>' : 
              sellerType === 'fisher' ? 
              '<span class="badge bg-info ms-2">Fisher</span>' : 
              '<span class="badge bg-secondary ms-2">Unknown</span>';
            
            dropdownHTML += `<div class="seller-option" data-id="${seller.id}" data-email="${seller.email}" data-phone="${seller.phone || ''}">
              ${seller.first_name} ${seller.last_name} (${seller.email}) ${typeBadge}
            </div>`;
          });
          
          sellerDropdown.innerHTML = dropdownHTML;
          sellerDropdown.style.display = 'block';
          currentIndex = -1;
        });
        
        // Handle keyboard navigation
        sellerSearch.addEventListener('keydown', function(e) {
          const options = sellerDropdown.querySelectorAll('.seller-option');
          
          if (e.key === 'ArrowDown') {
            e.preventDefault();
            currentIndex = Math.min(currentIndex + 1, options.length - 1);
            updateHighlight(options);
          } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentIndex = Math.max(currentIndex - 1, -1);
            updateHighlight(options);
          } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentIndex >= 0 && options[currentIndex]) {
              selectSeller(options[currentIndex]);
            }
          } else if (e.key === 'Escape') {
            sellerDropdown.style.display = 'none';
            currentIndex = -1;
          }
        });
        
        // Handle click on seller option
        sellerDropdown.addEventListener('click', function(e) {
          const option = e.target.closest('.seller-option');
          if (option) {
            selectSeller(option);
          }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
          if (!sellerSearch.contains(e.target) && !sellerDropdown.contains(e.target)) {
            sellerDropdown.style.display = 'none';
            currentIndex = -1;
          }
        });
        
        // Helper function to update highlighted option
        function updateHighlight(options) {
          options.forEach((option, index) => {
            if (index === currentIndex) {
              option.classList.add('highlight');
            } else {
              option.classList.remove('highlight');
            }
          });
        }
        
        // Helper function to select a seller
        function selectSeller(option) {
          const sellerId = option.getAttribute('data-id');
          const sellerEmailValue = option.getAttribute('data-email');
          const sellerPhoneValue = option.getAttribute('data-phone');
          
          sellerSearch.value = option.textContent.trim().replace(/\s*<.*?>.*?<.*?>\s*/g, '');
          sellerIdInput.value = sellerId;
          sellerEmail.value = sellerEmailValue;
          sellerPhone.value = sellerPhoneValue;
          
          sellerDropdown.style.display = 'none';
          currentIndex = -1;
        }
      });
      
      // Update category dropdown when product type changes
      // Modified to not reset category selection and preserve existing values
      document.getElementById('productType').addEventListener('change', function() {
        const categorySelect = document.getElementById('productCategory');
        const categoryField = document.getElementById('categoryField');
        const selectedType = this.value;
        
        // Don't clear existing options, just update based on selection
        if (selectedType === '') {
          // If no type selected, show placeholder
          const placeholder = document.createElement('option');
          placeholder.value = '';
          placeholder.textContent = 'Select Product Type First';
          
          // Clear and add placeholder only
          categorySelect.innerHTML = '';
          categorySelect.appendChild(placeholder);
          // Show category field for admin
          if (categoryField) categoryField.style.display = 'block';
        } else {
          // For sellers, automatically assign category and hide dropdown
          if (selectedType === 'farmer' || selectedType === 'fisher') {
            // Hide category field for sellers
            if (categoryField) categoryField.style.display = 'none';
            
            // Set hidden autoCategory field
            const autoCategoryField = document.getElementById('autoCategory');
            if (autoCategoryField) {
              // Automatically assign the correct category based on seller type
              autoCategoryField.value = selectedType === 'farmer' ? 'vegetables' : 'seafood';
            }
          } else {
            // Show category field for other types (admin)
            if (categoryField) categoryField.style.display = 'block';
            
            // Preserve existing selection if it's still valid for this type
            const currentValue = categorySelect.value;
            
            // Clear options except the current one if it's valid
            categorySelect.innerHTML = '';
            
            // Add default option
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select Category';
            categorySelect.appendChild(defaultOption);
            
            // Add category options based on selected type
            const typeCategories = categories[selectedType];
            let validCurrent = false;
            for (const [value, label] of Object.entries(typeCategories)) {
              const option = document.createElement('option');
              option.value = value;
              option.textContent = label;
              if (value === currentValue) {
                option.selected = true;
                validCurrent = true;
              }
              categorySelect.appendChild(option);
            }
            
            // If current value wasn't valid for this type, keep it selected but add it as an option
            if (currentValue && !validCurrent) {
              const currentOption = document.createElement('option');
              currentOption.value = currentValue;
              currentOption.textContent = currentValue;
              currentOption.selected = true;
              categorySelect.appendChild(currentOption);
            }
          }
        }
      });
      
      // Admin form submission handler
      function adminHandleProductSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const msgEl = document.getElementById('adminProductMsg');
        
        // Validate required fields
        if (!document.getElementById('seller_id').value) {
          msgEl.textContent = 'Please select a seller';
          msgEl.className = 'text-danger';
          return false;
        }
        
        // Disable submit button during processing
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';
        submitBtn.disabled = true;
        
        fetch('/ecommerce_farmers_fishers/admin/add_product.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            msgEl.textContent = 'Product added successfully!';
            msgEl.className = 'text-success';
            form.reset();
            // Reset the email and phone fields
            document.getElementById('sellerEmail').value = '';
            document.getElementById('sellerPhone').value = '';
            document.getElementById('sellerSearch').value = '';
            document.getElementById('seller_id').value = '';
            // Reset product type to trigger category reset
            document.getElementById('productType').value = '';
            document.getElementById('productType').dispatchEvent(new Event('change'));
          } else {
            msgEl.textContent = data.error || 'Failed to add product';
            msgEl.className = 'text-danger';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          msgEl.textContent = 'An error occurred while adding the product';
          msgEl.className = 'text-danger';
        })
        .finally(() => {
          // Re-enable submit button
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        });
        
        return false;
      }
      
      function adminSaveDraft() {
        document.getElementById('adminProductMsg').textContent = 'Draft saving not implemented yet.';
      }
      </script>


    <?php elseif ($currentTab === 'accounts'): ?>
      <div class="row mt-4">
        <div class="col-12">
          <div class="card p-3 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h5 class="mb-0">Manage Accounts</h5>
              <div>
                <!-- Filter and Search Form -->
                <form method="GET" class="d-inline-block me-3">
                  <input type="hidden" name="tab" value="accounts">
                  <div class="input-group input-group-sm" style="width: 300px;">
                    <input type="text" class="form-control" placeholder="Search by name, email, phone..." name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                      <i class="fas fa-search"></i>
                    </button>
                  </div>
                </form>
                
                <!-- Role Filter Dropdown -->
                <form method="GET" class="d-inline-block me-2">
                  <input type="hidden" name="tab" value="accounts">
                  <?php if (!empty($searchQuery)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                  <?php endif; ?>
                  <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?php echo $filterRole === 'all' ? 'selected' : ''; ?>>All Roles</option>
                    <option value="admin" <?php echo $filterRole === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="seller" <?php echo $filterRole === 'seller' ? 'selected' : ''; ?>>Seller</option>
                    <option value="buyer" <?php echo $filterRole === 'buyer' ? 'selected' : ''; ?>>Customer</option>
                    <option value="delivery_partner" <?php echo $filterRole === 'delivery_partner' ? 'selected' : ''; ?>>Delivery Partner</option>
                  </select>
                </form>
                
                <!-- Status Filter Dropdown -->
                <form method="GET" class="d-inline-block me-2">
                  <input type="hidden" name="tab" value="accounts">
                  <?php if (!empty($searchQuery)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
                  <?php endif; ?>
                  <?php if ($filterRole !== 'all'): ?>
                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($filterRole); ?>">
                  <?php endif; ?>
                  <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filterStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                  </select>
                </form>
                
                <!-- Reset Filters Button -->
                <a href="?tab=accounts" class="btn btn-outline-primary btn-sm">
                  <i class="fas fa-sync-alt me-1"></i>Reset
                </a>
              </div>
            </div>
            
            <?php if (isset($message)): ?>
              <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            <?php endif; ?>
            
            <div class="table-responsive">
              <table class="table table-hover" id="usersTable">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Type</th>
                    <th>Registration Date</th>
                    <th>Last Login</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($allUsers as $user): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($user['id']); ?></td>
                      <td><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                      <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                      <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                      <td>
                        <span class="badge bg-<?php 
                          echo ($user['role_name'] ?? '') === 'admin' ? 'primary' : 
                               (($user['role_name'] ?? '') === 'seller' ? 'success' : 
                               (($user['role_name'] ?? '') === 'buyer' ? 'info' : 'secondary'));
                        ?>">
                          <?php echo ucfirst(htmlspecialchars($user['role_name'] ?? 'unknown')); ?>
                        </span>
                      </td>
                      <td>
                        <?php if (($user['role_name'] ?? '') === 'seller'): ?>
                          <?php 
                          // Get seller type from the seller_type column
                          $sellerType = $user['seller_type'] ?? '';
                          
                          // Determine badge class based on seller type
                          $badgeClass = 'secondary';
                          if ($sellerType === 'farmer') {
                            $badgeClass = 'success';
                          } elseif ($sellerType === 'fisher') {
                            $badgeClass = 'info';
                          } elseif (in_array($sellerType, ['retailer', 'restaurant'])) {
                            $badgeClass = 'warning';
                          }
                          ?>
                          <span class="badge bg-<?php echo $badgeClass; ?>">
                            <?php echo !empty($sellerType) ? ucfirst(htmlspecialchars($sellerType)) : 'N/A'; ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted">N/A</span>
                        <?php endif; ?>
                      </td>
                      <td><?php echo !empty($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : 'N/A'; ?></td>
                      <td><?php echo !empty($user['last_login']) ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?></td>
                      <td>
                        <?php 
                        $status = $user['status'] ?? 'unknown';
                        $isActive = $user['is_active'] ?? 1;
                        $statusClass = '';
                        $statusText = '';
                        
                        if ($status === 'pending') {
                          $statusClass = 'bg-warning';
                          $statusText = 'Pending';
                        } elseif ($status === 'approved' && $isActive) {
                          $statusClass = 'bg-success';
                          $statusText = 'Active';
                        } elseif ($status === 'approved' && !$isActive) {
                          $statusClass = 'bg-secondary';
                          $statusText = 'Deactivated';
                        } elseif ($status === 'rejected') {
                          $statusClass = 'bg-danger';
                          $statusText = 'Rejected';
                        } elseif ($status === 'suspended') {
                          $statusClass = 'bg-secondary';
                          $statusText = 'Suspended';
                        } else {
                          $statusClass = 'bg-secondary';
                          $statusText = ucfirst($status);
                        }
                        ?>
                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                      </td>
                      <td>
                        <div class="btn-group btn-group-sm" role="group">
                          <?php if (($user['role_name'] ?? '') === 'seller' || ($user['role_name'] ?? '') === 'delivery_partner'): ?>
                            <?php if (($user['status'] ?? '') === 'pending'): ?>
                              <!-- Approve/Reject buttons for pending sellers and delivery partners -->
                              <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-outline-success" title="Approve <?php echo ucfirst($user['role_name'] ?? 'user'); ?>">
                                  <i class="fas fa-check"></i>
                                </button>
                              </form>
                              <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-outline-danger" title="Reject <?php echo ucfirst($user['role_name'] ?? 'user'); ?>" onclick="return confirm('Are you sure you want to reject this <?php echo $user['role_name'] ?? 'user'; ?>?')">
                                  <i class="fas fa-times"></i>
                                </button>
                              </form>
                            <?php else: ?>
                              <!-- Deactivate/Activate buttons for approved sellers and delivery partners -->
                              <?php if ($isActive): ?>
                                <form method="POST" style="display: inline;">
                                  <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                  <input type="hidden" name="action" value="deactivate">
                                  <button type="submit" class="btn btn-outline-warning" title="Deactivate" onclick="return confirm('Are you sure you want to deactivate this <?php echo $user['role_name'] ?? 'user'; ?>?')">
                                    <i class="fas fa-pause"></i>
                                  </button>
                                </form>
                              <?php else: ?>
                                <form method="POST" style="display: inline;">
                                  <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                  <input type="hidden" name="action" value="activate">
                                  <button type="submit" class="btn btn-outline-success" title="Activate">
                                    <i class="fas fa-play"></i>
                                  </button>
                                </form>
                              <?php endif; ?>
                            <?php endif; ?>
                          <?php elseif (($user['role_name'] ?? '') !== 'admin'): ?>
                            <!-- Standard activate/deactivate for non-admin users -->
                            <?php if ($isActive): ?>
                              <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="deactivate">
                                <button type="submit" class="btn btn-outline-warning" title="Deactivate" onclick="return confirm('Are you sure you want to deactivate this user?')">
                                  <i class="fas fa-pause"></i>
                                </button>
                              </form>
                            <?php else: ?>
                              <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="activate">
                                <button type="submit" class="btn btn-outline-success" title="Activate">
                                  <i class="fas fa-play"></i>
                                </button>
                              </form>
                            <?php endif; ?>
                          <?php endif; ?>
                          
                          <!-- Delete button for all non-admin users -->
                          <?php if (($user['role_name'] ?? '') !== 'admin'): ?>
                            <form method="POST" style="display: inline;">
                              <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                              <input type="hidden" name="action" value="delete">
                              <button type="submit" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                <i class="fas fa-trash"></i>
                              </button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      
      <script>
        // No client-side filtering needed since we're using server-side filtering
      </script>
    <?php elseif ($currentTab === 'analytics'): ?>
      <?php include __DIR__ . '/analytics_dashboard.php'; ?>
    <?php elseif ($currentTab === 'payments'): ?>
      <?php include __DIR__ . '/payments_dashboard.php'; ?>
    <?php elseif ($currentTab === 'contact_messages'): ?>
      <div class="row mt-4">
        <div class="col-12">
          <div class="card p-3 shadow-sm">
            <h5 class="mb-3">Contact Messages</h5>
            <?php 
            // Include only the content part, not the full HTML page
            ob_start();
            include __DIR__ . '/contact_messages.php';
            $content = ob_get_clean();
            // Extract only the content between body tags
            if (preg_match('/<body[^>]*>(.*?)<\/body>/s', $content, $matches)) {
                echo $matches[1];
            } else {
                // Fallback: include the file directly
                include __DIR__ . '/contact_messages.php';
            }
            ?>
          </div>
        </div>
      </div>
    <?php elseif ($currentTab === 'user_messages'): ?>
      <div class="row mt-4">
        <div class="col-12">
          <div class="card p-3 shadow-sm">
            <h5 class="mb-3">User Messages</h5>
            <?php 
            // Include only the content part, not the full HTML page
            ob_start();
            include __DIR__ . '/messages.php';
            $content = ob_get_clean();
            // Extract only the content between body tags
            if (preg_match('/<body[^>]*>(.*?)<\/body>/s', $content, $matches)) {
                echo $matches[1];
            } else {
                // Fallback: include the file directly
                include __DIR__ . '/messages.php';
            }
            ?>
          </div>
        </div>
      </div>
    <?php elseif ($currentTab === 'enhanced_analytics'): ?>
      <div class="row mt-4">
        <div class="col-12">
          <div class="card p-3 shadow-sm">
            <h5 class="mb-3">Enhanced Analytics</h5>
            <?php include __DIR__ . '/enhanced_analytics.php'; ?>
          </div>
        </div>
      </div>
    <?php elseif ($currentTab === 'ratings'): ?>
        <div class="row mt-4">
          <div class="col-12">
            <div class="card p-3 shadow-sm">
              <h5 class="mb-3">Seller Ratings</h5>
              <?php include __DIR__ . '/ratings.php'; ?>
            </div>
          </div>
        </div>
      <?php elseif ($currentTab === 'performance'): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card p-3 shadow-sm">
                    <h5 class="mb-3">Performance Dashboard</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Top Selling Sellers</h5>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($sellerPerformance['top_sellers'] as $seller): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($seller['name']); ?>
                                                <span class="badge bg-primary rounded-pill">₱<?php echo number_format($seller['total_revenue'], 2); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Top Rated Sellers</h5>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($sellerPerformance['top_rated_sellers'] as $seller): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($seller['name']); ?>
                                                <span class="badge bg-success rounded-pill"><?php echo number_format($seller['avg_rating'], 2); ?> ★</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Top Delivery Partners</h5>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($deliveryPartnerPerformance['top_partners'] as $partner): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?php echo htmlspecialchars($partner['name']); ?>
                                                <span class="badge bg-info rounded-pill"><?php echo $partner['total_deliveries']; ?> deliveries</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      <?php else: ?>
      <!-- Enhanced Overview Dashboard -->
      <div class="row mt-4">
        <div class="col-12">
          <!-- Welcome Banner -->
          <div class="welcome-banner">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h2><i class="fas fa-chart-line me-2"></i>Welcome back, Admin!</h2>
                <p class="mb-0">Here's what's happening with your marketplace today.</p>
              </div>
              <div class="col-md-4 text-md-end">
                <div class="d-inline-block bg-white bg-opacity-25 p-3 rounded">
                  <div class="fs-5"><?php echo date('l, F j, Y'); ?></div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Quick Stats -->
          <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
              <div class="card stat-card h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="text-muted mb-1">Total Revenue</h6>
                      <h3 class="mb-0 text-primary">₱<?php echo number_format($totalRevenue ?? 0, 2); ?></h3>
                      <small class="text-success"><i class="fas fa-arrow-up me-1"></i>12.5% from last month</small>
                    </div>
                    <div class="icon-wrapper bg-primary-light">
                      <i class="fas fa-money-bill-wave"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
              <div class="card stat-card h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="text-muted mb-1">Total Sellers</h6>
                      <h3 class="mb-0 text-success"><?php echo (int)($totalSellers ?? 0); ?></h3>
                      <small class="text-muted">Active / Pending</small>
                    </div>
                    <div class="icon-wrapper bg-success-light">
                      <i class="fas fa-users"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
              <div class="card stat-card h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="text-muted mb-1">Products</h6>
                      <h3 class="mb-0 text-warning"><?php echo (int)($totalProducts ?? 0); ?></h3>
                      <small class="text-muted">Total listings</small>
                    </div>
                    <div class="icon-wrapper bg-warning-light">
                      <i class="fas fa-box-open"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
              <div class="card stat-card h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="text-muted mb-1">Orders</h6>
                      <h3 class="mb-0 text-info"><?php echo (int)($totalOrders ?? 0); ?></h3>
                      <small class="text-muted">Total orders</small>
                    </div>
                    <div class="icon-wrapper bg-info-light">
                      <i class="fas fa-shopping-cart"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Quick Actions -->
          <div class="card mb-4">
            <div class="card-body">
              <h5 class="card-title mb-3"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
              <div class="quick-actions">
                <a href="/ecommerce_farmers_fishers/admin/dashboard.php?tab=addProduct" class="action-btn text-decoration-none text-dark">
                  <i class="fas fa-plus-circle text-primary"></i>
                  <div>Add Product</div>
                </a>
                <a href="/ecommerce_farmers_fishers/admin/dashboard.php?tab=products" class="action-btn text-decoration-none text-dark">
                  <i class="fas fa-box-open text-success"></i>
                  <div>Manage Products</div>
                </a>
                <a href="/ecommerce_farmers_fishers/admin/dashboard.php?tab=analytics" class="action-btn text-decoration-none text-dark">
                  <i class="fas fa-chart-bar text-warning"></i>
                  <div>Analytics</div>
                </a>
                <a href="/ecommerce_farmers_fishers/admin/dashboard.php?tab=messages" class="action-btn text-decoration-none text-dark">
                  <i class="fas fa-envelope text-info"></i>
                  <div>Messages</div>
                </a>
              </div>
            </div>
          </div>
          
          <!-- Recent Activity and Orders -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-body">
                  <h5 class="card-title mb-3"><i class="fas fa-bell me-2"></i>Recent Activity</h5>
                  <div class="activity-timeline">
                    <?php
                    // Fetch recent activity from database
                    $recentActivity = [];
                    try {
                      // Get recent user registrations
                      $recentUsersStmt = $pdo->query("SELECT first_name, last_name, created_at FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 3");
                      while ($user = $recentUsersStmt->fetch()) {
                        $recentActivity[] = [
                          'title' => 'New user registered',
                          'description' => htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')) . ' joined the platform',
                          'time' => $user['created_at']
                        ];
                      }
                      
                      // Get recent orders
                      $recentOrdersStmt = $pdo->query("SELECT o.id, u.first_name, u.last_name, o.created_at FROM orders o JOIN users u ON o.user_id = u.id WHERE o.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY o.created_at DESC LIMIT 3");
                      while ($order = $recentOrdersStmt->fetch()) {
                        $recentActivity[] = [
                          'title' => 'New order placed',
                          'description' => 'Order #' . $order['id'] . ' by ' . htmlspecialchars($order['first_name'] . ' ' . $order['last_name']),
                          'time' => $order['created_at']
                        ];
                      }
                      
                      // Sort by time
                      usort($recentActivity, function($a, $b) {
                        return strtotime($b['time']) - strtotime($a['time']);
                      });
                      
                      // Limit to 5 most recent
                      $recentActivity = array_slice($recentActivity, 0, 5);
                    } catch (Exception $e) {
                      // Fallback to static data if database query fails
                      $recentActivity = [
                        [
                          'title' => 'New seller registered',
                          'description' => 'John Farmer joined the platform',
                          'time' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                        ],
                        [
                          'title' => 'Product listed',
                          'description' => 'Fresh Organic Tomatoes added',
                          'time' => date('Y-m-d H:i:s', strtotime('-5 hours'))
                        ],
                        [
                          'title' => 'Order completed',
                          'description' => 'Order #12345 paid successfully',
                          'time' => date('Y-m-d H:i:s', strtotime('-1 day'))
                        ]
                      ];
                    }
                    
                    if (empty($recentActivity)) {
                      echo '<div class="activity-item"><div class="text-muted">No recent activity</div></div>';
                    } else {
                      foreach ($recentActivity as $activity) {
                        $timeAgo = '';
                        $activityTime = strtotime($activity['time']);
                        $currentTime = time();
                        $diff = $currentTime - $activityTime;
                        
                        if ($diff < 60) {
                          $timeAgo = 'Just now';
                        } elseif ($diff < 3600) {
                          $minutes = floor($diff / 60);
                          $timeAgo = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
                        } elseif ($diff < 86400) {
                          $hours = floor($diff / 3600);
                          $timeAgo = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                        } else {
                          $days = floor($diff / 86400);
                          $timeAgo = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
                        }
                        ?>
                        <div class="activity-item">
                          <div class="fw-bold"><?php echo htmlspecialchars($activity['title']); ?></div>
                          <div class="small text-muted"><?php echo htmlspecialchars($activity['description']); ?></div>
                          <div class="small text-muted"><?php echo $timeAgo; ?></div>
                        </div>
                        <?php
                      }
                    }
                    ?>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-lg-12">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title mb-3"><i class="fas fa-chart-pie me-2"></i>Platform Stats</h5>
                  <div class="d-flex justify-content-between mb-2">
                    <span>Buyers</span>
                    <span class="fw-bold"><?php echo (int)($totalBuyers ?? 0); ?></span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span>Recent Signups</span>
                    <span class="fw-bold"><?php
                      try {
                        $recentSignupsStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
                        $recentSignups = $recentSignupsStmt ? $recentSignupsStmt->fetchColumn() : 0;
                        echo (int)($recentSignups ?? 0);
                      } catch (Exception $e) {
                        echo '0';
                      }
                    ?></span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span>Unread Messages</span>
                    <span class="fw-bold"><?php
                      try {
                        $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
                        $unreadStmt->execute();
                        $unreadCount = $unreadStmt ? (int)$unreadStmt->fetchColumn() : 0;
                        echo $unreadCount;
                      } catch (Exception $e) {
                        echo '0';
                      }
                    ?></span>
                  </div>
                  <div class="progress mt-3" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 75%"></div>
                  </div>
                  <div class="text-center mt-2 small text-muted">Platform health: Good</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
<?php endif; ?>
  </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="changePasswordForm">
          <div class="mb-3">
            <label for="currentPassword" class="form-label">Current Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock"></i></span>
              <input type="password" class="form-control" id="currentPassword" name="current_password" required>
              <button class="btn btn-outline-secondary password-toggle" type="button" onclick="togglePasswordVisibility('currentPassword')">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="newPassword" class="form-label">New Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock"></i></span>
              <input type="password" class="form-control" id="newPassword" name="new_password" required>
              <button class="btn btn-outline-secondary password-toggle" type="button" onclick="togglePasswordVisibility('newPassword')">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <!-- Password Requirements -->
            <div class="password-requirements mt-2">
              <small class="text-muted">Password must contain:</small>
              <ul class="list-unstyled small">
                <li class="text-danger" id="req-length"><i class="fas fa-times-circle me-1"></i> At least 8 characters</li>
                <li class="text-danger" id="req-uppercase"><i class="fas fa-times-circle me-1"></i> At least one uppercase letter</li>
                <li class="text-danger" id="req-lowercase"><i class="fas fa-times-circle me-1"></i> At least one lowercase letter</li>
                <li class="text-danger" id="req-number"><i class="fas fa-times-circle me-1"></i> At least one number</li>
                <li class="text-danger" id="req-special"><i class="fas fa-times-circle me-1"></i> At least one special character</li>
              </ul>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="confirmNewPassword" class="form-label">Confirm New Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock"></i></span>
              <input type="password" class="form-control" id="confirmNewPassword" name="confirm_password" required>
              <button class="btn btn-outline-secondary password-toggle" type="button" onclick="togglePasswordVisibility('confirmNewPassword')">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <div id="passwordMatchIndicator" class="mt-2 text-muted">Enter password to confirm</div>
          </div>
          
          <div id="changePasswordMsg" class="mt-3"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="changePasswordBtn">Change Password</button>
      </div>
    </div>
  </div>
</div>

<div class="container-fluid p-4 admin-content">
  <?php 
  $currentTab = $_GET['tab'] ?? 'overview';
  
  // Load content based on the current tab
  $contentFile = __DIR__ . '/content_' . $currentTab . '.php';
  if (file_exists($contentFile)) {
    include $contentFile;
  } else {
    // Default content for overview tab
    if ($currentTab === 'overview'):
  ?>
    <div class="row">
      <div class="col-12">
        <h3 class="section-title">Admin Dashboard Overview</h3>
        <div class="alert alert-info">Welcome to the admin dashboard. Select a tab from the navigation to view different sections.</div>
      </div>
    </div>
  <?php 
    else:
      echo '<div class="alert alert-warning">Content for this tab is not available.</div>';
    endif;
  }
  ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

        </div>
      </div>
    </div>
  </div>
</body>
</html>