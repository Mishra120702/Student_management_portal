<?php
// Database connection
$db = new PDO('mysql:host=localhost;dbname=asd_academy1', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

header('Content-Type: application/json');

// Get POST data
$batchId = $_POST['batch_id'] ?? null;
$examDate = $_POST['exam_date'] ?? null;
$duration = $_POST['duration'] ?? null;
$mode = $_POST['mode'] ?? null;
$proctorName = $_POST['proctor_name'] ?? null;

// Validate input
if (!$batchId || !$examDate || !$duration || !$mode) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Generate exam ID
    $examId = 'EXAM' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Insert exam
    $stmt = $db->prepare("
        INSERT INTO proctored_exams 
        (exam_id, batch_id, exam_date, mode, duration, proctor_name) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $examId,
        $batchId,
        $examDate,
        $mode,
        $duration,
        $proctorName
    ]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}