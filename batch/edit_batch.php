<?php
// Database connection
require_once '../db_connection.php'; // Adjust the path as needed
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
// Get batch ID from URL
$batch_id = $_GET['id'] ?? null;

// Fetch batch data if ID is provided
$batch = null;
if ($batch_id) {
    $stmt = $db->prepare("SELECT b.*, t.name as mentor_name 
                         FROM batches b
                         LEFT JOIN trainers t ON b.batch_mentor_id = t.id
                         WHERE b.batch_id = ?");
    $stmt->execute([$batch_id]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission for updating batch
if (isset($_POST['update_batch'])) {
    $stmt = $db->prepare("UPDATE batches SET 
        course_name = ?, 
        start_date = ?, 
        end_date = ?, 
        time_slot = ?, 
        platform = ?, 
        meeting_link = ?, 
        max_students = ?, 
        current_enrollment = ?, 
        academic_year = ?,
        batch_mentor_id = ?, 
        num_students = ?,
        mode = ?, 
        status = ?
        WHERE batch_id = ?");
    
    $stmt->execute([
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
        $batch_id
    ]);
    
    // Redirect back to batch list
    header("Location: ../batch/batch_list.php");
    exit();
}

// If batch not found, redirect to list
if (!$batch) {
    header("Location: ../batch/batch_list.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASD Academy - Edit Batch</title>
    
    <!-- Include the same CSS as batch_list.php for consistency -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <style>
        /* Same styles as in batch_list.php */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            padding: 25px;
            max-width: 800px;
            margin: 30px auto;
        }
        
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 28px;
            position: relative;
            display: inline-block;
        }
        
        h2:after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 4px;
            background: #3498db;
            border-radius: 2px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            padding: 25px;
            margin-bottom: 30px;
            border: none;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d3dce6;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background-color: #f8fafc;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background-color: #8e44ad;
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #7d3c98;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
            border: none;
        }
        
        .btn-secondary:hover {
            background-color: #7f8c8d;
            transform: translateY(-2px);
        }
        
        /* Enhanced select dropdown */
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 15px;
            padding-right: 30px;
        }
        
        /* Grid layout similar to batch_list.php */
        .grid-cols-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .grid-cols-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>Edit Batch: <?= htmlspecialchars($batch['batch_id']) ?></h2>
            
            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Batch ID -->
                    <div class="form-group">
                        <label for="batch_id">Batch ID</label>
                        <input type="text" id="batch_id" class="form-control" value="<?= htmlspecialchars($batch['batch_id']) ?>" disabled>
                        <small>Batch ID cannot be changed</small>
                    </div>
                    
                    <!-- Course Name -->
                    <div class="form-group">
                        <label for="course_name">Course Name</label>
                        <input type="text" id="course_name" name="course_name" class="form-control" value="<?= htmlspecialchars($batch['course_name']) ?>" required>
                    </div>
                    
                    <!-- Start Date -->
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($batch['start_date']) ?>" required>
                    </div>
                    
                    <!-- End Date -->
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($batch['end_date']) ?>" required>
                    </div>
                    
                    <!-- Time Slot -->
                    <div class="form-group">
                        <label for="time_slot">Time Slot</label>
                        <input type="text" id="time_slot" name="time_slot" class="form-control" value="<?= htmlspecialchars($batch['time_slot']) ?>" placeholder="e.g., 10:00 AM - 12:00 PM">
                    </div>
                    
                    <!-- Max Students -->
                    <div class="form-group">
                        <label for="max_students">Max Students</label>
                        <input type="number" id="max_students" name="max_students" class="form-control" value="<?= htmlspecialchars($batch['max_students']) ?>" min="1" required>
                    </div>
                    
                    <!-- Current Enrollment -->
                    <div class="form-group">
                        <label for="current_enrollment">Current Enrollment</label>
                        <input type="number" id="current_enrollment" name="current_enrollment" class="form-control" value="<?= htmlspecialchars($batch['current_enrollment']) ?>" min="0">
                    </div>
                    
                    <!-- Academic Year -->
                    <div class="form-group">
                        <label for="academic_year">Academic Year</label>
                        <input type="text" id="academic_year" name="academic_year" class="form-control" value="<?= htmlspecialchars($batch['academic_year']) ?>" placeholder="e.g., 2023-2024">
                    </div>
                    
                    <!-- Batch Mentor -->
                    <div class="form-group">
                        <label for="batch_mentor_id">Batch Mentor</label>
                        <select id="batch_mentor_id" name="batch_mentor_id" class="form-control" required>
                            <option value="">Select Mentor</option>
                            <?php 
                            $mentors = $db->query("SELECT id, name FROM trainers")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($mentors as $mentor): ?>
                                <option value="<?= $mentor['id'] ?>" <?= $batch['batch_mentor_id'] == $mentor['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mentor['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Number of Students -->
                    <div >
                    </div>
                    
                    <!-- Mode -->
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mode*</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="mode" value="online" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500" <?= $batch['mode'] === 'online' ? 'checked' : '' ?>>
                                <span class="ml-2 text-gray-700">Online</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="mode" value="offline" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500" <?= $batch['mode'] === 'offline' ? 'checked' : '' ?>>
                                <span class="ml-2 text-gray-700">Offline</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Status -->
                    <div class="form-group">
                        <label for="status">Status*</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="upcoming" <?= $batch['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="ongoing" <?= $batch['status'] === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                            <option value="completed" <?= $batch['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $batch['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                </div>

                <!-- Online Fields -->
                <div id="onlineFields" style="<?= $batch['mode'] === 'online' ? '' : 'display: none;' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Platform -->
                        <div class="form-group">
                            <label for="platform">Platform</label>
                            <select id="platform" name="platform" class="form-control">
                                <option value="">Select Platform</option>
                                <option value="Google Meet" <?= $batch['platform'] === 'Google Meet' ? 'selected' : '' ?>>Google Meet</option>
                                <option value="Zoom" <?= $batch['platform'] === 'Zoom' ? 'selected' : '' ?>>Zoom</option>
                                <option value="Microsoft Teams" <?= $batch['platform'] === 'Microsoft Teams' ? 'selected' : '' ?>>Microsoft Teams</option>
                            </select>
                        </div>
                        
                        <!-- Meeting Link -->
                        <div class="form-group">
                            <label for="meeting_link">Meeting Link</label>
                            <input type="url" id="meeting_link" name="meeting_link" class="form-control" 
                                   value="<?= htmlspecialchars($batch['meeting_link']) ?>" 
                                   placeholder="https://meet.google.com/abc-xyz">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="../batch/batch_list.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" name="update_batch" class="btn btn-primary">Update Batch</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Show/hide online fields based on mode selection
        $('input[name="mode"]').change(function() {
            if ($(this).val() === 'online') {
                $('#onlineFields').show();
            } else {
                $('#onlineFields').hide();
            }
        });
    });
    </script>
</body>
</html>