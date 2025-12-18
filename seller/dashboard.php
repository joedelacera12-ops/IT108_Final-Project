<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notification_system.php';

// Only sellers may access
require_role('seller');
$user = current_user();
$pdo = get_db();

// Get seller information with address and business profile
try {
    $stmt = $pdo->prepare("SELECT u.*, ua.street_address as address_line1, ua.barangay as address_line2, ua.city, ua.province as state, ua.country, ua.postal_code, bp.business_name, bp.description as business_description FROM users u LEFT JOIN user_addresses ua ON u.id = ua.user_id AND ua.is_default = 1 LEFT JOIN business_profiles bp ON u.id = bp.user_id WHERE u.id = ?");
    $stmt->execute([$user['id']]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    $user = current_user(); // fallback to basic user data
}

$message = '';
$messageType = '';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['profile_picture']['type'], $allowedTypes)) {
            $message = 'Invalid file type. Please upload JPEG, PNG, or GIF images only.';
            $messageType = 'danger';
        } elseif ($_FILES['profile_picture']['size'] > $maxSize) {
            $message = 'File size too large. Maximum file size is 5MB.';
            $messageType = 'danger';
        } else {
            // Generate unique filename
            $extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user['id'] . '_' . time() . '.' . $extension;
            $uploadPath = __DIR__ . '/../uploads/profile_pictures/' . $filename;
            
            // Create directory if it doesn't exist
            if (!file_exists(__DIR__ . '/../uploads/profile_pictures/')) {
                mkdir(__DIR__ . '/../uploads/profile_pictures/', 0755, true);
            }
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                try {
                    // Update database with new profile image path
                    $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $stmt->execute(['/uploads/profile_pictures/' . $filename, $user['id']]);
                    
                    $message = 'Profile picture updated successfully.';
                    $messageType = 'success';
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT u.*, ua.street_address as address_line1, ua.barangay as address_line2, ua.city, ua.province as state, ua.country, ua.postal_code, bp.business_name, bp.description as business_description FROM users u LEFT JOIN user_addresses ua ON u.id = ua.user_id AND ua.is_default = 1 LEFT JOIN business_profiles bp ON u.id = bp.user_id WHERE u.id = ?");
                    $stmt->execute([$user['id']]);
                    $user = $stmt->fetch();
                } catch (Exception $e) {
                    $message = 'Error updating profile picture: ' . $e->getMessage();
                    $messageType = 'danger';
                    
                    // Delete uploaded file if database update failed
                    if (file_exists($uploadPath)) {
                        unlink($uploadPath);
                    }
                }
            } else {
                $message = 'Error uploading file. Please try again.';
                $messageType = 'danger';
            }
        }
    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        $message = 'Error uploading file. Please try again.';
        $messageType = 'danger';
    }
}

// Handle profile picture removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_picture'])) {
    try {
        // Get current profile image path
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $userData = $stmt->fetch();
        
        if ($userData && !empty($userData['profile_image'])) {
            // Delete the file if it exists
            $filePath = __DIR__ . '/..' . $userData['profile_image'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Update database to remove profile image reference
            $stmt = $pdo->prepare("UPDATE users SET profile_image = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            $message = 'Profile picture removed successfully.';
            $messageType = 'success';
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT u.*, ua.street_address as address_line1, ua.barangay as address_line2, ua.city, ua.province as state, ua.country, ua.postal_code, bp.business_name, bp.description as business_description FROM users u LEFT JOIN user_addresses ua ON u.id = ua.user_id AND ua.is_default = 1 LEFT JOIN business_profiles bp ON u.id = bp.user_id WHERE u.id = ?");
            $stmt->execute([$user['id']]);
            $user = $stmt->fetch();
        } else {
            $message = 'No profile picture to remove.';
            $messageType = 'warning';
        }
    } catch (Exception $e) {
        $message = 'Error removing profile picture: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $business_name = trim($_POST['business_name'] ?? '');
    $business_description = trim($_POST['business_description'] ?? '');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    
    try {
        // Update user info
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, email = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $phone, $email, $user['id']]);
        
        // Update or insert business profile
        $stmt = $pdo->prepare("INSERT INTO business_profiles (user_id, business_name, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE business_name = VALUES(business_name), description = VALUES(description)");
        $stmt->execute([$user['id'], $business_name, $business_description]);
        
        // Update or insert address
        $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, street_address, barangay, city, province, country, postal_code, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, '', 1) ON DUPLICATE KEY UPDATE street_address = VALUES(street_address), barangay = VALUES(barangay), city = VALUES(city), province = VALUES(province), country = VALUES(country), postal_code = VALUES(postal_code)");
        $stmt->execute([$user['id'], $address_line1, $address_line2, $city, $state, $country, $postal_code]);
        
        $message = 'Profile updated successfully';
        $messageType = 'success';
        
        // Refresh data
        $stmt = $pdo->prepare("SELECT u.*, ua.street_address as address_line1, ua.barangay as address_line2, ua.city, ua.province as state, ua.country, ua.postal_code, bp.business_name, bp.description as business_description FROM users u LEFT JOIN user_addresses ua ON u.id = ua.user_id AND ua.is_default = 1 LEFT JOIN business_profiles bp ON u.id = bp.user_id WHERE u.id = ?");
        $stmt->execute([$user['id']]);
        $user = $stmt->fetch();
    } catch (Exception $e) {
        $message = 'Error updating profile: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_status') {
        $orderId = $_POST['order_id'] ?? null;
        $status = $_POST['status'] ?? null;
        
        if ($orderId && $status) {
            try {
                // Check if the order belongs to this seller
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders o JOIN order_items oi ON oi.order_id = o.id JOIN products p ON oi.product_id = p.id WHERE o.id = ? AND p.seller_id = ?');
                $stmt->execute([$orderId, $user['id']]);
                $order_count = $stmt->fetchColumn();
                
                if ($order_count > 0) {
                    // Update order status
                    $stmt = $pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
                    $stmt->execute([$status, $orderId]);
                    
                    // Create notification for buyer
                    $notif = new NotificationSystem($pdo);
                    $notif->createDeliveryStatusNotification($_SESSION['user']['id'], $orderId, $status);
                    
                    $message = "Order status updated successfully.";
                    $messageType = "success";
                } else {
                    $message = "Order not found or unauthorized.";
                    $messageType = "danger";
                }
            } catch (Exception $e) {
                error_log("Order status update failed: " . $e->getMessage());
                $message = "Failed to update order status.";
                $messageType = "danger";
            }
        }
    }
}

