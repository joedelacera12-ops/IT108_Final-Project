<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/notification_system.php';
require_once __DIR__ . '/includes/redirect_handler.php';

$user = current_user();
if (!$user) {
    redirect_to('/ecommerce_farmers_fishers/php/login.php');
}

$pdo = get_db();
$notificationSystem = new NotificationSystem();
$notifications = $notificationSystem->getAllNotifications($user['id'], 20);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - AgriSea Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/ecommerce_farmers_fishers/assets/css/style.css">
</head>
<body>
    <div class="container-fluid p-4 main-with-sidebar">
        <div class="row">
            <div class="col-lg-2 d-none d-lg-block">
                <?php include __DIR__ . '/../includes/dashboard_sidebar.php'; ?>
            </div>
            <div class="col-lg-10">
                <div class="row mb-3">
                    <div class="col-12">
                        <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(90deg,#0d6efd,#6610f2); border-radius:8px; padding:0.5rem 1rem;">
                            <a class="navbar-brand text-white" href="#">Notifications</a>
                            <div class="collapse navbar-collapse">
                                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                                    <li class="nav-item">
                                        <a class="nav-link text-white" href="#" id="markAllRead">
                                            <i class="fas fa-check-double me-1"></i>Mark All as Read
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </nav>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="text-success"><i class="fas fa-bell me-2"></i>Notifications</h3>
                            </div>
                            
                            <?php if (empty($notifications)): ?>
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-bell-slash fa-2x mb-3"></i>
                                    <h5>No notifications</h5>
                                    <p class="mb-0">You're all caught up! Check back later for new notifications.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($notifications as $notification): ?>
                                        <div class="list-group-item list-group-item-action <?php echo !$notification['is_read'] ? 'bg-light' : ''; ?>">
                                            <div class="d-flex">
                                                <div class="me-3 mt-1">
                                                    <?php if ($notification['type'] === 'sale'): ?>
                                                        <i class="fas fa-shopping-cart fa-2x text-success"></i>
                                                    <?php elseif ($notification['type'] === 'subscription'): ?>
                                                        <i class="fas fa-crown fa-2x text-warning"></i>
                                                    <?php elseif ($notification['type'] === 'low_stock'): ?>
                                                        <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                                                    <?php elseif ($notification['type'] === 'new_order'): ?>
                                                        <i class="fas fa-box fa-2x text-info"></i>
                                                    <?php elseif ($notification['type'] === 'payment'): ?>
                                                        <i class="fas fa-credit-card fa-2x text-primary"></i>
                                                    <?php elseif ($notification['type'] === 'rating'): ?>
                                                        <i class="fas fa-star fa-2x text-warning"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-bell fa-2x text-primary"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between">
                                                        <h5 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h5>
                                                        <small class="text-muted">
                                                            <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                    <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-muted">
                                                            <?php 
                                                            $typeLabels = [
                                                                'sale' => 'Sale',
                                                                'subscription' => 'Subscription',
                                                                'low_stock' => 'Low Stock',
                                                                'new_order' => 'New Order',
                                                                'payment' => 'Payment',
                                                                'rating' => 'Rating'
                                                            ];
                                                            echo isset($typeLabels[$notification['type']]) ? $typeLabels[$notification['type']] : 'General';
                                                            ?>
                                                        </small>
                                                        <?php if (!$notification['is_read']): ?>
                                                            <button class="btn btn-sm btn-outline-success mark-read" 
                                                                    data-id="<?php echo $notification['id']; ?>">
                                                                Mark as Read
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mark all as read
            document.getElementById('markAllRead').addEventListener('click', function(e) {
                e.preventDefault();
                
                fetch('/ecommerce_farmers_fishers/mark_all_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to mark notifications as read');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to mark notifications as read');
                });
            });
            
            // Mark individual notification as read
            document.querySelectorAll('.mark-read').forEach(button => {
                button.addEventListener('click', function() {
                    const notificationId = this.getAttribute('data-id');
                    
                    fetch('/ecommerce_farmers_fishers/mark_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({id: notificationId})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Failed to mark notification as read');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to mark notification as read');
                    });
                });
            });
        });
    </script>
</body>
</html>