<?php
require_once 'includes/db.php';

try {
    $pdo = get_db();
    $stmt = $pdo->query('DESCRIBE delivery_partners');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Delivery Partners Table Structure:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check if user_id column exists
    $hasUserId = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'user_id') {
            $hasUserId = true;
            break;
        }
    }
    
    echo "\nHas user_id column: " . ($hasUserId ? 'Yes' : 'No') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>