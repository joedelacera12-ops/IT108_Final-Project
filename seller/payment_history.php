<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/payment_processor.php';

require_role('seller');
$user = current_user();
$pdo = get_db();
$paymentProcessor = new PaymentProcessor();
$paymentHistory = $paymentProcessor->getPaymentHistory($user['id']);
// Get seller subscription
$subscription = $paymentProcessor->getSellerSubscription($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - AgriSea Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
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
                            <div>
                                <a class="navbar-brand text-white" href="#">Seller Dashboard</a>
                                <?php $displayType = get_seller_type_display($pdo); ?>
                                <?php if (!empty($displayType)): ?>
                                    <div class="small text-white">Account Type: <?= htmlspecialchars($displayType) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="collapse navbar-collapse">
                                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                                    <li class="nav-item"><a class="nav-link text-white" href="dashboard.php">Overview</a></li>
                                    <li class="nav-item"><a class="nav-link text-white" href="add_product.php">Add Product</a></li>
                                    <li class="nav-item"><a class="nav-link text-white active" href="payment_history.php">Payment History</a></li>
                                    <li class="nav-item"><a class="nav-link text-white" href="edit_product.php">Manage Products</a></li>
                                </ul>
                            </div>
                        </nav>
                    </div>
                </div>

                <!-- Subscription Status -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm p-4">
                            <h4 class="text-success mb-3"><i class="fas fa-crown me-2"></i>Subscription Status</h4>
                            
                            <?php if ($subscription): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Plan:</strong> <?php echo ucfirst(htmlspecialchars($subscription['plan_type'])); ?></p>
                                        <p><strong>Amount:</strong> ₱<?php echo number_format($subscription['amount'], 2); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Status:</strong> 
                                            <span class="badge bg-<?php 
                                                echo $subscription['status'] === 'active' ? 'success' : 
                                                     ($subscription['status'] === 'expired' ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo ucfirst(htmlspecialchars($subscription['status'])); ?>
                                            </span>
                                        </p>
                                        <p><strong>Valid Until:</strong> <?php echo date('M j, Y', strtotime($subscription['end_date'])); ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($subscription['status'] !== 'active'): ?>
                                    <div class="mt-3">
                                        <a href="#" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#renewSubscriptionModal">
                                            <i class="fas fa-redo me-2"></i>Renew Subscription
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-circle me-2"></i>You don't have an active subscription. Please subscribe to continue selling.
                                </div>
                                <div class="mt-3">
                                    <a href="#" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#subscribeModal">
                                        <i class="fas fa-crown me-2"></i>Subscribe Now
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="text-success"><i class="fas fa-history me-2"></i>Payment History</h4>
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
                                                <th>Type</th>
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
                                                    <?php if (!empty($payment['subscription_plan'])): ?>
                                                        Subscription (<?php echo ucfirst(htmlspecialchars($payment['subscription_plan'])); ?>)
                                                    <?php elseif (!empty($payment['order_number'])): ?>
                                                        Order Payment
                                                    <?php else: ?>
                                                        Other
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

    <!-- Subscribe Modal -->
    <div class="modal fade" id="subscribeModal" tabindex="-1" aria-labelledby="subscribeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="subscribeModalLabel">Subscribe to AgriSea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="subscribeForm">
                        <div class="mb-3">
                            <label class="form-label">Subscription Plan</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="plan" id="monthly" value="monthly" checked>
                                <label class="form-check-label" for="monthly">
                                    Monthly Plan - ₱499.00/month
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="plan" id="yearly" value="yearly">
                                <label class="form-check-label" for="yearly">
                                    Yearly Plan - ₱4,999.00/year (Save ₱989.00)
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="gcash">GCash</option>
                                <option value="paymaya">PayMaya</option>
                                <option value="card">Debit/Credit Card</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" class="text-success">Terms of Service</a> and <a href="#" class="text-success">Privacy Policy</a>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="subscribeBtn">
                        <i class="fas fa-crown me-2"></i>Subscribe Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Renew Subscription Modal -->
    <div class="modal fade" id="renewSubscriptionModal" tabindex="-1" aria-labelledby="renewSubscriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="renewSubscriptionModalLabel">Renew Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Renew your subscription to continue selling on AgriSea Marketplace.</p>
                    
                    <form id="renewForm">
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="gcash">GCash</option>
                                <option value="paymaya">PayMaya</option>
                                <option value="card">Debit/Credit Card</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="renewBtn">
                        <i class="fas fa-redo me-2"></i>Renew Subscription
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Subscribe button handler
            document.getElementById('subscribeBtn').addEventListener('click', function() {
                const form = document.getElementById('subscribeForm');
                if (form.checkValidity()) {
                    // In a real implementation, this would process the subscription
                    alert('Subscription processed successfully!');
                    // Close modal and refresh page
                    bootstrap.Modal.getInstance(document.getElementById('subscribeModal')).hide();
                    location.reload();
                } else {
                    form.reportValidity();
                }
            });
            
            // Renew button handler
            document.getElementById('renewBtn').addEventListener('click', function() {
                const form = document.getElementById('renewForm');
                if (form.checkValidity()) {
                    // In a real implementation, this would process the renewal
                    alert('Subscription renewed successfully!');
                    // Close modal and refresh page
                    bootstrap.Modal.getInstance(document.getElementById('renewSubscriptionModal')).hide();
                    location.reload();
                } else {
                    form.reportValidity();
                }
            });
        });
    </script>
</body>
</html>