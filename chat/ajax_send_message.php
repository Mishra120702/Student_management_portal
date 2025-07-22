<?php
require_once '../db_connection.php';
require_once 'chat_functions.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['conversation_id']) || !isset($_POST['message'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

$conversation_id = intval($_POST['conversation_id']);
$message = trim($_POST['message']);
$user_id = $_SESSION['user_id'];

// Verify user is participant in this conversation
$query = "SELECT 1 FROM chat_participants WHERE conversation_id = ? AND user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$conversation_id, $user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    exit();
}

if (strlen($message) > 1000) {
    echo json_encode(['success' => false, 'error' => 'Message too long']);
    exit();
}

$success = sendMessage($conversation_id, $user_id, $message);

if ($success) {
    // Get the last inserted message ID
    $message_id = $db->lastInsertId();
    echo json_encode(['success' => true, 'message_id' => $message_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
}