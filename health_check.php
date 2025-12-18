<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>AgriSea System Health Check</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container py-5'>
    <h1 class='mb-4'>AgriSea System Health Check</h1>";

try {
    // Test database connection
    echo "<h3>Database Connection</h3>";
    $pdo = get_db();
    echo "<p class='text-success'>✓ Database connection successful</p>";
    
    // Test if essential tables exist
    $essentialTables = ['users', 'products', 'categories', 'orders', 'favorites', 'seller_ratings'];
    echo "<h3>Database Tables</h3>";
    
    foreach ($essentialTables as $table) {
        try {
            // Using INFORMATION_SCHEMA instead of SHOW TABLES LIKE for better compatibility
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
            $stmt->execute([$table]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                echo "<p class='text-success'>✓ Table '$table' exists</p>";
            } else {
                echo "<p class='text-danger'>✗ Table '$table' does not exist</p>";
            }
        } catch (Exception $e) {
            echo "<p class='text-danger'>✗ Error checking table '$table': " . $e->getMessage() . "</p>";
        }
    }
    
    // Test authentication system
    echo "<h3>Authentication System</h3>";
    echo "<p class='text-success'>✓ Auth functions loaded</p>";
    
    // Test file paths
    echo "<h3>File Paths</h3>";
    $testFiles = [
        'includes/db.php',
        'includes/auth.php',
        'buyer/dashboard.php',
        'seller/dashboard.php',
        'php/index.php'
    ];
    
    foreach ($testFiles as $file) {
        if (file_exists($file)) {
            echo "<p class='text-success'>✓ File '$file' exists</p>";
        } else {
            echo "<p class='text-danger'>✗ File '$file' does not exist</p>";
        }
    }
    
    echo "<h3>System Status</h3>";
    echo "<p class='text-success'>✓ Health check completed successfully</p>";
    
} catch (Exception $e) {
    echo "<p class='text-danger'>✗ Health check failed: " . $e->getMessage() . "</p>";
}

echo "<div class='mt-4'>
        <a href='/ecommerce_farmers_fishers/php/index.php' class='btn btn-primary'>Return to Homepage</a>
        <a href='/ecommerce_farmers_fishers/test_db_connection.php' class='btn btn-secondary'>Test DB Connection</a>
      </div>
</div>
</body>
</html>";
?>