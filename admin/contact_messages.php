<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and is admin
$user = current_user();
if (!$user) {
    header('Location: /ecommerce_farmers_fishers/php/login.php');
    exit;
}

// Check if user is admin
$pdo = get_db();
$stmt = $pdo->prepare("SELECT r.name FROM user_roles r JOIN users u ON r.id = u.role_id WHERE u.id = ?");
$stmt->execute([$user['id']]);
$userRole = $stmt->fetch();

if (!$userRole || $userRole['name'] !== 'admin') {
    header('Location: /ecommerce_farmers_fishers/php/login.php');
    exit;
}

// Handle marking as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    $messageId = (int)$_POST['message_id'];
    $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1, read_at = NOW() WHERE id = ?");
    $stmt->execute([$messageId]);
    $successMessage = "Message marked as read.";
}

// Handle deleting message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $messageId = (int)$_POST['message_id'];
    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $successMessage = "Message deleted successfully.";
}

// Handle replying to message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    $messageId = (int)$_POST['message_id'];
    $replyMessage = trim($_POST['reply_message'] ?? '');
    
    if ($replyMessage) {
        // Store the reply in the database
        $stmt = $pdo->prepare("INSERT INTO contact_message_replies (message_id, reply_text, replier_id, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$messageId, $replyMessage, $user['id'], date('Y-m-d H:i:s')]);
        
        // Mark the original message as read
        $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1, read_at = NOW() WHERE id = ?");
        $stmt->execute([$messageId]);
        
        $successMessage = "Reply sent successfully.";
    }
}

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query based on filter
$whereClause = "";
$params = [];

if ($filter === 'unread') {
    $whereClause = "WHERE cm.is_read = 0";
} elseif ($filter === 'read') {
    $whereClause = "WHERE cm.is_read = 1";
}

// Get messages
$stmt = $pdo->prepare("
    SELECT cm.*, 
           CASE 
               WHEN cm.is_read = 0 THEN 'Unread' 
               ELSE 'Read' 
           END as status_label
    FROM contact_messages cm
    $whereClause
    ORDER BY cm.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$limit, $offset]));
$messages = $stmt->fetchAll();

// Get total count for pagination
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM contact_messages cm
    $whereClause
");
$countStmt->execute($params);
$totalMessages = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalMessages / $limit);

// Get unread count for badge
try {
    $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0");
    $unreadStmt->execute();
    $unreadCount = (int)$unreadStmt->fetchColumn();
} catch (Exception $e) {
    $unreadCount = 0;
}

// Since this is now included in the dashboard, we don't need the full HTML structure
// Just the content part
?>

