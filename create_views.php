<?php
require_once 'includes/db.php';

try {
    $pdo = get_db();
    
    echo "Creating database views...\n";
    
    // Create v_seller_performance view if it doesn't exist
    echo "Creating v_seller_performance view...\n";
    $pdo->exec("DROP VIEW IF EXISTS v_seller_performance");
    
    $pdo->exec("
        CREATE VIEW v_seller_performance AS
        SELECT 
            u.id AS seller_id,
            CONCAT(u.first_name, ' ', u.last_name) AS seller_name,
            u.seller_type AS seller_type,
            COALESCE(SUM(oi.total), 0) AS total_income,
            COALESCE(SUM(oi.quantity), 0) AS total_products_sold,
            COUNT(DISTINCT o.id) AS total_orders,
            COALESCE(AVG(sr.rating), 0) AS avg_rating,
            COUNT(sr.id) AS total_ratings,
            COUNT(CASE WHEN o.status = 'delivered' THEN 1 END) AS delivered_orders,
            COUNT(CASE WHEN od.status = 'delivered' THEN 1 END) AS successfully_delivered_orders
        FROM users u
        LEFT JOIN products p ON u.id = p.seller_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
        LEFT JOIN order_deliveries od ON o.delivery_id = od.id
        LEFT JOIN seller_ratings sr ON u.id = sr.seller_id
        WHERE u.role_id = (SELECT id FROM user_roles WHERE name = 'seller')
        AND o.created_at IS NOT NULL
        GROUP BY u.id, u.first_name, u.last_name, u.seller_type
        ORDER BY COALESCE(SUM(oi.total), 0) DESC
    ");
    
    echo "✓ View 'v_seller_performance' successfully created\n";
    
    // Create v_delivery_partner_performance view
    echo "Creating v_delivery_partner_performance view...\n";
    $pdo->exec("DROP VIEW IF EXISTS v_delivery_partner_performance");
    
    $pdo->exec("
        CREATE VIEW v_delivery_partner_performance AS
        SELECT 
            dp.id AS delivery_partner_id,
            dp.name AS delivery_partner_name,
            dp.phone AS contact_phone,
            dp.email AS contact_email,
            COUNT(od.id) AS total_assignments,
            COUNT(CASE WHEN od.status = 'delivered' THEN 1 END) AS completed_deliveries,
            COUNT(CASE WHEN od.status = 'delivered' AND od.delivered_at <= o.estimated_delivery THEN 1 END) AS on_time_deliveries,
            COALESCE(AVG(CASE WHEN od.status = 'delivered' THEN DATEDIFF(od.delivered_at, od.assigned_at) END), 0) AS avg_delivery_days,
            COALESCE(AVG(CASE WHEN od.status = 'delivered' AND od.delivered_at <= o.estimated_delivery THEN 1 ELSE 0 END) * 100, 0) AS on_time_percentage
        FROM delivery_partners dp
        LEFT JOIN order_deliveries od ON dp.id = od.delivery_partner_id
        LEFT JOIN orders o ON od.order_id = o.id
        GROUP BY dp.id, dp.name, dp.phone, dp.email
        ORDER BY COUNT(CASE WHEN od.status = 'delivered' THEN 1 END) DESC
    ");
    
    echo "✓ View 'v_delivery_partner_performance' successfully created\n";
    
    echo "All views created successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error creating views: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>