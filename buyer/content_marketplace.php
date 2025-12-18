<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only buyers may access
require_role('buyer');
$user = current_user();
$pdo = get_db();

// Handle AJAX request for live search
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    
    $search_term = $_GET['search'] ?? '';
    
    // Preload seller ratings averages
    $sellerRatings = [];
    try {
      $r = $pdo->query("SELECT seller_id, AVG(rating) as avg_rating, COUNT(*) as cnt FROM seller_ratings GROUP BY seller_id");
      foreach ($r->fetchAll() as $row) {
        $sellerRatings[$row['seller_id']] = ['avg' => round($row['avg_rating'],2), 'count' => (int)$row['cnt']];
      }
    } catch (Exception $e) {
      // ignore if table missing
    }
    
    try {
        // Regular users only see published products
        if ($search_term) {
            $stmt = $pdo->prepare("SELECT p.id, p.name, p.price, p.stock, p.seller_id, p.description, u.first_name, u.last_name, pi.image_path, c.name as category_name FROM products p LEFT JOIN users u ON p.seller_id = u.id LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'published' AND (p.name LIKE ? OR p.description LIKE ? OR CAST(p.price AS CHAR) LIKE ? OR c.name LIKE ?) ORDER BY p.created_at DESC LIMIT 100");
            $stmt->execute(["%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%"]);
        } else {
            $stmt = $pdo->query("SELECT p.id, p.name, p.price, p.stock, p.seller_id, p.description, u.first_name, u.last_name, pi.image_path, c.name as category_name FROM products p LEFT JOIN users u ON p.seller_id = u.id LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'published' ORDER BY p.created_at DESC LIMIT 100");
        }
        $products = $stmt->fetchAll();
        
        // Add seller ratings to each product
        foreach ($products as &$product) {
            $sellerId = $product['seller_id'];
            if (isset($sellerRatings[$sellerId])) {
                $product['seller_rating'] = $sellerRatings[$sellerId];
            }
        }
        
        // Return JSON response
        echo json_encode(['success' => true, 'products' => $products, 'user_role' => $user['role'] ?? 'guest']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Fetch products with seller name where possible
// For regular users, only show published products
$search_term = $_GET['search'] ?? '';
try {
  if ($search_term) {
    $stmt = $pdo->prepare("SELECT p.id, p.name, p.price, p.stock, p.seller_id, p.description, u.first_name, u.last_name, pi.image_path, c.name as category_name FROM products p LEFT JOIN users u ON p.seller_id = u.id LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'published' AND (p.name LIKE ? OR p.description LIKE ? OR CAST(p.price AS CHAR) LIKE ? OR c.name LIKE ?) ORDER BY p.created_at DESC LIMIT 100");
    $stmt->execute(["%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%"]);
  } else {
    $stmt = $pdo->query("SELECT p.id, p.name, p.price, p.stock, p.seller_id, p.description, u.first_name, u.last_name, pi.image_path, c.name as category_name FROM products p LEFT JOIN users u ON p.seller_id = u.id LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'published' ORDER BY p.created_at DESC LIMIT 100");
  }
  $products = $stmt->fetchAll();
} catch (Exception $e) {
  $products = [];
}

// Preload seller ratings averages
$sellerRatings = [];
try {
  $r = $pdo->query("SELECT seller_id, AVG(rating) as avg_rating, COUNT(*) as cnt FROM seller_ratings GROUP BY seller_id");
  foreach ($r->fetchAll() as $row) {
    $sellerRatings[$row['seller_id']] = ['avg' => round($row['avg_rating'],2), 'count' => (int)$row['cnt']];
  }
} catch (Exception $e) {
  // ignore if table missing
}
?>

<div class="row">
    <div class="col-12">
        <h3 class="section-title">Marketplace</h3>
        
        <!-- Search Form -->
        <form method="GET" class="mb-4" id="searchForm">
            <div class="input-group">
                <input type="text" class="form-control" id="searchInput" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_term); ?>">
            </div>
        </form>
        
        <div id="productsContainer">
            <?php if (empty($products)): ?>
                <div class="alert alert-info">No products found.</div>
            <?php else: ?>
                <div class="row" style="margin-left: 0; margin-right: 0; max-width: 100vw; overflow-x: hidden;">
                    <?php foreach ($products as $p): ?>
                        <div class="col-md-4 mb-3" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
                            <div class="card h-100" style="max-width: 100vw; overflow-x: hidden;">
                                <div class="card-body d-flex flex-column" style="max-width: 100vw; overflow-x: hidden;">
                                    <?php if (!empty($p['image_path']) && file_exists(__DIR__ . '/../' . $p['image_path'])): ?>
                                        <img src="/ecommerce_farmers_fishers/<?php echo htmlspecialchars($p['image_path']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="card-img-top mb-2" style="height: 150px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center mb-2" style="height: 150px;">
                                            <i class="fas fa-image text-muted fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h6 class="card-title"><?php echo htmlspecialchars($p['name']); ?></h6>
                                    <p class="card-text text-muted small mb-2"><?php echo htmlspecialchars(substr($p['description'] ?? '', 0, 120)); ?></p>
                                    <div class="mt-auto d-flex justify-content-between align-items-center" style="max-width: 100vw; overflow-x: hidden;">
                                        <div style="max-width: 100vw; overflow-x: hidden;">
                                            <strong>₱<?php echo number_format($p['price'],2); ?></strong>
                                            <div class="text-muted small">Stock: <?php echo (int)$p['stock']; ?></div>
                                            <?php $sr = $sellerRatings[$p['seller_id']] ?? null; ?>
                                            <?php if ($sr): ?>
                                                <div class="small">
                                                    <span class="text-warning">
                                                        <?php
                                                        $avg = (float)$sr['avg'];
                                                        $filled = floor($avg);
                                                        $half = ($avg - $filled) >= 0.5;
                                                        for ($i=1;$i<=5;$i++) {
                                                            if ($i <= $filled) echo '★';
                                                            elseif ($i === $filled+1 && $half) echo '☆';
                                                            else echo '☆';
                                                        }
                                                        ?>
                                                    </span>
                                                    <span class="text-muted ms-2"><?php echo htmlspecialchars(number_format($avg,1)); ?> (<?php echo $sr['count']; ?>)</span>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-muted small">No ratings yet</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end" style="max-width: 100vw; overflow-x: hidden;">
                                            <div class="text-muted small">Seller<br><?php echo htmlspecialchars((trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''))) ?: '—'); ?></div>
                                            <?php if (isset($user['role']) && $user['role'] === 'buyer'): ?>
                                                <button class="btn btn-sm btn-primary mt-2" onclick="marketplaceAddToCart(<?php echo (int)$p['id']; ?>)">Add to Cart</button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-primary mt-2" disabled>Details</button>
                                            <?php endif; ?>
                                        </div>
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

