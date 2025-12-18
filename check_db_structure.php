<?php
require_once 'includes/db.php';

try {
    $pdo = get_db();
    
    // Check if country column exists in user_addresses table
    $stmt = $pdo->query("SHOW COLUMNS FROM user_addresses LIKE 'country'");
    $countryColumn = $stmt->fetch();
    
    if ($countryColumn) {
        echo "Country column exists in user_addresses table\n";
        print_r($countryColumn);
    } else {
        echo "Country column does NOT exist in user_addresses table\n";
    }
    
    // Show all columns in user_addresses table
    echo "\nAll columns in user_addresses table:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM user_addresses");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>