<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only sellers may access
require_role('seller');
$user = current_user();
$pdo = get_db();

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get seller ratings and reviews with pagination
$sellerRatings = [];
$totalRatings = 0;
$averageRating = 0;

try {
    // Get total count
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM seller_ratings WHERE seller_id = ?');
    $stmt->execute([$user['id']]);
    $totalRatings = (int)$stmt->fetch()['total'];
    
    // Get average rating
    $stmt = $pdo->prepare('SELECT AVG(rating) as avg_rating FROM seller_ratings WHERE seller_id = ?');
    $stmt->execute([$user['id']]);
    $averageRating = $stmt->fetch()['avg_rating'] ? round($stmt->fetch()['avg_rating'], 1) : 0;
    
    // Get ratings with pagination
    $stmt = $pdo->prepare('
        SELECT sr.*, u.first_name, u.last_name, o.order_number
        FROM seller_ratings sr 
        JOIN users u ON sr.buyer_id = u.id 
        JOIN orders o ON sr.order_id = o.id
        WHERE sr.seller_id = ? 
        ORDER BY sr.created_at DESC 
        LIMIT ? OFFSET ?
    ');
    $stmt->execute([$user['id'], $limit, $offset]);
    $sellerRatings = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Seller ratings query failed: " . $e->getMessage());
}

// Calculate pagination
$totalPages = ceil($totalRatings / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Reviews - AgriSea Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .review-card {
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
            border: none;
            margin-bottom: 1rem;
        }
        
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .rating-stars {
            color: #ffc107;
        }
        
        .average-rating {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 10px;
        }
        
        .pagination .page-link {
            color: #28a745;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #28a745;
            border-color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm" style="padding: 0.75rem 1rem;">
            <div class="container-fluid">
                <a class="navbar-brand" href="/ecommerce_farmers_fishers/seller/dashboard.php"><strong class="text-success">AgriSea</strong> Marketplace</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a href="/ecommerce_farmers_fishers/seller/dashboard.php?tab=marketplace" class="nav-link">Marketplace</a></li>
                        <li class="nav-item"><a href="/ecommerce_farmers_fishers/seller/dashboard.php" class="nav-link">Dashboard</a></li>
                        <li class="nav-item"><a href="/ecommerce_farmers_fishers/seller/reviews.php" class="nav-link active">Reviews</a></li>
                        <li class="nav-item"><a href="/ecommerce_farmers_fishers/logout.php" class="nav-link">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <div class="row">
                <div class="col-12">
                    <h2 class="mb-4">Customer Reviews</h2>
                    
                    <!-- Average Rating Summary -->
                    <div class="card average-rating mb-4 p-4">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center">
                                <h1 class="display-3 mb-0"><?php echo $averageRating; ?></h1>
                                <div class="rating-stars mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $averageRating): ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif ($i - 0.5 <= $averageRating): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <p class="mb-0"><?php echo $totalRatings; ?> Reviews</p>
                            </div>
                            <div class="col-md-9">
                                <h4 class="mb-3">Your Overall Rating</h4>
                                <p class="mb-0">Keep providing excellent service to maintain your high rating!</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reviews List -->
                    <?php if (empty($sellerRatings)): ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-comment-dots fa-2x mb-3"></i>
                            <h5>No Reviews Yet</h5>
                            <p class="mb-0">Once customers purchase from you and leave reviews, they'll appear here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sellerRatings as $rating): ?>
                            <div class="card review-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($rating['first_name'] . ' ' . $rating['last_name']); ?></h5>
                                            <p class="card-text text-muted mb-2">Order #<?php echo htmlspecialchars($rating['order_number']); ?></p>
                                        </div>
                                        <div class="text-end">
                                            <div class="rating-stars mb-1">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $rating['rating']): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($rating['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <?php if (!empty($rating['review'])): ?>
                                        <p class="card-text mt-3"><?php echo htmlspecialchars($rating['review']); ?></p>
                                    <?php else: ?>
                                        <p class="card-text text-muted mt-3"><em>No review provided</em></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Reviews pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>