<?php
require_once '../db_connection.php';
require_once 'chat_functions.php';

// Check admin login
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle new conversation creation
if (isset($_POST['create_conversations'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Invalid CSRF token';
        header("Location: index.php");
        exit;
    }

    $success_count = 0;
    $error_messages = [];
    $created_conversation_id = null;

    if (isset($_POST['student_ids'])) {
        foreach ($_POST['student_ids'] as $student_id) {
            $student_id = $db->quote($student_id);
            try {
                // Check if conversation already exists
                $existing = $db->query("SELECT id FROM chat_conversations 
                                      WHERE conversation_type = 'admin_student' 
                                      AND admin_id = $user_id 
                                      AND student_id = $student_id")->fetch();
                
                if ($existing) {
                    $created_conversation_id = $existing['id'];
                } else {
                    // Create new conversation
                    $db->query("INSERT INTO chat_conversations 
                              (conversation_type, admin_id, student_id, created_at, updated_at) 
                              VALUES 
                              ('admin_student', $user_id, $student_id, NOW(), NOW())");
                    $created_conversation_id = $db->lastInsertId();
                    
                    // Add participants
                    $db->query("INSERT INTO chat_participants (conversation_id, user_id) 
                              VALUES ($created_conversation_id, $user_id)");
                    
                    // Get student's user_id
                    $student_user_id = $db->query("SELECT user_id FROM students WHERE student_id = $student_id")->fetchColumn();
                    if ($student_user_id) {
                        $db->query("INSERT INTO chat_participants (conversation_id, user_id) 
                                  VALUES ($created_conversation_id, $student_user_id)");
                    }
                }
                
                if ($created_conversation_id) {
                    $success_count++;
                }
            } catch (PDOException $e) {
                $error_messages[] = "Failed to create conversation for student ID: $student_id";
            }
        }
    }
    
    if (isset($_POST['batch_ids'])) {
        foreach ($_POST['batch_ids'] as $batch_id) {
            $batch_id = $db->quote($batch_id);
            try {
                // Check if conversation already exists
                $existing = $db->query("SELECT id FROM chat_conversations 
                                      WHERE conversation_type = 'admin_batch' 
                                      AND admin_id = $user_id 
                                      AND batch_id = $batch_id")->fetch();
                
                if ($existing) {
                    $created_conversation_id = $existing['id'];
                } else {
                    // Create new conversation
                    $db->query("INSERT INTO chat_conversations 
                              (conversation_type, admin_id, batch_id, created_at, updated_at) 
                              VALUES 
                              ('admin_batch', $user_id, $batch_id, NOW(), NOW())");
                    $created_conversation_id = $db->lastInsertId();
                    
                    // Add admin as participant
                    $db->query("INSERT INTO chat_participants (conversation_id, user_id) 
                              VALUES ($created_conversation_id, $user_id)");
                    
                    // Add all students in the batch as participants
                    $students = $db->query("SELECT user_id FROM students WHERE batch_name = $batch_id")->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($students as $student_user_id) {
                        $db->query("INSERT INTO chat_participants (conversation_id, user_id) 
                                    VALUES ($created_conversation_id, $student_user_id)");
                    }
                }
                
                if ($created_conversation_id) {
                    $success_count++;
                }
            } catch (PDOException $e) {
                $error_messages[] = "Failed to create conversation for batch ID: $batch_id";
            }
        }
    }
    
    if ($success_count > 0) {
        $_SESSION['success'] = "Successfully created $success_count conversation(s)";
        // Redirect to the first created conversation
        if ($created_conversation_id) {
            header("Location: index.php?conversation=" . $created_conversation_id);
            exit;
        }
    }
    if (!empty($error_messages)) {
        $_SESSION['error'] = implode("<br>", $error_messages);
    }
    
    header("Location: index.php");
    exit;
}

// Get conversations for this admin
$conversations = getAdminConversations($user_id);

include 'header.php';
?>

<style>
/* Enhanced modal animations */
.modal.fade .modal-dialog {
    transform: translateY(-50px) scale(0.95);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.25, 0.5, 0.5, 1.25);
}

.modal.show .modal-dialog {
    transform: translateY(0) scale(1);
    opacity: 1;
}

/* Modal content styling */
.modal-content {
    border: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    border-radius: 12px;
    overflow: hidden;
}

.modal-header {
    background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
    color: white;
    border-bottom: none;
    padding: 1.5rem;
}

.modal-title {
    font-weight: 600;
    display: flex;
    align-items: center;
}

.modal-title i {
    margin-right: 10px;
    font-size: 1.2em;
}

.modal-body {
    padding: 2rem;
}

/* Tab styling */
.nav-tabs {
    border-bottom: 2px solid #f0f0f0;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    font-weight: 500;
    padding: 12px 20px;
    transition: all 0.3s;
}

.nav-tabs .nav-link:hover {
    color: #2575fc;
    background-color: rgba(37, 117, 252, 0.05);
}

.nav-tabs .nav-link.active {
    color: #2575fc;
    background-color: transparent;
    border-bottom: 3px solid #2575fc;
}

/* Search box styling */
.search-box {
    position: relative;
    margin-bottom: 1.5rem;
}

.search-box i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.search-box input {
    padding-left: 40px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s;
}

.search-box input:focus {
    border-color: #2575fc;
    box-shadow: 0 0 0 0.2rem rgba(37, 117, 252, 0.25);
}

/* List items styling */
.student-item, .batch-item {
    transition: all 0.2s;
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
}

.student-item:hover, .batch-item:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #2575fc;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.batch-item .user-avatar {
    background-color: #6a11cb;
}

/* Button styling */
.btn-light {
    background-color: #f8f9fa;
    border-color: #f8f9fa;
}

.btn-light:hover {
    background-color: #e9ecef;
    border-color: #e9ecef;
}

/* Floating action button */
.floating-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 5px 20px rgba(106, 17, 203, 0.3);
    cursor: pointer;
    z-index: 1000;
    transition: all 0.3s;
}

.floating-btn:hover {
    transform: translateY(-5px) scale(1.1);
    box-shadow: 0 8px 25px rgba(106, 17, 203, 0.4);
}

/* Checkbox styling */
.form-check-input {
    width: 20px;
    height: 20px;
    margin-top: 0;
}

.form-check-input:checked {
    background-color: #2575fc;
    border-color: #2575fc;
}

/* Smooth transitions for list items */
.list-group-item {
    transition: all 0.3s ease;
}

/* Pulse animation for new chat button */
@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(37, 117, 252, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(37, 117, 252, 0); }
    100% { box-shadow: 0 0 0 0 rgba(37, 117, 252, 0); }
}

.btn-new-chat {
    animation: pulse 2s infinite;
    position: relative;
    overflow: hidden;
}

.btn-new-chat::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 5px;
    height: 5px;
    background: rgba(255, 255, 255, 0.5);
    opacity: 0;
    border-radius: 100%;
    transform: scale(1, 1) translate(-50%, -50%);
    transform-origin: 50% 50%;
}

.btn-new-chat:focus:not(:active)::after {
    animation: ripple 1s ease-out;
}

@keyframes ripple {
    0% {
        transform: scale(0, 0);
        opacity: 0.5;
    }
    100% {
        transform: scale(20, 20);
        opacity: 0;
    }
}

/* Confirmation modal styling */
#deleteConversationModal .modal-header {
    background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
}

#deleteConversationModal .modal-body {
    padding: 2rem;
    text-align: center;
}

#deleteConversationModal .modal-footer {
    justify-content: center;
    border-top: none;
    padding-bottom: 2rem;
}

/* Chat Profile Modal Styling */
#chatProfileModal .modal-header {
    position: relative;
    padding-bottom: 0;
}

