<?php
/**
 * Unified Redirect Handler for AgriSea Marketplace
 * Centralized location for all redirect logic
 */

function redirect_to($location, $params = []) {
    // Add any parameters to the URL if provided
    if (!empty($params)) {
        $queryString = http_build_query($params);
        $location .= (strpos($location, '?') !== false ? '&' : '?') . $queryString;
    }
    
    // Log redirect for debugging purposes
    error_log('Redirecting to: ' . $location . ' from ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
    
    // Check if headers have already been sent
    if (headers_sent($filename, $linenum)) {
        error_log("Headers already sent in $filename on line $linenum");
        // Use JavaScript fallback if headers already sent
        echo "<script>window.location.href='$location';</script>";
        echo "<noscript>Redirecting to <a href='$location'>$location</a></noscript>";
        exit;
    }
    
    header('Location: ' . $location);
    exit;
}

// Common redirect functions
function redirect_to_home() {
    redirect_to('/ecommerce_farmers_fishers/php/index.php');
}

function redirect_to_login() {
    redirect_to('/ecommerce_farmers_fishers/php/login.php');
}

function redirect_to_register() {
    redirect_to('/ecommerce_farmers_fishers/php/register.php');
}

function redirect_to_buyer_dashboard() {
    redirect_to('/ecommerce_farmers_fishers/buyer/dashboard.php');
}

function redirect_to_seller_dashboard() {
    redirect_to('/ecommerce_farmers_fishers/seller/dashboard.php');
}

function redirect_to_admin_dashboard() {
    redirect_to('/ecommerce_farmers_fishers/admin/dashboard.php');
}

function redirect_with_error($location, $error_message) {
    redirect_to($location, ['error' => $error_message]);
}

function redirect_with_success($location, $success_message) {
    redirect_to($location, ['success' => $success_message]);
}

// Handle role-based redirects
function redirect_based_on_role($role_id) {
    switch ($role_id) {
        case 1: // Admin
            redirect_to_admin_dashboard();
            break;
        case 2: // Seller
            redirect_to_seller_dashboard();
            break;
        default: // Buyer or any other role
            redirect_to_buyer_dashboard();
            break;
    }
}
?>