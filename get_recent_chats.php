<?php
require_once '../db_connection.php';
header('Content-Type: application/json');

// session_start();
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

$userId = $_SESSION['user_id'];

try {
    // Get recent individual chats
    $stmt = $pdo->prepare("
        SELECT u.id AS user_id, 
               CONCAT(s.first_name, ' ', s.last_name) AS name,
               m.message_text AS last_message,
               m.timestamp AS last_message_time,
               SUM(CASE WHEN m.is_read = 0 AND m.receiver_id = :userId THEN 1 ELSE 0 END) AS unread_count
        FROM users u
        JOIN students s ON u.id = s.user_id
        LEFT JOIN messages m ON (
            (m.sender_id = u.id AND m.receiver_id = :userId) OR
            (m.sender_id = :userId AND m.receiver_id = u.id)
        WHERE u.role = 'student'
        GROUP BY u.id, s.first_name, s.last_name
        HAVING COUNT(m.message_id) > 0
        ORDER BY MAX(m.timestamp) DESC
        LIMIT 10
    ");
    $stmt->bindParam(':userId', $userId);
    $stmt->execute();
    $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($chats);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>