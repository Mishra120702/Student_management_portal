<?php
require_once '../db_connection  .php';
header('Content-Type: application/json');

// session_start();
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

$userId = $_SESSION['user_id'];
$type = $_GET['type'] ?? null;
$id = $_GET['id'] ?? null;

if (!$type || !$id) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

try {
    // Mark messages as read when fetching
    if ($type === 'student') {
        $stmt = $pdo->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE sender_id = :id AND receiver_id = :userId AND is_read = 0
        ");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();
    } elseif ($type === 'batch') {
        $stmt = $pdo->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE batch_id = :id AND receiver_id IS NULL AND is_read = 0
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }
    
    // Fetch messages
    if ($type === 'student') {
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   CONCAT(u.name, 
                          CASE WHEN u.id = :userId THEN ' (You)' ELSE '' END) AS sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = :userId AND m.receiver_id = :id) OR
                  (m.sender_id = :id AND m.receiver_id = :userId)
            ORDER BY m.timestamp ASC
        ");
        $stmt->bindParam(':userId', $userId);
        $stmt->bindParam(':id', $id);
    } elseif ($type === 'batch') {
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   CONCAT(u.name, 
                          CASE WHEN u.id = :userId THEN ' (You)' ELSE '' END) AS sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.batch_id = :id AND m.receiver_id IS NULL
            ORDER BY m.timestamp ASC
        ");
        $stmt->bindParam(':userId', $userId);
        $stmt->bindParam(':id', $id);
    } elseif ($type === 'general') {
        $stmt = $pdo->prepare("
            SELECT m.*, u.name AS sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.batch_id IS NULL AND m.receiver_id IS NULL
            ORDER BY m.timestamp ASC
        ");
    }
    
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($messages);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>