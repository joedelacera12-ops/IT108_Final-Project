<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role('buyer');
$pdo = get_db();
$user = current_user();

// Try to fetch favorites if table exists
$favorites = [];
try {
  $stmt = $pdo->prepare('SELECT f.id, f.product_id, p.name as product_name, p.price FROM favorites f LEFT JOIN products p ON f.product_id = p.id WHERE f.user_id = ? ORDER BY f.id DESC');
  $stmt->execute([(int)$user['id']]);
  $favorites = $stmt->fetchAll();
} catch (Exception $e) {
  // table may not exist - show placeholder
  $favorites = [];
}
?>

<div class="row">
  <div class="col-12">
    <h3 class="section-title mb-4">Your Favorites</h3>
    
    <?php if (empty($favorites)): ?>
      <div class="text-center py-5">
        <i class="fas fa-heart fa-3x text-muted mb-3"></i>
        <h4 class="mb-3">No favorites yet</h4>
        <p class="text-muted mb-4">Browse the marketplace and click the heart icon to save products or sellers.</p>
        <button class="btn btn-success btn-feature" onclick="loadContent('marketplace')">
          <i class="fas fa-store me-2"></i>Browse Marketplace
        </button>
      </div>
    <?php else: ?>
      <div class="row">
        <?php foreach ($favorites as $f): ?>
          <div class="col-md-6 mb-3">
            <div class="favorite-card p-3 h-100 d-flex align-items-center">
              <div class="d-flex align-items-center flex-grow-1">
                <div class="me-3">
                  <i class="fas fa-heart text-danger fa-lg"></i>
                </div>
                <div class="flex-grow-1">
                  <h6 class="mb-1 fw-bold"><?= htmlspecialchars($f['product_name'] ?? 'Product #' . (int)$f['product_id']) ?></h6>
                  <div class="text-muted small mb-1">ID: <?= (int)$f['product_id'] ?></div>
                  <?php if (!empty($f['price'])): ?>
                    <div class="fw-bold text-success">₱<?= number_format($f['price'],2) ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <div>
                <button class="btn btn-sm btn-outline-success rounded-pill px-3" onclick="loadContent('marketplace')">View</button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="text-center mt-4">
        <button class="btn btn-success btn-feature" onclick="loadContent('marketplace')">
          <i class="fas fa-store me-2"></i>Browse More Products
        </button>
      </div>
    <?php endif; ?>
  </div>
</div>