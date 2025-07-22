<?php
require_once '../db_connection.php';
require_once 'chat_functions.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['conversation_id']) || !isset($_GET['last_message_id'])) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit();
}

$conversation_id = intval($_GET['conversation_id']);
$last_message_id = intval($_GET['last_message_id']);
$user_id = $_SESSION['user_id'];

// Verify user is participant in this conversation
$query = "SELECT 1 FROM chat_participants WHERE conversation_id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("ii", $conversation_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$messages = getNewMessages($conversation_id, $last_message_id);

// Mark messages as read for this user
markMessagesAsRead($conversation_id, $user_id);

echo json_encode([
    'messages' => $messages,
    'csrf_token' => $_SESSION['csrf_token'] // Return new CSRF token
]);
?>