<script>
// Live search functionality
let searchTimeout;
const searchInput = document.getElementById('searchInput');

if (searchInput) {
  searchInput.addEventListener('input', function() {
    // Clear previous timeout
    clearTimeout(searchTimeout);
    
    // Set new timeout
    searchTimeout = setTimeout(() => {
      const searchTerm = this.value.trim();
      
      // Make AJAX request
      fetch(`/ecommerce_farmers_fishers/buyer/content_marketplace.php?ajax=1&search=${encodeURIComponent(searchTerm)}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            updateProductDisplay(data.products, data.user_role);
          }
        })
        .catch(error => {
          console.error('Search error:', error);
        });
    }, 300); // 300ms delay
  });
}

function updateProductDisplay(products, userRole) {
  const container = document.getElementById('productsContainer');
  
  if (products.length === 0) {
    container.innerHTML = '<div class="alert alert-info">No products found.</div>';
    return;
  }
  
  let html = '<div class="row" style="margin-left: 0; margin-right: 0; max-width: 100vw; overflow-x: hidden;">';
  
  products.forEach(p => {
    html += `
      <div class="col-md-4 mb-3" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
        <div class="card h-100" style="max-width: 100vw; overflow-x: hidden;">
          <div class="card-body d-flex flex-column" style="max-width: 100vw; overflow-x: hidden;">
    `;
    
    if (p.image_path && p.image_path !== '') {
      html += `<img src="/ecommerce_farmers_fishers/${p.image_path}" alt="${escapeHtml(p.name)}" class="card-img-top mb-2" style="height: 150px; object-fit: cover;">`;
    } else {
      html += `
        <div class="bg-light d-flex align-items-center justify-content-center mb-2" style="height: 150px;">
          <i class="fas fa-image text-muted fa-2x"></i>
        </div>
      `;
    }
    
    html += `
            <h6 class="card-title">${escapeHtml(p.name)}</h6>
            <p class="card-text text-muted small mb-2">${escapeHtml(p.description ? p.description.substring(0, 120) : '')}</p>
            <div class="mt-auto d-flex justify-content-between align-items-center" style="max-width: 100vw; overflow-x: hidden;">
              <div style="max-width: 100vw; overflow-x: hidden;">
                <strong>₱${parseFloat(p.price).toFixed(2)}</strong>
                <div class="text-muted small">Stock: ${parseInt(p.stock)}</div>
    `;
    
    // Add seller ratings if available
    if (p.seller_rating) {
      const avgRating = parseFloat(p.seller_rating.avg);
      const ratingCount = parseInt(p.seller_rating.count);
      
      // Generate star rating display
      let starsHtml = '';
      const filledStars = Math.floor(avgRating);
      const halfStar = (avgRating - filledStars) >= 0.5;
      
      for (let i = 1; i <= 5; i++) {
        if (i <= filledStars) {
          starsHtml += '★';
        } else if (i === filledStars + 1 && halfStar) {
          starsHtml += '☆';
        } else {
          starsHtml += '☆';
        }
      }
      
      html += `
        <div class="small">
          <span class="text-warning">${starsHtml}</span>
          <span class="text-muted ms-2">${avgRating.toFixed(1)} (${ratingCount})</span>
        </div>
      `;
    } else {
      html += '<div class="text-muted small">No ratings yet</div>';
    }
    
    html += `
              </div>
              <div class="text-end" style="max-width: 100vw; overflow-x: hidden;">
                <div class="text-muted small">Seller<br>${escapeHtml((p.first_name && p.last_name) ? p.first_name + ' ' + p.last_name : '—')}</div>
    `;
    
    // Add appropriate buttons based on user role
    if (userRole === 'buyer') {
      html += `
                <button class="btn btn-sm btn-primary mt-2" onclick="marketplaceAddToCart(${parseInt(p.id)})">Add to Cart</button>`;
    } else {
      html += `
                <button class="btn btn-sm btn-outline-primary mt-2" disabled>Details</button>`;
    }
    html += `
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  });
  
  html += '</div>';
  container.innerHTML = html;
}

// Helper function to escape HTML
function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  
  return text.toString().replace(/[&<>"]/g, function(m) { return map[m]; });
}

// Add to cart function specifically for the marketplace embedded in dashboard
async function marketplaceAddToCart(productId) {
  try {
    const response = await fetch('/ecommerce_farmers_fishers/buyer/add_to_cart.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `product_id=${productId}&quantity=1`
    });
    
    const data = await response.json();
    
    // If user is not logged in, show login prompt
    if (data.login_required) {
      const loginModal = document.createElement('div');
      loginModal.className = 'modal fade';
      loginModal.id = 'loginPromptModal';
      loginModal.tabIndex = '-1';
      loginModal.innerHTML = `
        <div class="modal-dialog" style="max-width: 100vw; margin: 1rem;">
          <div class="modal-content" style="max-width: 100vw; overflow-x: hidden;">
            <div class="modal-header" style="max-width: 100vw; overflow-x: hidden;">
              <h5 class="modal-title">Login Required</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-width: 100vw; overflow-x: hidden;">
              <p>Please log in or register to continue shopping.</p>
            </div>
            <div class="modal-footer" style="max-width: 100vw; overflow-x: hidden;">
              <a href="/ecommerce_farmers_fishers/buyer/dashboard.php" class="btn btn-primary">Login</a>
              <a href="/ecommerce_farmers_fishers/buyer/dashboard.php" class="btn btn-outline-primary">Register</a>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(loginModal);
      
      const modal = new bootstrap.Modal(loginModal);
      modal.show();
      
      // Remove modal from DOM when closed
      loginModal.addEventListener('hidden.bs.modal', function () {
        document.body.removeChild(loginModal);
      });
      
      return;
    }
    
    // Show notification
    const notification = document.createElement('div');
    notification.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger') + ' alert-dismissible fade show position-fixed';
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 90vw;';
    notification.innerHTML = `
      ${data.message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 3000);
    
    // Update cart count if successful
    if (data.success) {
      updateCartCount();
    }
  } catch (error) {
    console.error('Error adding to cart:', error);
    
    // Show error notification
    const notification = document.createElement('div');
    notification.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 90vw;';
    notification.innerHTML = `
      Failed to add product to cart. Please try again.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 3000);
  }
}

// Update cart count badge
async function updateCartCount() {
  try {
    const response = await fetch('/ecommerce_farmers_fishers/buyer/cart_count.php');
    const data = await response.json();
    
    if (data.success) {
      const countElement = document.querySelector('.cart-count');
      if (countElement) {
        const count = data.count;
        countElement.textContent = count;
        countElement.style.display = count > 0 ? 'block' : 'none';
      }
    }
  } catch (error) {
    console.error('Error updating cart count:', error);
  }
}
</script>