<?php
require_once '../db_connection.php';
header('Content-Type: application/json');

// session_start();
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

// $userId = $_SESSION['user_id'];
// $type = $_GET['type'] ?? null;
// $id = $_GET['id'] ?? null;

// if (!$type || !$id) {
//     echo json_encode(['error' => 'Invalid parameters']);
//     exit;}

try {
    $receiverId = null;
    $batchId = null;
    
    if ($type === 'student') {
        $receiverId = $userId; // We want to know if the other person is typing to us
        $senderId = $id;
    } elseif ($type === 'batch') {
        $batchId = $id;
    }
    
    if ($type === 'student') {
        $stmt = $pdo->prepare("
            SELECT u.name, ti.is_typing
            FROM typing_indicators ti
            JOIN users u ON ti.user_id = u.id
            WHERE ti.user_id = :sender_id AND ti.receiver_id = :receiver_id
            AND ti.is_typing = 1
            AND ti.last_updated > DATE_SUB(NOW(), INTERVAL 5 SECOND)
        ");
        $stmt->bindParam(':sender_id', $senderId);
        $stmt->bindParam(':receiver_id', $receiverId);
    } elseif ($type === 'batch') {
        $stmt = $pdo->prepare("
            SELECT u.name, ti.is_typing
            FROM typing_indicators ti
            JOIN users u ON ti.user_id = u.id
            WHERE ti.batch_id = :batch_id
            AND ti.user_id != :user_id
            AND ti.is_typing = 1
            AND ti.last_updated > DATE_SUB(NOW(), INTERVAL 5 SECOND)
        ");
        $stmt->bindParam(':batch_id', $batchId);
        $stmt->bindParam(':user_id', $userId);
    }
    
    $stmt->execute();
    $typingUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['typing_users' => $typingUsers]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>