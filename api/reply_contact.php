<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

function json_error(string $message, int $status = 400): void {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_success($data = null): void {
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Invalid request method', 405);
}

// Check if user is logged in and is admin
$user = current_user();
if (!$user) {
    json_error('Unauthorized', 401);
}

$pdo = get_db();
$stmt = $pdo->prepare("SELECT r.name FROM user_roles r JOIN users u ON r.id = u.role_id WHERE u.id = ?");
$stmt->execute([$user['id']]);
$userRole = $stmt->fetch();

if (!$userRole || $userRole['name'] !== 'admin') {
    json_error('Access denied. Admin only.', 403);
}

// Get form data
$message_id = (int)($_POST['message_id'] ?? 0);
$reply_message = trim($_POST['reply_message'] ?? '');

// Validate required fields
if (!$message_id || !$reply_message) {
    json_error('Missing required fields');
}

try {
    // Get the original message
    $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch();
    
    if (!$message) {
        json_error('Message not found');
    }
    
    // Store the reply in the database
    $stmt = $pdo->prepare("INSERT INTO contact_message_replies (message_id, reply_text, replier_id, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$message_id, $reply_message, $user['id']]);
    
    // Mark the original message as read
    $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1, read_at = NOW() WHERE id = ?");
    $stmt->execute([$message_id]);
    
    // In a real implementation, you would also send an email notification to the user
    
    json_success(['message' => 'Reply sent successfully']);
    
} catch (Exception $e) {
    error_log("Reply contact error: " . $e->getMessage());
    json_error('Failed to process reply. Please try again later.');
}