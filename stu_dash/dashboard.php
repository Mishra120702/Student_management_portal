<?php
session_start();
require_once '../db_connection.php';

// Check if student is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get student information
$student_id = $_SESSION['user_id'];
$student_query = $db->prepare("
    SELECT s.*, b.course_name, b.start_date, b.end_date, b.time_slot, b.mode, b.status, b.meeting_link
    FROM students s
    LEFT JOIN batches b ON s.batch_name = b.batch_id
    WHERE s.user_id = :user_id
");
$student_query->execute([':user_id' => $student_id]);
$student = $student_query->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student information not found");
}

// Set default values for missing fields
$student_name = isset($student['first_name']) ? $student['first_name'] . ' ' . (isset($student['last_name']) ? $student['last_name'] : '') : 'Student';
$batch_id = $student['batch_name'] ?? 'Not assigned';

// Get attendance summary
$attendance_query = $db->prepare("
    SELECT 
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
        COUNT(*) as total_attendance
    FROM attendance 
    WHERE student_name = :student_name AND batch_id = :batch_id
");
$attendance_query->execute([':student_name' => $student_name, ':batch_id' => $batch_id]);
$attendance = $attendance_query->fetch(PDO::FETCH_ASSOC);

// Initialize attendance data if empty
if (!$attendance) {
    $attendance = ['present_count' => 0, 'total_attendance' => 0];
}

// Get upcoming classes (next 5)
$upcoming_classes = [];
if ($batch_id !== 'Not assigned') {
    $upcoming_classes_query = $db->prepare("
        SELECT schedule_date, start_time, end_time, topic, description 
        FROM schedule 
        WHERE batch_id = :batch_id 
        AND schedule_date >= CURDATE() 
        AND is_cancelled = 0
        ORDER BY schedule_date ASC, start_time ASC 
        LIMIT 5
    ");
    $upcoming_classes_query->execute([':batch_id' => $batch_id]);
    $upcoming_classes = $upcoming_classes_query->fetchAll(PDO::FETCH_ASSOC);
}

// Get recent messages
$recent_msgs_query = $db->prepare("
    SELECT cm.message, cm.sent_at, u.name as sender_name
    FROM chat_messages cm
    JOIN users u ON cm.sender_id = u.id
    JOIN chat_conversations cc ON cm.conversation_id = cc.id
    WHERE (cc.admin_id = :user_id OR cc.batch_id = :batch_id)
    ORDER BY cm.sent_at DESC 
    LIMIT 5
");
$recent_msgs_query->execute([':user_id' => $student_id, ':batch_id' => $batch_id]);
$recent_msgs = $recent_msgs_query->fetchAll(PDO::FETCH_ASSOC);

// Get recent feedback
$recent_feedback = [];
if ($batch_id !== 'Not assigned') {
    $recent_feedback_query = $db->prepare("
        SELECT date, rating, feedback_text, action_taken
        FROM feedback
        WHERE student_name = :student_name AND batch_id = :batch_id
        ORDER BY date DESC
        LIMIT 3
    ");
    $recent_feedback_query->execute([':student_name' => $student_name, ':batch_id' => $batch_id]);
    $recent_feedback = $recent_feedback_query->fetchAll(PDO::FETCH_ASSOC);
}

// Get exam results
$exam_results = [];
if ($batch_id !== 'Not assigned') {
    $exam_results_query = $db->prepare("
        SELECT pe.exam_id, pe.exam_date, pe.duration, es.score, es.is_malpractice, es.notes
        FROM proctored_exams pe
        JOIN exam_students es ON pe.exam_id = es.exam_id
        WHERE pe.batch_id = :batch_id AND es.student_name = :student_name
        ORDER BY pe.exam_date DESC
        LIMIT 3
    ");
    $exam_results_query->execute([':batch_id' => $batch_id, ':student_name' => $student_name]);
    $exam_results = $exam_results_query->fetchAll(PDO::FETCH_ASSOC);
}

// Get unread notifications count for student
$unread_notifications = $db->query("
    SELECT COUNT(*) FROM notifications 
    WHERE user_id = $student_id AND is_read = 0
")->fetchColumn();

// Get pending feedback notifications
$pending_feedbacks = $db->query("
    SELECT f.id, f.student_name, f.batch_id, f.feedback_text, f.date
    FROM feedback f
    WHERE (f.action_taken IS NULL OR f.action_taken = '') AND f.student_name = '$student_name'
    ORDER BY f.date DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get unread messages
$unread_messages = $db->query("
    SELECT cm.id, cm.message, cm.sent_at, u.name as sender_name
    FROM chat_messages cm
    JOIN users u ON cm.sender_id = u.id
    JOIN chat_conversations cc ON cm.conversation_id = cc.id
    LEFT JOIN notifications n ON n.reference_id = cm.id AND n.type = 'message' AND n.user_id = $student_id
    WHERE (n.id IS NULL OR n.is_read = 0) AND cm.sender_id != $student_id AND (cc.admin_id = $student_id OR cc.batch_id = '$batch_id')
    ORDER BY cm.sent_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Check for new notifications since last visit
$last_notification_check = $_SESSION['last_notification_check'] ?? 0;
$new_notifications_count = $db->query("
    SELECT COUNT(*) FROM notifications 
    WHERE user_id = $student_id AND is_read = 0 AND created_at > FROM_UNIXTIME($last_notification_check)
")->fetchColumn();

// Update last check time
$_SESSION['last_notification_check'] = time();

// Determine if we should play notification sound
$play_notification_sound = $new_notifications_count > 0;
?>

<?php include '../header.php'; ?>
<?php include '../s_sidebar.php'; ?>

<!-- Audio element for notification sound (hidden) -->
<audio id="notificationSound" preload="auto">
    <source src="../assets/sounds/notification.mp3" type="audio/mpeg">
    <source src="../assets/sounds/notification.ogg" type="audio/ogg">
</audio>

<!-- Main Content -->
<div class="flex-1 ml-0 md:ml-64 min-h-screen transition-all duration-300 ease-in-out">
    <!-- Header -->
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <button class="md:hidden text-xl text-gray-600 hover:text-blue-500 transition-colors" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-tachometer-alt text-blue-500 animate-pulse"></i>
            <span>Student Dashboard</span>
        </h1>
        <div class="flex items-center space-x-4">
            <div class="relative">
                <button id="notificationButton" class="p-2 rounded-full hover:bg-gray-100 relative transition-colors duration-200 group">
                    <i class="fas fa-bell text-gray-600 group-hover:text-blue-500"></i>
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
                                <a href="../stu_dash/student_feedback.php" class="block transform hover:scale-[1.01] transition-transform duration-200">
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
                                                    <span class="notification-type font-medium text-gray-900 truncate">Feedback Response</span>
                                                    <span class="notification-time text-xs text-gray-500 whitespace-nowrap ml-2"><?= date('M j, g:i A', strtotime($feedback['date'])) ?></span>
                                                </div>
                                                <div class="notification-message text-sm text-gray-600 mt-1 truncate">
                                                    Response to your feedback (Batch <?= $feedback['batch_id'] ?>)
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                            
                            <?php foreach ($unread_messages as $message): ?>
                                <a href="../stu_chat/index.php" class="block transform hover:scale-[1.01] transition-transform duration-200">
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
            <a href="../logout.php" class="text-sm text-red-600 hover:underline flex items-center space-x-1 transition-colors hover:text-red-700">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>    
        </div>
    </header>

    <div class="p-4 md:p-6">
        <!-- Welcome Section -->
        <div class="bg-white p-6 rounded-xl shadow mb-6 transform hover:scale-[1.005] transition-transform duration-300">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-800 animate-fade-in">Welcome, <?= htmlspecialchars($student['first_name'] ?? 'Student') ?>!</h2>
                    <p class="text-gray-600">Here's what's happening with your course.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-500">Batch Status:</span>
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?= ($student['status'] ?? '') === 'ongoing' ? 'bg-blue-100 text-blue-800' : 
                               (($student['status'] ?? '') === 'upcoming' ? 'bg-yellow-100 text-yellow-800' : 
                               (($student['status'] ?? '') === 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')) ?>">
                            <?= ucfirst($student['status'] ?? 'Not assigned') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metrics and Course Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Course Info -->
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-300">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-book-open text-blue-500 mr-2"></i>
                    Course Information
                </h3>
                <div class="space-y-3">
                    <div class="animate-fade-in-up" style="animation-delay: 0.1s">
                        <p class="text-sm text-gray-500">Course Name</p>
                        <p class="font-medium"><?= htmlspecialchars($student['course_name'] ?? 'Not assigned') ?></p>
                    </div>
                    <div class="animate-fade-in-up" style="animation-delay: 0.2s">
                        <p class="text-sm text-gray-500">Batch ID</p>
                        <p class="font-medium"><?= htmlspecialchars($batch_id) ?></p>
                    </div>
                    <div class="animate-fade-in-up" style="animation-delay: 0.3s">
                        <p class="text-sm text-gray-500">Schedule</p>
                        <p class="font-medium">
                            <?= isset($student['time_slot']) ? htmlspecialchars($student['time_slot']) : 'Not assigned' ?> 
                            (<?= isset($student['mode']) ? ucfirst($student['mode']) : 'Not assigned' ?>)
                        </p>
                    </div>
                    <div class="animate-fade-in-up" style="animation-delay: 0.4s">
                        <p class="text-sm text-gray-500">Duration</p>
                        <p class="font-medium">
                            <?= isset($student['start_date']) ? date('M j, Y', strtotime($student['start_date'])) : 'Not assigned' ?> - 
                            <?= isset($student['end_date']) ? date('M j, Y', strtotime($student['end_date'])) : 'Not assigned' ?>
                        </p>
                    </div>
                    <?php if (($student['mode'] ?? '') === 'online' && isset($student['meeting_link'])): ?>
                    <div class="animate-fade-in-up" style="animation-delay: 0.5s">
                        <p class="text-sm text-gray-500">Meeting Link</p>
                        <a href="<?= htmlspecialchars($student['meeting_link']) ?>" target="_blank" class="font-medium text-blue-600 hover:underline transition-colors">
                            Join Class <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Attendance Summary -->
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-300">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-calendar-check text-green-500 mr-2"></i>
                    Attendance Summary
                </h3>
                <div class="flex items-center justify-between mb-4">
                    <div class="animate-fade-in-up" style="animation-delay: 0.2s">
                        <p class="text-3xl font-bold"><?= $attendance['present_count'] ?>/<?= $attendance['total_attendance'] ?></p>
                        <p class="text-sm text-gray-500">Classes Attended</p>
                    </div>
                    <div class="w-24 h-24 animate-fade-in-up" style="animation-delay: 0.4s">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
                <?php if ($batch_id !== 'Not assigned'): ?>
                <a href="attendance/view_attendance.php?student=<?= urlencode($student_name) ?>&batch=<?= $batch_id ?>" class="text-sm text-blue-600 hover:underline transition-colors inline-flex items-center">
                    View Full Attendance Record <i class="fas fa-chevron-right ml-1 text-xs"></i>
                </a>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-300">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                    Quick Actions
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="../stu_dash/student_feedback.php" class="flex flex-col items-center justify-center p-4 bg-blue-50 rounded-lg border border-blue-100 hover:bg-blue-100 transition-all duration-300 hover:scale-105">
                        <div class="bg-blue-100 text-blue-600 p-3 rounded-full mb-2 transition-colors group-hover:bg-blue-200">
                            <i class="fas fa-comment-dots"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Submit Feedback</span>
                    </a>
                    <a href="../stu_chat/index.php" class="flex flex-col items-center justify-center p-4 bg-green-50 rounded-lg border border-green-100 hover:bg-green-100 transition-all duration-300 hover:scale-105">
                        <div class="bg-green-100 text-green-600 p-3 rounded-full mb-2 transition-colors group-hover:bg-green-200">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Send Message</span>
                    </a>
                    <a href="view_resources.php" class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-lg border border-purple-100 hover:bg-purple-100 transition-all duration-300 hover:scale-105">
                        <div class="bg-purple-100 text-purple-600 p-3 rounded-full mb-2 transition-colors group-hover:bg-purple-200">
                            <i class="fas fa-book"></i>
                        </div>
                        <span class="text-sm font-medium text-center">View Resources</span>
                    </a>
                    <a href="assignments/view_assignments.php" class="flex flex-col items-center justify-center p-4 bg-yellow-50 rounded-lg border border-yellow-100 hover:bg-yellow-100 transition-all duration-300 hover:scale-105">
                        <div class="bg-yellow-100 text-yellow-600 p-3 rounded-full mb-2 transition-colors group-hover:bg-yellow-200">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Assignments</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Upcoming Classes and Messages -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Upcoming Classes -->
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-300">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-calendar-day text-indigo-500 mr-2"></i>
                        Upcoming Classes
                    </h3>
                    <?php if ($batch_id !== 'Not assigned'): ?>
                    <a href="schedule/view_schedule.php?batch=<?= $batch_id ?>" class="text-xs text-blue-500 hover:underline transition-colors flex items-center">
                        View All <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <div class="space-y-3">
                    <?php if (count($upcoming_classes) > 0): ?>
                        <?php foreach ($upcoming_classes as $index => $class): ?>
                            <div class="flex items-start p-3 bg-gray-50 rounded-lg border border-gray-100 hover:bg-gray-100 transition-colors duration-200 animate-fade-in-up" style="animation-delay: <?= 0.1 * $index ?>s">
                                <div class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3 transition-colors group-hover:bg-blue-200">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-800"><?= htmlspecialchars($class['topic']) ?></h4>
                                    <p class="text-sm text-gray-600">
                                        <?= date('D, M j', strtotime($class['schedule_date'])) ?> | 
                                        <?= date('g:i A', strtotime($class['start_time'])) ?> - <?= date('g:i A', strtotime($class['end_time'])) ?>
                                    </p>
                                    <?php if ($class['description']): ?>
                                    <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($class['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-6">
                            <div class="text-gray-400 mb-2">
                                <i class="fas fa-calendar-times text-3xl"></i>
                            </div>
                            <p class="text-gray-500">No upcoming classes scheduled</p>
                            <?php if ($batch_id === 'Not assigned'): ?>
                            <p class="text-xs text-gray-400 mt-1">You haven't been assigned to a batch yet</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Messages -->
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-300">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-comments text-blue-500 mr-2"></i>
                        Recent Messages
                    </h3>
                    <a href="messages/view_messages.php" class="text-xs text-blue-500 hover:underline transition-colors flex items-center">
                        View All <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
                <div class="space-y-3">
                    <?php if (count($recent_msgs) > 0): ?>
                        <?php foreach ($recent_msgs as $index => $msg): ?>
                            <div class="flex items-start p-3 bg-blue-50 rounded-lg border border-blue-100 hover:bg-blue-100 transition-colors duration-200 animate-fade-in-up" style="animation-delay: <?= 0.1 * $index ?>s">
                                <div class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3 transition-colors group-hover:bg-blue-200">
                                    <i class="fas fa-comment"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <h4 class="font-medium text-gray-800"><?= htmlspecialchars($msg['sender_name']) ?></h4>
                                        <span class="text-xs text-gray-500"><?= date('M j, g:i A', strtotime($msg['sent_at'])) ?></span>
                                    </div>
                                    <p class="text-gray-700 mt-1"><?= htmlspecialchars($msg['message']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-6">
                            <div class="text-gray-400 mb-2">
                                <i class="fas fa-comment-slash text-3xl"></i>
                            </div>
                            <p class="text-gray-500">No recent messages</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Feedback and Exam Results -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Feedback -->
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-300">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-star text-yellow-500 mr-2"></i>
                        Your Recent Feedback
                    </h3>
                    <a href="../stu_dash/student_feedback.php" class="text-xs text-blue-500 hover:underline transition-colors flex items-center">
                        View All <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
                <div class="space-y-3">
                    <?php if (count($recent_feedback) > 0): ?>
                        <?php foreach ($recent_feedback as $index => $feedback): ?>
                            <div class="flex items-start p-3 bg-green-50 rounded-lg border border-green-100 hover:bg-green-100 transition-colors duration-200 animate-fade-in-up" style="animation-delay: <?= 0.1 * $index ?>s">
                                <div class="bg-green-100 text-green-600 p-2 rounded-lg mr-3 transition-colors group-hover:bg-green-200">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= $feedback['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-xs text-gray-500"><?= date('M j, Y', strtotime($feedback['date'])) ?></span>
                                    </div>
                                    <p class="text-gray-700 mt-1"><?= htmlspecialchars($feedback['feedback_text']) ?></p>
                                    <?php if ($feedback['action_taken']): ?>
                                    <div class="mt-2 p-2 bg-white rounded border border-gray-200">
                                        <p class="text-xs font-medium text-gray-500">Action Taken:</p>
                                        <p class="text-xs"><?= htmlspecialchars($feedback['action_taken']) ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-6">
                            <div class="text-gray-400 mb-2">
                                <i class="fas fa-star-half-alt text-3xl"></i>
                            </div>
                            <p class="text-gray-500">No feedback submitted yet</p>
                            <a href="../stu_dash/student_feedback.php" class="text-xs text-blue-500 hover:underline mt-2 inline-block">
                                Submit your first feedback
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Exam Results -->
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition-shadow duration-300">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-clipboard-check text-purple-500 mr-2"></i>
                        Exam Results
                    </h3>
                    <a href="exams/view_results.php" class="text-xs text-blue-500 hover:underline transition-colors flex items-center">
                        View All <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
                <div class="space-y-3">
                    <?php if (count($exam_results) > 0): ?>
                        <?php foreach ($exam_results as $index => $exam): ?>
                            <div class="flex items-start p-3 bg-purple-50 rounded-lg border border-purple-100 hover:bg-purple-100 transition-colors duration-200 animate-fade-in-up" style="animation-delay: <?= 0.1 * $index ?>s">
                                <div class="bg-purple-100 text-purple-600 p-2 rounded-lg mr-3 transition-colors group-hover:bg-purple-200">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <h4 class="font-medium text-gray-800">Exam <?= htmlspecialchars($exam['exam_id']) ?></h4>
                                        <span class="text-xs text-gray-500"><?= date('M j, Y', strtotime($exam['exam_date'])) ?></span>
                                    </div>
                                    <div class="flex items-center mt-1">
                                        <span class="text-lg font-bold <?= $exam['score'] >= 80 ? 'text-green-600' : ($exam['score'] >= 60 ? 'text-yellow-600' : 'text-red-600') ?>">
                                            <?= $exam['score'] ?>%
                                        </span>
                                        <?php if ($exam['is_malpractice']): ?>
                                            <span class="ml-2 px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Malpractice</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($exam['notes']): ?>
                                    <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($exam['notes']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-6">
                            <div class="text-gray-400 mb-2">
                                <i class="fas fa-clipboard-list text-3xl"></i>
                            </div>
                            <p class="text-gray-500">No exam results available</p>
                            <?php if ($batch_id === 'Not assigned'): ?>
                            <p class="text-xs text-gray-400 mt-1">You haven't been assigned to a batch yet</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-fade-in {
        animation: fade-in 0.5s ease-out forwards;
    }
    
    .animate-fade-in-up {
        animation: fade-in-up 0.5s ease-out forwards;
        opacity: 0;
    }
    
    .hover-grow {
        transition: transform 0.3s ease;
    }
    
    .hover-grow:hover {
        transform: scale(1.02);
    }

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

    // Attendance Chart
    const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(attendanceCtx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent/Late'],
            datasets: [{
                data: [
                    <?= $attendance['present_count'] ?>,
                    <?= $attendance['total_attendance'] - $attendance['present_count'] ?>
                ],
                backgroundColor: ['#10B981', '#EF4444'],
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
            maintainAspectRatio: false,
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });

    // Animate elements as they come into view
    document.addEventListener('DOMContentLoaded', function() {
        const animateElements = document.querySelectorAll('.animate-fade-in-up');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        animateElements.forEach(el => observer.observe(el));
    });
</script>

<?php include '../footer.php'; ?>