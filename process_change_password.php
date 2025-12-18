<?php
/**
 * AgriSea Marketplace - Change Password Processing
 * This script processes password change requests
 */

// Include required files
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// Set JSON header for AJAX responses
header('Content-Type: application/json');

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Check if user is logged in
    $user = current_user();
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'You must be logged in to change your password.']);
        exit;
    }
    
    $pdo = get_db();
    
    // Get the action from the request
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate input
            if (!$current_password || !$new_password || !$confirm_password) {
                echo json_encode(['success' => false, 'error' => 'Please fill in all password fields.']);
                exit;
            }
            
            if ($new_password !== $confirm_password) {
                echo json_encode(['success' => false, 'error' => 'New passwords do not match.']);
                exit;
            }
            
            // Enhanced password validation
            if (strlen($new_password) < 8) {
                echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters long.']);
                exit;
            }
            
            if (!preg_match('/[A-Z]/', $new_password)) {
                echo json_encode(['success' => false, 'error' => 'Password must contain at least one uppercase letter.']);
                exit;
            }
            
            if (!preg_match('/[a-z]/', $new_password)) {
                echo json_encode(['success' => false, 'error' => 'Password must contain at least one lowercase letter.']);
                exit;
            }
            
            if (!preg_match('/\d/', $new_password)) {
                echo json_encode(['success' => false, 'error' => 'Password must contain at least one number.']);
                exit;
            }
            
            if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $new_password)) {
                echo json_encode(['success' => false, 'error' => 'Password must contain at least one special character.']);
                exit;
            }
            
            // Get current user's password hash from database
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$user['id']]);
            $user_data = $stmt->fetch();
            
            if (!$user_data) {
                echo json_encode(['success' => false, 'error' => 'User not found.']);
                exit;
            }
            
            // Verify current password
            if (!password_verify($current_password, $user_data['password_hash'])) {
                echo json_encode(['success' => false, 'error' => 'Current password is incorrect.']);
                exit;
            }
            
            // Check if new password is the same as current password
            if (password_verify($new_password, $user_data['password_hash'])) {
                echo json_encode(['success' => false, 'error' => 'New password must be different from current password.']);
                exit;
            }
            
            // Hash the new password
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update the user's password
            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([$new_password_hash, $user['id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Password successfully changed!'
            ]);
            exit;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action.']);
            exit;
    }
    
} catch (Exception $e) {
    // In production log error. For now, show a basic error.
    error_log('Password change error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error. Please try again later.']);
    exit;
}
?>