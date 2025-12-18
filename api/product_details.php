<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../includes/db.php';

function json_error(string $message, int $status = 500): void {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_success($data = null): void {
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

// Ensure database connection
try {
    $pdo = get_db();
    // Test connection
    $stmt = $pdo->query('SELECT 1');
    $stmt->fetch();
} catch (Throwable $e) {
    error_log('Database connection error in product_details.php: ' . $e->getMessage());
    json_error('Database connection failed. Please check server logs.', 500);
}

// Get product ID from request
$productId = (int)($_GET['id'] ?? 0);

if (!$productId) {
    json_error('Product ID is required', 400);
}

// Get product details with seller and category information
$sql = "
SELECT
  p.*,
  COALESCE(CONCAT(u.first_name, ' ', u.last_name), '') AS seller_name,
  u.email AS seller_email,
  u.phone AS seller_phone,
  u.rating AS seller_rating,
  u.review_count AS seller_review_count,
  u.is_verified_seller,
  c.name AS category_name,
  c.slug AS category_slug,
  (
    SELECT JSON_ARRAYAGG(pi.image_path)
    FROM product_images pi
    WHERE pi.product_id = p.id
    ORDER BY pi.sort_order ASC, pi.id ASC
  ) AS images
FROM products p
LEFT JOIN users u ON u.id = p.seller_id
LEFT JOIN categories c ON c.id = p.category_id
WHERE p.id = ? AND p.status IN ('active','out_of_stock')
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        json_error('Product not found', 404);
    }
    
    // Process images
    $images = [];
    if ($product['images']) {
        $images = json_decode($product['images'], true);
        if (!is_array($images)) {
            $images = [];
        }
    }
    
    // Set default image if none exist
    if (empty($images)) {
        $images[] = '/assets/images/product-placeholder.png';
    }
    
    // Get product reviews with media
    $reviewsSql = "
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
    ";
    
    $reviewsStmt = $pdo->prepare($reviewsSql);
    $reviewsStmt->execute([$productId]);
    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    
    // Get related products (same category, limit 4)
    $relatedSql = "
    SELECT 
        p.id, p.name, p.price, p.stock,
        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id AND pi.is_primary = 1
            ORDER BY pi.sort_order ASC, pi.id ASC
            LIMIT 1
        ) AS image_path
    FROM products p
    WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
    ORDER BY p.sales_count DESC, p.created_at DESC
    LIMIT 4
    ";
    
    $relatedStmt = $pdo->prepare($relatedSql);
    $relatedStmt->execute([$product['category_id'], $productId]);
    $relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format related products
    foreach ($relatedProducts as &$related) {
        $related['image'] = $related['image_path'] ? 
            (strpos($related['image_path'], 'http') === 0 ? $related['image_path'] : '/' . ltrim($related['image_path'], '/')) : 
            '/assets/images/product-placeholder.png';
        unset($related['image_path']);
    }
    
    // Prepare response data
    $responseData = [
        'id' => (int)$product['id'],
        'name' => (string)$product['name'],
        'description' => (string)$product['description'],
        'short_description' => (string)($product['short_description'] ?? ''),
        'price' => (float)$product['price'],
        'sale_price' => $product['sale_price'] ? (float)$product['sale_price'] : null,
        'stock' => (int)$product['stock'],
        'unit' => (string)($product['unit'] ?? 'unit'),
        'is_organic' => (bool)($product['is_organic'] ?? false),
        'harvest_date' => $product['harvest_date'] ? (string)$product['harvest_date'] : null,
        'expiry_date' => $product['expiry_date'] ? (string)$product['expiry_date'] : null,
        'origin_location' => (string)($product['origin_location'] ?? ''),
        'processing_method' => (string)($product['processing_method'] ?? ''),
        'storage_requirements' => (string)($product['storage_requirements'] ?? ''),
        'nutritional_info' => $product['nutritional_info'] ? json_decode($product['nutritional_info'], true) : null,
        'allergens' => $product['allergens'] ? json_decode($product['allergens'], true) : null,
        'category' => [
            'id' => (int)$product['category_id'],
            'name' => (string)$product['category_name'],
            'slug' => (string)$product['category_slug']
        ],
        'seller' => [
            'id' => (int)$product['seller_id'],
            'name' => (string)trim($product['seller_name']) ?: 'Seller',
            'email' => (string)($product['seller_email'] ?? ''),
            'phone' => (string)($product['seller_phone'] ?? ''),
            'rating' => (float)($product['seller_rating'] ?? 0),
            'review_count' => (int)($product['seller_review_count'] ?? 0),
            'is_verified' => (bool)($product['is_verified_seller'] ?? false)
        ],
        'images' => $images,
        'reviews' => $reviews,
        'related_products' => $relatedProducts
    ];
    
    // Increment view count
    try {
        $viewStmt = $pdo->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = ?");
        $viewStmt->execute([$productId]);
    } catch (Exception $e) {
        // Log error but don't fail the request
        error_log('Failed to increment view count: ' . $e->getMessage());
    }
    
    json_success($responseData);
    
} catch (Throwable $e) {
    json_error('Failed to retrieve product details: ' . $e->getMessage(), 500);
}