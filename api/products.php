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

// Ensure database connection
try {
    $pdo = get_db();
    // Test connection
    $stmt = $pdo->query('SELECT 1');
    $stmt->fetch();
} catch (Throwable $e) {
    error_log('Database connection error in products.php: ' . $e->getMessage());
    json_error('Database connection failed. Please check server logs.', 500);
}

// Optional: basic pagination and simple filters (category slug and search)
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(50, (int)($_GET['limit'] ?? 24)));
$offset = ($page - 1) * $limit;
$category = isset($_GET['category']) ? trim((string)$_GET['category']) : '';
$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

$where = [];
$params = [];

// Only show active/available products
$where[] = "p.status IN ('active','out_of_stock')";

if ($category !== '') {
    $where[] = "c.slug = ?";
    $params[] = $category;
}

if ($search !== '') {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Primary image if exists; fallback to placeholder
$sql = "
SELECT
  p.id,
  p.name,
  p.price,
  p.stock,
  p.description,
  p.unit,
  p.seller_id,
  p.is_organic,
  COALESCE(CONCAT(u.first_name, ' ', u.last_name), '') AS seller_name,
  u.email AS seller_email,
  u.phone AS seller_phone,
  c.slug AS category_slug,
  c.name AS category_name,
  (
    SELECT pi.image_path
    FROM product_images pi
    WHERE pi.product_id = p.id AND pi.is_primary = 1
    ORDER BY pi.sort_order ASC, pi.id ASC
    LIMIT 1
  ) AS image_path
FROM products p
LEFT JOIN users u ON u.id = p.seller_id
LEFT JOIN categories c ON c.id = p.category_id
$whereSql
ORDER BY p.created_at DESC
LIMIT $limit OFFSET $offset
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    json_error('Failed to query products', 500);
}

$baseUrl = '';
$placeholder = '/assets/images/product-placeholder.png';

// Map DB rows to the front-end structure used in market.html script
$data = array_map(function(array $r) use ($baseUrl, $placeholder) {
    $image = $r['image_path'] ? (strpos($r['image_path'], 'http') === 0 ? $r['image_path'] : '/' . ltrim($r['image_path'], '/')) : $placeholder;
    return [
        'id' => (int)$r['id'],
        'name' => (string)$r['name'],
        'price' => (float)$r['price'],
        'stock' => (int)$r['stock'],
        'category' => (string)($r['category_slug'] ?? ''),
        'category_name' => (string)($r['category_name'] ?? ''),
        'description' => (string)($r['description'] ?? ''),
        'seller' => trim((string)($r['seller_name'] ?? '')) ?: 'Seller',
        'seller_email' => (string)($r['seller_email'] ?? ''),
        'seller_phone' => (string)($r['seller_phone'] ?? ''),
        'location' => 'Philippines', // default location
        'delivery' => 'Available', // default delivery info
        'image' => $image,
        'organic' => isset($r['is_organic']) ? (bool)$r['is_organic'] : false,
        'fresh' => true, // assume products are fresh
        'local' => true, // assume products are local
        'rating' => 4.5, // default rating
        'reviews' => rand(5, 50), // random review count
        'unit' => (string)($r['unit'] ?? 'unit')
    ];
}, $rows);

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id $whereSql";
try {
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();
} catch (Throwable $e) {
    $totalCount = count($data); // fallback
}

echo json_encode([
    'success' => true,
    'page' => $page,
    'limit' => $limit,
    'count' => count($data),
    'total' => $totalCount,
    'products' => $data
], JSON_UNESCAPED_UNICODE);
?>