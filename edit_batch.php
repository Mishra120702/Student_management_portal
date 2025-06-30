<?php
// Database connection
$db = new PDO('mysql:host=localhost;dbname=asd_academy1', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get batch ID from URL
$batch_id = $_GET['id'] ?? null;

// Fetch batch data if ID is provided
$batch = null;
if ($batch_id) {
    $stmt = $db->prepare("SELECT * FROM batches WHERE batch_id = ?");
    $stmt->execute([$batch_id]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission for updating batch
if (isset($_POST['update_batch'])) {
    $stmt = $db->prepare("UPDATE batches SET 
        course_name = ?, 
        start_date = ?, 
        end_date = ?, 
        no_of_students = ?, 
        batch_mentor = ?, 
        mode = ?, 
        status = ?, 
        time_slot = ?, 
        platform = ?, 
        link = ?, 
        max_students = ?, 
        current_enrollment = ?, 
        academic_year = ?
        WHERE batch_id = ?");
    
    $stmt->execute([
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
        $_POST['academic_year'],
        $batch_id
    ]);
    
    // Redirect back to batch list
    header("Location: batch_list.php");
    exit();
}

// If batch not found, redirect to list
if (!$batch) {
    header("Location: batch_list.php");
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
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
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
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>Edit Batch: <?= htmlspecialchars($batch['batch_id']) ?></h2>
            
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="batch_id">Batch ID</label>
                        <input type="text" id="batch_id" class="form-control" value="<?= htmlspecialchars($batch['batch_id']) ?>" disabled>
                        <small>Batch ID cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="course_name">Course Name</label>
                        <input type="text" id="course_name" name="course_name" class="form-control" value="<?= htmlspecialchars($batch['course_name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($batch['start_date']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($batch['end_date']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="time_slot">Time Slot</label>
                        <input type="text" id="time_slot" name="time_slot" class="form-control" value="<?= htmlspecialchars($batch['time_slot']) ?>" placeholder="e.g., 10:00 AM - 12:00 PM">
                    </div>
                    
                    <div class="form-group">
                        <label for="batch_mentor">Mentor</label>
                        <input type="text" id="batch_mentor" name="batch_mentor" class="form-control" value="<?= htmlspecialchars($batch['batch_mentor']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="mode">Mode</label>
                        <select id="mode" name="mode" class="form-control" required>
                            <option value="online" <?= $batch['mode'] === 'online' ? 'selected' : '' ?>>Online</option>
                            <option value="offline" <?= $batch['mode'] === 'offline' ? 'selected' : '' ?>>Offline</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="upcoming" <?= $batch['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="ongoing" <?= $batch['status'] === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                            <option value="completed" <?= $batch['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $batch['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_students">Max Students</label>
                        <input type="number" id="max_students" name="max_students" class="form-control" value="<?= htmlspecialchars($batch['max_students']) ?>" min="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="current_enrollment">Current Enrollment</label>
                        <input type="number" id="current_enrollment" name="current_enrollment" class="form-control" value="<?= htmlspecialchars($batch['current_enrollment']) ?>" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="academic_year">Academic Year</label>
                        <input type="text" id="academic_year" name="academic_year" class="form-control" value="<?= htmlspecialchars($batch['academic_year']) ?>" placeholder="e.g., 2023-2024">
                    </div>
                    
                    <div class="form-group full-width" id="platformGroup">
                        <label for="platform">Platform (for online batches)</label>
                        <input type="text" id="platform" name="platform" class="form-control" value="<?= htmlspecialchars($batch['platform']) ?>" placeholder="e.g., Zoom, Google Meet">
                    </div>
                    
                    <div class="form-group full-width" id="linkGroup">
                        <label for="link">Meeting Link (for online batches)</label>
                        <input type="url" id="link" name="link" class="form-control" value="<?= htmlspecialchars($batch['link']) ?>" placeholder="https://">
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="batch_list.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" name="update_batch" class="btn btn-primary">Update Batch</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Show/hide platform and link fields based on mode selection
        function toggleOnlineFields() {
            if ($('#mode').val() === 'online') {
                $('#platformGroup, #linkGroup').show();
                $('#platform, #link').attr('required', true);
            } else {
                $('#platformGroup, #linkGroup').hide();
                $('#platform, #link').removeAttr('required');
            }
        }
        
        // Initial toggle
        toggleOnlineFields();
        
        // Toggle when mode changes
        $('#mode').change(toggleOnlineFields);
    });
    </script>
</body>
</html>