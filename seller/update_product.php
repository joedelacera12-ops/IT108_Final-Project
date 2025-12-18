<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('seller');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = get_db();
    
    // Handle special actions (delete image, set primary)
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_image':
                $imageId = intval($_POST['image_id'] ?? 0);
                if (!$imageId) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing image ID']);
                    exit;
                }
                
                // Verify image ownership
                $checkStmt = $pdo->prepare('SELECT pi.*, p.seller_id FROM product_images pi JOIN products p ON pi.product_id = p.id WHERE pi.id = ?');
                $checkStmt->execute([$imageId]);
                $image = $checkStmt->fetch();
                
                if (!$image || $image['seller_id'] != $user['id']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Forbidden']);
                    exit;
                }
                
                // Delete file from filesystem
                $filePath = __DIR__ . '/../' . $image['image_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // Delete from database
                $deleteStmt = $pdo->prepare('DELETE FROM product_images WHERE id = ?');
                $deleteStmt->execute([$imageId]);
                
                echo json_encode(['success' => true]);
                exit;
                
            case 'set_primary':
                $imageId = intval($_POST['image_id'] ?? 0);
                $productId = intval($_POST['product_id'] ?? 0);
                
                if (!$imageId || !$productId) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing image or product ID']);
                    exit;
                }
                
                // Verify product ownership
                $checkStmt = $pdo->prepare('SELECT seller_id FROM products WHERE id = ?');
                $checkStmt->execute([$productId]);
                $product = $checkStmt->fetch();
                
                if (!$product || $product['seller_id'] != $user['id']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Forbidden']);
                    exit;
                }
                
                // Set current primary images to non-primary
                $updateStmt = $pdo->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = ?');
                $updateStmt->execute([$productId]);
                
                // Set selected image as primary
                $primaryStmt = $pdo->prepare('UPDATE product_images SET is_primary = 1 WHERE id = ?');
                $primaryStmt->execute([$imageId]);
                
                echo json_encode(['success' => true]);
                exit;
        }
    }
    
    // Regular product update
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing product id']);
        exit;
    }
    
    // Verify ownership
    $check = $pdo->prepare('SELECT seller_id FROM products WHERE id = ? LIMIT 1');
    $check->execute([$id]);
    $row = $check->fetch();
    if (!$row || $row['seller_id'] != $user['id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    
    $name = trim($_POST['productName'] ?? '');
    $categorySlug = trim($_POST['productCategory'] ?? '');
    $description = trim($_POST['productDescription'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $unit = trim($_POST['unit'] ?? ''); // This will be stored in weight_unit field
    
    if (!$name) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    // Resolve category_id by slug if provided
    $categoryId = null;
    if ($categorySlug !== '') {
        try {
            $c = $pdo->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
            $c->execute([$categorySlug]);
            $categoryId = $c->fetchColumn() ?: null;
        } catch (Exception $e) {
            $categoryId = null;
        }
    }
    
    // Update product - map unit to weight_unit
    $fields = [];
    $params = [];
    $fields[] = 'name = ?'; $params[] = $name;
    $fields[] = 'category_id = ?'; $params[] = $categoryId;
    $fields[] = 'description = ?'; $params[] = $description;
    $fields[] = 'price = ?'; $params[] = $price;
    $fields[] = 'stock = ?'; $params[] = $stock;
    $fields[] = 'weight_unit = ?'; $params[] = $unit ?: null; // Changed from 'unit' to 'weight_unit'
    
    $params[] = $id;
    $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $upd = $pdo->prepare($sql);
    $upd->execute($params);
    
    // Handle new file uploads
    if (isset($_FILES['productImages']) && is_array($_FILES['productImages']['name']) && count($_FILES['productImages']['name']) > 0) {
        $uploadDir = __DIR__ . '/../uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileCount = count($_FILES['productImages']['name']);
        $maxFiles = min($fileCount, 5); // Limit to 5 images
        
        // Check current image count
        $currentCountStmt = $pdo->prepare('SELECT COUNT(*) FROM product_images WHERE product_id = ?');
        $currentCountStmt->execute([$id]);
        $currentCount = (int)$currentCountStmt->fetchColumn();
        
        for ($i = 0; $i < $maxFiles && $currentCount < 5; $i++) {
            if ($_FILES['productImages']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['productImages']['name'][$i];
                $fileTmpName = $_FILES['productImages']['tmp_name'][$i];
                $fileSize = $_FILES['productImages']['size'][$i];
                $fileType = $_FILES['productImages']['type'][$i];
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($fileType, $allowedTypes)) {
                    continue; // Skip invalid file types
                }
                
                // Validate file size (max 5MB)
                if ($fileSize > 5 * 1024 * 1024) {
                    continue; // Skip large files
                }
                
                // Generate unique filename
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = $id . '_' . ($currentCount + 1) . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $newFileName;
                
                // Move uploaded file
                if (move_uploaded_file($fileTmpName, $uploadPath)) {
                    // Check if this should be primary (if no primary image exists)
                    $isPrimary = 0;
                    if ($currentCount === 0) {
                        $primaryCheck = $pdo->prepare('SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_primary = 1');
                        $primaryCheck->execute([$id]);
                        if ((int)$primaryCheck->fetchColumn() === 0) {
                            $isPrimary = 1;
                        }
                    }
                    
                    // Save to database
                    $imageStmt = $pdo->prepare('INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, ?, ?)');
                    $imageStmt->execute([$id, 'uploads/products/' . $newFileName, $isPrimary, $currentCount]);
                    $currentCount++;
                }
            }
        }
    }
    
    echo json_encode(['success' => true]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
}