<?php
/**
 * AgriSea Marketplace - Login Processing
 * This script processes login requests from the login form
 */

// Include required files
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/redirect_handler.php';

// Set JSON header for AJAX responses
header('Content-Type: application/json');

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get POST data
$identifier = $_POST['identifier'] ?? '';
$password = $_POST['password'] ?? '';

// Validate required fields
if (empty($identifier) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Please fill in both identifier and password.']);
    exit;
}

// Validate identifier (email or phone)
$isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
$isPhone = preg_match('/^(\+63|0)[0-9]{10}$/', str_replace(' ', '', $identifier));

if (!$isEmail && !$isPhone) {
    echo json_encode(['success' => false, 'error' => 'Please enter a valid email address or phone number.']);
    exit;
}

// Check credentials against database
try {
    $pdo = get_db();
    
    // Prepare query based on identifier type
    if ($isEmail) {
        $stmt = $pdo->prepare('SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.role_id, u.status, u.password_hash, ur.name as role FROM users u LEFT JOIN user_roles ur ON u.role_id = ur.id WHERE u.email = ?');
    } else {
        // Normalize phone number
        $normalizedPhone = str_replace([' ', '-', '(', ')'], '', $identifier);
        if (strpos($normalizedPhone, '+63') === 0) {
            $normalizedPhone = '0' . substr($normalizedPhone, 3);
        }
        $stmt = $pdo->prepare('SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.role_id, u.status, u.password_hash, ur.name as role FROM users u LEFT JOIN user_roles ur ON u.role_id = ur.id WHERE u.phone = ?');
    }
    
    $stmt->execute([$identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Invalid credentials. Please check your email/phone and password.']);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid credentials. Please check your email/phone and password.']);
        exit;
    }
    
    // Check if seller account is approved
    if ($user['role_id'] == 2 && $user['status'] !== 'approved') {
        echo json_encode([
            'success' => false, 
            'error' => 'Your seller account is pending approval by admin. Please check back later.',
            'pending' => true
        ]);
        exit;
    }
    
    // Login successful - create session
    login_user($user);
    
    // Check if there's a redirect URL stored in session
    $redirect = $_SESSION['redirect_after_login'] ?? null;
    unset($_SESSION['redirect_after_login']); // Clear the redirect URL
    
    // If no specific redirect, determine based on role
    if (!$redirect) {
        switch ($user['role_id']) {
            case 1: // Admin
                $redirect = '/ecommerce_farmers_fishers/admin/dashboard.php';
                break;
            case 2: // Seller
                $redirect = '/ecommerce_farmers_fishers/seller/dashboard.php';
                break;
            case 4: // Delivery Partner
                $redirect = '/ecommerce_farmers_fishers/delivery_partner/dashboard.php';
                break;
            default: // Buyer or any other role
                $redirect = '/ecommerce_farmers_fishers/buyer/dashboard.php';
                break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful. Redirecting...',
        'redirect' => $redirect
    ]);
    exit;
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    // For debugging, you might want to temporarily show the actual error (remove this in production)
    // echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    echo json_encode(['success' => false, 'error' => 'Server error. Please try again later.']);
    exit;
}
?>