<?php
// Database connection
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
// Check if batch_id is provided in URL
$preselected_batch = isset($_GET['batch_id']) ? $_GET['batch_id'] : '';
$preselected_date = isset($_GET['date']) ? $_GET['date'] : '';
// Get all batches for the filter dropdown
$stmt = $db->query("SELECT batch_id, course_name FROM batches");
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle file upload if submitted
if (isset($_POST['import'])) {
    if (isset($_FILES['excel_file'])) {
        require_once 'attendance_upload.php'; // Include the processing script
        header("Location: attendance.php"); // Redirect back to prevent form resubmission
        exit();
    }
}

// Handle new attendance creation
if (isset($_POST['create_attendance'])) {
    $batch_id = $_POST['batch_id'];
    $date = $_POST['date'];
    
    // Check if attendance already exists for this batch and date
    $stmt = $db->prepare("SELECT COUNT(*) FROM attendance WHERE batch_id = ? AND date = ?");
    $stmt->execute([$batch_id, $date]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        $_SESSION['error_message'] = "Attendance already exists for batch $batch_id on $date";
    } else {
        // Get all students in this batch
        $stmt = $db->prepare("SELECT CONCAT(first_name, ' ', last_name) as student_name 
                             FROM students 
                             WHERE batch_name = ?");
        $stmt->execute([$batch_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Insert attendance records for each student (default to Absent and Camera Off)
        foreach ($students as $student) {
            $stmt = $db->prepare("INSERT INTO attendance (date, batch_id, student_name, status, camera_status) 
                                 VALUES (?, ?, ?, 'Absent', 'Off')");
            $stmt->execute([$date, $batch_id, $student['student_name']]);
        }
        
        $_SESSION['success_message'] = "New attendance created for batch $batch_id on $date with all students marked as Absent";
    }
    
    header("Location: attendance.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Tracking - ASD Academy</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-700: #374151;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            transition: all 0.3s ease;
        }
        
        /* Card styles with subtle shadow and animation */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 1.75rem;
            margin-bottom: 1.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }
        
        /* Header with gradient background */
        header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        /* Status badges with animation */
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            min-width: 70px;
            justify-content: center;
            transition: all 0.2s ease;
            transform-origin: center;
        }
        
        .status-badge:hover {
            transform: scale(1.05);
        }
        
        .status-present {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .status-absent {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .status-late {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        /* Switch toggle styles with animation */
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
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
            background-color: #e2e8f0;
            transition: .4s;
            border-radius: 24px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        
        input:checked + .slider {
            background-color: var(--success);
        }
        
        input:checked + .slider:before {
            transform: translateX(20px);
        }
        
        .camera-slider input:checked + .slider {
            background-color: var(--primary);
        }
        
        /* Button styles with hover effects */
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 2px 5px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        /* Toggle buttons with animation */
        .toggle-buttons {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .toggle-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .toggle-btn.active {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }
        
        .toggle-btn:not(.active) {
            background-color: var(--gray-100);
            color: var(--gray-700);
        }
        
        .toggle-btn:not(.active):hover {
            background-color: var(--gray-200);
            transform: translateY(-1px);
        }
        
        /* Input styles with focus effects */
        .minimal-input {
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 0.6rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background-color: white;
            width: 100%;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        
        .minimal-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            outline: none;
        }
        
        /* Table styles with hover effects */
        #attendanceTable tbody tr {
            transition: all 0.2s ease;
        }
        
        #attendanceTable tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.05);
            transform: scale(1.005);
        }
        
        /* Alert animations */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.5s ease-out;
            border-left: 4px solid transparent;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-color: #10b981;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #b91c1b;
            border-color: #ef4444;
        }
        
        /* Progress bar animation */
        .progress-bar {
            transition: width 1s ease-in-out;
        }
        
        /* Keyframe animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .toggle-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .toggle-btn {
                width: 100%;
            }
            
            .card {
                padding: 1rem;
            }
        }
        
        /* Floating action button */
        .fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 50;
        }
        
        .fab:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }
        
        /* Modal overlay */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            padding: 2rem;
            transform: translateY(-20px);
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }
        
        /* Add to your existing CSS */
        .switch input:disabled + .slider {
            background-color: var(--gray-200);
            cursor: not-allowed;
        }
        
        .switch input:disabled + .slider:before {
            background-color: var(--gray-100);
        }
        
        /* Loading spinner */
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(59, 130, 246, 0.2);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Section transitions */
        .section-transition {
            animation: fadeInUp 0.5s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>
    
    <div class="flex-1 ml-0 md:ml-64 min-h-screen transition-all duration-300">
        <!-- Header -->
        <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
            <button class="md:hidden text-xl text-gray-600 hover:text-blue-500 transition-colors" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fas fa-clipboard-check text-blue-500 animate-pulse"></i>
                <span>Attendance Tracking</span>
            </h1>
            <div class="flex items-center space-x-4">
                <!-- Empty for now -->
            </div>
        </header>

        <div class="p-4 md:p-6">
            <!-- Toggle buttons -->
            <div class="toggle-buttons animate__animated animate__fadeIn">
                <button id="showManualBtn" class="toggle-btn active">
                    <i class="fas fa-edit mr-2"></i> Manual Attendance
                </button>
                <button id="showUploadBtn" class="toggle-btn">
                    <i class="fas fa-file-upload mr-2"></i> Upload Excel
                </button>
                <button id="showMonthlyBtn" class="toggle-btn">
                    <i class="fas fa-calendar-alt mr-2"></i> Monthly View
                </button>
                <button id="showCreateBtn" class="toggle-btn">
                    <i class="fas fa-plus-circle mr-2"></i> New Attendance
                </button>
            </div>
            
            <!-- Manual Attendance Section -->
            <div id="manualAttendanceSection" class="section-transition">
                <!-- Filters Card -->
                <div class="filter-card animate__animated animate__fadeInUp">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <select id="batchFilter" class="minimal-input">
                            <option value="">All Batches</option>
                            <?php foreach ($batches as $batch): ?>
                            <option value="<?= htmlspecialchars($batch['batch_id']) ?>" 
                                <?= ($preselected_batch === $batch['batch_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($batch['batch_id'] . ' - ' . $batch['course_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="text" id="dateFilter" class="minimal-input date-picker" placeholder="Select date">
                        
                        <button id="markAllPresent" class="btn-primary hover:animate-pulse">
                            <i class="fas fa-check-circle mr-2"></i> Mark All Present
                        </button>
                    </div>
                </div>
                
                <!-- Attendance Table Card -->
                <div class="card animate__animated animate__fadeInUp animate__delay-1s">
                    <table id="attendanceTable" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Batch ID</th>
                                <th>Status</th>
                                <th>Camera</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                    
                    <div class="flex justify-end mt-4">
                        <button id="saveAttendance" class="btn-primary hover:animate-pulse">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Upload Excel Section (initially hidden) -->
            <div id="uploadExcelSection" style="display: none;" class="section-transition">
                <?php if (isset($_SESSION['import_message'])): ?>
                    <div class="alert alert-info bg-blue-50 text-blue-800 p-4 rounded-lg mb-4 animate__animated animate__fadeIn">
                        <?php 
                        echo $_SESSION['import_message']; 
                        unset($_SESSION['import_message']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="card animate__animated animate__fadeInUp">
                    <h5 class="font-bold text-lg mb-3 text-gray-800 flex items-center">
                        <i class="fas fa-file-excel text-green-600 mr-2"></i> Instructions:
                    </h5>
                    <ol class="list-decimal pl-5 mb-4 space-y-2 text-gray-700">
                        <li class="flex items-start">
                            <span class="mr-2">1.</span>
                            <span>Download the sample Excel template below</span>
                        </li>
                        <li class="flex items-start">
                            <span class="mr-2">2.</span>
                            <span>Fill in the attendance data following the format</span>
                        </li>
                        <li class="flex items-start">
                            <span class="mr-2">3.</span>
                            <span>Upload the completed file</span>
                        </li>
                    </ol>
                    <p class="text-red-500 mb-4 text-sm font-medium flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i> Important: Do not modify the column headers in the template.
                    </p>
                    
                    <div class="download-sample mb-6">
                        <a href="download_sample.php" class="btn-primary inline-flex items-center hover:animate-pulse">
                            <i class="fas fa-download mr-2"></i> Download Sample Template
                        </a>
                    </div>

                    <form action="attendance.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-6">
                            <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-file-excel mr-2 text-green-600"></i> Select Excel File
                            </label>
                            <div class="file-upload-wrapper relative">
                                <input class="w-full minimal-input cursor-pointer" type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                                <div class="file-upload-indicator absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Only .xlsx or .xls files are accepted</p>
                        </div>
                        
                        <div class="mb-6">
                            <p class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-table mr-2"></i> Expected columns in order:
                            </p>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch ID</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Camera Status</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">YYYY-MM-DD</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">B001</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">Alice Williams</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">Present/Absent/Late</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">On/Off</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">Optional notes</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            <button type="submit" name="import" class="btn-primary hover:animate-pulse">
                                <i class="fas fa-upload mr-2"></i> Upload Attendance
                            </button>
                            <a href="attendance.php" class="btn-gray">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Monthly View Section (initially hidden) -->
            <div id="monthlyViewSection" style="display: none;" class="section-transition">
                <div class="filter-card animate__animated animate__fadeInUp">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <select id="monthlyBatchFilter" class="minimal-input">
                            <option value="">Select Batch</option>
                            <?php foreach ($batches as $batch): ?>
                            <option value="<?= htmlspecialchars($batch['batch_id']) ?>">
                                <?= htmlspecialchars($batch['batch_id'] . ' - ' . $batch['course_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="text" id="monthFilter" class="minimal-input month-picker" placeholder="Select month">
                        
                        <button id="generateMonthlyReport" class="btn-primary hover:animate-pulse">
                            <i class="fas fa-chart-bar mr-2"></i> Generate Report
                        </button>
                        
                        <button id="exportMonthlyReport" class="btn-gray" disabled>
                            <i class="fas fa-file-export mr-2"></i> Export Report
                        </button>
                    </div>
                </div>
                
                <div class="card animate__animated animate__fadeInUp animate__delay-1s">
                    <div id="monthlyReportContainer">
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-chart-pie text-4xl mb-3 text-blue-500 opacity-50"></i>
                            <p class="text-lg">Select a batch and month to generate attendance report</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Create New Attendance Section (initially hidden) -->
            <div id="createAttendanceSection" style="display: none;" class="section-transition">
                <div class="card animate__animated animate__fadeInUp">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-plus-circle text-blue-500 mr-2"></i> Create New Attendance
                    </h2>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert-error animate__animated animate__fadeIn">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?php 
                            echo $_SESSION['error_message']; 
                            unset($_SESSION['error_message']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert-success animate__animated animate__fadeIn">
                            <i class="fas fa-check-circle mr-2"></i>
                            <?php 
                            echo $_SESSION['success_message']; 
                            unset($_SESSION['success_message']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="attendance.php" method="POST" class="create-attendance-form">
                        <div class="form-group">
                            <label for="createBatch" class="form-label flex items-center">
                                <i class="fas fa-users mr-2 text-blue-500"></i> Select Batch
                            </label>
                            <select id="createBatch" name="batch_id" class="minimal-input" required>
                                <option value="">-- Select Batch --</option>
                                <?php foreach ($batches as $batch): ?>
                                <option value="<?= htmlspecialchars($batch['batch_id']) ?>" 
                                    <?= ($preselected_batch === $batch['batch_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($batch['batch_id'] . ' - ' . $batch['course_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="createDate" class="form-label flex items-center">
                                <i class="fas fa-calendar-day mr-2 text-blue-500"></i> Select Date
                            </label>
                            <input type="text" id="createDate" name="date" class="minimal-input create-date-picker" required>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="submit" name="create_attendance" class="btn-primary hover:animate-pulse">
                                <i class="fas fa-plus-circle mr-2"></i> Create Attendance
                            </button>
                            <a href="attendance.php" class="btn-gray">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div id="loadingModal" class="modal-overlay">
        <div class="modal-content flex flex-col items-center justify-center p-8">
            <div class="spinner mb-4"></div>
            <h3 class="text-lg font-medium text-gray-800">Processing...</h3>
            <p class="text-gray-600 text-sm mt-2">Please wait while we process your request</p>
        </div>
    </div>

    <!-- Success Toast -->
    <div id="successToast" class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-y-10 opacity-0 transition-all duration-300 z-50">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span id="toastMessage">Operation completed successfully!</span>
        </div>
    </div>

    <!-- Include JavaScript files -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <script>
    $(document).ready(function() {
        // Show loading modal
        function showLoading() {
            $('#loadingModal').addClass('active');
        }
        
        // Hide loading modal
        function hideLoading() {
            $('#loadingModal').removeClass('active');
        }
        
        // Show toast message
        function showToast(message, isSuccess = true) {
            const toast = $('#successToast');
            toast.removeClass('bg-red-500').addClass('bg-green-500');
            
            if (!isSuccess) {
                toast.removeClass('bg-green-500').addClass('bg-red-500');
                toast.find('i').removeClass('fa-check-circle').addClass('fa-exclamation-circle');
            } else {
                toast.find('i').removeClass('fa-exclamation-circle').addClass('fa-check-circle');
            }
            
            $('#toastMessage').text(message);
            toast.removeClass('translate-y-10 opacity-0').addClass('translate-y-0 opacity-100');
            
            setTimeout(() => {
                toast.removeClass('translate-y-0 opacity-100').addClass('translate-y-10 opacity-0');
            }, 3000);
        }
        
        // Initialize date picker (use preselected date if available, otherwise default to today)
        flatpickr("#dateFilter", {
            dateFormat: "Y-m-d",
            defaultDate: <?= $preselected_date ? "'" . $preselected_date . "'" : 'new Date()' ?>,
            allowInput: true
        });
        
        // Initialize create attendance date picker
        flatpickr(".create-date-picker", {
            dateFormat: "Y-m-d",
            defaultDate: new Date(),
            allowInput: true
        });
        
        // Toggle between sections with animation
        function switchSection(sectionToShow) {
            const sections = ['manualAttendanceSection', 'uploadExcelSection', 'monthlyViewSection', 'createAttendanceSection'];
            const buttons = ['showManualBtn', 'showUploadBtn', 'showMonthlyBtn', 'showCreateBtn'];
            
            sections.forEach(section => {
                const el = $(`#${section}`);
                if (section === sectionToShow) {
                    el.show().addClass('animate__fadeIn');
                } else {
                    el.hide().removeClass('animate__fadeIn');
                }
            });
            
            buttons.forEach(button => {
                const btn = $(`#${button}`);
                if (button === sectionToShow.replace('Section', 'Btn')) {
                    btn.addClass('active');
                } else {
                    btn.removeClass('active');
                }
            });
        }
        
        $('#showManualBtn').click(() => switchSection('manualAttendanceSection'));
        $('#showUploadBtn').click(() => switchSection('uploadExcelSection'));
        $('#showMonthlyBtn').click(() => switchSection('monthlyViewSection'));
        $('#showCreateBtn').click(() => {
            switchSection('createAttendanceSection');
            <?php if (!empty($preselected_batch)): ?>
                $('#createBatch').val('<?= $preselected_batch ?>');
            <?php endif; ?>
        });
        
        // Initialize DataTable
        var table = $('#attendanceTable').DataTable({
            ajax: {
                url: 'attendance_api.php?action=fetch',
                data: function(d) {
                    return {
                        batch_id: $('#batchFilter').val() || '<?= $preselected_batch ?>',
                        date: $('#dateFilter').val() || '<?= $preselected_date ?>'
                    };
                },
                dataSrc: 'data',
                beforeSend: showLoading,
                complete: hideLoading
            },
            columns: [
                { 
                    data: 'student_name',
                    render: function(data, type, row) {
                        return `<span class="student-name font-medium">${data}</span>`;
                    }
                },
                { 
                    data: 'batch_id',
                    render: function(data, type, row) {
                        return `<span class="batch-id text-gray-600">${data}</span>`;
                    }
                },
                { 
                    data: null,
                    render: function(data, type, row) {
                        let statusClass = row.status === 'Present' ? 'status-present' : 
                                        row.status === 'Late' ? 'status-late' : 'status-absent';
                        let statusText = row.status;
                        
                        return `
                            <div class="flex items-center gap-3">
                                <span class="status-badge ${statusClass}">${statusText}</span>
                                <label class="switch">
                                    <input type="checkbox" class="status-toggle" data-id="${row.id}" 
                                        ${row.status === 'Present' || row.status === 'Late' ? 'checked' : ''}>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        `;
                    }
                },
                { 
                    data: null,
                    render: function(data, type, row) {
                        let isPresent = row.status === 'Present' || row.status === 'Late';
                        return `
                            <div class="flex justify-center">
                                <label class="switch camera-slider">
                                    <input type="checkbox" class="camera-toggle" data-id="${row.id}" 
                                        ${row.camera_status === 'On' ? 'checked' : ''}
                                        ${!isPresent ? 'disabled' : ''}>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        `;
                    }
                },
                { 
                    data: 'remarks',
                    render: function(data, type, row) {
                        if (data && (row.status === 'Absent' || row.status === 'Late')) {
                            return `
                                <div class="flex items-center gap-3">
                                    <div class="remarks-tooltip">
                                        <span class="text-gray-600 truncate max-w-xs">${data || 'N/A'}</span>
                                        <span class="tooltiptext">${data || 'No remarks'}</span>
                                    </div>
                                    <input type="text" class="remarks-input" data-id="${row.id}" 
                                           value="${data || ''}" placeholder="Add remarks" style="display: none;">
                                </div>
                            `;
                        }
                        return `
                            <input type="text" class="remarks-input" data-id="${row.id}" 
                                   value="${data || ''}" placeholder="Add remarks">
                        `;
                    }
                }
            ],
            responsive: true,
            language: {
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                search: "Search:",
                paginate: {
                    previous: "<i class='fas fa-chevron-left'></i>",
                    next: "<i class='fas fa-chevron-right'></i>"
                }
            },
            dom: '<"flex justify-between items-center mb-4"lf><"table-responsive"t><"flex justify-between items-center mt-4"ip>',
            initComplete: function() {
                initializeCameraToggles();
            }
        });
        
        // Reload table when filters change
        $('#batchFilter, #dateFilter').change(function() {
            table.ajax.reload();
        });
        
        // If batch_id was preselected, trigger the change event to load data immediately
        <?php if (!empty($preselected_batch)): ?>
        $(document).ready(function() {
            table.ajax.reload();
        });
        <?php endif; ?>
        
        // Mark all present
        $('#markAllPresent').click(function() {
            let batchId = $('#batchFilter').val();
            let date = $('#dateFilter').val();
            
            if (!date) {
                showToast('Please select a date first', false);
                return;
            }
            
            if (!batchId) {
                showToast('Please select a batch first', false);
                return;
            }
            
            if (confirm('Mark all students as Present for this batch on ' + date + '?')) {
                showLoading();
                $.post('attendance_api.php', {
                    action: 'mark_all_present',
                    batch_id: batchId,
                    date: date
                }, function(response) {
                    hideLoading();
                    if (response.success) {
                        showToast('All students marked as present');
                        table.ajax.reload();
                    } else {
                        showToast(response.message || 'Error marking attendance', false);
                    }
                }, 'json').fail(() => {
                    hideLoading();
                    showToast('Error processing request', false);
                });
            }
        });
        
        // Save attendance changes
        $('#saveAttendance').click(function() {
            let updates = [];
            
            $('.status-toggle').each(function() {
                let id = $(this).data('id');
                let status = $(this).is(':checked') ? 'Present' : 'Absent';
                let cameraStatus = $(`.camera-toggle[data-id="${id}"]`).is(':checked') ? 'On' : 'Off';
                let remarks = $(`.remarks-input[data-id="${id}"]`).val();
                
                updates.push({
                    id: id,
                    status: status,
                    camera_status: cameraStatus,
                    remarks: remarks
                });
            });
            
            if (updates.length === 0) {
                showToast('No changes to save', false);
                return;
            }
            
            showLoading();
            // Send updates in batches if there are many
            Promise.all(updates.map(update => {
                return $.post('attendance_api.php', {
                    action: 'update',
                    id: update.id,
                    status: update.status,
                    camera_status: update.camera_status,
                    remarks: update.remarks
                });
            })).then(() => {
                hideLoading();
                showToast('Attendance updated successfully');
                table.ajax.reload();
            }).catch(() => {
                hideLoading();
                showToast('Error saving attendance', false);
            });
        });
        
        // Show remarks input when status is Absent
        $(document).on('change', '.status-toggle', function() {
            let isPresent = $(this).is(':checked');
            let id = $(this).data('id');
            let remarksInput = $(`.remarks-input[data-id="${id}"]`);
            let statusBadge = $(this).closest('.flex').find('.status-badge');
            let cameraToggle = $(`.camera-toggle[data-id="${id}"]`);
            
            if (!isPresent) {
                remarksInput.show().addClass('animate__animated animate__fadeIn');
                statusBadge.removeClass('status-present status-late').addClass('status-absent').text('Absent');
                // Force camera to Off when absent
                cameraToggle.prop('checked', false).trigger('change');
                cameraToggle.prop('disabled', true);
            } else {
                remarksInput.hide();
                statusBadge.removeClass('status-absent').addClass('status-present').text('Present');
                cameraToggle.prop('disabled', false);
            }
        });
        
        // Initialize camera toggle states on page load/table reload
        function initializeCameraToggles() {
            $('.status-toggle').each(function() {
                let isPresent = $(this).is(':checked');
                let id = $(this).data('id');
                let cameraToggle = $(`.camera-toggle[data-id="${id}"]`);
                
                if (!isPresent) {
                    cameraToggle.prop('disabled', true);
                }
            });
        }
        
        // Call this after table loads
        table.on('draw.dt', function() {
            initializeCameraToggles();
        });
        
        // Initialize month picker
        flatpickr("#monthFilter", {
            dateFormat: "Y-m",
            defaultDate: new Date(),
            allowInput: true
        });
        
        // Generate monthly report
        $('#generateMonthlyReport').click(function() {
            let batchId = $('#monthlyBatchFilter').val();
            let month = $('#monthFilter').val();
            
            if (!batchId) {
                showToast('Please select a batch first', false);
                return;
            }
            
            if (!month) {
                showToast('Please select a month first', false);
                return;
            }
            
            showLoading();
            $.get('attendance_api.php', {
                action: 'monthly_summary',
                batch_id: batchId,
                month: month
            }, function(response) {
                hideLoading();
                if (response.success) {
                    renderMonthlyReport(response);
                    $('#exportMonthlyReport').prop('disabled', false);
                    showToast('Report generated successfully');
                } else {
                    showToast(response.message || 'Error generating report', false);
                }
            }, 'json').fail(() => {
                hideLoading();
                showToast('Error generating report', false);
            });
        });
        
        // Export monthly report
        $('#exportMonthlyReport').click(function() {
            let batchId = $('#monthlyBatchFilter').val();
            let month = $('#monthFilter').val();
            
            if (!batchId || !month) return;
            
            showLoading();
            window.location.href = `export_monthly_report.php?batch_id=${batchId}&month=${month}`;
        });
        
        // Function to render monthly report
        function renderMonthlyReport(data) {
            let html = `
                <div class="mb-6 animate__animated animate__fadeIn">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">${data.month_name} Attendance Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white p-4 rounded-lg shadow border border-gray-100 hover:shadow-md transition-shadow">
                            <p class="text-sm text-gray-500">Total Classes</p>
                            <p class="text-2xl font-bold">${data.total_classes}</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow border border-gray-100 hover:shadow-md transition-shadow">
                            <p class="text-sm text-gray-500">Present</p>
                            <p class="text-2xl font-bold text-green-600">${data.total_present}</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow border border-gray-100 hover:shadow-md transition-shadow">
                            <p class="text-sm text-gray-500">Absent</p>
                            <p class="text-2xl font-bold text-red-600">${data.total_absent}</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow border border-gray-100 hover:shadow-md transition-shadow">
                            <p class="text-sm text-gray-500">Late</p>
                            <p class="text-2xl font-bold text-yellow-600">${data.total_late}</p>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <h4 class="font-bold text-gray-800 mb-3">Overall Attendance: ${data.attendance_percentage}%</h4>
                        <div class="w-full bg-gray-200 rounded-full h-4">
                            <div class="bg-blue-500 h-4 rounded-full progress-bar" style="width: ${data.attendance_percentage}%"></div>
                        </div>
                    </div>
                    
                    <h4 class="font-bold text-gray-800 mb-3">Student-wise Attendance</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Present</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Absent</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Late</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attendance %</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
            `;
            
            data.students.forEach(student => {
                html += `
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">${student.student_name}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${student.present_count}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${student.absent_count}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${student.late_count}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="mr-2">${student.attendance_percentage}%</span>
                                <div class="w-24 bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full progress-bar" style="width: ${student.attendance_percentage}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            $('#monthlyReportContainer').html(html);
            
            // Animate progress bars
            setTimeout(() => {
                $('.progress-bar').each(function() {
                    $(this).css('width', $(this).attr('style').split('width:')[1].split('%')[0] + '%');
                });
            }, 100);
        }
    });
    </script>
</body>
</html>