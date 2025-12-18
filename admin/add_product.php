<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins can add products
require_role('admin');
$user = current_user();

// Log the request for debugging
error_log("Add product request received: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$name = trim($_POST['productName'] ?? '');
$categorySlug = trim($_POST['productCategory'] ?? '');
$autoCategory = trim($_POST['autoCategory'] ?? ''); // Get auto-assigned category for sellers
$description = trim($_POST['productDescription'] ?? '');
$price = (float)($_POST['price'] ?? 0);
$stock = (int)($_POST['stock'] ?? 0);
$unit = trim($_POST['unit'] ?? ''); // This will be stored in weight_unit field
$sellerId = (int)($_POST['seller_id'] ?? 0);
$status = trim($_POST['productStatus'] ?? 'published'); // Get product status (published or draft) - default to published

error_log("Product data: name=$name, categorySlug=$categorySlug, autoCategory=$autoCategory, status=$status, sellerId=$sellerId");

// Validate required fields
if (empty($name) || empty($description) || $price <= 0 || $stock < 0 || empty($sellerId)) {
    error_log("Missing required fields");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Validate status - map UI values to database values
$dbStatus = 'draft'; // Default to draft
if ($status === 'active') {
    $dbStatus = 'published'; // 'active' in UI maps to 'published' in database
} else if ($status === 'draft') {
    $dbStatus = 'draft';
}

error_log("Mapped status: UI status=$status, DB status=$dbStatus");

try {
    $pdo = get_db();

    // Resolve category_id by slug if provided
    $categoryId = null;
    if ($categorySlug !== '') {
        try {
            $c = $pdo->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
            $c->execute([$categorySlug]);
            $categoryId = $c->fetchColumn() ?: null;
            error_log("Found category ID: $categoryId for slug: $categorySlug");
        } catch (Exception $e) {
            error_log("Error resolving category: " . $e->getMessage());
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
    
    error_log("Inserting product: seller_id=$sellerId, category_id=$categoryId, name=$name, slug=$slug, description=$description, price=$price, stock=$stock, weight_unit=$weightUnit, status=$dbStatus");

    // Insert product with the specified status
    // Note: Removed 'unit' field since it doesn't exist in the database schema
    $stmt = $pdo->prepare('INSERT INTO products (seller_id, category_id, name, slug, description, price, stock, weight_unit, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $result = $stmt->execute([$sellerId, $categoryId, $name, $slug, $description, $price, $stock, $weightUnit, $dbStatus]);
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
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Product added successfully!']);
    exit;
} catch (Exception $e) {
    error_log("Server error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit;
}