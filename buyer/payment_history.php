<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/payment_processor.php';

require_role('buyer');
$user = current_user();
$pdo = get_db();

$paymentProcessor = new PaymentProcessor();
// Get payment history with order numbers
try {
    $stmt = $pdo->prepare("SELECT ph.*, o.order_number FROM payment_history ph LEFT JOIN orders o ON ph.order_id = o.id WHERE ph.user_id = ? ORDER BY ph.created_at DESC");
    $stmt->execute([$user['id']]);
    $paymentHistory = $stmt->fetchAll();
} catch (Exception $e) {
    $paymentHistory = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - AgriSea Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/ecommerce_farmers_fishers/assets/css/style.css">
    <style>
        .rating-stars {
            font-size: 2rem;
            cursor: pointer;
        }
        .star {
            color: #dee2e6;
            transition: color 0.2s;
        }
        .star:hover {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
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
            <li class="nav-item"><a href="/ecommerce_farmers_fishers/buyer/orders.php" class="nav-link">Orders</a></li>
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
        <div class="container-fluid p-4">
                <div class="row mb-3">
                    <div class="col-12">

                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="text-success"><i class="fas fa-history me-2"></i>Payment History</h3>
                            </div>
                            
                            <?php if (empty($paymentHistory)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>No payment history found.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Transaction ID</th>
                                                <th>Order #</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Date</th>
                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($paymentHistory as $payment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['transaction_id']); ?></td>
                                                <td>
                                                    <?php if (!empty($payment['order_number'])): ?>
                                                        <?php echo htmlspecialchars($payment['order_number']); ?>
                                                        <!-- Add rating link if order is delivered -->
                                                        <?php 
                                                        // Check if order is delivered and can be rated
                                                        try {
                                                            $stmt = $pdo->prepare("SELECT o.status, o.id, o.seller_id FROM orders o WHERE o.id = ? AND o.buyer_id = ?");
                                                            $stmt->execute([$payment['order_id'], $user['id']]);
                                                            $order = $stmt->fetch();
                                                            
                                                            if ($order && $order['status'] === 'delivered') {
                                                                // Check if already rated
                                                                $ratedStmt = $pdo->prepare("SELECT COUNT(*) FROM seller_ratings WHERE order_id = ?");
                                                                $ratedStmt->execute([$order['id']]);
                                                                $isRated = $ratedStmt->fetchColumn() > 0;
                                                                
                                                                if (!$isRated) {
                                                                    echo ' <a href="#" class="btn btn-sm btn-outline-primary rate-order" data-order-id="' . $order['id'] . '" data-seller-id="' . $order['seller_id'] . '">Rate</a>';
                                                                } else {
                                                                    echo ' <span class="badge bg-success">Rated</span>';
                                                                }
                                                            }
                                                        } catch (Exception $e) {
                                                            // Ignore errors
                                                        }
                                                        ?>
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                                <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($payment['created_at'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $payment['status'] === 'completed' ? 'success' : 
                                                             ($payment['status'] === 'pending' ? 'warning' : 
                                                             ($payment['status'] === 'failed' ? 'danger' : 'secondary')); 
                                                    ?>">
                                                        <?php echo ucfirst(htmlspecialchars($payment['status'])); ?>
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Rating modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const rateButtons = document.querySelectorAll('.rate-order');
            
            rateButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const orderId = this.getAttribute('data-order-id');
                    const sellerId = this.getAttribute('data-seller-id');
                    
                    // Create rating modal
                    const modal = document.createElement('div');
                    modal.className = 'modal fade';
                    modal.id = 'ratingModal';
                    modal.tabIndex = -1;
                    modal.innerHTML = `
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Rate Your Purchase</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="ratingForm">
                                        <input type="hidden" name="order_id" value="${orderId}">
                                        <input type="hidden" name="seller_id" value="${sellerId}">
                                        <div class="mb-3">
                                            <label class="form-label">Rating</label>
                                            <div class="rating-stars">
                                                <span class="star" data-rating="1">★</span>
                                                <span class="star" data-rating="2">★</span>
                                                <span class="star" data-rating="3">★</span>
                                                <span class="star" data-rating="4">★</span>
                                                <span class="star" data-rating="5">★</span>
                                            </div>
                                            <input type="hidden" name="rating" id="ratingInput" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="review" class="form-label">Review (Optional)</label>
                                            <textarea class="form-control" id="review" name="review" rows="3"></textarea>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-success" id="submitRating">Submit Rating</button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.body.appendChild(modal);
                    
                    // Initialize Bootstrap modal
                    const bootstrapModal = new bootstrap.Modal(modal);
                    bootstrapModal.show();
                    
                    // Handle star rating
                    const stars = modal.querySelectorAll('.star');
                    const ratingInput = document.getElementById('ratingInput');
                    
                    stars.forEach(star => {
                        star.addEventListener('click', function() {
                            const rating = this.getAttribute('data-rating');
                            ratingInput.value = rating;
                            
                            // Update star appearance
                            stars.forEach((s, index) => {
                                if (index < rating) {
                                    s.style.color = '#ffc107';
                                } else {
                                    s.style.color = '#dee2e6';
                                }
                            });
                        });
                    });
                    
                    // Handle form submission
                    document.getElementById('submitRating').addEventListener('click', function() {
                        if (!ratingInput.value) {
                            alert('Please select a rating');
                            return;
                        }
                        
                        const formData = new FormData(document.getElementById('ratingForm'));
                        
                        fetch('/ecommerce_farmers_fishers/buyer/rate.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Thank you for your rating!');
                                bootstrapModal.hide();
                                location.reload();
                            } else {
                                alert('Error: ' + (data.error || 'Failed to submit rating'));
                            }
                        })
                        .catch(error => {
                            alert('Error submitting rating');
                            console.error('Error:', error);
                        });
                    });
                    
                    // Remove modal when closed
                    modal.addEventListener('hidden.bs.modal', function() {
                        document.body.removeChild(modal);
                    });
                });
            });
        });
    </script>
</body>
</html>