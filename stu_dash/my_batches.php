<?php

require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$student_query = $db->prepare("SELECT * FROM students WHERE user_id = :user_id");
$student_query->execute([':user_id' => $student_id]);
$student = $student_query->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student information not found");
}

// Get current batch
$current_batch_query = $db->prepare("
    SELECT b.* 
    FROM batches b
    JOIN students s ON b.batch_id = s.batch_name
    WHERE s.user_id = :user_id
");
$current_batch_query->execute([':user_id' => $student_id]);
$current_batch = $current_batch_query->fetch(PDO::FETCH_ASSOC);

// Get batch history
$batch_history_query = $db->prepare("
    SELECT sbh.*, b.course_name, b.start_date, b.end_date
    FROM student_batch_history sbh
    JOIN batches b ON sbh.to_batch_id = b.batch_id
    WHERE sbh.student_id = :student_id
    ORDER BY sbh.transfer_date DESC
");
$batch_history_query->execute([':student_id' => $student['student_id']]);
$batch_history = $batch_history_query->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../header.php'; ?>
<?php include '../s_sidebar.php'; ?>

<div class="flex-1 ml-0 md:ml-64 min-h-screen">
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-users text-blue-500"></i>
            <span>My Batches</span>
        </h1>
    </header>

    <div class="p-4 md:p-6">
        <!-- Current Batch -->
        <div class="bg-white p-6 rounded-xl shadow mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Current Batch</h2>
            <?php if ($current_batch): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Batch ID</p>
                        <p class="font-medium"><?= htmlspecialchars($current_batch['batch_id']) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Course Name</p>
                        <p class="font-medium"><?= htmlspecialchars($current_batch['course_name']) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Schedule</p>
                        <p class="font-medium"><?= htmlspecialchars($current_batch['time_slot']) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Duration</p>
                        <p class="font-medium">
                            <?= date('M j, Y', strtotime($current_batch['start_date'])) ?> - 
                            <?= date('M j, Y', strtotime($current_batch['end_date'])) ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Mode</p>
                        <p class="font-medium"><?= ucfirst($current_batch['mode']) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?= $current_batch['status'] === 'ongoing' ? 'bg-blue-100 text-blue-800' : 
                               ($current_batch['status'] === 'upcoming' ? 'bg-yellow-100 text-yellow-800' : 
                               ($current_batch['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')) ?>">
                            <?= ucfirst($current_batch['status']) ?>
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No current batch assigned</p>
            <?php endif; ?>
        </div>

        <!-- Batch History -->
        <div class="bg-white p-6 rounded-xl shadow">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Batch History</h2>
            <?php if (count($batch_history) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transfer Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($batch_history as $history): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($history['to_batch_id']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($history['course_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y', strtotime($history['start_date'])) ?> - 
                                        <?= date('M j, Y', strtotime($history['end_date'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y', strtotime($history['transfer_date'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No batch history available</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>