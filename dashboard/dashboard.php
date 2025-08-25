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

// Prepare all database queries with parameterized statements to prevent SQL injection
// Running Batches
$stmt = $db->prepare("SELECT COUNT(*) FROM batches WHERE status = 'ongoing'");
$stmt->execute();
$running_batches = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM batches WHERE status = 'ongoing' AND 
                     (start_date <= :lastMonthEnd AND (end_date >= :lastMonthStart OR end_date IS NULL))");
$stmt->bindParam(':lastMonthEnd', $lastMonthEnd);
$stmt->bindParam(':lastMonthStart', $lastMonthStart);
$stmt->execute();
$last_month_running = $stmt->fetchColumn();
$running_diff = $running_batches - $last_month_running;

// Upcoming Batches
$stmt = $db->prepare("SELECT COUNT(*) FROM batches WHERE status = 'upcoming'");
$stmt->execute();
$upcoming_batches = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM batches WHERE status = 'upcoming' AND 
                     created_at BETWEEN :lastMonthStart AND :lastMonthEnd");
$stmt->bindParam(':lastMonthStart', $lastMonthStart);
$stmt->bindParam(':lastMonthEnd', $lastMonthEnd);
$stmt->execute();
$last_month_upcoming = $stmt->fetchColumn();
$upcoming_diff = $upcoming_batches - $last_month_upcoming;

// Total Enrolled Students
$stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE current_status = 'active'");
$stmt->execute();
$total_students = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE current_status = 'active' AND 
                     enrollment_date <= :lastMonthEnd");
$stmt->bindParam(':lastMonthEnd', $lastMonthEnd);
$stmt->execute();
$last_month_students = $stmt->fetchColumn();
$students_diff = $total_students - $last_month_students;

// Classes Occurred
$stmt = $db->prepare("SELECT COUNT(DISTINCT date) FROM attendance");
$stmt->execute();
$classes_occurred = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(DISTINCT date) FROM attendance WHERE date BETWEEN :lastMonthStart AND :lastMonthEnd");
$stmt->bindParam(':lastMonthStart', $lastMonthStart);
$stmt->bindParam(':lastMonthEnd', $lastMonthEnd);
$stmt->execute();
$last_month_classes = $stmt->fetchColumn();
$classes_diff = $classes_occurred - $last_month_classes;

