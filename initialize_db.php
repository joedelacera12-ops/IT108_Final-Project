<?php
/**
 * Database Initialization Script for AgriSea Marketplace
 * This script initializes the database with all required tables and seed data
 */

require_once 'includes/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>AgriSea Database Initialization</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<div class='container py-5'>
    <h1 class='mb-4'>AgriSea Database Initialization</h1>";

try {
    $pdo = get_db();
    
    echo "<h3>Initializing Database...</h3>";
    
    // Read and execute the schema file
    $schemaFile = __DIR__ . '/agrisea_complete_database.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $sql = file_get_contents($schemaFile);
    if (empty($sql)) {
        throw new Exception("Schema file is empty");
    }
    
    // Split the SQL into individual statements
    $statements = explode(';', $sql);
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
        } catch (Exception $e) {
            // Skip errors for DROP statements if tables don't exist
            if (stripos($statement, 'DROP') !== false) {
                $successCount++;
                continue;
            }
            
            echo "<p class='text-warning'>Warning: " . $e->getMessage() . "</p>";
            echo "<pre>" . htmlspecialchars(substr($statement, 0, 100)) . "...</pre>";
            $errorCount++;
        }
    }
    
    echo "<p class='text-success'>✓ Executed $successCount statements</p>";
    if ($errorCount > 0) {
        echo "<p class='text-warning'>⚠ Encountered $errorCount errors (these may be expected)</p>";
    }
    
    // Verify that essential tables exist
    echo "<h3>Verifying Tables...</h3>";
    $essentialTables = ['user_roles', 'users', 'categories', 'products'];
    
    foreach ($essentialTables as $table) {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->rowCount() > 0) {
                echo "<p class='text-success'>✓ Table '$table' exists</p>";
            } else {
                echo "<p class='text-danger'>✗ Table '$table' does not exist</p>";
            }
        } catch (Exception $e) {
            echo "<p class='text-danger'>✗ Error checking table '$table': " . $e->getMessage() . "</p>";
        }
    }
    
    // Verify admin account exists
    echo "<h3>Checking Admin Account...</h3>";
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(['admin@agrisea.local']);
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<p class='text-success'>✓ Admin account exists: " . htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) . "</p>";
        } else {
            echo "<p class='text-warning'>⚠ Admin account not found. You may need to create one.</p>";
            
            // Try to create admin account
            try {
                $roleStmt = $pdo->prepare("SELECT id FROM user_roles WHERE name = ? LIMIT 1");
                $roleStmt->execute(['admin']);
                $adminRoleId = $roleStmt->fetchColumn();
                
                if ($adminRoleId) {
                    $passwordHash = password_hash('@Abcde12345', PASSWORD_DEFAULT);
                    $insertStmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password_hash, role_id, status, street_address, city, province) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insertStmt->execute([
                        'Admin',
                        'User',
                        'admin@agrisea.local',
                        '09171234567',
                        $passwordHash,
                        $adminRoleId,
                        'approved',
                        '123 Admin Street',
                        'Quezon City',
                        'Metro Manila'
                    ]);
                    
                    $userId = $pdo->lastInsertId();
                    echo "<p class='text-success'>✓ Admin account created with ID: $userId</p>";
                } else {
                    echo "<p class='text-danger'>✗ Could not find admin role ID</p>";
                }
            } catch (Exception $e) {
                echo "<p class='text-danger'>✗ Error creating admin account: " . $e->getMessage() . "</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='text-danger'>✗ Error checking admin account: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Database Initialization Complete</h3>";
    echo "<p class='text-success'>The database has been initialized successfully.</p>";
    
} catch (Exception $e) {
    echo "<p class='text-danger'>✗ Initialization failed: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "
<div class='mt-4'>
    <a href='/ecommerce_farmers_fishers/php/index.php' class='btn btn-primary'>Go to Homepage</a>
    <a href='/ecommerce_farmers_fishers/check_accounts.php' class='btn btn-secondary'>Check Accounts</a>
    <a href='/ecommerce_farmers_fishers/health_check.php' class='btn btn-info'>Run Health Check</a>
</div>
</div>
</body>
</html>";
?>