[file name]: create_conversation.php
[file content begin]
<?php
require_once '../db_connection.php';
require_once 'chat_functions.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

$admin_id = $_SESSION['user_id'];

if (isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id']);
    $conversation_id = getOrCreateStudentConversation($admin_id, $student_id);
    echo json_encode(['success' => true, 'conversation_id' => $conversation_id]);
} elseif (isset($_POST['batch_id'])) {
    $batch_id = intval($_POST['batch_id']);
    $conversation_id = getOrCreateBatchConversation($admin_id, $batch_id);
    echo json_encode(['success' => true, 'conversation_id' => $conversation_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
}
?>
[file content end]