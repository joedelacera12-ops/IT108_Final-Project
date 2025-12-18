<?php
require_once __DIR__ . '/includes/db.php';

$pdo = get_db();

try {
    $stmt = $pdo->prepare("SELECT id, name, description FROM products WHERE name LIKE ? OR name LIKE ?");
    $stmt->execute(['%Bangus%', '%tilapia%']);
    $products = $stmt->fetchAll();
    
    echo "<h2>Products matching 'Bangus' or 'tilapia':</h2>\n";
    if (empty($products)) {
        echo "<p>No products found.</p>\n";
    } else {
        foreach ($products as $product) {
            echo "<p>ID: {$product['id']}, Name: {$product['name']}, Description: {$product['description']}</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>\n";
}
?>