<?php
require_once '../db_connection.php';
header('Content-Type: application/json');

// session_start();
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$type = $data['type'] ?? null;
$id = $data['id'] ?? null;
$isTyping = $data['is_typing'] ?? false;

if (!$type || !$id) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

try {
    $receiverId = null;
    $batchId = null;
    
    if ($type === 'student') {
        $receiverId = $id;
    } elseif ($type === 'batch') {
        $batchId = $id;
    }
    
    // Check if typing indicator already exists
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM typing_indicators 
        WHERE user_id = :user_id AND receiver_id = :receiver_id AND batch_id = :batch_id
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':receiver_id', $receiverId);
    $stmt->bindParam(':batch_id', $batchId);
    $stmt->execute();
    $exists = $stmt->fetchColumn();
    
    if ($exists) {
        // Update existing record
        $stmt = $pdo->prepare("
            UPDATE typing_indicators 
            SET is_typing = :is_typing, last_updated = NOW()
            WHERE user_id = :user_id AND receiver_id = :receiver_id AND batch_id = :batch_id
        ");
    } else {
        // Insert new record
        $stmt = $pdo->prepare("
            INSERT INTO typing_indicators 
            (user_id, receiver_id, batch_id, is_typing, last_updated)
            VALUES (:user_id, :receiver_id, :batch_id, :is_typing, NOW())
        ");
    }
    
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':receiver_id', $receiverId);
    $stmt->bindParam(':batch_id', $batchId);
    $stmt->bindParam(':is_typing', $isTyping, PDO::PARAM_BOOL);
    
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>