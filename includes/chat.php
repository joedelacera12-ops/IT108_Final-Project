<?php
// Chat helper functions

require_once __DIR__ . '/db.php';

// Send a chat message between users
function send_chat_message($sender_id, $receiver_id, $message) {
    try {
        $pdo = get_db();
        
        // Insert the chat message
        $stmt = $pdo->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message, is_read, created_at) VALUES (?, ?, ?, FALSE, CURRENT_TIMESTAMP)");
        $stmt->execute([$sender_id, $receiver_id, $message]);
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Error sending chat message: " . $e->getMessage());
        return false;
    }
}

// Get chat messages between two users
function get_chat_messages($user1_id, $user2_id) {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            SELECT cm.*, 
                   u1.first_name as sender_first_name, 
                   u1.last_name as sender_last_name,
                   u2.first_name as receiver_first_name,
                   u2.last_name as receiver_last_name
            FROM chat_messages cm
            JOIN users u1 ON cm.sender_id = u1.id
            JOIN users u2 ON cm.receiver_id = u2.id
            WHERE (cm.sender_id = ? AND cm.receiver_id = ?) 
               OR (cm.sender_id = ? AND cm.receiver_id = ?)
            ORDER BY cm.created_at ASC
        ");
        $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting chat messages: " . $e->getMessage());
        return [];
    }
}

// Get unread chat message count for a user
function get_unread_chat_count($user_id) {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM chat_messages 
            WHERE receiver_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error getting unread chat count: " . $e->getMessage());
        return 0;
    }
}

// Mark chat messages as read
function mark_chat_messages_as_read($user1_id, $user2_id) {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            UPDATE chat_messages 
            SET is_read = TRUE 
            WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$user1_id, $user2_id]);
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Error marking chat messages as read: " . $e->getMessage());
        return 0;
    }
}

// Get chat contacts for a user (users they've chatted with)
function get_chat_contacts($user_id) {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            SELECT DISTINCT 
                CASE 
                    WHEN cm.sender_id = ? THEN cm.receiver_id 
                    ELSE cm.sender_id 
                END as contact_id,
                u.first_name,
                u.last_name,
                u.role_id,
                ur.name as role_name,
                (SELECT COUNT(*) FROM chat_messages cm2 WHERE cm2.sender_id = contact_id AND cm2.receiver_id = ? AND cm2.is_read = FALSE) as unread_count,
                (SELECT cm3.message FROM chat_messages cm3 WHERE (cm3.sender_id = ? AND cm3.receiver_id = contact_id) OR (cm3.sender_id = contact_id AND cm3.receiver_id = ?) ORDER BY cm3.created_at DESC LIMIT 1) as last_message,
                (SELECT cm4.created_at FROM chat_messages cm4 WHERE (cm4.sender_id = ? AND cm4.receiver_id = contact_id) OR (cm4.sender_id = contact_id AND cm4.receiver_id = ?) ORDER BY cm4.created_at DESC LIMIT 1) as last_message_time
            FROM chat_messages cm
            JOIN users u ON u.id = (CASE WHEN cm.sender_id = ? THEN cm.receiver_id ELSE cm.sender_id END)
            JOIN user_roles ur ON u.role_id = ur.id
            WHERE cm.sender_id = ? OR cm.receiver_id = ?
            ORDER BY last_message_time DESC
        ");
        $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting chat contacts: " . $e->getMessage());
        return [];
    }
}

// Get user info by ID
function get_user_info($user_id) {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.role_id, ur.name as role_name
            FROM users u
            JOIN user_roles ur ON u.role_id = ur.id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting user info: " . $e->getMessage());
        return false;
    }
}
?>