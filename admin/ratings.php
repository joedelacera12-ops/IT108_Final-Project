<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('admin');
$pdo = get_db();

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    try {
        $stmt = $pdo->prepare('DELETE FROM seller_ratings WHERE id = ?');
        $stmt->execute([$id]);
        // Redirect to the ratings tab within the dashboard
        header('Location: /ecommerce_farmers_fishers/admin/dashboard.php?tab=ratings');
        exit;
    } catch (Exception $e) {
        $error = 'Unable to delete rating';
    }
}

// Fetch ratings
$ratings = [];
try {
    $stmt = $pdo->query("SELECT r.id, r.seller_id, r.buyer_id, r.order_id, r.rating, r.review, r.created_at, s.first_name as seller_fn, s.last_name as seller_ln, b.first_name as buyer_fn, b.last_name as buyer_ln FROM seller_ratings r LEFT JOIN users s ON r.seller_id = s.id LEFT JOIN users b ON r.buyer_id = b.id ORDER BY r.created_at DESC");
    $ratings = $stmt->fetchAll();
} catch (Exception $e) { $ratings = []; }

// Since this is now included in the dashboard, we don't need the full HTML structure
// Just the content part
?>

<div class="container-fluid p-4">
  <div class="row">
    <div class="col-12">
      <h3>Seller Ratings</h3>
      <p class="text-muted">Manage ratings submitted by buyers.</p>

        <?php if (empty($ratings)): ?>
          <div class="alert alert-info">No ratings yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead><tr><th>Seller</th><th>Buyer</th><th>Order</th><th>Rating</th><th>Review</th><th>When</th><th>Actions</th></tr></thead>
              <tbody>
                <?php foreach ($ratings as $r): ?>
                  <tr>
                    <td><?= htmlspecialchars((trim(($r['seller_fn']??'') . ' ' . ($r['seller_ln']??''))) ?: '—') ?></td>
                    <td><?= htmlspecialchars((trim(($r['buyer_fn']??'') . ' ' . ($r['buyer_ln']??''))) ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['order_id'] ?? '') ?></td>
                    <td>
                      <?php for ($s=1;$s<=5;$s++): ?>
                        <?php if ($s <= (int)$r['rating']): ?>
                          <span class="text-warning">★</span>
                        <?php else: ?>
                          <span class="text-muted">☆</span>
                        <?php endif; ?>
                      <?php endfor; ?>
                    </td>
                    <td><?= htmlspecialchars(substr($r['review'] ?? '', 0, 120)) ?></td>
                    <td><?= htmlspecialchars($r['created_at'] ?? '') ?></td>
                    <td>
                      <form method="post" style="display:inline-block" onsubmit="return confirm('Delete this rating?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-sm btn-danger">Delete</button>
                      </form>
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