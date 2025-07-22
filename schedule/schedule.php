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
    
    // Get schedule for this batch
    $stmt = $conn->prepare("SELECT * FROM schedule WHERE batch_id = ? ORDER BY schedule_date, start_time");
    $stmt->execute([$batch_id]);
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get upcoming classes (next 7 days)
    $upcoming_start = date('Y-m-d');
    $upcoming_end = date('Y-m-d', strtotime('+7 days'));
    
    $stmt = $conn->prepare("SELECT * FROM schedule WHERE batch_id = ? AND schedule_date BETWEEN ? AND ? ORDER BY schedule_date, start_time");
    $stmt->execute([$batch_id, $upcoming_start, $upcoming_end]);
    $upcoming_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle delete request
    if (isset($_GET['delete_id'])) {
        $delete_id = $_GET['delete_id'];
        $stmt = $conn->prepare("DELETE FROM schedule WHERE id = ? AND batch_id = ?");
        $stmt->execute([$delete_id, $batch_id]);
        header("Location: schedule.php?batch_id=" . $batch_id);
        exit();
    }
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Function to generate calendar days
function generateCalendarDays($year, $month, $batch_id, $conn) {
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $firstDayOfMonth = date('N', strtotime("$year-$month-01"));
    $calendarDays = [];
    
    // Add empty cells for days before the first day of the month
    for ($i = 1; $i < $firstDayOfMonth; $i++) {
        $calendarDays[] = ['day' => '', 'classes' => []];
    }
    
    // Add days of the month
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
        $classes = [];
        
        // Get classes for this day
        $stmt = $conn->prepare("SELECT * FROM schedule WHERE batch_id = ? AND schedule_date = ? ORDER BY start_time");
        $stmt->execute([$batch_id, $date]);
        $dayClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($dayClasses as $class) {
            $classes[] = [
                'id' => $class['id'],
                'time' => date('g:i A', strtotime($class['start_time'])) . ' - ' . date('g:i A', strtotime($class['end_time'])),
                'topic' => $class['topic'],
                'is_cancelled' => $class['is_cancelled']
            ];
        }
        
        $calendarDays[] = [
            'day' => $day,
            'date' => $date,
            'classes' => $classes,
            'isToday' => $date === date('Y-m-d')
        ];
    }
    
    return $calendarDays;
}

$currentYear = date('Y');
$currentMonth = date('m');
$calendarDays = generateCalendarDays($currentYear, $currentMonth, $batch_id, $conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule | <?= htmlspecialchars($batch['batch_id']) ?> | ASD Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php
    include '../header.php';
    include '../sidebar.php';
    ?>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Back button -->
            <a href="../batch/batch_view.php?batch_id=<?= $batch_id ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4">
                <i class="fas fa-arrow-left mr-2"></i> Back to Batch
            </a>
            
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Schedule - <?= htmlspecialchars($batch['batch_id']) ?></h1>
                <div class="flex space-x-2">
                    <a href="add_schedule.php?batch_id=<?= $batch_id ?>" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Add Class
                    </a>
                </div>
            </div>
            
            <!-- Upcoming Classes -->
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-800 mb-4">Upcoming Classes (Next 7 Days)</h2>
                
                <?php if (count($upcoming_classes) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($upcoming_classes as $class): ?>
                            <div class="border-l-4 <?= $class['is_cancelled'] ? 'border-red-500' : 'border-blue-500' ?> pl-4 py-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-medium text-gray-900"><?= htmlspecialchars($class['topic'] ?? 'Class') ?></h3>
                                        <p class="text-sm text-gray-600">
                                            <?= date('D, M j', strtotime($class['schedule_date'])) ?> 
                                            • <?= date('g:i A', strtotime($class['start_time'])) ?> - <?= date('g:i A', strtotime($class['end_time'])) ?>
                                            <?php if ($class['is_cancelled']): ?>
                                                <span class="text-red-500 ml-2">(Cancelled)</span>
                                            <?php endif; ?>
                                        </p>
                                        <?php if (!empty($class['description'])): ?>
                                            <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($class['description']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($class['cancellation_reason'])): ?>
                                            <p class="text-sm text-red-500 mt-1">Reason: <?= htmlspecialchars($class['cancellation_reason']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="edit_schedule.php?id=<?= $class['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="schedule.php?batch_id=<?= $batch_id ?>&delete_id=<?= $class['id'] ?>" class="text-red-600 hover:text-red-900" title="Delete" onclick="return confirm('Are you sure you want to delete this class?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-calendar-times text-gray-400 text-4xl mb-2"></i>
                        <p class="text-gray-600">No upcoming classes scheduled</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Calendar View -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-medium text-gray-800"><?= date('F Y') ?></h2>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Today
                        </button>
                        <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-7 gap-px bg-gray-200 border border-gray-200">
                    <!-- Weekday headers -->
                    <?php 
                    $weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    foreach ($weekdays as $day): 
                    ?>
                        <div class="bg-gray-100 py-2 text-center text-xs font-medium text-gray-500">
                            <?= $day ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Calendar days -->
                    <?php foreach ($calendarDays as $day): ?>
                        <div class="bg-white p-2 h-32 overflow-y-auto ">
                            <?php if ($day['day'] !== ''): ?>
                                <div class="text-right font-medium mb-1"><?= $day['day'] ?></div>
                                
                                <?php if (!empty($day['classes'])): ?>
                                    <div class="space-y-1">
                                        <?php foreach ($day['classes'] as $class): ?>
                                            <div class="text-xs p-1 <?= $class['is_cancelled'] ? 'bg-red-100' : 'bg-blue-100' ?> rounded truncate" title="<?= htmlspecialchars($class['topic']) ?>">
                                                <a href="edit_schedule.php?id=<?= $class['id'] ?>" class="block">
                                                    <?= htmlspecialchars($class['time']) ?><br>
                                                    <?= htmlspecialchars($class['topic']) ?>
                                                    <?php if ($class['is_cancelled']): ?>
                                                        <span class="text-red-500">(Cancelled)</span>
                                                    <?php endif; ?>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>