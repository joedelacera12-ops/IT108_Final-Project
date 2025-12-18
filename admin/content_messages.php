<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/messages.php';

// Only admins may access
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
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="section-title mb-0">Admin Messages</h3>
                <p class="text-muted mb-0">Manage communications with users</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-pill px-3 py-2"><?php echo count($messages); ?> Messages</span>
                <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
            </div>
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
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <h5 class="mb-0 text-primary fw-bold">User Messages</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-outline-primary filter-btn active" id="filterAll">All Messages</button>
                        <button class="btn btn-sm btn-outline-warning filter-btn" id="filterUnread">Unread <span class="badge bg-warning text-dark ms-1">0</span></button>
                        <button class="btn btn-sm btn-outline-success filter-btn" id="filterReplied">Replied <span class="badge bg-success ms-1">0</span></button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <div class="rounded-circle bg-light mx-auto d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-inbox fa-2x text-muted"></i>
                            </div>
                        </div>
                        <h5 class="text-muted mb-2">Empty Inbox</h5>
                        <p class="text-muted mb-0">No messages from users yet.</p>
                        <p class="text-muted small mb-0">Check back later for new messages.</p>
                    </div>
                <?php else: ?>
                    <div class="message-list">
                        <?php foreach ($messages as $message): ?>
                            <div class="message-item border-bottom rounded-0 <?php echo $message['status'] === 'unread' ? 'bg-light border-start border-3 border-warning' : 'border-start border-3 border-transparent'; ?>" 
                                 data-status="<?php echo $message['status']; ?>">
                                <div class="p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1 me-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <h6 class="mb-0 fw-bold text-dark me-2"><?php echo htmlspecialchars($message['subject'] ?? 'No Subject'); ?></h6>
                                                <?php if ($message['status'] === 'unread'): ?>
                                                    <span class="badge bg-warning text-dark rounded-pill">NEW</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex flex-wrap align-items-center small text-muted mb-2">
                                                <span class="me-3 mb-1 mb-md-0"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?></span>
                                                <span class="me-3 mb-1 mb-md-0"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($message['email']); ?></span>
                                                <span><i class="fas fa-clock me-1"></i><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-<?php 
                                                    echo $message['status'] === 'unread' ? 'warning text-dark' : 
                                                         ($message['status'] === 'replied' ? 'success' : 'secondary'); 
                                                ?> rounded-pill px-2 py-1">
                                                    <?php echo ucfirst($message['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column align-items-end">
                                            <button class="btn btn-sm btn-outline-primary view-message mb-2" 
                                                    data-message='<?php echo htmlspecialchars(json_encode($message)); ?>'>
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <p class="mb-0 text-dark message-content"><?php echo nl2br(htmlspecialchars(substr($message['message'], 0, 300))); ?><?php echo strlen($message['message']) > 300 ? '...' : ''; ?></p>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end">
                                        <button class="btn btn-sm btn-outline-success" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#replyForm<?php echo $message['id']; ?>" 
                                                aria-expanded="false" 
                                                aria-controls="replyForm<?php echo $message['id']; ?>">
                                            <i class="fas fa-reply me-1"></i>Reply to Message
                                        </button>
                                    </div>
                                    
                                    <!-- Reply Form -->
                                    <div class="collapse mt-4" id="replyForm<?php echo $message['id']; ?>">
                                        <div class="card border-start border-4 border-success rounded-0 bg-light-subtle">
                                            <div class="card-body">
                                                <h6 class="card-title text-success mb-3"><i class="fas fa-reply me-2"></i>Reply to Message</h6>
                                                <form method="POST" class="mb-0">
                                                    <input type="hidden" name="receiver_id" value="<?php echo $message['sender_id']; ?>">
                                                    <input type="hidden" name="original_message_id" value="<?php echo $message['id']; ?>">
                                                    <div class="mb-3">
                                                        <label for="reply_message_<?php echo $message['id']; ?>" class="form-label fw-bold text-success">Your Reply</label>
                                                        <textarea class="form-control border-success" 
                                                                  id="reply_message_<?php echo $message['id']; ?>" 
                                                                  name="reply_message" 
                                                                  rows="4" 
                                                                  placeholder="Write your reply here..." 
                                                                  required></textarea>
                                                    </div>
                                                    <div class="d-flex justify-content-end gap-2">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                                data-bs-toggle="collapse" 
                                                                data-bs-target="#replyForm<?php echo $message['id']; ?>">
                                                            <i class="fas fa-times me-1"></i>Cancel
                                                        </button>
                                                        <button type="submit" name="send_reply" class="btn btn-success btn-sm">
                                                            <i class="fas fa-paper-plane me-1"></i>Send Reply
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Message Detail Modal -->
<div class="modal fade" id="messageDetailModal" tabindex="-1" aria-labelledby="messageDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageDetailModalLabel">Message Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="message-detail-content">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    .message-item {
        transition: all 0.3s ease;
        border-left-width: 4px !important;
    }
    .message-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        background-color: rgba(0,0,0,0.02) !important;
    }
    .message-item.unread {
        background-color: rgba(255, 193, 7, 0.08) !important;
    }
    .status-badge {
        font-size: 0.75rem;
    }
    .message-content {
        white-space: pre-wrap;
        word-break: break-word;
        line-height: 1.5;
    }
    .filter-btn.active {
        background-color: #0d6efd !important;
        color: white !important;
        border-color: #0d6efd !important;
    }
    .reply-form {
        background-color: rgba(25, 135, 84, 0.05);
        border-left: 4px solid #198754;
    }
    .section-title {
        font-weight: 600;
        color: #2c5e1a;
    }
</style>

<script>
// Message filtering functionality with dynamic badge counts
document.addEventListener('DOMContentLoaded', function() {
    // Calculate message counts
    const messageItems = document.querySelectorAll('.message-item');
    let unreadCount = 0;
    let repliedCount = 0;
    
    messageItems.forEach(item => {
        const status = item.getAttribute('data-status');
        if (status === 'unread') {
            unreadCount++;
        } else if (status === 'replied') {
            repliedCount++;
        }
    });
    
    // Update badge counts
    document.querySelector('#filterUnread .badge').textContent = unreadCount;
    document.querySelector('#filterReplied .badge').textContent = repliedCount;
    
    // Filter buttons
    const filterAll = document.getElementById('filterAll');
    const filterUnread = document.getElementById('filterUnread');
    const filterReplied = document.getElementById('filterReplied');
    
    filterAll.addEventListener('click', function() {
        messageItems.forEach(item => item.style.display = 'block');
        setActiveFilter(this);
    });
    
    filterUnread.addEventListener('click', function() {
        messageItems.forEach(item => {
            const status = item.getAttribute('data-status');
            item.style.display = status === 'unread' ? 'block' : 'none';
        });
        setActiveFilter(this);
    });
    
    filterReplied.addEventListener('click', function() {
        messageItems.forEach(item => {
            const status = item.getAttribute('data-status');
            item.style.display = status === 'replied' ? 'block' : 'none';
        });
        setActiveFilter(this);
    });
    
    function setActiveFilter(activeButton) {
        // Update active state for filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
            btn.classList.add('btn-outline-primary');
        });
        activeButton.classList.add('active');
        activeButton.classList.remove('btn-outline-primary');
    }
    
    // Auto-select filter based on URL parameter (if any)
    const urlParams = new URLSearchParams(window.location.search);
    const filterParam = urlParams.get('filter');
    if (filterParam === 'unread') {
        filterUnread.click();
    } else if (filterParam === 'replied') {
        filterReplied.click();
    } else {
        filterAll.click();
    }
    
    // View message detail functionality
    const viewButtons = document.querySelectorAll('.view-message');
    const messageDetailModal = new bootstrap.Modal(document.getElementById('messageDetailModal'));
    const messageDetailContent = document.querySelector('.message-detail-content');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const messageData = JSON.parse(this.getAttribute('data-message'));
            
            messageDetailContent.innerHTML = `
                <div class="mb-4">
                    <h5>${htmlspecialchars(messageData.subject || 'No Subject')}</h5>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <p class="mb-1"><strong>From:</strong> ${htmlspecialchars(messageData.first_name + ' ' + messageData.last_name)} (${htmlspecialchars(messageData.email)})</p>
                            <p class="mb-0 text-muted"><strong>Date:</strong> ${new Date(messageData.created_at).toLocaleString()}</p>
                        </div>
                        <span class="badge bg-${messageData.status === 'unread' ? 'warning' : (messageData.status === 'replied' ? 'success' : 'secondary')}" style="font-size: 1rem;">
                            ${messageData.status.charAt(0).toUpperCase() + messageData.status.slice(1)}
                        </span>
                    </div>
                </div>
                <div class="border-top pt-3">
                    <h6>Message:</h6>
                    <div class="bg-light p-3 rounded">
                        <p class="mb-0">${nl2br(htmlspecialchars(messageData.message))}</p>
                    </div>
                </div>
            `;
            
            messageDetailModal.show();
        });
    });
    
    // Helper functions
    function htmlspecialchars(str) {
        return str.replace(/&/g, '&amp;')
                 .replace(/</g, '&lt;')
                 .replace(/>/g, '&gt;')
                 .replace(/"/g, '&quot;')
                 .replace(/'/g, '&#039;');
    }
    
    function nl2br(str) {
        return str.replace(/\n/g, '<br>');
    }
});
</script>