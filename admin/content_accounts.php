<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins may access
require_role('admin');
$user = current_user();
$pdo = get_db();

// Get all users for accounts management
$allUsers = [];
try {
    // Simple query to get all users
    $sql = "SELECT u.*, 
                 ur.name as role_name
          FROM users u 
          LEFT JOIN user_roles ur ON u.role_id = ur.id
          ORDER BY u.created_at DESC";
    
    $usersStmt = $pdo->prepare($sql);
    $usersStmt->execute();
    $allUsers = $usersStmt ? $usersStmt->fetchAll() : [];
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $allUsers = [];
}
?>
