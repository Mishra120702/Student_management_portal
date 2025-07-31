<?php
require_once '../db_connection.php';
require_once '../header.php';
require_once '../sidebar.php';

// Get filter parameters
$batch_id = $_GET['batch_id'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$student_id = $_GET['student_id'] ?? '';
$report_view = $_GET['view'] ?? 'overview'; // Added view parameter

// Get all batches and students for filter dropdowns
$batches_query = $db->query("SELECT batch_id, course_name FROM batches ORDER BY start_date DESC");
$batches = $batches_query->fetchAll(PDO::FETCH_ASSOC);

$students_query = $db->query("SELECT student_id, CONCAT(first_name, ' ', last_name) as name FROM students ORDER BY first_name");
$students = $students_query->fetchAll(PDO::FETCH_ASSOC);

// Initialize performance data
$performance_data = [];
$student_name = '';
$report_title = 'Student Performance Report';
$batch_name = '';

// Get student performance data if student selected
if (!empty($student_id)) {
    $student_info = $db->prepare("SELECT CONCAT(first_name, ' ', last_name) as name, batch_name FROM students WHERE student_id = ?");
    $student_info->execute([$student_id]);
    $student_data = $student_info->fetch(PDO::FETCH_ASSOC);
    $student_name = $student_data['name'];
    $batch_name = $student_data['batch_name'];
    $report_title = "Performance Report for " . $student_name;
    
    // Attendance performance
    $attendance_perf = $db->prepare("SELECT 
        date as date, 
        'Attendance' as type,
        NULL as score,
        status as status,
        remarks as remarks,
        batch_id
        FROM attendance 
        WHERE student_name = ?
        AND date BETWEEN ? AND ?
        " . (!empty($batch_id) ? " AND batch_id = ?" : "") . "
        ORDER BY date DESC");
    
    $params = [$student_name, $start_date, $end_date];
    if (!empty($batch_id)) {
        $params[] = $batch_id;
    }
    $attendance_perf->execute($params);
    
    // Exam performance
    $exam_perf = $db->prepare("SELECT 
        e.exam_date as date,
        'Exam' as type,
        es.score as score,
        IF(es.is_malpractice = 1, 'Malpractice', 'Completed') as status,
        es.notes as remarks,
        e.batch_id as batch_id
        FROM exam_students es
        JOIN proctored_exams e ON es.exam_id = e.exam_id
        WHERE es.student_name = ?
        AND e.exam_date BETWEEN ? AND ?
        " . (!empty($batch_id) ? " AND e.batch_id = ?" : "") . "
        ORDER BY e.exam_date DESC");
    
    $params = [$student_name, $start_date, $end_date];
    if (!empty($batch_id)) {
        $params[] = $batch_id;
    }
    $exam_perf->execute($params);
    
    // Combine results
    $performance_data = array_merge($attendance_perf->fetchAll(PDO::FETCH_ASSOC), $exam_perf->fetchAll(PDO::FETCH_ASSOC));
    
    // Sort by date
    usort($performance_data, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // Get feedback data
    $feedback_query = $db->prepare("SELECT 
        date, 
        class_rating, 
        assignment_understanding, 
        practical_understanding, 
        satisfied,
        suggestions
        FROM feedback 
        WHERE student_name = ?
        AND date BETWEEN ? AND ?
        ORDER BY date DESC");
    $feedback_query->execute([$student_name, $start_date, $end_date]);
    $feedback_data = $feedback_query->fetchAll(PDO::FETCH_ASSOC);
}

// Prepare chart data
$chart_data = [];
$summary_data = [
    'total_attendance' => 0,
    'present_percentage' => 0,
    'average_exam_score' => 'N/A',
    'total_exams' => 0,
    'average_feedback_rating' => 'N/A',
    'total_feedback' => 0
];

if (!empty($performance_data)) {
    $attendance_counts = ['Present' => 0, 'Absent' => 0, 'Late' => 0];
    $exam_scores = [];
    $attendance_by_month = [];
    $exam_scores_by_month = [];
    
    foreach ($performance_data as $record) {
        $month = date('Y-m', strtotime($record['date']));
        
        if ($record['type'] === 'Attendance') {
            $attendance_counts[$record['status']]++;
            
            // For monthly breakdown
            if (!isset($attendance_by_month[$month])) {
                $attendance_by_month[$month] = ['Present' => 0, 'Absent' => 0, 'Late' => 0];
            }
            $attendance_by_month[$month][$record['status']]++;
        } elseif ($record['type'] === 'Exam' && $record['score'] !== null) {
            $exam_scores[] = $record['score'];
            
            // For monthly breakdown
            if (!isset($exam_scores_by_month[$month])) {
                $exam_scores_by_month[$month] = [];
            }
            $exam_scores_by_month[$month][] = $record['score'];
        }
    }
    
    // Prepare monthly attendance data
    $monthly_attendance_labels = [];
    $monthly_attendance_present = [];
    $monthly_attendance_absent = [];
    $monthly_attendance_late = [];
    
    foreach ($attendance_by_month as $month => $counts) {
        $monthly_attendance_labels[] = date('M Y', strtotime($month));
        $monthly_attendance_present[] = $counts['Present'];
        $monthly_attendance_absent[] = $counts['Absent'];
        $monthly_attendance_late[] = $counts['Late'];
    }
    
    // Prepare monthly exam data
    $monthly_exam_labels = [];
    $monthly_exam_scores = [];
    $monthly_exam_avg = [];
    
    foreach ($exam_scores_by_month as $month => $scores) {
        $monthly_exam_labels[] = date('M Y', strtotime($month));
        $monthly_exam_scores[] = $scores;
        $monthly_exam_avg[] = array_sum($scores) / count($scores);
    }
    
    $chart_data = [
        'attendance' => [
            'labels' => array_keys($attendance_counts),
            'data' => array_values($attendance_counts),
            'colors' => ['#4ade80', '#f87171', '#fbbf24'],
            'title' => 'Attendance Distribution',
            'type' => 'pie'
        ],
        'monthly_attendance' => [
            'labels' => $monthly_attendance_labels,
            'datasets' => [
                ['label' => 'Present', 'data' => $monthly_attendance_present, 'backgroundColor' => '#4ade80'],
                ['label' => 'Absent', 'data' => $monthly_attendance_absent, 'backgroundColor' => '#f87171'],
                ['label' => 'Late', 'data' => $monthly_attendance_late, 'backgroundColor' => '#fbbf24']
            ],
            'title' => 'Monthly Attendance Breakdown',
            'type' => 'bar'
        ],
        'exams' => !empty($exam_scores) ? [
            'labels' => array_map(function($i) { return 'Exam ' . ($i+1); }, array_keys($exam_scores)),
            'data' => $exam_scores,
            'colors' => ['#3b82f6'],
            'title' => 'Exam Scores',
            'type' => 'line'
        ] : null,
        'monthly_exams' => !empty($monthly_exam_avg) ? [
            'labels' => $monthly_exam_labels,
            'data' => $monthly_exam_avg,
            'colors' => ['#8b5cf6'],
            'title' => 'Monthly Exam Averages',
            'type' => 'line'
        ] : null
    ];
    
    // Calculate summary data
    $total_attendance = 0;
    $present_count = 0;
    $total_exams = 0;
    $total_score = 0;
    
    foreach ($performance_data as $record) {
        if ($record['type'] === 'Attendance') {
            $total_attendance++;
            if ($record['status'] === 'Present') $present_count++;
        } elseif ($record['type'] === 'Exam' && $record['score'] !== null) {
            $total_exams++;
            $total_score += $record['score'];
        }
    }
    
    // Calculate feedback averages if available
    $total_feedback = count($feedback_data);
    $feedback_ratings = [];
    
    if ($total_feedback > 0) {
        foreach ($feedback_data as $feedback) {
            $feedback_ratings[] = $feedback['class_rating'];
            $feedback_ratings[] = $feedback['assignment_understanding'];
            $feedback_ratings[] = $feedback['practical_understanding'];
            $feedback_ratings[] = $feedback['satisfied'];
        }
        
        $average_feedback_rating = array_sum($feedback_ratings) / count($feedback_ratings);
    }
    
    $summary_data = [
        'total_attendance' => $total_attendance,
        'present_percentage' => $total_attendance > 0 ? round(($present_count / $total_attendance) * 100) : 0,
        'average_exam_score' => $total_exams > 0 ? round($total_score / $total_exams, 1) : 'N/A',
        'total_exams' => $total_exams,
        'average_feedback_rating' => $total_feedback > 0 ? round($average_feedback_rating, 1) : 'N/A',
        'total_feedback' => $total_feedback
    ];
    
    // Prepare feedback chart data
    if ($total_feedback > 0) {
        $feedback_labels = array_map(function($item) { 
            return date('M d, Y', strtotime($item['date'])); 
        }, $feedback_data);
        
        $feedback_class = array_column($feedback_data, 'class_rating');
        $feedback_assignments = array_column($feedback_data, 'assignment_understanding');
        $feedback_practical = array_column($feedback_data, 'practical_understanding');
        $feedback_satisfaction = array_column($feedback_data, 'satisfied');
        
        $chart_data['feedback'] = [
            'labels' => $feedback_labels,
            'datasets' => [
                ['label' => 'Class Rating', 'data' => $feedback_class, 'borderColor' => '#3b82f6', 'backgroundColor' => '#3b82f620'],
                ['label' => 'Assignments', 'data' => $feedback_assignments, 'borderColor' => '#10b981', 'backgroundColor' => '#10b98120'],
                ['label' => 'Practical', 'data' => $feedback_practical, 'borderColor' => '#f59e0b', 'backgroundColor' => '#f59e0b20'],
                ['label' => 'Satisfaction', 'data' => $feedback_satisfaction, 'borderColor' => '#8b5cf6', 'backgroundColor' => '#8b5cf620']
            ],
            'title' => 'Feedback Ratings Over Time',
            'type' => 'line'
        ];
    }
}
?>
<div class="ml-64 p-8 transition-all duration-300">
    <!-- Main Navigation Tabs -->
    <div class="flex mb-6 border-b border-gray-200">
        <a href="trainers.php" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= basename($_SERVER['PHP_SELF']) === 'trainers.php' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-chalkboard-teacher mr-2"></i> Teachers
        </a>
        <a href="batches.php" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= basename($_SERVER['PHP_SELF']) === 'batches.php' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-users mr-2"></i> Batches
        </a>
        <a href="exams.php" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= basename($_SERVER['PHP_SELF']) === 'exams.php' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-graduation-cap mr-2"></i> Exams
        </a>
        <a href="workshops.php" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= basename($_SERVER['PHP_SELF']) === 'workshops.php' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-laptop-code mr-2"></i> Workshops
        </a>
        <a href="attendance.php" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= basename($_SERVER['PHP_SELF']) === 'attendance.php' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-calendar-check mr-2"></i> Attendance
        </a>
        <a href="feedbacks.php" class="px-4 py-2 font-medium text-sm rounded-t-lg transition-all duration-300 <?= basename($_SERVER['PHP_SELF']) === 'feedbacks.php' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-comment-alt mr-2"></i> Feedbacks
        </a>
    </div>
</div>

<div class="ml-64 p-8 transition-all duration-300">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800"><?= $report_title ?></h1>
        <div class="flex space-x-4">
            <button onclick="window.print()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors transform hover:scale-105">
                <i class="fas fa-print mr-2"></i> Print Report
            </button>
            <button onclick="exportToPDF()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors transform hover:scale-105">
                <i class="fas fa-file-pdf mr-2"></i> Export PDF
            </button>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8 transition-all hover:shadow-lg animate-fade-in">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Filter Performance Data</h2>
        <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                <select name="student_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <option value="">Select Student</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student['student_id'] ?>" <?= $student_id === $student['student_id'] ? 'selected' : '' ?>>
                            <?= $student['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">View</label>
                <select name="view" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <option value="overview" <?= $report_view === 'overview' ? 'selected' : '' ?>>Overview</option>
                    <option value="attendance" <?= $report_view === 'attendance' ? 'selected' : '' ?>>Attendance Analysis</option>
                    <option value="exams" <?= $report_view === 'exams' ? 'selected' : '' ?>>Exam Performance</option>
                    <option value="feedback" <?= $report_view === 'feedback' ? 'selected' : '' ?>>Feedback Analysis</option>
                </select>
            </div> -->
            
            <div class="md:col-span-3 grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="<?= $start_date ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date" value="<?= $end_date ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                </div>
            </div>
            
            <div class="md:col-span-3 flex justify-end space-x-4">
                <button type="reset" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition-colors transform hover:scale-105">
                    <i class="fas fa-redo mr-2"></i> Reset
                </button>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors transform hover:scale-105">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- View Tabs -->
    <div class="flex mb-6 border-b border-gray-200">
        <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'overview'])) ?>" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= $report_view === 'overview' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-chart-pie mr-2"></i> Overview
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'attendance'])) ?>" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= $report_view === 'attendance' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-calendar-check mr-2"></i> Attendance
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'exams'])) ?>" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= $report_view === 'exams' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-graduation-cap mr-2"></i> Exams
        </a>
        <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'feedback'])) ?>" class="px-4 py-2 font-medium text-sm rounded-t-lg transition-all duration-300 <?= $report_view === 'feedback' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-comment-alt mr-2"></i> Feedback
        </a>
    </div>

    <!-- Performance Report Section -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8 transition-all hover:shadow-lg animate-slide-up">
        <div class="flex justify-between items-center bg-gradient-to-r from-blue-50 to-indigo-50 p-6 border-b border-gray-200">
            <div>
                <h2 class="text-xl font-semibold text-gray-800"><?= $report_title ?></h2>
                <?php if (!empty($student_name)): ?>
                    <p class="text-sm text-gray-600 mt-1">Batch: <?= $batch_name ?> | Period: <?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?></p>
                <?php endif; ?>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Last updated: <?= date('M d, Y H:i') ?></span>
                <span class="animate-pulse h-2 w-2 rounded-full bg-green-500"></span>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6">
            <div class="bg-white rounded-lg shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-1">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Attendance</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $summary_data['total_attendance'] ?></h3>
                        <p class="text-xs text-gray-500 mt-1"><?= $summary_data['present_percentage'] ?>% present rate</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-calendar-check text-blue-600"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="h-1 w-full bg-gray-200 rounded-full">
                        <div class="h-1 bg-blue-600 rounded-full" style="width: <?= $summary_data['present_percentage'] ?>%"></div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-1">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Exams</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1">
                            <?= $summary_data['total_exams'] ?>
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Avg. score: <?= $summary_data['average_exam_score'] ?></p>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-full">
                        <i class="fas fa-graduation-cap text-indigo-600"></i>
                    </div>
                </div>
                <?php if (is_numeric($summary_data['average_exam_score'])): ?>
                <div class="mt-4">
                    <div class="h-1 w-full bg-gray-200 rounded-full">
                        <div class="h-1 bg-indigo-600 rounded-full" style="width: <?= $summary_data['average_exam_score'] ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-1">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Feedback Received</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1">
                            <?= $summary_data['total_feedback'] ?>
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Avg. rating: <?= $summary_data['average_feedback_rating'] ?>/5</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-comment-alt text-purple-600"></i>
                    </div>
                </div>
                <?php if (is_numeric($summary_data['average_feedback_rating'])): ?>
                <div class="mt-4">
                    <div class="h-1 w-full bg-gray-200 rounded-full">
                        <div class="h-1 bg-purple-600 rounded-full" style="width: <?= ($summary_data['average_feedback_rating'] / 5) * 100 ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-1">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Overall Performance</p>
                        <?php if ($summary_data['total_attendance'] > 0 && $summary_data['total_exams'] > 0): ?>
                            <?php 
                                $attendance_score = ($summary_data['present_percentage'] / 100) * 40;
                                $exam_score = ($summary_data['average_exam_score'] / 100) * 50;
                                $feedback_score = $summary_data['total_feedback'] > 0 ? ($summary_data['average_feedback_rating'] / 5) * 10 : 0;
                                $overall_score = $attendance_score + $exam_score + $feedback_score;
                                $performance_level = $overall_score >= 80 ? 'Excellent' : ($overall_score >= 60 ? 'Good' : ($overall_score >= 40 ? 'Average' : 'Needs Improvement'));
                                $performance_color = $overall_score >= 80 ? 'green' : ($overall_score >= 60 ? 'blue' : ($overall_score >= 40 ? 'yellow' : 'red'));
                            ?>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= round($overall_score) ?>%</h3>
                            <p class="text-xs text-<?= $performance_color ?>-600 font-medium mt-1">
                                <i class="fas fa-<?= $performance_level === 'Excellent' ? 'trophy' : ($performance_level === 'Good' ? 'thumbs-up' : ($performance_level === 'Average' ? 'chart-line' : 'exclamation-triangle')) ?> mr-1"></i>
                                <?= $performance_level ?>
                            </p>
                        <?php else: ?>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">N/A</h3>
                            <p class="text-xs text-gray-500 mt-1">Insufficient data</p>
                        <?php endif; ?>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-chart-line text-green-600"></i>
                    </div>
                </div>
                <?php if (isset($overall_score)): ?>
                <div class="mt-4">
                    <div class="h-1 w-full bg-gray-200 rounded-full">
                        <div class="h-1 bg-green-600 rounded-full" style="width: <?= $overall_score ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Overview View -->
        <?php if ($report_view === 'overview'): ?>
            <!-- Chart Visualization -->
            <?php if (!empty($chart_data)): ?>
            <div class="bg-white p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-50 rounded-lg p-4 transition-all hover:shadow-md">
                        <h3 class="text-lg font-medium text-gray-700 mb-4"><?= $chart_data['attendance']['title'] ?></h3>
                        <div class="h-64">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                    
                    <?php if (!empty($chart_data['monthly_attendance'])): ?>
                    <div class="bg-gray-50 rounded-lg p-4 transition-all hover:shadow-md">
                        <h3 class="text-lg font-medium text-gray-700 mb-4"><?= $chart_data['monthly_attendance']['title'] ?></h3>
                        <div class="h-64">
                            <canvas id="monthlyAttendanceChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($chart_data['exams'])): ?>
                    <div class="bg-gray-50 rounded-lg p-4 transition-all hover:shadow-md">
                        <h3 class="text-lg font-medium text-gray-700 mb-4"><?= $chart_data['exams']['title'] ?></h3>
                        <div class="h-64">
                            <canvas id="examsChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($chart_data['monthly_exams'])): ?>
                    <div class="bg-gray-50 rounded-lg p-4 transition-all hover:shadow-md">
                        <h3 class="text-lg font-medium text-gray-700 mb-4"><?= $chart_data['monthly_exams']['title'] ?></h3>
                        <div class="h-64">
                            <canvas id="monthlyExamsChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($chart_data['feedback'])): ?>
                    <div class="bg-gray-50 rounded-lg p-4 transition-all hover:shadow-md md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-700 mb-4"><?= $chart_data['feedback']['title'] ?></h3>
                        <div class="h-64">
                            <canvas id="feedbackChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Performance Data Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($performance_data)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No performance records found matching your criteria
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($performance_data as $index => $row): ?>
                                <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= date('M d, Y', strtotime($row['date'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= $row['type'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if ($row['score'] !== null): ?>
                                            <span class="font-bold <?= $row['score'] >= 80 ? 'text-green-600' : ($row['score'] >= 60 ? 'text-blue-600' : 'text-red-600') ?>">
                                                <?= $row['score'] ?>
                                            </span>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php 
                                            $color = [
                                                'Present' => 'green',
                                                'Absent' => 'red',
                                                'Late' => 'yellow',
                                                'Completed' => 'blue',
                                                'Malpractice' => 'red'
                                            ][$row['status']] ?? 'gray';
                                            echo '<span class="px-2 py-1 rounded-full text-xs font-medium bg-'.$color.'-100 text-'.$color.'-800">'.$row['status'].'</span>';
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($row['remarks'] ?? 'N/A') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= $row['batch_id'] ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Attendance View -->
        <?php if ($report_view === 'attendance'): ?>
            <div class="p-6">
                <?php if (!empty($chart_data['attendance']) || !empty($chart_data['monthly_attendance'])): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <?php if (!empty($chart_data['attendance'])): ?>
                    <div class="bg-gray-50 rounded-lg p-4 transition-all hover:shadow-md">
                        <h3 class="text-lg font-medium text-gray-700 mb-4">Attendance Distribution</h3>
                        <div class="h-64">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($chart_data['monthly_attendance'])): ?>
                    <div class="bg-gray-50 rounded-lg p-4 transition-all hover:shadow-md">
                        <h3 class="text-lg font-medium text-gray-700 mb-4">Monthly Attendance Breakdown</h3>
                        <div class="h-64">
                            <canvas id="monthlyAttendanceChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Attendance Details</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                    $attendance_records = array_filter($performance_data, function($item) {
                                        return $item['type'] === 'Attendance';
                                    });
                                ?>
                                <?php if (empty($attendance_records)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                            No attendance records found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($attendance_records as $index => $row): ?>
                                        <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('M d, Y', strtotime($row['date'])) ?>
                                            </td>
                                                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php 
                                                    $color = [
                                                        'Present' => 'green',
                                                        'Absent' => 'red',
                                                        'Late' => 'yellow'
                                                    ][$row['status']] ?? 'gray';
                                                    echo '<span class="px-2 py-1 rounded-full text-xs font-medium bg-'.$color.'-100 text-'.$color.'-800">'.$row['status'].'</span>';
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($row['remarks'] ?? 'N/A') ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= $row['batch_id'] ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Exams View -->
        <?php if ($report_view === 'exams'): ?>
            <div class="p-6">
                <?php if (!empty($chart_data['exams']) || !empty($chart_data['monthly_exams'])): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <?php if (!empty($chart_data['exams'])): ?>
                    <div class="bg-gray-50 rounded-lg p-4 transition-all hover:shadow-md">
                        <h3 class="text-lg font-medium text-gray-700 mb-4">Exam Scores</h3>
                        <div class="h-64">
                            <canvas id="examsChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($chart_data['monthly_exams'])): ?>
                    <div class="bg-gray-50 rounded-lg p-4 transition-all hover:shadow-md">
                        <h3 class="text-lg font-medium text-gray-700 mb-4">Monthly Exam Averages</h3>
                        <div class="h-64">
                            <canvas id="monthlyExamsChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Exam Details</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                    $exam_records = array_filter($performance_data, function($item) {
                                        return $item['type'] === 'Exam';
                                    });
                                ?>
                                <?php if (empty($exam_records)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            No exam records found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($exam_records as $index => $row): ?>
                                        <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('M d, Y', strtotime($row['date'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php if ($row['score'] !== null): ?>
                                                    <span class="font-bold <?= $row['score'] >= 80 ? 'text-green-600' : ($row['score'] >= 60 ? 'text-blue-600' : 'text-red-600') ?>">
                                                        <?= $row['score'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php 
                                                    $color = [
                                                        'Completed' => 'blue',
                                                        'Malpractice' => 'red'
                                                    ][$row['status']] ?? 'gray';
                                                    echo '<span class="px-2 py-1 rounded-full text-xs font-medium bg-'.$color.'-100 text-'.$color.'-800">'.$row['status'].'</span>';
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($row['remarks'] ?? 'N/A') ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= $row['batch_id'] ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Feedback View -->
        <?php if ($report_view === 'feedback'): ?>
            <div class="p-6">
                <?php if (!empty($chart_data['feedback'])): ?>
                <div class="bg-gray-50 rounded-lg p-4 transition-all hover:shadow-md mb-8">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Feedback Ratings Over Time</h3>
                    <div class="h-64">
                        <canvas id="feedbackChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Feedback Details</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class Rating</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignment</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Practical</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satisfaction</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Suggestions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($feedback_data)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No feedback records found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($feedback_data as $index => $row): ?>
                                        <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('M d, Y', strtotime($row['date'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex items-center">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <svg class="w-4 h-4 <?= $i <= $row['class_rating'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                        </svg>
                                                    <?php endfor; ?>
                                                    <span class="ml-1 text-xs text-gray-500">(<?= $row['class_rating'] ?>/5)</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex items-center">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <svg class="w-4 h-4 <?= $i <= $row['assignment_understanding'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                        </svg>
                                                    <?php endfor; ?>
                                                    <span class="ml-1 text-xs text-gray-500">(<?= $row['assignment_understanding'] ?>/5)</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex items-center">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <svg class="w-4 h-4 <?= $i <= $row['practical_understanding'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                        </svg>
                                                    <?php endfor; ?>
                                                    <span class="ml-1 text-xs text-gray-500">(<?= $row['practical_understanding'] ?>/5)</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex items-center">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <svg class="w-4 h-4 <?= $i <= $row['satisfied'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                        </svg>
                                                    <?php endfor; ?>
                                                    <span class="ml-1 text-xs text-gray-500">(<?= $row['satisfied'] ?>/5)</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <?= htmlspecialchars($row['suggestions'] ?? 'No suggestions') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Function to render charts
function renderCharts() {
    <?php if (!empty($chart_data['attendance'])): ?>
    // Attendance Chart
    new Chart(document.getElementById('attendanceChart'), {
        type: '<?= $chart_data['attendance']['type'] ?>',
        data: {
            labels: <?= json_encode($chart_data['attendance']['labels']) ?>,
            datasets: [{
                data: <?= json_encode($chart_data['attendance']['data']) ?>,
                backgroundColor: <?= json_encode($chart_data['attendance']['colors']) ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if (!empty($chart_data['monthly_attendance'])): ?>
    // Monthly Attendance Chart
    new Chart(document.getElementById('monthlyAttendanceChart'), {
        type: '<?= $chart_data['monthly_attendance']['type'] ?>',
        data: {
            labels: <?= json_encode($chart_data['monthly_attendance']['labels']) ?>,
            datasets: <?= json_encode($chart_data['monthly_attendance']['datasets']) ?>
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if (!empty($chart_data['exams'])): ?>
    // Exams Chart
    new Chart(document.getElementById('examsChart'), {
        type: '<?= $chart_data['exams']['type'] ?>',
        data: {
            labels: <?= json_encode($chart_data['exams']['labels']) ?>,
            datasets: [{
                label: 'Score',
                data: <?= json_encode($chart_data['exams']['data']) ?>,
                backgroundColor: <?= json_encode($chart_data['exams']['colors']) ?>,
                borderColor: <?= json_encode($chart_data['exams']['colors']) ?>,
                borderWidth: 2,
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if (!empty($chart_data['monthly_exams'])): ?>
    // Monthly Exams Chart
    new Chart(document.getElementById('monthlyExamsChart'), {
        type: '<?= $chart_data['monthly_exams']['type'] ?>',
        data: {
            labels: <?= json_encode($chart_data['monthly_exams']['labels']) ?>,
            datasets: [{
                label: 'Average Score',
                data: <?= json_encode($chart_data['monthly_exams']['data']) ?>,
                backgroundColor: <?= json_encode($chart_data['monthly_exams']['colors']) ?>,
                borderColor: <?= json_encode($chart_data['monthly_exams']['colors']) ?>,
                borderWidth: 2,
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if (!empty($chart_data['feedback'])): ?>
    // Feedback Chart
    new Chart(document.getElementById('feedbackChart'), {
        type: '<?= $chart_data['feedback']['type'] ?>',
        data: {
            labels: <?= json_encode($chart_data['feedback']['labels']) ?>,
            datasets: <?= json_encode($chart_data['feedback']['datasets']) ?>
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    <?php endif; ?>
}

// Export to PDF function
function exportToPDF() {
    // In a real implementation, you would use a library like jsPDF or make an AJAX call to a PDF generation endpoint
    alert('PDF export functionality would be implemented here. In a real application, this would generate a PDF report.');
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    renderCharts();
});
</script>

<?php require_once '../footer.php'; ?>