<div class="container-fluid p-0">
    <div class="row">
        <div class="col-12">
            <h2 class="text-success"><i class="fas fa-envelope me-2"></i>Contact Messages</h2>
            <p class="text-muted">Manage and respond to user inquiries</p>
        </div>
    </div>

    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($successMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-envelope me-2"></i>Contact Messages
                        </h5>
                        <div>
                            <a href="?tab=contact_messages&filter=all" class="btn btn-sm <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>">All</a>
                            <a href="?tab=contact_messages&filter=unread" class="btn btn-sm <?php echo $filter === 'unread' ? 'btn-warning' : 'btn-outline-secondary'; ?>">
                                Unread
                                <?php if ($unreadCount > 0): ?>
                                    <span class="badge bg-danger"><?php echo $unreadCount; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="?tab=contact_messages&filter=read" class="btn btn-sm <?php echo $filter === 'read' ? 'btn-success' : 'btn-outline-secondary'; ?>">Read</a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($messages)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No messages found</h5>
                            <p class="text-muted">There are no contact messages<?php echo $filter !== 'all' ? ' matching your filter' : ''; ?>.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="20%">From</th>
                                        <th width="15%">Subject</th>
                                        <th width="30%">Message</th>
                                        <th width="15%">Date</th>
                                        <th width="10%">Status</th>
                                        <th width="5%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($messages as $message): ?>
                                        <tr class="<?php echo $message['is_read'] ? '' : 'table-warning'; ?>">
                                            <td><?php echo (int)$message['id']; ?></td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?></strong>
                                                </div>
                                                <div class="small text-muted">
                                                    <?php echo htmlspecialchars($message['email']); ?>
                                                    <?php if ($message['phone']): ?>
                                                        <br><?php echo htmlspecialchars($message['phone']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($message['subject']); ?></span>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($message['message']); ?>">
                                                    <?php echo htmlspecialchars($message['message']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?php echo date('M j, Y', strtotime($message['created_at'])); ?></div>
                                                <div class="small text-muted"><?php echo date('g:i A', strtotime($message['created_at'])); ?></div>
                                            </td>
                                            <td>
                                                <?php if ($message['is_read']): ?>
                                                    <span class="badge bg-success">Read</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Unread</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-h"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="showMessageModal(<?php echo (int)$message['id']; ?>, '<?php echo htmlspecialchars(addslashes($message['first_name'] . ' ' . $message['last_name'])); ?>', '<?php echo htmlspecialchars(addslashes($message['email'])); ?>', '<?php echo htmlspecialchars(addslashes($message['subject'])); ?>', '<?php echo htmlspecialchars(addslashes($message['message'])); ?>', '<?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?>')">
                                                                <i class="fas fa-eye me-1"></i>View
                                                            </a>
                                                        </li>
                                                        <?php if (!$message['is_read']): ?>
                                                            <li>
                                                                <form method="POST" style="display:inline;">
                                                                    <input type="hidden" name="action" value="mark_read">
                                                                    <input type="hidden" name="message_id" value="<?php echo (int)$message['id']; ?>">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-check me-1"></i>Mark as Read
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php endif; ?>
                                                        <li>
                                                            <hr class="dropdown-divider">
                                                        </li>
                                                        <li>
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this message?')">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="message_id" value="<?php echo (int)$message['id']; ?>">
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="fas fa-trash me-1"></i>Delete
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="card-footer">
                                <nav>
                                    <ul class="pagination justify-content-center mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?tab=contact_messages&filter=<?php echo urlencode($filter); ?>&page=<?php echo $page - 1; ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?tab=contact_messages&filter=<?php echo urlencode($filter); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?tab=contact_messages&filter=<?php echo urlencode($filter); ?>&page=<?php echo $page + 1; ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Message Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>From</h6>
                        <p id="modalSender"></p>
                        
                        <h6>Email</h6>
                        <p id="modalEmail"></p>
                        
                        <h6>Date</h6>
                        <p id="modalDate"></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Subject</h6>
                        <p><span class="badge bg-secondary" id="modalSubject"></span></p>
                    </div>
                </div>
                
                <hr>
                
                <h6>Message</h6>
                <p id="modalMessage" class="pre-line"></p>
                
                <!-- Previous Replies Section -->
                <div id="previousReplies" class="mb-3"></div>
                
                <hr>
                
                <h6>Reply</h6>
                <form id="replyForm">
                    <input type="hidden" id="replyMessageId" name="message_id">
                    <div class="mb-3">
                        <label for="replyMessage" class="form-label">Your Reply</label>
                        <textarea class="form-control" id="replyMessage" name="reply_message" rows="4" placeholder="Type your reply here..."></textarea>
                    </div>
                    <button type="button" class="btn btn-success" onclick="sendReply()">Send Reply</button>
                    <span id="replyMsg" class="ms-2"></span>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function showMessageModal(id, sender, email, subject, message, date) {
        document.getElementById('modalSender').textContent = sender;
        document.getElementById('modalEmail').textContent = email;
        document.getElementById('modalSubject').textContent = subject;
        document.getElementById('modalMessage').textContent = message;
        document.getElementById('modalDate').textContent = date;
        document.getElementById('replyMessageId').value = id;
        document.getElementById('replyMessage').value = '';
        document.getElementById('replyMsg').textContent = '';
        
        // Load previous replies
        loadPreviousReplies(id);
        
        new bootstrap.Modal(document.getElementById('messageModal')).show();
    }
    
    async function loadPreviousReplies(messageId) {
        try {
            // In a real implementation, this would fetch replies from the server
            // For now, we'll just clear the replies section
            document.getElementById('previousReplies').innerHTML = '';
        } catch (error) {
            console.error('Error loading replies:', error);
        }
    }
    
    async function sendReply() {
        const messageId = document.getElementById('replyMessageId').value;
        const replyMessage = document.getElementById('replyMessage').value;
        const replyMsg = document.getElementById('replyMsg');
        
        if (!replyMessage.trim()) {
            replyMsg.innerHTML = '<span class="text-danger">Please enter a reply message.</span>';
            return;
        }
        
        // Disable button during submission
        const replyBtn = document.querySelector('#replyForm button');
        const originalBtnText = replyBtn.innerHTML;
        replyBtn.disabled = true;
        replyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
        
        try {
            // Send reply to server
            const formData = new FormData();
            formData.append('action', 'reply');
            formData.append('message_id', messageId);
            formData.append('reply_message', replyMessage);
            
            const response = await fetch('/ecommerce_farmers_fishers/api/reply_contact.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                replyMsg.innerHTML = '<span class="text-success">Reply sent successfully!</span>';
                
                // Mark message as read
                const readFormData = new FormData();
                readFormData.append('action', 'mark_read');
                readFormData.append('message_id', messageId);
                
                await fetch('', { method: 'POST', body: readFormData });
                
                // Reload page to reflect changes
                setTimeout(() => location.reload(), 1500);
            } else {
                replyMsg.innerHTML = '<span class="text-danger">Failed to send reply: ' + (result.error || 'Unknown error') + '</span>';
            }
        } catch (error) {
            replyMsg.innerHTML = '<span class="text-danger">Failed to send reply. Please try again.</span>';
        } finally {
            replyBtn.disabled = false;
            replyBtn.innerHTML = originalBtnText;
        }
    }
</script>

<style>
    .pre-line {
        white-space: pre-line;
    }
    .table-warning {
        --bs-table-bg: #fff3cd;
        --bs-table-striped-bg: #f2e7c3;
        --bs-table-striped-color: #000;
        --bs-table-active-bg: #e6dbb9;
        --bs-table-active-color: #000;
        --bs-table-hover-bg: #ece1be;
        --bs-table-hover-color: #000;
    }
</style>