<?php
// student_chat/index.php
require_once '../db_connection.php';
require_once 'chat_functions.php';

// Check student login
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Get student information to determine batch and admin
$student_query = $db->prepare("
    SELECT s.student_id, s.batch_name, b.batch_mentor_id as admin_id 
    FROM students s
    JOIN batches b ON s.batch_name = b.batch_id
    WHERE s.user_id = ?
");
$student_query->execute([$user_id]);
$student = $student_query->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student information not found");
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get conversations for this student
$conversations = [];
$query = $db->prepare("
    SELECT c.id, 
             CASE 
                 WHEN c.conversation_type = 'admin_student' THEN 
                     CONCAT('Admin: ', u.name)
                 WHEN c.conversation_type = 'admin_batch' THEN 
                     CONCAT('Batch: ', b.batch_id, ' - ', b.course_name)
             END as name,
             (SELECT COUNT(*) FROM chat_messages m 
              WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.is_read = 0) as unread,
             (SELECT MAX(sent_at) FROM chat_messages WHERE conversation_id = c.id) as last_message_time,
             (SELECT message FROM chat_messages WHERE conversation_id = c.id ORDER BY sent_at DESC LIMIT 1) as last_message
      FROM chat_conversations c
      LEFT JOIN users u ON c.admin_id = u.id
      LEFT JOIN batches b ON c.batch_id = b.batch_id
      WHERE (c.conversation_type = 'admin_student' AND c.student_id = ?)
         OR (c.conversation_type = 'admin_batch' AND c.batch_id = ?)
      ORDER BY last_message_time DESC
");
$query->execute([$user_id, $student['student_id'], $student['batch_name']]);
$conversations = $query->fetchAll(PDO::FETCH_ASSOC);

// Validate conversation access if one is selected (now via POST)
$active_conversation = null;
if (isset($_POST['conversation_id'])) {
    $conversation_id = intval($_POST['conversation_id']);
    
    // Check if user has access to this conversation
    foreach ($conversations as $conv) {
        if ($conv['id'] == $conversation_id) {
            $active_conversation = $conv;
            $_SESSION['active_conversation_id'] = $conversation_id; // Store in session
            break;
        }
    }
    
    if (!$active_conversation) {
        // User doesn't have access to this conversation
        unset($_SESSION['active_conversation_id']);
    }
} elseif (isset($_SESSION['active_conversation_id'])) {
    // Check if we have a conversation in session
    $conversation_id = $_SESSION['active_conversation_id'];
    foreach ($conversations as $conv) {
        if ($conv['id'] == $conversation_id) {
            $active_conversation = $conv;
            break;
        }
    }
    
    if (!$active_conversation) {
        unset($_SESSION['active_conversation_id']);
    }
}

include 'header.php';
?>

<div class="container-fluid p-0">
    <div class="row g-0 min-vh-100">
        <!-- Conversation List -->
        <div class="col-md-4 conversation-list">
            <div class="p-3 border-bottom">
                <h5 class="mb-0 fw-bold">Conversations</h5>
            </div>
            
            <?php if (empty($conversations)): ?>
                <div class="empty-state p-4">
                    <div class="empty-state-icon">
                        <i class="far fa-comment-dots"></i>
                    </div>
                    <div class="empty-state-text">
                        No conversations yet
                    </div>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($conversations as $conv): 
                        $initials = '';
                        if (strpos($conv['name'], 'Admin:') === 0) {
                            $parts = explode(' ', substr($conv['name'], 6));
                            $initials = substr($parts[0], 0, 1) . (count($parts) > 1 ? substr($parts[1], 0, 1) : '');
                        } else {
                            $initials = substr($conv['name'], 0, 1);
                        }
                    ?>
                        <form method="POST" action="index.php" class="conversation-form">
                            <input type="hidden" name="conversation_id" value="<?= $conv['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" class="list-group-item list-group-item-action conversation-item <?= ($active_conversation['id'] ?? '') == $conv['id'] ? 'active' : '' ?>">
                                <div class="d-flex align-items-center">
                                    <div class="conversation-avatar">
                                        <?= strtoupper($initials) ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 conversation-name"><?= htmlspecialchars($conv['name']) ?></h6>
                                            <small class="conversation-time"><?= $conv['last_message_time'] ? date('g:i A', strtotime($conv['last_message_time'])) : '' ?></small>
                                        </div>
                                        <p class="mb-0 conversation-preview"><?= htmlspecialchars($conv['last_message'] ?? 'No messages yet') ?></p>
                                    </div>
                                    <?php if ($conv['unread'] > 0): ?>
                                        <span class="unread-badge ms-2"><?= $conv['unread'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Chat Area -->
        <div class="col-md-8 chat-area">
            <?php if ($active_conversation): 
                $conversation_id = $active_conversation['id'];
                $messages = getConversationMessages($conversation_id, $user_id);
                markMessagesAsRead($conversation_id, $user_id);
                
                // Group messages by date
                $groupedMessages = [];
                foreach ($messages as $msg) {
                    $date = date('Y-m-d', strtotime($msg['sent_at']));
                    $groupedMessages[$date][] = $msg;
                }
            ?>
                <div class="chat-header d-flex align-items-center">
                    <div class="conversation-avatar me-3">
                        <?php 
                            $initials = '';
                            $convName = getConversationName($conversation_id, $user_id);
                            if (strpos($convName, 'Admin:') === 0) {
                                $parts = explode(' ', substr($convName, 6));
                                $initials = substr($parts[0], 0, 1) . (count($parts) > 1 ? substr($parts[1], 0, 1) : '');
                            } else {
                                $initials = substr($convName, 0, 1);
                            }
                            echo strtoupper($initials);
                        ?>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold"><?= htmlspecialchars($convName) ?></h5>
                    </div>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <?php foreach ($groupedMessages as $date => $dateMessages): ?>
                        <div class="date-divider">
                            <?= date('F j, Y', strtotime($date)) ?>
                        </div>
                        <?php foreach ($dateMessages as $msg): ?>
                            <div class="message <?= $msg['sender_id'] == $user_id ? 'message-out' : 'message-in' ?>" data-message-id="<?= $msg['id'] ?>">
                                <div class="message-bubble">
                                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                    <div class="message-info">
                                        <?= date('g:i A', strtotime($msg['sent_at'])) ?>
                                        <?php if ($msg['sender_id'] == $user_id): ?>
                                            <span class="message-status">
                                                <i class="fas fa-check<?= $msg['is_read'] ? '-double' : '' ?>"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
                
                <div class="chat-input">
                    <form id="messageForm" class="d-flex align-items-center gap-2" method="POST">
                        <input type="hidden" name="conversation_id" value="<?= $conversation_id ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="text" name="message" class="form-control message-input" placeholder="Type your message..." required>
                        <button type="submit" class="btn btn-primary send-btn">
                            <span id="buttonText">Send</span>
                            <span id="buttonSpinner" class="d-none ms-2">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            </span>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="far fa-comments"></i>
                    </div>
                    <h4 class="empty-state-text">Select a conversation to start chatting</h4>
                    <p class="text-muted">Or start a new conversation with your admin</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dayjs@1/plugin/relativeTime.js"></script>
<script>
dayjs.extend(window.dayjs_plugin_relativeTime);

$(document).ready(function() {
    let isSending = false;
    let isTyping = false;
    let lastMessageDate = null;
    
    // Initialize chat messages scroll position
    scrollToBottom();
    
    // Auto refresh messages every 2 seconds
    setInterval(fetchNewMessages, 2000);
    
    // Send message with enhanced UI
    $('#messageForm').submit(function(e) {
        e.preventDefault();
        
        if (isSending) return;
        
        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        const messageInput = form.find('input[name="message"]');
        const message = messageInput.val().trim();
        
        if (!message) return;
        
        // Show sending state
        submitButton.prop('disabled', true);
        $('#buttonText').text('Sending');
        $('#buttonSpinner').removeClass('d-none');
        isSending = true;
        
        // Add temporary "typing" indicator
        if (!isTyping) {
            isTyping = true;
            const typingHtml = `
                <div class="message message-in" id="typingIndicator">
                    <div class="typing-indicator">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
            `;
            $('#chatMessages').append(typingHtml);
            scrollToBottom();
        }
        
        $.ajax({
            url: 'ajax_send_message.php',
            type: 'POST',
            data: {
                conversation_id: form.find('input[name="conversation_id"]').val(),
                message: message,
                csrf_token: $('input[name="csrf_token"]').val()
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    messageInput.val('');
                    // Remove typing indicator
                    $('#typingIndicator').remove();
                    isTyping = false;
                    
                    // The message will appear in the next auto-refresh
                } else if (data.error) {
                    showAlert('Error', data.error, 'danger');
                }
            },
            error: function(xhr, status, error) {
                showAlert('Error', 'Failed to send message. Please try again.', 'danger');
            },
            complete: function() {
                submitButton.prop('disabled', false);
                $('#buttonText').text('Send');
                $('#buttonSpinner').addClass('d-none');
                isSending = false;
            }
        });
    });
    
    // Focus message input when conversation is selected
    if ($('input[name="conversation_id"]').length) {
        $('input[name="message"]').focus();
    }
    
    // Function to fetch new messages
    function fetchNewMessages() {
        if (!$('#chatMessages').length) return;
        
        const conversation_id = $('input[name="conversation_id"]').val();
        const last_message_id = $('#chatMessages .message').last().data('message-id') || 0;
        
        $.post('ajax_get_messages.php', {
            conversation_id: conversation_id,
            last_message_id: last_message_id
        }, function(data) {
            if (data.messages && data.messages.length > 0) {
                // Remove typing indicator if present
                $('#typingIndicator').remove();
                
                // Group messages by date
                const groupedMessages = {};
                data.messages.forEach(msg => {
                    const date = dayjs(msg.sent_at).format('YYYY-MM-DD');
                    if (!groupedMessages[date]) {
                        groupedMessages[date] = [];
                    }
                    groupedMessages[date].push(msg);
                });
                
                // Add new messages to chat
                for (const date in groupedMessages) {
                    const messages = groupedMessages[date];
                    
                    // Check if we need to add a date divider
                    const currentDate = dayjs().format('YYYY-MM-DD');
                    const messageDate = dayjs(date).format('YYYY-MM-DD');
                    const displayDate = messageDate === currentDate ? 'Today' : dayjs(messageDate).format('MMMM D, YYYY');
                    
                    // Add date divider if this is a new date
                    if (messageDate !== lastMessageDate) {
                        const dateDivider = $(`<div class="date-divider">${displayDate}</div>`);
                        $('#chatMessages').append(dateDivider);
                        lastMessageDate = messageDate;
                    }
                    
                    // Add messages
                    messages.forEach(msg => {
                        const isMe = msg.sender_id == <?= $user_id ?>;
                        const messageHtml = `
                            <div class="message ${isMe ? 'message-out' : 'message-in'}" data-message-id="${msg.id}">
                                <div class="message-bubble">
                                    ${msg.conversation_type === 'admin_batch' && !isMe ? `<div class="sender-name">${msg.sender_name}</div>` : ''}
                                    ${msg.message.replace(/\n/g, '<br>')}
                                    <div class="message-info">
                                        ${dayjs(msg.sent_at).format('h:mm A')}
                                        ${isMe ? `<span class="message-status"><i class="fas fa-check${msg.is_read ? '-double' : ''}"></i></span>` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                        $('#chatMessages').append(messageHtml);
                    });
                }
                
                // Smooth scroll to bottom
                scrollToBottom();
                
                // Update unread counts in conversation list
                updateUnreadCounts();
                
                // Play notification sound for new messages not from me
                const newMessagesFromOthers = data.messages.filter(msg => msg.sender_id != <?= $user_id ?>);
                if (newMessagesFromOthers.length > 0) {
                    playNotificationSound();
                }
            }
        }, 'json').fail(function(xhr, status, error) {
            console.error('Error fetching messages:', error);
        });
    }
    
    // Function to update unread counts in conversation list
    function updateUnreadCounts() {
        $.post('get_unread_counts.php', function(data) {
            if (data && data.conversations) {
                data.conversations.forEach(conv => {
                    const badge = $(`.conversation-form input[value="${conv.id}"]`).siblings('button').find('.unread-badge');
                    if (conv.unread > 0) {
                        if (badge.length) {
                            badge.text(conv.unread);
                        } else {
                            $(`.conversation-form input[value="${conv.id}"]`).siblings('button').append(
                                `<span class="unread-badge ms-2">${conv.unread}</span>`
                            );
                        }
                    } else {
                        badge.remove();
                    }
                });
            }
        }, 'json');
    }
    
    // Function to scroll to bottom of chat
    function scrollToBottom() {
        const chatMessages = $('#chatMessages')[0];
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }
    
    // Function to show alert messages
    function showAlert(title, message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show m-3 position-fixed top-0 end-0" style="z-index: 1100;" role="alert">
                <strong>${title}</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('body').append(alertHtml);
        setTimeout(() => {
            $('.alert').alert('close');
        }, 5000);
    }
    
    // Function to play notification sound
    function playNotificationSound() {
        const audio = new Audio('https://assets.mixkit.co/sfx/preview/mixkit-software-interface-start-2574.mp3');
        audio.volume = 0.3;
        audio.play().catch(e => console.log('Audio play failed:', e));
    }
    
    // Make conversation items draggable on touch devices
    if ('ontouchstart' in window) {
        $('.conversation-item').each(function() {
            let startX, startY;
            const item = $(this);
            
            item.on('touchstart', function(e) {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
            });
            
            item.on('touchmove', function(e) {
                if (!startX || !startY) return;
                
                const diffX = e.touches[0].clientX - startX;
                const diffY = e.touches[0].clientY - startY;
                
                if (Math.abs(diffX) > Math.abs(diffY)) {
                    e.preventDefault();
                    item.css('transform', `translateX(${diffX}px)`);
                }
            });
            
            item.on('touchend', function(e) {
                const diffX = e.changedTouches[0].clientX - startX;
                
                if (Math.abs(diffX) > 50) {
                    item.css('transform', 'translateX(0)');
                    item.css('transition', 'transform 0.3s ease');
                    setTimeout(() => {
                        item.css('transition', '');
                    }, 300);
                } else {
                    item.css('transform', 'translateX(0)');
                }
                
                startX = null;
                startY = null;
            });
        });
    }
});
</script>

<?php include 'footer.php'; ?>