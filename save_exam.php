<?php
header('Content-Type: application/json');
require_once '../db_connection.php';

try {
    $data = $_POST;
    
    // Generate exam ID
    $exam_id = 'EXAM' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    $stmt = $db->prepare("INSERT INTO proctored_exams 
                         (exam_id, batch_id, exam_date, mode, duration, proctor_name) 
                         VALUES (?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $exam_id,
        $data['batch_id'],
        $data['exam_date'],
        $data['mode'],
        $data['duration'],
        $data['proctor_name']
    ]);
    
    echo json_encode(['success' => true, 'exam_id' => $exam_id]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>