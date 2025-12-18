<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('buyer');
$user = current_user();
$pdo = get_db();

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    header('Location: /ecommerce_farmers_fishers/buyer/dashboard.php?page=orders');
    exit;
}

// Fetch order details to ensure it belongs to the user and is delivered
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'delivered'");
$stmt->execute([$order_id, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    // Order not found, not delivered, or doesn't belong to the user
    header('Location: /ecommerce_farmers_fishers/buyer/dashboard.php?page=orders');
    exit;
}

// Check if a rating already exists
$stmt = $pdo->prepare("SELECT * FROM seller_ratings WHERE order_id = ? AND buyer_id = ?");
$stmt->execute([$order_id, $user['id']]);
$existing_rating = $stmt->fetch();

if ($existing_rating) {
    // Rating already submitted
    header('Location: /ecommerce_farmers_fishers/buyer/dashboard.php?page=orders');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'] ?? 0;
    $review = $_POST['review'] ?? '';

    if ($rating > 0) {
        // Get the seller_id from the order_items table since it's not in the orders table
        $stmt = $pdo->prepare("SELECT seller_id FROM order_items WHERE order_id = ? LIMIT 1");
        $stmt->execute([$order_id]);
        $seller = $stmt->fetch();
        
        if ($seller) {
            $stmt = $pdo->prepare("INSERT INTO seller_ratings (order_id, buyer_id, seller_id, rating, review) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$order_id, $user['id'], $seller['seller_id'], $rating, $review]);
        }

        header('Location: /ecommerce_farmers_fishers/buyer/dashboard.php?page=orders');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h2>Rate Your Order</h2>
    <p>Order #<?php echo htmlspecialchars($order['order_number']); ?></p>

    <form method="POST">
        <div class="mb-3">
            <label for="rating" class="form-label">Rating</label>
            <select name="rating" id="rating" class="form-control">
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Good</option>
                <option value="3">3 - Average</option>
                <option value="2">2 - Fair</option>
                <option value="1">1 - Poor</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="review" class="form-label">Review</label>
            <textarea name="review" id="review" class="form-control" rows="4"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit Rating</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>