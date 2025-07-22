<?php
// Database connection
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle delete action
if (isset($_POST['delete_batch'])) {
    $batch_id = $_POST['batch_id'];
    $stmt = $db->prepare("DELETE FROM batches WHERE batch_id = ?");
    $stmt->execute([$batch_id]);
}
// Get the last batch ID from the database
$lastBatch = $db->query("SELECT batch_id FROM batches ORDER BY batch_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$nextBatchId = 'B001'; // Default if no batches exist

if ($lastBatch) {
    // Extract the numeric part and increment
    $lastNumber = (int) substr($lastBatch['batch_id'], 1);
    $nextNumber = $lastNumber + 1;
    $nextBatchId = 'B' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
}

// Handle add new batch action
if (isset($_POST['add_batch'])) {
    $stmt = $db->prepare("INSERT INTO batches (
        batch_id, course_name, start_date, end_date, time_slot, platform, 
        meeting_link, max_students, current_enrollment, academic_year,
        batch_mentor_id, num_students, mode, status, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $_POST['batch_id'],
        $_POST['course_name'],
        $_POST['start_date'],
        $_POST['end_date'],
        $_POST['time_slot'],
        $_POST['platform'],
        $_POST['meeting_link'],
        $_POST['max_students'],
        $_POST['current_enrollment'],
        $_POST['academic_year'],
        $_POST['batch_mentor_id'],
        $_POST['num_students'],
        $_POST['mode'],
        $_POST['status'],
        $_POST['created_by']
    ]);
    
    // Refresh the page to show the new batch
    header("Location: ../batch/batch_list.php");
    exit();
}

// Get filter values from GET parameters
$course_filter = $_GET['course'] ?? '';
$status_filter = $_GET['status'] ?? '';
$mode_filter = $_GET['mode'] ?? '';
$date_filter = $_GET['date_range'] ?? '';

// Build the query with filters
$query = "SELECT b.*, t.name as mentor_name 
          FROM batches b
          LEFT JOIN trainers t ON b.batch_mentor_id = t.id
          WHERE 1=1";
$params = [];

if (!empty($course_filter)) {
    $query .= " AND b.course_name LIKE ?";
    $params[] = "%$course_filter%";
}

if (!empty($status_filter)) {
    $query .= " AND b.status = ?";
    $params[] = $status_filter;
}

if (!empty($mode_filter)) {
    $query .= " AND b.mode = ?";
    $params[] = $mode_filter;
}

if (!empty($date_filter)) {
    $dates = explode(' to ', $date_filter);
    if (count($dates) === 2) {
        $query .= " AND b.start_date >= ? AND b.end_date <= ?";
        $params[] = $dates[0];
        $params[] = $dates[1];
    }
}

// Execute the query
$stmt = $db->prepare($query);
$stmt->execute($params);
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Management - ASD Academy</title>
    <!-- Include your existing CSS files here -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/batch.css">
