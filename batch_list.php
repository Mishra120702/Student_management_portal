<?php
// Database connection (same as your batch_list.php)
$db = new PDO('mysql:host=localhost;dbname=asd_academy1', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle delete action
if (isset($_POST['delete_batch'])) {
    $batch_id = $_POST['batch_id'];
    $stmt = $db->prepare("DELETE FROM batches WHERE batch_id = ?");
    $stmt->execute([$batch_id]);
}

// Handle add new batch action
if (isset($_POST['add_batch'])) {
    $stmt = $db->prepare("INSERT INTO batches (
        batch_id, course_name, start_date, end_date, no_of_students, batch_mentor, 
        mode, status, time_slot, platform, link, max_students, current_enrollment, 
        academic_year
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $_POST['batch_id'],
        $_POST['course_name'],
        $_POST['start_date'],
        $_POST['end_date'],
        $_POST['current_enrollment'],
        $_POST['batch_mentor'],
        $_POST['mode'],
        $_POST['status'],
        $_POST['time_slot'],
        $_POST['platform'],
        $_POST['link'],
        $_POST['max_students'],
        $_POST['current_enrollment'],
        $_POST['academic_year']
    ]);
    
    // Refresh the page to show the new batch
    header("Location: batch_list.php");
    exit();
}

// Get filter values from GET parameters
$course_filter = $_GET['course'] ?? '';
$status_filter = $_GET['status'] ?? '';
$mode_filter = $_GET['mode'] ?? '';
$date_filter = $_GET['date_range'] ?? '';

// Build the query with filters
$query = "SELECT * FROM batches WHERE 1=1";
$params = [];

