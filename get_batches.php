<?php
require_once '../db_connection.php';
header('Content-Type: application/json');

// session_start();
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

try {
    $stmt = $pdo->prepare("
        SELECT b.batch_id, b.course_name, b.time_slot, b.num_students,
               (SELECT COUNT(*) FROM messages m 
                WHERE (m.batch_id = b.batch_id) AND m.is_read = 0 AND m.receiver_id IS NULL
               ) AS unread_count
        FROM batches b
        WHERE b.status IN ('ongoing', 'upcoming')
        ORDER BY b.start_date DESC
    ");
    $stmt->execute();
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($batches);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>