#chatProfileModal .profile-header {
    text-align: center;
    padding: 2rem 1rem 1rem;
}

#chatProfileModal .profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    margin: 0 auto 1rem;
    background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: bold;
}

#chatProfileModal .profile-name {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

#chatProfileModal .profile-meta {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}

#chatProfileModal .profile-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 1.5rem;
}

#chatProfileModal .profile-action-btn {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    transition: all 0.2s;
    color: #495057;
    text-decoration: none;
}

#chatProfileModal .profile-action-btn:hover {
    background-color: #f8f9fa;
    color: #2575fc;
}

#chatProfileModal .profile-action-btn i {
    margin-right: 10px;
    font-size: 1.1rem;
    width: 24px;
    text-align: center;
}

#chatProfileModal .profile-action-btn.danger {
    color: #dc3545;
}

#chatProfileModal .profile-action-btn.danger:hover {
    background-color: rgba(220, 53, 69, 0.05);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .modal-dialog {
        margin: 1rem auto;
    }
    
    .modal-content {
        border-radius: 0;
    }
    
    .floating-btn {
        width: 50px;
        height: 50px;
        bottom: 20px;
        right: 20px;
    }
    
    #chatProfileModal .profile-avatar {
        width: 80px;
        height: 80px;
        font-size: 2rem;
    }
}
</style>

