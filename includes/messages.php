<?php
// Messages helper functions

require_once __DIR__ . '/db.php';

// Get admin user ID (assuming there's only one admin)
function get_admin_id() {
    static $adminId = null;
    
    if ($adminId !== null) {
        return $adminId;
    }
    
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role_id = (SELECT id FROM user_roles WHERE name = 'admin' LIMIT 1) LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch();
        $adminId = $admin ? $admin['id'] : null;
        return $adminId;
    } catch (Exception $e) {
        error_log("Error getting admin ID: " . $e->getMessage());
        return null;
    }
}

// Send a message from user to admin
function send_message_to_admin($sender_id, $subject, $message) {
    try {
        $pdo = get_db();
        $admin_id = get_admin_id();
        
        if (!$admin_id) {
            throw new Exception("Admin user not found");
        }
        
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, status, created_at) VALUES (?, ?, ?, ?, 'unread', CURRENT_TIMESTAMP)");
        $stmt->execute([$sender_id, $admin_id, $subject, $message]);
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Error sending message: " . $e->getMessage());
        return false;
    }
}

// Send a reply from admin to user
function send_reply_to_user($admin_id, $receiver_id, $original_message_id, $message) {
    try {
        $pdo = get_db();
        
        // Insert the reply message
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message, status, created_at) VALUES (?, ?, ?, ?, 'unread', CURRENT_TIMESTAMP)");
        // Prefix subject with "Re: " if it doesn't already have it
        $original_stmt = $pdo->prepare("SELECT subject FROM messages WHERE id = ?");
        $original_stmt->execute([$original_message_id]);
        $original = $original_stmt->fetch();
        $subject = $original['subject'] ?? 'No Subject';
        if (strpos($subject, 'Re: ') !== 0) {
            $subject = 'Re: ' . $subject;
        }
        
        $stmt->execute([$admin_id, $receiver_id, $subject, $message]);
        $reply_id = $pdo->lastInsertId();
        
        // Mark original message as replied
        $update_stmt = $pdo->prepare("UPDATE messages SET status = 'replied' WHERE id = ?");
        $update_stmt->execute([$original_message_id]);
        
        return $reply_id;
    } catch (Exception $e) {
        error_log("Error sending reply: " . $e->getMessage());
        return false;
    }
}

// Get messages for a user
function get_user_messages($user_id) {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT m.*, u.first_name, u.last_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = ? ORDER BY m.created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting user messages: " . $e->getMessage());
        return [];
    }
}

// Get messages for admin
function get_admin_messages($admin_id) {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT m.*, u.first_name, u.last_name, u.email FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = ? ORDER BY m.created_at DESC");
        $stmt->execute([$admin_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting admin messages: " . $e->getMessage());
        return [];
    }
}

// Get a specific message with full details
function get_message($message_id) {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT m.*, u.first_name, u.last_name, u.email FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.id = ?");
        $stmt->execute([$message_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting message: " . $e->getMessage());
        return false;
    }
}

// Mark message as read
function mark_message_as_read($message_id) {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("UPDATE messages SET status = 'read' WHERE id = ? AND status = 'unread'");
        $stmt->execute([$message_id]);
        return $stmt->rowCount() > 0; // Returns true if a row was updated
    } catch (Exception $e) {
        error_log("Error marking message as read: " . $e->getMessage());
        return false;
    }
}

// Get unread message count for a user
function get_unread_message_count($user_id) {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND status = 'unread'");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error getting unread message count: " . $e->getMessage());
        return 0;
    }
}

// Get unread message count for admin
function get_admin_unread_count() {
    try {
        $admin_id = get_admin_id();
        if (!$admin_id) return 0;
        
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND status = 'unread'");
        $stmt->execute([$admin_id]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error getting admin unread count: " . $e->getMessage());
        return 0;
    }
}
?>