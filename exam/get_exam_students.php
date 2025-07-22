<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php
");
    exit;
}
// Database connection
$db = new PDO('mysql:host=localhost;dbname=asd_academy1', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

header('Content-Type: application/json');

if (!isset($_GET['exam_id'])) {
    echo json_encode(['error' => 'Exam ID not provided']);
    exit;
}

$examId = $_GET['exam_id'];

try {
    // Get exam details first to know which batch we're dealing with
    $stmt = $db->prepare("SELECT batch_id FROM proctored_exams WHERE exam_id = ?");
    $stmt->execute([$examId]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exam) {
        echo json_encode(['error' => 'Exam not found']);
        exit;
    }
    
    $batchId = $exam['batch_id'];
    
    // Get all students in this batch with their exam results
    $query = "
        SELECT 
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            es.score,
            es.is_malpractice,
            es.notes
        FROM students s
        LEFT JOIN exam_students es ON es.student_name = CONCAT(s.first_name, ' ', s.last_name) AND es.exam_id = ?
        WHERE s.batch_name = ?
        ORDER BY s.last_name, s.first_name
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$examId, $batchId]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($students);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}