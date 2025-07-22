<?php
include '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get current month and last month dates
$currentMonthStart = date('Y-m-01');
$lastMonthStart = date('Y-m-01', strtotime('-1 month'));
$lastMonthEnd = date('Y-m-t', strtotime('-1 month'));

// Running Batches - current and last month comparison
$running_batches = $db->query("SELECT COUNT(*) FROM batches WHERE status = 'ongoing'")->fetchColumn();
$last_month_running = $db->query("SELECT COUNT(*) FROM batches WHERE status = 'ongoing' AND 
                                 (start_date <= '$lastMonthEnd' AND (end_date >= '$lastMonthStart' OR end_date IS NULL))")->fetchColumn();
$running_diff = $running_batches - $last_month_running;

// Upcoming Batches - current and last month comparison
$upcoming_batches = $db->query("SELECT COUNT(*) FROM batches WHERE status = 'upcoming'")->fetchColumn();
$last_month_upcoming = $db->query("SELECT COUNT(*) FROM batches WHERE status = 'upcoming' AND 
                                  created_at BETWEEN '$lastMonthStart' AND '$lastMonthEnd'")->fetchColumn();
$upcoming_diff = $upcoming_batches - $last_month_upcoming;

// Total Enrolled Students - current and last month comparison
$total_students = $db->query("SELECT COUNT(*) FROM students WHERE current_status = 'active'")->fetchColumn();
$last_month_students = $db->query("SELECT COUNT(*) FROM students WHERE current_status = 'active' AND 
                                  enrollment_date <= '$lastMonthEnd'")->fetchColumn();
$students_diff = $total_students - $last_month_students;

// Classes Occurred (based on attendance records)
$classes_occurred = $db->query("SELECT COUNT(DISTINCT date) FROM attendance")->fetchColumn();
$last_month_classes = $db->query("SELECT COUNT(DISTINCT date) FROM attendance WHERE date BETWEEN '$lastMonthStart' AND '$lastMonthEnd'")->fetchColumn();
$classes_diff = $classes_occurred - $last_month_classes;

// Upcoming Live Classes (next 5) - Modified to fetch from today onward
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
    LIMIT 2
")->fetchAll(PDO::FETCH_ASSOC);

// Unaddressed Feedbacks - current and last month comparison
$unaddressed_feedbacks = $db->query("
    SELECT COUNT(*) FROM feedback 
    WHERE action_taken IS NULL OR action_taken = ''
")->fetchColumn();
$last_month_feedbacks = $db->query("
    SELECT COUNT(*) FROM feedback 
    WHERE (action_taken IS NULL OR action_taken = '') AND date <= '$lastMonthEnd'
")->fetchColumn();
$feedbacks_diff = $unaddressed_feedbacks - $last_month_feedbacks;

// Recent Messages
$recent_msgs = $db->query("
    SELECT cm.message, cm.sent_at, u.name as sender_name
    FROM chat_messages cm
    JOIN users u ON cm.sender_id = u.id
    ORDER BY cm.sent_at DESC 
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

// Get unread notifications count
$unread_notifications = $db->query("
    SELECT COUNT(*) FROM notifications 
    WHERE user_id = {$_SESSION['user_id']} AND is_read = 0
")->fetchColumn();

// Get pending feedback notifications
$pending_feedbacks = $db->query("
    SELECT f.id, f.student_name, f.batch_id, f.feedback_text, f.date
    FROM feedback f
    WHERE (f.action_taken IS NULL OR f.action_taken = '')
    ORDER BY f.date DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get unread messages
$unread_messages = $db->query("
    SELECT cm.id, cm.message, cm.sent_at, u.name as sender_name
    FROM chat_messages cm
    JOIN users u ON cm.sender_id = u.id
    LEFT JOIN notifications n ON n.reference_id = cm.id AND n.type = 'message' AND n.user_id = {$_SESSION['user_id']}
    WHERE (n.id IS NULL OR n.is_read = 0) AND cm.sender_id != {$_SESSION['user_id']}
    ORDER BY cm.sent_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Batch status data for chart
$batch_status_data = [
    'ongoing' => $db->query("SELECT COUNT(*) FROM batches WHERE status='ongoing'")->fetchColumn(),
    'upcoming' => $db->query("SELECT COUNT(*) FROM batches WHERE status='upcoming'")->fetchColumn(),
    'completed' => $db->query("SELECT COUNT(*) FROM batches WHERE status='completed'")->fetchColumn(),
    'cancelled' => $db->query("SELECT COUNT(*) FROM batches WHERE status='cancelled'")->fetchColumn()
];

// Check for new notifications since last visit
$last_notification_check = $_SESSION['last_notification_check'] ?? 0;
$new_notifications_count = $db->query("
    SELECT COUNT(*) FROM notifications 
    WHERE user_id = {$_SESSION['user_id']} AND is_read = 0 AND created_at > FROM_UNIXTIME($last_notification_check)
")->fetchColumn();

// Update last check time
$_SESSION['last_notification_check'] = time();

// Determine if we should play notification sound
$play_notification_sound = $new_notifications_count > 0;
?>
<?php
// At the top of dashboard.php, after session_start() if you have it
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'batch_created') {
        echo '<script>alert("Batch created successfully!");</script>';
    } elseif ($_GET['success'] === 'notification_marked') {
        echo '<script>alert("Notifications marked as read!");</script>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ASD Academy</title>
    <style>
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .notification-badge.animate-pulse {
            animation: pulse 2s infinite;
        }
        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .notification-item:hover {
            background-color: #f5f5f5;
        }
        .notification-time {
            font-size: 12px;
            color: #666;
        }
        .notification-type {
            font-weight: bold;
            margin-right: 5px;
        }
        .notification-message {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .notification-dot {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 8px;
            height: 8px;
            background-color: #ef4444;
            border-radius: 50%;
            display: none;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        #notificationDropdown {
            transform-origin: top right;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
        }
        #notificationDropdown.opacity-0 {
            opacity: 0;
        }
        #notificationDropdown.opacity-100 {
            opacity: 1;
        }
        #notificationDropdown.scale-95 {
            transform: scale(0.95);
        }
        #notificationDropdown.scale-100 {
            transform: scale(1);
        }
        .ripple-effect {
            position: absolute;
            border-radius: 50%;
            background-color: rgba(59, 130, 246, 0.3);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        .notification-item {
            transition: background-color 0.2s ease, transform 0.2s ease;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
<?php include '../header.php'; // Include header with CSS and JS links
    include '../sidebar.php'; // Include sidebar
?>
<!-- Audio element for notification sound (hidden) -->
<audio id="notificationSound" preload="auto">
    <source src="../assets/sounds/notification.mp3" type="audio/mpeg">
    <source src="../assets/sounds/notification.ogg" type="audio/ogg">
</audio>

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
            <div class="relative">
                <button id="notificationButton" class="p-2 rounded-full hover:bg-gray-100 relative transition-colors duration-200">
                    <i class="fas fa-bell text-gray-600"></i>
                    <?php if ($unread_notifications > 0): ?>
                        <span class="notification-badge animate-pulse"><?= $unread_notifications ?></span>
                    <?php endif; ?>
                    <span id="notificationDot" class="notification-dot <?= $new_notifications_count > 0 ? 'block' : 'hidden' ?>"></span>
                </button>
                
                <!-- Notification Dropdown -->
                <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl z-50 border border-gray-200 transform origin-top-right transition-all duration-300 ease-out opacity-0 scale-95">
                    <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gradient-to-r from-blue-50 to-purple-50">
                        <h3 class="font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-bell mr-2 text-blue-600"></i>
                            Notifications
                        </h3>
                        <form action="../notifications/mark_notifications_read.php" method="POST">
                            <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 transition-colors duration-200 flex items-center">
                                <i class="fas fa-check-circle mr-1"></i> Mark all as read
                            </button>
                        </form>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        <?php if (count($pending_feedbacks) > 0 || count($unread_messages) > 0): ?>
                            <?php foreach ($pending_feedbacks as $feedback): ?>
                                <a href="../dashboard/pending_feedbacks.php" class="block transform hover:scale-[1.01] transition-transform duration-200">
                                    <div class="notification-item px-4 py-3 hover:bg-blue-50 border-b border-gray-100 last:border-0">
                                        <div class="flex items-start">
                                            <div class="relative flex-shrink-0">
                                                <div class="bg-red-100 text-red-600 p-2 rounded-full mr-3 flex items-center justify-center h-10 w-10">
                                                    <i class="fas fa-comment-dots"></i>
                                                </div>
                                                <?php if (array_search($feedback, $pending_feedbacks) === 0): ?>
                                                    <span class="absolute -top-1 -right-1 h-3 w-3 bg-red-500 rounded-full border-2 border-white"></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-start">
                                                    <span class="notification-type font-medium text-gray-900 truncate">New Feedback</span>
                                                    <span class="notification-time text-xs text-gray-500 whitespace-nowrap ml-2"><?= date('M j, g:i A', strtotime($feedback['date'])) ?></span>
                                                </div>
                                                <div class="notification-message text-sm text-gray-600 mt-1 truncate">
                                                    From <?= htmlspecialchars($feedback['student_name']) ?> (Batch <?= $feedback['batch_id'] ?>)
                                                </div>
                                                <div class="mt-2 text-xs text-red-600 font-medium flex items-center">
                                                    <i class="fas fa-exclamation-circle mr-1"></i> Requires action
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                            
                            <?php foreach ($unread_messages as $message): ?>
                                <a href="../chat/index.php" class="block transform hover:scale-[1.01] transition-transform duration-200">
                                    <div class="notification-item px-4 py-3 hover:bg-blue-50 border-b border-gray-100 last:border-0">
                                        <div class="flex items-start">
                                            <div class="relative flex-shrink-0">
                                                <div class="bg-blue-100 text-blue-600 p-2 rounded-full mr-3 flex items-center justify-center h-10 w-10">
                                                    <i class="fas fa-comment"></i>
                                                </div>
                                                <?php if (array_search($message, $unread_messages) === 0): ?>
                                                    <span class="absolute -top-1 -right-1 h-3 w-3 bg-blue-500 rounded-full border-2 border-white"></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-start">
                                                    <span class="notification-type font-medium text-gray-900 truncate">New Message</span>
                                                    <span class="notification-time text-xs text-gray-500 whitespace-nowrap ml-2"><?= date('M j, g:i A', strtotime($message['sent_at'])) ?></span>
                                                </div>
                                                <div class="notification-message text-sm text-gray-600 mt-1">
                                                    <span class="font-medium"><?= htmlspecialchars($message['sender_name']) ?>:</span> 
                                                    <?= htmlspecialchars(substr($message['message'], 0, 50)) ?>...
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-6 text-center text-gray-500 flex flex-col items-center">
                                <div class="bg-gray-100 p-3 rounded-full mb-3 text-gray-400">
                                    <i class="fas fa-bell-slash text-xl"></i>
                                </div>
                                <p class="text-sm">No new notifications</p>
                                <p class="text-xs mt-1">You're all caught up!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-3 border-t border-gray-200 text-center bg-gray-50 rounded-b-lg">
                        <a href="../notifications/all_notifications.php" class="text-sm text-blue-600 hover:text-blue-800 transition-colors duration-200 inline-flex items-center">
                            <i class="fas fa-list mr-1"></i> View all notifications
                        </a>
                    </div>
                </div>
            </div>
            <a href="../logout.php" class="text-sm text-red-600 hover:underline flex items-center space-x-1">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>    
        </div>
    </header>

    <div class="p-4 md:p-6">
        <!-- Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <a href="../dashboard/running_batches.php">
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
                <p class="text-xs text-gray-500 mt-2">
                    <?php if ($running_diff > 0): ?>
                        <span class="text-green-500">+<?= $running_diff ?></span> from last month
                    <?php elseif ($running_diff < 0): ?>
                        <span class="text-red-500"><?= $running_diff ?></span> from last month
                    <?php else: ?>
                        <span>No change</span> from last month
                    <?php endif; ?>
                </p>
            </div></a>
            <a href="../dashboard/upcoming_batches.php">
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
                <p class="text-xs text-gray-500 mt-2">
                    <?php if ($upcoming_diff > 0): ?>
                        <span class="text-green-500">+<?= $upcoming_diff ?></span> from last month
                    <?php elseif ($upcoming_diff < 0): ?>
                        <span class="text-red-500"><?= $upcoming_diff ?></span> from last month
                    <?php else: ?>
                        <span>No change</span> from last month
                    <?php endif; ?>
                </p>
            </div></a>
            <a href="../dashboard/enrolled_students.php">
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
                <p class="text-xs text-gray-500 mt-2">
                    <?php if ($students_diff > 0): ?>
                        <span class="text-green-500">+<?= $students_diff ?></span> from last month
                    <?php elseif ($students_diff < 0): ?>
                        <span class="text-red-500"><?= $students_diff ?></span> from last month
                    <?php else: ?>
                        <span>No change</span> from last month
                    <?php endif; ?>
                </p>
            </div></a>
            <a href="../dashboard/classes_occurred.php">
            <div class="metric-card bg-white p-5 rounded-xl shadow transition-all duration-200 border-l-4 border-yellow-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Classes Occurred</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $classes_occurred ?></h3>
                    </div>
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-chalkboard-teacher text-lg"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    <?php if ($classes_diff > 0): ?>
                        <span class="text-green-500">+<?= $classes_diff ?></span> from last month
                    <?php elseif ($classes_diff < 0): ?>
                        <span class="text-red-500"><?= $classes_diff ?></span> from last month
                    <?php else: ?>
                        <span>No change</span> from last month
                    <?php endif; ?>
                </p>
            </div></a>
            <a href="../dashboard/pending_feedbacks.php">
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
                <p class="text-xs text-gray-500 mt-2">
                    <?php if ($feedbacks_diff > 0): ?>
                        <span class="text-red-500">+<?= $feedbacks_diff ?></span> from last month
                    <?php elseif ($feedbacks_diff < 0): ?>
                        <span class="text-green-500"><?= $feedbacks_diff ?></span> from last month
                    <?php else: ?>
                        <span>No change</span> from last month
                    <?php endif; ?>
                </p>
            </div>
        </div></a>

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
                        <a href="../dashboard/upcoming_live_classes.php" class="text-xs text-blue-500 hover:underline">View All</a>
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
                        <?php if (empty($upcoming_classes)): ?>
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 text-center text-gray-500">
                                No upcoming classes scheduled.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow info-card">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Recent Absentees</h2>
                        <a href="../dashboard/absent_reasons.php" class="text-xs text-blue-500 hover:underline">View All</a>
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
                    <a href="../chat/index.php" class="text-xs text-blue-500 hover:underline">View All</a>
                </div>
                <div class="space-y-3">
                    <?php foreach ($recent_msgs as $msg): ?>
                        <div class="flex items-start p-3 bg-blue-50 rounded-lg border border-blue-100">
                            <div class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3">
                                <i class="fas fa-comment"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-800"><strong><?= htmlspecialchars($msg['sender_name']) ?>:</strong> <?= htmlspecialchars($msg['message']) ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= date('M j, g:i A', strtotime($msg['sent_at'])) ?></p>
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
                    <a href="../attendance/attendance.php" class="flex flex-col items-center justify-center p-4 bg-green-50 rounded-lg border border-green-100 hover:bg-green-100 transition-colors">
                        <div class="bg-green-100 text-green-600 p-3 rounded-full mb-2">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Mark Attendance</span>
                    </a>
                    <a href="../exam/exams.php" class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-lg border border-purple-100 hover:bg-purple-100 transition-colors">
                        <div class="bg-purple-100 text-purple-600 p-3 rounded-full mb-2">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Create Exam</span>
                    </a>
                    <a href="../content/upload_content.php" class="flex flex-col items-center justify-center p-4 bg-yellow-50 rounded-lg border border-yellow-100 hover:bg-yellow-100 transition-colors">
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
    // Play notification sound if there are new notifications
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($play_notification_sound): ?>
            const notificationSound = document.getElementById('notificationSound');
            notificationSound.volume = 0.3; // Set volume to 30%
            notificationSound.play().catch(e => console.log('Notification sound play failed:', e));
        <?php endif; ?>
    });

    // Enhanced notification dropdown toggle with animations
    const notificationButton = document.getElementById('notificationButton');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationDot = document.getElementById('notificationDot');
    let isDropdownOpen = false;

    notificationButton.addEventListener('click', function(e) {
        e.stopPropagation();
        
        if (isDropdownOpen) {
            // Close dropdown with animation
            notificationDropdown.classList.remove('opacity-100', 'scale-100');
            notificationDropdown.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                notificationDropdown.classList.add('hidden');
            }, 200);
        } else {
            // Open dropdown with animation
            notificationDropdown.classList.remove('hidden');
            setTimeout(() => {
                notificationDropdown.classList.remove('opacity-0', 'scale-95');
                notificationDropdown.classList.add('opacity-100', 'scale-100');
            }, 10);
            
            // Hide the red dot when dropdown is opened
            notificationDot.classList.add('hidden');
            
            // Mark notifications as seen via AJAX
            fetch('../notifications/mark_notifications_seen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({}),
            }).catch(error => console.error('Error:', error));
        }
        
        isDropdownOpen = !isDropdownOpen;
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        if (isDropdownOpen) {
            notificationDropdown.classList.remove('opacity-100', 'scale-100');
            notificationDropdown.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                notificationDropdown.classList.add('hidden');
                isDropdownOpen = false;
            }, 200);
        }
    });
    
    // Prevent dropdown from closing when clicking inside it
    notificationDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Add ripple effect to notification items
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function(e) {
            // Create ripple element
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');
            
            // Position the ripple
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = `${size}px`;
            ripple.style.left = `${e.clientX - rect.left - size/2}px`;
            ripple.style.top = `${e.clientY - rect.top - size/2}px`;
            
            // Add ripple to the item
            this.appendChild(ripple);
            
            // Remove ripple after animation
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

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
<?php include '../footer.php'; // Include footer with scripts ?>