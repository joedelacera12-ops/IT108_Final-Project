<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/redirect_handler.php';

// Check if headers have already been sent before trying to set session params
if (headers_sent()) {
    // If headers already sent, we can't change session params, but we can still start session if not active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} else {
    // Only set session params if headers haven't been sent yet
    if (session_status() === PHP_SESSION_NONE) {
        // Ensure session cookie is scoped to the project subfolder so cookies are sent
        // when the app runs under a subdirectory (e.g. /ecommerce_farmers_fishers).
        // Use a relaxed SameSite for compatibility during local development.
        $projectPath = '/ecommerce_farmers_fishers/';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $projectPath,
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_start();
    }
}

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function login_user(array $user)
{
    // store minimal info in session
    $_SESSION['user'] = [
        'id' => $user['id'],
        // keep both role name and role_id when available for compatibility
        'role' => $user['role'] ?? null,
        'role_id' => $user['role_id'] ?? ($user['role_id'] ?? null),
        'first_name' => $user['first_name'] ?? '',
        'last_name' => $user['last_name'] ?? '',
        'email' => $user['email'] ?? '',
        'phone' => $user['phone'] ?? '',
        'status' => $user['status'] ?? 'pending'
    ];
}

function logout_user()
{
    unset($_SESSION['user']);
    session_destroy();
}

function require_login()
{
    if (!current_user()) {
        redirect_to('/ecommerce_farmers_fishers/php/login.php');
    }
}

function require_role($role)
{
    $u = current_user();
    if (!$u) {
        redirect_to('/ecommerce_farmers_fishers/php/login.php');
    }

    // If role passed is a name (string), compare with stored role
    if (is_string($role) && isset($u['role']) && $u['role'] === $role) {
        return;
    }

    // If session has role_id and caller passed a name, try to resolve it
    if (is_string($role) && isset($u['role_id'])) {
        // attempt to map role_id to name via DB
        $pdo = get_db();
        try {
            $stmt = $pdo->prepare('SELECT name FROM user_roles WHERE id = ? LIMIT 1');
            $stmt->execute([$u['role_id']]);
            $name = $stmt->fetchColumn();
            if ($name === $role) return;
        } catch (Exception $e) {
            // ignore
        }
    }

    // If caller passed an id, compare with stored role_id
    if (is_numeric($role) && isset($u['role_id']) && (int)$u['role_id'] === (int)$role) {
        return;
    }

    // fallback: deny access
    if ($u['role'] !== $role) {
        redirect_to('/ecommerce_farmers_fishers/php/login.php');
    }
}

/**
 * Get seller type display name for current user
 * @param PDO $pdo Database connection
 * @return string Display name of seller type
 */
function get_seller_type_display($pdo) {
    $user = current_user();
    if (!$user) return '';
    
    try {
        $stmt = $pdo->prepare('SELECT meta FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $userData = $stmt->fetch();
        
        if ($userData && !empty($userData['meta'])) {
            $meta = json_decode($userData['meta'], true);
            if (isset($meta['accttype'])) {
                $sellerType = $meta['accttype'];
                
                // Map seller types to display names
                $sellerTypeMap = [
                    'farmer' => 'Farmer',
                    'fisher' => 'Fisher',
                    'retailer' => 'Retailer',
                    'restaurant' => 'Restaurant'
                ];
                
                return isset($sellerTypeMap[$sellerType]) ? $sellerTypeMap[$sellerType] : ucfirst($sellerType);
            }
        }
    } catch (Exception $e) {
        return '';
    }
    
    return '';
}