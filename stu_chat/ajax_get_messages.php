<?php
require_once '../db_connection.php';
require_once '../chat/chat_functions.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['conversation_id']) || !isset($_POST['last_message_id'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$conversation_id = intval($_POST['conversation_id']);
$last_message_id = intval($_POST['last_message_id']);
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
    echo json_encode(['error' => 'Unauthorized access to conversation']);
    exit;
}

// Get new messages since last_message_id
$query = $db->prepare("
    SELECT m.id, m.sender_id, m.message, m.sent_at, m.is_read, u.name as sender_name, c.conversation_type
    FROM chat_messages m
    JOIN users u ON m.sender_id = u.id
    JOIN chat_conversations c ON m.conversation_id = c.id
    WHERE m.conversation_id = ? 
    AND m.id > ?
    ORDER BY m.sent_at ASC
");
$query->execute([$conversation_id, $last_message_id]);
$messages = $query->fetchAll(PDO::FETCH_ASSOC);

// Mark new messages as read if they're not from the current user
foreach ($messages as &$msg) {
    if ($msg['sender_id'] != $user_id && !$msg['is_read']) {
        $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE id = ?")
          ->execute([$msg['id']]);
        $msg['is_read'] = 1;
    }
}

echo json_encode(['messages' => $messages]);