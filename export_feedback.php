<?php
require 'vendor/autoload.php'; // Require Composer's autoloader if using libraries

// Database connection
$db = new PDO('mysql:host=localhost;dbname=asd_academy1', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get filter if any
$batch_filter = $_GET['batch'] ?? '';

// Get feedback data
$query = "SELECT * FROM feedback";
$params = [];
if (!empty($batch_filter)) {
    $query .= " WHERE batch_id = ?";
    $params[] = $batch_filter;
}
$stmt = $db->prepare($query);
$stmt->execute($params);
$feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine export type
$export_type = $_GET['export'] ?? 'excel';

switch ($export_type) {
    case 'excel':
        exportExcel($feedback);
        break;
    case 'csv':
        exportCSV($feedback);
        break;
    case 'pdf':
        exportPDF($feedback);
        break;
    default:
        header("Location: feedback.php");
        exit();
}

function exportExcel($data) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="feedback_export_' . date('Y-m-d') . '.xls"');
    
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>Date</th>';
    echo '<th>Student Name</th>';
    echo '<th>Email</th>';
    echo '<th>Regular</th>';
    echo '<th>Batch</th>';
    echo '<th>Course</th>';
    echo '<th>Class Rating</th>';
    echo '<th>Assignment</th>';
    echo '<th>Practical</th>';
    echo '<th>Satisfied</th>';
    echo '<th>Suggestions</th>';
    echo '<th>Feedback</th>';
    echo '<th>Action Taken</th>';
    echo '</tr>';
    
    foreach ($data as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['date']) . '</td>';
        echo '<td>' . htmlspecialchars($row['student_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
        echo '<td>' . htmlspecialchars($row['is_regular']) . '</td>';
        echo '<td>' . htmlspecialchars($row['batch_id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['course_name']) . '</td>';
        echo '<td>' . $row['class_rating'] . '/5</td>';
        echo '<td>' . $row['assignment_understanding'] . '/5</td>';
        echo '<td>' . $row['practical_understanding'] . '/5</td>';
        echo '<td>' . htmlspecialchars($row['satisfied']) . '</td>';
        echo '<td>' . htmlspecialchars($row['suggestions']) . '</td>';
        echo '<td>' . htmlspecialchars($row['feedback_text']) . '</td>';
        echo '<td>' . htmlspecialchars($row['action_taken'] ?? '') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    exit();
}

function exportCSV($data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="feedback_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, [
        'Date', 'Student Name', 'Email', 'Regular', 'Batch', 'Course', 
        'Class Rating', 'Assignment', 'Practical', 'Satisfied', 
        'Suggestions', 'Feedback', 'Action Taken'
    ]);
    
    // Data rows
    foreach ($data as $row) {
        fputcsv($output, [
            $row['date'],
            $row['student_name'],
            $row['email'],
            $row['is_regular'],
            $row['batch_id'],
            $row['course_name'],
            $row['class_rating'] . '/5',
            $row['assignment_understanding'] . '/5',
            $row['practical_understanding'] . '/5',
            $row['satisfied'],
            $row['suggestions'],
            $row['feedback_text'],
            $row['action_taken'] ?? ''
        ]);
    }
    
    fclose($output);
    exit();
}

function exportPDF($data) {
    // Require TCPDF library (install via composer: composer require tecnickcom/tcpdf)
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('ASD Academy');
    $pdf->SetTitle('Feedback Report');
    $pdf->SetSubject('Feedback Data');
    $pdf->SetKeywords('Feedback, Report, ASD Academy');
    
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Feedback Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    
    // Add date and filter info
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1);
    if (!empty($_GET['batch'])) {
        $pdf->Cell(0, 10, 'Filter: Batch ' . htmlspecialchars($_GET['batch']), 0, 1);
    }
    
    // Table header
    $pdf->SetFont('helvetica', 'B', 8);
    $header = ['Date', 'Student', 'Regular', 'Batch', 'Course', 'Class', 'Assign', 'Pract', 'Satis', 'Action'];
    $w = [20, 30, 15, 15, 30, 10, 10, 10, 10, 30];
    
    for ($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C');
    }
    $pdf->Ln();
    
    // Table data
    $pdf->SetFont('helvetica', '', 8);
    foreach ($data as $row) {
        $pdf->Cell($w[0], 6, $row['date'], 'LR', 0, 'C');
        $pdf->Cell($w[1], 6, substr($row['student_name'], 0, 20), 'LR', 0, 'L');
        $pdf->Cell($w[2], 6, $row['is_regular'], 'LR', 0, 'C');
        $pdf->Cell($w[3], 6, $row['batch_id'], 'LR', 0, 'C');
        $pdf->Cell($w[4], 6, substr($row['course_name'], 0, 20), 'LR', 0, 'L');
        $pdf->Cell($w[5], 6, $row['class_rating'], 'LR', 0, 'C');
        $pdf->Cell($w[6], 6, $row['assignment_understanding'], 'LR', 0, 'C');
        $pdf->Cell($w[7], 6, $row['practical_understanding'], 'LR', 0, 'C');
        $pdf->Cell($w[8], 6, $row['satisfied'], 'LR', 0, 'C');
        $pdf->Cell($w[9], 6, substr($row['action_taken'] ?? '', 0, 20), 'LR', 0, 'L');
        $pdf->Ln();
    }
    
    $pdf->Output('feedback_export_' . date('Y-m-d') . '.pdf', 'D');
    exit();
}