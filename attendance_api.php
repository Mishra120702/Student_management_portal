<?php
header('Content-Type: application/json');

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
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>