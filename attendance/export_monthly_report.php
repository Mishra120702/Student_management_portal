<?php
include '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
// Get parameters
$batch_id = $_GET['batch_id'] ?? '';
$month = $_GET['month'] ?? '';

if (empty($batch_id) || empty($month)) {
    die('Batch ID and month are required');
}

// Get month name
$month_name = date('F Y', strtotime($month));

// Get report data
$stmt = $db->prepare("SELECT 
                        student_name,
                        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
                        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count
                      FROM attendance
                      WHERE batch_id = :batch_id
                      AND DATE_FORMAT(date, '%Y-%m') = :month
                      GROUP BY student_name
                      ORDER BY student_name");
$stmt->execute([':batch_id' => $batch_id, ':month' => $month]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total classes
$stmt = $db->prepare("SELECT COUNT(DISTINCT date) as total_classes 
                      FROM attendance 
                      WHERE batch_id = :batch_id 
                      AND DATE_FORMAT(date, '%Y-%m') = :month");
$stmt->execute([':batch_id' => $batch_id, ':month' => $month]);
$total_classes = $stmt->fetch(PDO::FETCH_ASSOC)['total_classes'];

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="attendance_report_' . $batch_id . '_' . $month . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, ['Monthly Attendance Report - ' . $month_name]);
fputcsv($output, ['Batch ID', $batch_id]);
fputcsv($output, ['Total Classes', $total_classes]);
fputcsv($output, []);
fputcsv($output, ['Student Name', 'Present', 'Absent', 'Late', 'Attendance Percentage']);

// Write data rows
foreach ($students as $student) {
    $total = $student['present_count'] + $student['absent_count'] + $student['late_count'];
    $percentage = $total > 0 ? round(($student['present_count'] + $student['late_count']) * 100 / $total, 2) : 0;
    
    fputcsv($output, [
        $student['student_name'],
        $student['present_count'],
        $student['absent_count'],
        $student['late_count'],
        $percentage . '%'
    ]);
}

fclose($output);