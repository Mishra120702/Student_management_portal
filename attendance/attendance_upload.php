<?php
require '../vendor/autoload.php'; // PhpSpreadsheet autoloader
include '../db_connection.php'; // Database connection (should be PDO)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
use PhpOffice\PhpSpreadsheet\IOFactory;

session_start();

if (isset($_POST['import'])) {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['excel_file']['tmp_name'];
        $spreadsheet = IOFactory::load($fileTmpPath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        $skipped = [];
        $successCount = 0;

        // Start from row 1 (assuming first row is headers)
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];

            // Validate row has enough columns
            if (count($row) < 6) {
                $skipped[] = "Row " . ($i + 1) . ": Insufficient data columns";
                continue;
            }

            $date = date('Y-m-d', strtotime($row[0]));
            $batch_id = $row[1];
            $student_name = $row[2];
            $status = $row[3];
            $camera_status = isset($row[4]) ? $row[4] : 'Off';
            $remarks = isset($row[5]) ? $row[5] : null;

            // Validate required fields
            if (empty($date) || empty($batch_id) || empty($student_name) || empty($status)) {
                $skipped[] = "Row " . ($i + 1) . ": Missing required fields";
                continue;
            }

            // Validate status
            if (!in_array($status, ['Present', 'Absent', 'Late'])) {
                $skipped[] = "Row " . ($i + 1) . ": Invalid status value";
                continue;
            }

            // Validate camera status
            if (!in_array($camera_status, ['On', 'Off'])) {
                $camera_status = 'Off';
            }

            try {
                // Check if batch exists (PDO version)
                $check_batch = $db->prepare("SELECT batch_id FROM batches WHERE batch_id = :batch_id");
                $check_batch->bindParam(':batch_id', $batch_id);
                $check_batch->execute();

                if ($check_batch->rowCount() === 0) {
                    $skipped[] = "Row " . ($i + 1) . ": Batch ID $batch_id not found";
                    continue;
                }

                // Insert into attendance table (PDO version)
                $stmt = $db->prepare("INSERT INTO attendance (date, batch_id, student_name, status, camera_status, remarks) 
                                      VALUES (:date, :batch_id, :student_name, :status, :camera_status, :remarks)");
                
                $stmt->bindParam(':date', $date);
                $stmt->bindParam(':batch_id', $batch_id);
                $stmt->bindParam(':student_name', $student_name);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':camera_status', $camera_status);
                $stmt->bindParam(':remarks', $remarks);
                
                if ($stmt->execute()) {
                    $successCount++;
                } else {
                    $skipped[] = "Row " . ($i + 1) . ": " . implode(" ", $stmt->errorInfo());
                }
            } catch (PDOException $e) {
                $skipped[] = "Row " . ($i + 1) . ": " . $e->getMessage();
            }
        }

        // Prepare result message
        $message = "Attendance data imported successfully. $successCount records added.";
        if (!empty($skipped)) {
            $message .= " Skipped rows: " . implode(', ', $skipped);
        }

        $_SESSION['import_message'] = $message;
        header("Location: ../dashboard/dashboard.php#attendance");
        exit;
    } else {
        $_SESSION['import_message'] = "Error uploading file.";
        header("Location: ../dashboard/dashboard.php#attendance");
        exit;
    }
} else {
    $_SESSION['import_message'] = "Invalid request.";
    header("Location: ../dashboard/dashboard.php#attendance");
    exit;
}
?>