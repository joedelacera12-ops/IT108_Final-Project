<?php
/**
 * Low Stock Notifier Script
 * Checks for products with low stock and sends notifications to sellers
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notification_system.php';

try {
    $pdo = get_db();
    
    // Find products with low stock (less than 10 units)
    $stmt = $pdo->prepare("
        SELECT p.*, u.first_name, u.last_name, u.email 
        FROM products p
        JOIN users u ON p.seller_id = u.id
        WHERE p.stock <= 10 
        AND p.stock > 0
        AND p.status = 'active'
    ");
    
    $stmt->execute();
    $lowStockProducts = $stmt->fetchAll();
    
    $notificationSystem = new NotificationSystem();
    
    foreach ($lowStockProducts as $product) {
        // Create low stock notification for seller
        $notificationSystem->createLowStockNotification(
            $product['seller_id'],
            $product['id'],
            $product['name'],
            $product['stock']
        );
        
        // Log the notification
        error_log("Low stock notification sent to seller ID: " . $product['seller_id'] . " for product: " . $product['name']);
    }
    
    echo "Processed " . count($lowStockProducts) . " low stock products.\n";
    
} catch (Exception $e) {
    error_log("Low stock notifier script error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
?>