// Get current tab
$currentTab = $_GET['tab'] ?? 'overview';

// Analytics data
$stats = [
    'total_revenue' => 0,
    'total_orders' => 0,
    'completed_orders' => 0,
    'pending_orders' => 0,
    'average_rating' => 0,
    'rating_count' => 0
];

try {
    // Total revenue and orders
    $stmt = $pdo->prepare("SELECT SUM(total) as total_revenue, COUNT(id) as total_orders FROM orders WHERE seller_id = ?");
    $stmt->execute([$user['id']]);
    $result = $stmt->fetch();
    $stats['total_revenue'] = $result['total_revenue'] ?? 0;
    $stats['total_orders'] = $result['total_orders'] ?? 0;

    // Order statuses
    $stmt = $pdo->prepare("SELECT status, COUNT(id) as count FROM orders WHERE seller_id = ? GROUP BY status");
    $stmt->execute([$user['id']]);
    while ($row = $stmt->fetch()) {
        if ($row['status'] === 'delivered') {
            $stats['completed_orders'] = $row['count'];
        } elseif ($row['status'] === 'pending') {
            $stats['pending_orders'] = $row['count'];
        }
    }

    // Average rating
    $stmt = $pdo->prepare("SELECT AVG(rating) as average_rating, COUNT(id) as rating_count FROM seller_ratings WHERE seller_id = ?");
    $stmt->execute([$user['id']]);
    $result = $stmt->fetch();
    $stats['average_rating'] = $result['average_rating'] ?? 0;
    $stats['rating_count'] = $result['rating_count'] ?? 0;

} catch (Exception $e) {
    // error_log($e->getMessage());
}


// Get seller orders
$sorders = [];
try {
    $s = $pdo->prepare('SELECT o.id, o.order_number, o.total, o.status, o.created_at, o.payment_status, o.actual_delivery FROM orders o JOIN order_items oi ON oi.order_id = o.id JOIN products p ON oi.product_id = p.id WHERE p.seller_id = ? GROUP BY o.id ORDER BY o.created_at DESC');
    $s->execute([(int)$user['id']]);
    $sorders = $s->fetchAll();
} catch (Exception $e) { 
    $sorders = []; 
    error_log("Seller orders query failed: " . $e->getMessage());
}

