<?php
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$batch_id = isset($_GET['batch_id']) ? $_GET['batch_id'] : null;

if (!$batch_id) {
    header("Location: ../batch_list.php");
    exit();
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get batch details
    $stmt = $conn->prepare("SELECT * FROM batches WHERE batch_id = ?");
    $stmt->execute([$batch_id]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$batch) {
        header("Location: ../batch_list.php");
        exit();
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $schedule_date = $_POST['schedule_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $topic = $_POST['topic'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("INSERT INTO schedule (batch_id, schedule_date, start_time, end_time, topic, description, created_by) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$batch_id, $schedule_date, $start_time, $end_time, $topic, $description, 1]); // Assuming admin ID is 1
        
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
    <title>Add Schedule | <?= htmlspecialchars($batch['batch_id']) ?> | ASD Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php
    include '../header.php';
    include '../sidebar.php';   
    ?>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Back button -->
            <a href="schedule.php?batch_id=<?= $batch_id ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4">
                <i class="fas fa-arrow-left mr-2"></i> Back to Schedule
            </a>
            
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Add New Class - <?= htmlspecialchars($batch['batch_id']) ?></h1>
            </div>
            
            <!-- Form -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <form action="add_schedule.php?batch_id=<?= $batch_id ?>" method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="schedule_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input type="date" id="schedule_date" name="schedule_date" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                            <input type="time" id="start_time" name="start_time" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                            <input type="time" id="end_time" name="end_time" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="topic" class="block text-sm font-medium text-gray-700 mb-1">Topic</label>
                            <input type="text" id="topic" name="topic" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                            <textarea id="description" name="description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                            <i class="fas fa-save mr-2"></i> Save Class
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Set default time to batch time slot
        document.addEventListener('DOMContentLoaded', function() {
            const timeSlot = "<?= $batch['time_slot'] ?>";
            if (timeSlot) {
                const [startTime, endTime] = timeSlot.split('-');
                document.getElementById('start_time').value = startTime.trim();
                document.getElementById('end_time').value = endTime.trim();
            }
            
            // Set default date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('schedule_date').value = today;
            document.getElementById('schedule_date').min = today;
        });
    </script>
</body>
</html>