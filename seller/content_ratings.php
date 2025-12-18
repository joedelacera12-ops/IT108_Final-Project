<?php
// Fetch seller ratings
$sellerRatings = [];
try {
    $stmt = $pdo->prepare("
        SELECT sr.*, u.first_name, u.last_name 
        FROM seller_ratings sr
        JOIN users u ON sr.buyer_id = u.id
        WHERE sr.seller_id = ? 
        ORDER BY sr.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    $sellerRatings = $stmt->fetchAll();
} catch (Exception $e) {
    // error_log($e->getMessage());
}
?>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Recent Ratings & Reviews</h5>
        <?php if (empty($sellerRatings)): ?>
            <p class="text-muted">You have no ratings yet.</p>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($sellerRatings as $rating): ?>
                    <li class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?php echo htmlspecialchars($rating['first_name'] . ' ' . $rating['last_name']); ?></h6>
                            <small><?php echo date('M j, Y', strtotime($rating['created_at'])); ?></small>
                        </div>
                        <p class="mb-1">Rating: <?php echo $rating['rating']; ?>/5</p>
                        <p class="mb-1"><?php echo htmlspecialchars($rating['review']); ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>