<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only buyers may access
require_role('buyer');
$pdo = get_db();

// Try to fetch orders for current user if table exists
$user = current_user();
$orders = [];
try {
    $stmt = $pdo->prepare('SELECT o.*, GROUP_CONCAT(oi.product_name SEPARATOR ", ") as items, MIN(p.seller_id) as seller_id FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id LEFT JOIN products p ON oi.product_id = p.id WHERE o.user_id = ? GROUP BY o.id ORDER BY o.created_at DESC');
    $stmt->execute([(int)$user['id']]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    // Table might not exist or DB error; leave $orders empty
    error_log("Error fetching orders: " . $e->getMessage());
    $orders = [];
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Your Orders - AgriSea</title>
  <link href="/ecommerce_farmers_fishers/assets/css/style.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Your Orders</h2>
        </div>

        <?php if (empty($orders)): ?>
          <div class="alert alert-info">You have no orders yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead><tr><th>Order #</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th>Delivery Status</th><th>Action</th><th>Rate</th></tr></thead>
              <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                  <td><?= htmlspecialchars($o['id']) ?></td>
                  <td><?= htmlspecialchars($o['created_at'] ?? '') ?></td>
                  <td><?= htmlspecialchars($o['items'] ?? '') ?></td>
                  <td><?= htmlspecialchars($o['total'] ?? '') ?></td>
                  <td>
                    <span class="badge bg-<?php 
                      $status = strtolower(trim($o['status'] ?? ''));
                      echo $status === 'delivered' ? 'success' : 
                           ($status === 'processing' ? 'warning' : 
                           ($status === 'shipped' ? 'info' : 'secondary')); 
                    ?>">
                      <?= htmlspecialchars($o['status'] ?? '') ?>
                    </span>
                  </td>
                  <td>
                    <?php
                    // Get delivery status if available
                    $deliveryStatus = '';
                    $deliveryInfo = '';
                    if (!empty($o['delivery_id'])) {
                        try {
                            $stmt = $pdo->prepare('SELECT od.status, dp.name as partner_name FROM order_deliveries od LEFT JOIN delivery_partners dp ON od.delivery_partner_id = dp.id WHERE od.id = ?');
                            $stmt->execute([$o['delivery_id']]);
                            $delivery = $stmt->fetch();
                            if ($delivery) {
                                $deliveryStatus = $delivery['status'];
                                $deliveryInfo = $delivery['partner_name'] ?? 'N/A';
                            }
                        } catch (Exception $e) {
                            // Ignore errors
                        }
                    }
                    ?>
                    <?php if ($deliveryStatus): ?>
                      <span class="badge bg-<?php 
                        echo $deliveryStatus === 'delivered' ? 'success' : 
                             ($deliveryStatus === 'out_for_delivery' ? 'warning' : 
                             ($deliveryStatus === 'picked_up' ? 'info' : 'secondary')); 
                      ?>">
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $deliveryStatus)) ?? '') ?>
                      </span>
                      <?php if ($deliveryInfo): ?>
                        <small class="d-block text-muted"><?= htmlspecialchars($deliveryInfo) ?></small>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php 
                    if ($status === 'shipped'): ?>
                      <button class="btn btn-sm btn-success mark-received" data-order="<?= (int)$o['id'] ?>">
                        Mark as Received
                      </button>
                    <?php elseif ($status === 'delivered'): ?>
                      <span class="text-success">Received</span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($status === 'delivered'): ?>
                      <?php
                        // check if already rated
                        $rated = false;
                        try {
                          $ch = $pdo->prepare('SELECT COUNT(*) FROM seller_ratings WHERE order_id = ?');
                          $ch->execute([(int)$o['id']]);
                          $rated = ((int)$ch->fetchColumn() > 0);
                        } catch (Exception $e) { $rated = false; }
                      ?>
                      <?php if ($rated): ?>
                        <span class="text-success">Rated</span>
                      <?php else: ?>
                        <button class="btn btn-sm btn-primary rate-open" data-order="<?= (int)$o['id'] ?>" data-seller="<?= (int)($o['seller_id'] ?? 0) ?>">Rate</button>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <a href="/ecommerce_farmers_fishers/buyer/dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Rate modal and handler -->
  <div class="modal fade" id="rateModal" tabindex="-1" aria-labelledby="rateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="rateModalLabel">Rate Your Order</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="rateForm">
            <input type="hidden" name="order_id" id="rate_order_id">
            <input type="hidden" name="seller_id" id="rate_seller_id">
            <div class="mb-3">
              <label class="form-label">Rating</label>
              <div id="starGroup">
                <button type="button" class="btn btn-light star" data-value="1">★</button>
                <button type="button" class="btn btn-light star" data-value="2">★</button>
                <button type="button" class="btn btn-light star" data-value="3">★</button>
                <button type="button" class="btn btn-light star" data-value="4">★</button>
                <button type="button" class="btn btn-light star" data-value="5">★</button>
              </div>
            </div>
            <div class="mb-3">
              <label for="rate_review" class="form-label">Review (optional)</label>
              <textarea id="rate_review" name="review" class="form-control" rows="3" placeholder="Share your experience..."></textarea>
            </div>
            <div class="text-end">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Submit Rating</button>
            </div>
          </form>
          <div id="rateMsg" class="mt-2"></div>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function(){
      var currentRating = 0;
      function setStars(n){
        currentRating = n;
        document.querySelectorAll('#starGroup .star').forEach(function(b){
          var v = parseInt(b.getAttribute('data-value'));
          b.classList.toggle('btn-warning', v <= n);
          b.classList.toggle('btn-light', v > n);
        });
      }

      document.addEventListener('DOMContentLoaded', function(){
        console.log('DOM Loaded, checking for action buttons...');
        
        // Check for mark-received buttons
        const markReceivedButtons = document.querySelectorAll('.mark-received');
        console.log('Found', markReceivedButtons.length, 'mark-received buttons');
        
        // Check for rate-open buttons
        const rateOpenButtons = document.querySelectorAll('.rate-open');
        console.log('Found', rateOpenButtons.length, 'rate-open buttons');
        
        // open modal from buttons
        document.querySelectorAll('.rate-open').forEach(function(btn){
          console.log('Found rate-open button:', btn);
          btn.addEventListener('click', function(){
            var order = btn.getAttribute('data-order');
            var seller = btn.getAttribute('data-seller');
            console.log('Opening rating modal for order:', order, 'seller:', seller);
            document.getElementById('rate_order_id').value = order;
            document.getElementById('rate_seller_id').value = seller;
            document.getElementById('rate_review').value = '';
            document.getElementById('rateMsg').textContent = '';
            setStars(5); // default to 5
            var modal = new bootstrap.Modal(document.getElementById('rateModal'));
            modal.show();
          });
        });

        // star clicks
        document.querySelectorAll('#starGroup .star').forEach(function(s){
          s.addEventListener('click', function(){ setStars(parseInt(this.getAttribute('data-value'))); });
        });

        // submit rating
        document.getElementById('rateForm').addEventListener('submit', function(e){
          e.preventDefault();
          var form = e.target;
          var data = new FormData(form);
          data.append('rating', currentRating);
          fetch('/ecommerce_farmers_fishers/buyer/rate.php', { method:'POST', body: data, credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(json){
              if (json && json.success) {
                document.getElementById('rateMsg').textContent = 'Thanks for your rating!';
                // update the table row: find button with this order id and replace with 'Rated'
                var orderId = document.getElementById('rate_order_id').value;
                var btn = document.querySelector('.rate-open[data-order="'+orderId+'"]');
                if (btn) {
                  var span = document.createElement('span'); span.className='text-success'; span.textContent='Rated';
                  btn.parentNode.replaceChild(span, btn);
                }
                setTimeout(function(){ var m = bootstrap.Modal.getInstance(document.getElementById('rateModal')); if (m) m.hide(); }, 900);
              } else {
                document.getElementById('rateMsg').textContent = json.error || 'Error submitting rating';
              }
            }).catch(function(err){ console.error(err); document.getElementById('rateMsg').textContent = 'Server error'; });
        });

        // Add mark as received functionality
        document.querySelectorAll('.mark-received').forEach(function(btn){
          console.log('Found mark-received button:', btn);
          btn.addEventListener('click', function(){
            const orderId = btn.getAttribute('data-order');
            console.log('Marking order as received:', orderId);
            if (!confirm('Are you sure you want to mark this order as received?')) {
              return;
            }
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            fetch('/ecommerce_farmers_fishers/api/mark_order_received.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                order_id: orderId
              })
            })
            .then(function(r){ 
              console.log('Response received:', r);
              return r.json(); 
            })
            .then(function(json){
              console.log('JSON response:', json);
              if (json && json.success) {
                alert('Order marked as received successfully!');
                location.reload();
              } else {
                alert(json.error || 'Error marking order as received');
                btn.disabled = false;
                btn.innerHTML = 'Mark as Received';
              }
            }).catch(function(err){ 
              console.error('Error:', err); 
              alert('Server error'); 
              btn.disabled = false;
              btn.innerHTML = 'Mark as Received';
            });
          });
        });
        
      });
    })();
  </script>
</body>
</html>