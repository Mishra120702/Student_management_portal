<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Check if user is logged in as trainer
if (!isset($_SESSION['user_id'])) {
    header("Location: log2.php");
    exit;
}

require_once '../db_connection.php';

// Database connection details
$host = '127.0.0.1';
$dbname = 'asd_academy1';
$user = 'root';
$pass = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get trainer details
    $trainer_id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT t.* FROM trainers t WHERE t.user_id = ?");
    $stmt->execute([$trainer_id]);
    $trainer = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get batches assigned to this trainer
    $stmt = $db->prepare("SELECT * FROM batches WHERE batch_mentor_id = ?");
    $stmt->execute([$trainer_id]);
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get upcoming classes
    $stmt = $db->prepare("SELECT s.*, b.course_name 
                         FROM schedule s 
                         JOIN batches b ON s.batch_id = b.batch_id 
                         WHERE b.batch_mentor_id = ? AND s.schedule_date >= CURDATE() 
                         ORDER BY s.schedule_date, s.start_time 
                         LIMIT 5");
    $stmt->execute([$trainer_id]);
    $upcoming_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get attendance stats for chart
    $attendance_stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(a.date, '%Y-%m') as month,
            COUNT(*) as total_classes,
            SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_count,
            CASE 
                WHEN COUNT(*) > 0 THEN ROUND((SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) / COUNT(*)) * 100)
                ELSE 0 
            END as attendance_percentage
        FROM attendance a
        JOIN batches b ON a.batch_id = b.batch_id
        WHERE b.batch_mentor_id = ? AND a.date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
        GROUP BY DATE_FORMAT(a.date, '%Y-%m')
        ORDER BY month ASC
    ");
    $attendance_stmt->execute([$trainer_id]);
    $attendance_data = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get batch distribution data
    $batch_status_stmt = $db->prepare("
        SELECT status, COUNT(*) as count 
        FROM batches 
        WHERE batch_mentor_id = ?
        GROUP BY status
    ");
    $batch_status_stmt->execute([$trainer_id]);
    $batch_status_data = $batch_status_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare batch status data for chart
    $batch_status_labels = [];
    $batch_status_counts = [];
    $batch_status_colors = [];
    
    foreach ($batch_status_data as $row) {
        $batch_status_labels[] = ucfirst($row['status']);
        $batch_status_counts[] = $row['count'];
        
        // Assign colors based on status
        switch ($row['status']) {
            case 'upcoming': $batch_status_colors[] = '#4e73df'; break;
            case 'ongoing': $batch_status_colors[] = '#1cc88a'; break;
            case 'completed': $batch_status_colors[] = '#f6c23e'; break;
            default: $batch_status_colors[] = '#858796';
        }
    }

} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard | ASD Academy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/ScrollTrigger.min.js"></script>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #4338ca;
            --secondary: #10b981;
            --accent: #f59e0b;
            --dark: #1e293b;
            --light: #f8fafc;
        }
        
        /* Keyframe animations */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fade-in-up {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fade-in-left {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes fade-in-right {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }
        
        @keyframes ripple {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(2.5); opacity: 0; }
        }
        
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Animation classes */
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        .animate-fade-in {
            animation: fade-in 0.6s ease-out forwards;
        }
        
        .animate-fade-in-up {
            animation: fade-in-up 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .animate-fade-in-left {
            animation: fade-in-left 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .animate-fade-in-right {
            animation: fade-in-right 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .animate-pulse {
            animation: pulse 2s infinite;
        }
        
        .animate-delay-100 {
            animation-delay: 0.1s;
        }
        
        .animate-delay-200 {
            animation-delay: 0.2s;
        }
        
        .animate-delay-300 {
            animation-delay: 0.3s;
        }
        
        /* Hover effects */
        .hover-grow {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        .hover-grow:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .hover-float {
            transition: all 0.3s ease;
        }
        
        .hover-float:hover {
            transform: translateY(-5px);
        }
        
        .hover-shine {
            position: relative;
            overflow: hidden;
        }
        
        .hover-shine::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -60%;
            width: 200%;
            height: 200%;
            opacity: 0;
            transform: rotate(30deg);
            background: rgba(255, 255, 255, 0.13);
            background: linear-gradient(
                to right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.13) 77%,
                rgba(255, 255, 255, 0.5) 92%,
                rgba(255, 255, 255, 0) 100%
            );
        }
        
        .hover-shine:hover::after {
            opacity: 1;
            left: 100%;
            transition-property: left, opacity;
            transition-duration: 0.7s, 0.15s;
            transition-timing-function: ease;
        }
        
        /* UI Elements */
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
            z-index: 10;
        }
        
        .ripple {
            position: relative;
            overflow: hidden;
        }
        
        .ripple-effect {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            background-size: 200% 200%;
            animation: gradient-shift 8s ease infinite;
        }
        
        .sidebar {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-collapsed {
            width: 80px;
        }
        
        .sidebar-expanded {
            width: 250px;
        }
        
        .main-content {
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .content-collapsed {
            margin-left: 80px;
        }
        
        .content-expanded {
            margin-left: 250px;
        }
        
        .sidebar-link {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: rgba(255, 255, 255, 0.15);
            transition: width 0.3s ease;
        }
        
        .sidebar-link:hover::before {
            width: 100%;
        }
        
        .sidebar-link.active {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .sidebar-link.active::before {
            width: 4px;
            background: white;
        }
        
        .card {
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }
        
        .card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .glow-effect {
            box-shadow: 0 0 15px rgba(79, 70, 229, 0.3);
            transition: box-shadow 0.3s ease;
        }
        
        .glow-effect:hover {
            box-shadow: 0 0 25px rgba(79, 70, 229, 0.5);
        }
        
        .table-row-hover {
            transition: all 0.2s ease;
        }
        
        .table-row-hover:hover {
            background-color: #f8fafc !important;
            transform: translateX(4px);
        }
        
        .chart-container {
            position: relative;
            height: 100%;
            min-height: 300px;
        }
        
        .wave-bg {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" fill="%234f46e5" opacity=".25"/><path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" fill="%234f46e5" opacity=".5"/><path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,931.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" fill="%234f46e5"/></svg>');
            background-size: cover;
            background-repeat: no-repeat;
            opacity: 0.1;
            z-index: -1;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <!-- Sidebar -->
    <?php // include '../t_sidebar.php'; ?>
    <!-- Main Content -->
    <div class="main-content content-expanded min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
            <button class="md:hidden text-xl text-gray-600 hover:text-blue-500 transition-colors ripple" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2 animate-fade-in">
                <i class="fas fa-tachometer-alt text-blue-500"></i>
                <span>Trainer Dashboard</span>
            </h1>
            <div class="flex items-center space-x-4">
                <a href="logout.php" class="text-sm text-red-600 hover:underline flex items-center space-x-1 transition-colors hover:text-red-700 ripple">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>    
            </div>
        </header>

        <div class="p-4 md:p-6 relative">
            <!-- Animated background elements -->
            <div class="absolute top-0 right-0 -z-10 opacity-10">
                <svg width="300" height="300" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#4f46e5" d="M45.1,-65.1C58.1,-56.5,68.4,-43.3,74.8,-27.9C81.1,-12.5,83.5,5.1,78.8,20.4C74.1,35.7,62.3,48.7,47.8,58.9C33.3,69.1,16.6,76.5,0.3,76.1C-16,75.7,-32,67.5,-45.3,56.7C-58.6,45.9,-69.2,32.5,-74.1,16.9C-79,1.3,-78.2,-16.5,-70.4,-31.5C-62.6,-46.5,-47.8,-58.7,-32.8,-66.6C-17.8,-74.5,-2.6,-78.2,13.8,-75.2C30.2,-72.2,60.5,-62.6,71.1,-48.8C81.7,-35,72.7,-17,69.4,-0.7C66.1,15.6,68.6,31.2,62.2,43.5C55.8,55.8,40.6,64.8,24.8,70.7C9,76.6,-7.4,79.4,-21.3,74.9C-35.2,70.4,-46.6,58.6,-56.2,45.7C-65.8,32.8,-73.6,18.8,-76.8,3.3C-80,-12.2,-78.6,-29.2,-69.7,-41.7C-60.8,-54.2,-44.4,-62.2,-29.2,-69.6C-14,-77,0,-83.8,13.8,-80.1C27.6,-76.4,55.2,-62.1,64.3,-43.5C73.4,-24.8,64,-1.7,57.5,17.3C51,36.3,47.4,52.3,38.4,63.9C29.4,75.5,15,82.7,-0.7,83.9C-16.4,85.1,-32.8,80.3,-44.8,70.4C-56.8,60.5,-64.4,45.5,-69.8,30.2C-75.2,14.9,-78.4,-0.7,-74.4,-14.4C-70.4,-28.1,-59.2,-39.9,-46.4,-49.1C-33.6,-58.3,-19.3,-64.9,-3.3,-60.3C12.7,-55.7,25.4,-40,33.8,-26.6C42.2,-13.2,46.4,-2.1,49.7,11.5C53,25.1,55.5,41.3,49.2,51.1C42.9,60.9,27.8,64.3,12.5,70.4C-2.8,76.5,-18.3,85.3,-30.5,81.8C-42.7,78.3,-51.6,62.5,-60.1,47.2C-68.6,31.9,-76.7,17,-77.3,1.8C-77.9,-13.5,-71,-29,-61.3,-42.1C-51.6,-55.2,-39.1,-65.9,-25.1,-73.9C-11.1,-81.9,4.4,-87.2,18.8,-83.1C33.2,-79,46.5,-65.5,45.1,-65.1Z" transform="translate(100 100)" />
                </svg>
            </div>

            <!-- Welcome Section -->
            <div class="bg-white p-6 rounded-xl shadow-lg mb-6 hover-grow glow-effect animate-fade-in-up">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Welcome, <?php echo htmlspecialchars($trainer['name']); ?>! 👋</h2>
                        <p class="text-gray-600 mt-1">Here's your training overview and quick stats.</p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <div class="flex items-center space-x-2 bg-gradient-to-r from-blue-50 to-purple-50 px-4 py-2 rounded-full">
                            <span class="text-sm font-medium text-gray-600">Active Batches:</span>
                            <span class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-800 font-bold">
                                <?php echo count($batches); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Active Batches Card -->
                <div class="bg-white p-6 rounded-xl shadow-lg hover-grow hover-float animate-fade-in-up animate-delay-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-blue-100 text-blue-600 p-3 rounded-lg shadow-inner">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Active Batches</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo count($batches); ?></p>
                        </div>
                    </div>
                    <div class="relative h-2 bg-gray-200 rounded-full overflow-hidden mb-3">
                        <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-blue-400 to-blue-600 rounded-full" style="width: <?php echo min(100, count($batches) * 25); ?>%"></div>
                    </div>
                    <a href="batches.php" class="text-sm text-blue-600 hover:underline transition-colors inline-flex items-center ripple">
                        View Batches <i class="fas fa-chevron-right ml-1 text-xs"></i>
                    </a>
                </div>

                <!-- Upcoming Classes Card -->
                <div class="bg-white p-6 rounded-xl shadow-lg hover-grow hover-float animate-fade-in-up animate-delay-150">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-green-100 text-green-600 p-3 rounded-lg shadow-inner">
                            <i class="fas fa-calendar-day text-xl"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Upcoming Classes</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo count($upcoming_classes); ?></p>
                        </div>
                    </div>
                    <div class="relative h-2 bg-gray-200 rounded-full overflow-hidden mb-3">
                        <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-green-400 to-green-600 rounded-full" style="width: <?php echo min(100, count($upcoming_classes) * 20); ?>%"></div>
                    </div>
                    <a href="schedule.php" class="text-sm text-blue-600 hover:underline transition-colors inline-flex items-center ripple">
                        View Schedule <i class="fas fa-chevron-right ml-1 text-xs"></i>
                    </a>
                </div>

                <!-- Total Students Card -->
                <div class="bg-white p-6 rounded-xl shadow-lg hover-grow hover-float animate-fade-in-up animate-delay-200">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-purple-100 text-purple-600 p-3 rounded-lg shadow-inner">
                            <i class="fas fa-user-graduate text-xl"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Total Students</p>
                            <p class="text-3xl font-bold text-gray-800">
                                <?php 
                                $total_students = 0;
                                foreach ($batches as $batch) {
                                    $total_students += $batch['current_enrollment'];
                                }
                                echo $total_students;
                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="relative h-2 bg-gray-200 rounded-full overflow-hidden mb-3">
                        <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-purple-400 to-purple-600 rounded-full" style="width: <?php echo min(100, ($total_students > 0 ? ($total_students / 50) * 100 : 0)); ?>%"></div>
                    </div>
                    <a href="students.php" class="text-sm text-blue-600 hover:underline transition-colors inline-flex items-center ripple">
                        View Students <i class="fas fa-chevron-right ml-1 text-xs"></i>
                    </a>
                </div>

                <!-- Attendance Rate Card -->
                <div class="bg-white p-6 rounded-xl shadow-lg hover-grow hover-float animate-fade-in-up animate-delay-250">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-yellow-100 text-yellow-600 p-3 rounded-lg shadow-inner">
                            <i class="fas fa-clipboard-check text-xl"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Avg Attendance</p>
                            <p class="text-3xl font-bold text-gray-800">
                                <?php 
                                $avg_attendance = 0;
                                if (count($attendance_data) > 0) {
                                    $sum = 0;
                                    $count = 0;
                                    foreach ($attendance_data as $row) {
                                        if ($row['attendance_percentage'] > 0) {
                                            $sum += $row['attendance_percentage'];
                                            $count++;
                                        }
                                    }
                                    $avg_attendance = $count > 0 ? round($sum / $count) : 0;
                                }
                                echo $avg_attendance . '%';
                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="relative h-2 bg-gray-200 rounded-full overflow-hidden mb-3">
                        <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-full" style="width: <?php echo $avg_attendance; ?>%"></div>
                    </div>
                    <a href="attendance.php" class="text-sm text-blue-600 hover:underline transition-colors inline-flex items-center ripple">
                        View Details <i class="fas fa-chevron-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>

            <!-- Batch Distribution Chart -->
            <div class="bg-white p-6 rounded-xl shadow-lg hover-grow mb-6 animate-fade-in-up animate-delay-300">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-chart-pie text-green-500 mr-2"></i>
                    Batch Distribution
                </h3>
                <div class="chart-container">
                    <canvas id="batchChart"></canvas>
                </div>
            </div>

            <!-- Upcoming Classes Section -->
            <div class="bg-white p-6 rounded-xl shadow-lg hover-glow mb-6 animate-fade-in-up animate-delay-400">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-calendar-alt text-indigo-500 mr-2"></i>
                        Upcoming Classes
                    </h3>
                    <a href="schedule.php" class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full hover:bg-indigo-100 transition-colors flex items-center ripple">
                        View All <i class="fas fa-chevron-right ml-1 text-xs"></i>
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Topic</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($upcoming_classes) > 0): ?>
                                <?php foreach ($upcoming_classes as $index => $class): ?>
                                    <tr class="table-row-hover animate-fade-in-up" style="animation-delay: <?php echo 0.4 + ($index * 0.05); ?>s">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                <?php echo date('M j, Y', strtotime($class['schedule_date'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('g:i A', strtotime($class['start_time'])) . ' - ' . date('g:i A', strtotime($class['end_time'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($class['batch_id']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($class['course_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="truncate max-w-xs" title="<?php echo htmlspecialchars($class['topic']); ?>">
                                                <?php echo htmlspecialchars($class['topic']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="class_details.php?id=<?php echo $class['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3 ripple">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </a>
                                            <a href="edit_class.php?id=<?php echo $class['id']; ?>" class="text-green-600 hover:text-green-900 ripple">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="animate-fade-in-up animate-delay-400">
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center py-6">
                                            <i class="fas fa-calendar-times text-4xl text-gray-400 mb-2 animate-pulse"></i>
                                            <p>No upcoming classes scheduled</p>
                                            <a href="schedule.php" class="text-sm text-blue-500 hover:underline mt-2 ripple">Schedule a class</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- My Batches Section -->
            <div class="bg-white p-6 rounded-xl shadow-lg hover-glow animate-fade-in-up animate-delay-500">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-users text-blue-500 mr-2"></i>
                        My Batches
                    </h3>
                    <a href="batches.php" class="text-xs bg-blue-50 text-blue-600 px-3 py-1 rounded-full hover:bg-blue-100 transition-colors flex items-center ripple">
                        View All <i class="fas fa-chevron-right ml-1 text-xs"></i>
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($batches) > 0): ?>
                                <?php foreach ($batches as $index => $batch): ?>
                                    <tr class="table-row-hover animate-fade-in-up" style="animation-delay: <?php echo 0.5 + ($index * 0.05); ?>s">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($batch['batch_id']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($batch['course_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($batch['time_slot']); ?><br>
                                            <small class="text-gray-400"><?php echo date('M j, Y', strtotime($batch['start_date'])) . ' - ' . date('M j, Y', strtotime($batch['end_date'])); ?></small>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="mb-1">
                                                <?php echo htmlspecialchars($batch['current_enrollment']) . '/' . htmlspecialchars($batch['max_students']); ?>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($batch['current_enrollment'] / max(1, $batch['max_students'])) * 100; ?>%"></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php 
                                            $status_class = '';
                                            switch ($batch['status']) {
                                                case 'upcoming': $status_class = 'bg-blue-100 text-blue-800'; break;
                                                case 'ongoing': $status_class = 'bg-green-100 text-green-800'; break;
                                                case 'completed': $status_class = 'bg-yellow-100 text-yellow-800'; break;
                                                default: $status_class = 'bg-gray-100 text-gray-800';
                                            }
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                <?php echo ucfirst($batch['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="batch_details.php?id=<?php echo $batch['batch_id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3 ripple">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </a>
                                            <a href="batch_students.php?id=<?php echo $batch['batch_id']; ?>" class="text-green-600 hover:text-green-900 ripple">
                                                <i class="fas fa-users mr-1"></i> Students
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="animate-fade-in-up animate-delay-500">
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center py-6">
                                            <i class="fas fa-users-slash text-4xl text-gray-400 mb-2 animate-pulse"></i>
                                            <p>You don't have any batches assigned yet</p>
                                            <a href="#" class="text-sm text-blue-500 hover:underline mt-2 ripple">Request a batch</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize ripple effects
            const rippleButtons = document.querySelectorAll('.ripple');
            rippleButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const rect = this.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    const ripple = document.createElement('span');
                    ripple.className = 'ripple-effect';
                    ripple.style.left = `${x}px`;
                    ripple.style.top = `${y}px`;
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            // Initialize charts
            function initCharts() {
                // Batch Distribution Doughnut Chart
                const batchCtx = document.getElementById('batchChart').getContext('2d');
                const batchChart = new Chart(batchCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode($batch_status_labels); ?>,
                        datasets: [{
                            data: <?php echo json_encode($batch_status_counts); ?>,
                            backgroundColor: <?php echo json_encode($batch_status_colors); ?>,
                            hoverBackgroundColor: [
                                '#3a5bc7',
                                '#17a673',
                                '#e0b132'
                            ],
                            hoverBorderColor: 'rgba(234, 236, 244, 1)',
                            borderWidth: 2,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20,
                                    font: {
                                        family: 'sans-serif',
                                        weight: 'bold',
                                        size: 12
                                    },
                                    color: '#1e293b'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(30, 41, 59, 0.9)',
                                bodyFont: {
                                    size: 12,
                                    family: 'sans-serif'
                                },
                                padding: 12,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round((value / total) * 100);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '75%',
                        animation: {
                            duration: 1500,
                            easing: 'easeOutQuart',
                            animateScale: true,
                            animateRotate: true
                        }
                    }
                });
            }

            // Initialize charts when page loads
            initCharts();
            
            // Initialize GSAP animations
            gsap.registerPlugin(ScrollTrigger);
            
            // Animate cards on scroll
            gsap.utils.toArray('.animate-fade-in-up').forEach((element, index) => {
                gsap.from(element, {
                    scrollTrigger: {
                        trigger: element,
                        start: "top 80%",
                        toggleActions: "play none none none"
                    },
                    y: 30,
                    opacity: 0,
                    duration: 0.6,
                    delay: index * 0.05,
                    ease: "power2.out"
                });
            });

            // Toggle sidebar
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            sidebarToggle.addEventListener('click', function(e) {
                // Create ripple effect
                const ripple = document.createElement('span');
                ripple.className = 'ripple-effect';
                ripple.style.left = `${e.offsetX}px`;
                ripple.style.top = `${e.offsetY}px`;
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
                
                // Toggle sidebar
                sidebar.classList.toggle('sidebar-expanded');
                sidebar.classList.toggle('sidebar-collapsed');
                mainContent.classList.toggle('content-expanded');
                mainContent.classList.toggle('content-collapsed');
                
                // Change icon based on state
                if (sidebar.classList.contains('sidebar-expanded')) {
                    sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
                } else {
                    sidebarToggle.innerHTML = '<i class="fas fa-ellipsis-h"></i>';
                }
            });

            // Mobile sidebar toggle
            function toggleSidebar() {
                sidebar.classList.toggle('sidebar-expanded');
                sidebar.classList.toggle('sidebar-collapsed');
                mainContent.classList.toggle('content-expanded');
                mainContent.classList.toggle('content-collapsed');
            }
            
            // Add hover shine effect to cards
            const cards = document.querySelectorAll('.card, .hover-grow');
            cards.forEach(card => {
                card.addEventListener('mousemove', function(e) {
                    const rect = this.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    this.style.setProperty('--mouse-x', `${x}px`);
                    this.style.setProperty('--mouse-y', `${y}px`);
                });
            });
            
            // Add confetti effect to welcome card
            const welcomeCard = document.querySelector('.bg-white.p-6.rounded-xl.shadow-lg.mb-6');
            welcomeCard.addEventListener('click', function() {
                if (this.classList.contains('confetti-active')) return;
                
                this.classList.add('confetti-active');
                
                const confettiCount = 30;
                const colors = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444'];
                
                for (let i = 0; i < confettiCount; i++) {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.width = `${Math.random() * 10 + 5}px`;
                    confetti.style.height = `${Math.random() * 10 + 5}px`;
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.position = 'absolute';
                    confetti.style.left = `${Math.random() * 100}%`;
                    confetti.style.top = '0';
                    confetti.style.opacity = '0';
                    confetti.style.borderRadius = '2px';
                    confetti.style.transform = 'rotate(' + (Math.random() * 360) + 'deg)';
                    
                    this.appendChild(confetti);
                    
                    const animation = gsap.to(confetti, {
                        y: this.offsetHeight,
                        x: (Math.random() - 0.5) * 100,
                        opacity: 1,
                        duration: 1,
                        ease: 'power1.out',
                        onComplete: () => {
                            confetti.remove();
                        }
                    });
                }
                
                setTimeout(() => {
                    this.classList.remove('confetti-active');
                }, 1000);
            });
        });
    </script>
</body>
</html>