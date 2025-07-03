<?php
require_once '../db_connection.php';

// Get batch ID from URL
$batch_id = isset($_GET['batch_id']) ? $_GET['batch_id'] : null;

if (!$batch_id) {
    header("Location: ../batch/batch_list.php");
    exit();
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get batch details with mentor information
    $stmt = $conn->prepare("SELECT 
                            b.batch_id,
                            b.course_name,
                            b.start_date,
                            b.end_date,
                            b.time_slot,
                            b.platform,
                            b.meeting_link,
                            b.max_students,
                            b.current_enrollment,
                            b.num_students,
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
    
    // Get students in this batch (using students table directly)
    $stmt = $conn->prepare("SELECT 
                            s.student_id,
                            s.first_name,
                            s.last_name,
                            s.email,
                            s.phone_number,
                            s.enrollment_date,
                            s.current_status
                        FROM students s
                        WHERE s.batch_name = ?
                        ORDER BY s.first_name, s.last_name");
    $stmt->execute([$batch_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get other available batches for transfer
    $stmt = $conn->prepare("SELECT batch_id, course_name 
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Back button -->
            <a href="../batch/batch_list.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4">
                <i class="fas fa-arrow-left mr-2"></i> 
            </a>
            
            <!-- Batch Header -->
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($batch['course_name']) ?></h1>
                        <p class="text-gray-600">Batch ID: <?= htmlspecialchars($batch['batch_id']) ?></p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                            <?= $batch['status'] === 'ongoing' ? 'bg-blue-100 text-blue-800' : 
                               ($batch['status'] === 'completed' ? 'bg-gray-100 text-gray-800' : 
                               ($batch['status'] === 'upcoming' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')) ?>">
                            <?= htmlspecialchars(ucfirst($batch['status'])) ?>
                        </span>
                    </div>
                </div>
                
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-500">Start Date</p>
                        <p class="font-medium"><?= date('M j, Y', strtotime($batch['start_date'])) ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-500">End Date</p>
                        <p class="font-medium"><?= date('M j, Y', strtotime($batch['end_date'])) ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-500">Mode</p>
                        <p class="font-medium"><?= htmlspecialchars(ucfirst($batch['mode'])) ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-500">Students</p>
                        <p class="font-medium"><?= htmlspecialchars($batch['current_enrollment'] ?? 0) ?>/<?= htmlspecialchars($batch['max_students'] ?? 'N/A') ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Batch Details Sections -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Mentor Information -->
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Batch Mentor</h2>
                    <?php if (!empty($batch['mentor_name'])): ?>
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0 h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-user text-blue-500"></i>
                            </div>
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($batch['mentor_name']) ?></p>
                                <p class="text-sm text-gray-500"><?= htmlspecialchars($batch['mentor_email'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">No mentor assigned to this batch</p>
                    <?php endif; ?>
                </div>
                
                <!-- Platform Information -->
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Platform Details</h2>
                    <?php if ($batch['mode'] === 'online'): ?>
                        <p class="text-sm text-gray-500">Platform</p>
                        <p class="font-medium mb-3"><?= htmlspecialchars($batch['platform'] ?? 'Not specified') ?></p>
                        <?php if (!empty($batch['meeting_link'])): ?>
                            <p class="text-sm text-gray-500">Meeting Link</p>
                            <a href="<?= htmlspecialchars($batch['meeting_link']) ?>" target="_blank" class="font-medium text-blue-600 hover:underline break-all">
                                <?= htmlspecialchars($batch['meeting_link']) ?>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-gray-500">Offline batch - no platform information</p>
                    <?php endif; ?>
                </div>
                
                <!-- Batch Actions -->
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Actions</h2>
                    <div class="space-y-3">
                        <a href="#" class="block w-full px-4 py-2 border border-gray-300 rounded-md text-center text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-envelope mr-2"></i> Send Announcement
                        </a>
                        <a href="../student/manage_students.php?batch_id=<?= $batch['batch_id'] ?>" class="block w-full px-4 py-2 border border-gray-300 rounded-md text-center text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
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
            
            <!-- Students List -->
            <div class="bg-white shadow-md rounded-lg p-6 mt-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-medium text-gray-800">Students (<?= count($students) ?>)</h2>
                    <a href="add_student.php?batch_id=<?= $batch['batch_id'] ?>" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Add Student
                    </a>
                </div>
                
                <?php if (count($students) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled Since</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($students as $student): ?>
                                    <tr>
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <a href="../student/student_view.php?id=<?= $student['student_id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="mailto:<?= $student['email'] ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Email">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                            <a href="../student/edit_student.php?id=<?= $student['student_id'] ?>" class="text-blue-600 hover:text-blue-900" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-users-slash text-gray-400 text-4xl mb-2"></i>
                        <p class="text-gray-600">No students enrolled in this batch yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Shift Students Modal -->
    <div id="shiftStudentsModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Shift Students to Another Batch
                            </h3>
                            <div class="mt-4">
                                <div class="mb-4">
                                    <label for="targetBatch" class="block text-sm font-medium text-gray-700 mb-1">Select Target Batch</label>
                                    <select id="targetBatch" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">-- Select a batch --</option>
                                        <?php foreach ($available_batches as $batch_option): ?>
                                            <option value="<?= htmlspecialchars($batch_option['batch_id']) ?>">
                                                <?= htmlspecialchars($batch_option['batch_id']) ?> - <?= htmlspecialchars($batch_option['course_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="max-h-96 overflow-y-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Select</th>
                                                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                                <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td class="px-3 py-2 whitespace-nowrap">
                                                        <input type="checkbox" class="student-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" 
                                                               value="<?= htmlspecialchars($student['student_id']) ?>">
                                                    </td>
                                                    <td class="px-3 py-2 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900">
                                                            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($student['student_id']) ?></div>
                                                    </td>
                                                    <td class="px-3 py-2 whitespace-nowrap">
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
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirmShiftBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Shift Selected Students
                    </button>
                    <button type="button" id="cancelShiftBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
                <div id="shiftStatusMessage" class="px-4 py-2 hidden"></div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Modal controls
        const modal = $('#shiftStudentsModal');
        const shiftBtn = $('#shiftStudentsBtn');
        const cancelBtn = $('#cancelShiftBtn');
        const confirmBtn = $('#confirmShiftBtn');
        const statusMessage = $('#shiftStatusMessage');
        
        // Open modal
        shiftBtn.click(function() {
            modal.removeClass('hidden');
        });
        
        // Close modal
        cancelBtn.click(function() {
            modal.addClass('hidden');
            statusMessage.addClass('hidden').text('');
        });
        
        // Confirm shift action
        confirmBtn.click(function() {
            const targetBatch = $('#targetBatch').val();
            const selectedStudents = [];
            
            $('.student-checkbox:checked').each(function() {
                selectedStudents.push($(this).val());
            });
            
            if (!targetBatch) {
                showStatusMessage('Please select a target batch', 'error');
                return;
            }
            
            if (selectedStudents.length === 0) {
                showStatusMessage('Please select at least one student', 'error');
                return;
            }
            
            // Disable button during processing
            confirmBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Processing...');
            
            // Send AJAX request
            $.ajax({
                url: 'batch/shift_students.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    current_batch: '<?= $batch_id ?>',
                    target_batch: targetBatch,
                    students: selectedStudents
                },
                // In the AJAX success handler:
success: function(response) {
    // Ensure response is parsed correctly
    if (typeof response === 'string') {
        try {
            response = JSON.parse(response);
        } catch (e) {
            showStatusMessage('Invalid server response', 'error');
            confirmBtn.prop('disabled', false).text('Shift Selected Students');
            return;
        }
    }

    if (response.success) {
        showStatusMessage(response.message, 'success');
        setTimeout(function() {
            location.reload();
        }, 2000);
    } else {
        showStatusMessage(response.message || 'Unknown error occurred', 'error');
        confirmBtn.prop('disabled', false).text('Shift Selected Students');
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
    showStatusMessage(errorMsg, 'error');
    confirmBtn.prop('disabled', false).text('Shift Selected Students');
}
            });
        });
        
        function showStatusMessage(message, type) {
            statusMessage.removeClass('hidden')
                .removeClass('bg-red-100 text-red-800')
                .removeClass('bg-green-100 text-green-800')
                .addClass(type === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800')
                .text(message);
        }
    });
    </script>
</body>
</html> 
