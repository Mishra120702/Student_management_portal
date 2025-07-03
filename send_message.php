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
$messageText = $data['message'] ?? null;
$messageType = $data['message_type'] ?? 'text';
$filePath = $data['file_path'] ?? null;
$latitude = $data['latitude'] ?? null;
$longitude = $data['longitude'] ?? null;

if (!$type || !$id) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $receiverId = null;
    $batchId = null;
    
    if ($type === 'student') {
        $receiverId = $id;
    } elseif ($type === 'batch') {
        $batchId = $id;
    } elseif ($type === 'general') {
        // General announcements - no specific receiver or batch
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO messages 
        (sender_id, receiver_id, batch_id, message_text, message_type, file_path, latitude, longitude, timestamp)
        VALUES (:sender_id, :receiver_id, :batch_id, :message_text, :message_type, :file_path, :latitude, :longitude, NOW())
    ");
    
    $stmt->bindParam(':sender_id', $userId);
    $stmt->bindParam(':receiver_id', $receiverId);
    $stmt->bindParam(':batch_id', $batchId);
    $stmt->bindParam(':message_text', $messageText);
    $stmt->bindParam(':message_type', $messageType);
    $stmt->bindParam(':file_path', $filePath);
    $stmt->bindParam(':latitude', $latitude);
    $stmt->bindParam(':longitude', $longitude);
    
    $stmt->execute();
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}
?>