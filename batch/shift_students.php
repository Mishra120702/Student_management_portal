<?php
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Set header first to ensure JSON response
header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST; // Fallback to regular POST data
    }

    $current_batch = $input['current_batch'] ?? null;
    $target_batch = $input['target_batch'] ?? null;
    $students = $input['students'] ?? [];
    
    if (!$current_batch || !$target_batch || empty($students)) {
        throw new Exception("Invalid input data");
    }
    
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get batch status to determine if it's completed
    $batchStmt = $conn->prepare("SELECT status FROM batches WHERE batch_id = ?");
    $batchStmt->execute([$current_batch]);
    $batchStatus = $batchStmt->fetchColumn();
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Prepare statements
    $updateStudentStmt = $conn->prepare("UPDATE students SET batch_name = ? WHERE student_id = ?");
    $insertHistoryStmt = $conn->prepare("INSERT INTO student_batch_history 
                                        (student_id, from_batch_id, to_batch_id, transferred_by) 
                                        VALUES (?, ?, ?, ?)");
    
    // Get admin ID (in a real app, this would come from session)
    $admin_id = 1;
    
    $success_count = 0;
    $errors = [];
    
    foreach ($students as $student_id) {
        try {
            // Only update the batch_name if the current batch is not completed
            if ($batchStatus !== 'completed') {
                $updateStudentStmt->execute([$target_batch, $student_id]);
            }
            
            // Record in history regardless of batch status
            $insertHistoryStmt->execute([
                $student_id,
                $current_batch,
                $target_batch,
                $admin_id
            ]);
            
            $success_count++;
        } catch (PDOException $e) {
            $errors[] = "Error transferring student $student_id: " . $e->getMessage();
        }
    }
    
    if ($success_count > 0) {
        // Update batch enrollment counts only if not completed batch
        if ($batchStatus !== 'completed') {
            $conn->exec("UPDATE batches SET current_enrollment = current_enrollment - $success_count 
                        WHERE batch_id = '$current_batch'");
            $conn->exec("UPDATE batches SET current_enrollment = current_enrollment + $success_count 
                        WHERE batch_id = '$target_batch'");
        }
        
        // Commit transaction
        $conn->commit();
        
        $response = [
            'success' => true,
            'message' => "Successfully transferred $success_count student(s) to batch $target_batch."
        ];
    } else {
        $conn->rollBack();
        $response['message'] = "Failed to transfer any students. Errors: " . implode(", ", $errors);
    }
    
} catch (Exception $e) {
    // Rollback on error if transaction was started
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $response['message'] = "Error: " . $e->getMessage();
}

// Ensure we only output JSON
echo json_encode($response);
exit();
?>