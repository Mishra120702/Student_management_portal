<?php
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
// Get batch ID from URL
$batch_id = isset($_GET['batch_id']) ? $_GET['batch_id'] : null;

if (!$batch_id) {
    header("Location: ../batch/batch_list.php");
    exit();
}

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get batch details with mentor information
    $stmt = $db->prepare("SELECT 
                            b.batch_id,
                            b.course_name,
                            b.start_date,
                            b.end_date,
                            b.time_slot,
                            b.platform,
                            b.meeting_link,
                            b.max_students,
                            b.current_enrollment,
                            b.mode,
                            b.status,
                            b.academic_year,
                            u.name as mentor_name,
                            u.email as mentor_email
                            /* Removed u.phone as it doesn't exist in users table */
                        FROM batches b
                        LEFT JOIN users u ON b.batch_mentor_id = u.id
                        WHERE b.batch_id = ?");
    $stmt->execute([$batch_id]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$batch) {
        header("Location: ../batch/batch_list.php");
        exit();
    }
    
    // Get students in this batch (current and historical for completed batches)
    if ($batch['status'] === 'completed') {
        $stmt = $db->prepare("SELECT 
                                s.student_id,
                                s.first_name,
                                s.last_name,
                                s.email,
                                s.phone_number,
                                s.enrollment_date,
                                s.current_status,
                                IF(s.batch_name = ?, 'current', 'historical') as student_type
                            FROM students s
                            JOIN student_batch_history h ON s.student_id = h.student_id
                            WHERE h.from_batch_id = ? OR s.batch_name = ?
                            GROUP BY s.student_id
                            ORDER BY s.first_name, s.last_name");
        $stmt->execute([$batch_id, $batch_id, $batch_id]);
    } else {
        $stmt = $db->prepare("SELECT 
                                s.student_id,
                                s.first_name,
                                s.last_name,
                                s.email,
                                s.phone_number,
                                s.enrollment_date,
                                s.current_status,
                                'current' as student_type
                            FROM students s
                            WHERE s.batch_name = ?
                            ORDER BY s.first_name, s.last_name");
        $stmt->execute([$batch_id]);
    }
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get other available batches for transfer
    $stmt = $db->prepare("SELECT batch_id, course_name 
                          FROM batches 
                          WHERE batch_id != ? AND status IN ('upcoming', 'ongoing')
                          ORDER BY start_date ASC");
    $stmt->execute([$batch_id]);
    $available_batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch <?= htmlspecialchars($batch['batch_id']) ?> | ASD Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --light-text: #6c757d;
            --success-color: #4bb543;
            --danger-color: #f94144;
        }
        
        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            object-fit: cover;
        }
        
        .stat-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            font-weight: 500;
        }
        
        .nav-tabs .nav-link {
            color: var(--light-text);
            border: none;
            padding: 12px 20px;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link:hover {
            border: none;
            color: var(--primary-color);
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .bg-ongoing {
            background-color: rgba(9, 235, 92, 0.8);
            color: white;
        }
        
        .bg-completed {
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
        }
        
        .bg-upcoming {
            background-color: rgba(255, 193, 7, 0.9);
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>
    
    <div class="flex-1 ml-0 md:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
            <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fas fa-users-class text-blue-500"></i>
                <span>Batch Details</span>
            </h1>
            <div class="flex items-center space-x-4">
                <a href="../batch/batch_list.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </a>
            </div>
        </header>
        
        <div class="p-4 md:p-6">
            <!-- Batch Header Card -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-6 text-white">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                        <div>
                            <h2 class="text-3xl font-bold mb-1"><?= htmlspecialchars($batch['course_name']) ?></h2>
                            <p class="text-blue-100">Batch ID: <?= htmlspecialchars($batch['batch_id']) ?></p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <span class="status-badge <?= $batch['status'] === 'ongoing' ? 'bg-ongoing' : 
                                                       ($batch['status'] === 'completed' ? 'bg-completed' : 'bg-upcoming') ?>">
                                <?= htmlspecialchars(ucfirst($batch['status'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Batch Stats -->
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="stat-card bg-white border border-gray-200 p-4 rounded-lg">
                            <div class="text-sm text-gray-500">Start Date</div>
                            <div class="text-xl font-bold"><?= date('M j, Y', strtotime($batch['start_date'])) ?></div>
                        </div>
                        <div class="stat-card bg-white border border-gray-200 p-4 rounded-lg">
                            <div class="text-sm text-gray-500">End Date</div>
                            <div class="text-xl font-bold"><?= date('M j, Y', strtotime($batch['end_date'])) ?></div>
                        </div>
                        <div class="stat-card bg-white border border-gray-200 p-4 rounded-lg">
                            <div class="text-sm text-gray-500">Mode</div>
                            <div class="text-xl font-bold"><?= htmlspecialchars(ucfirst($batch['mode'])) ?></div>
                        </div>
                        <div class="stat-card bg-white border border-gray-200 p-4 rounded-lg">
                            <div class="text-sm text-gray-500">Students</div>
                            <div class="text-xl font-bold"><?= htmlspecialchars($batch['current_enrollment'] ?? 0) ?>/<?= htmlspecialchars($batch['max_students'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                    
                    <!-- Batch Details Sections -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Mentor Information -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <i class="fas fa-chalkboard-teacher mr-2 text-blue-500"></i> Batch Mentor
                            </h3>
                            <?php if (!empty($batch['mentor_name'])): ?>
                                <div class="flex items-center space-x-4">
                                    <div class="student-avatar">
                                        <i class="fas fa-user text-gray-500"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium"><?= htmlspecialchars($batch['mentor_name']) ?></p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($batch['mentor_email'] ?? 'N/A') ?></p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500"><i class="fas fa-info-circle mr-2"></i> No mentor assigned to this batch</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Platform Information -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <i class="fas fa-laptop mr-2 text-blue-500"></i> Platform Details
                            </h3>
                            <?php if ($batch['mode'] === 'online'): ?>
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-sm text-gray-500">Platform</p>
                                        <p class="font-medium"><?= htmlspecialchars($batch['platform'] ?? 'Not specified') ?></p>
                                    </div>
                                    <?php if (!empty($batch['meeting_link'])): ?>
                                        <div>
                                            <p class="text-sm text-gray-500">Meeting Link</p>
                                            <a href="<?= htmlspecialchars($batch['meeting_link']) ?>" target="_blank" class="font-medium text-blue-600 hover:underline break-all">
                                                <?= htmlspecialchars($batch['meeting_link']) ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500"><i class="fas fa-info-circle mr-2"></i> Offline batch - no platform information</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Batch Actions -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <i class="fas fa-cog mr-2 text-blue-500"></i> Actions
                            </h3>
                            <div class="space-y-3">
                                <a href="#" class="block w-full px-4 py-2 border border-gray-300 rounded-md text-center text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-envelope mr-2"></i> Send Announcement
                                </a>
                                <a href="../student/manage_student.php?batch_id=<?= $batch['batch_id'] ?>" class="block w-full px-4 py-2 border border-gray-300 rounded-md text-center text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-users mr-2"></i> Manage Students
                                </a>
                                <button id="shiftStudentsBtn" class="w-full px-4 py-2 border border-gray-300 rounded-md text-center text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-exchange-alt mr-2"></i> Shift Students to Another Batch
                                </button>
                                <a href="../schedule/schedule.php?batch_id=<?= $batch['batch_id'] ?>" class="block w-full px-4 py-2 border border-gray-300 rounded-md text-center text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-calendar-alt mr-2"></i> View Schedule
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Students Section -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-user-graduate mr-2 text-blue-500"></i> Students (<?= count($students) ?>)
                    </h2>
                    <a href="../batch/student_add.php?batch_id=<?= $batch['batch_id'] ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-2"></i> Add Student
                    </a>
                </div>
                
                <div class="p-6">
                    <?php if (count($students) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Enrolled Since</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td>
                                                <div class="flex items-center">
                                                    <div class="student-avatar">
                                                        <i class="fas fa-user text-gray-500"></i>
                                                    </div>
                                                    <div>
                                                        <div class="font-medium"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></div>
                                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($student['student_id']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($student['email'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($student['phone_number'] ?? 'N/A') ?></td>
                                            <td><?= date('M j, Y', strtotime($student['enrollment_date'])) ?></td>
                                            <td>
                                                <span class="status-badge <?= $student['current_status'] === 'active' ? 'bg-green-400' : 
                                                                           ($student['current_status'] === 'dropped' ? 'bg-danger' : 'bg-warning') ?>">
                                                    <?= ucfirst($student['current_status']) ?>
                                                </span>
                                                <?php if (isset($student['student_type']) && $student['student_type'] === 'historical'): ?>
                                                    <span class="status-badge bg-gray-100 text-gray-800 ml-2">Historical</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="flex space-x-2">
                                                    <a href="../batch/student_view.php?id=<?= $student['student_id'] ?>" class="text-blue-600 hover:text-blue-900" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="mailto:<?= $student['email'] ?>" class="text-blue-600 hover:text-blue-900" title="Email">
                                                        <i class="fas fa-envelope"></i>
                                                    </a>
                                                    <a href="../student/edit_student.php?id=<?= $student['student_id'] ?>" class="text-blue-600 hover:text-blue-900" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-users-slash text-gray-400 fa-4x mb-3"></i>
                            <h5 class="text-gray-600">No students enrolled in this batch yet</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Shift Students Modal -->
    <div id="shiftStudentsModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Shift Students to Another Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label for="targetBatch" class="form-label">Select Target Batch</label>
                        <select id="targetBatch" class="form-select">
                            <option value="">-- Select a batch --</option>
                            <?php foreach ($available_batches as $batch_option): ?>
                                <option value="<?= htmlspecialchars($batch_option['batch_id']) ?>">
                                    <?= htmlspecialchars($batch_option['batch_id']) ?> - <?= htmlspecialchars($batch_option['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th width="50">Select</th>
                                    <th>Student</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input student-checkbox" 
                                                   value="<?= htmlspecialchars($student['student_id']) ?>">
                                        </td>
                                        <td>
                                            <div class="font-medium"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($student['student_id']) ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $student['current_status'] === 'active' ? 'bg-success' : 
                                                                       ($student['current_status'] === 'dropped' ? 'bg-danger' : 'bg-warning') ?>">
                                                <?= ucfirst($student['current_status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmShiftBtn" class="btn btn-primary">
                        <i class="fas fa-exchange-alt mr-2"></i> Shift Selected Students
                    </button>
                </div>
                <div id="shiftStatusMessage" class="alert alert-dismissible fade show mb-0 rounded-0" style="display: none;">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <span id="shiftMessageText"></span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        }
        
        // Modal controls
        $(document).ready(function() {
            const modal = new bootstrap.Modal(document.getElementById('shiftStudentsModal'));
            const shiftBtn = $('#shiftStudentsBtn');
            const confirmBtn = $('#confirmShiftBtn');
            const statusMessage = $('#shiftStatusMessage');
            const messageText = $('#shiftMessageText');
            
            // Open modal
            shiftBtn.click(function() {
                modal.show();
            });
            
            // Confirm shift action
            confirmBtn.click(function() {
                const targetBatch = $('#targetBatch').val();
                const selectedStudents = [];
                
                $('.student-checkbox:checked').each(function() {
                    selectedStudents.push($(this).val());
                });
                
                if (!targetBatch) {
                    showStatusMessage('Please select a target batch', 'danger');
                    return;
                }
                
                if (selectedStudents.length === 0) {
                    showStatusMessage('Please select at least one student', 'danger');
                    return;
                }
                
                // Disable button during processing
                confirmBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Processing...');
                
                // Send AJAX request
                $.ajax({
                    url: '../batch/shift_students.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        current_batch: '<?= $batch_id ?>',
                        target_batch: targetBatch,
                        students: selectedStudents
                    },
                    success: function(response) {
                        if (response.success) {
                            showStatusMessage(response.message, 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showStatusMessage(response.message || 'Unknown error occurred', 'danger');
                            confirmBtn.prop('disabled', false).html('<i class="fas fa-exchange-alt mr-2"></i> Shift Selected Students');
                        }
                    },
                    error: function(xhr, status, error) {
                        let errorMsg = 'An error occurred';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMsg = response.message || errorMsg;
                        } catch (e) {
                            errorMsg = xhr.responseText || errorMsg;
                        }
                        showStatusMessage(errorMsg, 'danger');
                        confirmBtn.prop('disabled', false).html('<i class="fas fa-exchange-alt mr-2"></i> Shift Selected Students');
                    }
                });
            });
            
            function showStatusMessage(message, type) {
                statusMessage.removeClass('alert-success alert-danger')
                           .addClass('alert-' + type)
                           .show();
                messageText.text(message);
            }
        });
    </script>
</body>
</html>