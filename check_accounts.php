<?php
require_once 'includes/db.php';

try {
    $pdo = get_db();
    
    echo "<h1>Account Check</h1>";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "<p>No users table found</p>";
        exit;
    }
    
    // Check user roles table
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_roles'");
    if ($stmt->rowCount() == 0) {
        echo "<p>No user_roles table found</p>";
    } else {
        echo "<h2>User Roles</h2>";
        $stmt = $pdo->query("SELECT * FROM user_roles");
        $roles = $stmt->fetchAll();
        foreach ($roles as $role) {
            echo "<p>ID: " . $role['id'] . " | Name: " . $role['name'] . "</p>";
        }
    }
    
    // Check users
    echo "<h2>Users</h2>";
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p>No users found in database</p>";
    } else {
        foreach ($users as $user) {
            echo "<p>ID: " . $user['id'] . " | Name: " . $user['first_name'] . " " . ($user['last_name'] ?? '') . 
                 " | Email: " . $user['email'] . " | Role ID: " . $user['role_id'] . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>