// Upcoming Live Classes
$stmt = $db->prepare("
    SELECT schedule_date, start_time, end_time, topic, batch_id 
    FROM schedule 
    WHERE schedule_date >= CURDATE() AND is_cancelled = 0 
    ORDER BY schedule_date ASC, start_time ASC 
    LIMIT 5
");
$stmt->execute();
$upcoming_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent Absentees
$stmt = $db->prepare("
    SELECT student_name, date, batch_id 
    FROM attendance 
    WHERE status = 'Absent' 
    ORDER BY date DESC 
    LIMIT 2
");
$stmt->execute();
$recent_absentees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Unaddressed Feedbacks
$stmt = $db->prepare("
    SELECT COUNT(*) FROM feedback 
    WHERE action_taken IS NULL OR action_taken = ''
");
$stmt->execute();
$unaddressed_feedbacks = $stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT COUNT(*) FROM feedback 
    WHERE (action_taken IS NULL OR action_taken = '') AND date <= :lastMonthEnd
");
$stmt->bindParam(':lastMonthEnd', $lastMonthEnd);
$stmt->execute();
$last_month_feedbacks = $stmt->fetchColumn();
$feedbacks_diff = $unaddressed_feedbacks - $last_month_feedbacks;

// Recent Messages
$stmt = $db->prepare("
    SELECT cm.message, cm.sent_at, u.name as sender_name
    FROM chat_messages cm
    JOIN users u ON cm.sender_id = u.id
    ORDER BY cm.sent_at DESC 
    LIMIT 3
");
$stmt->execute();
$recent_msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Unread notifications count
$stmt = $db->prepare("
    SELECT COUNT(*) FROM notifications 
    WHERE user_id = :user_id AND is_read = 0
");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$unread_notifications = $stmt->fetchColumn();

// Pending feedback notifications
$stmt = $db->prepare("
    SELECT f.id, f.student_name, f.batch_id, f.feedback_text, f.date
    FROM feedback f
    WHERE (f.action_taken IS NULL OR f.action_taken = '')
    ORDER BY f.date DESC
    LIMIT 5
");
$stmt->execute();
$pending_feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Unread messages
$stmt = $db->prepare("
    SELECT cm.id, cm.message, cm.sent_at, u.name as sender_name
    FROM chat_messages cm
    JOIN users u ON cm.sender_id = u.id
    LEFT JOIN notifications n ON n.reference_id = cm.id AND n.type = 'message' AND n.user_id = :user_id1
    WHERE (n.id IS NULL OR n.is_read = 0) AND cm.sender_id != :user_id2
    ORDER BY cm.sent_at DESC
    LIMIT 5
");
$stmt->bindParam(':user_id1', $_SESSION['user_id']);
$stmt->bindParam(':user_id2', $_SESSION['user_id']);
$stmt->execute();
$unread_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Batch status data for chart
$batch_status_data = [
    'ongoing' => $db->query("SELECT COUNT(*) FROM batches WHERE status='ongoing'")->fetchColumn(),
    'upcoming' => $db->query("SELECT COUNT(*) FROM batches WHERE status='upcoming'")->fetchColumn(),
    'completed' => $db->query("SELECT COUNT(*) FROM batches WHERE status='completed'")->fetchColumn(),
    'cancelled' => $db->query("SELECT COUNT(*) FROM batches WHERE status='cancelled'")->fetchColumn()
];

// Check for new notifications since last visit
$last_notification_check = $_SESSION['last_notification_check'] ?? 0;
$stmt = $db->prepare("
    SELECT COUNT(*) FROM notifications 
    WHERE user_id = :user_id AND is_read = 0 AND created_at > FROM_UNIXTIME(:last_check)
");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->bindParam(':last_check', $last_notification_check);
$stmt->execute();
$new_notifications_count = $stmt->fetchColumn();

// Update last check time
$_SESSION['last_notification_check'] = time();

// Determine if we should play notification sound
$play_notification_sound = $new_notifications_count > 0;

// Success messages
if (isset($_GET['success'])) {
    $success_messages = [
        'batch_created' => "Batch created successfully!",
        'notification_marked' => "Notifications marked as read!"
    ];
    
    if (array_key_exists($_GET['success'], $success_messages)) {
        $success_message = htmlspecialchars($success_messages[$_GET['success']]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ASD Academy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #3B82F6;
            --primary-light: #EFF6FF;
            --secondary: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --info: #6366F1;
            --dark: #1F2937;
            --light: #F9FAFB;
            --gray: #6B7280;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F3F4F6;
            color: var(--dark);
        }
        
        /* Notification styles */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            z-index: 10;
        }
        
        .notification-badge.animate-pulse {
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
        }
        
        /* Notification dropdown */
        #notificationDropdown {
            transform-origin: top right;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            transition: all 0.2s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            opacity: 0;
            transform: scale(0.95) translateY(-10px);
            visibility: hidden;
        }
        
        #notificationDropdown.show {
            opacity: 1;
            transform: scale(1) translateY(0);
            visibility: visible;
        }
        
        .notification-item {
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }
        
        .notification-item::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.8) 50%, rgba(255,255,255,0) 100%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }
        
        .notification-item:hover::after {
            transform: translateX(100%);
        }
        
        /* Card styles */
        .metric-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 0;
            background: linear-gradient(to bottom, var(--primary), var(--info));
            transition: height 0.3s ease;
        }
        
        .metric-card:hover::before {
            height: 100%;
        }
        
        /* Info card styles */
        .info-card {
            transition: all 0.3s ease;
        }
        
        .info-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }
        
        /* Quick action buttons */
        .quick-action {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .quick-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        }
        
        .quick-action::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255,255,255,0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }
        
        .quick-action:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }
        
        /* Floating animation for notification bell */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }
        
        .notification-float {
            animation: float 3s ease-in-out infinite;
        }
        
        /* Glow effect for important notifications */
        @keyframes glow {
            0% { box-shadow: 0 0 5px rgba(239, 68, 68, 0.5); }
            50% { box-shadow: 0 0 20px rgba(239, 68, 68, 0.8); }
            100% { box-shadow: 0 0 5px rgba(239, 68, 68, 0.5); }
        }
        
        .notification-glow {
            animation: glow 2s infinite;
        }
        
        /* Modern notification icon */
        .notification-icon {
            position: relative;
            width: 24px;
            height: 24px;
        }
        
        .notification-icon i {
            position: relative;
            z-index: 2;
        }
        
        .notification-icon::before {
            content: '';
            position: absolute;
            top: -5px;
            right: -5px;
            width: 12px;
            height: 12px;
            background-color: var(--danger);
            border-radius: 50%;
            border: 2px solid white;
            z-index: 3;
            opacity: 0;
            transform: scale(0);
            transition: all 0.3s ease;
        }
        
        .notification-icon.has-notifications::before {
            opacity: 1;
            transform: scale(1);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .grid-cols-1 {
                grid-template-columns: 1fr;
            }
            
            .lg\:grid-cols-3 {
                grid-template-columns: 1fr;
            }
            
            .lg\:col-span-2 {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
<?php 
include '../header.php';
include '../sidebar.php';
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
                <button id="notificationButton" class="p-2 rounded-full hover:bg-gray-100 relative transition-colors duration-200 notification-float">
                    <div class="notification-icon <?= $unread_notifications > 0 ? 'has-notifications' : '' ?>">
                        <i class="fas fa-bell text-gray-600"></i>
                    </div>
                    <?php if ($unread_notifications > 0): ?>
                        <span class="notification-badge animate-pulse"><?= $unread_notifications ?></span>
                    <?php endif; ?>
                </button>
                
                <!-- Notification Dropdown -->
                <div id="notificationDropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl z-50 border border-gray-200 hidden">
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
                                <a href="../dashboard/pending_feedbacks.php" class="block">
                                    <div class="notification-item px-4 py-3 hover:bg-blue-50 border-b border-gray-100 last:border-0 <?= array_search($feedback, $pending_feedbacks) === 0 ? 'notification-glow' : '' ?>">
                                        <div class="flex items-start">
                                            <div class="relative flex-shrink-0">
                                                <div class="bg-red-100 text-red-600 p-2 rounded-full mr-3 flex items-center justify-center h-10 w-10">
                                                    <i class="fas fa-comment-dots"></i>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-start">
                                                    <span class="font-medium text-gray-900 truncate">New Feedback</span>
                                                    <span class="text-xs text-gray-500 whitespace-nowrap ml-2"><?= date('M j, g:i A', strtotime($feedback['date'])) ?></span>
                                                </div>
                                                <div class="text-sm text-gray-600 mt-1 truncate">
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
                                <a href="../chat/index.php" class="block">
                                    <div class="notification-item px-4 py-3 hover:bg-blue-50 border-b border-gray-100 last:border-0">
                                        <div class="flex items-start">
                                            <div class="relative flex-shrink-0">
                                                <div class="bg-blue-100 text-blue-600 p-2 rounded-full mr-3 flex items-center justify-center h-10 w-10">
                                                    <i class="fas fa-comment"></i>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-start">
                                                    <span class="font-medium text-gray-900 truncate">New Message</span>
                                                    <span class="text-xs text-gray-500 whitespace-nowrap ml-2"><?= date('M j, g:i A', strtotime($message['sent_at'])) ?></span>
                                                </div>
                                                <div class="text-sm text-gray-600 mt-1">
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
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg animate-fade-in" role="alert">
                <div class="flex items-center">
                    <div class="py-1"><i class="fas fa-check-circle mr-3 text-green-500"></i></div>
                    <div>
                        <p class="font-bold">Success</p>
                        <p><?= $success_message ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <a href="../dashboard/running_batches.php" class="metric-card bg-white p-5 rounded-xl shadow transition-all duration-200 border-l-4 border-blue-500 hover:border-blue-600">
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
            </a>
            
            <a href="../dashboard/upcoming_batches.php" class="metric-card bg-white p-5 rounded-xl shadow transition-all duration-200 border-l-4 border-purple-500 hover:border-purple-600">
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
            </a>
            
            <a href="../dashboard/enrolled_students.php" class="metric-card bg-white p-5 rounded-xl shadow transition-all duration-200 border-l-4 border-green-500 hover:border-green-600">
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
            </a>
            
            <a href="../dashboard/classes_occurred.php" class="metric-card bg-white p-5 rounded-xl shadow transition-all duration-200 border-l-4 border-yellow-500 hover:border-yellow-600">
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
            </a>
            
            <a href="../dashboard/pending_feedbacks.php" class="metric-card bg-white p-5 rounded-xl shadow transition-all duration-200 border-l-4 border-red-500 hover:border-red-600">
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
            </a>
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
                        <a href="../dashboard/upcoming_live_classes.php" class="text-xs text-blue-500 hover:underline">View All</a>
                    </div>
                    <div class="space-y-3">
                        <?php foreach ($upcoming_classes as $class): ?>
                            <div class="flex items-start p-3 bg-gray-50 rounded-lg border border-gray-100 hover:border-blue-200 transition-all duration-200">
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
                            <div class="flex items-center p-3 bg-red-50 rounded-lg border border-red-100 hover:border-red-200 transition-all duration-200">
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
                        <?php if (empty($recent_absentees)): ?>
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 text-center text-gray-500">
                                No recent absentees.
                            </div>
                        <?php endif; ?>
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
                        <div class="flex items-start p-3 bg-blue-50 rounded-lg border border-blue-100 hover:border-blue-200 transition-all duration-200">
                            <div class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3">
                                <i class="fas fa-comment"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-800"><strong><?= htmlspecialchars($msg['sender_name']) ?>:</strong> <?= htmlspecialchars($msg['message']) ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= date('M j, g:i A', strtotime($msg['sent_at'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($recent_msgs)): ?>
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 text-center text-gray-500">
                            No recent messages.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white p-5 rounded-xl shadow info-card">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 gap-3">
                    <a href="add_batch.php" class="quick-action flex flex-col items-center justify-center p-4 bg-blue-50 rounded-lg border border-blue-100 hover:bg-blue-100 transition-colors">
                        <div class="bg-blue-100 text-blue-600 p-3 rounded-full mb-2">
                            <i class="fas fa-plus"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Add Batch</span>
                    </a>
                    <a href="../attendance/attendance.php" class="quick-action flex flex-col items-center justify-center p-4 bg-green-50 rounded-lg border border-green-100 hover:bg-green-100 transition-colors">
                        <div class="bg-green-100 text-green-600 p-3 rounded-full mb-2">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Mark Attendance</span>
                    </a>
                    <a href="../exam/exams.php" class="quick-action flex flex-col items-center justify-center p-4 bg-purple-50 rounded-lg border border-purple-100 hover:bg-purple-100 transition-colors">
                        <div class="bg-purple-100 text-purple-600 p-3 rounded-full mb-2">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Create Exam</span>
                    </a>
                    <a href="../content/upload_content.php" class="quick-action flex flex-col items-center justify-center p-4 bg-yellow-50 rounded-lg border border-yellow-100 hover:bg-yellow-100 transition-colors">
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
            notificationSound.volume = 0.3;
            notificationSound.play().catch(e => console.log('Notification sound play failed:', e));
            
            // Add animation to notification icon
            const notificationIcon = document.querySelector('.notification-icon');
            if (notificationIcon) {
                notificationIcon.classList.add('animate-pulse');
                setTimeout(() => {
                    notificationIcon.classList.remove('animate-pulse');
                }, 3000);
            }
        <?php endif; ?>
        
        // Show success message with animation
        const successMessage = document.querySelector('.animate-fade-in');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.opacity = '0';
                successMessage.style.transition = 'opacity 1s ease';
                setTimeout(() => {
                    successMessage.remove();
                }, 1000);
            }, 5000);
        }
    });

    // Enhanced notification dropdown toggle with animations
    const notificationButton = document.getElementById('notificationButton');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationDot = document.querySelector('.notification-icon::before');
    
    notificationButton.addEventListener('click', function(e) {
        e.stopPropagation();
        
        if (notificationDropdown.classList.contains('hidden')) {
            // Open dropdown with animation
            notificationDropdown.classList.remove('hidden');
            setTimeout(() => {
                notificationDropdown.classList.add('show');
            }, 10);
            
            // Hide the notification dot when dropdown is opened
            document.querySelector('.notification-icon').classList.remove('has-notifications');
            
            // Mark notifications as seen via AJAX
            fetch('../notifications/mark_notifications_seen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({}),
            }).catch(error => console.error('Error:', error));
        } else {
            // Close dropdown with animation
            notificationDropdown.classList.remove('show');
            setTimeout(() => {
                notificationDropdown.classList.add('hidden');
            }, 200);
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        if (!notificationDropdown.classList.contains('hidden')) {
            notificationDropdown.classList.remove('show');
            setTimeout(() => {
                notificationDropdown.classList.add('hidden');
            }, 200);
        }
    });
    
    // Prevent dropdown from closing when clicking inside it
    notificationDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Add ripple effect to quick action buttons
    document.querySelectorAll('.quick-action').forEach(button => {
        button.addEventListener('click', function(e) {
            // Create ripple element
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');
            
            // Position the ripple
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = `${size}px`;
            ripple.style.left = `${e.clientX - rect.left - size/2}px`;
            ripple.style.top = `${e.clientY - rect.top - size/2}px`;
            
            // Add ripple to the button
            this.appendChild(ripple);
            
            // Remove ripple after animation
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    // Batch Status Chart with animation
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
            animation: {
                animateScale: true,
                animateRotate: true
            },
            maintainAspectRatio: false
        }
    });
    
    // Add hover effect to metric cards
    document.querySelectorAll('.metric-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
</script>

<?php include '../footer.php'; ?>