if (!empty($course_filter)) {
    $query .= " AND course_name LIKE ?";
    $params[] = "%$course_filter%";
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

if (!empty($mode_filter)) {
    $query .= " AND mode = ?";
    $params[] = $mode_filter;
}

if (!empty($date_filter)) {
    $dates = explode(' to ', $date_filter);
    if (count($dates) === 2) {
        $query .= " AND start_date >= ? AND end_date <= ?";
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
    <title>ASD Academy - Batch Management</title>
    
    <!-- Include your existing CSS files here -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/batch.css">
</head>
<body>
    <!-- Include your dashboard header/sidebar here -->
    
    <div class="container">
        <div class="action-bar">
            <h2>Batch Management</h2>
            <button id="openModalBtn" class="btn-primary">
                <i class="fas fa-plus"></i> Add New Batch
            </button>
        </div>
        
        <!-- Filter Card -->
        <div class="card filter-card">
            <form method="GET" action="">
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
                
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="batch_list.php" class="btn-gray">Reset</a>
            </form>
        </div>
        
        <!-- Batch Table Card -->
        <div class="card">
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
                        <td><?= htmlspecialchars($batch['batch_id']) ?></td>
                        <td><?= htmlspecialchars($batch['course_name']) ?></td>
                        <td>
                            <?= date('d-M-Y', strtotime($batch['start_date'])) ?> to 
                            <?= date('d-M-Y', strtotime($batch['end_date'])) ?>
                        </td>
                        <td><?= htmlspecialchars($batch['time_slot'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($batch['current_enrollment'] ?? 0) ?>/<?= htmlspecialchars($batch['max_students'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($batch['batch_mentor']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($batch['mode'])) ?></td>
                        <td>
                            <?php 
                            $badgeClass = '';
                            if ($batch['status'] === 'ongoing') $badgeClass = 'bg-success';
                            else if ($batch['status'] === 'completed') $badgeClass = 'bg-secondary';
                            else if ($batch['status'] === 'upcoming') $badgeClass = 'bg-warning';
                            else if ($batch['status'] === 'cancelled') $badgeClass = 'bg-danger';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($batch['status'])) ?></span>
                        </td>
                        <td>
    <a href="edit_batch.php?id=<?= $batch['batch_id'] ?>" class="action-btn">✏️</a>
    <a href="delete_batch.php?id=<?= $batch['batch_id'] ?>" class="action-btn" onclick="return confirm('Are you sure you want to delete this batch?')">🗑️</a>
</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Batch Modal -->
    <div id="addBatchModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Batch</h3>
                <span class="close-modal">&times;</span>
            </div>
            <form id="batchForm" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Batch ID -->
                        <div>
                            <label for="batch_id" class="block text-sm font-medium text-gray-700 mb-1">Batch ID*</label>
                            <input type="text" id="batch_id" name="batch_id" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            <p id="batch_id_error" class="mt-1 text-sm text-red-600 hidden">Batch ID is required</p>
                        </div>
                        
                        <!-- Course Name -->
                        <div>
                            <label for="course_name" class="block text-sm font-medium text-gray-700 mb-1">Course Name*</label>
                            <input type="text" id="course_name" name="course_name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            <p id="course_name_error" class="mt-1 text-sm text-red-600 hidden">Course name is required</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date*</label>
                            <input type="date" id="start_date" name="start_date" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            <p id="start_date_error" class="mt-1 text-sm text-red-600 hidden">Start date is required</p>
                        </div>
                        
                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date*</label>
                            <input type="date" id="end_date" name="end_date" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            <p id="end_date_error" class="mt-1 text-sm text-red-600 hidden">End date must be after start date</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Number of Students -->
                        <div>
                            <label for="num_students" class="block text-sm font-medium text-gray-700 mb-1">Number of Students*</label>
                            <input type="number" id="num_students" name="num_students" min="1" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            <p id="num_students_error" class="mt-1 text-sm text-red-600 hidden">Number must be greater than 0</p>
                        </div>
                        
                        <!-- Batch Mentor -->
                        <div>
                            <label for="batch_mentor" class="block text-sm font-medium text-gray-700 mb-1">Batch Mentor*</label>
                            <select id="batch_mentor" name="batch_mentor" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                                <option value="">Select a mentor</option>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                            <p id="batch_mentor_error" class="mt-1 text-sm text-red-600 hidden">Please select a mentor</p>
                        </div>
                    </div>

                    <!-- Mode (Online/Offline) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mode*</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="mode" value="Online" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500" checked>
                                <span class="ml-2 text-gray-700">Online</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="mode" value="Offline" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-gray-700">Offline</span>
                            </label>
                        </div>
                    </div>

                    <!-- Platform (conditional on mode) -->
                    <div id="platformField">
                        <label for="platform" class="block text-sm font-medium text-gray-700 mb-1">Platform*</label>
                        <select id="platform" name="platform" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select a platform</option>
                            <option value="Google Meet">Google Meet</option>
                            <option value="Zoom">Zoom</option>
                            <option value="Microsoft Teams">Microsoft Teams</option>
                        </select>
                        <p id="platform_error" class="mt-1 text-sm text-red-600 hidden">Please select a platform</p>
                    </div>

                    <!-- Meeting Link (conditional on mode) -->
                    <div id="meetingLinkField">
                        <label for="meeting_link" class="block text-sm font-medium text-gray-700 mb-1">Meeting Link*</label>
                        <input type="url" id="meeting_link" name="meeting_link" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="https://meet.google.com/abc-xyz">
                        <p id="meeting_link_error" class="mt-1 text-sm text-red-600 hidden">Please enter a valid URL</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status*</label>
                        <select id="status" name="status" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                            <option value="">Select status</option>
                            <option value="Running">Running</option>
                            <option value="Completed">Completed</option>
                            <option value="Upcoming">Upcoming</option>
                        </select>
                        <p id="status_error" class="mt-1 text-sm text-red-600 hidden">Please select a status</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="button" onclick="window.history.back()" 
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
        
        // Also close when clicking the cancel button
        document.querySelector(".btn-secondary.close-modal").onclick = function() {
            modal.style.display = "none";
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        
        // Show/hide platform and link fields based on mode selection
        $('#mode').change(function() {
            if ($(this).val() === 'online') {
                $('#platformGroup, #linkGroup').show();
                $('#platform, #link').attr('required', true);
            } else {
                $('#platformGroup, #linkGroup').hide();
                $('#platform, #link').removeAttr('required');
            }
        }).trigger('change');
    });
    </script>
    <script>
        $(document).ready(function() {
            // Load mentors from server
            $.ajax({
                url: 'get_mentors.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    const mentorSelect = $('#batch_mentor');
                    mentorSelect.empty();
                    mentorSelect.append('<option value="">Select a mentor</option>');
                    
                    data.forEach(function(mentor) {
                        mentorSelect.append(`<option value="${mentor.id}">${mentor.name}</option>`);
                    });
                },
                error: function() {
                    console.error('Failed to load mentors');
                }
            });

            // Toggle platform fields based on mode selection
            $('input[name="mode"]').change(function() {
                if ($(this).val() === 'Online') {
                    $('#platformField, #meetingLinkField').show();
                    $('#platform, #meeting_link').prop('required', true);
                } else {
                    $('#platformField, #meetingLinkField').hide();
                    $('#platform, #meeting_link').prop('required', false);
                }
            });

            // Initialize visibility
            if ($('input[name="mode"]:checked').val() === 'Online') {
                $('#platformField, #meetingLinkField').show();
            } else {
                $('#platformField, #meetingLinkField').hide();
            }

            // Form validation
            $('#batchForm').submit(function(e) {
                e.preventDefault();
                let isValid = true;

                // Reset error states
                $('.text-red-600').addClass('hidden');
                $('input, select').removeClass('border-red-500').addClass('border-gray-300');

                // Validate Batch ID
                if (!$('#batch_id').val()) {
                    $('#batch_id_error').removeClass('hidden');
                    $('#batch_id').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                }

                // Validate Course Name
                if (!$('#course_name').val()) {
                    $('#course_name_error').removeClass('hidden');
                    $('#course_name').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                }

                // Validate Dates
                const startDate = new Date($('#start_date').val());
                const endDate = new Date($('#end_date').val());
                
                if (!$('#start_date').val()) {
                    $('#start_date_error').removeClass('hidden');
                    $('#start_date').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                }
                
                if (!$('#end_date').val()) {
                    $('#end_date_error').removeClass('hidden');
                    $('#end_date').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                } else if (endDate <= startDate) {
                    $('#end_date_error').removeClass('hidden');
                    $('#end_date').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                }

                // Validate Number of Students
                if (!$('#num_students').val() || $('#num_students').val() <= 0) {
                    $('#num_students_error').removeClass('hidden');
                    $('#num_students').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                }

                // Validate Batch Mentor
                if (!$('#batch_mentor').val()) {
                    $('#batch_mentor_error').removeClass('hidden');
                    $('#batch_mentor').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                }

                // Validate Status
                if (!$('#status').val()) {
                    $('#status_error').removeClass('hidden');
                    $('#status').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                }

                // Validate Platform and Meeting Link if Online
                if ($('input[name="mode"]:checked').val() === 'Online') {
                    if (!$('#platform').val()) {
                        $('#platform_error').removeClass('hidden');
                        $('#platform').removeClass('border-gray-300').addClass('border-red-500');
                        isValid = false;
                    }
                    
                    if (!$('#meeting_link').val()) {
                        $('#meeting_link_error').removeClass('hidden');
                        $('#meeting_link').removeClass('border-gray-300').addClass('border-red-500');
                        isValid = false;
                    } else if (!isValidUrl($('#meeting_link').val())) {
                        $('#meeting_link_error').removeClass('hidden');
                        $('#meeting_link').removeClass('border-gray-300').addClass('border-red-500');
                        isValid = false;
                    }
                }

                if (isValid) {
                    // Submit form via AJAX
                    $.ajax({
                        url: 'create_batch.php',
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(response) {
                            try {
                                const result = JSON.parse(response);
                                if (result.success) {
                                    alert('Batch created successfully!');
                                    window.location.href = 'batches_list.php'; // Redirect to batches list
                                } else {
                                    alert('Error: ' + result.message);
                                }
                            } catch (e) {
                                alert('An error occurred. Please try again.');
                            }
                        },
                        error: function() {
                            alert('Failed to submit form. Please try again.');
                        }
                    });
                }
            });

            function isValidUrl(string) {
                try {
                    new URL(string);
                    return true;
                } catch (_) {
                    return false;
                }
            }
        });
    </script>
</body>
</html>