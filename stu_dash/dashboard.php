
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
    JOIN batches b ON s.batch_name = b.batch_id
    WHERE s.user_id = :user_id
");
$student_query->execute([':user_id' => $student_id]);
$student = $student_query->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student information not found");
}

$student_name = $student['first_name'] . ' ' . $student['last_name'];
$batch_id = $student['batch_name'];

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

// Get upcoming classes (next 5)
$upcoming_classes = $db->prepare("
    SELECT schedule_date, start_time, end_time, topic, description 
    FROM schedule 
    WHERE batch_id = :batch_id 
    AND schedule_date >= CURDATE() 
    AND is_cancelled = 0
    ORDER BY schedule_date ASC, start_time ASC 
    LIMIT 5
");
$upcoming_classes->execute([':batch_id' => $batch_id]);
$upcoming_classes = $upcoming_classes->fetchAll(PDO::FETCH_ASSOC);

// Get recent messages
$recent_msgs = $db->prepare("
    SELECT cm.message, cm.sent_at, u.name as sender_name
    FROM chat_messages cm
    JOIN users u ON cm.sender_id = u.id
    JOIN chat_conversations cc ON cm.conversation_id = cc.id
    WHERE (cc.admin_id = :user_id OR cc.batch_id = :batch_id)
    ORDER BY cm.sent_at DESC 
    LIMIT 5
");
$recent_msgs->execute([':user_id' => $student_id, ':batch_id' => $batch_id]);
$recent_msgs = $recent_msgs->fetchAll(PDO::FETCH_ASSOC);

// Get recent feedback
$recent_feedback = $db->prepare("
    SELECT date, rating, feedback_text, action_taken
    FROM feedback
    WHERE student_name = :student_name AND batch_id = :batch_id
    ORDER BY date DESC
    LIMIT 3
");
$recent_feedback->execute([':student_name' => $student_name, ':batch_id' => $batch_id]);
$recent_feedback = $recent_feedback->fetchAll(PDO::FETCH_ASSOC);

// Get exam results
$exam_results = $db->prepare("
    SELECT pe.exam_id, pe.exam_date, pe.duration, es.score, es.is_malpractice, es.notes
    FROM proctored_exams pe
    JOIN exam_students es ON pe.exam_id = es.exam_id
    WHERE pe.batch_id = :batch_id AND es.student_name = :student_name
    ORDER BY pe.exam_date DESC
    LIMIT 3
");
$exam_results->execute([':batch_id' => $batch_id, ':student_name' => $student_name]);
$exam_results = $exam_results->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../header.php'; ?>
<?php include '../s_sidebar.php'; ?>

<!-- Main Content -->
<div class="flex-1 ml-0 md:ml-64 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-tachometer-alt text-blue-500"></i>
            <span>Student Dashboard</span>
        </h1>
        <div class="flex items-center space-x-4">
            <button class="p-2 rounded-full hover:bg-gray-100">
                <i class="fas fa-bell text-gray-600"></i>
            </button>
            <a href="../logout.php" class="text-sm text-red-600 hover:underline flex items-center space-x-1">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>    
        </div>
    </header>

    <div class="p-4 md:p-6">
        <!-- Welcome Section -->
        <div class="bg-white p-6 rounded-xl shadow mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Welcome, <?= htmlspecialchars($student['first_name']) ?>!</h2>
                    <p class="text-gray-600">Here's what's happening with your course.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-500">Batch Status:</span>
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?= $student['status'] === 'ongoing' ? 'bg-blue-100 text-blue-800' : 
                               ($student['status'] === 'upcoming' ? 'bg-yellow-100 text-yellow-800' : 
                               ($student['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')) ?>">
                            <?= ucfirst($student['status']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metrics and Course Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Course Info -->
            <div class="bg-white p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Course Information</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Course Name</p>
                        <p class="font-medium"><?= htmlspecialchars($student['course_name']) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Batch ID</p>
                        <p class="font-medium"><?= htmlspecialchars($batch_id) ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Schedule</p>
                        <p class="font-medium"><?= htmlspecialchars($student['time_slot']) ?> (<?= ucfirst($student['mode']) ?>)</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Duration</p>
                        <p class="font-medium"><?= date('M j, Y', strtotime($student['start_date'])) ?> - <?= date('M j, Y', strtotime($student['end_date'])) ?></p>
                    </div>
                    <?php if ($student['mode'] === 'online' && $student['meeting_link']): ?>
                    <div>
                        <p class="text-sm text-gray-500">Meeting Link</p>
                        <a href="<?= htmlspecialchars($student['meeting_link']) ?>" target="_blank" class="font-medium text-blue-600 hover:underline">
                            Join Class
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Attendance Summary -->
            <div class="bg-white p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Attendance Summary</h3>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-3xl font-bold"><?= $attendance['present_count'] ?>/<?= $attendance['total_attendance'] ?></p>
                        <p class="text-sm text-gray-500">Classes Attended</p>
                    </div>
                    <div class="w-24 h-24">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
                <a href="attendance/view_attendance.php?student=<?= urlencode($student_name) ?>&batch=<?= $batch_id ?>" class="text-sm text-blue-600 hover:underline">
                    View Full Attendance Record
                </a>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="../stu_dash/student_feedback.php" class="flex flex-col items-center justify-center p-4 bg-blue-50 rounded-lg border border-blue-100 hover:bg-blue-100 transition-colors">
                        <div class="bg-blue-100 text-blue-600 p-3 rounded-full mb-2">
                            <i class="fas fa-comment-dots"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Submit Feedback</span>
                    </a>
                    <a href="../stu_chat/index.php" class="flex flex-col items-center justify-center p-4 bg-green-50 rounded-lg border border-green-100 hover:bg-green-100 transition-colors">
                        <div class="bg-green-100 text-green-600 p-3 rounded-full mb-2">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Send Message</span>
                    </a>
                    <a href="view_resources.php" class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-lg border border-purple-100 hover:bg-purple-100 transition-colors">
                        <div class="bg-purple-100 text-purple-600 p-3 rounded-full mb-2">
                            <i class="fas fa-book"></i>
                        </div>
                        <span class="text-sm font-medium text-center">View Resources</span>
                    </a>
                    <a href="assignments/view_assignments.php" class="flex flex-col items-center justify-center p-4 bg-yellow-50 rounded-lg border border-yellow-100 hover:bg-yellow-100 transition-colors">
                        <div class="bg-yellow-100 text-yellow-600 p-3 rounded-full mb-2">
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
            <div class="bg-white p-6 rounded-xl shadow">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Upcoming Classes</h3>
                    <a href="schedule/view_schedule.php?batch=<?= $batch_id ?>" class="text-xs text-blue-500 hover:underline">View All</a>
                </div>
                <div class="space-y-3">
                    <?php if (count($upcoming_classes) > 0): ?>
                        <?php foreach ($upcoming_classes as $class): ?>
                            <div class="flex items-start p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3">
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
                        <p class="text-gray-500 text-center py-4">No upcoming classes scheduled</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Messages -->
<div class="bg-white p-6 rounded-xl shadow">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Recent Messages</h3>
        <a href="messages/view_messages.php" class="text-xs text-blue-500 hover:underline">View All</a>
    </div>
    <div class="space-y-3">
        <?php if (count($recent_msgs) > 0): ?>
            <?php foreach ($recent_msgs as $msg): ?>
                <div class="flex items-start p-3 bg-blue-50 rounded-lg border border-blue-100">
                    <div class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3">
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
            <p class="text-gray-500 text-center py-4">No recent messages</p>
        <?php endif; ?>
    </div>
</div>

        <!-- Feedback and Exam Results -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Feedback -->
            <div class="bg-white p-6 rounded-xl shadow">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Your Recent Feedback</h3>
                    <a href="../stu_dash/student_feedback.php" class="text-xs text-blue-500 hover:underline">View All</a>
                </div>
                <div class="space-y-3">
                    <?php if (count($recent_feedback) > 0): ?>
                        <?php foreach ($recent_feedback as $feedback): ?>
                            <div class="flex items-start p-3 bg-green-50 rounded-lg border border-green-100">
                                <div class="bg-green-100 text-green-600 p-2 rounded-lg mr-3">
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
                        <p class="text-gray-500 text-center py-4">No feedback submitted yet</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Exam Results -->
            <div class="bg-white p-6 rounded-xl shadow">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Exam Results</h3>
                    <a href="exams/view_results.php" class="text-xs text-blue-500 hover:underline">View All</a>
                </div>
                <div class="space-y-3">
                    <?php if (count($exam_results) > 0): ?>
                        <?php foreach ($exam_results as $exam): ?>
                            <div class="flex items-start p-3 bg-purple-50 rounded-lg border border-purple-100">
                                <div class="bg-purple-100 text-purple-600 p-2 rounded-lg mr-3">
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
                        <p class="text-gray-500 text-center py-4">No exam results available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
            maintainAspectRatio: false
        }
    });
</script>

<?php include '../footer.php'; ?>