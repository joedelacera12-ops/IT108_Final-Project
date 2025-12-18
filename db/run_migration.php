<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = get_db();
    
    // Check if country column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM `user_addresses` LIKE 'country'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        // Add country column to user_addresses table
        $pdo->exec("ALTER TABLE `user_addresses` ADD COLUMN `country` varchar(100) DEFAULT NULL AFTER `province`");
        echo "Country column added successfully to user_addresses table.\n";
    } else {
        echo "Country column already exists in user_addresses table.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>