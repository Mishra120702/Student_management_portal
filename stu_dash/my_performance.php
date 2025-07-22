<?php
session_start();
require_once '../db_connection.php';

if (!isset($_SESSION['user_id']) ) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_query = $db->prepare("
    SELECT s.*, b.batch_id, b.course_name
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
$batch_id = $student['batch_id'];

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

// Get exam results
$exam_results = $db->prepare("
    SELECT pe.exam_id, pe.exam_date, pe.duration, es.score, es.is_malpractice, es.notes
    FROM proctored_exams pe
    JOIN exam_students es ON pe.exam_id = es.exam_id
    WHERE pe.batch_id = :batch_id AND es.student_name = :student_name
    ORDER BY pe.exam_date DESC
");
$exam_results->execute([':batch_id' => $batch_id, ':student_name' => $student_name]);
$exam_results = $exam_results->fetchAll(PDO::FETCH_ASSOC);

// Get assignment submissions
$assignments = $db->prepare("
    SELECT a.assignment_id, a.title, a.due_date, s.submission_date, 
           s.grade, s.feedback, s.status
    FROM assignments a
    LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id 
                                     AND s.student_id = :student_id
    WHERE a.batch_id = :batch_id
    ORDER BY a.due_date DESC
");
$assignments->execute([':batch_id' => $batch_id, ':student_id' => $student['student_id']]);
$assignments = $assignments->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../header.php'; ?>
<?php include '../s_sidebar.php'; ?>

<div class="flex-1 ml-0 md:ml-64 min-h-screen">
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-chart-line text-blue-500"></i>
            <span>My Performance</span>
        </h1>
    </header>

    <div class="p-4 md:p-6">
        <!-- Performance Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Attendance -->
            <div class="bg-white p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Attendance</h3>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold">
                            <?= $attendance['present_count'] ?>/<?= $attendance['total_attendance'] ?>
                        </p>
                        <p class="text-sm text-gray-500">Classes Attended</p>
                    </div>
                    <div class="w-24 h-24">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-2">
                    <?php 
                    $percentage = $attendance['total_attendance'] > 0 ? 
                        round(($attendance['present_count'] / $attendance['total_attendance']) * 100) : 0;
                    echo "Attendance Rate: $percentage%";
                    ?>
                </p>
            </div>

            <!-- Average Grades -->
            <div class="bg-white p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Exam Performance</h3>
                <?php if (count($exam_results) > 0): ?>
                    <?php
                    $total_score = 0;
                    $exam_count = 0;
                    foreach ($exam_results as $exam) {
                        $total_score += $exam['score'];
                        $exam_count++;
                    }
                    $average_score = $exam_count > 0 ? round($total_score / $exam_count) : 0;
                    ?>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-3xl font-bold <?= $average_score >= 80 ? 'text-green-600' : ($average_score >= 60 ? 'text-yellow-600' : 'text-red-600') ?>">
                                <?= $average_score ?>%
                            </p>
                            <p class="text-sm text-gray-500">Average Score</p>
                        </div>
                        <div class="text-5xl <?= $average_score >= 80 ? 'text-green-600' : ($average_score >= 60 ? 'text-yellow-600' : 'text-red-600') ?>">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">
                        Based on <?= $exam_count ?> exam<?= $exam_count !== 1 ? 's' : '' ?>
                    </p>
                <?php else: ?>
                    <p class="text-gray-500">No exam results available</p>
                <?php endif; ?>
            </div>

            <!-- Assignment Completion -->
            <div class="bg-white p-6 rounded-xl shadow">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Assignments</h3>
                <?php if (count($assignments) > 0): ?>
                    <?php
                    $completed = 0;
                    $graded = 0;
                    foreach ($assignments as $assignment) {
                        if ($assignment['submission_date']) $completed++;
                        if ($assignment['grade'] !== null) $graded++;
                    }
                    $completion_rate = count($assignments) > 0 ? round(($completed / count($assignments)) * 100) : 0;
                    ?>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-3xl font-bold <?= $completion_rate >= 80 ? 'text-green-600' : ($completion_rate >= 60 ? 'text-yellow-600' : 'text-red-600') ?>">
                                <?= $completion_rate ?>%
                            </p>
                            <p class="text-sm text-gray-500">Completion Rate</p>
                        </div>
                        <div class="text-5xl <?= $completion_rate >= 80 ? 'text-green-600' : ($completion_rate >= 60 ? 'text-yellow-600' : 'text-red-600') ?>">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">
                        <?= $completed ?> of <?= count($assignments) ?> submitted
                        <?php if ($graded > 0): ?>
                            <br><?= $graded ?> graded
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <p class="text-gray-500">No assignments available</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detailed Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Exam Results -->
            <div class="bg-white p-6 rounded-xl shadow">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Exam Results</h3>
                </div>
                <?php if (count($exam_results) > 0): ?>
                    <div class="space-y-3">
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
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">No exam results available</p>
                <?php endif; ?>
            </div>

            <!-- Assignments -->
            <div class="bg-white p-6 rounded-xl shadow">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Assignments</h3>
                </div>
                <?php if (count($assignments) > 0): ?>
                    <div class="space-y-3">
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="flex items-start p-3 bg-blue-50 rounded-lg border border-blue-100">
                                <div class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <h4 class="font-medium text-gray-800"><?= htmlspecialchars($assignment['title']) ?></h4>
                                        <span class="text-xs text-gray-500">Due: <?= date('M j', strtotime($assignment['due_date'])) ?></span>
                                    </div>
                                    <div class="flex items-center mt-1">
                                        <?php if ($assignment['submission_date']): ?>
                                            <span class="text-xs text-green-600">Submitted on <?= date('M j', strtotime($assignment['submission_date'])) ?></span>
                                        <?php else: ?>
                                            <span class="text-xs text-red-600">Not submitted</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($assignment['grade'] !== null): ?>
                                        <div class="mt-1">
                                            <span class="text-sm font-medium">Grade: </span>
                                            <span class="text-sm <?= $assignment['grade'] >= 80 ? 'text-green-600' : ($assignment['grade'] >= 60 ? 'text-yellow-600' : 'text-red-600') ?>">
                                                <?= $assignment['grade'] ?>%
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($assignment['feedback']): ?>
                                        <div class="mt-1">
                                            <p class="text-xs text-gray-600">Feedback: <?= htmlspecialchars($assignment['feedback']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">No assignments available</p>
                <?php endif; ?>
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