</head>
<body class="bg-gray-50 text-gray-800">
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>
    
    <div class="flex-1 ml-0 md:ml-64 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-chalkboard text-blue-500"></i>
            <span>Batch Management</span>
        </h1>
        <div class="flex items-center space-x-4">
            
        </div>
    </header>

    <div class="p-4 md:p-6">

            <div class="action-bar">
               
                <button id="openModalBtn" class="btn-primary">
                    <i class="fas fa-plus"></i> Add New Batch
                </button>
            </div>
            
            <!-- Filter Card -->
            <div class="card filter-card mb-6">
                <form method="GET" action="">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <input type="text" name="course" id="courseFilter" placeholder="Filter by course..." 
                               class="minimal-input" value="<?= htmlspecialchars($course_filter) ?>">
                        
                        <input type="text" name="date_range" id="dateRangeFilter" placeholder="Select date range" 
                               class="minimal-input date-picker" value="<?= htmlspecialchars($date_filter) ?>">
                        
                        <select name="status" id="statusFilter" class="minimal-input">
                            <option value="">All Statuses</option>
                            <option value="ongoing" <?= $status_filter === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="upcoming" <?= $status_filter === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        
                        <select name="mode" id="modeFilter" class="minimal-input">
                            <option value="">All Modes</option>
                            <option value="online" <?= $mode_filter === 'online' ? 'selected' : '' ?>>Online</option>
                            <option value="offline" <?= $mode_filter === 'offline' ? 'selected' : '' ?>>Offline</option>
                        </select>
                        
                        <div class="flex space-x-2">
                            <button type="submit" class="btn-primary">Apply Filters</button>
                            <a href="../batch/batch_list.php" class="btn-gray">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Batch Table Card -->
            <div class="card bg-white rounded-lg shadow p-4">
                <table id="batchTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Course</th>
                            <th>Dates (Start-End)</th>
                            <th>Time Slot</th>
                            <th>Students</th>
                            <th>Mentor</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($batches as $batch): ?>
                        <tr>
                            <td><a href="../batch/batch_view.php?batch_id=<?= htmlspecialchars($batch['batch_id']) ?>" class="text-blue-600 hover:text-blue-800 font-medium"><?= htmlspecialchars($batch['batch_id']) ?></a></td>
                            <td><?= htmlspecialchars($batch['course_name']) ?></td>
                            <td>
                                <?= date('d-M-Y', strtotime($batch['start_date'])) ?> to 
                                <?= date('d-M-Y', strtotime($batch['end_date'])) ?>
                            </td>
                            <td><?= htmlspecialchars($batch['time_slot'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($batch['current_enrollment'] ?? 0) ?>/<?= htmlspecialchars($batch['max_students'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($batch['mentor_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars(ucfirst($batch['mode'])) ?></td>
                            <td>
                                <?php 
                                $badgeClass = '';
                                if ($batch['status'] === 'ongoing') $badgeClass = 'bg-green-100 text-green-800';
                                else if ($batch['status'] === 'completed') $badgeClass = 'bg-gray-100 text-gray-800';
                                else if ($batch['status'] === 'upcoming') $badgeClass = 'bg-yellow-100 text-yellow-800';
                                else if ($batch['status'] === 'cancelled') $badgeClass = 'bg-red-100 text-red-800';
                                ?>
                                <span class="px-2 py-1 text-xs rounded-full <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($batch['status'])) ?></span>
                            </td>
                            <td>
                                <a href="../batch/edit_batch.php?id=<?= $batch['batch_id'] ?>" class="text-blue-500 hover:text-blue-700 mr-2"><i class="fas fa-edit"></i></a>
                                <a href="../batch/delete_batch.php?id=<?= $batch['batch_id'] ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure you want to delete this batch?')"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Batch Modal -->
    <div id="addBatchModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Batch</h3>
                <span class="close-modal">&times;</span>
            </div>
            <form id="batchForm" method="post" class="space-y-6">
                <input type="hidden" name="add_batch" value="1">
                <input type="hidden" name="created_by" value="1"> <!-- Assuming admin ID is 1 -->
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Batch ID -->
                    <div>
                        <label for="batch_id" class="block text-sm font-medium text-gray-700 mb-1">Batch ID*</label>
                        <input type="text" id="batch_id" name="batch_id" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100"
                               value="<?= htmlspecialchars($nextBatchId) ?>" readonly required>
                    </div>
                    
                    <!-- Course Name -->
                    <div>
                        <label for="course_name" class="block text-sm font-medium text-gray-700 mb-1">Course Name*</label>
                        <input type="text" id="course_name" name="course_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Start Date -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date*</label>
                        <input type="date" id="start_date" name="start_date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>
                    
                    <!-- End Date -->
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date*</label>
                        <input type="date" id="end_date" name="end_date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Time Slot -->
                    <div>
                        <label for="time_slot" class="block text-sm font-medium text-gray-700 mb-1">Time Slot</label>
                        <input type="text" id="time_slot" name="time_slot" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g. 18:00-20:00">
                    </div>
                    
                    <!-- Max Students -->
                    <div>
                        <label for="max_students" class="block text-sm font-medium text-gray-700 mb-1">Max Students*</label>
                        <input type="number" id="max_students" name="max_students" min="1" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Current Enrollment -->
                    <div>
                        <label for="current_enrollment" class="block text-sm font-medium text-gray-700 mb-1">Current Enrollment</label>
                        <input type="number" id="current_enrollment" name="current_enrollment" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <!-- Academic Year -->
                    <div>
                        <label for="academic_year" class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                        <input type="text" id="academic_year" name="academic_year" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g. 2024-25">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Batch Mentor -->
                    <div>
                        <label for="batch_mentor_id" class="block text-sm font-medium text-gray-700 mb-1">Batch Mentor</label>
                        <select id="batch_mentor_id" name="batch_mentor_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Mentor</option>
                            <?php 
                            $mentors = $db->query("SELECT id, name FROM trainers")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($mentors as $mentor): ?>
                                <option value="<?= $mentor['id'] ?>"><?= htmlspecialchars($mentor['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Number of Students -->
                    <div>
                        <label for="num_students" class="block text-sm font-medium text-gray-700 mb-1">Number of Students</label>
                        <input type="number" id="num_students" name="num_students" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Mode -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mode*</label>
                    <div class="flex space-x-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="mode" value="online" 
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500" checked>
                            <span class="ml-2 text-gray-700">Online</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="mode" value="offline" 
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-gray-700">Offline</span>
                        </label>
                    </div>
                </div>

                <!-- Online Fields -->
                <div id="onlineFields">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Platform -->
                        <div>
                            <label for="platform" class="block text-sm font-medium text-gray-700 mb-1">Platform</label>
                            <select id="platform" name="platform" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Platform</option>
                                <option value="Google Meet">Google Meet</option>
                                <option value="Zoom">Zoom</option>
                                <option value="Microsoft Teams">Microsoft Teams</option>
                            </select>
                        </div>                        
                    </div>
                    
                    <!-- Meeting Link -->
                    <div>
                        <label for="meeting_link" class="block text-sm font-medium text-gray-700 mb-1">Meeting Link</label>
                        <input type="url" id="meeting_link" name="meeting_link" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="https://meet.google.com/abc-xyz">
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status*</label>
                    <select id="status" name="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-4 pt-4">
                    <button type="button" onclick="document.getElementById('addBatchModal').style.display='none'" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Create Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Include your existing JS files here -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize date picker
        flatpickr("#dateRangeFilter", {
            mode: "range",
            dateFormat: "Y-m-d",
            allowInput: true
        });
        
        // Initialize DataTable
        $('#batchTable').DataTable({
            responsive: true,
            columnDefs: [
                { targets: [3, 4, 5, 6, 7, 8], orderable: false }
            ]
        });
        
        // Modal functionality
        const modal = document.getElementById("addBatchModal");
        const btn = document.getElementById("openModalBtn");
        const span = document.getElementsByClassName("close-modal")[0];
        
        btn.onclick = function() {
            modal.style.display = "block";
        }
        
        span.onclick = function() {
            modal.style.display = "none";
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        
        // Show/hide online fields based on mode selection
        $('input[name="mode"]').change(function() {
            if ($(this).val() === 'online') {
                $('#onlineFields').show();
            } else {
                $('#onlineFields').hide();
            }
        }).trigger('change');
    });
    </script>
</body>
</html>