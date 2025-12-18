<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only buyers may access
require_role('buyer');
$user = current_user();
$pdo = get_db();

// Get buyer orders
$orders = [];
try {
    $stmt = $pdo->prepare("SELECT id, order_number, total, status, created_at, payment_status FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Buyer orders query failed: " . $e->getMessage());
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Dashboard - Agrisea Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/ecommerce_farmers_fishers/assets/css/unified_light_theme.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        /* Buyer Dashboard - Unified Light Theme */
        .buyer-content {
            background-color: var(--light-gray);
        }
        
        .dashboard-header {
            background: var(--primary-light);
            color: var(--dark-gray);
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 25px 25px;
            box-shadow: 0 6px 15px rgba(76, 175, 80, 0.2);
        }
        
        .feature-card {
            border-radius: 15px;
            border: none;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.1);
            background: var(--white);
        }
        
        .feature-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(76, 175, 80, 0.2);
        }
        
        .feature-icon {
            width: 75px;
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 1rem;
            font-size: 2rem;
            background: var(--primary-light);
            color: var(--primary-color);
            opacity: 1; /* Make icons solid */
            font-weight: 900; /* Make icons bold */
        }
        
        .cart-icon-bg { background-color: rgba(76, 175, 80, 0.15); color: var(--primary-color); }
        .orders-icon-bg { background-color: rgba(33, 150, 243, 0.15); color: var(--info-color); }
        .market-icon-bg { background-color: rgba(40, 167, 69, 0.15); color: var(--success-color); }
        .fav-icon-bg { background-color: rgba(220, 53, 69, 0.15); color: var(--danger-color); }
        
        .btn-feature {
            border-radius: 50px;
            font-weight: 600;
            padding: 0.6rem 1.75rem;
            transition: all 0.3s ease;
            border: none;
            background: var(--primary-color);
            color: var(--white);
        }
        
        .btn-feature:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(76, 175, 80, 0.3);
        }
        
        .section-title {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px;
        }
        
        .stat-card {
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.1);
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
            padding: 1.5rem;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.2);
        }
        
        .stat-icon {
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            font-size: 1.4rem;
            background: var(--primary-light);
            color: var(--primary-color);
            opacity: 1; /* Make icons solid */
            font-weight: 900; /* Make icons bold */
        }
        
        .recent-orders-table {
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.1);
            overflow: hidden;
        }
        
        .table th {
            font-weight: 600;
            color: var(--primary-color);
            border-top: none;
            background-color: var(--primary-light);
        }
        
        .badge-status {
            font-weight: 500;
            padding: 0.4em 0.8em;
            border-radius: 50px;
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.75rem 0;
                border-radius: 0 0 20px 20px;
            }
            
            .feature-card {
                margin-bottom: 1.25rem;
            }
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

                <!-- Loading Spinner -->
                <div class="loading-spinner" id="loadingSpinner">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div class="container-fluid p-4 buyer-content" id="mainContent">
                    <!-- Default Overview Content -->
                    <div class="dashboard-header text-center">
                        <div class="container">
                            <h1 class="display-5 fw-bold mb-3">Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                            <p class="lead mb-0">Manage your orders and shopping experience</p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-3 mb-4">
                            <div class="feature-card text-center p-4 h-100 d-flex flex-column justify-content-center align-items-center">
                                <div class="feature-icon cart-icon-bg mb-3">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <h5 class="fw-bold mb-2">Shopping Cart</h5>
                                <p class="text-muted small mb-3">View and manage your cart</p>
                                <a href="#" class="btn btn-success btn-feature mt-auto" onclick="loadContent('cart'); return false;">View Cart</a>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="feature-card text-center p-4 h-100 d-flex flex-column justify-content-center align-items-center">
                                <div class="feature-icon orders-icon-bg mb-3">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <h5 class="fw-bold mb-2">My Orders</h5>
                                <p class="text-muted small mb-3">Track your order history</p>
                                <a href="/ecommerce_farmers_fishers/buyer/dashboard.php?tab=orders" class="btn btn-primary btn-feature mt-auto" onclick="loadContent('orders'); return false;">View Orders</a>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="feature-card text-center p-4 h-100 d-flex flex-column justify-content-center align-items-center">
                                <div class="feature-icon market-icon-bg mb-3">
                                    <i class="fas fa-store"></i>
                                </div>
                                <h5 class="fw-bold mb-2">Marketplace</h5>
                                <p class="text-muted small mb-3">Browse and shop products</p>
                                <a href="#" class="btn btn-info btn-feature mt-auto" onclick="loadContent('marketplace'); return false;">Shop Now</a>
                            </div>
                        </div>
                        <div class="col-md-3 mb-4">
                            <div class="feature-card text-center p-4 h-100 d-flex flex-column justify-content-center align-items-center">
                                <div class="feature-icon fav-icon-bg mb-3">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <h5 class="fw-bold mb-2">Favorites</h5>
                                <p class="text-muted small mb-3">View your favorite products</p>
                                <a href="#" class="btn btn-danger btn-feature mt-auto" onclick="loadContent('favorites'); return false;">View Favorites</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <h3 class="section-title">Recent Activity</h3>
                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <div class="stat-card p-4 h-100">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-success text-white me-3">
                                                <i class="fas fa-shopping-cart"></i>
                                            </div>
                                            <div>
                                                <h5 class="mb-0 fw-bold"><?php echo count($orders); ?></h5>
                                                <p class="text-muted mb-0 small">Total Orders</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <div class="stat-card p-4 h-100">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-primary text-white me-3">
                                                <i class="fas fa-box"></i>
                                            </div>
                                            <div>
                                                <h5 class="mb-0 fw-bold">
                                                    <?php 
                                                        $deliveredOrders = array_filter($orders, function($order) { 
                                                            return $order['status'] === 'delivered'; 
                                                        });
                                                        echo count($deliveredOrders);
                                                    ?>
                                                </h5>
                                                <p class="text-muted mb-0 small">Delivered</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <div class="stat-card p-4 h-100">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-info text-white me-3">
                                                <i class="fas fa-wallet"></i>
                                            </div>
                                            <div>
                                                <h5 class="mb-0 fw-bold">₱<?php 
                                                    $totalSpent = array_sum(array_column($orders, 'total'));
                                                    echo number_format($totalSpent, 2);
                                                ?></h5>
                                                <p class="text-muted mb-0 small">Total Spent</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="/ecommerce_farmers_fishers/assets/js/dashboard-loader.js"></script>
    <script>
        // Update cart count
        function updateCartCount() {
            $.get('/ecommerce_farmers_fishers/buyer/cart_count.php', function(data) {
                const count = parseInt(data.count) || 0;
                const badge = $('.cart-count');
                if (count > 0) {
                    badge.text(count).show();
                } else {
                    badge.hide();
                }
            }).fail(function() {
                $('.cart-count').hide();
            });
        }

        // Initialize cart count on page load
        $(document).ready(function() {
            updateCartCount();
            
            // Check if tab parameter is present in URL and load corresponding content
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab) {
                // Small delay to ensure DOM is fully loaded
                setTimeout(function() {
                    loadContent(tab);
                }, 100);
            }
            
            // Handle password change form submission
            $('#changePasswordForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    currentPassword: $('#currentPassword').val(),
                    newPassword: $('#newPassword').val(),
                    confirmPassword: $('#confirmPassword').val()
                };
                
                // Simple client-side validation
                if (formData.newPassword !== formData.confirmPassword) {
                    alert('New passwords do not match!');
                    return;
                }
                
                if (formData.newPassword.length < 6) {
                    alert('Password must be at least 6 characters long!');
                    return;
                }
                
                // Send AJAX request to change password
                $.post('/ecommerce_farmers_fishers/user/change_password.php', formData, function(response) {
                    if (response.success) {
                        alert('Password changed successfully!');
                        $('#changePasswordModal').modal('hide');
                        $('#changePasswordForm')[0].reset();
                    } else {
                        alert('Error: ' + response.error);
                    }
                }).fail(function() {
                    alert('An error occurred while changing the password.');
                });
            });
        });
    </script>
</body>
</html>