<?php
require_once '../db_connection.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../unauthorized.php");
    exit;
}

// Handle both GET (view) and POST (submit) methods
$student_id = isset($_REQUEST['id']) ? trim($_REQUEST['id']) : null;

if (!$student_id) {
    $_SESSION['error'] = "Student ID not provided";
    header("Location: students_list.php");
    exit;
}

// Get student details for the form
$student = [];
try {
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        $_SESSION['error'] = "Student not found";
        header("Location: students_list.php");
        exit;
    }
    
    // Check if student is already dropped
    if ($student['current_status'] === 'dropped') {
        $_SESSION['error'] = "Student is already dropped";
        header("Location: students_list.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: students_list.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = isset($_POST['id']) ? trim($_POST['id']) : null;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    
    if (empty($student_id)) {
        $_SESSION['error'] = "Student ID not provided";
        header("Location: students_list.php");
        exit;
    }
    
    if (empty($reason)) {
        $_SESSION['error'] = "Please provide a reason for dropping the student";
        header("Location: drop_student.php?id=" . $student_id);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        // Update student status
        $stmt = $db->prepare("UPDATE students SET current_status = 'dropped', 
                             dropout_date = CURDATE(), dropout_reason = ?,
                             dropout_processed_by = ?, dropout_processed_at = NOW()
                             WHERE student_id = ?");
        $stmt->execute([$reason, $_SESSION['user_id'], $student_id]);
        
        // Verify the update worked
        $rowCount = $stmt->rowCount();
        if ($rowCount === 0) {
            throw new Exception("No rows were updated - student may not exist");
        }
        
        // Log the action
        $log_stmt = $db->prepare("INSERT INTO student_status_log 
                                 (student_id, action, reason, processed_by)
                                 VALUES (?, 'dropped', ?, ?)");
        $log_stmt->execute([$student_id, $reason, $_SESSION['user_id']]);
        
        $db->commit();
        
        $_SESSION['success'] = "Student successfully dropped";
        header("Location: drop_list.php");  // Changed to redirect to drop list
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Failed to drop student: " . $e->getMessage();
        header("Location: drop_student.php?id=" . $student_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drop Student - ASD Academy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Drop Student</h1>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="mb-6">
                <p class="mb-2"><strong>Student:</strong> <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></p>
                <p class="mb-4"><strong>ID:</strong> <?= htmlspecialchars($student['student_id']) ?></p>
                <p class="mb-4"><strong>Current Status:</strong> <?= htmlspecialchars(ucfirst($student['current_status'])) ?></p>
                
                <form method="POST">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($student_id) ?>">
                    <div class="mb-4">
                        <label for="reason" class="block text-gray-700 font-medium mb-2">Drop Reason *</label>
                        <textarea id="reason" name="reason" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <a href="students_list.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Cancel
                        </a>
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            Confirm Drop
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>