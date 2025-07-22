<?php
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

include '../header.php';
include '../sidebar.php';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Initialize filter variables
    $nameFilter = isset($_GET['name']) ? $_GET['name'] : '';
    $batchFilter = isset($_GET['batch']) ? $_GET['batch'] : '';
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
    $courseFilter = isset($_GET['course']) ? $_GET['course'] : '';
    $enrollmentDateFrom = isset($_GET['enrollment_from']) ? $_GET['enrollment_from'] : '';
    $enrollmentDateTo = isset($_GET['enrollment_to']) ? $_GET['enrollment_to'] : '';
    
    // Pagination variables
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    // Base query for counting total records
    $countQuery = "
        SELECT COUNT(*) as total
        FROM students s
        LEFT JOIN batches b ON s.batch_name = b.batch_id
        WHERE 1=1
    ";
    
    // Base query for fetching data
    $query = "
        SELECT s.student_id, s.first_name, s.last_name, s.email, s.phone_number, 
               s.date_of_birth, s.enrollment_date, s.current_status,
               b.batch_id, b.course_name, b.start_date, b.end_date, b.status as batch_status
        FROM students s
        LEFT JOIN batches b ON s.batch_name = b.batch_id
        WHERE 1=1
    ";
    
    // Apply filters to both queries
    if (!empty($nameFilter)) {
        $query .= " AND (s.first_name LIKE :name OR s.last_name LIKE :name)";
        $countQuery .= " AND (s.first_name LIKE :name OR s.last_name LIKE :name)";
    }
    if (!empty($batchFilter)) {
        $query .= " AND b.batch_id = :batch";
        $countQuery .= " AND b.batch_id = :batch";
    }
    if (!empty($statusFilter)) {
        $query .= " AND s.current_status = :status";
        $countQuery .= " AND s.current_status = :status";
    }
    if (!empty($courseFilter)) {
        $query .= " AND b.course_name = :course";
        $countQuery .= " AND b.course_name = :course";
    }
    if (!empty($enrollmentDateFrom)) {
        $query .= " AND s.enrollment_date >= :enrollment_from";
        $countQuery .= " AND s.enrollment_date >= :enrollment_from";
    }
    if (!empty($enrollmentDateTo)) {
        $query .= " AND s.enrollment_date <= :enrollment_to";
        $countQuery .= " AND s.enrollment_date <= :enrollment_to";
    }
    
    $query .= " ORDER BY s.first_name, s.last_name LIMIT :limit OFFSET :offset";
    
    // First get total count
    $countStmt = $db->prepare($countQuery);
    
    // Bind parameters to count query
    if (!empty($nameFilter)) {
        $countStmt->bindValue(':name', '%' . $nameFilter . '%');
    }
    if (!empty($batchFilter)) {
        $countStmt->bindValue(':batch', $batchFilter);
    }
    if (!empty($statusFilter)) {
        $countStmt->bindValue(':status', $statusFilter);
    }
    if (!empty($courseFilter)) {
        $countStmt->bindValue(':course', $courseFilter);
    }
    if (!empty($enrollmentDateFrom)) {
        $countStmt->bindValue(':enrollment_from', $enrollmentDateFrom);
    }
    if (!empty($enrollmentDateTo)) {
        $countStmt->bindValue(':enrollment_to', $enrollmentDateTo);
    }
    
    $countStmt->execute();
    $totalResults = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalResults / $perPage);
    
    // Now get paginated data
    $stmt = $db->prepare($query);
    
    // Bind parameters to data query
    if (!empty($nameFilter)) {
        $stmt->bindValue(':name', '%' . $nameFilter . '%');
    }
    if (!empty($batchFilter)) {
        $stmt->bindValue(':batch', $batchFilter);
    }
    if (!empty($statusFilter)) {
        $stmt->bindValue(':status', $statusFilter);
    }
    if (!empty($courseFilter)) {
        $stmt->bindValue(':course', $courseFilter);
    }
    if (!empty($enrollmentDateFrom)) {
        $stmt->bindValue(':enrollment_from', $enrollmentDateFrom);
    }
    if (!empty($enrollmentDateTo)) {
        $stmt->bindValue(':enrollment_to', $enrollmentDateTo);
    }
    
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get distinct values for filters
    $batchStmt = $db->query("SELECT DISTINCT batch_id, course_name FROM batches ORDER BY course_name");
    $batches = $batchStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $statusStmt = $db->query("SELECT DISTINCT current_status FROM students");
    $statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $courseStmt = $db->query("SELECT DISTINCT course_name FROM batches ORDER BY course_name");
    $courses = $courseStmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <!-- Primary Tailwind CDN with fallback -->
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <!-- Add this before your custom script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.Tailwind || document.write('<script src="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.js"><\/script>')
    </script>
    <!-- Font Awesome from jsDelivr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-link:hover {
            background-color: #f0f7ff;
        }
        .sidebar-link.active {
            background-color: #e1f0ff;
            border-left: 4px solid #3b82f6;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .info-card {
            transition: all 0.2s ease;
        }
        .info-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .student-row:hover {
            background-color: #f8fafc;
        }
    </style>
    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("-translate-x-full");
            document.getElementById("sidebar").classList.toggle("md:translate-x-0");
        }
        
        function resetFilters() {
            // Get the current URL without query parameters
            const url = window.location.href.split('?')[0];
            // Redirect to the clean URL
            window.location.href = url;
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800">
    <!-- Sidebar -->
    <?php include '../sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="md:ml-64">
        <!-- In the header section of students_list.php, around line 180 -->
<header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
    <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
        <i class="fas fa-users text-blue-500"></i>
        <span>Student Directory</span>
    </h1>
    <div class="flex items-center space-x-4">
        <!-- Add the Drop List button here -->
        <a href="drop_list.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-user-minus mr-2"></i> Drop List
        </a>
        <a href="add_student.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-plus mr-2"></i> Add Student
        </a>
    </div>
</header>

        <div class="container mx-auto px-4 py-8">
            <!-- Filter Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">Filter Students</h2>
                <form id="filterForm" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <input type="hidden" name="page" value="1">
                    
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($nameFilter) ?>" 
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                               placeholder="Search by name">
                    </div>
                    
                    <div>
                        <label for="batch" class="block text-sm font-medium text-gray-700 mb-1">Batch</label>
                        <select id="batch" name="batch" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Batches</option>
                            <?php foreach ($batches as $batch): ?>
                                <option value="<?= htmlspecialchars($batch['batch_id']) ?>" <?= $batchFilter == $batch['batch_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($batch['batch_id']) ?> - <?= htmlspecialchars($batch['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= htmlspecialchars($status) ?>" <?= $statusFilter == $status ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucfirst($status)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="course" class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                        <select id="course" name="course" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= htmlspecialchars($course) ?>" <?= $courseFilter == $course ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($course) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="enrollment_from" class="block text-sm font-medium text-gray-700 mb-1">Enrollment From</label>
                        <input type="date" id="enrollment_from" name="enrollment_from" value="<?= htmlspecialchars($enrollmentDateFrom) ?>" 
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="enrollment_to" class="block text-sm font-medium text-gray-700 mb-1">Enrollment To</label>
                        <input type="date" id="enrollment_to" name="enrollment_to" value="<?= htmlspecialchars($enrollmentDateTo) ?>" 
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                            <i class="fas fa-filter mr-2"></i> Apply Filters
                        </button>
                        <button type="button" onclick="resetFilters()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg flex items-center">
                            <i class="fas fa-redo mr-2"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Student List -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($students as $student): ?>
                                <tr class="student-row hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($student['student_id']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?= htmlspecialchars($student['date_of_birth'] ? date('M d, Y', strtotime($student['date_of_birth'])) : 'N/A') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($student['email'] ?: 'N/A') ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($student['phone_number'] ?: 'N/A') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($student['batch_id']): ?>
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($student['batch_id']) ?></div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars(date('M Y', strtotime($student['start_date']))) ?> - <?= htmlspecialchars(date('M Y', strtotime($student['end_date']))) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-500">No batch assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($student['course_name'] ?: 'N/A') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            <?= $student['current_status'] == 'active' ? 'bg-green-100 text-green-800' : 
                                               ($student['current_status'] == 'inactive' ? 'bg-red-100 text-red-800' : 
                                               'bg-gray-100 text-gray-800') ?>">
                                            <?= htmlspecialchars(ucfirst($student['current_status'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="student_view.php?id=<?= htmlspecialchars($student['student_id']) ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="edit_student.php?id=<?= htmlspecialchars($student['student_id']) ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="#" onclick="confirmDelete('<?= htmlspecialchars($student['student_id']) ?>')" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                        <!-- In the actions column of students_list.php -->
                                        <form action="drop_student.php" method="POST" class="inline">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($student['student_id']) ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900 ml-3" 
                                                    onclick="return confirm('Are you sure you want to drop this student?')">
                                                <i class="fas fa-user-minus"></i> Drop
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No students found matching your criteria.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <div class="mt-4 flex justify-between items-center">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-medium"><?= $offset + 1 ?></span> to <span class="font-medium"><?= min($offset + $perPage, $totalResults) ?></span> of <span class="font-medium"><?= $totalResults ?></span> results
                </div>
                <div class="flex space-x-2">
                    <a href="?<?= 
                        http_build_query(array_merge(
                            $_GET,
                            ['page' => max(1, $page - 1)]
                        ))
                    ?>" class="px-3 py-1 border rounded text-gray-600 bg-white <?= $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100' ?>">
                        Previous
                    </a>
                    <a href="?<?= 
                        http_build_query(array_merge(
                            $_GET,
                            ['page' => min($totalPages, $page + 1)]
                        ))
                    ?>" class="px-3 py-1 border rounded text-gray-600 bg-white <?= $page >= $totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100' ?>">
                        Next
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Profile Modal -->
    <div id="studentModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <!-- Modal content remains the same as in your original file -->
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.student-row');
            
            rows.forEach(row => {
                const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const id = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                if (name.includes(searchTerm) || id.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Confirm delete function
        function confirmDelete(studentId) {
            if (confirm('Are you sure you want to delete this student?')) {
                window.location.href = 'student_delete.php?id=' + studentId;
            }
        }
        
        // Modal functionality remains the same as in your original file
    </script>
</body>
</html>