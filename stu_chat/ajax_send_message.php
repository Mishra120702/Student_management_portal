<?php
require_once '../db_connection.php';
require_once '../chat/chat_functions.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['conversation_id']) || !isset($_POST['message']) || !isset($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

// Validate CSRF token
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$conversation_id = intval($_POST['conversation_id']);
$message = trim($_POST['message']);
$user_id = $_SESSION['user_id'];

// Verify user has access to this conversation
$query = $db->prepare("
    SELECT c.id 
    FROM chat_conversations c
    WHERE c.id = ? 
    AND (
        (c.conversation_type = 'admin_student' AND c.student_id = (SELECT student_id FROM students WHERE user_id = ?))
        OR 
        (c.conversation_type = 'admin_batch' AND c.batch_id = (SELECT batch_name FROM students WHERE user_id = ?))
    )
");
$query->execute([$conversation_id, $user_id, $user_id]);
$conversation = $query->fetch(PDO::FETCH_ASSOC);

if (!$conversation) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access to conversation']);
    exit;
}

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    exit;
}

$message_id = sendMessage($conversation_id, $user_id, $message);

if ($message_id) {
    echo json_encode([
        'success' => true,
        'message_id' => $message_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to send message'
    ]);
}