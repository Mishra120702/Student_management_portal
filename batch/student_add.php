<?php
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$batch_id = isset($_GET['batch_id']) ? $_GET['batch_id'] : null;

if (!$batch_id) {
    header("Location: ../batch/batch_list.php");
    exit();
}

$students = [];
$statuses = [];
$batch = [];
$nameFilter = $statusFilter = $enrollmentDateFrom = $enrollmentDateTo = "";

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Get batch details
    $stmt = $db->prepare("SELECT batch_id, course_name FROM batches WHERE batch_id = ?");
    $stmt->execute([$batch_id]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$batch) {
        header("Location: ../batch/batch_list.php");
        exit();
    }

    // Initialize filter variables
    $nameFilter = $_GET['name'] ?? '';
    $statusFilter = $_GET['status'] ?? '';
    $enrollmentDateFrom = $_GET['enrollment_from'] ?? '';
    $enrollmentDateTo = $_GET['enrollment_to'] ?? '';

    // Prepare base query
    $query = "SELECT 
                s.student_id,
                s.first_name,
                s.last_name,
                s.email,
                s.phone_number,
                s.enrollment_date,
                s.current_status
              FROM students s
              WHERE (s.batch_name IS NULL OR s.batch_name != :batch_id)";

    if (!empty($nameFilter)) {
        $query .= " AND (s.first_name LIKE :name OR s.last_name LIKE :name)";
    }
    if (!empty($statusFilter)) {
        $query .= " AND s.current_status = :status";
    }
    if (!empty($enrollmentDateFrom)) {
        $query .= " AND s.enrollment_date >= :enrollment_from";
    }
    if (!empty($enrollmentDateTo)) {
        $query .= " AND s.enrollment_date <= :enrollment_to";
    }

    $query .= " ORDER BY s.first_name, s.last_name";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':batch_id', $batch_id);

    if (!empty($nameFilter)) {
        $stmt->bindValue(':name', '%' . $nameFilter . '%');
    }
    if (!empty($statusFilter)) {
        $stmt->bindValue(':status', $statusFilter);
    }
    if (!empty($enrollmentDateFrom)) {
        $stmt->bindValue(':enrollment_from', $enrollmentDateFrom);
    }
    if (!empty($enrollmentDateTo)) {
        $stmt->bindValue(':enrollment_to', $enrollmentDateTo);
    }

    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get distinct statuses for dropdown
    $statusStmt = $db->query("SELECT DISTINCT current_status FROM students");
    $statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_ids'])) {
        $selectedStudents = $_POST['student_ids'];

        $db->beginTransaction();

        $updateStmt = $db->prepare("UPDATE students SET batch_name = ? WHERE student_id = ?");
        foreach ($selectedStudents as $student_id) {
            $updateStmt->execute([$batch_id, $student_id]);
        }

        $updateBatchStmt = $db->prepare(
            "UPDATE batches SET current_enrollment = (SELECT COUNT(*) FROM students WHERE batch_name = ?) WHERE batch_id = ?"
        );
        $updateBatchStmt->execute([$batch_id, $batch_id]);

        $db->commit();

        header("Location: batch_view.php?batch_id=$batch_id&success=Students+added+to+batch+successfully");
        exit();
    }

} catch (PDOException $e) {
    if (isset($db)) {
        $db = null; // Close connection
    }

    echo "<div style='padding:20px;background:#ffe0e0;color:#a00;font-weight:bold;'>Something went wrong. Please try again later.</div>";
    exit();
} finally {
    // Properly close all statements
    if (isset($stmt)) {
        $stmt = null;
    }
    if (isset($statusStmt)) {
        $statusStmt = null;
    }
    if (isset($updateStmt)) {
        $updateStmt = null;
    }
    if (isset($updateBatchStmt)) {
        $updateBatchStmt = null;
    }
    if (isset($db)) {
        $db = null; // Close connection in all cases
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Students to <?= htmlspecialchars($batch['batch_id']) ?> | ASD Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-50">
    <?php
    include '../header.php';
    include '../sidebar.php';
    ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Back button -->
            <a href="batch_view.php?batch_id=<?= $batch_id ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4">
                <i class="fas fa-arrow-left mr-2"></i> Back to Batch
            </a>
            
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Add Students to Batch</h1>
                <p class="text-gray-600 mb-4">Batch: <?= htmlspecialchars($batch['batch_id']) ?> - <?= htmlspecialchars($batch['course_name']) ?></p>
                
                <!-- Filter Section -->
                <form method="GET" class="mb-6">
                    <input type="hidden" name="batch_id" value="<?= $batch_id ?>">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($nameFilter) ?>" 
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                   placeholder="Search by name">
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
                            <label for="enrollment_from" class="block text-sm font-medium text-gray-700 mb-1">Enrollment From</label>
                            <input type="date" id="enrollment_from" name="enrollment_from" value="<?= htmlspecialchars($enrollmentDateFrom) ?>" 
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="enrollment_to" class="block text-sm font-medium text-gray-700 mb-1">Enrollment To</label>
                            <input type="date" id="enrollment_to" name="enrollment_to" value="<?= htmlspecialchars($enrollmentDateTo) ?>" 
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-between items-center">
                        <div class="flex space-x-2">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                                <i class="fas fa-filter mr-2"></i> Apply Filters
                            </button>
                            <a href="student_add.php?batch_id=<?= $batch_id ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg flex items-center">
                                <i class="fas fa-redo mr-2"></i> Reset
                            </a>
                        </div>
                        <button type="submit" form="studentForm" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                            <i class="fas fa-plus mr-2"></i> Add Selected Students to Batch
                        </button>
                    </div>
                </form>
                
                <!-- Students List -->
                <form method="POST" action="student_add.php?batch_id=<?= $batch_id ?>" id="studentForm">
                    <?php if (count($students) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Select</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled Since</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox" name="student_ids[]" value="<?= htmlspecialchars($student['student_id']) ?>" 
                                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                        <i class="fas fa-user text-gray-500"></i>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($student['student_id']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?= htmlspecialchars($student['email'] ?? 'N/A') ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?= htmlspecialchars($student['phone_number'] ?? 'N/A') ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?= date('M j, Y', strtotime($student['enrollment_date'])) ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs rounded-full 
                                                    <?= $student['current_status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                       ($student['current_status'] === 'dropped' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                                    <?= ucfirst($student['current_status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-users-slash text-gray-400 text-4xl mb-2"></i>
                            <p class="text-gray-600">No students found matching your criteria</p>
                            <p class="text-gray-500 mt-2">All students are either already in this batch or no students exist</p>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Select all checkbox functionality
        $('#selectAll').change(function() {
            $('input[name="student_ids[]"]').prop('checked', $(this).prop('checked'));
        });

        // Form submission with confirmation
        $('#studentForm').submit(function(e) {
            const selectedCount = $('input[name="student_ids[]"]:checked').length;
            if (selectedCount === 0) {
                e.preventDefault();
                alert('Please select at least one student to add to the batch.');
                return false;
            }
            
            return confirm(`Are you sure you want to add ${selectedCount} student(s) to this batch?`);
        });
    });
    </script>
</body>
</html>