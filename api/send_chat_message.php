<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/chat.php';

// Ensure user is logged in
$user = current_user();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    // Validate input
    if (!$receiver_id || !$message) {
        echo json_encode(['success' => false, 'error' => 'Receiver and message are required']);
        exit;
    }
    
    // Prevent sending messages to self
    if ($receiver_id == $user['id']) {
        echo json_encode(['success' => false, 'error' => 'Cannot send message to yourself']);
        exit;
    }
    
    // Verify receiver exists
    $receiver = get_user_info($receiver_id);
    if (!$receiver) {
        echo json_encode(['success' => false, 'error' => 'Receiver not found']);
        exit;
    }
    
    // Send message
    $result = send_chat_message($user['id'], $receiver_id, $message);
    
    if ($result) {
        echo json_encode(['success' => true, 'message_id' => $result]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send message']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}