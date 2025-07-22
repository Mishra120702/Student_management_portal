<?php
session_start();
require_once '../db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_query = $db->prepare("
    SELECT s.*, b.batch_id
    FROM students s
    JOIN batches b ON s.batch_name = b.batch_id
    WHERE s.user_id = :user_id
");
$student_query->execute([':user_id' => $student_id]);
$student = $student_query->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student information not found");
}

$batch_id = $student['batch_id'];

// Get upcoming classes (next 30 days)
$upcoming_classes = $db->prepare("
    SELECT schedule_date, start_time, end_time, topic, description, is_cancelled, cancellation_reason 
    FROM schedule 
    WHERE batch_id = :batch_id 
    AND schedule_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY schedule_date ASC, start_time ASC
");
$upcoming_classes->execute([':batch_id' => $batch_id]);
$upcoming_classes = $upcoming_classes->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../header.php'; ?>
<?php include '../s_sidebar.php'; ?>

<div class="flex-1 ml-0 md:ml-64 min-h-screen">
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-calendar-alt text-blue-500"></i>
            <span>Upcoming Schedule</span>
        </h1>
    </header>

    <div class="p-4 md:p-6">
        <div class="bg-white p-6 rounded-xl shadow">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Next 30 Days Schedule</h2>
            </div>
            
            <?php if (count($upcoming_classes) > 0): ?>
                <div class="space-y-4">
                    <?php 
                    $current_date = null;
                    foreach ($upcoming_classes as $class): 
                        $class_date = date('l, F j, Y', strtotime($class['schedule_date']));
                        if ($class_date !== $current_date):
                            $current_date = $class_date;
                    ?>
                            <h3 class="text-md font-bold text-gray-800 mt-4 mb-2"><?= $current_date ?></h3>
                        <?php endif; ?>
                        
                        <div class="flex items-start p-4 border rounded-lg <?= $class['is_cancelled'] ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-200' ?>">
                            <div class="mr-4 mt-1">
                                <div class="bg-<?= $class['is_cancelled'] ? 'red' : 'blue' ?>-100 text-<?= $class['is_cancelled'] ? 'red' : 'blue' ?>-600 p-2 rounded-lg">
                                    <i class="fas fa-<?= $class['is_cancelled'] ? 'times' : 'calendar-check' ?>"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <h4 class="font-medium text-gray-800">
                                        <?= htmlspecialchars($class['topic']) ?>
                                        <?php if ($class['is_cancelled']): ?>
                                            <span class="ml-2 text-red-600">(Cancelled)</span>
                                        <?php endif; ?>
                                    </h4>
                                    <span class="text-sm text-gray-500">
                                        <?= date('g:i A', strtotime($class['start_time'])) ?> - <?= date('g:i A', strtotime($class['end_time'])) ?>
                                    </span>
                                </div>
                                <?php if ($class['is_cancelled'] && $class['cancellation_reason']): ?>
                                    <p class="text-sm text-red-600 mt-1">Reason: <?= htmlspecialchars($class['cancellation_reason']) ?></p>
                                <?php endif; ?>
                                <?php if ($class['description']): ?>
                                    <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($class['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No upcoming classes scheduled in the next 30 days</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>