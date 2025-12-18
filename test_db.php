<?php
require_once 'includes/db.php';

try {
    $pdo = get_db();
    
    // Check if business_profiles table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'business_profiles'");
    if ($stmt->rowCount() > 0) {
        echo "business_profiles table exists\n";
    } else {
        echo "business_profiles table does not exist\n";
    }
    
    // Check if user_addresses table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_addresses'");
    if ($stmt->rowCount() > 0) {
        echo "user_addresses table exists\n";
    } else {
        echo "user_addresses table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>