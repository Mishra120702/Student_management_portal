<?php
header('Content-Type: application/json');
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

try {
    $exam_id = $_POST['exam_id'];
    
    // First delete existing marks for this exam
    $stmt = $db->prepare("DELETE FROM exam_students WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    
    // Insert new marks
    $stmt = $db->prepare("INSERT INTO exam_students 
                         (exam_id, student_name, score, is_malpractice, notes) 
                         VALUES (?, ?, ?, ?, ?)");
    
    for ($i = 0; $i < count($_POST['students']); $i++) {
        $stmt->execute([
            $exam_id,
            $_POST['students'][$i],
            $_POST['scores'][$i] ?: null,
            $_POST['malpractices'][$i],
            $_POST['notes'][$i] ?: null
        ]);
    }
    
    // Update malpractice count in proctored_exams
    $stmt = $db->prepare("UPDATE proctored_exams 
                         SET malpractice_cases = (
                             SELECT COUNT(*) FROM exam_students 
                             WHERE exam_id = ? AND is_malpractice = 1
                         ) 
                         WHERE exam_id = ?");
    $stmt->execute([$exam_id, $exam_id]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>