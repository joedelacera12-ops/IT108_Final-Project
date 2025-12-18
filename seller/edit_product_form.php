<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('seller');
$user = current_user();
$pdo = get_db();

// Get product ID from query parameter
$productId = intval($_GET['id'] ?? 0);
if (!$productId) {
    header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=products');
    exit;
}

// Fetch product details
try {
    $stmt = $pdo->prepare('SELECT p.*, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.seller_id = ?');
    $stmt->execute([$productId, $user['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=products');
        exit;
    }
} catch (Exception $e) {
    header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=products');
    exit;
}

// Fetch product images
try {
    $imgStmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC');
    $imgStmt->execute([$productId]);
    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $images = [];
}

// Determine seller_type (may be present on $user or query DB)
$sellerType = $user['seller_type'] ?? null;
if (empty($sellerType)) {
    // attempt to read from DB if $pdo available or via get_db()
    try {
        if (!isset($pdo)) { if (function_exists('get_db')) $pdo = get_db(); }
        if (isset($pdo) && isset($user['id'])) {
            $q = $pdo->prepare('SELECT seller_type FROM users WHERE id = ? LIMIT 1');
            $q->execute([(int)$user['id']]);
            $sellerType = $q->fetchColumn() ?: null;
        }
    } catch (Exception $e) { $sellerType = $sellerType ?? null; }
}

// Default category lists for farmers and fishers
$farmerCats = [
    'vegetables' => 'Vegetables',
    'fruits' => 'Fruits',
    'grains' => 'Grains & Cereals',
    'dairy' => 'Dairy Products',
    'poultry' => 'Poultry & Eggs',
    'herbs' => 'Herbs & Spices',
    'other' => 'Other'
];
$fisherCats = [
    'seafood' => 'Seafood',
    'fresh_fish' => 'Fresh Fish',
    'shellfish' => 'Shellfish',
    'crustaceans' => 'Crustaceans',
    'seaweed' => 'Seaweed',
    'processed_seafood' => 'Processed Seafood',
    'other' => 'Other'
];

$categoryOptions = ($sellerType === 'fisher') ? $fisherCats : $farmerCats;

// Convert to format compatible with existing code
$categories = [];
foreach ($categoryOptions as $slug => $name) {
    $categories[] = ['slug' => $slug, 'name' => $name];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - AgriSea Seller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Product</h4>
                                <?php $displayType = get_seller_type_display($pdo); ?>
                                <?php if (!empty($displayType)): ?>
                                    <div class="small">Account Type: <?= htmlspecialchars($displayType) ?></div>
                                <?php endif; ?>
                            </div>
                            <a href="/ecommerce_farmers_fishers/seller/dashboard.php?tab=products" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Back to Products
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="editProductForm" enctype="multipart/form-data">
                            <input type="hidden" id="productId" value="<?= htmlspecialchars($product['id']) ?>">
                            
                            <div class="mb-3">
                                <label for="productName" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="productName" value="<?= htmlspecialchars($product['name']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="productCategory" class="form-label">Category</label>
                                <select class="form-select" id="productCategory">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['slug']) ?>" <?= ($product['category_slug'] === $category['slug']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="productDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="productDescription" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price (₱) *</label>
                                        <input type="number" class="form-control" id="price" step="0.01" min="0" value="<?= htmlspecialchars($product['price']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="stock" class="form-label">Stock *</label>
                                        <input type="number" class="form-control" id="stock" min="0" value="<?= htmlspecialchars($product['stock']) ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="unit" class="form-label">Unit (e.g., kg, pcs, bunch)</label>
                                <input type="text" class="form-control" id="unit" value="<?= htmlspecialchars($product['weight_unit'] ?? '') ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Current Images</label>
                                <div class="row">
                                    <?php if (!empty($images)): ?>
                                        <?php foreach ($images as $image): ?>
                                            <div class="col-md-3 mb-2">
                                                <div class="position-relative">
                                                    <img src="/ecommerce_farmers_fishers/<?= htmlspecialchars($image['image_path']) ?>" 
                                                         alt="Product Image" 
                                                         class="img-fluid rounded border">
                                                    <button type="button" 
                                                            class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1" 
                                                            onclick="deleteImage(<?= $image['id'] ?>)"
                                                            title="Delete Image">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <?php if ($image['is_primary']): ?>
                                                        <span class="badge bg-success position-absolute bottom-0 start-0 m-1">Primary</span>
                                                    <?php else: ?>
                                                        <button type="button" 
                                                                class="btn btn-outline-success btn-sm position-absolute bottom-0 start-0 m-1" 
                                                                onclick="setPrimaryImage(<?= $image['id'] ?>, <?= $product['id'] ?>)"
                                                                title="Set as Primary">
                                                            <i class="fas fa-star"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <p class="text-muted">No images uploaded yet.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="productImages" class="form-label">Upload New Images (Max 5 images, 5MB each)</label>
                                <input type="file" class="form-control" id="productImages" name="productImages[]" multiple accept="image/*">
                                <div class="form-text">Select new images to add to your product. You can select up to 5 images.</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-secondary" onclick="cancelEdit()">Cancel</button>
                                <button type="submit" class="btn btn-success">Update Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('editProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('id', document.getElementById('productId').value);
            formData.append('productName', document.getElementById('productName').value);
            formData.append('productCategory', document.getElementById('productCategory').value);
            formData.append('productDescription', document.getElementById('productDescription').value);
            formData.append('price', document.getElementById('price').value);
            formData.append('stock', document.getElementById('stock').value);
            formData.append('unit', document.getElementById('unit').value);
            
            // Add new images if any
            const imageFiles = document.getElementById('productImages').files;
            for (let i = 0; i < imageFiles.length; i++) {
                formData.append('productImages[]', imageFiles[i]);
            }
            
            fetch('/ecommerce_farmers_fishers/seller/update_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product updated successfully!');
                    window.location.href = '/ecommerce_farmers_fishers/seller/dashboard.php?tab=products';
                } else {
                    alert('Error updating product: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating product. Please try again.');
            });
        });
        
        function deleteImage(imageId) {
            if (confirm('Are you sure you want to delete this image?')) {
                const formData = new FormData();
                formData.append('action', 'delete_image');
                formData.append('image_id', imageId);
                
                fetch('/ecommerce_farmers_fishers/seller/update_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Image deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error deleting image: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting image. Please try again.');
                });
            }
        }
        
        function setPrimaryImage(imageId, productId) {
            if (confirm('Set this image as the primary image for this product?')) {
                const formData = new FormData();
                formData.append('action', 'set_primary');
                formData.append('image_id', imageId);
                formData.append('product_id', productId);
                
                fetch('/ecommerce_farmers_fishers/seller/update_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Primary image updated successfully!');
                        location.reload();
                    } else {
                        alert('Error updating primary image: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating primary image. Please try again.');
                });
            }
        }
        
        function cancelEdit() {
            if (confirm('Are you sure you want to cancel editing? Changes will be lost.')) {
                window.location.href = '/ecommerce_farmers_fishers/seller/dashboard.php?tab=products';
            }
        }
    </script>
</body>
</html>