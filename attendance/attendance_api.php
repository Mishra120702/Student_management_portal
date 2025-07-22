<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
// Database connection
$db = new PDO('mysql:host=localhost;dbname=asd_academy1', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'fetch':
            // Fetch attendance records
            $batch_id = $_GET['batch_id'] ?? '';
            $date = $_GET['date'] ?? date('Y-m-d');
            
            $query = "SELECT * FROM attendance WHERE date = ?";
            $params = [$date];
            
            if (!empty($batch_id)) {
                $query .= " AND batch_id = ?";
                $params[] = $batch_id;
            }
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['data' => $records]);
            break;
            
        case 'update':
            // Update attendance status
            $id = $_POST['id'];
            $status = $_POST['status'];
            $camera_status = $_POST['camera_status'] ?? 'On';
            $remarks = $_POST['remarks'] ?? null;
            
            $stmt = $db->prepare("UPDATE attendance SET status = ?, camera_status = ?, remarks = ? WHERE id = ?");
            $stmt->execute([$status, $camera_status, $remarks, $id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'mark_all_present':
            // Mark all students present for a batch/date
            $batch_id = $_POST['batch_id'];
            $date = $_POST['date'];
            
            $stmt = $db->prepare("UPDATE attendance SET status = 'Present', camera_status = 'On', remarks = NULL WHERE batch_id = ? AND date = ?");
            $stmt->execute([$batch_id, $date]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'monthly_summary':
            // Get monthly attendance summary
            $batch_id = $_GET['batch_id'] ?? '';
            $month = $_GET['month'] ?? '';
            
            if (empty($batch_id) || empty($month)) {
                echo json_encode(['success' => false, 'message' => 'Batch ID and month are required']);
                exit;
            }
            
            // Get month name for display
            $month_name = date('F Y', strtotime($month));
            
            // Get total distinct class dates in the month
            $stmt = $db->prepare("SELECT COUNT(DISTINCT date) as total_classes 
                                 FROM attendance 
                                 WHERE batch_id = :batch_id 
                                 AND DATE_FORMAT(date, '%Y-%m') = :month");
            $stmt->execute([':batch_id' => $batch_id, ':month' => $month]);
            $total_classes = $stmt->fetch(PDO::FETCH_ASSOC)['total_classes'];
            
            // Get all students in the batch
            $stmt = $db->prepare("SELECT DISTINCT student_name FROM attendance WHERE batch_id = :batch_id");
            $stmt->execute([':batch_id' => $batch_id]);
            $students = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Initialize response array
            $response = [
                'success' => true,
                'month_name' => $month_name,
                'total_classes' => $total_classes,
                'total_present' => 0,
                'total_absent' => 0,
                'total_late' => 0,
                'students' => []
            ];
            
            if ($total_classes > 0) {
                // Get attendance for each student
                foreach ($students as $student) {
                    $stmt = $db->prepare("SELECT 
                                            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
                                            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
                                            SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count
                                          FROM attendance
                                          WHERE batch_id = :batch_id
                                          AND student_name = :student_name
                                          AND DATE_FORMAT(date, '%Y-%m') = :month");
                    $stmt->execute([
                        ':batch_id' => $batch_id,
                        ':student_name' => $student,
                        ':month' => $month
                    ]);
                    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Calculate attendance percentage
                    $total_days = $attendance['present_count'] + $attendance['absent_count'] + $attendance['late_count'];
                    $attendance_percentage = $total_days > 0 ? 
                        round(($attendance['present_count'] + $attendance['late_count']) * 100 / $total_days, 2) : 0;
                    
                    // Add to response
                    $response['students'][] = [
                        'student_name' => $student,
                        'batch_id' => $batch_id,
                        'month' => $month,
                        'present_count' => $attendance['present_count'],
                        'absent_count' => $attendance['absent_count'],
                        'late_count' => $attendance['late_count'],
                        'attendance_percentage' => $attendance_percentage
                    ];
                    
                    // Update totals
                    $response['total_present'] += $attendance['present_count'];
                    $response['total_absent'] += $attendance['absent_count'];
                    $response['total_late'] += $attendance['late_count'];
                }
                
                // Calculate overall attendance percentage
                $total_records = $response['total_present'] + $response['total_absent'] + $response['total_late'];
                $response['attendance_percentage'] = $total_records > 0 ? 
                    round(($response['total_present'] + $response['total_late']) * 100 / $total_records, 2) : 0;
            }
            
            echo json_encode($response);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>