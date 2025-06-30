<?php
// Database connection (same as your batch_list.php)
$db = new PDO('mysql:host=localhost;dbname=asd_academy1', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get batches for dropdown
$batches = $db->query("SELECT batch_id, course_name FROM batches")->fetchAll(PDO::FETCH_ASSOC);

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $stmt = $db->prepare("INSERT INTO feedback (date, student_name, batch_id, course_name, rating, feedback_text) 
                         VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        date('Y-m-d'),
        $_POST['student_name'],
        $_POST['batch_id'],
        $_POST['course_name'],
        $_POST['rating'],
        $_POST['feedback_text']
    ]);
    $success = true;
}

// Get feedback data (filtered by batch if specified)
$batch_filter = $_GET['batch'] ?? '';
$query = "SELECT * FROM feedback";
$params = [];
if (!empty($batch_filter)) {
    $query .= " WHERE batch_id = ?";
    $params[] = $batch_filter;
}
$stmt = $db->prepare($query);
$stmt->execute($params);
$feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASD Academy - Feedback System</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
        /* Base styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e0e6ed;
        }
        
        .minimal-input {
            border: 1px solid #d3dce6;
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 15px;
            font-size: 14px;
            width: 100%;
            font-family: 'Segoe UI', sans-serif;
            transition: border-color 0.3s;
        }
        
        .minimal-input:focus {
            border-color: #3498db;
            outline: none;
        }
        
        .btn-blue {
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 18px;
            cursor: pointer;
            font-size: 14px;
            font-family: 'Segoe UI', sans-serif;
            transition: background-color 0.3s;
        }
        
        .btn-blue:hover {
            background-color: #2980b9;
        }
        
        .btn-gray {
            background-color: #95a5a6;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 18px;
            cursor: pointer;
            font-size: 14px;
            font-family: 'Segoe UI', sans-serif;
            transition: background-color 0.3s;
        }
        
        .btn-gray:hover {
            background-color: #7f8c8d;
        }
        
        .star-rating {
            font-size: 24px;
            margin: 10px 0;
        }
        
        .star-rating span {
            cursor: pointer;
            color: #ecf0f1;
            transition: color 0.2s;
        }
        
        .star-rating span.active {
            color: #f39c12;
        }
        
        .stars-inline {
            color: #f39c12;
            font-size: 16px;
        }
        
        /* Success message */
        .success-message {
            color: #27ae60;
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        /* Table styles */
        #feedbackTable {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        #feedbackTable th {
            background-color: #f8fafc;
            color: #3498db;
            font-weight: 600;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #e0e6ed;
        }
        
        #feedbackTable td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e6ed;
        }
        
        #feedbackTable tr:hover {
            background-color: #f8fafc;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .filter-controls {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-controls select, 
            .filter-controls button {
                width: 100%;
                margin-bottom: 10px;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Include your dashboard header/sidebar here -->
    
    <div class="container">
        <h2>Feedback System</h2>
        
        <!-- Feedback Submission Card -->
        <div class="card">
            <h3>Submit Feedback</h3>
            <?php if (isset($success) && $success): ?>
                <div class="success-message">Feedback submitted successfully!</div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <select name="batch_id" class="minimal-input" required>
                    <option value="">Select Batch</option>
                    <?php foreach ($batches as $batch): ?>
                        <option value="<?= htmlspecialchars($batch['batch_id']) ?>">
                            <?= htmlspecialchars($batch['batch_id']) ?> - <?= htmlspecialchars($batch['course_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" name="student_name" class="minimal-input" placeholder="Your Name" required>
                <input type="text" name="course_name" class="minimal-input" placeholder="Course Name" required>
                
                <div class="star-rating">
                    <span data-value="1">★</span>
                    <span data-value="2">★</span>
                    <span data-value="3">★</span>
                    <span data-value="4">★</span>
                    <span data-value="5">★</span>
                </div>
                <input type="hidden" name="rating" id="ratingValue" required>
                
                <textarea name="feedback_text" class="minimal-input" placeholder="Your feedback..." required style="min-height: 100px;"></textarea>
                
                <button type="submit" name="submit_feedback" class="btn-blue">Submit</button>
            </form>
        </div>
        
        <!-- Feedback Display Table -->
        <div class="card">
            <h3>Feedback Records</h3>
            
            <div class="filter-controls" style="margin-bottom: 15px; display: flex; align-items: center;">
                <form method="GET" action="" style="display: flex; align-items: center; gap: 10px;">
                    <select name="batch" class="minimal-input" style="width: auto;">
                        <option value="">All Batches</option>
                        <?php foreach ($batches as $batch): ?>
                            <option value="<?= htmlspecialchars($batch['batch_id']) ?>" 
                                <?= $batch_filter === $batch['batch_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($batch['batch_id']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-blue">Filter</button>
                    <a href="feedback.php" class="btn-gray">Reset</a>
                </form>
            </div>
            
            <table id="feedbackTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Batch</th>
                        <th>Course</th>
                        <th>Rating</th>
                        <th>Feedback</th>
                        <th>Action Taken</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feedback as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['date']) ?></td>
                        <td><?= htmlspecialchars($item['student_name']) ?></td>
                        <td><?= htmlspecialchars($item['batch_id']) ?></td>
                        <td><?= htmlspecialchars($item['course_name']) ?></td>
                        <td>
                            <div class="stars-inline">
                                <?= str_repeat('★', $item['rating']) ?><?= str_repeat('☆', 5 - $item['rating']) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($item['feedback_text']) ?></td>
                        <td>
                            <?php if (!empty($item['action_taken'])): ?>
                                <?= htmlspecialchars($item['action_taken']) ?>
                            <?php else: ?>
                                <form method="POST" action="feedback/update_action.php" style="display: inline;">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <input type="text" name="action_taken" placeholder="Add action..." 
                                           class="minimal-input" style="width: 150px; display: inline-block;">
                                    <button type="submit" class="btn-blue" style="padding: 8px 12px;">Save</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Include your existing JS files here -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#feedbackTable').DataTable({
            responsive: true,
            columnDefs: [
                { targets: [5, 6], orderable: false }
            ]
        });
        
        // Star rating interaction
        $('.star-rating span').click(function() {
            const rating = $(this).data('value');
            $(this).addClass('active').prevAll().addClass('active');
            $(this).nextAll().removeClass('active');
            $('#ratingValue').val(rating);
        });
    });
    </script>
</body>
</html>