<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only sellers may access
require_role('seller');
$user = current_user();
$pdo = get_db();

// Get product ID from URL
$productId = (int)($_GET['id'] ?? 0);

if (empty($productId)) {
    header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=products');
    exit;
}

// Fetch product details
try {
    $stmt = $pdo->prepare('
        SELECT p.*, c.name as category_name, pi.image_path 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.id = ? AND p.seller_id = ?
    ');
    $stmt->execute([$productId, $user['id']]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=products');
        exit;
    }
} catch (Exception $e) {
    error_log("Error fetching product: " . $e->getMessage());
    header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=products');
    exit;
}

// Fetch all categories for dropdown
try {
    $stmt = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name');
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $categoryId = intval($_POST['category_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'draft');
    
    // Validate inputs
    $errors = [];
    if (empty($name)) {
        $errors[] = 'Product name is required';
    }
    if (empty($description)) {
        $errors[] = 'Product description is required';
    }
    if ($price <= 0) {
        $errors[] = 'Price must be greater than zero';
    }
    if ($stock < 0) {
        $errors[] = 'Stock cannot be negative';
    }
    if ($categoryId <= 0) {
        $errors[] = 'Category is required';
    }
    
    if (empty($errors)) {
        try {
            // Update product
            $stmt = $pdo->prepare('
                UPDATE products 
                SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, status = ?, updated_at = NOW()
                WHERE id = ? AND seller_id = ?
            ');
            $stmt->execute([
                $name,
                $description,
                $price,
                $stock,
                $categoryId,
                $status,
                $productId,
                $user['id']
            ]);
            
            // Handle image upload if provided
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    $fileName = uniqid() . '_' . $productId . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                        $relativePath = 'uploads/products/' . $fileName;
                        
                        // Check if product already has a primary image
                        $checkStmt = $pdo->prepare('SELECT id FROM product_images WHERE product_id = ? AND is_primary = 1');
                        $checkStmt->execute([$productId]);
                        $existingImage = $checkStmt->fetch();
                        
                        if ($existingImage) {
                            // Update existing primary image
                            $updateStmt = $pdo->prepare('UPDATE product_images SET image_path = ?, updated_at = NOW() WHERE id = ?');
                            $updateStmt->execute([$relativePath, $existingImage['id']]);
                        } else {
                            // Insert new primary image
                            $insertStmt = $pdo->prepare('INSERT INTO product_images (product_id, image_path, is_primary, created_at) VALUES (?, ?, 1, NOW())');
                            $insertStmt->execute([$productId, $relativePath]);
                        }
                    }
                }
            }
            
            $successMessage = 'Product updated successfully!';
            // Refresh product data
            $stmt = $pdo->prepare('
                SELECT p.*, c.name as category_name, pi.image_path 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.id = ? AND p.seller_id = ?
            ');
            $stmt->execute([$productId, $user['id']]);
            $product = $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error updating product: " . $e->getMessage());
            $errors[] = 'Failed to update product. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Agrisea Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/ecommerce_farmers_fishers/assets/css/unified_light_theme.css">
</head>
<body>
    <div class="container-fluid p-0">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm seller-navbar">
            <div class="container-fluid">
                <a class="navbar-brand" href="/ecommerce_farmers_fishers/seller/dashboard.php"><strong class="text-success">Agrisea</strong> Marketplace</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a href="/ecommerce_farmers_fishers/seller/dashboard.php?tab=products" class="nav-link"><i class="fas fa-arrow-left me-1"></i>Back to Products</a></li>
                        <li class="nav-item"><a href="/ecommerce_farmers_fishers/logout.php" class="nav-link">Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid p-4 seller-content">
            <div class="row">
                <div class="col-12">
                    <h3 class="mb-4"><i class="fas fa-edit me-2"></i>Edit Product</h3>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($successMessage); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Product Name *</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description *</label>
                                            <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="price" class="form-label">Price (₱) *</label>
                                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01" value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="stock" class="form-label">Stock Quantity *</label>
                                                    <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?php echo htmlspecialchars($product['stock'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="category_id" class="form-label">Category *</label>
                                                    <select class="form-select" id="category_id" name="category_id" required>
                                                        <option value="">Select Category</option>
                                                        <?php foreach ($categories as $category): ?>
                                                            <option value="<?php echo $category['id']; ?>" <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($category['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="status" class="form-label">Status</label>
                                                    <select class="form-select" id="status" name="status">
                                                        <option value="draft" <?php echo ($product['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                                        <option value="published" <?php echo ($product['status'] === 'published') ? 'selected' : ''; ?>>Published</option>
                                                        <option value="unpublished" <?php echo ($product['status'] === 'unpublished') ? 'selected' : ''; ?>>Unpublished</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Current Image</label>
                                            <div class="mb-2">
                                                <?php if (!empty($product['image_path']) && file_exists(__DIR__ . '/../' . $product['image_path'])): ?>
                                                    <img src="/ecommerce_farmers_fishers/<?php echo htmlspecialchars($product['image_path']); ?>" alt="Product Image" class="img-fluid rounded" style="max-height: 200px;">
                                                <?php else: ?>
                                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                                        <i class="fas fa-image text-muted fa-2x"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <label for="image" class="form-label">Upload New Image</label>
                                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                            <div class="form-text">Leave blank to keep current image. JPG, PNG, GIF only.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="/ecommerce_farmers_fishers/seller/dashboard.php?tab=products" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i>Update Product
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>