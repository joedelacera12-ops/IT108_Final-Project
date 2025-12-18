<?php
/**
 * Error Handler for AgriSea Marketplace
 * Centralized error handling and logging
 */

/**
 * Log an error message with context
 * @param string $message Error message
 * @param string $context Context where error occurred
 * @param array $data Additional data to log
 */
function log_error($message, $context = '', $data = []) {
    $timestamp = date('Y-m-d H:i:s');
    $user = isset($_SESSION['user']) ? $_SESSION['user']['id'] : 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
    
    $logEntry = "[{$timestamp}] [USER: {$user}] [IP: {$ip}] [URI: {$uri}]";
    if ($context) {
        $logEntry .= " [CONTEXT: {$context}]";
    }
    $logEntry .= " {$message}";
    
    if (!empty($data)) {
        $logEntry .= " DATA: " . json_encode($data);
    }
    
    error_log($logEntry);
}

/**
 * Handle redirect loops by tracking redirect history
 * @param string $location Target location
 * @param int $maxRedirects Maximum allowed redirects in a short period
 * @return bool True if redirect is allowed, false if loop detected
 */
function allow_redirect($location, $maxRedirects = 5) {
    // Initialize redirect tracking in session
    if (!isset($_SESSION['redirect_history'])) {
        $_SESSION['redirect_history'] = [];
    }
    
    // Clean old redirects (older than 1 minute)
    $oneMinuteAgo = time() - 60;
    $_SESSION['redirect_history'] = array_filter($_SESSION['redirect_history'], function($timestamp) use ($oneMinuteAgo) {
        return $timestamp > $oneMinuteAgo;
    });
    
    // Check if we're redirecting to the same location too frequently
    $recentRedirects = array_filter($_SESSION['redirect_history'], function($timestamp) use ($location) {
        return isset($_SESSION['last_redirect_location']) && $_SESSION['last_redirect_location'] === $location;
    });
    
    if (count($recentRedirects) >= $maxRedirects) {
        log_error("Potential redirect loop detected to {$location}", 'redirect_handler');
        return false;
    }
    
    // Record this redirect
    $_SESSION['redirect_history'][] = time();
    $_SESSION['last_redirect_location'] = $location;
    
    return true;
}

/**
 * Safe redirect function that prevents loops
 * @param string $location Target location
 * @param array $params Query parameters
 */
function safe_redirect_to($location, $params = []) {
    // Add any parameters to the URL if provided
    if (!empty($params)) {
        $queryString = http_build_query($params);
        $location .= (strpos($location, '?') !== false ? '&' : '?') . $queryString;
    }
    
    // Check for redirect loops
    if (!allow_redirect($location)) {
        // Redirect to a safe page instead
        $location = '/ecommerce_farmers_fishers/php/index.php';
        log_error("Redirect loop prevented, redirecting to home", 'redirect_handler');
    }
    
    // Log the redirect
    log_error("Redirecting to: {$location}", 'redirect_handler');
    
    // Perform the redirect
    header('Location: ' . $location);
    exit;
}

/**
 * Handle fatal errors gracefully
 * @param string $message Error message
 * @param string $redirect_url URL to redirect to after showing error
 */
function handle_fatal_error($message, $redirect_url = '/ecommerce_farmers_fishers/php/index.php') {
    log_error($message, 'fatal_error');
    
    // Store error message in session to display on redirect target
    $_SESSION['fatal_error'] = $message;
    
    // Redirect to safe location
    safe_redirect_to($redirect_url);
}

?>