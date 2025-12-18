<?php
require_once 'includes/db.php';

try {
    $pdo = get_db();
    
    // Test if v_seller_performance view exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.views WHERE table_schema = 'agrisea' AND table_name = 'v_seller_performance'");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "v_seller_performance view exists\n";
        
        // Test the view
        $stmt = $pdo->query("SELECT * FROM v_seller_performance LIMIT 1");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "View data: ";
        print_r($results);
    } else {
        echo "v_seller_performance view does not exist\n";
    }
    
    // Test if v_delivery_partner_performance view exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.views WHERE table_schema = 'agrisea' AND table_name = 'v_delivery_partner_performance'");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "v_delivery_partner_performance view exists\n";
        
        // Test the view
        $stmt = $pdo->query("SELECT * FROM v_delivery_partner_performance LIMIT 1");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "View data: ";
        print_r($results);
    } else {
        echo "v_delivery_partner_performance view does not exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>