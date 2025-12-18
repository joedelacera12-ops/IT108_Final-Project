<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    redirect_to('/ecommerce_farmers_fishers/php/login.php');
}

require_role('buyer');
$user = current_user();
$pdo = get_db();

// Get order ID from query parameter
$orderId = $_GET['order'] ?? null;

if (!$orderId) {
    redirect_to('/ecommerce_farmers_fishers/buyer/dashboard.php');
    exit;
}

// Get order details
try {
    $stmt = $pdo->prepare('
        SELECT o.*, u.first_name, u.last_name, u.email, u.phone, u.street_address, u.city, u.province 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? AND o.user_id = ?
    ');
    $stmt->execute([$orderId, $user['id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        redirect_to('/ecommerce_farmers_fishers/buyer/dashboard.php');
        exit;
    }
    
    // Get order items
    $itemsStmt = $pdo->prepare('
        SELECT oi.*, p.name as product_name 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ');
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll();
} catch (Exception $e) {
    redirect_to('/ecommerce_farmers_fishers/buyer/dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - AgriSea Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm" style="padding: 0.75rem 1rem;">
      <div class="container-fluid">
        <a class="navbar-brand" href="/ecommerce_farmers_fishers/buyer/dashboard.php"><strong class="text-success">AgriSea</strong> Marketplace</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a href="/ecommerce_farmers_fishers/buyer/dashboard.php?tab=marketplace" class="nav-link">Marketplace</a></li>
            <li class="nav-item"><a href="/ecommerce_farmers_fishers/buyer/dashboard.php" class="nav-link">Dashboard</a></li>
            <li class="nav-item position-relative">
              <a href="/ecommerce_farmers_fishers/buyer/cart.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">0</span>
              </a>
            </li>
            <li class="nav-item"><a href="/ecommerce_farmers_fishers/logout.php" class="nav-link">Logout</a></li>
          </ul>
        </div>
      </div>
    </nav>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white text-center">
                        <h4 class="mb-0"><i class="fas fa-check-circle me-2"></i>Order Confirmed!</h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                            <h3 class="mt-3">Thank You for Your Purchase!</h3>
                            <p class="text-muted">Your order has been successfully processed.</p>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Order Details</h5>
                                <p>
                                    <strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?><br>
                                    <strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?><br>
                                    <strong>Payment Status:</strong> <span class="badge bg-success">Paid</span><br>
                                    <strong>Order Status:</strong> <span class="badge bg-info">Processing</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5>Shipping Information</h5>
                                <p>
                                    <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?><br>
                                    <?php echo htmlspecialchars(($order['street_address'] ?? '') . ', ' . ($order['city'] ?? '') . ', ' . ($order['province'] ?? '')); ?><br>
                                    <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <h5>Order Summary</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td class="text-end">₱<?php echo number_format($item['total'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                        <td class="text-end">₱<?php echo number_format($order['subtotal'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                                        <td class="text-end">₱<?php echo number_format($order['shipping'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end"><strong>₱<?php echo number_format($order['total'], 2); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="/ecommerce_farmers_fishers/buyer/dashboard.php" class="btn btn-primary">View My Orders</a>
                            <a href="/ecommerce_farmers_fishers/buyer/dashboard.php?tab=marketplace" class="btn btn-success">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Add confetti effect for successful order
    document.addEventListener('DOMContentLoaded', function() {
      // Simple confetti effect
      const confettiContainer = document.createElement('div');
      confettiContainer.style.position = 'fixed';
      confettiContainer.style.top = '0';
      confettiContainer.style.left = '0';
      confettiContainer.style.width = '100%';
      confettiContainer.style.height = '100%';
      confettiContainer.style.pointerEvents = 'none';
      confettiContainer.style.zIndex = '9998';
      confettiContainer.id = 'confetti';
      document.body.appendChild(confettiContainer);
      
      // Create confetti pieces
      for (let i = 0; i < 100; i++) {
        const confetti = document.createElement('div');
        confetti.style.position = 'absolute';
        confetti.style.width = Math.random() * 10 + 5 + 'px';
        confetti.style.height = Math.random() * 10 + 5 + 'px';
        confetti.style.backgroundColor = getRandomColor();
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.top = '-10px';
        confetti.style.opacity = Math.random();
        confetti.style.borderRadius = '50%';
        confettiContainer.appendChild(confetti);
        
        // Animate confetti
        animateConfetti(confetti);
      }
      
      // Remove confetti after animation
      setTimeout(() => {
        if (confettiContainer.parentNode) {
          confettiContainer.parentNode.removeChild(confettiContainer);
        }
      }, 3000);
      
      function getRandomColor() {
        const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57', '#ff9ff3', '#54a0ff'];
        return colors[Math.floor(Math.random() * colors.length)];
      }
      
      function animateConfetti(element) {
        const animation = element.animate([
          { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
          { transform: `translateY(${window.innerHeight}px) rotate(${Math.random() * 360}deg)`, opacity: 0 }
        ], {
          duration: Math.random() * 3000 + 2000,
          easing: 'cubic-bezier(0.1, 0.8, 0.2, 1)'
        });
        
        animation.onfinish = () => {
          if (element.parentNode) {
            element.parentNode.removeChild(element);
          }
        };
      }
    });
  </script>
</body>
</html>