<!-- Display success/error messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card chat-container">
                <div class="card-header chat-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-comments me-2"></i> Conversations</span>
                    <button class="btn btn-sm btn-primary rounded-pill btn-new-chat" onclick="showNewChatModal()">
                        <i class="fas fa-plus me-1"></i> New Chat
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="p-3 search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="conversationSearch" class="form-control ps-4" placeholder="Search conversations...">
                    </div>
                    <div class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($conversations as $conv): 
                            $initials = '';
                            if ($conv['conversation_type'] == 'admin_student') {
                                $nameParts = explode(' ', $conv['name']);
                                $initials = strtoupper(
                                    substr($nameParts[0], 0, 1) . 
                                    (count($nameParts) > 1 ? substr(end($nameParts), 0, 1) : '')
                                );
                            } else {
                                $initials = 'B';
                            }
                        ?>
                            <a href="?conversation=<?= $conv['id'] ?>" 
                               class="list-group-item list-group-item-action conversation-item d-flex align-items-center <?= ($_GET['conversation'] ?? '') == $conv['id'] ? 'active' : '' ?>">
                                <div class="conversation-avatar me-3">
                                    <?= $initials ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <div class="conversation-name"><?= htmlspecialchars($conv['name']) ?></div>
                                        <?php if ($conv['unread'] > 0): ?>
                                            <span class="badge bg-primary rounded-pill"><?= $conv['unread'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="conversation-preview">
                                        <?php 
                                        $lastMessage = getLastMessagePreview($conv['id']);
                                        echo $lastMessage ? htmlspecialchars($lastMessage) : 'No messages yet';
                                        ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <?php if (isset($_GET['conversation'])): 
                $conversation_id = intval($_GET['conversation']);
                $messages = getConversationMessages($conversation_id);
                markMessagesAsRead($conversation_id, $user_id);
                $conversation_name = getConversationName($conversation_id);
                $is_batch = strpos($conversation_name, 'Batch:') === 0;
                
                // Get conversation details for profile modal
                $conversation = $db->query("SELECT * FROM chat_conversations WHERE id = $conversation_id")->fetch();
                $participant_count = $db->query("SELECT COUNT(*) FROM chat_participants WHERE conversation_id = $conversation_id")->fetchColumn();
                
                // Get creation date
                $created_at = new DateTime($conversation['created_at']);
                $created_date = $created_at->format('M j, Y');
                $created_time = $created_at->format('g:i A');
            ?>
                <div class="card chat-container h-100">
                    <div class="card-header chat-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div>
                                <i class="fas fa-user-friends me-2"></i>
                                <?= htmlspecialchars($conversation_name) ?>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-light me-2" onclick="showChatProfileModal()">
                                <i class="fas fa-exclamation-circle"></i>
                            </button>
                    </div>
                    <div class="card-body chat-messages" id="chatMessages">
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-comment-slash fa-3x mb-3"></i>
                                <h5>No messages yet</h5>
                                <p>Start the conversation by sending a message</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <div class="message <?= $msg['sender_id'] == $user_id ? 'message-out' : 'message-in' ?>" data-message-id="<?= $msg['id'] ?>">
                                    <div class="message-bubble">
                                        <?php if ($is_batch && $msg['sender_id'] != $user_id): ?>
                                            <div class="fw-bold small mb-1"><?= htmlspecialchars($msg['sender_name']) ?></div>
                                        <?php endif; ?>
                                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                        
                                        <?php if ($msg['has_attachments']): ?>
                                            <?php 
                                            $attachments = $db->query("SELECT * FROM chat_attachments WHERE message_id = {$msg['id']}")->fetchAll(PDO::FETCH_ASSOC);
                                            foreach ($attachments as $attachment): ?>
                                                <div class="attachment mt-2">
                                                    <a href="download_attachment.php?id=<?= $attachment['id'] ?>" class="d-flex align-items-center text-decoration-none">
                                                        <div class="attachment-icon me-2">
                                                            <?php 
                                                            $file_ext = pathinfo($attachment['file_name'], PATHINFO_EXTENSION);
                                                            $icon_class = 'fa-file';
                                                            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                                                                $icon_class = 'fa-file-image';
                                                            } elseif (in_array($file_ext, ['pdf'])) {
                                                                $icon_class = 'fa-file-pdf';
                                                            } elseif (in_array($file_ext, ['doc', 'docx'])) {
                                                                $icon_class = 'fa-file-word';
                                                            } elseif (in_array($file_ext, ['xls', 'xlsx'])) {
                                                                $icon_class = 'fa-file-excel';
                                                            } elseif (in_array($file_ext, ['ppt', 'pptx'])) {
                                                                $icon_class = 'fa-file-powerpoint';
                                                            } elseif (in_array($file_ext, ['zip', 'rar', '7z'])) {
                                                                $icon_class = 'fa-file-archive';
                                                            }
                                                            ?>
                                                            <i class="fas <?= $icon_class ?> fa-lg text-primary"></i>
                                                        </div>
                                                        <div class="attachment-info">
                                                            <div class="attachment-name"><?= htmlspecialchars($attachment['file_name']) ?></div>
                                                            <small class="text-muted"><?= formatFileSize($attachment['file_size']) ?></small>
                                                        </div>
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <div class="message-info">
                                            <?= date('M j, Y g:i A', strtotime($msg['sent_at'])) ?>
                                            <?php if ($msg['sender_id'] == $user_id): ?>
                                                <span class="ms-2"><i class="fas fa-check<?= $msg['is_read'] ? '-double text-info' : '' ?>"></i></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div id="typingIndicator" class="d-none">
                            <div class="message message-in">
                                <div class="typing-indicator">
                                    <div class="typing-dot"></div>
                                    <div class="typing-dot"></div>
                                    <div class="typing-dot"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer chat-input">
                        <form id="messageForm" class="d-flex flex-column">
                            <input type="hidden" name="conversation_id" value="<?= $conversation_id ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="d-flex mb-2" id="filePreviewContainer" style="display: none;">
                                <div class="file-preview d-flex align-items-center bg-light p-2 rounded">
                                    <i class="fas fa-paperclip me-2"></i>
                                    <span id="fileNamePreview"></span>
                                    <button type="button" class="btn-close ms-2" onclick="cancelFileUpload()"></button>
                                </div>
                            </div>
                            
                            <div class="d-flex">
                                <div class="btn-group me-2">
                                    <button type="button" class="btn btn-light" onclick="document.getElementById('fileInput').click()">
                                        <i class="fas fa-paperclip"></i>
                                    </button>
                                    <input type="file" id="fileInput" name="attachment" style="display: none;" onchange="handleFileSelect(this)">
                                </div>
                                <input type="text" name="message" class="form-control flex-grow-1 me-2" placeholder="Type your message...">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i> Send
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card chat-container h-100">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center">
                        <div class="mb-4">
                            <i class="fas fa-comments fa-4x text-muted"></i>
                        </div>
                        <h4 class="mb-3">Welcome to ASD Academy Chat</h4>
                        <p class="text-muted mb-4">Select a conversation to start chatting or create a new one</p>
                        <button class="btn btn-primary btn-new-chat" onclick="showNewChatModal()">
                            <i class="fas fa-plus me-1"></i> Start New Chat
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Enhanced New Chat Selection Modal -->
<div class="modal fade" id="newChatModal" tabindex="-1" aria-labelledby="newChatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newChatModalLabel"><i class="fas fa-plus-circle me-2"></i>Create New Conversations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="newChatForm" method="post" action="index.php">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="create_conversations" value="1">
                    
                    <ul class="nav nav-tabs" id="newChatTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="student-tab" data-bs-toggle="tab" data-bs-target="#student-tab-pane" type="button" role="tab">
                                <i class="fas fa-user-graduate me-1"></i> Students
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="batch-tab" data-bs-toggle="tab" data-bs-target="#batch-tab-pane" type="button" role="tab">
                                <i class="fas fa-users me-1"></i> Batches
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content p-4" id="newChatTabsContent">
                        <div class="tab-pane fade show active" id="student-tab-pane" role="tabpanel">
                            <div class="search-box mb-3">
                                <i class="fas fa-search"></i>
                                <input type="text" id="studentSearch" class="form-control ps-4" placeholder="Search students...">
                            </div>
                            <div class="mb-3">
                                <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="selectAllStudents()">
                                    <i class="fas fa-check-circle me-1"></i> Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllStudents()">
                                    <i class="fas fa-times-circle me-1"></i> Deselect All
                                </button>
                            </div>
                            <div id="studentList" style="max-height: 400px; overflow-y: auto;">
                                <?php
                                $students = $db->query("SELECT student_id, first_name, last_name FROM students ORDER BY first_name")->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($students as $student): 
                                    $initials = strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1));
                                ?>
                                    <div class="student-item p-3 border-bottom d-flex align-items-center">
                                        <div class="form-check me-3">
                                            <input class="form-check-input student-checkbox" type="checkbox" name="student_ids[]" value="<?= $student['student_id'] ?>" id="student-<?= $student['student_id'] ?>">
                                        </div>
                                        <div class="user-avatar me-3">
                                            <?= $initials ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <label class="form-check-label" for="student-<?= $student['student_id'] ?>">
                                                <div class="fw-bold"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></div>
                                                <small class="text-muted">Student ID: <?= $student['student_id'] ?></small>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="batch-tab-pane" role="tabpanel">
                            <div class="search-box mb-3">
                                <i class="fas fa-search"></i>
                                <input type="text" id="batchSearch" class="form-control ps-4" placeholder="Search batches...">
                            </div>
                            <div class="mb-3">
                                <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="selectAllBatches()">
                                    <i class="fas fa-check-circle me-1"></i> Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllBatches()">
                                    <i class="fas fa-times-circle me-1"></i> Deselect All
                                </button>
                            </div>
                            <div id="batchList" style="max-height: 400px; overflow-y: auto;">
                                <?php
                                $batches = $db->query("SELECT batch_id, course_name FROM batches ORDER BY batch_id")->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($batches as $batch): ?>
                                    <div class="batch-item p-3 border-bottom d-flex align-items-center">
                                        <div class="form-check me-3">
                                            <input class="form-check-input batch-checkbox" type="checkbox" name="batch_ids[]" value="<?= $batch['batch_id'] ?>" id="batch-<?= $batch['batch_id'] ?>">
                                        </div>
                                        <div class="user-avatar me-3 bg-info">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <label class="form-check-label" for="batch-<?= $batch['batch_id'] ?>">
                                                <div class="fw-bold"><?= htmlspecialchars('Batch: ' . $batch['batch_id']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($batch['course_name']) ?></small>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-comments me-1"></i> Create Conversations
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Chat Profile Modal -->
<div class="modal fade" id="chatProfileModal" tabindex="-1" aria-labelledby="chatProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chatProfileModalLabel"><i class="fas fa-info-circle me-2"></i>Chat Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php 
                        $nameParts = explode(' ', $conversation_name);
                        $initials = strtoupper(
                            substr($nameParts[0], 0, 1) . 
                            (count($nameParts) > 1 ? substr(end($nameParts), 0, 1) : ''
                        ));
                        echo $initials;
                        ?>
                    </div>
                    <div class="profile-name"><?= htmlspecialchars($conversation_name) ?></div>
                    <div class="profile-meta">
                        <div><i class="fas fa-users me-1"></i> <?= $participant_count ?> participants</div>
                        <div><i class="fas fa-calendar-alt me-1"></i> Created on <?= $created_date ?> at <?= $created_time ?></div>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <a href="#" class="profile-action-btn" data-bs-toggle="modal" data-bs-target="#participantsModal" onclick="loadParticipants()">
                        <i class="fas fa-users"></i> View Participants
                    </a>
                    <a href="#" class="profile-action-btn" onclick="confirmClearHistory()">
                        <i class="fas fa-trash"></i> Clear Chat History
                    </a>
                    <a href="#" class="profile-action-btn" onclick="muteNotifications()">
                        <i class="fas fa-bell-slash"></i> Mute Notifications
                    </a>
                    <a href="#" class="profile-action-btn danger" onclick="confirmDeleteConversation()">
                        <i class="fas fa-trash-alt"></i> Delete Conversation
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Participants Modal -->
<div class="modal fade" id="participantsModal" tabindex="-1" aria-labelledby="participantsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="participantsModalLabel"><i class="fas fa-users me-2"></i>Participants</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="participantsList">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Delete Conversation Confirmation Modal -->
<div class="modal fade" id="deleteConversationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this conversation? This action cannot be undone.</p>
                <div id="deleteConversationWarning" class="alert alert-warning d-none mt-3">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Warning:</strong> As the admin, deleting this conversation will remove it for all participants.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Floating action button -->
<div class="floating-btn d-md-none" onclick="showNewChatModal()">
    <i class="fas fa-comment-alt fa-lg"></i>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize all dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Scroll to bottom of chat messages
    function scrollToBottom() {
        const chatMessages = $('#chatMessages');
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }
    
    // Initialize scroll position
    scrollToBottom();
    
    // Conversation search functionality
    $('#conversationSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.conversation-item').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    });

    // Student search functionality
    $('#studentSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.student-item').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    });

    // Batch search functionality
    $('#batchSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.batch-item').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    });
    
    // Auto refresh messages every 3 seconds
    let isTyping = false;
    let refreshInterval = setInterval(refreshMessages, 3000);
    
    function refreshMessages() {
        if ($('#chatMessages').length && !isTyping) {
            const conversation_id = $('input[name="conversation_id"]').val();
            const last_message_id = $('#chatMessages .message').last().data('message-id') || 0;
            
            $.get('ajax_get_messages.php', {
                conversation_id: conversation_id,
                last_message_id: last_message_id
            }, function(data) {
                if (data.messages && data.messages.length > 0) {
                    // Remove "no messages" placeholder if it exists
                    $('#chatMessages .text-center').remove();
                    
                    data.messages.forEach(function(msg) {
                        const isMe = msg.sender_id == <?= $user_id ?>;
                        const messageClass = isMe ? 'message-out' : 'message-in';
                        
                        let attachmentsHtml = '';
                        if (msg.attachments && msg.attachments.length > 0) {
                            msg.attachments.forEach(function(attachment) {
                                const file_ext = attachment.file_name.split('.').pop().toLowerCase();
                                let icon_class = 'fa-file';
                                if (['jpg', 'jpeg', 'png', 'gif'].includes(file_ext)) {
                                    icon_class = 'fa-file-image';
                                } else if (['pdf'].includes(file_ext)) {
                                    icon_class = 'fa-file-pdf';
                                } else if (['doc', 'docx'].includes(file_ext)) {
                                    icon_class = 'fa-file-word';
                                } else if (['xls', 'xlsx'].includes(file_ext)) {
                                    icon_class = 'fa-file-excel';
                                } else if (['ppt', 'pptx'].includes(file_ext)) {
                                    icon_class = 'fa-file-powerpoint';
                                } else if (['zip', 'rar', '7z'].includes(file_ext)) {
                                    icon_class = 'fa-file-archive';
                                }
                                
                                attachmentsHtml += `
                                    <div class="attachment mt-2">
                                        <a href="download_attachment.php?id=${attachment.id}" class="d-flex align-items-center text-decoration-none">
                                            <div class="attachment-icon me-2">
                                                <i class="fas ${icon_class} fa-lg text-primary"></i>
                                            </div>
                                            <div class="attachment-info">
                                                <div class="attachment-name">${attachment.file_name}</div>
                                                <small class="text-muted">${formatFileSize(attachment.file_size)}</small>
                                            </div>
                                        </a>
                                    </div>
                                `;
                            });
                        }
                        
                        const messageHtml = `
                            <div class="message ${messageClass}" data-message-id="${msg.id}">
                                <div class="message-bubble">
                                    ${<?= isset($is_batch) && $is_batch ? 'true' : 'false' ?> && !isMe ? `<div class="fw-bold small mb-1">${msg.sender_name}</div>` : ''}
                                    ${msg.message ? msg.message.replace(/\n/g, '<br>') : ''}
                                    ${attachmentsHtml}
                                    <div class="message-info">
                                        ${new Date(msg.sent_at).toLocaleString()}
                                        ${isMe ? `<span class="ms-2"><i class="fas fa-check${msg.is_read ? '-double text-info' : ''}"></i></span>` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        $('#chatMessages').append(messageHtml);
                    });
                    
                    scrollToBottom();
                }
                
                // Update CSRF token
                if (data.csrf_token) {
                    $('input[name="csrf_token"]').val(data.csrf_token);
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('Error fetching messages:', error);
            });
        }
    }
    
    // Send message with file attachment
    $('#messageForm').submit(function(e) {
        e.preventDefault();
        
        const form = $(this);
        const messageInput = form.find('input[name="message"]');
        const message = messageInput.val().trim();
        const fileInput = document.getElementById('fileInput');
        const file = fileInput.files[0];
        
        if (!message && !file) {
            return;
        }
        
        const formData = new FormData();
        formData.append('conversation_id', form.find('input[name="conversation_id"]').val());
        formData.append('message', message);
        formData.append('csrf_token', $('input[name="csrf_token"]').val());
        if (file) {
            formData.append('attachment', file);
        }
        
        $.ajax({
            url: 'ajax_send_message.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function() {
                // Show sending state
                messageInput.prop('disabled', true);
                form.find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin me-1"></i> Sending');
            },
            success: function(data) {
                if (data.success) {
                    messageInput.val('');
                    // Remove "no messages" placeholder if it exists
                    $('#chatMessages .text-center').remove();
                    
                    // Manually add the sent message to the chat
                    let attachmentsHtml = '';
                    if (data.attachments && data.attachments.length > 0) {
                        data.attachments.forEach(function(attachment) {
                            const file_ext = attachment.file_name.split('.').pop().toLowerCase();
                            let icon_class = 'fa-file';
                            if (['jpg', 'jpeg', 'png', 'gif'].includes(file_ext)) {
                                icon_class = 'fa-file-image';
                            } else if (['pdf'].includes(file_ext)) {
                                icon_class = 'fa-file-pdf';
                            } else if (['doc', 'docx'].includes(file_ext)) {
                                icon_class = 'fa-file-word';
                            } else if (['xls', 'xlsx'].includes(file_ext)) {
                                icon_class = 'fa-file-excel';
                            } else if (['ppt', 'pptx'].includes(file_ext)) {
                                icon_class = 'fa-file-powerpoint';
                            } else if (['zip', 'rar', '7z'].includes(file_ext)) {
                                icon_class = 'fa-file-archive';
                            }
                            
                            attachmentsHtml += `
                                <div class="attachment mt-2">
                                    <a href="download_attachment.php?id=${attachment.id}" class="d-flex align-items-center text-decoration-none">
                                        <div class="attachment-icon me-2">
                                            <i class="fas ${icon_class} fa-lg text-primary"></i>
                                        </div>
                                        <div class="attachment-info">
                                            <div class="attachment-name">${attachment.file_name}</div>
                                            <small class="text-muted">${formatFileSize(attachment.file_size)}</small>
                                        </div>
                                    </a>
                                </div>
                            `;
                        });
                    }
                    
                    const messageHtml = `
                        <div class="message message-out" data-message-id="${data.message_id}">
                            <div class="message-bubble">
                                ${message ? message.replace(/\n/g, '<br>') : ''}
                                ${attachmentsHtml}
                                <div class="message-info">
                                    ${new Date().toLocaleString()}
                                    <span class="ms-2"><i class="fas fa-check"></i></span>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#chatMessages').append(messageHtml);
                    scrollToBottom();
                    
                    // Reset file input
                    cancelFileUpload();
                } else if (data.error) {
                    alert('Error: ' + data.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('Error sending message. Please try again.');
            },
            complete: function() {
                messageInput.prop('disabled', false);
                form.find('button[type="submit"]').html('<i class="fas fa-paper-plane me-1"></i> Send');
                messageInput.focus();
            }
        });
    });
    
    // Typing indicator
    let typingTimeout;
    $('input[name="message"]').on('input', function() {
        if ($(this).val().trim().length > 0) {
            if (!isTyping) {
                isTyping = true;
                $('#typingIndicator').removeClass('d-none');
                scrollToBottom();
            }
            
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(function() {
                isTyping = false;
                $('#typingIndicator').addClass('d-none');
            }, 2000);
        } else {
            isTyping = false;
            $('#typingIndicator').addClass('d-none');
        }
    });
    
    // Focus message input when conversation is selected
    if ($('input[name="message"]').length) {
        $('input[name="message"]').focus();
    }
});

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Handle file selection
function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileNamePreview = document.getElementById('fileNamePreview');
        const filePreviewContainer = document.getElementById('filePreviewContainer');
        
        fileNamePreview.textContent = file.name;
        filePreviewContainer.style.display = 'flex';
        
        // Add animation
        filePreviewContainer.style.animation = 'fadeIn 0.3s ease-out';
    }
}

