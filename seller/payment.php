<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('seller');
$user = current_user();
$pdo = get_db();

// Fetch complete user data if email/phone are missing
if (!isset($user['email']) || !isset($user['phone'])) {
    try {
        $stmt = $pdo->prepare('SELECT first_name, last_name, email, phone FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $fullUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fullUser) {
            $user = array_merge($user, $fullUser);
        }
    } catch (Exception $e) {
        // If we can't fetch user data, continue with what we have
    }
}

// Get subscription ID from query parameter
$subscriptionId = $_GET['subscription'] ?? null;
$planType = $_GET['plan'] ?? null;

if (!$subscriptionId && !$planType) {
    // Redirect to subscription plans page
    header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=subscription');
    exit;
}

// Get subscription details
$subscription = null;
$planDetails = null;

if ($subscriptionId) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM subscriptions WHERE id = ? AND user_id = ?');
        $stmt->execute([$subscriptionId, $user['id']]);
        $subscription = $stmt->fetch();
        
        if (!$subscription) {
            header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=subscription');
            exit;
        }
        
        $planType = $subscription['plan_type'];
    } catch (Exception $e) {
        header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=subscription');
        exit;
    }
} else if ($planType) {
    // If we have a plan type but no subscription ID, create a pending subscription
    try {
        // Validate plan type
        $validPlans = ['premium' => ['name' => 'Premium Plan', 'price' => 299], 'pro' => ['name' => 'Pro Plan', 'price' => 599]];
        if (!isset($validPlans[$planType])) {
            header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=subscription');
            exit;
        }
        
        // Create a pending subscription
        $endDate = date('Y-m-d H:i:s', strtotime('+1 month'));
        
        $insertStmt = $pdo->prepare('
            INSERT INTO subscriptions (user_id, plan_type, status, start_date, end_date, created_at) 
            VALUES (?, ?, ?, NOW(), ?, NOW())
        ');
        $insertStmt->execute([
            $user['id'], 
            $planType, 
            'pending', 
            $endDate
        ]);
        
        $subscriptionId = $pdo->lastInsertId();
        $subscription = [
            'id' => $subscriptionId,
            'plan_type' => $planType,
            'status' => 'pending'
        ];
    } catch (Exception $e) {
        error_log('Error creating subscription: ' . $e->getMessage());
        header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=subscription');
        exit;
    }
}

// Define plan prices
$plans = [
    'premium' => ['name' => 'Premium Plan', 'price' => 299],
    'pro' => ['name' => 'Pro Plan', 'price' => 599]
];

if (!isset($plans[$planType])) {
    header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=subscription');
    exit;
}

$planDetails = $plans[$planType];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - AgriSea Seller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment for <?= htmlspecialchars($planDetails['name']) ?></h4>
                                <?php $displayType = get_seller_type_display($pdo); ?>
                                <?php if (!empty($displayType)): ?>
                                    <div class="small">Account Type: <?= htmlspecialchars($displayType) ?></div>
                                <?php endif; ?>
                            </div>
                            <a href="/ecommerce_farmers_fishers/seller/dashboard.php?tab=subscription" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Back to Subscription Plans
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Billing Information</h5>
                                <p>
                                    <strong>Name:</strong> <?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?><br>
                                    <strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? 'N/A') ?><br>
                                    <strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'N/A') ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5>Order Summary</h5>
                                <table class="table">
                                    <tr>
                                        <td><?= htmlspecialchars($planDetails['name']) ?></td>
                                        <td class="text-end">₱<?= number_format($planDetails['price'], 2) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total</strong></td>
                                        <td class="text-end"><strong>₱<?= number_format($planDetails['price'], 2) ?></strong></td>
                                    </tr>
                                </table>
                                <?php 
                                // Check if user has an active subscription
                                $subscriptionLimitReached = false;
                                try {
                                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM subscriptions WHERE user_id = ? AND status = "active" AND end_date >= NOW()');
                                    $stmt->execute([$user['id']]);
                                    $subscriptionCount = (int)$stmt->fetchColumn();
                                    
                                    if ($subscriptionCount > 0) {
                                        $subscriptionLimitReached = true;
                                    }
                                } catch (Exception $e) {
                                    // Ignore errors
                                }
                                ?>
                                <div class="alert alert-success mt-3">
                                    <?php if ($subscriptionLimitReached): ?>
                                        You have already subscribed this month.
                                    <?php else: ?>
                                        You can subscribe now.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <h5>Payment Method</h5>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="card payment-method" onclick="selectPaymentMethod('gcash')">
                                            <div class="card-body text-center">
                                                <i class="fab fa-google-pay fa-2x mb-2"></i>
                                                <div>GCash</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card payment-method" onclick="selectPaymentMethod('paymaya')">
                                            <div class="card-body text-center">
                                                <i class="fas fa-wallet fa-2x mb-2"></i>
                                                <div>PayMaya</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card payment-method" onclick="selectPaymentMethod('card')">
                                            <div class="card-body text-center">
                                                <i class="fas fa-credit-card fa-2x mb-2"></i>
                                                <div>Credit/Debit Card</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="paymentForm" class="mt-4" style="display: none;">
                                    <div class="mb-3">
                                        <label for="accountNumber" class="form-label">Account Number</label>
                                        <input type="text" class="form-control" id="accountNumber" placeholder="Enter account number">
                                    </div>
                                    
                                    <div class="mb-3" id="cardDetails" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="cardNumber" class="form-label">Card Number</label>
                                                <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="expiryDate" class="form-label">Expiry Date</label>
                                                <input type="text" class="form-control" id="expiryDate" placeholder="MM/YY">
                                            </div>
                                            <div class="col-md-3">
                                                <label for="cvv" class="form-label">CVV</label>
                                                <input type="text" class="form-control" id="cvv" placeholder="123">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button class="btn btn-success w-100" onclick="processPayment()">
                                        <i class="fas fa-lock me-2"></i>Pay ₱<?= number_format($planDetails['price'], 2) ?>
                                    </button>
                                </div>
                                
                                <!-- Payment Status Display -->
                                <div id="paymentStatus" class="mt-4"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedMethod = '';
        
        function selectPaymentMethod(method) {
            selectedMethod = method;
            
            // Highlight selected method
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('border-success');
            });
            event.currentTarget.classList.add('border-success');
            
            // Show payment form
            document.getElementById('paymentForm').style.display = 'block';
            
            // Show/hide card details based on method
            if (method === 'card') {
                document.getElementById('cardDetails').style.display = 'block';
            } else {
                document.getElementById('cardDetails').style.display = 'none';
            }
        }
        
        function processPayment() {
            if (!selectedMethod) {
                alert('Please select a payment method');
                return;
            }
            
            // Check if subscription ID is available
            const subscriptionId = '<?= $subscriptionId ?>';
            if (!subscriptionId) {
                alert('Subscription ID is missing. Please try again.');
                return;
            }
            
            // In a real implementation, this would process the payment through a gateway
            // For now, we'll simulate a successful payment
            
            // Create form data
            const formData = new FormData();
            formData.append('subscription_id', subscriptionId);
            formData.append('payment_method', selectedMethod);
            formData.append('amount', '<?= $planDetails['price'] ?>');
            
            fetch('/ecommerce_farmers_fishers/seller/process_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI without page refresh
                    document.getElementById('paymentStatus').innerHTML = `
                        <div class="alert alert-success">
                            <h4><i class="fas fa-check-circle"></i> Payment Successful!</h4>
                            <p>Your subscription is now active.</p>
                            <p>Transaction ID: ${data.transaction_id}</p>
                            <button class="btn btn-success" onclick="window.location.href='/ecommerce_farmers_fishers/seller/dashboard.php?tab=subscription'">Continue to Dashboard</button>
                        </div>
                    `;
                    // Update payment button
                    document.querySelector('button[onclick="processPayment()"]').disabled = true;
                    document.querySelector('button[onclick="processPayment()"]').textContent = 'Payment Processed';
                } else {
                    // Handle subscription limit error
                    if (data.error && data.error.includes('only make one subscription per month')) {
                        document.getElementById('paymentStatus').innerHTML = `
                            <div class="alert alert-danger">
                                <p>You have already subscribed this month.</p>
                                <button class="btn btn-primary" onclick="window.location.href='/ecommerce_farmers_fishers/seller/dashboard.php?tab=subscription'">Back to Subscription Plans</button>
                            </div>
                        `;
                        // Disable payment button
                        document.querySelector('button[onclick="processPayment()"]').disabled = true;
                        document.querySelector('button[onclick="processPayment()"]').textContent = 'You have already subscribed this month.';
                    } else {
                        alert('Error: ' + (data.error || 'Payment failed. Please try again.'));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Payment failed. Please try again.');
            });
        }
    </script>
    
    <style>
        .payment-method {
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-method:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .border-success {
            border: 2px solid #198754 !important;
        }
    </style>
</body>
</html>