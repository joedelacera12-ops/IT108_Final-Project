<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only sellers can get their products list
require_role('seller');
$user = current_user();

header('Content-Type: application/json');

try {
    $pdo = get_db();
    
    // Get products for this seller with category information
    $st = $pdo->prepare('SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.seller_id = ? ORDER BY p.created_at DESC');
    $st->execute([(int)$user['id']]);
    $plist = $st->fetchAll();
    
    // Generate HTML for the product list
    $html = '';
    if (empty($plist)) {
        $html .= '<tr><td colspan="8" class="text-center">No products found. <a href="/ecommerce_farmers_fishers/seller/dashboard.php?tab=addProduct">Add your first product</a></td></tr>';
    } else {
        foreach ($plist as $p) {
            // Get product image
            $imageHtml = '';
            try {
                $imgStmt = $pdo->prepare('SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1');
                $imgStmt->execute([$p['id']]);
                $image = $imgStmt->fetch();
                if ($image && file_exists(__DIR__ . '/../' . $image['image_path'])) {
                    $imageHtml = '<img src="/ecommerce_farmers_fishers/' . htmlspecialchars($image['image_path']) . '" alt="' . htmlspecialchars($p['name']) . '" class="product-image">';
                } else {
                    $imageHtml = '<div class="bg-light d-flex align-items-center justify-content-center product-image text-muted" style="width: 50px; height: 50px;"><i class="fas fa-image"></i></div>';
                }
            } catch (Exception $e) {
                $imageHtml = '<div class="bg-light d-flex align-items-center justify-content-center product-image text-muted" style="width: 50px; height: 50px;"><i class="fas fa-image"></i></div>';
            }
            
            // Status badge
            $status = $p['status'] ?? 'draft';
            $badgeClass = '';
            switch ($status) {
                case 'active': $badgeClass = 'bg-success'; break;
                case 'inactive': $badgeClass = 'bg-secondary'; break;
                case 'out_of_stock': $badgeClass = 'bg-warning'; break;
                case 'draft': $badgeClass = 'bg-info'; break;
                default: $badgeClass = 'bg-secondary';
            }
            $statusBadge = '<span class="badge ' . $badgeClass . ' status-badge">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
            
            $html .= '<tr>';
            $html .= '<td>' . $imageHtml . '</td>';
            $html .= '<td>' . htmlspecialchars($p['name'] ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($p['category_name'] ?? 'Uncategorized') . '</td>';
            $html .= '<td>₱' . number_format((float)$p['price'],2) . '</td>';
            $html .= '<td>' . (int)($p['stock'] ?? 0) . '</td>';
            $html .= '<td>' . $statusBadge . '</td>';
            $html .= '<td>' . date('M j, Y', strtotime($p['created_at'] ?? '')) . '</td>';
            $html .= '<td><div class="btn-group btn-group-sm" role="group">';
            $html .= '<a href="/ecommerce_farmers_fishers/seller/edit_product.php?id=' . $p['id'] . '" class="btn btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>';
            $html .= '<a href="/ecommerce_farmers_fishers/seller/delete_product.php?id=' . $p['id'] . '" class="btn btn-outline-danger" title="Delete" onclick="return confirm(\'Are you sure you want to delete this product?\')"><i class="fas fa-trash"></i></a>';
            $html .= '</div></td>';
            $html .= '</tr>';
        }
    }
    
    echo json_encode(['success' => true, 'html' => $html]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}