// Cancel file upload
function cancelFileUpload() {
    const filePreviewContainer = document.getElementById('filePreviewContainer');
    filePreviewContainer.style.animation = 'fadeOut 0.3s ease-out';
    setTimeout(() => {
        document.getElementById('fileInput').value = '';
        filePreviewContainer.style.display = 'none';
    }, 300);
}

// Show new chat modal
function showNewChatModal() {
    const modal = new bootstrap.Modal(document.getElementById('newChatModal'), {
        backdrop: 'static',
        keyboard: false
    });
    modal.show();
    
    // Reset form when modal is shown
    $('#newChatForm')[0].reset();
    $('.student-checkbox, .batch-checkbox').prop('checked', false);
}

// Show chat profile modal
function showChatProfileModal() {
    const modal = new bootstrap.Modal(document.getElementById('chatProfileModal'), {
        backdrop: 'static',
        keyboard: false
    });
    modal.show();
}

// Select all students
function selectAllStudents() {
    $('.student-checkbox').prop('checked', true);
    animateCheckboxes('.student-checkbox');
}

// Deselect all students
function deselectAllStudents() {
    $('.student-checkbox').prop('checked', false);
}

// Select all batches
function selectAllBatches() {
    $('.batch-checkbox').prop('checked', true);
    animateCheckboxes('.batch-checkbox');
}

