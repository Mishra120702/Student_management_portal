<?php
require_once '../db_connection.php';
require_once '../chat/chat_functions.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['conversation_id']) || !isset($_GET['last_message_id'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$conversation_id = intval($_GET['conversation_id']);
$last_message_id = intval($_GET['last_message_id']);
$user_id = $_SESSION['user_id'];

// Get new messages since last_message_id
// Update the query in ajax_get_messages.php to include sender name
$query = $db->prepare("
    SELECT m.id, m.sender_id, m.message, m.sent_at, m.is_read, u.name as sender_name
    FROM chat_messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.conversation_id = ? 
    AND m.id > ?
    ORDER BY m.sent_at ASC
");
$query->execute([$conversation_id, $last_message_id]);
$messages = $query->fetchAll(PDO::FETCH_ASSOC);

// Mark new messages as read if they're not from the current user
foreach ($messages as $msg) {
    if ($msg['sender_id'] != $user_id && !$msg['is_read']) {
        $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE id = ?")
          ->execute([$msg['id']]);
        $msg['is_read'] = 1;
    }
}

echo json_encode(['messages' => $messages]);