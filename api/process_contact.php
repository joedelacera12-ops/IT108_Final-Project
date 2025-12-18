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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['firstName', 'lastName', 'email', 'subject', 'message'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        json_error("Missing required field: $field");
    }
}

$firstName = trim($input['firstName']);
$lastName = trim($input['lastName']);
$email = trim($input['email']);
$phone = trim($input['phone'] ?? '');
$subject = trim($input['subject']);
$message = trim($input['message']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('Invalid email format');
}

// Validate subject
$valid_subjects = ['general', 'support', 'partnership', 'feedback', 'complaint', 'other'];
if (!in_array($subject, $valid_subjects)) {
    json_error('Invalid subject');
}

// Map subject values to display names
$subject_map = [
    'general' => 'General Inquiry',
    'support' => 'Technical Support',
    'partnership' => 'Partnership Opportunity',
    'feedback' => 'Feedback',
    'complaint' => 'Complaint',
    'other' => 'Other'
];

$display_subject = $subject_map[$subject];

// Get IP address and user agent
$ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

// Save to database
try {
    $pdo = get_db();
    
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages 
        (first_name, last_name, email, phone, subject, message, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $firstName,
        $lastName,
        $email,
        $phone ?: null,
        $display_subject,
        $message,
        $ip_address,
        $user_agent
    ]);
    
    $message_id = $pdo->lastInsertId();
    
    // Create notification for admin
    $notification_stmt = $pdo->prepare("
        SELECT id FROM users WHERE role_id = (SELECT id FROM user_roles WHERE name = 'admin') LIMIT 1
    ");
    $notification_stmt->execute();
    $admin = $notification_stmt->fetch();
    
    if ($admin) {
        $notification_stmt = $pdo->prepare("
            INSERT INTO notifications 
            (user_id, type, title, message, created_at) 
            VALUES (?, 'info', ?, ?, NOW())
        ");
        
        $notification_title = "New Contact Message";
        $notification_message = "You have received a new contact message from $firstName $lastName regarding '$display_subject'.";
        
        $notification_stmt->execute([
            $admin['id'],
            $notification_title,
            $notification_message
        ]);
    }
    
    json_success(['message' => 'Your message has been sent successfully. We will get back to you soon.', 'id' => $message_id]);
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    json_error('Failed to process your message. Please try again later.');
}