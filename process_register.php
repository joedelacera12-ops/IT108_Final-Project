<?php
/**
 * AgriSea Marketplace - Registration Processing
 * This script processes registration requests from the register form
 */

// Include required files
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/redirect_handler.php';

// Set JSON header for AJAX responses
header('Content-Type: application/json');

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$name_extension = trim($_POST['extension'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$accttype = $_POST['accttype'] ?? '';
$phone = trim($_POST['phone'] ?? '');
$alternate_phone = trim($_POST['alternate_phone'] ?? '');
$preferred_contact = trim($_POST['preferred_contact'] ?? 'email');
$street_address = trim($_POST['street_address'] ?? '');
$city = trim($_POST['city'] ?? '');
$province = trim($_POST['province'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');
$country = trim($_POST['country'] ?? '');
$birthdate = trim($_POST['birthdate'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$business_name = trim($_POST['business_name'] ?? '');
$business_type = trim($_POST['business_type'] ?? '');
$years_in_business = $_POST['years_in_business'] !== null ? (int)$_POST['years_in_business'] : null;
$business_license = trim($_POST['business_license'] ?? '');
$farm_size = $_POST['farm_size'] !== null && $_POST['farm_size'] !== '' ? (float)$_POST['farm_size'] : null;
$crop_types_text = trim($_POST['crop_types'] ?? '');
$fishing_method = trim($_POST['fishing_method'] ?? '');
$fish_types_text = trim($_POST['fish_types'] ?? '');
$newsletter = isset($_POST['newsletter']) ? 1 : 0;
$marketing = isset($_POST['marketing']) ? 1 : 0;

// Security questions
$security_question_1 = trim($_POST['security_question_1'] ?? '');
$security_answer_1 = trim($_POST['security_answer_1'] ?? '');
$security_question_2 = trim($_POST['security_question_2'] ?? '');
$security_answer_2 = trim($_POST['security_answer_2'] ?? '');

// Basic validation
if (!$first_name || !$last_name || !$email || !$password || !$street_address || !$city || !$province || !$country) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.']);
    exit;
}

// Validate security questions
if (!$security_question_1 || !$security_answer_1 || !$security_question_2 || !$security_answer_2) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all security questions and answers.']);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
    exit;
}

// Validate phone
if (!preg_match('/^(\+63|0)[0-9]{10}$/', str_replace(' ', '', $phone))) {
    echo json_encode(['success' => false, 'error' => 'Please enter a valid Philippine phone number.']);
    exit;
}

// Validate birthdate (must be between 18 and 60 years old)
if ($birthdate) {
    $birthDate = new DateTime($birthdate);
    $today = new DateTime();
    $age = $birthDate->diff($today)->y;
    
    if ($age < 18 || $age > 60) {
        echo json_encode(['success' => false, 'error' => 'Age must be between 18 and 60 years old.']);
        exit;
    }
}

// Enhanced password validation
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters long.']);
    exit;
}

if (!preg_match('/[A-Z]/', $password)) {
    echo json_encode(['success' => false, 'error' => 'Password must contain at least one uppercase letter.']);
    exit;
}

if (!preg_match('/[a-z]/', $password)) {
    echo json_encode(['success' => false, 'error' => 'Password must contain at least one lowercase letter.']);
    exit;
}

if (!preg_match('/\d/', $password)) {
    echo json_encode(['success' => false, 'error' => 'Password must contain at least one number.']);
    exit;
}

if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
    echo json_encode(['success' => false, 'error' => 'Password must contain at least one special character.']);
    exit;
}

// Check if passwords match
$confirmPassword = $_POST['confirmPassword'] ?? '';
if ($password !== $confirmPassword) {
    echo json_encode(['success' => false, 'error' => 'Passwords do not match.']);
    exit;
}

try {
    $pdo = get_db();

    // check email
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'An account with this email already exists.']);
        exit;
    }

    $role = in_array($accttype, ['farmer','fisher','retailer','restaurant']) ? 'seller' : ($accttype === 'delivery_partner' ? 'delivery_partner' : 'buyer');
    // Automatically approve customers, require admin approval for sellers and delivery partners
    $status = ($role === 'buyer') ? 'approved' : 'pending';

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Normalize list fields into JSON arrays where applicable
    $crop_types = $crop_types_text !== '' ? json_encode(array_values(array_filter(array_map('trim', explode(',', $crop_types_text))))) : null;
    $fish_types = $fish_types_text !== '' ? json_encode(array_values(array_filter(array_map('trim', explode(',', $fish_types_text))))) : null;

    $meta = [
        'accttype' => $accttype,
    ];

    // Resolve role_id from user_roles table (new schema uses role_id)
    $role_id = null;
    try {
        $r = $pdo->prepare('SELECT id FROM user_roles WHERE name = ? LIMIT 1');
        $r->execute([$role]);
        $roleRow = $r->fetch();
        if ($roleRow) $role_id = $roleRow['id'];
    } catch (Exception $e) {
        // ignore - we'll fallback to writing a 'role' column if it exists
    }

    $seller_type = in_array($accttype, ['farmer','fisher']) ? $accttype : null;

    if ($role_id !== null) {
        // insert using role_id with core user fields only
        $insert = $pdo->prepare('INSERT INTO users (first_name, middle_name, last_name, name_extension, email, phone, alternate_phone, preferred_contact, street_address, city, province, postal_code, birthdate, gender, newsletter, marketing, password_hash, role_id, status, seller_type, meta, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
        $insert->execute([
            $first_name,
            $middle_name ?: null,
            $last_name,
            $name_extension ?: null,
            $email,
            $phone,
            $alternate_phone ?: null,
            $preferred_contact ?: 'email',
            $street_address,
            $city,
            $province,
            $postal_code ?: null,
            $birthdate ?: null,
            $gender ?: null,
            $newsletter ? 1 : 0,
            $marketing ? 1 : 0,
            $password_hash,
            $role_id,
            $status,
            $seller_type,
            json_encode($meta)
        ]);
    } else {
        // fallback for older schema using 'role' column
        $insert = $pdo->prepare('INSERT INTO users (first_name, middle_name, last_name, name_extension, email, phone, alternate_phone, preferred_contact, street_address, city, province, postal_code, birthdate, gender, newsletter, marketing, password_hash, role, status, seller_type, meta, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
        $insert->execute([
            $first_name,
            $middle_name ?: null,
            $last_name,
            $name_extension ?: null,
            $email,
            $phone,
            $alternate_phone ?: null,
            $preferred_contact ?: 'email',
            $street_address,
            $city,
            $province,
            $postal_code ?: null,
            $birthdate ?: null,
            $gender ?: null,
            $newsletter ? 1 : 0,
            $marketing ? 1 : 0,
            $password_hash,
            $role,
            $status,
            $seller_type,
            json_encode($meta)
        ]);
    }

    // After creating the user, optionally auto-login for buyers and approved sellers
    $newUserId = $pdo->lastInsertId();

    // Create default address row for shipping/billing
    try {
        $addr = $pdo->prepare('INSERT INTO user_addresses (user_id, type, is_default, street_address, city, province, country, postal_code, phone, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
        $addr->execute([(int)$newUserId, 'both', 1, $street_address, $city, $province, $country, $postal_code ?: null, $phone ?: null]);
    } catch (Exception $e) {
        // If the table doesn't exist or insertion fails, continue
        error_log('Failed to create user address: ' . $e->getMessage());
    }

    // Create role-specific profile rows when applicable
    if ($seller_type === 'farmer') {
        try {
            $fp = $pdo->prepare('INSERT INTO farmer_profiles (user_id, farm_size, crop_types, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)');
            $fp->execute([(int)$newUserId, $farm_size ?: 0, $crop_types]);
        } catch (Exception $e) {
            error_log('Failed to create farmer profile: ' . $e->getMessage());
        }
    } elseif ($seller_type === 'fisher') {
        try {
            $sp = $pdo->prepare('INSERT INTO fisher_profiles (user_id, fishing_method, species_types, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)');
            $sp->execute([(int)$newUserId, $fishing_method ?: null, $fish_types]);
        } catch (Exception $e) {
            error_log('Failed to create fisher profile: ' . $e->getMessage());
        }
    }

    // Create business profile for all sellers and delivery partners
    if (in_array($accttype, ['farmer','fisher','retailer','restaurant','delivery_partner'])) {
        try {
            $bp = $pdo->prepare('INSERT INTO business_profiles (user_id, business_name, business_type, years_in_business, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)');
            $bp->execute([(int)$newUserId, $business_name ?: 'N/A', $business_type ?: 'individual', $years_in_business ?: 0]);
        } catch (Exception $e) {
            error_log('Failed to create business profile: ' . $e->getMessage());
        }
    }
    
    // Create delivery partner entry for delivery partners
    if ($accttype === 'delivery_partner') {
        try {
            $dp = $pdo->prepare('INSERT INTO delivery_partners (name, phone, email, address, created_at) VALUES (?, ?, ?, ?, ?)');
            $dp->execute([
                $business_name ?: ($first_name . ' ' . $last_name),
                $phone,
                $email,
                $street_address . ', ' . $city . ', ' . $province . ($postal_code ? ' ' . $postal_code : '') . ', ' . $country,
                date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('Failed to create delivery partner entry: ' . $e->getMessage());
        }
    }
    
    // Create security questions
    try {
        $sq = $pdo->prepare('INSERT INTO user_security_questions (user_id, question_1, answer_1, question_2, answer_2, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
        $sq->execute([(int)$newUserId, $security_question_1, password_hash($security_answer_1, PASSWORD_DEFAULT), $security_question_2, password_hash($security_answer_2, PASSWORD_DEFAULT)]);
    } catch (Exception $e) {
        error_log('Failed to create security questions: ' . $e->getMessage());
    }

    // Fetch the created user row
    $stmtUser = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmtUser->execute([$newUserId]);
    $createdUser = $stmtUser->fetch();

    // Map role_id to name when present
    if ($createdUser && isset($createdUser['role_id'])) {
        try {
            $r = $pdo->prepare('SELECT name FROM user_roles WHERE id = ? LIMIT 1');
            $r->execute([$createdUser['role_id']]);
            $roleName = $r->fetchColumn();
            if ($roleName) $createdUser['role'] = $roleName;
        } catch (Exception $e) {
            // ignore
        }
    }

    // If the account is a seller or delivery partner and status is pending, don't auto-login—send to login page with pending message
    if ($status === 'pending') {
        $message = ($role === 'seller') ? 'Registration received. Your seller account is pending admin approval.' : 'Registration received. Your delivery partner account is pending admin approval.';
        echo json_encode([
            'success' => true,
            'message' => $message,
            'redirect' => '/ecommerce_farmers_fishers/php/login.php?registered=1&pending=1',
            'pending' => true
        ]);
        exit;
    }

    // For approved accounts (buyers or approved sellers), log the user in and redirect to dashboard
    if ($createdUser) {
        require_once __DIR__ . '/includes/auth.php';
        login_user($createdUser);

        // Redirect based on role
        $role = $createdUser['role'] ?? null;
        if ($role === 'admin') {
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful. Redirecting to admin dashboard...',
                'redirect' => '/ecommerce_farmers_fishers/admin/dashboard.php'
            ]);
            exit;
        }
        if ($role === 'seller') {
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful. Redirecting to seller dashboard...',
                'redirect' => '/ecommerce_farmers_fishers/seller/dashboard.php'
            ]);
            exit;
        }
        if ($role === 'delivery_partner') {
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful. Redirecting to delivery partner dashboard...',
                'redirect' => '/ecommerce_farmers_fishers/delivery_partner/dashboard.php'
            ]);
            exit;
        }
        // default buyer
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful. Redirecting to buyer dashboard...',
            'redirect' => '/ecommerce_farmers_fishers/buyer/dashboard.php'
        ]);
        exit;
    }

    // Fallback: redirect to login
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful. You may now log in.',
        'redirect' => '/ecommerce_farmers_fishers/php/login.php?registered=1'
    ]);
    exit;

} catch (Exception $e) {
    // In production log error. For now, show a basic error.
    error_log('Registration error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error. Please try again later.']);
    exit;
}
?>