// Get seller ratings and reviews
$sellerRatings = [];
$averageRating = 0;
$totalRatings = 0;
try {
    // Get average rating
    $stmt = $pdo->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM seller_ratings WHERE seller_id = ?');
    $stmt->execute([$user['id']]);
    $ratingData = $stmt->fetch();
    $averageRating = $ratingData['avg_rating'] ? round($ratingData['avg_rating'], 1) : 0;
    $totalRatings = $ratingData['total_ratings'] ? (int)$ratingData['total_ratings'] : 0;
    
    // Get recent ratings
    $stmt = $pdo->prepare('SELECT sr.*, u.first_name, u.last_name FROM seller_ratings sr JOIN users u ON sr.buyer_id = u.id WHERE sr.seller_id = ? ORDER BY sr.created_at DESC LIMIT 5');
    $stmt->execute([$user['id']]);
    $sellerRatings = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Seller ratings query failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Agrisea Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/ecommerce_farmers_fishers/assets/css/unified_light_theme.css">
    <style>
        /* Seller Dashboard - Unified Light Theme */
        .seller-content {
            background-color: var(--light-gray);
        }
        
        .dashboard-card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(76, 175, 80, 0.15);
            transition: all 0.3s ease;
            border: 1px solid var(--border-light);
            background: var(--white);
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(76, 175, 80, 0.25);
            border-color: var(--border-light);
        }
        .stat-card {
            border-left: 5px solid var(--primary-color);
            background: var(--white);
        }
        .stat-card:nth-child(2) {
            border-left-color: var(--info-color);
        }
        .stat-card:nth-child(3) {
            border-left-color: var(--warning-color);
        }
        .stat-card:nth-child(4) {
            border-left-color: var(--danger-color);
        }
        .compact-form .form-control, .compact-form .form-select {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            height: calc(1.5em + 0.75rem + 2px);
            border: 1px solid var(--border-light);
        }
        .compact-form .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        .compact-form .form-label {
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
            color: var(--primary-color);
        }
        .compact-form .mb-3 {
            margin-bottom: 1rem !important;
        }
        .dashboard-visual {
            background: var(--primary-light);
        }
        .calendar-panel {
            min-height: 300px;
            background: var(--white);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.1);
            border: 1px solid var(--border-light);
        }
        .chart-panel {
            min-height: 200px;
            background: var(--white);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.1);
            border: 1px solid var(--border-light);
        }
        .nav-tabs .nav-link {
            border: 1px solid transparent;
            border-bottom: 3px solid transparent;
            color: var(--gray);
        }
        .nav-tabs .nav-link.active {
            border-bottom: 3px solid var(--primary-color);
            font-weight: 600;
            color: var(--primary-color);
            background: var(--primary-light);
            border-radius: 0.375rem 0.375rem 0 0;
        }
        .order-table th {
            font-weight: 600;
            color: var(--primary-color);
            background-color: var(--primary-light);
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25em 0.5em;
            border-radius: 12px;
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        .rating-stars {
            color: #ffc107;
        }
        .review-card {
            border-left: 4px solid var(--primary-color);
            padding-left: 1rem;
            background-color: var(--primary-light);
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Main Content (Full Width since sidebar is removed) -->
            <div class="col-12">
                <!-- Unified Navigation -->
                <?php include __DIR__ . '/../includes/unified_navbar.php'; ?>
            </div>
        </div>

        <div class="container-fluid p-4 seller-content">
            <?php 
            // Display success message if product was added
            $success_msg = $_GET['success'] ?? '';
            $error_msg = $_GET['error'] ?? '';
            ?>
                        <?php if (!empty($success_msg)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($success_msg); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_msg)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error_msg); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['tab']) && $_GET['tab'] === 'orders'): ?>
                <div class="container-fluid p-3">
                    <div class="card p-3 shadow-sm">
                        <h5>Orders Received</h5>
                        <?php if (empty($sorders)): ?>
                            <div class="alert alert-info">No orders yet.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped order-table">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Payment Status</th>
                                            <th>Order Status</th>
                                            <th>Delivery Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sorders as $so): ?>
                                            <?php 
                                                $status = strtolower(trim($so['status'] ?? ''));
                                                $paymentStatus = strtolower(trim($so['payment_status'] ?? ''));
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($so['order_number'] ?? '') ?></td>
                                                <td><?= date('M j, Y', strtotime($so['created_at'])) ?></td>
                                                <td>₱<?= number_format($so['total'] ?? 0, 2) ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $paymentStatus === 'paid' ? 'success' : 
                                                             ($paymentStatus === 'pending' ? 'warning' : 'secondary'); 
                                                    ?> status-badge">
                                                        <?= htmlspecialchars(ucfirst($so['payment_status'] ?? '')) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $status === 'delivered' ? 'success' : 
                                                             ($status === 'processing' ? 'warning' : 
                                                             ($status === 'shipped' ? 'info' : 
                                                             ($status === 'cancelled' ? 'danger' : 'secondary'))); 
                                                    ?> status-badge">
                                                        <?= htmlspecialchars(ucfirst($so['status'] ?? '')) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($so['actual_delivery'])): ?>
                                                        <?= date('M j, Y', strtotime($so['actual_delivery'])) ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($status === 'pending'): ?>
                                                        <button class="btn btn-sm btn-success me-1" onclick="updateOrderStatus(<?= $so['id'] ?>, 'processing')">Approve</button>
                                                        <button class="btn btn-sm btn-danger" onclick="updateOrderStatus(<?= $so['id'] ?>, 'cancelled')">Decline</button>
                                                    <?php elseif ($status === 'processing'): ?>
                                                        <button class="btn btn-sm btn-primary me-1" onclick="updateOrderStatus(<?= $so['id'] ?>, 'shipped')">Ready to Ship</button>
                                                        <button class="btn btn-sm btn-info" onclick="openAssignDeliveryModal(<?= $so['id'] ?>)">Assign Delivery</button>
                                                    <?php elseif ($status === 'shipped'): ?>
                                                        <button class="btn btn-sm btn-warning me-1" onclick="openUpdateDeliveryModal(<?= $so['id'] ?>)">Update Delivery</button>
                                                        <span class="text-muted">Waiting for delivery confirmation</span>
                                                    <?php elseif ($status === 'delivered'): ?>
                                                        <span class="text-success">Delivered</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">No actions</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif (isset($_GET['tab']) && $_GET['tab'] === 'addProduct'): ?>
                <div class="container-fluid p-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body compact-form">
                            <h5 class="card-title mb-4">Add New Product</h5>
                            <form id="productForm" class="compact-form" method="POST" action="/ecommerce_farmers_fishers/seller/add_product.php" enctype="multipart/form-data">
                                <?php include 'add-product-fields.php'; ?>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-plus me-1"></i>List Product
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <small id="productMsg" class="text-success"></small>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php elseif (isset($_GET['tab']) && $_GET['tab'] === 'subscription'): ?>
                <div class="container-fluid p-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <?php 
                            // Check if user has already made a subscription this month (to show red alert)
                            $subscriptionThisMonth = false;
                            try {
                                $stmt = $pdo->prepare('SELECT COUNT(*) FROM subscriptions WHERE user_id = ? AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())');
                                $stmt->execute([$user['id']]);
                                $subscriptionCount = (int)$stmt->fetchColumn();
                                
                                if ($subscriptionCount > 0) {
                                    $subscriptionThisMonth = true;
                                }
                            } catch (Exception $e) {
                                // Ignore errors
                            }

                            // Check if user has an active subscription (to show green alert)
                            $hasActiveSubscription = false;
                            try {
                                $stmt = $pdo->prepare('SELECT COUNT(*) FROM subscriptions WHERE user_id = ? AND status = "active" AND end_date >= NOW()');
                                $stmt->execute([$user['id']]);
                                $activeSubscriptionCount = (int)$stmt->fetchColumn();
                                
                                if ($activeSubscriptionCount > 0) {
                                    $hasActiveSubscription = true;
                                }
                            } catch (Exception $e) {
                                // Ignore errors
                            }

                            // Show appropriate alert based on subscription status
                            if ($subscriptionThisMonth) {
                                echo '<div class="alert alert-danger subscription-alert mb-3">';
                                echo 'You have already subscribed this month.';
                                echo '</div>';
                            } elseif ($hasActiveSubscription) {
                                echo '<div class="alert alert-success mb-3">';
                                echo 'You are still within the validity of an active plan.';
                                echo '</div>';
                            }
                            ?>
                            <?php include 'subscription_plans.php'; ?>
                        </div>
                    </div>
                </div>
            <?php elseif (isset($_GET['tab']) && $_GET['tab'] === 'messages'): ?>
                <div class="container-fluid p-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <?php include __DIR__ . '/../user/messages_content.php'; ?>
                        </div>
                    </div>
                </div>
            <?php elseif (isset($_GET['tab']) && $_GET['tab'] === 'profile'): ?>
                <div class="container-fluid p-3">
                    <div class="row">
                        <div class="col-12">
                            <h3 class="mb-4"><i class="fas fa-user-edit me-2"></i>Seller Profile</h3>
                            
                            <?php if (!empty($message)): ?>
                                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-camera me-2"></i>Profile Picture</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 text-center">
                                            <?php 
                                            $profileImage = $user['profile_image'] ?? null;
                                            if ($profileImage && file_exists(__DIR__ . '/..' . $profileImage)) {
                                                $imageUrl = '/ecommerce_farmers_fishers' . $profileImage;
                                            } else {
                                                // Use gender-based default avatars
                                                $gender = $user['gender'] ?? '';
                                                if ($gender === 'male') {
                                                    $imageUrl = '/ecommerce_farmers_fishers/assets/images/avatar-male.png';
                                                } elseif ($gender === 'female') {
                                                    $imageUrl = '/ecommerce_farmers_fishers/assets/images/avatar-female.png';
                                                } else {
                                                    $imageUrl = '/ecommerce_farmers_fishers/assets/images/avatar-placeholder.png';
                                                }
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                        </div>
                                        <div class="col-md-8">
                                            <form method="POST" enctype="multipart/form-data">
                                                <div class="mb-3">
                                                    <label for="profilePicture" class="form-label">Upload New Picture</label>
                                                    <input type="file" class="form-control" id="profilePicture" name="profile_picture" accept="image/*">
                                                    <div class="form-text">Maximum file size: 5MB. Supported formats: JPG, PNG, GIF</div>
                                                </div>
                                                <button type="submit" name="upload_picture" class="btn btn-outline-primary">
                                                    <i class="fas fa-upload me-1"></i>Upload Picture
                                                </button>
                                                <?php if ($user && !empty($user['profile_image'])): ?>
                                                <button type="submit" name="remove_picture" class="btn btn-outline-danger mt-2">
                                                    <i class="fas fa-trash me-1"></i>Remove Picture
                                                </button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Profile Information</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="firstName" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="firstName" name="first_name" 
                                                       value="<?php echo $user ? htmlspecialchars($user['first_name'] ?? '') : ''; ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="lastName" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="lastName" name="last_name" 
                                                       value="<?php echo $user ? htmlspecialchars($user['last_name'] ?? '') : ''; ?>" required>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="email" class="form-label">Email Address</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo $user ? htmlspecialchars($user['email'] ?? '') : ''; ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="phone" class="form-label">Phone Number</label>
                                                <input type="text" class="form-control" id="phone" name="phone" 
                                                       value="<?php echo $user ? htmlspecialchars($user['phone'] ?? '') : ''; ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="businessName" class="form-label">Business Name</label>
                                            <input type="text" class="form-control" id="businessName" name="business_name" 
                                                   value="<?php echo htmlspecialchars($user['business_name'] ?? ''); ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="businessDescription" class="form-label">Business Description</label>
                                            <textarea class="form-control" id="businessDescription" name="business_description" rows="3"><?php 
                                                echo htmlspecialchars($user['business_description'] ?? ''); 
                                            ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label for="addressLine1" class="form-label">Address Line 1</label>
                                            <input type="text" class="form-control" id="addressLine1" name="address_line1" 
                                                   value="<?php echo htmlspecialchars($user['address_line1'] ?? ''); ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="addressLine2" class="form-label">Address Line 2</label>
                                            <input type="text" class="form-control" id="addressLine2" name="address_line2" 
                                                   value="<?php echo htmlspecialchars($user['address_line2'] ?? ''); ?>">
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label for="city" class="form-label">City</label>
                                                <input type="text" class="form-control" id="city" name="city" 
                                                       value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="state" class="form-label">State/Province</label>
                                                <input type="text" class="form-control" id="state" name="state" 
                                                       value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="postalCode" class="form-label">Postal Code</label>
                                                <input type="text" class="form-control" id="postalCode" name="postal_code" 
                                                       value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="country" class="form-label">Country</label>
                                            <input type="text" class="form-control" id="country" name="country" 
                                                   value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                                        </div>

                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Save Changes
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Sales Summary</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Get sales stats
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id) as total_orders, SUM(o.total) as total_revenue, COUNT(p.id) as total_products FROM users u LEFT JOIN products p ON u.id = p.seller_id LEFT JOIN order_items oi ON p.id = oi.product_id LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'delivered' WHERE u.id = ?");
                                        $stmt->execute([$user['id']]);
                                        $stats = $stmt->fetch();
                                    } catch (Exception $e) {
                                        $stats = ['total_orders' => 0, 'total_revenue' => 0, 'total_products' => 0];
                                    }
                                    ?>
                                    
                                    <div class="text-center mb-3">
                                        <div class="display-6 text-primary">₱<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                                        <div class="text-muted">Total Revenue</div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total Products</span>
                                        <span class="fw-bold"><?php echo $stats['total_products'] ?? 0; ?></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Completed Orders</span>
                                        <span class="fw-bold"><?php echo $stats['total_orders'] ?? 0; ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Account Settings</h5>
                                </div>
                                <div class="card-body">
                                    <button class="btn btn-outline-secondary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                        <i class="fas fa-key me-1"></i>Change Password
                                    </button>
                                    <button class="btn btn-outline-danger w-100" disabled>
                                        <i class="fas fa-exclamation-triangle me-1"></i>Deactivate Account
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif (isset($_GET['tab']) && $_GET['tab'] === 'ratings'): ?>
                <div class="container-fluid p-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-4">Ratings & Reviews</h5>
                            <?php if (empty($sellerRatings)): ?>
                                <div class="alert alert-info">No reviews yet.</div>
                            <?php else: ?>
                                <?php foreach ($sellerRatings as $rating): ?>
                                    <div class="review-card mb-3 p-3 border-start border-success border-3 rounded bg-light">
                                        <div class="d-flex justify-content-between">
                                            <strong><?= htmlspecialchars($rating['first_name'] . ' ' . $rating['last_name']) ?></strong>
                                            <span class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $rating['rating']): ?>
                                                        <i class="fas fa-star text-warning"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star text-muted"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </span>
                                        </div>
                                        <p class="mb-1 mt-2"><?= htmlspecialchars($rating['review'] ?? '') ?></p>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($rating['created_at'])) ?></small>
                                    </div>
                                <?php endforeach; ?>
                                <div class="text-center mt-3">
                                    <a href="/ecommerce_farmers_fishers/seller/reviews.php" class="btn btn-sm btn-outline-primary">View All Reviews</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php elseif (isset($_GET['tab']) && $_GET['tab'] === 'products'): ?>
                <div class="container-fluid p-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-4">My Products</h5>
                            <?php 
                            // Get seller's products
                            $products = [];
                            try {
                                $stmt = $pdo->prepare('SELECT p.*, c.name as category_name, pi.image_path FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 WHERE p.seller_id = ? ORDER BY p.created_at DESC');
                                $stmt->execute([$user['id']]);
                                $products = $stmt->fetchAll();
                            } catch (Exception $e) {
                                error_log("Error fetching products: " . $e->getMessage());
                            }
                            ?>
                            
                            <?php if (empty($products)): ?>
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-box-open fa-2x mb-3"></i>
                                    <h6>No products found</h6>
                                    <p>You haven't added any products yet.</p>
                                    <a href="/ecommerce_farmers_fishers/seller/dashboard.php?tab=addProduct" class="btn btn-success">
                                        <i class="fas fa-plus me-1"></i>Add Your First Product
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Image</th>
                                                <th>Product Name</th>
                                                <th>Category</th>
                                                <th>Price</th>
                                                <th>Stock</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($products as $product): ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($product['image_path']) && file_exists(__DIR__ . '/../' . $product['image_path'])): ?>
                                                            <img src="/ecommerce_farmers_fishers/<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . (strlen($product['description']) > 50 ? '...' : ''); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                                    <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                                    <td><?php echo (int)$product['stock']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $product['status'] === 'published' ? 'success' : 
                                                                 ($product['status'] === 'pending' ? 'warning' : 
                                                                 ($product['status'] === 'draft' ? 'secondary' : 'danger')); 
                                                        ?>">
                                                            <?php echo ucfirst(htmlspecialchars($product['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="/ecommerce_farmers_fishers/seller/edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Product">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)" title="Delete Product">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
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
            <?php else: ?>
                <div class="container-fluid p-3">
                    <div class="row mb-4">
                        <div class="col-12">
                            <h3 class="mb-0"><i class="fas fa-chart-line me-2"></i>Dashboard Overview</h3>
                            <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')); ?>! Here's what's happening with your store today.</p>
                        </div>
                    </div>
                    
                    <!-- Account Type Banner -->
                    <?php 
                    $displayType = get_seller_type_display($pdo); 
                    ?>
                    <?php if (!empty($displayType)): ?>
                        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-user-tag me-2"></i><strong>Account Type:</strong> <?= htmlspecialchars($displayType) ?> Account
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Stats Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="card dashboard-card h-100 shadow-sm border-0">
                                <div class="card-body text-center">
                                    <div class="stat-icon bg-success text-white d-inline-flex align-items-center justify-content-center rounded-circle mb-3 mx-auto" style="width: 60px; height: 60px;">
                                        <i class="fas fa-box fs-4"></i>
                                    </div>
                                    <h6 class="text-muted mb-1">Your Products</h6>
                                    <h3 class="mb-0"><span class="product-count" id="productCount"><?php
                                        $prodCount = 0;
                                        try {
                                          $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE seller_id = ?');
                                          $stmt->execute([(int)$user['id']]);
                                          $prodCount = (int)$stmt->fetchColumn();
                                        } catch (Exception $e) { $prodCount = 0; }
                                        echo $prodCount;
                                    ?></span></h3>
                                    <small class="text-muted">Active listings</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card dashboard-card h-100 shadow-sm border-0">
                                <div class="card-body text-center">
                                    <div class="stat-icon bg-primary text-white d-inline-flex align-items-center justify-content-center rounded-circle mb-3 mx-auto" style="width: 60px; height: 60px;">
                                        <i class="fas fa-shopping-cart fs-4"></i>
                                    </div>
                                    <h6 class="text-muted mb-1">Total Orders</h6>
                                    <h3 class="mb-0"><span class="order-count" id="orderCount"><?php
                                        $orderCount = 0;
                                        try {
                                          // Count orders for this seller
                                          $stmt = $pdo->prepare('SELECT COUNT(DISTINCT o.id) FROM orders o JOIN order_items oi ON oi.order_id = o.id JOIN products p ON oi.product_id = p.id WHERE p.seller_id = ?');
                                          $stmt->execute([(int)$user['id']]);
                                          $orderCount = (int)$stmt->fetchColumn();
                                        } catch (Exception $e) { 
                                          $orderCount = 0; 
                                          error_log("Order count query failed: " . $e->getMessage());
                                        }
                                        echo $orderCount;
                                    ?></span></h3>
                                    <small class="text-muted">Received orders</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card dashboard-card h-100 shadow-sm border-0">
                                <div class="card-body text-center">
                                    <div class="stat-icon bg-warning text-white d-inline-flex align-items-center justify-content-center rounded-circle mb-3 mx-auto" style="width: 60px; height: 60px;">
                                        <i class="fas fa-star fs-4"></i>
                                    </div>
                                    <h6 class="text-muted mb-1">Seller Rating</h6>
                                    <h3 class="mb-0">
                                        <?php if ($totalRatings > 0): ?>
                                            <span class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $averageRating): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php elseif ($i - 0.5 <= $averageRating): ?>
                                                        <i class="fas fa-star-half-alt"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </span>
                                            <small class="d-block mt-1"><?= $averageRating ?>/5 (<?= $totalRatings ?> review<?= $totalRatings != 1 ? 's' : '' ?>)</small>
                                        <?php else: ?>
                                            <span class="text-muted">No ratings</span>
                                        <?php endif; ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>

                        <!-- Calendar and Reviews Section -->
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="card dashboard-card shadow-sm border-0 mb-0">
                                    <div class="card-header bg-white py-3 border-0">
                                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Restock & Delivery Calendar</h5>
                                    </div>
                                    <div class="card-body">
                                    <?php 
                                    // Get user's current subscription expiration date
                                    $subscriptionEndDate = null;
                                    try {
                                        $stmt = $pdo->prepare('SELECT end_date FROM subscriptions WHERE user_id = ? AND status = "active" ORDER BY end_date DESC LIMIT 1');
                                        $stmt->execute([$user['id']]);
                                        $subscription = $stmt->fetch();
                                        if ($subscription) {
                                            $subscriptionEndDate = $subscription['end_date'];
                                        }
                                    } catch (Exception $e) {
                                        // Ignore errors
                                    }
                                    ?>
                                    <?php if ($subscriptionEndDate): ?>
                                        <div class="alert alert-info mb-2">
                                            <i class="fas fa-info-circle me-2"></i>Your subscription expires on: <?= date('F j, Y', strtotime($subscriptionEndDate)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="calendar-panel" id="sellerCalendar"></div>
                                </div>
                            </div>
                                <div class="col-lg-4">
                                <div class="card dashboard-card shadow-sm border-0 mb-0">
                                    <div class="card-header bg-white py-3 border-0">
                                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Sales Summary</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Total Revenue</span>
                                            <span class="fw-bold">₱<?= number_format($stats['total_revenue'], 2) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Completed Orders</span>
                                            <span class="fw-bold"><?= $stats['completed_orders'] ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Pending Orders</span>
                                            <span class="fw-bold"><?= $stats['pending_orders'] ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Average Rating</span>
                                            <span class="fw-bold"><?= number_format($stats['average_rating'], 2) ?> (<?= $stats['rating_count'] ?>)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Product Form Scripts -->
    <script>
        // Handle form submission feedback
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const msg = document.getElementById('productMsg');
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Show loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
            }
            
            // Clear previous messages
            if (msg) {
                msg.innerHTML = '';
            }
        });
        
        // Function to update product count
        function updateProductCount() {
            fetch('/ecommerce_farmers_fishers/seller/get_product_count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const countElement = document.getElementById('productCount');
                        if (countElement) {
                            countElement.textContent = data.count;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error updating product count:', error);
                });
        }
        
        // Function to update order count
        function updateOrderCount() {
            fetch('/ecommerce_farmers_fishers/seller/get_order_count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const countElement = document.getElementById('orderCount');
                        if (countElement) {
                            countElement.textContent = data.count;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error updating order count:', error);
                });
        }
        
        // Update product and order counts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            updateProductCount();
            updateOrderCount();
        });
    </script>
    
    <!-- Order Status Update Script -->
    <script>
        // Function to update order status
        function updateOrderStatus(orderId, status) {
            if (!confirm(`Are you sure you want to ${status === 'processing' ? 'approve' : status === 'cancelled' ? 'decline' : 'mark as ready to ship'} this order?`)) {
                return;
            }
            
            // Show loading indicator
            const buttons = document.querySelectorAll(`button[onclick*="${orderId}"]`);
            buttons.forEach(btn => {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
            });
            
            fetch('/ecommerce_farmers_fishers/api/update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('Order status updated successfully!');
                    // Refresh the page to show updated status
                    location.reload();
                } else {
                    // Show error message
                    alert('Error updating order status: ' + (data.error || 'Unknown error'));
                    // Restore buttons
                    buttons.forEach(btn => {
                        btn.disabled = false;
                        // Restore original text based on status
                        if (status === 'processing') {
                            btn.innerHTML = 'Approve';
                        } else if (status === 'cancelled') {
                            btn.innerHTML = 'Decline';
                        } else if (status === 'shipped') {
                            btn.innerHTML = 'Ready to Ship';
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating order status. Please try again.');
                // Restore buttons
                buttons.forEach(btn => {
                    btn.disabled = false;
                    // Restore original text based on status
                    if (status === 'processing') {
                        btn.innerHTML = 'Approve';
                    } else if (status === 'cancelled') {
                        btn.innerHTML = 'Decline';
                    } else if (status === 'shipped') {
                        btn.innerHTML = 'Ready to Ship';
                    }
                });
            });
        }
    </script>
    
    <!-- Delivery Management Scripts -->
    <script>
        // Function to load delivery partners
        function loadDeliveryPartners() {
            // Show loading indicator
            const select = document.getElementById('deliveryPartnerSelect');
            const originalContent = select.innerHTML;
            select.innerHTML = '<option>Loading delivery partners...</option>';
            select.disabled = true;
            
            fetch('/ecommerce_farmers_fishers/api/delivery_partners.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        select.innerHTML = '<option value="">Select a delivery partner</option>';
                        
                        data.partners.forEach(partner => {
                            const option = document.createElement('option');
                            option.value = partner.id;
                            option.textContent = partner.name;
                            select.appendChild(option);
                        });
                        select.disabled = false;
                    } else {
                        select.innerHTML = '<option>Error loading partners</option>';
                        console.error('Error loading delivery partners:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error loading delivery partners:', error);
                    select.innerHTML = '<option>Error loading partners</option>';
                    select.disabled = false;
                });
        }
        
        // Function to open assign delivery modal
        function openAssignDeliveryModal(orderId) {
            document.getElementById('assignOrderId').value = orderId;
            loadDeliveryPartners();
            const modal = new bootstrap.Modal(document.getElementById('assignDeliveryModal'));
            modal.show();
        }
        
        // Function to open update delivery modal
        function openUpdateDeliveryModal(orderId) {
            document.getElementById('updateOrderId').value = orderId;
            const modal = new bootstrap.Modal(document.getElementById('updateDeliveryModal'));
            modal.show();
        }
        
        // Handle assign delivery form submission
        document.getElementById('assignDeliveryForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const orderId = formData.get('order_id');
            const deliveryPartnerId = formData.get('delivery_partner_id');
            
            if (!orderId || !deliveryPartnerId) {
                alert('Please select a delivery partner');
                return;
            }
            
            // Show loading indicator
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';
            submitBtn.disabled = true;
            
            fetch('/ecommerce_farmers_fishers/api/delivery_partners.php?action=assign', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message and close modal
                    alert('Delivery assigned successfully!');
                    // Close the modal
                    const modalElement = document.getElementById('assignDeliveryModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                    // Refresh the page to show updated status
                    location.reload();
                } else {
                    // Show error message
                    alert('Error assigning delivery: ' + (data.error || 'Unknown error'));
                    // Restore button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error assigning delivery. Please try again.');
                // Restore button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // Handle update delivery form submission
        document.getElementById('updateDeliveryForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const orderId = formData.get('order_id');
            const status = formData.get('status');
            
            if (!orderId || !status) {
                alert('Please select a status');
                return;
            }
            
            // Show loading indicator
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            submitBtn.disabled = true;
            
            fetch('/ecommerce_farmers_fishers/api/delivery_updates.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message and close modal
                    alert('Delivery status updated successfully!');
                    // Close the modal
                    const modalElement = document.getElementById('updateDeliveryModal');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                    // Refresh the page to show updated status
                    location.reload();
                } else {
                    // Show error message
                    alert('Error updating delivery status: ' + (data.error || 'Unknown error'));
                    // Restore button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating delivery status. Please try again.');
                // Restore button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // Function to delete product
        function deleteProduct(productId) {
            if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                return;
            }
            
            // Show loading indicator
            const deleteButtons = document.querySelectorAll(`button[onclick*="${productId}"]`);
            deleteButtons.forEach(btn => {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
            });
            
            fetch('/ecommerce_farmers_fishers/seller/delete_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('Product deleted successfully!');
                    // Refresh the page to show updated product list
                    location.reload();
                } else {
                    // Show error message
                    alert('Error deleting product: ' + (data.error || 'Unknown error'));
                    // Restore buttons
                    deleteButtons.forEach(btn => {
                        btn.innerHTML = '<i class="fas fa-trash"></i>';
                        btn.disabled = false;
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting product. Please try again.');
                // Restore buttons
                deleteButtons.forEach(btn => {
                    btn.innerHTML = '<i class="fas fa-trash"></i>';
                    btn.disabled = false;
                });
            });
        }
    </script>
    
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="changePasswordForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="changePasswordMessage" class="alert d-none"></div>
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="currentPassword" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="currentPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="newPassword" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="newPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                <small>Password must contain:</small>
                                <ul class="list-unstyled mb-0">
                                    <li class="text-danger" id="req-length"><i class="fas fa-times-circle me-1"></i> At least 8 characters</li>
                                    <li class="text-danger" id="req-uppercase"><i class="fas fa-times-circle me-1"></i> At least one uppercase letter</li>
                                    <li class="text-danger" id="req-lowercase"><i class="fas fa-times-circle me-1"></i> At least one lowercase letter</li>
                                    <li class="text-danger" id="req-number"><i class="fas fa-times-circle me-1"></i> At least one number</li>
                                    <li class="text-danger" id="req-special"><i class="fas fa-times-circle me-1"></i> At least one special character</li>
                                </ul>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmPassword" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="passwordMatchError" class="text-danger d-none mt-1">
                                <i class="fas fa-exclamation-circle me-1"></i> Passwords do not match
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="changePasswordBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            <span class="sr-only">Change Password</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const targetInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    targetInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
        
        // Password strength validation
        const newPasswordInput = document.getElementById('newPassword');
        const requirements = {
            length: document.getElementById('req-length'),
            uppercase: document.getElementById('req-uppercase'),
            lowercase: document.getElementById('req-lowercase'),
            number: document.getElementById('req-number'),
            special: document.getElementById('req-special')
        };
        
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            
            // Check length
            if (password.length >= 8) {
                requirements.length.classList.remove('text-danger');
                requirements.length.classList.add('text-success');
                requirements.length.innerHTML = '<i class="fas fa-check-circle me-1"></i> At least 8 characters';
            } else {
                requirements.length.classList.remove('text-success');
                requirements.length.classList.add('text-danger');
                requirements.length.innerHTML = '<i class="fas fa-times-circle me-1"></i> At least 8 characters';
            }
            
            // Check uppercase
            if (/[A-Z]/.test(password)) {
                requirements.uppercase.classList.remove('text-danger');
                requirements.uppercase.classList.add('text-success');
                requirements.uppercase.innerHTML = '<i class="fas fa-check-circle me-1"></i> At least one uppercase letter';
            } else {
                requirements.uppercase.classList.remove('text-success');
                requirements.uppercase.classList.add('text-danger');
                requirements.uppercase.innerHTML = '<i class="fas fa-times-circle me-1"></i> At least one uppercase letter';
            }
            
            // Check lowercase
            if (/[a-z]/.test(password)) {
                requirements.lowercase.classList.remove('text-danger');
                requirements.lowercase.classList.add('text-success');
                requirements.lowercase.innerHTML = '<i class="fas fa-check-circle me-1"></i> At least one lowercase letter';
            } else {
                requirements.lowercase.classList.remove('text-success');
                requirements.lowercase.classList.add('text-danger');
                requirements.lowercase.innerHTML = '<i class="fas fa-times-circle me-1"></i> At least one lowercase letter';
            }
            
            // Check number
            if (/\d/.test(password)) {
                requirements.number.classList.remove('text-danger');
                requirements.number.classList.add('text-success');
                requirements.number.innerHTML = '<i class="fas fa-check-circle me-1"></i> At least one number';
            } else {
                requirements.number.classList.remove('text-success');
                requirements.number.classList.add('text-danger');
                requirements.number.innerHTML = '<i class="fas fa-times-circle me-1"></i> At least one number';
            }
            
            // Check special character
            if (/[^A-Za-z0-9]/.test(password)) {
                requirements.special.classList.remove('text-danger');
                requirements.special.classList.add('text-success');
                requirements.special.innerHTML = '<i class="fas fa-check-circle me-1"></i> At least one special character';
            } else {
                requirements.special.classList.remove('text-success');
                requirements.special.classList.add('text-danger');
                requirements.special.innerHTML = '<i class="fas fa-times-circle me-1"></i> At least one special character';
            }
        });
        
        // Password confirmation validation
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const passwordMatchError = document.getElementById('passwordMatchError');
        
        function validatePasswordMatch() {
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                passwordMatchError.classList.remove('d-none');
                return false;
            } else {
                passwordMatchError.classList.add('d-none');
                return true;
            }
        }
        
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);
        newPasswordInput.addEventListener('input', validatePasswordMatch);
        
        // Handle form submission
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            // Validate passwords match
            if (!validatePasswordMatch()) {
                return;
            }
            
            // Check all requirements are met
            const allRequirementsMet = Array.from(Object.values(requirements)).every(req => 
                req.classList.contains('text-success')
            );
            
            if (!allRequirementsMet) {
                showMessage('Please meet all password requirements.', 'danger');
                return;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('changePasswordBtn');
            const spinner = submitBtn.querySelector('.spinner-border');
            const buttonText = submitBtn.querySelector('.sr-only');
            
            spinner.classList.remove('d-none');
            buttonText.textContent = 'Changing...';
            submitBtn.disabled = true;
            
            // Send request to server
            fetch('/ecommerce_farmers_fishers/process_change_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'change_password',
                    current_password: currentPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    // Reset form
                    document.getElementById('changePasswordForm').reset();
                    // Reset requirements display
                    Object.values(requirements).forEach(req => {
                        req.classList.remove('text-success');
                        req.classList.add('text-danger');
                        const text = req.textContent.replace('fa-check-circle', 'fa-times-circle');
                        req.innerHTML = text.replace('At least', '<i class="fas fa-times-circle me-1"></i> At least');
                    });
                    passwordMatchError.classList.add('d-none');
                    // Close modal after delay
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
                    }, 2000);
                } else {
                    showMessage(data.error || 'An error occurred. Please try again.', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                // Reset loading state
                spinner.classList.add('d-none');
                buttonText.textContent = 'Change Password';
                submitBtn.disabled = false;
            });
        });
        
        function showMessage(message, type) {
            const messageDiv = document.getElementById('changePasswordMessage');
            messageDiv.className = `alert alert-${type} d-block`;
            messageDiv.textContent = message;
        }
        
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const targetInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    targetInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    </script>
    
</body>
</html>