// Deselect all batches
function deselectAllBatches() {
    $('.batch-checkbox').prop('checked', false);
}

// Animate checkboxes when selected
function animateCheckboxes(selector) {
    $(selector).each(function(index) {
        const $checkbox = $(this);
        setTimeout(() => {
            $checkbox.parent().css('transform', 'scale(1.2)');
            setTimeout(() => {
                $checkbox.parent().css('transform', 'scale(1)');
            }, 200);
        }, index * 50);
    });
}

// Load participants
function loadParticipants() {
    const conversation_id = $('input[name="conversation_id"]').val();
    
    $.get('ajax_get_participants.php', { conversation_id: conversation_id }, function(data) {
        if (data.participants) {
            let html = '<div class="list-group list-group-flush">';
            data.participants.forEach(participant => {
                html += `
                    <div class="list-group-item d-flex align-items-center">
                        <div class="user-avatar me-3">
                            ${participant.initials}
                        </div>
                        <div>
                            <div class="fw-bold">${participant.name}</div>
                            <small class="text-muted">${participant.role}</small>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            $('#participantsList').html(html);
        }
    }, 'json');
}

// Mute notifications
function muteNotifications() {
    alert('Notifications muted for this conversation');
    $('#chatProfileModal').modal('hide');
}

// Confirm conversation deletion
function confirmDeleteConversation() {
    const conversation_id = $('input[name="conversation_id"]').val();
    const user_id = <?= $user_id ?>;
    const is_batch = <?= isset($is_batch) && $is_batch ? 'true' : 'false' ?>;
    
    // Check if user is admin of this conversation
    $.get('ajax_check_conversation_admin.php', { 
        conversation_id: conversation_id 
    }, function(data) {
        if (data.is_admin) {
            $('#deleteConversationWarning').removeClass('d-none');
        } else {
            $('#deleteConversationWarning').addClass('d-none');
        }
        
        const modal = new bootstrap.Modal(document.getElementById('deleteConversationModal'), {
            backdrop: 'static',
            keyboard: false
        });
        modal.show();
        
        $('#confirmDeleteBtn').off('click').on('click', function() {
            $(this).html('<i class="fas fa-spinner fa-spin me-1"></i> Deleting...');
            deleteConversation(conversation_id, user_id);
        });
    }, 'json');
}

// Delete conversation
function deleteConversation(conversation_id, user_id) {
    $.post('ajax_delete_conversation.php', {
        conversation_id: conversation_id,
        csrf_token: $('input[name="csrf_token"]').val()
    }, function(data) {
        if (data.success) {
            // Show success message before redirect
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConversationModal'));
            modal.hide();
            
            // Show success toast
            showToast('Conversation deleted successfully', 'success');
            
            // Redirect after a short delay
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1500);
        } else {
            $('#confirmDeleteBtn').html('<i class="fas fa-trash me-1"></i> Delete');
            showToast('Error: ' + data.error, 'danger');
        }
    }, 'json');
}

// Clear chat history
function confirmClearHistory() {
    if (confirm('Are you sure you want to clear all messages in this conversation? This cannot be undone.')) {
        const conversation_id = $('input[name="conversation_id"]').val();
        
        $.post('ajax_clear_history.php', {
            conversation_id: conversation_id,
            csrf_token: $('input[name="csrf_token"]').val()
        }, function(data) {
            if (data.success) {
                $('#chatMessages').html(`
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-comment-slash fa-3x mb-3"></i>
                        <h5>No messages yet</h5>
                        <p>Start the conversation by sending a message</p>
                    </div>
                `);
                showToast('Chat history cleared successfully', 'success');
                $('#chatProfileModal').modal('hide');
            } else {
                showToast('Error: ' + data.error, 'danger');
            }
        }, 'json');
    }
}

// Show toast notification
function showToast(message, type) {
    const toast = $(`
        <div class="toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `);
    
    $('body').append(toast);
    const bsToast = new bootstrap.Toast(toast[0]);
    bsToast.show();
    
    // Remove toast after it hides
    toast.on('hidden.bs.toast', function() {
        $(this).remove();
    });
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(10px); }
    }
    
    .toast {
        z-index: 1100;
    }
`;
document.head.appendChild(style);
</script>

<?php include 'footer.php'; ?>