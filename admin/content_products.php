<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins may access
require_role('admin');
$user = current_user();
$pdo = get_db();

// Get all products with seller information
$products = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            u.first_name as seller_first_name,
            u.last_name as seller_last_name,
            u.email as seller_email
        FROM products p
        JOIN users u ON p.seller_id = u.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $products = [];
}

// Handle product deletion if requested
$message = '';
$messageType = '';

if (isset($_POST['action']) && isset($_POST['product_id']) && $_POST['action'] === 'delete') {
    $productId = (int)$_POST['product_id'];
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Delete related data first
        $pdo->prepare("DELETE FROM cart_items WHERE product_id = ?")->execute([$productId]);
        $pdo->prepare("DELETE FROM order_items WHERE product_id = ?")->execute([$productId]);
        $pdo->prepare("DELETE FROM favorites WHERE product_id = ?")->execute([$productId]);
        $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$productId]);
        
        // Delete product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        
        $pdo->commit();
        
        $message = "Product deleted successfully.";
        $messageType = "success";
        
        // Refresh products list
        $stmt = $pdo->prepare("
            SELECT 
                p.*, 
                u.first_name as seller_first_name,
                u.last_name as seller_last_name,
                u.email as seller_email
            FROM products p
            JOIN users u ON p.seller_id = u.id
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
        $products = $stmt->fetchAll();
    } catch (Exception $e) {
        $pdo->rollback();
        $message = "Error deleting product: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Handle product approval/rejection
if (isset($_POST['action']) && isset($_POST['product_id']) && in_array($_POST['action'], ['approve', 'reject'])) {
    $productId = (int)$_POST['product_id'];
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    try {
        $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
        $stmt->execute([$status, $productId]);
        
        $message = "Product " . $action . "d successfully.";
        $messageType = "success";
        
        // Refresh products list
        $stmt = $pdo->prepare("
            SELECT 
                p.*, 
                u.first_name as seller_first_name,
                u.last_name as seller_last_name,
                u.email as seller_email
            FROM products p
            JOIN users u ON p.seller_id = u.id
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
        $products = $stmt->fetchAll();
    } catch (Exception $e) {
        $message = "Error updating product status: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>

<div class="row">
    <div class="col-12">
        <h3 class="section-title">Manage Products</h3>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card p-3 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">All Products</h5>
                <div>
                    <a href="/ecommerce_farmers_fishers/admin/dashboard.php?tab=addProduct" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>Add New Product
                    </a>
                </div>
            </div>
            
            <?php if (empty($products)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>No products found
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Seller</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['id']); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($product['image_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center me-2" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div><?php echo htmlspecialchars($product['name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . (strlen($product['description']) > 50 ? '...' : ''); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(($product['seller_first_name'] ?? '') . ' ' . ($product['seller_last_name'] ?? '')); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($product['seller_email'] ?? ''); ?></small>
                                    </td>
                                    <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($product['stock']); ?> <?php echo htmlspecialchars($product['unit'] ?? 'pcs'); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo ucfirst(htmlspecialchars($product['category'] ?? 'Uncategorized')); ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $status = $product['status'] ?? 'pending';
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        switch ($status) {
                                            case 'approved':
                                                $statusClass = 'bg-success';
                                                $statusText = 'Approved';
                                                break;
                                            case 'pending':
                                                $statusClass = 'bg-warning';
                                                $statusText = 'Pending';
                                                break;
                                            case 'rejected':
                                                $statusClass = 'bg-danger';
                                                $statusText = 'Rejected';
                                                break;
                                            default:
                                                $statusClass = 'bg-secondary';
                                                $statusText = ucfirst($status);
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if ($product['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-outline-success" title="Approve Product">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-outline-danger" title="Reject Product">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <a href="/ecommerce_farmers_fishers/admin/edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary" title="Edit Product">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-outline-danger" title="Delete Product" onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
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