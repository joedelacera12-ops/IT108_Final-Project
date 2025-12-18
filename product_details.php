<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// Get product ID from URL
$product_id = intval($_GET['id'] ?? 0);

if (!$product_id) {
    header('Location: /ecommerce_farmers_fishers/php/market.php');
    exit;
}

$pdo = get_db();

// Fetch product details
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               CONCAT(u.first_name, ' ', u.last_name) as seller_name,
               u.rating as seller_rating,
               u.review_count as seller_review_count,
               c.name as category_name,
               (
                   SELECT JSON_ARRAYAGG(image_path)
                   FROM product_images
                   WHERE product_id = p.id
                   ORDER BY sort_order ASC, id ASC
               ) as images
        FROM products p
        LEFT JOIN users u ON p.seller_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.status IN ('active','out_of_stock')
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: /ecommerce_farmers_fishers/php/market.php');
        exit;
    }
    
    // Process images
    $images = [];
    if ($product['images']) {
        $images = json_decode($product['images'], true);
        if (!is_array($images)) {
            $images = [];
        }
    }
    
    if (empty($images)) {
        $images[] = '/assets/images/product-placeholder.png';
    }
    
    // Fetch product reviews with media
    $stmt = $pdo->prepare("
        SELECT 
            pr.id,
            pr.rating,
            pr.title,
            pr.review,
            pr.created_at,
            u.id AS reviewer_id,
            CONCAT(u.first_name, ' ', u.last_name) AS reviewer_name,
            u.profile_image AS reviewer_avatar,
            (
                SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'id', rm.id,
                        'media_type', rm.media_type,
                        'file_path', rm.file_path,
                        'file_name', rm.file_name
                    )
                )
                FROM review_media rm
                WHERE rm.review_id = pr.id
            ) AS media
        FROM product_reviews pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.product_id = ? AND pr.status = 'approved'
        ORDER BY pr.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process reviews
    foreach ($reviews as &$review) {
        // Process media
        if ($review['media']) {
            $media = json_decode($review['media'], true);
            if (is_array($media)) {
                $review['media'] = $media;
            } else {
                $review['media'] = [];
            }
        } else {
            $review['media'] = [];
        }
        
        // Clean up reviewer avatar
        if ($review['reviewer_avatar']) {
            $review['reviewer_avatar'] = strpos($review['reviewer_avatar'], 'http') === 0 ? 
                $review['reviewer_avatar'] : 
                '/' . ltrim($review['reviewer_avatar'], '/');
        } else {
            $review['reviewer_avatar'] = '/assets/images/avatar-placeholder.png';
        }
    }
    
    // Increment view count
    try {
        $viewStmt = $pdo->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = ?");
        $viewStmt->execute([$product_id]);
    } catch (Exception $e) {
        // Log error but don't fail the request
        error_log('Failed to increment view count: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    error_log('Failed to fetch product details: ' . $e->getMessage());
    header('Location: /ecommerce_farmers_fishers/php/market.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - AgriSea Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/ecommerce_farmers_fishers/assets/css/enhanced_modern_with_tracking.css">
    <style>
        .product-image-main {
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .product-image-thumb {
            height: 80px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 5px;
        }
        
        .product-image-thumb.active {
            border-color: #28a745;
        }
        
        .review-media {
            max-width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .star-rating {
            color: #ffc107;
        }
        
        .review-card {
            border-left: 3px solid #28a745;
        }
        
        .media-modal .modal-dialog {
            max-width: 800px;
        }
        
        .media-modal .modal-content {
            border-radius: 10px;
        }
        
        .media-modal .modal-body img,
        .media-modal .modal-body video {
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <?php include __DIR__ . '/includes/header.php'; ?>
        
        <div class="container-fluid p-4">
            <div class="row">
                <div class="col-12 mb-4">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/ecommerce_farmers_fishers/php/market.php">Marketplace</a></li>
                            <li class="breadcrumb-item"><a href="/ecommerce_farmers_fishers/php/market.php"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
                        </ol>
                    </nav>
                </div>
            </div>
            
            <div class="row">
                <!-- Product Images -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <img id="mainImage" src="<?php echo htmlspecialchars($images[0]); ?>" class="img-fluid product-image-main" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                            <div class="d-flex overflow-auto gap-2 pb-2">
                                <?php foreach ($images as $index => $image): ?>
                                    <img src="<?php echo htmlspecialchars($image); ?>" class="img-fluid product-image-thumb <?php echo $index === 0 ? 'active' : ''; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onclick="changeMainImage('<?php echo htmlspecialchars($image); ?>', this)">
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Product Details -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h2>
                            
                            <div class="d-flex align-items-center mb-3">
                                <div class="star-rating me-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= round($product['rating'] ?? 0) ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-muted">(<?php echo $product['review_count'] ?? 0; ?> reviews)</span>
                            </div>
                            
                            <div class="mb-3">
                                <span class="h3 text-success fw-bold">₱<?php echo number_format($product['price'], 2); ?></span>
                                <?php if ($product['unit']): ?>
                                    <span class="text-muted">per <?php echo htmlspecialchars($product['unit']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                <div class="mb-3">
                                    <span class="text-decoration-line-through text-muted">₱<?php echo number_format($product['price'], 2); ?></span>
                                    <span class="badge bg-danger ms-2">Save ₱<?php echo number_format($product['price'] - $product['sale_price'], 2); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <span class="badge <?php echo $product['stock'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $product['stock'] > 0 ? $product['stock'] . ' in stock' : 'Out of stock'; ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <h5>Description</h5>
                                <p><?php echo htmlspecialchars($product['description']); ?></p>
                            </div>
                            
                            <?php if ($product['short_description']): ?>
                                <div class="mb-3">
                                    <h6>Key Features</h6>
                                    <p><?php echo htmlspecialchars($product['short_description']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <h6>Seller Information</h6>
                                <div class="d-flex align-items-center">
                                    <div class="star-rating me-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= round($product['seller_rating'] ?? 0) ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-muted">(<?php echo $product['seller_review_count'] ?? 0; ?> reviews)</span>
                                </div>
                                <p class="mb-0"><?php echo htmlspecialchars($product['seller_name']); ?></p>
                            </div>
                            
                            <?php if ($product['stock'] > 0): ?>
                                <div class="d-flex gap-2">
                                    <div class="input-group" style="max-width: 150px;">
                                        <button class="btn btn-outline-secondary" type="button" onclick="decreaseQuantity()">-</button>
                                        <input type="number" class="form-control text-center" id="quantityInput" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                        <button class="btn btn-outline-secondary" type="button" onclick="increaseQuantity()">+</button>
                                    </div>
                                    <button class="btn btn-success flex-grow-1" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                                    </button>
                                </div>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="fas fa-exclamation-circle me-2"></i>Out of Stock
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Reviews Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h3 class="mb-4">Customer Reviews</h3>
                            
                            <?php if (empty($reviews)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-comment-alt fa-3x text-muted mb-3"></i>
                                    <h5>No reviews yet</h5>
                                    <p class="text-muted">Be the first to review this product!</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($reviews as $review): ?>
                                        <div class="col-12 mb-4">
                                            <div class="review-card p-3">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo htmlspecialchars($review['reviewer_avatar']); ?>" class="rounded-circle me-3" width="50" height="50" alt="<?php echo htmlspecialchars($review['reviewer_name']); ?>">
                                                        <div>
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($review['reviewer_name']); ?></h6>
                                                            <div class="star-rating">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                                <?php endfor; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></small>
                                                </div>
                                                
                                                <?php if ($review['title']): ?>
                                                    <h6 class="mt-2"><?php echo htmlspecialchars($review['title']); ?></h6>
                                                <?php endif; ?>
                                                
                                                <?php if ($review['review']): ?>
                                                    <p class="mb-3"><?php echo htmlspecialchars($review['review']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($review['media'])): ?>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <?php foreach ($review['media'] as $media): ?>
                                                            <?php if ($media['media_type'] === 'image'): ?>
                                                                <img src="<?php echo htmlspecialchars($media['file_path']); ?>" class="review-media" alt="<?php echo htmlspecialchars($media['file_name']); ?>" onclick="showMediaModal('<?php echo htmlspecialchars($media['file_path']); ?>', 'image')">
                                                            <?php elseif ($media['media_type'] === 'video'): ?>
                                                                <div class="position-relative review-media bg-light d-flex align-items-center justify-content-center" onclick="showMediaModal('<?php echo htmlspecialchars($media['file_path']); ?>', 'video')">
                                                                    <i class="fas fa-play-circle fa-2x text-primary"></i>
                                                                    <div class="position-absolute bottom-0 start-0 bg-dark text-white px-1" style="font-size: 0.7rem;">
                                                                        <i class="fas fa-video me-1"></i>Video
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Media Modal -->
    <div class="modal fade media-modal" id="mediaModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review Media</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="mediaContent"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Change main product image
        function changeMainImage(src, element) {
            document.getElementById('mainImage').src = src;
            // Update active class
            document.querySelectorAll('.product-image-thumb').forEach(thumb => {
                thumb.classList.remove('active');
            });
            element.classList.add('active');
        }
        
        // Quantity controls
        function decreaseQuantity() {
            const input = document.getElementById('quantityInput');
            if (input.value > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }
        
        function increaseQuantity() {
            const input = document.getElementById('quantityInput');
            const max = parseInt(input.max);
            if (input.value < max) {
                input.value = parseInt(input.value) + 1;
            }
        }
        
        // Add to cart
        async function addToCart(productId) {
            try {
                const quantity = document.getElementById('quantityInput').value;
                
                const response = await fetch('/ecommerce_farmers_fishers/buyer/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=${quantity}`
                });
                
                const data = await response.json();
                
                // If user is not logged in, show login prompt
                if (data.login_required) {
                    const loginModal = document.createElement('div');
                    loginModal.className = 'modal fade';
                    loginModal.id = 'loginPromptModal';
                    loginModal.tabIndex = '-1';
                    loginModal.innerHTML = `
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Login Required</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Please log in or register to continue shopping.</p>
                                </div>
                                <div class="modal-footer">
                                    <a href="/ecommerce_farmers_fishers/php/login.php" class="btn btn-primary">Login</a>
                                    <a href="/ecommerce_farmers_fishers/php/register.php" class="btn btn-outline-primary">Register</a>
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
                notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
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
                notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
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
        
        // Show media in modal
        function showMediaModal(src, type) {
            const modal = new bootstrap.Modal(document.getElementById('mediaModal'));
            const mediaContent = document.getElementById('mediaContent');
            
            if (type === 'image') {
                mediaContent.innerHTML = `<img src="${src}" class="img-fluid" alt="Review Media">`;
            } else if (type === 'video') {
                mediaContent.innerHTML = `<video src="${src}" class="img-fluid" controls autoplay></video>`;
            }
            
            modal.show();
        }
        
        // Update cart count badge
        async function updateCartCount() {
            try {
                const response = await fetch('/ecommerce_farmers_fishers/buyer/cart_count.php');
                const data = await response.json();
                
                if (data.success) {
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.count;
                        cartCountElement.style.display = data.count > 0 ? 'inline' : 'none';
                    }
                }
            } catch (error) {
                console.error('Error updating cart count:', error);
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
</body>
</html>