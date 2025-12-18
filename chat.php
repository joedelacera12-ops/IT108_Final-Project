<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/chat.php';

// Ensure user is logged in
$user = current_user();
if (!$user) {
    header('Location: /ecommerce_farmers_fishers/php/login.php');
    exit;
}

// Get contacts
$contacts = get_chat_contacts($user['id']);

// Get selected contact if any
$selected_contact_id = (int)($_GET['contact'] ?? 0);
$selected_contact = null;
$chat_messages = [];

if ($selected_contact_id) {
    $selected_contact = get_user_info($selected_contact_id);
    if ($selected_contact) {
        $chat_messages = get_chat_messages($user['id'], $selected_contact_id);
        // Mark messages as read
        mark_chat_messages_as_read($selected_contact_id, $user['id']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - AgriSea Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .chat-container {
            height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
        }
        
        .contacts-list {
            height: 100%;
            overflow-y: auto;
            border-right: 1px solid #dee2e6;
        }
        
        .chat-messages {
            height: 100%;
            overflow-y: auto;
            padding: 1rem;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 15px;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .sent-message {
            background-color: #d1ecf1;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        
        .received-message {
            background-color: #e9ecef;
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }
        
        .contact-item {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .contact-item:hover {
            background-color: #f8f9fa;
        }
        
        .contact-item.active {
            background-color: #e9f7fe;
            border-left: 3px solid #0d6efd;
        }
        
        .unread-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            min-width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            padding: 2px;
        }
        
        .chat-input {
            border-top: 1px solid #dee2e6;
            padding: 1rem;
        }
        
        .role-badge {
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <?php include __DIR__ . '/includes/header.php'; ?>
        
        <div class="container-fluid p-4 chat-container">
            <div class="row h-100">
                <!-- Contacts List -->
                <div class="col-lg-4 col-md-5 h-100">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Contacts</h5>
                            <span class="badge bg-primary"><?php echo count($contacts); ?> contacts</span>
                        </div>
                        <div class="card-body p-0 contacts-list">
                            <?php if (empty($contacts)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No contacts yet. Start a conversation!</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($contacts as $contact): ?>
                                        <a href="?contact=<?php echo $contact['contact_id']; ?>" 
                                           class="list-group-item list-group-item-action contact-item <?php echo $selected_contact_id == $contact['contact_id'] ? 'active' : ''; ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?></h6>
                                                    <small class="text-muted">
                                                        <?php echo ucfirst(str_replace('_', ' ', $contact['role_name'])); ?>
                                                    </small>
                                                    <?php if ($contact['last_message']): ?>
                                                        <p class="mb-0 text-muted small">
                                                            <?php echo htmlspecialchars(strlen($contact['last_message']) > 30 ? substr($contact['last_message'], 0, 30) . '...' : $contact['last_message']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="position-relative">
                                                    <?php if ($contact['unread_count'] > 0): ?>
                                                        <span class="badge bg-danger unread-badge"><?php echo $contact['unread_count']; ?></span>
                                                    <?php endif; ?>
                                                    <small class="text-muted">
                                                        <?php echo $contact['last_message_time'] ? date('M j', strtotime($contact['last_message_time'])) : ''; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Chat Area -->
                <div class="col-lg-8 col-md-7 h-100">
                    <div class="card h-100">
                        <?php if ($selected_contact): ?>
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($selected_contact['first_name'] . ' ' . $selected_contact['last_name']); ?></h5>
                                    <small class="text-muted">
                                        <?php echo ucfirst(str_replace('_', ' ', $selected_contact['role_name'])); ?>
                                    </small>
                                </div>
                                <div>
                                    <span class="badge bg-info role-badge">
                                        <?php echo ucfirst(str_replace('_', ' ', $selected_contact['role_name'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body p-0 d-flex flex-column">
                                <div class="chat-messages" id="chatMessages">
                                    <?php if (empty($chat_messages)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No messages yet. Start the conversation!</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($chat_messages as $message): ?>
                                            <div class="message-bubble <?php echo $message['sender_id'] == $user['id'] ? 'sent-message' : 'received-message'; ?> mx-3 my-2">
                                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                                <small class="text-muted">
                                                    <?php echo date('M j, g:i A', strtotime($message['created_at'])); ?>
                                                    <?php if ($message['sender_id'] == $user['id'] && $message['is_read']): ?>
                                                        <i class="fas fa-check-double text-primary ms-1"></i>
                                                    <?php elseif ($message['sender_id'] == $user['id']): ?>
                                                        <i class="fas fa-check text-muted ms-1"></i>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="chat-input">
                                    <form id="chatForm" method="POST">
                                        <input type="hidden" name="receiver_id" value="<?php echo $selected_contact['id']; ?>">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="messageInput" name="message" placeholder="Type your message..." required>
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card-body d-flex align-items-center justify-content-center">
                                <div class="text-center">
                                    <i class="fas fa-comment-alt fa-4x text-muted mb-3"></i>
                                    <h5>Select a contact to start chatting</h5>
                                    <p class="text-muted">Choose a contact from the list to begin a conversation</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to bottom of chat messages
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Handle form submission
            const chatForm = document.getElementById('chatForm');
            if (chatForm) {
                chatForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const messageInput = document.getElementById('messageInput');
                    const message = messageInput.value.trim();
                    
                    if (message) {
                        // Send message via AJAX
                        fetch('/ecommerce_farmers_fishers/api/send_chat_message.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams(new FormData(chatForm))
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Clear input and refresh messages
                                messageInput.value = '';
                                location.reload();
                            } else {
                                alert('Failed to send message: ' + (data.error || 'Unknown error'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to send message. Please try again.');
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>