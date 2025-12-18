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

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Invalid request method', 405);
}

// Check if user is logged in
$user = current_user();
if (!$user) {
    json_error('Unauthorized', 401);
}

try {
    $pdo = get_db();
    
    // Fetch replies for messages sent by this user's email
    $stmt = $pdo->prepare("SELECT cm.id, cm.subject, cm.message, cm.created_at as message_date, 
                           cmr.reply_text, cmr.created_at as reply_date, 
                           u.first_name, u.last_name 
                           FROM contact_messages cm 
                           LEFT JOIN contact_message_replies cmr ON cm.id = cmr.message_id 
                           LEFT JOIN users u ON cmr.replier_id = u.id 
                           WHERE cm.email = ? 
                           ORDER BY cm.created_at DESC, cmr.created_at ASC");
    $stmt->execute([$user['email']]);
    $results = $stmt->fetchAll();
    
    // Fetch replies for messages sent by this user's email
    $stmt = $pdo->prepare("SELECT cm.id, cm.subject, cm.message, cm.created_at as message_date, 
                           cmr.reply_text, cmr.created_at as reply_date, 
                           u.first_name, u.last_name 
                           FROM contact_messages cm 
                           LEFT JOIN contact_message_replies cmr ON cm.id = cmr.message_id 
                           LEFT JOIN users u ON cmr.replier_id = u.id 
                           WHERE cm.email = ? 
                           ORDER BY cm.created_at DESC, cmr.created_at ASC");
    $stmt->execute([$user['email']]);
    $results = $stmt->fetchAll();
    
    // Group replies by message
    $messages = [];
    foreach ($results as $row) {
        $messageId = $row['id'];
        if (!isset($messages[$messageId])) {
            $messages[$messageId] = [
                'id' => $row['id'],
                'subject' => $row['subject'],
                'message' => $row['message'],
                'date' => $row['message_date'],
                'replies' => []
            ];
        }
        
        if ($row['reply_text']) {
            $messages[$messageId]['replies'][] = [
                'reply_text' => $row['reply_text'],
                'date' => $row['reply_date'],
                'admin_name' => $row['first_name'] . ' ' . $row['last_name']
            ];
        }
    }
    
    // Convert to indexed array
    $replies = array_values($messages);
    
    json_success(['replies' => $replies]);
    
} catch (Exception $e) {
    error_log("Get user replies error: " . $e->getMessage());
    json_error('Failed to fetch replies. Please try again later.');
}