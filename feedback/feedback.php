<?php
// Database connection (same as your batch_list.php)
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

// Get batches for dropdown
$batches = $db->query("SELECT batch_id, course_name FROM batches")->fetchAll(PDO::FETCH_ASSOC);

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $stmt = $db->prepare("INSERT INTO feedback (date, student_name, email, is_regular, batch_id, course_name, 
                         class_rating, assignment_understanding, practical_understanding, satisfied, 
                         suggestions, feedback_text) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        date('Y-m-d'),
        $_POST['student_name'],
        $_POST['email'],
        $_POST['regular_in_class'],
        $_POST['batch_id'],
        $_POST['course_name'],
        $_POST['class_rating'],
        $_POST['assignment_understanding'],
        $_POST['practical_understanding'],
        $_POST['satisfied'],
        $_POST['suggestions'],
        $_POST['feedback_text']
    ]);
    $success = true;
}

// Get filter parameters
$batch_filter = $_GET['batch'] ?? '';
$rating_filter = $_GET['rating'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$satisfaction_filter = $_GET['satisfaction'] ?? '';
$regularity_filter = $_GET['regularity'] ?? '';

// Build query with filters
$query = "SELECT * FROM feedback WHERE 1=1";
$params = [];

if (!empty($batch_filter)) {
    $query .= " AND batch_id = ?";
    $params[] = $batch_filter;
}

if (!empty($rating_filter)) {
    $query .= " AND (class_rating = ? OR assignment_understanding = ? OR practical_understanding = ?)";
    $params[] = $rating_filter;
    $params[] = $rating_filter;
    $params[] = $rating_filter;
}

if (!empty($date_from)) {
    $query .= " AND date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND date <= ?";
    $params[] = $date_to;
}

if (!empty($satisfaction_filter)) {
    $query .= " AND satisfied = ?";
    $params[] = $satisfaction_filter;
}

if (!empty($regularity_filter)) {
    $query .= " AND is_regular = ?";
    $params[] = $regularity_filter;
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics for dashboard
$summary = $db->query("
    SELECT 
        COUNT(*) as total_feedback,
        AVG(class_rating) as avg_class_rating,
        AVG(assignment_understanding) as avg_assignment_rating,
        AVG(practical_understanding) as avg_practical_rating,
        SUM(CASE WHEN satisfied = 'Yes' THEN 1 ELSE 0 END) as satisfied_count,
        SUM(CASE WHEN is_regular = 'Yes' THEN 1 ELSE 0 END) as regular_count
    FROM feedback
")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASD Academy - Feedback System</title>
    <!-- Primary Tailwind CDN with fallback -->
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <!-- Font Awesome from jsDelivr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <style>
    /* Base styles */
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f5f7fa;
        color: #333;
        line-height: 1.6;
    }
    
    .main-content {
        margin-left: 16rem; /* Match sidebar width */
        padding: 2rem;
        width: calc(100% - 16rem);
        box-sizing: border-box;
        transition: all 0.3s ease;
    }
    
    h2, h3, h4 {
        color: #2c3e50;
        margin-bottom: 20px;
        font-weight: 600;
    }
    
    h3 {
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #e0e6ed;
    }
    
    h4 {
        margin: 15px 0 10px 0;
        font-size: 16px;
    }
    
    .card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        padding: 25px;
        margin-bottom: 30px;
        border: 1px solid #e0e6ed;
        width: 100%;
        box-sizing: border-box;
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }
    
    .minimal-input {
        border: 1px solid #d3dce6;
        border-radius: 6px;
        padding: 10px 15px;
        margin-bottom: 15px;
        font-size: 14px;
        width: 100%;
        font-family: 'Inter', sans-serif;
        transition: all 0.3s;
        box-sizing: border-box;
    }
    
    .minimal-input:focus {
        border-color: #3498db;
        outline: none;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }
    
    textarea.minimal-input {
        min-height: 100px;
        resize: vertical;
    }
    
    .btn-blue {
        background-color: #3498db;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 12px 20px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        font-family: 'Inter', sans-serif;
        transition: all 0.3s;
    }
    
    .btn-blue:hover {
        background-color: #2980b9;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(41, 128, 185, 0.2);
    }
    
    .btn-gray {
        background-color: #95a5a6;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 12px 20px;
        cursor: pointer;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        transition: all 0.3s;
    }
    
    .btn-gray:hover {
        background-color: #7f8c8d;
        transform: translateY(-1px);
    }
    
    /* Star rating */
    .star-rating {
        font-size: 24px;
        margin: 10px 0 20px;
        display: inline-block;
    }
    
    .star-rating span {
        cursor: pointer;
        color: #e0e6ed;
        transition: all 0.2s;
        padding: 0 2px;
    }
    
    .star-rating span:hover,
    .star-rating span.active {
        color: #f39c12;
        transform: scale(1.2);
    }
    
    .stars-inline {
        color: #f39c12;
        font-size: 16px;
        white-space: nowrap;
    }
    
    /* Success message */
    .success-message {
        color: #27ae60;
        margin: -10px 0 20px;
        padding: 10px;
        background-color: rgba(39, 174, 96, 0.1);
        border-radius: 4px;
        font-weight: 500;
        animation: fadeIn 0.5s ease-in-out;
    }
    
    /* Table styles */
    #feedbackTable {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        font-size: 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    #feedbackTable thead th {
        background-color: #3498db;
        color: white;
        font-weight: 500;
        padding: 12px 10px;
        text-align: left;
        position: sticky;
        top: 0;
    }
    
    #feedbackTable tbody td {
        padding: 10px;
        border-bottom: 1px solid #e0e6ed;
        vertical-align: top;
        word-break: break-word;
    }
    
    #feedbackTable tr:nth-child(even) {
        background-color: #f8fafc;
    }
    
    #feedbackTable tr {
        transition: all 0.2s ease;
    }
    
    #feedbackTable tr:hover {
        background-color: #eaf2f8;
        transform: scale(1.005);
    }
    
    /* Make some columns narrower */
    #feedbackTable th:nth-child(1), /* Date */
    #feedbackTable td:nth-child(1) {
        width: 90px;
    }
    
    #feedbackTable th:nth-child(4), /* Regular */
    #feedbackTable td:nth-child(4),
    #feedbackTable th:nth-child(5), /* Batch */
    #feedbackTable td:nth-child(5),
    #feedbackTable th:nth-child(10), /* Satisfied */
    #feedbackTable td:nth-child(10) {
        width: 80px;
    }
    
    #feedbackTable th:nth-child(6), /* Course */
    #feedbackTable td:nth-child(6) {
        width: 120px;
    }
    
    #feedbackTable th:nth-child(7), /* Class Rating */
    #feedbackTable td:nth-child(7),
    #feedbackTable th:nth-child(8), /* Assignment */
    #feedbackTable td:nth-child(8),
    #feedbackTable th:nth-child(9), /* Practical */
    #feedbackTable td:nth-child(9) {
        width: 70px;
    }
    
    /* Action column */
    #feedbackTable th:nth-child(13),
    #feedbackTable td:nth-child(13) {
        width: 180px;
    }
    
    /* Star ratings in table */
    .stars-inline {
        color: #f39c12;
        font-size: 14px;
        white-space: nowrap;
        display: inline-block;
        min-width: 70px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 992px) {
        .main-content {
            margin-left: 0;
            width: 100%;
            padding: 1rem;
            padding-top: 60px; /* Make space for mobile header */
        }
        
        #feedbackTable {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        #feedbackTable thead th,
        #feedbackTable tbody td {
            padding: 8px 6px;
        }
    }
    
    /* Word counter styles */
    .word-counter {
        font-size: 12px;
        color: #95a5a6;
        text-align: right;
        margin-top: -10px;
        margin-bottom: 15px;
    }
    
    .word-counter.limit-reached {
        color: #e74c3c;
        font-weight: bold;
    }
    
    .filter-controls {
        margin-bottom: 15px; 
        display: flex; 
        align-items: center; 
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .filter-controls form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .page-header {
        background: white;
        padding: 1rem 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .page-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    /* Stats cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        text-align: center;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.12);
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #3498db;
        margin: 10px 0;
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: #7f8c8d;
    }
    
    /* Chart container */
    .chart-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    /* Filter panel */
    .filter-panel {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        margin-bottom: 30px;
        animation: slideDown 0.5s ease-out;
    }
    
    .filter-panel h3 {
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .filter-panel h3 i {
        transition: transform 0.3s ease;
    }
    
    .filter-panel.collapsed h3 i {
        transform: rotate(-90deg);
    }
    
    .filter-panel.collapsed .filter-content {
        display: none;
    }
    
    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    
    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Pulse animation for important elements */
    .pulse {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(52, 152, 219, 0); }
        100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0); }
    }
    
    /* Tooltip styles */
    .tooltip {
        position: relative;
        display: inline-block;
    }
    
    .tooltip .tooltiptext {
        visibility: hidden;
        width: 200px;
        background-color: #555;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px;
        position: absolute;
        z-index: 1;
        bottom: 125%;
        left: 50%;
        margin-left: -100px;
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .tooltip:hover .tooltiptext {
        visibility: visible;
        opacity: 1;
    }
    
    /* Badge styles */
    .badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        margin-left: 5px;
    }
    
    .badge-success {
        background-color: #27ae60;
        color: white;
    }
    
    .badge-warning {
        background-color: #f39c12;
        color: white;
    }
    
    .badge-danger {
        background-color: #e74c3c;
        color: white;
    }
    
    /* Toggle switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    
    .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    input:checked + .slider {
        background-color: #3498db;
    }
    
    input:checked + .slider:before {
        transform: translateX(26px);
    }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header animate__animated animate__fadeIn">
            <h1 class="page-title">
                <i class="fas fa-comment-dots text-blue-500"></i>
                <span>Feedback System</span>
            </h1>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600">Last updated: <?= date('M d, Y H:i') ?></span>
                <span class="badge badge-success pulse">Live</span>
            </div>
        </div>
        
        <!-- Stats Dashboard -->
        <div class="stats-grid animate__animated animate__fadeIn">
            <div class="stat-card">
                <div class="stat-label">Total Feedback</div>
                <div class="stat-value"><?= $summary['total_feedback'] ?></div>
                <div class="text-sm text-gray-500"><i class="fas fa-chart-line mr-1"></i> All time</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Avg. Class Rating</div>
                <div class="stat-value"><?= number_format($summary['avg_class_rating'], 1) ?>/5</div>
                <div class="stars-inline">
                    <?= str_repeat('★', round($summary['avg_class_rating'])) ?><?= str_repeat('☆', 5 - round($summary['avg_class_rating'])) ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Satisfaction Rate</div>
                <div class="stat-value"><?= $summary['total_feedback'] > 0 ? round(($summary['satisfied_count'] / $summary['total_feedback']) * 100) : 0 ?>%</div>
                <div class="text-sm text-gray-500"><?= $summary['satisfied_count'] ?> out of <?= $summary['total_feedback'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Regular Students</div>
                <div class="stat-value"><?= $summary['regular_count'] ?></div>
                <div class="text-sm text-gray-500"><?= $summary['total_feedback'] > 0 ? round(($summary['regular_count'] / $summary['total_feedback']) * 100) : 0 ?>% of feedback</div>
            </div>
        </div>
        
        <!-- Feedback Submission Card -->
        <div class="card animate__animated animate__fadeInUp">
            <h3 class="flex items-center justify-between">
                <span><i class="fas fa-paper-plane mr-2 text-blue-500"></i> Submit Feedback</span>
                <span class="text-sm font-normal text-gray-500">All fields are required</span>
            </h3>
            <?php if (isset($success) && $success): ?>
                <div class="success-message animate__animated animate__fadeIn">
                    <i class="fas fa-check-circle mr-2"></i> Feedback submitted successfully!
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="animate__animated animate__fadeIn">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <input type="email" name="email" class="minimal-input" placeholder="Your Email" required>
                    </div>
                    <div>
                        <input type="text" name="student_name" class="minimal-input" placeholder="Your Name" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <select name="regular_in_class" class="minimal-input" required>
                            <option value="">Are you regular in class?</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                            <option value="Sometimes">Sometimes</option>
                        </select>
                    </div>
                    <div>
                        <select name="batch_id" class="minimal-input" required>
                            <option value="">Select Batch</option>
                            <?php foreach ($batches as $batch): ?>
                                <option value="<?= htmlspecialchars($batch['batch_id']) ?>">
                                    <?= htmlspecialchars($batch['batch_id']) ?> - <?= htmlspecialchars($batch['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <input type="text" name="course_name" class="minimal-input" placeholder="Course Name" required>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                    <div>
                        <h4>Class Rating</h4>
                        <div class="star-rating" data-target="class_rating">
                            <span data-value="1">★</span>
                            <span data-value="2">★</span>
                            <span data-value="3">★</span>
                            <span data-value="4">★</span>
                            <span data-value="5">★</span>
                        </div>
                        <input type="hidden" name="class_rating" id="class_rating" required>
                    </div>
                    
                    <div>
                        <h4>Assignment Understanding</h4>
                        <div class="star-rating" data-target="assignment_understanding">
                            <span data-value="1">★</span>
                            <span data-value="2">★</span>
                            <span data-value="3">★</span>
                            <span data-value="4">★</span>
                            <span data-value="5">★</span>
                        </div>
                        <input type="hidden" name="assignment_understanding" id="assignment_understanding" required>
                    </div>
                    
                    <div>
                        <h4>Practical Understanding</h4>
                        <div class="star-rating" data-target="practical_understanding">
                            <span data-value="1">★</span>
                            <span data-value="2">★</span>
                            <span data-value="3">★</span>
                            <span data-value="4">★</span>
                            <span data-value="5">★</span>
                        </div>
                        <input type="hidden" name="practical_understanding" id="practical_understanding" required>
                    </div>
                </div>
                
                <select name="satisfied" class="minimal-input mt-6" required>
                    <option value="">Are you satisfied with the course?</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
                
                <div class="textarea-wrapper">
                    <textarea name="suggestions" class="minimal-input" placeholder="Your suggestions or issues..." style="min-height: 100px;" maxlength="500" data-max-words="100"></textarea>
                    <div class="word-counter" id="suggestions-counter">0/100 words</div>
                </div>
                
                <div class="textarea-wrapper">
                    <textarea name="feedback_text" class="minimal-input" placeholder="Additional feedback..." style="min-height: 100px;" maxlength="1000" data-max-words="200"></textarea>
                    <div class="word-counter" id="feedback-counter">0/200 words</div>
                </div>
                
                <div class="flex justify-end mt-4">
                    <button type="submit" name="submit_feedback" class="btn-blue pulse">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Feedback
                    </button>
                </div>
            </form>
        </div>

        <!-- Feedback Analysis Chart -->
        <div class="chart-container animate__animated animate__fadeIn">
            <h3><i class="fas fa-chart-pie mr-2 text-blue-500"></i> Feedback Analysis</h3>
            <canvas id="feedbackChart" height="300"></canvas>
        </div>

        <!-- Feedback Display Table -->
        <div class="card animate__animated animate__fadeIn">
            <h3><i class="fas fa-table mr-2 text-blue-500"></i> Feedback Records</h3>
            
            <!-- Filter Panel -->
            <div class="filter-panel" id="filterPanel">
                <h3 onclick="toggleFilterPanel()">
                    <span><i class="fas fa-filter mr-2"></i> Advanced Filters</span>
                    <i class="fas fa-chevron-down"></i>
                </h3>
                <div class="filter-content">
                    <form method="GET" action="" class="mt-4">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Batch</label>
                                <select name="batch" class="minimal-input">
                                    <option value="">All Batches</option>
                                    <?php foreach ($batches as $batch): ?>
                                        <option value="<?= htmlspecialchars($batch['batch_id']) ?>" 
                                            <?= $batch_filter === $batch['batch_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($batch['batch_id']) ?> - <?= htmlspecialchars($batch['course_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
                                <select name="rating" class="minimal-input">
                                    <option value="">Any Rating</option>
                                    <option value="5" <?= $rating_filter === '5' ? 'selected' : '' ?>>5 Stars</option>
                                    <option value="4" <?= $rating_filter === '4' ? 'selected' : '' ?>>4 Stars</option>
                                    <option value="3" <?= $rating_filter === '3' ? 'selected' : '' ?>>3 Stars</option>
                                    <option value="2" <?= $rating_filter === '2' ? 'selected' : '' ?>>2 Stars</option>
                                    <option value="1" <?= $rating_filter === '1' ? 'selected' : '' ?>>1 Star</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Satisfaction</label>
                                <select name="satisfaction" class="minimal-input">
                                    <option value="">Any</option>
                                    <option value="Yes" <?= $satisfaction_filter === 'Yes' ? 'selected' : '' ?>>Satisfied</option>
                                    <option value="No" <?= $satisfaction_filter === 'No' ? 'selected' : '' ?>>Not Satisfied</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="filter-row">
                            <div class="filter-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Regularity</label>
                                <select name="regularity" class="minimal-input">
                                    <option value="">Any</option>
                                    <option value="Yes" <?= $regularity_filter === 'Yes' ? 'selected' : '' ?>>Regular</option>
                                    <option value="No" <?= $regularity_filter === 'No' ? 'selected' : '' ?>>Not Regular</option>
                                    <option value="Sometimes" <?= $regularity_filter === 'Sometimes' ? 'selected' : '' ?>>Sometimes</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                                <input type="date" name="date_from" class="minimal-input" value="<?= $date_from ?>">
                            </div>
                            
                            <div class="filter-group">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                                <input type="date" name="date_to" class="minimal-input" value="<?= $date_to ?>">
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center mt-4">
                            <div>
                                <button type="submit" class="btn-blue">
                                    <i class="fas fa-filter mr-2"></i> Apply Filters
                                </button>
                                <a href="feedback.php" class="btn-gray ml-2">
                                    <i class="fas fa-redo mr-2"></i> Reset
                                </a>
                            </div>
                            <div class="flex items-center">
                                <label class="switch tooltip" title="Toggle to show/hide detailed view">
                                    <input type="checkbox" id="detailedViewToggle">
                                    <span class="slider"></span>
                                </label>
                                <span class="ml-2 text-sm text-gray-600">Detailed View</span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table id="feedbackTable" class="display">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Regular</th>
                            <th>Batch</th>
                            <th>Course</th>
                            <th>Class</th>
                            <th>Assign</th>
                            <th>Pract</th>
                            <th>Satis</th>
                            <th>Suggestions</th>
                            <th>Feedback</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedback as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['date']) ?></td>
                            <td><?= htmlspecialchars($item['student_name']) ?></td>
                            <td><?= htmlspecialchars($item['email']) ?></td>
                            <td>
                                <?php if ($item['is_regular'] === 'Yes'): ?>
                                    <span class="badge badge-success">Yes</span>
                                <?php elseif ($item['is_regular'] === 'No'): ?>
                                    <span class="badge badge-danger">No</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Sometimes</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($item['batch_id']) ?></td>
                            <td><?= htmlspecialchars($item['course_name']) ?></td>
                            <td>
                                <div class="stars-inline" title="Class Rating: <?= $item['class_rating'] ?>/5">
                                    <?= str_repeat('★', $item['class_rating']) ?><?= str_repeat('☆', 5 - $item['class_rating']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="stars-inline" title="Assignment Understanding: <?= $item['assignment_understanding'] ?>/5">
                                    <?= str_repeat('★', $item['assignment_understanding']) ?><?= str_repeat('☆', 5 - $item['assignment_understanding']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="stars-inline" title="Practical Understanding: <?= $item['practical_understanding'] ?>/5">
                                    <?= str_repeat('★', $item['practical_understanding']) ?><?= str_repeat('☆', 5 - $item['practical_understanding']) ?>
                                </div>
                            </td>
                            <td>
                                <?= $item['satisfied'] === 'Yes' ? 
                                    '<span class="badge badge-success">Yes</span>' : 
                                    '<span class="badge badge-danger">No</span>' ?>
                            </td>
                            <td class="truncate max-w-xs" title="<?= htmlspecialchars($item['suggestions']) ?>">
                                <?= htmlspecialchars(substr($item['suggestions'], 0, 50)) ?><?= strlen($item['suggestions']) > 50 ? '...' : '' ?>
                            </td>
                            <td class="truncate max-w-xs" title="<?= htmlspecialchars($item['feedback_text']) ?>">
                                <?= htmlspecialchars(substr($item['feedback_text'], 0, 50)) ?><?= strlen($item['feedback_text']) > 50 ? '...' : '' ?>
                            </td>
                            <td>
                                <?php if (!empty($item['action_taken'])): ?>
                                    <span class="text-green-600" title="<?= htmlspecialchars($item['action_taken']) ?>">
                                        <i class="fas fa-check-circle"></i> Action taken
                                    </span>
                                <?php else: ?>
                                    <form method="POST" action="../feedback/update_action.php" style="display: flex; gap: 5px;">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <input type="text" name="action_taken" placeholder="Action..." 
                                               class="minimal-input" style="flex-grow: 1; min-width: 0; padding: 6px;">
                                        <button type="submit" class="btn-blue" style="padding: 6px 10px;" title="Mark as resolved">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable with export buttons
    var table = $('#feedbackTable').DataTable({
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copy',
                text: '<i class="fas fa-copy"></i> Copy',
                className: 'btn-gray'
            },
            {
                extend: 'csv',
                text: '<i class="fas fa-file-csv"></i> CSV',
                className: 'btn-gray'
            },
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn-gray'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn-gray'
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print',
                className: 'btn-gray'
            }
        ],
        columnDefs: [
            { targets: [6, 7, 8], orderable: false }
        ],
        initComplete: function() {
            $('.dt-buttons .btn').removeClass('dt-button');
        }
    });
    
    // Toggle detailed view
    $('#detailedViewToggle').change(function() {
        if(this.checked) {
            table.columns([2, 10, 11]).visible(true); // Show email, suggestions, feedback columns
        } else {
            table.columns([2, 10, 11]).visible(false); // Hide email, suggestions, feedback columns
        }
    }).trigger('change'); // Initialize state
    
    // Star rating interaction
    $('.star-rating span').on('click', function() {
        const rating = $(this).data('value');
        const target = $(this).parent().data('target');
        
        // Update stars appearance
        $(this).siblings().removeClass('active');
        $(this).prevAll().addBack().addClass('active');
        
        // Update hidden input value
        $('#' + target).val(rating);
    });
    
    // Hover effect for stars
    $('.star-rating span').on('mouseover', function() {
        $(this).prevAll().addBack().css('color', '#f39c12');
    }).on('mouseout', function() {
        const activeStars = $(this).parent().find('.active');
        if (activeStars.length > 0) {
            $(this).siblings().css('color', '#e0e6ed');
            activeStars.css('color', '#f39c12');
        } else {
            $(this).siblings().addBack().css('color', '#e0e6ed');
        }
    });
    
    // Word counter functionality
    function countWords(text) {
        // Trim whitespace and count non-empty sequences of characters separated by whitespace
        return text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
    }
    
    function updateWordCounter(textarea, counter) {
        const text = $(textarea).val();
        const wordCount = countWords(text);
        const maxWords = parseInt($(textarea).data('max-words'));
        
        $(counter).text(wordCount + '/' + maxWords + ' words');
        
        if (wordCount > maxWords) {
            $(counter).addClass('limit-reached');
            // Truncate the text if over limit
            const words = text.trim().split(/\s+/);
            const truncated = words.slice(0, maxWords).join(' ');
            $(textarea).val(truncated);
            $(counter).text(maxWords + '/' + maxWords + ' words (limit reached)');
        } else {
            $(counter).removeClass('limit-reached');
        }
    }
    
    // Initialize word counters
    $('textarea[minlength], textarea[maxlength], textarea[data-max-words]').each(function() {
        const textareaId = $(this).attr('name');
        $(this).on('input', function() {
            updateWordCounter(this, $('#' + textareaId + '-counter'));
        });
        
        // Initial count
        updateWordCounter(this, $('#' + textareaId + '-counter'));
    });
    
    // Feedback chart
    const ctx = document.getElementById('feedbackChart').getContext('2d');
    const feedbackChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Class Rating', 'Assignment', 'Practical', 'Satisfaction', 'Regularity'],
            datasets: [{
                label: 'Average Ratings',
                data: [
                    <?= $summary['avg_class_rating'] ?>,
                    <?= $summary['avg_assignment_rating'] ?>,
                    <?= $summary['avg_practical_rating'] ?>,
                    <?= $summary['total_feedback'] > 0 ? ($summary['satisfied_count'] / $summary['total_feedback']) * 5 : 0 ?>,
                    <?= $summary['total_feedback'] > 0 ? ($summary['regular_count'] / $summary['total_feedback']) * 5 : 0 ?>
                ],
                backgroundColor: [
                    'rgba(52, 152, 219, 0.7)',
                    'rgba(155, 89, 182, 0.7)',
                    'rgba(46, 204, 113, 0.7)',
                    'rgba(241, 196, 15, 0.7)',
                    'rgba(231, 76, 60, 0.7)'
                ],
                borderColor: [
                    'rgba(52, 152, 219, 1)',
                    'rgba(155, 89, 182, 1)',
                    'rgba(46, 204, 113, 1)',
                    'rgba(241, 196, 15, 1)',
                    'rgba(231, 76, 60, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5,
                    title: {
                        display: true,
                        text: 'Rating (out of 5)'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += context.parsed.y.toFixed(1);
                            }
                            return label;
                        }
                    }
                },
                legend: {
                    display: false
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeOutQuart'
            }
        }
    });
    
    // Toggle filter panel
    window.toggleFilterPanel = function() {
        $('#filterPanel').toggleClass('collapsed');
    }
    
    // Initialize tooltips
    $('[title]').tooltip({
        position: {
            my: "center bottom-20",
            at: "center top",
            using: function(position, feedback) {
                $(this).css(position);
                $("<div>")
                    .addClass("arrow")
                    .addClass(feedback.vertical)
                    .addClass(feedback.horizontal)
                    .appendTo(this);
            }
        }
    });
});
</script>
</body>
</html>