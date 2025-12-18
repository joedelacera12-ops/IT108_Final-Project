<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only sellers can add products
require_role('seller');
$user = current_user();

// Log the request for debugging
error_log("Add product request received: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=addProduct&error=Method not allowed');
    exit;
}

$name = trim($_POST['productName'] ?? '');
$categorySlug = trim($_POST['productCategory'] ?? '');
$autoCategory = trim($_POST['autoCategory'] ?? ''); // Get auto-assigned category for sellers
$description = trim($_POST['productDescription'] ?? '');
$price = (float)($_POST['price'] ?? 0);
$stock = (int)($_POST['stock'] ?? 0);
$unit = trim($_POST['unit'] ?? ''); // This will be stored in weight_unit field
$status = trim($_POST['productStatus'] ?? 'published'); // Get product status (published or draft) - default to published

error_log("Product data: name=$name, categorySlug=$categorySlug, autoCategory=$autoCategory, status=$status");
error_log("Seller type: $sellerType");

// Validate status - map UI values to database values
$dbStatus = 'draft'; // Default to draft
if ($status === 'active') {
    $dbStatus = 'published'; // 'active' in UI maps to 'published' in database
} else if ($status === 'draft') {
    $dbStatus = 'draft';
}

error_log("Mapped status: UI status=$status, DB status=$dbStatus");

// Get seller type to automatically assign category if needed
$sellerType = null;
try {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT seller_type FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$user['id']]);
    $sellerType = $stmt->fetchColumn();
    error_log("Seller type: $sellerType");
} catch (Exception $e) {
    error_log("Error getting seller type: " . $e->getMessage());
    $sellerType = null;
}

// For sellers, use auto-assigned category if available
if (!empty($autoCategory)) {
    $categorySlug = $autoCategory;
    error_log("Using autoCategory: $categorySlug");
} else if ($sellerType === 'farmer') {
    // For farmers, automatically assign to 'vegetables' category
    $categorySlug = 'vegetables';
    error_log("Auto-assigning vegetables category for farmer");
} else if ($sellerType === 'fisher') {
    // For fishers, automatically assign to 'seafood' category
    $categorySlug = 'seafood';
    error_log("Auto-assigning seafood category for fisher");
} else {
    // For other sellers or if seller type is not set, use a default category or allow without category
    // Only productName is required
    if (!$name) {
        error_log("Missing required field: name=$name");
        header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=addProduct&error=Product name is required');
        exit;
    }
    // If no category is provided, we'll try to find a default or allow null
    error_log("No category specified, proceeding with optional category");
}

try {
    $pdo = get_db();

    // Resolve category_id by slug if provided
    $categoryId = null;
    if (!empty($categorySlug)) {
        try {
            $c = $pdo->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
            $c->execute([$categorySlug]);
            $categoryId = $c->fetchColumn() ?: null;
            error_log("Found category ID: $categoryId for slug: $categorySlug");
        } catch (Exception $e) {
            error_log("Error resolving category: " . $e->getMessage());
            $categoryId = null;
        }
    } else {
        // If no category is specified, try to find a default category
        try {
            $c = $pdo->prepare('SELECT id FROM categories ORDER BY id LIMIT 1');
            $c->execute();
            $categoryId = $c->fetchColumn() ?: null;
            error_log("Using default category ID: $categoryId");
        } catch (Exception $e) {
            error_log("Error finding default category: " . $e->getMessage());
            $categoryId = null;
        }
    }

    // Generate unique slug
    $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)));
    $baseSlug = trim($baseSlug, '-');
    if ($baseSlug === '') $baseSlug = 'product';
    $slug = $baseSlug;
    $i = 1;
    while (true) {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM products WHERE slug = ?');
        $chk->execute([$slug]);
        if ((int)$chk->fetchColumn() === 0) break;
        $slug = $baseSlug . '-' . (++$i);
    }

    // Map the unit field to weight_unit in the database
    $weightUnit = !empty($unit) ? $unit : null;
    
    error_log("Inserting product: seller_id=" . $user['id'] . ", category_id=$categoryId, name=$name, slug=$slug, description=$description, price=$price, stock=$stock, weight_unit=$weightUnit, status=$dbStatus");
    error_log("Prepared statement: INSERT INTO products (seller_id, category_id, name, slug, description, price, stock, weight_unit, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Insert product with the specified status
    // Note: Removed 'unit' field since it doesn't exist in the database schema
    $stmt = $pdo->prepare('INSERT INTO products (seller_id, category_id, name, slug, description, price, stock, weight_unit, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $result = $stmt->execute([(int)$user['id'], $categoryId, $name, $slug, $description, $price, $stock, $weightUnit, $dbStatus]);
    $productId = $pdo->lastInsertId();
    
    error_log("Product insert result: " . ($result ? "success" : "failed") . ", product ID: $productId");

    // Handle file uploads
    if (isset($_FILES['productImages']) && is_array($_FILES['productImages']['name']) && count($_FILES['productImages']['name']) > 0) {
        error_log("Processing file uploads");
        $uploadDir = __DIR__ . '/../uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileCount = count($_FILES['productImages']['name']);
        $maxFiles = min($fileCount, 5); // Limit to 5 images

        for ($i = 0; $i < $maxFiles; $i++) {
            if ($_FILES['productImages']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['productImages']['name'][$i];
                $fileTmpName = $_FILES['productImages']['tmp_name'][$i];
                $fileSize = $_FILES['productImages']['size'][$i];
                $fileType = $_FILES['productImages']['type'][$i];

                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($fileType, $allowedTypes)) {
                    error_log("Skipping invalid file type: $fileType");
                    continue; // Skip invalid file types
                }

                // Validate file size (max 5MB)
                if ($fileSize > 5 * 1024 * 1024) {
                    error_log("Skipping large file: $fileSize bytes");
                    continue; // Skip large files
                }

                // Generate unique filename
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = $productId . '_' . ($i + 1) . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $newFileName;

                // Move uploaded file
                if (move_uploaded_file($fileTmpName, $uploadPath)) {
                    // Save to database
                    $isPrimary = ($i === 0) ? 1 : 0; // First image is primary
                    $imageStmt = $pdo->prepare('INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, ?, ?)');
                    $imageStmt->execute([$productId, 'uploads/products/' . $newFileName, $isPrimary, $i]);
                    error_log("Saved image: uploads/products/$newFileName");
                } else {
                    error_log("Failed to move uploaded file: $fileName");
                }
            }
        }
    }

    error_log("Product added successfully with ID: $productId");
    header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=addProduct&success=Product added successfully!');
    exit;
} catch (Exception $e) {
    error_log("Server error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    header('Location: /ecommerce_farmers_fishers/seller/dashboard.php?tab=addProduct&error=Server error: ' . urlencode($e->getMessage()));
    exit;
}