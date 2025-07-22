<?php
// droplist.php
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get all dropped students with additional statistics
$query = "SELECT s.*, u.name as processed_by_name, b.course_name, b.batch_id
          FROM students s
          LEFT JOIN users u ON s.dropout_processed_by = u.id
          LEFT JOIN batches b ON s.batch_name = b.batch_id
          WHERE s.current_status = 'dropped'
          ORDER BY s.dropout_date DESC";
$dropped_students = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Get statistics for dashboard
$stats_query = "SELECT 
                COUNT(*) as total_dropped,
                COUNT(CASE WHEN dropout_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_dropped,
                (SELECT COUNT(*) FROM students WHERE current_status = 'active') as total_active
                FROM students WHERE current_status = 'dropped'";
$stats = $db->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Handle reactivation if requested
if (isset($_POST['reactivate'])) {
    $student_id = $_POST['student_id'];
    $reason = $_POST['reactivation_reason'];
    
    $stmt = $db->prepare("UPDATE students SET current_status = 'active', dropout_date = NULL, 
                         dropout_reason = NULL, dropout_processed_by = NULL, dropout_processed_at = NULL
                         WHERE student_id = ?");
    $stmt->execute([$student_id]);
    
    // Log the reactivation
    $log_stmt = $db->prepare("INSERT INTO student_status_log (student_id, action, reason, processed_by, processed_at)
                             VALUES (?, 'reactivated', ?, ?, NOW())");
    $log_stmt->execute([$student_id, $reason, $_SESSION['user_id']]);
    
    header("Location: drop_list.php?success=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Drop List - ASD Academy</title>
    <!-- Primary Tailwind CDN with fallback -->
    <link rel="stylesheet" href="../assets/css/tailwind.min.css">
    <!-- Add this before your custom script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.Tailwind || document.write('<script src="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.js"><\/script>')
    </script>
    <!-- Font Awesome from jsDelivr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }
        @media (min-width: 768px) {
            body {
                margin-left: 16rem;
            }
        }
        .sidebar-link:hover {
            background-color: #f0f7ff;
            transform: translateX(4px);
            transition: all 0.2s ease;
        }
        .sidebar-link.active {
            background-color: #e1f0ff;
            border-left: 4px solid #3b82f6;
        }
        .main-content {
            padding: 2rem;
            animation: fadeIn 0.5s ease-out;
            width: 100%;
            min-height: 100vh;
        }
        @media (min-width: 768px) {
            .main-content {
                width: calc(100% - 16rem);
                margin-left: 16rem;
            }
        }
        .action-btns { white-space: nowrap; }
        .reactivate-form { 
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .reactivate-form.show {
            max-height: 100px;
            margin-top: 10px;
        }
        .table-responsive { 
            margin: 20px 0;
            animation: slideUp 0.4s ease-out;
        }
        .stat-card {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .badge {
            transition: all 0.2s ease;
        }
        .badge:hover {
            transform: scale(1.05);
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
        .highlight-row {
            animation: highlight 2s;
        }
        @keyframes highlight {
            0% { background-color: rgba(255, 255, 0, 0.3); }
            100% { background-color: transparent; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-user-minus mr-3 text-blue-500"></i>
                    Student Drop Management
                </h2>
                <p class="text-sm text-gray-500 mt-1">Track and manage students who have left the program</p>
            </div>
            <button onclick="toggleSidebar()" class="md:hidden bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-lg transition-all duration-300 transform hover:scale-105">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <!-- Stats Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="stat-card bg-white rounded-lg shadow p-4 border-l-4 border-red-500 animate__animated animate__fadeInLeft">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Dropped</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $stats['total_dropped'] ?></p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-user-slash text-red-500 text-xl"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-xs font-medium <?= $stats['recent_dropped'] > 0 ? 'text-red-500' : 'text-green-500' ?>">
                        <i class="fas <?= $stats['recent_dropped'] > 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?> mr-1"></i>
                        <?= $stats['recent_dropped'] ?> in last 30 days
                    </span>
                </div>
            </div>
            
            <div class="stat-card bg-white rounded-lg shadow p-4 border-l-4 border-blue-500 animate__animated animate__fadeIn">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Currently Active</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $stats['total_active'] ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-user-check text-blue-500 text-xl"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-xs font-medium text-blue-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Total enrolled students
                    </span>
                </div>
            </div>
            
            <div class="stat-card bg-white rounded-lg shadow p-4 border-l-4 border-purple-500 animate__animated animate__fadeInRight">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Dropout Rate</p>
                        <p class="text-2xl font-bold text-gray-800">
                            <?= $stats['total_active'] > 0 ? 
                                round(($stats['total_dropped'] / ($stats['total_active'] + $stats['total_dropped'])) * 100, 1) : 0 ?>%
                        </p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-chart-line text-purple-500 text-xl"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-xs font-medium text-purple-500">
                        <i class="fas fa-percentage mr-1"></i>
                        Historical rate
                    </span>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow overflow-hidden animate__animated animate__fadeInUp">
            <div class="p-4 border-b bg-gradient-to-r from-blue-50 to-gray-50">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 flex items-center">
                            <i class="fas fa-list-ul mr-2 text-blue-500"></i>
                            Dropped Students List
                        </h3>
                        <p class="text-sm text-gray-500">Review and manage student dropouts</p>
                    </div>
                    <div class="mt-2 md:mt-0">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-info-circle mr-1"></i>
                            <?= count($dropped_students) ?> records found
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive p-4">
                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 animate__animated animate__fadeInDown" role="alert">
                        <span class="block sm:inline">
                            <i class="fas fa-check-circle mr-2"></i>
                            Student was successfully reactivated and returned to active status.
                        </span>
                        <button onclick="this.parentElement.remove()" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>
                
                <table id="dropTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Drop Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed By</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($dropped_students as $student): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150 <?= isset($_GET['reactivated']) && $_GET['reactivated'] == $student['student_id'] ? 'highlight-row' : '' ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <?= htmlspecialchars($student['student_id']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <i class="fas fa-user text-blue-500"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($student['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?= htmlspecialchars($student['course_name']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($student['batch_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="flex items-center">
                                    <i class="fas fa-calendar-day mr-2 text-gray-400"></i>
                                    <?= date('M j, Y', strtotime($student['dropout_date'])) ?>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <?= round((time() - strtotime($student['dropout_date'])) / (60 * 60 * 24)) ?> days ago
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="flex items-center">
                                    <i class="fas fa-comment-alt mr-2 text-gray-400"></i>
                                    <?= htmlspecialchars($student['dropout_reason']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="flex items-center">
                                    <i class="fas fa-user-tie mr-2 text-gray-400"></i>
                                    <?= htmlspecialchars($student['processed_by_name'] ?? 'System') ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium action-btns">
                                <button class="reactivate-btn bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-md text-sm shadow-md transition-all duration-300 transform hover:scale-105"
                                        data-student-id="<?= $student['student_id'] ?>">
                                    <i class="fas fa-user-plus mr-1"></i> Reactivate
                                </button>
                                <div class="reactivate-form" id="reactivate-form-<?= $student['student_id'] ?>">
                                    <form method="POST" class="mt-2 bg-blue-50 p-3 rounded-lg">
                                        <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['student_id']) ?>">
                                        <div class="flex flex-col space-y-2">
                                            <label class="text-sm font-medium text-gray-700">Reactivation Reason</label>
                                            <input type="text" name="reactivation_reason" 
                                                class="flex-1 px-3 py-2 border rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                                                placeholder="Why is this student being reactivated?" required>
                                            <div class="flex space-x-2">
                                                <button type="submit" name="reactivate" 
                                                        class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-2 rounded-md text-sm shadow-md transition-all duration-300 transform hover:scale-105 flex-1">
                                                    <i class="fas fa-check mr-1"></i> Confirm Reactivation
                                                </button>
                                                <button type="button" onclick="cancelReactivation('<?= $student['student_id'] ?>')"
                                                        class="bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white px-4 py-2 rounded-md text-sm shadow-md transition-all duration-300 transform hover:scale-105">
                                                    <i class="fas fa-times mr-1"></i> Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dropTable').DataTable({
                responsive: true,
                order: [[4, 'desc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search dropped students...",
                    lengthMenu: "Show _MENU_ students per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ dropped students",
                    infoEmpty: "No dropped students found",
                    infoFiltered: "(filtered from _MAX_ total students)"
                },
                initComplete: function() {
                    $('.dataTables_filter input').addClass('border rounded px-3 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500');
                    $('.dataTables_length select').addClass('border rounded px-3 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500');
                }
            });
            
            $('.reactivate-btn').click(function() {
                const studentId = $(this).data('student-id');
                $(this).addClass('hidden');
                $('#reactivate-form-' + studentId).addClass('show');
            });
            
            // Highlight any row that was just reactivated
            const urlParams = new URLSearchParams(window.location.search);
            if(urlParams.has('success')) {
                // Scroll to the top of the table
                $('html, body').animate({
                    scrollTop: $('.table-responsive').offset().top - 20
                }, 500);
            }
        });
        
        function cancelReactivation(studentId) {
            $('#reactivate-form-' + studentId).removeClass('show');
            $('[data-student-id="' + studentId + '"]').removeClass('hidden');
        }
        
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("-translate-x-full");
            sidebar.classList.toggle("md:translate-x-0");
            
            // Toggle body margin
            if (sidebar.classList.contains("-translate-x-full")) {
                document.body.style.marginLeft = "0";
            } else {
                document.body.style.marginLeft = "16rem";
            }
        }
    </script>
    <?php include '../footer.php'; ?>
</body>
</html>