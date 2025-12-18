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
    redirect_to('/ecommerce_farmers_fishers/buyer/cart.php');
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
        redirect_to('/ecommerce_farmers_fishers/buyer/orders.php');
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
    redirect_to('/ecommerce_farmers_fishers/buyer/orders.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - AgriSea Marketplace</title>
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
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment for Order #<?php echo htmlspecialchars($order['order_number']); ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Billing Information</h5>
                                <p>
                                    <strong>Name:</strong> <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?><br>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? ''); ?><br>
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?><br>
                                    <strong>Address:</strong> <?php echo htmlspecialchars(($order['street_address'] ?? '') . ', ' . ($order['city'] ?? '') . ', ' . ($order['province'] ?? '')); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5>Order Summary</h5>
                                <table class="table">
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?> (x<?php echo $item['quantity']; ?>)</td>
                                        <td class="text-end">₱<?php echo number_format($item['total'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td><strong>Total</strong></td>
                                        <td class="text-end"><strong>₱<?php echo number_format($order['total'], 2); ?></strong></td>
                                    </tr>
                                </table>
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
                                        <i class="fas fa-lock me-2"></i>Pay ₱<?php echo number_format($order['total'], 2); ?>
                                    </button>
                                </div>
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
                showToast('Payment Error', 'Please select a payment method', 'warning');
                return;
            }
            
            // Get account number
            const accountNumber = document.getElementById('accountNumber').value;
            if (!accountNumber) {
                showToast('Payment Error', 'Please enter your account number', 'warning');
                return;
            }
            
            // Validate card details if card payment is selected
            if (selectedMethod === 'card') {
                const cardNumber = document.getElementById('cardNumber').value;
                const expiryDate = document.getElementById('expiryDate').value;
                const cvv = document.getElementById('cvv').value;
                
                if (!cardNumber || !expiryDate || !cvv) {
                    showToast('Payment Error', 'Please fill in all card details', 'warning');
                    return;
                }
            }
            
            // Disable payment button to prevent double submissions
            const payButton = document.querySelector('.btn-success');
            const originalButtonText = payButton.innerHTML;
            payButton.disabled = true;
            payButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing Payment...';
            
            // In a real implementation, this would process the payment through a gateway
            // For now, we'll simulate a successful payment
            
            // Create form data
            const formData = new FormData();
            formData.append('order_id', '<?php echo $orderId; ?>');
            formData.append('payment_method', selectedMethod);
            formData.append('amount', '<?php echo $order['total']; ?>');
            
            fetch('/ecommerce_farmers_fishers/buyer/process_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const confirmationUrl = '/ecommerce_farmers_fishers/buyer/orders.php';
                    showToast('Payment Successful!', 'Your payment has been processed successfully.', 'success', confirmationUrl);
                    
                    // Redirect to confirmation page after a short delay
                    setTimeout(() => {
                        window.location.href = confirmationUrl;
                    }, 3000);
                } else {
                    // Re-enable button and show error
                    payButton.disabled = false;
                    payButton.innerHTML = originalButtonText;
                    showToast('Payment Failed', data.error || 'Payment failed. Please try again.', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Re-enable button and show error
                payButton.disabled = false;
                payButton.innerHTML = originalButtonText;
                showToast('Payment Failed', 'Payment failed. Please try again.', 'danger');
            });
        }
        
        // Toast notification function
        function showToast(title, message, type, redirectUrl = null) {
            // Create toast container if it doesn't exist
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.style.position = 'fixed';
                toastContainer.style.top = '20px';
                toastContainer.style.right = '20px';
                toastContainer.style.zIndex = '9999';
                document.body.appendChild(toastContainer);
            }
            
            // Create toast element
            const toastEl = document.createElement('div');
            toastEl.className = `alert alert-${type} alert-dismissible fade show`;
            toastEl.style.minWidth = '300px';
            toastEl.style.maxWidth = '90vw';
            
            let toastBody = `<strong>${title}</strong><br>${message}`;
            if (redirectUrl) {
                toastBody += `<div class="mt-2 pt-2 border-top"><a href="${redirectUrl}" class="btn btn-sm btn-light"><strong>View Order</strong></a></div>`;
            }
            
            toastEl.innerHTML = `
                ${toastBody}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Add to container
            toastContainer.appendChild(toastEl);
            
            // Auto remove after 5 seconds if no redirect
            if (!redirectUrl) {
                setTimeout(() => {
                    if (toastEl.parentNode) {
                        toastEl.parentNode.removeChild(toastEl);
                    }
                }, 5000);
            }
        }
        
        // Add input validation
        document.addEventListener('DOMContentLoaded', function() {
            // Format card number input
            const cardNumberInput = document.getElementById('cardNumber');
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = value.replace(/(.{4})/g, '$1 ').trim();
                    e.target.value = value;
                });
            }
            
            // Format expiry date input
            const expiryInput = document.getElementById('expiryDate');
            if (expiryInput) {
                expiryInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    e.target.value = value;
                });
            }
            
            // Format CVV input
            const cvvInput = document.getElementById('cvv');
            if (cvvInput) {
                cvvInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
                });
            }
        });
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