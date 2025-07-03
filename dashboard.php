<?php
// At the top of dashboard.php, after session_start() if you have it
if (isset($_GET['success']) && $_GET['success'] === 'batch_created') {
    echo '<script>alert("Batch created successfully!");</script>';
}
?>
<?php
include '../db_connection.php';

// Running Batches
$running_batches = $db->query("SELECT COUNT(*) FROM batches WHERE status = 'ongoing'")->fetchColumn();

// Upcoming Batches
$upcoming_batches = $db->query("SELECT COUNT(*) FROM batches WHERE status = 'upcoming'")->fetchColumn();

// Total Enrolled Students
$total_students = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();

// Upcoming Live Classes (next 5)
$upcoming_classes = $db->query("
    SELECT schedule_date, start_time, end_time, topic, batch_id 
    FROM schedule 
    WHERE schedule_date >= CURDATE() AND is_cancelled = 0 
    ORDER BY schedule_date ASC, start_time ASC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Recent Absentees (last 5)
$recent_absentees = $db->query("
    SELECT student_name, date, batch_id 
    FROM attendance 
    WHERE status = 'Absent' 
    ORDER BY date DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Unaddressed Feedbacks
$unaddressed_feedbacks = $db->query("
    SELECT COUNT(*) FROM feedback 
    WHERE action_taken IS NULL OR action_taken = ''
")->fetchColumn();

// Recent Messages
$recent_msgs = $db->query("
    SELECT message_text, timestamp 
    FROM messages 
    ORDER BY timestamp DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Batch status data for chart
$batch_status_data = [
    'ongoing' => $db->query("SELECT COUNT(*) FROM batches WHERE status='ongoing'")->fetchColumn(),
    'upcoming' => $db->query("SELECT COUNT(*) FROM batches WHERE status='upcoming'")->fetchColumn(),
    'completed' => $db->query("SELECT COUNT(*) FROM batches WHERE status='completed'")->fetchColumn(),
    'cancelled' => $db->query("SELECT COUNT(*) FROM batches WHERE status='cancelled'")->fetchColumn()
];
?>

<?php include '../header.php'; // Include header with CSS and JS links
    include '../sidebar.php'; // Include sidebar
?>
<!-- Sidebar -->

<!-- Main Content -->
<div class="flex-1 ml-0 md:ml-64 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-tachometer-alt text-blue-500"></i>
            <span>Admin Dashboard</span>
        </h1>
        <div class="flex items-center space-x-4">
            <button class="p-2 rounded-full hover:bg-gray-100">
                <i class="fas fa-bell text-gray-600"></i>
            </button>
            <a href="logout.php" class="text-sm text-red-600 hover:underline flex items-center space-x-1">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>    
        </div>
    </header>

    <div class="p-4 md:p-6">
        <!-- Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="metric-card bg-white p-5 rounded-xl shadow transition-all duration-200 border-l-4 border-blue-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Running Batches</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $running_batches ?></h3>
                    </div>
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-play-circle text-lg"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2"><span class="text-green-500">+2.5%</span> from last month</p>
            </div>
            
            <div class="metric-card bg-white p-5 rounded-xl shadow transition-all duration-200 border-l-4 border-purple-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Upcoming Batches</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $upcoming_batches ?></h3>
                    </div>
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-calendar-alt text-lg"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2"><span class="text-green-500">+1</span> new batch scheduled</p>
            </div>
            
            <div class="metric-card bg-white p-5 rounded-xl shadow transition-all duration-200 border-l-4 border-green-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Enrolled Students</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $total_students ?></h3>
                    </div>
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-users text-lg"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2"><span class="text-green-500">+12</span> new students this week</p>
            </div>
            
            <div class="metric-card bg-white p-5 rounded-xl shadow transition-all duration-200 border-l-4 border-red-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Pending Feedbacks</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $unaddressed_feedbacks ?></h3>
                    </div>
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <i class="fas fa-comment-dots text-lg"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2"><span class="text-red-500">+3</span> new feedbacks today</p>
            </div>
        </div>

        <!-- Charts and Info Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Batch Status Chart -->
            <div class="bg-white p-5 rounded-xl shadow info-card lg:col-span-1">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Batch Status</h2>
                    <select class="text-xs border rounded px-2 py-1 bg-gray-50">
                        <option>This Month</option>
                        <option>Last Month</option>
                        <option>This Year</option>
                    </select>
                </div>
                <div class="h-64">
                    <canvas id="batchChart"></canvas>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-4 text-xs">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                        <span>Ongoing (<?= $batch_status_data['ongoing'] ?>)</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                        <span>Upcoming (<?= $batch_status_data['upcoming'] ?>)</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                        <span>Completed (<?= $batch_status_data['completed'] ?>)</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                        <span>Cancelled (<?= $batch_status_data['cancelled'] ?>)</span>
                    </div>
                </div>
            </div>

            <!-- Class & Absentee Info -->
            <div class="space-y-6 lg:col-span-2">
                <div class="bg-white p-5 rounded-xl shadow info-card">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Upcoming Live Classes</h2>
                        <a href="#" class="text-xs text-blue-500 hover:underline">View All</a>
                    </div>
                    <div class="space-y-3">
                        <?php foreach ($upcoming_classes as $class): ?>
                            <div class="flex items-start p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-800"><?= htmlspecialchars($class['topic']) ?></h4>
                                    <p class="text-sm text-gray-600">
                                        <?= date('D, M j', strtotime($class['schedule_date'])) ?> | 
                                        <?= date('g:i A', strtotime($class['start_time'])) ?> - <?= date('g:i A', strtotime($class['end_time'])) ?>
                                    </p>
                                    <span class="text-xs bg-blue-50 text-blue-600 px-2 py-1 rounded-full mt-1 inline-block">Batch #<?= $class['batch_id'] ?></span>
                                </div>
                                <button class="text-gray-400 hover:text-blue-500">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow info-card">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Recent Absentees</h2>
                        <a href="#" class="text-xs text-blue-500 hover:underline">View All</a>
                    </div>
                    <div class="space-y-3">
                        <?php foreach ($recent_absentees as $absent): ?>
                            <div class="flex items-center p-3 bg-red-50 rounded-lg border border-red-100">
                                <div class="bg-red-100 text-red-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-800"><?= htmlspecialchars($absent['student_name']) ?></h4>
                                    <p class="text-sm text-gray-600"><?= date('M j, Y', strtotime($absent['date'])) ?></p>
                                    <span class="text-xs bg-red-50 text-red-600 px-2 py-1 rounded-full mt-1 inline-block">Batch #<?= $absent['batch_id'] ?></span>
                                </div>
                                <button class="text-gray-400 hover:text-blue-500">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages and Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Recent Messages -->
            <div class="bg-white p-5 rounded-xl shadow info-card lg:col-span-2">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Recent Messages</h2>
                    <a href="#" class="text-xs text-blue-500 hover:underline">View All</a>
                </div>
                <div class="space-y-3">
                    <?php foreach ($recent_msgs as $msg): ?>
                        <div class="flex items-start p-3 bg-blue-50 rounded-lg border border-blue-100">
                            <div class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3">
                                <i class="fas fa-comment"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-800"><?= htmlspecialchars($msg['message_text']) ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= date('M j, g:i A', strtotime($msg['timestamp'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white p-5 rounded-xl shadow info-card">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 gap-3">
                    <a href="add_batch.php" class="flex flex-col items-center justify-center p-4 bg-blue-50 rounded-lg border border-blue-100 hover:bg-blue-100 transition-colors">
                        <div class="bg-blue-100 text-blue-600 p-3 rounded-full mb-2">
                            <i class="fas fa-plus"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Add Batch</span>
                    </a>
                    <a href="mark_attendance.php" class="flex flex-col items-center justify-center p-4 bg-green-50 rounded-lg border border-green-100 hover:bg-green-100 transition-colors">
                        <div class="bg-green-100 text-green-600 p-3 rounded-full mb-2">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Mark Attendance</span>
                    </a>
                    <a href="create_exam.php" class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-lg border border-purple-100 hover:bg-purple-100 transition-colors">
                        <div class="bg-purple-100 text-purple-600 p-3 rounded-full mb-2">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Create Exam</span>
                    </a>
                    <a href="upload_content.php" class="flex flex-col items-center justify-center p-4 bg-yellow-50 rounded-lg border border-yellow-100 hover:bg-yellow-100 transition-colors">
                        <div class="bg-yellow-100 text-yellow-600 p-3 rounded-full mb-2">
                            <i class="fas fa-upload"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Upload Content</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Batch Status Chart
    const batchCtx = document.getElementById('batchChart').getContext('2d');
    const batchChart = new Chart(batchCtx, {
        type: 'doughnut',
        data: {
            labels: ['Ongoing', 'Upcoming', 'Completed', 'Cancelled'],
            datasets: [{
                data: [
                    <?= $batch_status_data['ongoing'] ?>,
                    <?= $batch_status_data['upcoming'] ?>,
                    <?= $batch_status_data['completed'] ?>,
                    <?= $batch_status_data['cancelled'] ?>
                ],
                backgroundColor: ['#3B82F6', '#F59E0B', '#10B981', '#EF4444'],
                borderWidth: 0,
            }]
        },
        options: {
            cutout: '70%',
            plugins: {
                legend: {
                    display: false
                }
            },
            maintainAspectRatio: false
        }
    });
</script>

</body>
</html>