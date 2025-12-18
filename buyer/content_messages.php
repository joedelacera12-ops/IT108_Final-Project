<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/messages.php';

// Only buyers may access
require_role('buyer');
$user = current_user();
$pdo = get_db();

// Check if user is admin
$is_admin = false;
try {
    $admin_id = get_admin_id();
    $is_admin = ($user['id'] == $admin_id);
} catch (Exception $e) {
    // If we can't determine admin status, assume not admin
    $is_admin = false;
}

// Handle message sending (only if not admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if ($is_admin) {
        $error_message = "Admin cannot send messages to themselves.";
    } else {
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        if (!empty($subject) && !empty($message)) {
            $result = send_message_to_admin($user['id'], $subject, $message);
            if ($result) {
                $success_message = "Message sent successfully!";
            } else {
                $error_message = "Failed to send message. Please try again.";
            }
        } else {
            $error_message = "Please fill in both subject and message fields.";
        }
    }
}

// Get user's messages (admins don't have incoming messages in this view)
if (!$is_admin) {
    $messages = get_user_messages($user['id']);
    
    // Mark messages as read when viewed
    foreach ($messages as $message) {
        if ($message['status'] === 'unread') {
            mark_message_as_read($message['id']);
        }
    }
} else {
    $messages = [];
}
?>

<div class="row">
    <div class="col-12">
        <h3 class="section-title mb-4">Messages</h3>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!$is_admin): ?>
        <!-- Send Message Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Send Message to Admin</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>
                    <button type="submit" name="send_message" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Send Message
                    </button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Admin Messages</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">As an administrator, you can respond to messages from users in the messages tab.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Messages List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo $is_admin ? 'User Messages' : 'Your Messages'; ?></h5>
            </div>
            <div class="card-body">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
                        <p class="text-muted"><?php echo $is_admin ? 'No messages from users yet.' : 'No messages yet. Send a message to the admin using the form above.'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($messages as $message): ?>
                            <div class="list-group-item message-card <?php echo $message['status'] === 'unread' ? 'unread' : ''; ?>">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($message['subject'] ?? 'No Subject'); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?>
                                    </small>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars(substr($message['message'], 0, 100)) . (strlen($message['message']) > 100 ? '...' : ''); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        From: <?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?>
                                    </small>
                                    <span class="badge bg-<?php 
                                        echo $message['status'] === 'unread' ? 'primary' : 
                                             ($message['status'] === 'replied' ? 'success' : 'secondary'); 
                                    ?> status-badge">
                                        <?php echo ucfirst($message['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>