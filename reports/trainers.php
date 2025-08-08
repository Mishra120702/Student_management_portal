<?php
require_once '../db_connection.php';
require_once '../header.php';
require_once '../sidebar.php';

// Get filter parameters
$trainer_id = $_GET['trainer_id'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_view = $_GET['view'] ?? 'overview';

// Get all trainers for filter dropdown
$trainers_query = $db->query("SELECT id, name FROM trainers ORDER BY name");
$trainers = $trainers_query->fetchAll(PDO::FETCH_ASSOC);

// Initialize performance data
$performance_data = [];
$trainer_name = '';
$report_title = 'Trainer Performance Report';

// Get trainer performance data if trainer selected
if (!empty($trainer_id)) {
    $trainer_info = $db->prepare("SELECT name FROM trainers WHERE id = ?");
    $trainer_info->execute([$trainer_id]);
    $trainer_data = $trainer_info->fetch(PDO::FETCH_ASSOC);
    $trainer_name = $trainer_data['name'];
    $report_title = "Performance Report for " . $trainer_name;
    
    // Get batches handled by this trainer
    $batches_query = $db->prepare("SELECT batch_id, course_name FROM batches WHERE batch_mentor_id = ?");
    $batches_query->execute([$trainer_id]);
    $batches = $batches_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Get schedule data
    $schedule_query = $db->prepare("SELECT 
        schedule_date as date,
        'Class' as type,
        topic,
        description,
        batch_id,
        is_cancelled,
        cancellation_reason
        FROM schedule 
        WHERE created_by = ?
        AND schedule_date BETWEEN ? AND ?
        ORDER BY schedule_date DESC");
    $schedule_query->execute([$trainer_id, $start_date, $end_date]);
    $schedule_data = $schedule_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Get exams conducted by this trainer
    $exams_query = $db->prepare("SELECT 
        e.exam_date as date,
        'Exam' as type,
        e.exam_id,
        e.batch_id,
        e.duration,
        COUNT(es.student_name) as student_count,
        AVG(es.score) as avg_score
        FROM proctored_exams e
        JOIN exam_students es ON e.exam_id = es.exam_id
        WHERE e.proctor_name = ?
        AND e.exam_date BETWEEN ? AND ?
        GROUP BY e.exam_id
        ORDER BY e.exam_date DESC");
    $exams_query->execute([$trainer_name, $start_date, $end_date]);
    $exams_data = $exams_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine results
    $performance_data = array_merge($schedule_data, $exams_data);
    
    // Sort by date
    usort($performance_data, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // Get feedback data
    $feedback_query = $db->prepare("SELECT 
        date, 
        AVG(class_rating) as avg_class_rating,
        AVG(assignment_understanding) as avg_assignment_rating,
        AVG(practical_understanding) as avg_practical_rating,
        AVG(satisfied) as avg_satisfaction,
        COUNT(id) as feedback_count
        FROM feedback 
        WHERE batch_id IN (SELECT batch_id FROM batches WHERE batch_mentor_id = ?)
        AND date BETWEEN ? AND ?
        GROUP BY date
        ORDER BY date DESC");
    $feedback_query->execute([$trainer_id, $start_date, $end_date]);
    $feedback_data = $feedback_query->fetchAll(PDO::FETCH_ASSOC);
}

// Prepare chart data
$chart_data = [];
$summary_data = [
    'total_classes' => 0,
    'cancelled_classes' => 0,
    'total_exams' => 0,
    'average_exam_score' => 'N/A',
    'average_feedback_rating' => 'N/A',
    'total_feedback' => 0
];

if (!empty($performance_data)) {
    $class_counts = ['Completed' => 0, 'Cancelled' => 0];
    $class_by_month = [];
    $exam_scores = [];
    $exam_counts_by_month = [];
    
    foreach ($performance_data as $record) {
        $month = date('Y-m', strtotime($record['date']));
        
        if ($record['type'] === 'Class') {
            $status = $record['is_cancelled'] ? 'Cancelled' : 'Completed';
            $class_counts[$status]++;
            
            // For monthly breakdown
            if (!isset($class_by_month[$month])) {
                $class_by_month[$month] = ['Completed' => 0, 'Cancelled' => 0];
            }
            $class_by_month[$month][$status]++;
        } elseif ($record['type'] === 'Exam' && isset($record['avg_score'])) {
            $exam_scores[] = $record['avg_score'];
            
            // For monthly breakdown
            if (!isset($exam_counts_by_month[$month])) {
                $exam_counts_by_month[$month] = ['count' => 0, 'total_score' => 0];
            }
            $exam_counts_by_month[$month]['count']++;
            $exam_counts_by_month[$month]['total_score'] += $record['avg_score'];
        }
    }
    
    // Prepare monthly class data
    $monthly_class_labels = [];
    $monthly_class_completed = [];
    $monthly_class_cancelled = [];
    
    foreach ($class_by_month as $month => $counts) {
        $monthly_class_labels[] = date('M Y', strtotime($month));
        $monthly_class_completed[] = $counts['Completed'];
        $monthly_class_cancelled[] = $counts['Cancelled'];
    }
    
    // Prepare monthly exam data
    $monthly_exam_labels = [];
    $monthly_exam_avg = [];
    
    foreach ($exam_counts_by_month as $month => $data) {
        $monthly_exam_labels[] = date('M Y', strtotime($month));
        $monthly_exam_avg[] = $data['total_score'] / $data['count'];
    }
    
    $chart_data = [
        'classes' => [
            'labels' => array_keys($class_counts),
            'data' => array_values($class_counts),
            'colors' => ['#4ade80', '#f87171'],
            'title' => 'Class Distribution',
            'type' => 'pie'
        ],
        'monthly_classes' => [
            'labels' => $monthly_class_labels,
            'datasets' => [
                ['label' => 'Completed', 'data' => $monthly_class_completed, 'backgroundColor' => '#4ade80'],
                ['label' => 'Cancelled', 'data' => $monthly_class_cancelled, 'backgroundColor' => '#f87171']
            ],
            'title' => 'Monthly Class Breakdown',
            'type' => 'bar'
        ],
        'exams' => !empty($exam_scores) ? [
            'labels' => array_map(function($i) { return 'Exam ' . ($i+1); }, array_keys($exam_scores)),
            'data' => $exam_scores,
            'colors' => ['#3b82f6'],
            'title' => 'Exam Averages',
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
    $total_classes = array_sum($class_counts);
    $cancelled_classes = $class_counts['Cancelled'];
    $total_exams = count($exam_scores);
    $total_score = array_sum($exam_scores);
    
    $summary_data = [
        'total_classes' => $total_classes,
        'cancelled_classes' => $cancelled_classes,
        'total_exams' => $total_exams,
        'average_exam_score' => $total_exams > 0 ? round($total_score / $total_exams, 1) : 'N/A',
    ];
    
    // Calculate feedback averages if available
    if (!empty($feedback_data)) {
        $total_feedback = 0;
        $total_rating = 0;
        
        foreach ($feedback_data as $feedback) {
            $total_feedback += $feedback['feedback_count'];
            $total_rating += ($feedback['avg_class_rating'] + $feedback['avg_assignment_rating'] + 
                             $feedback['avg_practical_rating'] + $feedback['avg_satisfaction']) / 4;
        }
        
        $summary_data['total_feedback'] = $total_feedback;
        $summary_data['average_feedback_rating'] = $total_feedback > 0 ? round($total_rating / count($feedback_data), 1) : 'N/A';
        
        // Prepare feedback chart data
        $feedback_labels = array_map(function($item) { 
            return date('M d, Y', strtotime($item['date'])); 
        }, $feedback_data);
        
        $feedback_class = array_column($feedback_data, 'avg_class_rating');
        $feedback_assignments = array_column($feedback_data, 'avg_assignment_rating');
        $feedback_practical = array_column($feedback_data, 'avg_practical_rating');
        $feedback_satisfaction = array_column($feedback_data, 'avg_satisfaction');
        
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
    <?php include 'navbar.php'?>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Trainer</label>
                <select name="trainer_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <option value="">Select Trainer</option>
                    <?php foreach ($trainers as $trainer): ?>
                        <option value="<?= $trainer['id'] ?>" <?= $trainer_id == $trainer['id'] ? 'selected' : '' ?>>
                            <?= $trainer['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
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
        <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'classes'])) ?>" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= $report_view === 'classes' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-calendar-alt mr-2"></i> Classes
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
                <?php if (!empty($trainer_name)): ?>
                    <p class="text-sm text-gray-600 mt-1">Period: <?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?></p>
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
                        <p class="text-sm font-medium text-gray-500">Total Classes</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $summary_data['total_classes'] ?></h3>
                        <p class="text-xs text-gray-500 mt-1"><?= $summary_data['cancelled_classes'] ?> cancelled</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-chalkboard text-blue-600"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="h-1 w-full bg-gray-200 rounded-full">
                        <div class="h-1 bg-blue-600 rounded-full" style="width: <?= $summary_data['total_classes'] > 0 ? round((($summary_data['total_classes'] - $summary_data['cancelled_classes']) / $summary_data['total_classes']) * 100) : 0 ?>%"></div>
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
                        <?php if ($summary_data['total_classes'] > 0 && $summary_data['total_feedback'] > 0): ?>
                            <?php 
                                $class_score = (($summary_data['total_classes'] - $summary_data['cancelled_classes']) / $summary_data['total_classes']) * 40;
                                $feedback_score = $summary_data['total_feedback'] > 0 ? ($summary_data['average_feedback_rating'] / 5) * 40 : 0;
                                $exam_score = $summary_data['total_exams'] > 0 ? ($summary_data['average_exam_score'] / 100) * 20 : 0;
                                $overall_score = $class_score + $feedback_score + $exam_score;
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
                        <h3 class="text-lg font-medium text-gray-700 mb-4"><?= $chart_data['classes']['title'] ?></h3>
                        <div class="h-64">
                            <canvas id="classesChart"></canvas>
                        </div>
                    </div>
                    
                    <?php if (!empty($chart_data['monthly_classes'])): ?>
                    <div class="bg-gray-50 rounded-lg p-4 transition-all hover:shadow-md">
                        <h3 class="text-lg font-medium text-gray-700 mb-4"><?= $chart_data['monthly_classes']['title'] ?></h3>
                        <div class="h-64">
                            <canvas id="monthlyClassesChart"></canvas>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($performance_data)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
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
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php if ($row['type'] === 'Class'): ?>
                                            <div class="font-medium"><?= $row['topic'] ?></div>
                                            <div class="text-xs text-gray-500"><?= $row['description'] ?? 'No description' ?></div>
                                        <?php else: ?>
                                            <div class="font-medium">Exam ID: <?= $row['exam_id'] ?></div>
                                            <div class="text-xs text-gray-500">
                                                Duration: <?= $row['duration'] ?> mins | 
                                                Students: <?= $row['student_count'] ?> | 
                                                Avg Score: <?= $row['avg_score'] ?? 'N/A' ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= $row['batch_id'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php 
                                            if ($row['type'] === 'Class') {
                                                $color = $row['is_cancelled'] ? 'red' : 'green';
                                                $status = $row['is_cancelled'] ? 'Cancelled' : 'Completed';
                                                echo '<span class="px-2 py-1 rounded-full text-xs font-medium bg-'.$color.'-100 text-'.$color.'-800">'.$status.'</span>';
                                            } else {
                                                echo '<span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Conducted</span>';
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Classes View -->
        <?php if ($report_view === 'classes'): ?>
            <div class="p-6">
                <?php if (!empty($chart_data['classes']) || !empty($chart_data['monthly_classes'])): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <?php if (!empty($chart_data['classes'])): ?>
                    <div class="bg-gray-50 rounded-lg p-4 transition-all hover:shadow-md">
                        <h3 class="text-lg font-medium text-gray-700 mb-4">Class Distribution</h3>
                        <div class="h-64">
                            <canvas id="classesChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($chart_data['monthly_classes'])): ?>
                    <div class="bg-gray-50 rounded-lg p-4 transition-all hover:shadow-md">
                        <h3 class="text-lg font-medium text-gray-700 mb-4">Monthly Class Breakdown</h3>
                        <div class="h-64">
                            <canvas id="monthlyClassesChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Class Details</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Topic</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                    $class_records = array_filter($performance_data, function($item) {
                                        return $item['type'] === 'Class';
                                    });
                                ?>
                                <?php if (empty($class_records)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            No class records found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($class_records as $index => $row): ?>
                                        <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> hover:bg-blue-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('M d, Y', strtotime($row['date'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= $row['topic'] ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <?= $row['description'] ?? 'No description' ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= $row['batch_id'] ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php 
                                                    $color = $row['is_cancelled'] ? 'red' : 'green';
                                                    $status = $row['is_cancelled'] ? 'Cancelled' : 'Completed';
                                                    echo '<span class="px-2 py-1 rounded-full text-xs font-medium bg-'.$color.'-100 text-'.$color.'-800">'.$status.'</span>';
                                                ?>
                                                <?php if ($row['is_cancelled'] && !empty($row['cancellation_reason'])): ?>
                                                    <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($row['cancellation_reason']) ?></div>
                                                <?php endif; ?>
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
                        <h3 class="text-lg font-medium text-gray-700 mb-4">Exam Averages</h3>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exam ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
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
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
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
                                                <?= $row['exam_id'] ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <div class="font-medium">Duration: <?= $row['duration'] ?> mins</div>
                                                <div class="text-xs text-gray-500">
                                                    Students: <?= $row['student_count'] ?> | 
                                                    Avg Score: <?= $row['avg_score'] ?? 'N/A' ?>
                                                </div>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responses</th>
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
                                                        <svg class="w-4 h-4 <?= $i <= $row['avg_class_rating'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                        </svg>
                                                    <?php endfor; ?>
                                                    <span class="ml-1 text-xs text-gray-500">(<?= round($row['avg_class_rating'], 1) ?>/5)</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex items-center">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <svg class="w-4 h-4 <?= $i <= $row['avg_assignment_rating'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                        </svg>
                                                    <?php endfor; ?>
                                                    <span class="ml-1 text-xs text-gray-500">(<?= round($row['avg_assignment_rating'], 1) ?>/5)</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex items-center">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <svg class="w-4 h-4 <?= $i <= $row['avg_practical_rating'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                        </svg>
                                                    <?php endfor; ?>
                                                    <span class="ml-1 text-xs text-gray-500">(<?= round($row['avg_practical_rating'], 1) ?>/5)</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex items-center">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <svg class="w-4 h-4 <?= $i <= $row['avg_satisfaction'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                        </svg>
                                                    <?php endfor; ?>
                                                    <span class="ml-1 text-xs text-gray-500">(<?= round($row['avg_satisfaction'], 1) ?>/5)</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= $row['feedback_count'] ?>
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
    <?php if (!empty($chart_data['classes'])): ?>
    // Classes Chart
    new Chart(document.getElementById('classesChart'), {
        type: '<?= $chart_data['classes']['type'] ?>',
        data: {
            labels: <?= json_encode($chart_data['classes']['labels']) ?>,
            datasets: [{
                data: <?= json_encode($chart_data['classes']['data']) ?>,
                backgroundColor: <?= json_encode($chart_data['classes']['colors']) ?>,
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
    
    <?php if (!empty($chart_data['monthly_classes'])): ?>
    // Monthly Classes Chart
    new Chart(document.getElementById('monthlyClassesChart'), {
        type: '<?= $chart_data['monthly_classes']['type'] ?>',
        data: {
            labels: <?= json_encode($chart_data['monthly_classes']['labels']) ?>,
            datasets: <?= json_encode($chart_data['monthly_classes']['datasets']) ?>
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
                label: 'Average Score',
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
            data: <?= json_encode($chart_data['monthly_exams']['data']) ?>,
            backgroundColor: <?= json_encode($chart_data['monthly_exams']['colors']) ?>,
            borderColor: <?= json_encode($chart_data['monthly_exams']['colors']) ?>,
            borderWidth: 2,
            tension: 0.1,
            fill: false
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