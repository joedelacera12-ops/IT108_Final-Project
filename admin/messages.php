<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/messages.php';

// Ensure admin is logged in
require_role('admin');
$admin = current_user();
if (!$admin) {
    header('Location: /ecommerce_farmers_fishers/php/login.php');
    exit;
}

// Handle reply sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $original_message_id = (int)$_POST['original_message_id'];
    $message = trim($_POST['reply_message'] ?? '');
    
    if (!empty($message)) {
        $result = send_reply_to_user($admin['id'], $receiver_id, $original_message_id, $message);
        if ($result) {
            $success_message = "Reply sent successfully!";
        } else {
            $error_message = "Failed to send reply. Please try again.";
        }
    } else {
        $error_message = "Please enter a reply message.";
    }
}

// Get admin's messages
$messages = get_admin_messages($admin['id']);

// Mark messages as read when viewed
foreach ($messages as $message) {
    if ($message['status'] === 'unread') {
        mark_message_as_read($message['id']);
    }
}

// Get unread count for badge
$unread_count = get_admin_unread_count();

// Since this is now included in the dashboard, we don't need the full HTML structure
// Just the content part
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Messages</h1>
            </div>

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

            <!-- Messages List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Messages from Users</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-envelope-open-text fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No messages from users yet.</p>
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
                                    <p class="mb-1"><strong>From:</strong> <?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?> (<?php echo htmlspecialchars($message['email']); ?>)</p>
                                    <p class="mb-2"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-<?php 
                                            echo $message['status'] === 'unread' ? 'primary' : 
                                                 ($message['status'] === 'replied' ? 'success' : 'secondary'); 
                                        ?> status-badge">
                                            <?php echo ucfirst($message['status']); ?>
                                        </span>
                                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#replyForm<?php echo $message['id']; ?>" aria-expanded="false" aria-controls="replyForm<?php echo $message['id']; ?>">
                                            <i class="fas fa-reply me-1"></i>Reply
                                        </button>
                                    </div>
                                    
                                    <!-- Reply Form -->
                                    <div class="collapse mt-3 reply-form" id="replyForm<?php echo $message['id']; ?>">
                                        <form method="POST" class="p-3">
                                            <input type="hidden" name="receiver_id" value="<?php echo $message['sender_id']; ?>">
                                            <input type="hidden" name="original_message_id" value="<?php echo $message['id']; ?>">
                                            <div class="mb-2">
                                                <label for="reply_message_<?php echo $message['id']; ?>" class="form-label">Your Reply</label>
                                                <textarea class="form-control" id="reply_message_<?php echo $message['id']; ?>" name="reply_message" rows="3" required></textarea>
                                            </div>
                                            <button type="submit" name="send_reply" class="btn btn-primary btn-sm">
                                                <i class="fas fa-paper-plane me-1"></i>Send Reply
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .message-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    .message-card.unread {
        border-left-color: #0d6efd;
        background-color: rgba(13, 110, 253, 0.05);
    }
    .message-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .status-badge {
        font-size: 0.75rem;
    }
    .reply-form {
        background-color: rgba(13, 110, 253, 0.03);
        border-left: 3px solid #0d6efd;
    }
</style>