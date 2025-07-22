<?php
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$class_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$class_id) {
    header("Location: ../batch_list.php");
    exit();
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get class details
    $stmt = $conn->prepare("SELECT s.*, b.batch_id, b.course_name FROM schedule s 
                           JOIN batches b ON s.batch_id = b.batch_id 
                           WHERE s.id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$class) {
        header("Location: ../batch_list.php");
        exit();
    }
    
    $batch_id = $class['batch_id'];
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $schedule_date = $_POST['schedule_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $topic = $_POST['topic'];
        $description = $_POST['description'];
        $is_cancelled = isset($_POST['is_cancelled']) ? 1 : 0;
        $cancellation_reason = $_POST['cancellation_reason'];
        
        $stmt = $conn->prepare("UPDATE schedule 
                               SET schedule_date = ?, start_time = ?, end_time = ?, topic = ?, 
                                   description = ?, is_cancelled = ?, cancellation_reason = ?
                               WHERE id = ?");
        $stmt->execute([$schedule_date, $start_time, $end_time, $topic, $description, 
                       $is_cancelled, $cancellation_reason, $class_id]);
        
        header("Location: schedule.php?batch_id=" . $batch_id);
        exit();
    }
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule | <?= htmlspecialchars($class['batch_id']) ?> | ASD Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Back button -->
            <a href="schedule.php?batch_id=<?= $batch_id ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4">
                <i class="fas fa-arrow-left mr-2"></i> Back to Schedule
            </a>
            
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Edit Class - <?= htmlspecialchars($class['batch_id']) ?></h1>
                <p class="text-sm text-gray-600"><?= htmlspecialchars($class['course_name']) ?></p>
            </div>
            
            <!-- Form -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <form action="edit_schedule.php?id=<?= $class_id ?>" method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="schedule_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input type="date" id="schedule_date" name="schedule_date" required
                                   value="<?= htmlspecialchars($class['schedule_date']) ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                            <input type="time" id="start_time" name="start_time" required
                                   value="<?= htmlspecialchars(substr($class['start_time'], 0, 5)) ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                            <input type="time" id="end_time" name="end_time" required
                                   value="<?= htmlspecialchars(substr($class['end_time'], 0, 5)) ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="topic" class="block text-sm font-medium text-gray-700 mb-1">Topic</label>
                            <input type="text" id="topic" name="topic" required
                                   value="<?= htmlspecialchars($class['topic']) ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($class['description']) ?></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <div class="flex items-center">
                                <input type="checkbox" id="is_cancelled" name="is_cancelled" 
                                       <?= $class['is_cancelled'] ? 'checked' : '' ?>
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="is_cancelled" class="ml-2 block text-sm text-gray-700">Cancel this class</label>
                            </div>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-1">Cancellation Reason (if applicable)</label>
                            <textarea id="cancellation_reason" name="cancellation_reason" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($class['cancellation_reason']) ?></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-between">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                        <a href="schedule.php?batch_id=<?= $batch_id ?>&delete_id=<?= $class_id ?>" 
                           class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700"
                           onclick="return confirm('Are you sure you want to delete this class?')">
                            <i class="fas fa-trash mr-2"></i> Delete Class
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>