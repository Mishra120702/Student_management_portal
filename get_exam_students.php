<?php
header('Content-Type: application/json');
require_once '../db_connection.php';

$exam_id = $_GET['exam_id'] ?? '';

try {
    $stmt = $db->prepare("SELECT * FROM exam_students WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($students);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>