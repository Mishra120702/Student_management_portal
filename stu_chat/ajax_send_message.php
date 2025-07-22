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