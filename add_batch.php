<?php
require_once '../db_connection.php'; // Include your database connection file

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_batch'])) {
    // Prepare batch data
    $batchData = [
        'batch_id' => $_POST['batch_id'],
        'course_name' => $_POST['course_name'],
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'time_slot' => $_POST['time_slot'] ?? null,
        'platform' => $_POST['platform'] ?? null,
        'meeting_link' => $_POST['meeting_link'] ?? null,
        'max_students' => $_POST['max_students'],
        'current_enrollment' => $_POST['current_enrollment'] ?? 0,
        'academic_year' => $_POST['academic_year'] ?? null,
        'batch_mentor_id' => $_POST['batch_mentor_id'] ?? null,
        'num_students' => $_POST['num_students'] ?? 0,
        'mode' => $_POST['mode'],
        'status' => $_POST['status'],
        'created_by' => $_POST['created_by']
    ];

    // Validate data
    $errors = validateBatchData($batchData);
    
    if (empty($errors)) {
        if (createBatch($db, $batchData)) {
            // Redirect to dashboard with success message
            header("Location: ../dashboard/dashboard.php?success=batch_created");
            exit();
        } else {
            $errors[] = 'Failed to create batch. Please try again.';
        }
    }
}

function generateNextBatchId(PDO $db): string {
    $lastBatch = $db->query("SELECT batch_id FROM batches ORDER BY batch_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $nextBatchId = 'B001'; // Default if no batches exist

    if ($lastBatch) {
        // Extract the numeric part and increment
        $lastNumber = (int) substr($lastBatch['batch_id'], 1);
        $nextNumber = $lastNumber + 1;
        $nextBatchId = 'B' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    return $nextBatchId;
}

function createBatch(PDO $db, array $batchData): bool {
    try {
        $stmt = $db->prepare("INSERT INTO batches (
            batch_id, course_name, start_date, end_date, time_slot, platform, 
            meeting_link, max_students, current_enrollment, academic_year,
            batch_mentor_id, num_students, mode, status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        return $stmt->execute([
            $batchData['batch_id'],
            $batchData['course_name'],
            $batchData['start_date'],
            $batchData['end_date'],
            $batchData['time_slot'],
            $batchData['platform'],
            $batchData['meeting_link'],
            $batchData['max_students'],
            $batchData['current_enrollment'],
            $batchData['academic_year'],
            $batchData['batch_mentor_id'],
            $batchData['num_students'],
            $batchData['mode'],
            $batchData['status'],
            $batchData['created_by']
        ]);
    } catch (PDOException $e) {
        error_log("Error creating batch: " . $e->getMessage());
        return false;
    }
}

function validateBatchData(array $data): array {
    $errors = [];
    
    if (empty($data['course_name'])) {
        $errors[] = 'Course name is required';
    }
    
    if (empty($data['start_date'])) {
        $errors[] = 'Start date is required';
    }
    
    if (empty($data['end_date'])) {
        $errors[] = 'End date is required';
    } elseif (!empty($data['start_date']) && $data['end_date'] < $data['start_date']) {
        $errors[] = 'End date must be after start date';
    }
    
    if (empty($data['max_students']) || $data['max_students'] <= 0) {
        $errors[] = 'Max students must be a positive number';
    }
    
    if (!empty($data['current_enrollment']) && $data['current_enrollment'] > $data['max_students']) {
        $errors[] = 'Current enrollment cannot exceed max students';
    }
    
    return $errors;
}

$nextBatchId = generateNextBatchId($db);
?>
<?php
include '../header.php'; // Include your header file
include '../sidebar.php'; // Include your sidebar file
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Batch</title>
    <link rel="stylesheet" href="../assets/css/tailwind.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6 max-w-4xl">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New Batch</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
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
                    <a href="batches.php" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Create Batch
                    </button>
                </div>

            </form>
        </div>
    </div>
    <script>
        // Show/hide online fields based on mode selection
        function toggleOnlineFields() {
            const onlineFields = document.getElementById('onlineFields');
            const mode = document.querySelector('input[name="mode"]:checked').value;
            onlineFields.style.display = mode === 'online' ? 'block' : 'none';
        }
        
        // Set up event listeners for mode toggle
        document.querySelectorAll('input[name="mode"]').forEach(radio => {
            radio.addEventListener('change', toggleOnlineFields);
        });
        
        // Initial toggle when page loads
        document.addEventListener('DOMContentLoaded', toggleOnlineFields);
    </script>
</body>
</html>