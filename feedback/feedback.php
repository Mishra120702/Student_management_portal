<?php
// Database connection (same as your batch_list.php)
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get batches for dropdown
$batches = $db->query("SELECT batch_id, course_name FROM batches")->fetchAll(PDO::FETCH_ASSOC);

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $stmt = $db->prepare("INSERT INTO feedback (date, student_name, email, is_regular, batch_id, course_name, 
                         class_rating, assignment_understanding, practical_understanding, satisfied, 
                         suggestions, feedback_text) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        date('Y-m-d'),
        $_POST['student_name'],
        $_POST['email'],
        $_POST['regular_in_class'],
        $_POST['batch_id'],
        $_POST['course_name'],
        $_POST['class_rating'],
        $_POST['assignment_understanding'],
        $_POST['practical_understanding'],
        $_POST['satisfied'],
        $_POST['suggestions'],
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
    <!-- Primary Tailwind CDN with fallback -->
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <!-- Font Awesome from jsDelivr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
    /* Base styles */
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f5f7fa;
        color: #333;
        line-height: 1.6;
    }
    
    .main-content {
        margin-left: 16rem; /* Match sidebar width */
        padding: 2rem;
        width: calc(100% - 16rem);
        box-sizing: border-box;
    }
    
    h2, h3, h4 {
        color: #2c3e50;
        margin-bottom: 20px;
        font-weight: 600;
    }
    
    h3 {
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #e0e6ed;
    }
    
    h4 {
        margin: 15px 0 10px 0;
        font-size: 16px;
    }
    
    .card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        padding: 25px;
        margin-bottom: 30px;
        border: 1px solid #e0e6ed;
        width: 100%;
        box-sizing: border-box;
    }
    
    .minimal-input {
        border: 1px solid #d3dce6;
        border-radius: 6px;
        padding: 10px 15px;
        margin-bottom: 15px;
        font-size: 14px;
        width: 100%;
        font-family: 'Inter', sans-serif;
        transition: all 0.3s;
        box-sizing: border-box;
    }
    
    .minimal-input:focus {
        border-color: #3498db;
        outline: none;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }
    
    textarea.minimal-input {
        min-height: 100px;
        resize: vertical;
    }
    
    .btn-blue {
        background-color: #3498db;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 12px 20px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        font-family: 'Inter', sans-serif;
        transition: all 0.3s;
    }
    
    .btn-blue:hover {
        background-color: #2980b9;
        transform: translateY(-1px);
    }
    
    .btn-gray {
        background-color: #95a5a6;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 12px 20px;
        cursor: pointer;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        transition: all 0.3s;
    }
    
    .btn-gray:hover {
        background-color: #7f8c8d;
    }
    
    /* Star rating */
    .star-rating {
        font-size: 24px;
        margin: 10px 0 20px;
        display: inline-block;
    }
    
    .star-rating span {
        cursor: pointer;
        color: #e0e6ed;
        transition: all 0.2s;
        padding: 0 2px;
    }
    
    .star-rating span:hover,
    .star-rating span.active {
        color: #f39c12;
        transform: scale(1.2);
    }
    
    .stars-inline {
        color: #f39c12;
        font-size: 16px;
        white-space: nowrap;
    }
    
    /* Success message */
    .success-message {
        color: #27ae60;
        margin: -10px 0 20px;
        padding: 10px;
        background-color: rgba(39, 174, 96, 0.1);
        border-radius: 4px;
        font-weight: 500;
    }
    
    /* Table styles */
    #feedbackTable {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        font-size: 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    #feedbackTable thead th {
        background-color: #3498db;
        color: white;
        font-weight: 500;
        padding: 12px 10px;
        text-align: left;
        position: sticky;
        top: 0;
    }
    
    #feedbackTable tbody td {
        padding: 10px;
        border-bottom: 1px solid #e0e6ed;
        vertical-align: top;
        word-break: break-word;
    }
    
    #feedbackTable tr:nth-child(even) {
        background-color: #f8fafc;
    }
    
    #feedbackTable tr:hover {
        background-color: #eaf2f8;
    }
    
    /* Make some columns narrower */
    #feedbackTable th:nth-child(1), /* Date */
    #feedbackTable td:nth-child(1) {
        width: 90px;
    }
    
    #feedbackTable th:nth-child(4), /* Regular */
    #feedbackTable td:nth-child(4),
    #feedbackTable th:nth-child(5), /* Batch */
    #feedbackTable td:nth-child(5),
    #feedbackTable th:nth-child(10), /* Satisfied */
    #feedbackTable td:nth-child(10) {
        width: 80px;
    }
    
    #feedbackTable th:nth-child(6), /* Course */
    #feedbackTable td:nth-child(6) {
        width: 120px;
    }
    
    #feedbackTable th:nth-child(7), /* Class Rating */
    #feedbackTable td:nth-child(7),
    #feedbackTable th:nth-child(8), /* Assignment */
    #feedbackTable td:nth-child(8),
    #feedbackTable th:nth-child(9), /* Practical */
    #feedbackTable td:nth-child(9) {
        width: 70px;
    }
    
    /* Action column */
    #feedbackTable th:nth-child(13),
    #feedbackTable td:nth-child(13) {
        width: 180px;
    }
    
    /* Star ratings in table */
    .stars-inline {
        color: #f39c12;
        font-size: 14px;
        white-space: nowrap;
        display: inline-block;
        min-width: 70px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 992px) {
        .main-content {
            margin-left: 0;
            width: 100%;
            padding: 1rem;
            padding-top: 60px; /* Make space for mobile header */
        }
        
        #feedbackTable {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        #feedbackTable thead th,
        #feedbackTable tbody td {
            padding: 8px 6px;
        }
    }
    
    /* Word counter styles */
    .word-counter {
        font-size: 12px;
        color: #95a5a6;
        text-align: right;
        margin-top: -10px;
        margin-bottom: 15px;
    }
    
    .word-counter.limit-reached {
        color: #e74c3c;
        font-weight: bold;
    }
    
    .filter-controls {
        margin-bottom: 15px; 
        display: flex; 
        align-items: center; 
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .filter-controls form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .page-header {
        background: white;
        padding: 1rem 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .page-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-comment-dots text-blue-500"></i>
                <span>Feedback System</span>
            </h1>
        </div>
        
        <!-- Feedback Submission Card -->
        <div class="card">
            <h3>Submit Feedback</h3>
            <?php if (isset($success) && $success): ?>
                <div class="success-message">Feedback submitted successfully!</div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="email" name="email" class="minimal-input" placeholder="Your Email" required>
                <input type="text" name="student_name" class="minimal-input" placeholder="Your Name" required>
                
                <select name="regular_in_class" class="minimal-input" required>
                    <option value="">Are you regular in class?</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                    <option value="Sometimes">Sometimes</option>
                </select>
                
                <select name="batch_id" class="minimal-input" required>
                    <option value="">Select Batch</option>
                    <?php foreach ($batches as $batch): ?>
                        <option value="<?= htmlspecialchars($batch['batch_id']) ?>">
                            <?= htmlspecialchars($batch['batch_id']) ?> - <?= htmlspecialchars($batch['course_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" name="course_name" class="minimal-input" placeholder="Course Name" required>
                
                <h4>Class Rating</h4>
                <div class="star-rating" data-target="class_rating">
                    <span data-value="1">★</span>
                    <span data-value="2">★</span>
                    <span data-value="3">★</span>
                    <span data-value="4">★</span>
                    <span data-value="5">★</span>
                </div>
                <input type="hidden" name="class_rating" id="class_rating" required>
                
                <h4>Assignment Understanding</h4>
                <div class="star-rating" data-target="assignment_understanding">
                    <span data-value="1">★</span>
                    <span data-value="2">★</span>
                    <span data-value="3">★</span>
                    <span data-value="4">★</span>
                    <span data-value="5">★</span>
                </div>
                <input type="hidden" name="assignment_understanding" id="assignment_understanding" required>
                
                <h4>Practical Understanding</h4>
                <div class="star-rating" data-target="practical_understanding">
                    <span data-value="1">★</span>
                    <span data-value="2">★</span>
                    <span data-value="3">★</span>
                    <span data-value="4">★</span>
                    <span data-value="5">★</span>
                </div>
                <input type="hidden" name="practical_understanding" id="practical_understanding" required>
                
                <select name="satisfied" class="minimal-input" required>
                    <option value="">Are you satisfied with the course?</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
                
                <div class="textarea-wrapper">
                    <textarea name="suggestions" class="minimal-input" placeholder="Your suggestions or issues..." style="min-height: 100px;" maxlength="500" data-max-words="100"></textarea>
                    <div class="word-counter" id="suggestions-counter">0/100 words</div>
                </div>
                
                <div class="textarea-wrapper">
                    <textarea name="feedback_text" class="minimal-input" placeholder="Additional feedback..." style="min-height: 100px;" maxlength="1000" data-max-words="200"></textarea>
                    <div class="word-counter" id="feedback-counter">0/200 words</div>
                </div>
                
                <button type="submit" name="submit_feedback" class="btn-blue">Submit</button>
            </form>
        </div>

        <!-- Feedback Display Table -->
        <div class="card">
            <h3>Feedback Records</h3>
            
            <div class="filter-controls">
                <form method="GET" action="">
                    <select name="batch" class="minimal-input" style="width: auto;">
                        <option value="">All Batches</option>
                        <?php foreach ($batches as $batch): ?>
                            <option value="<?= htmlspecialchars($batch['course_name']) ?>" 
                                <?= $batch_filter === $batch['course_name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($batch['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-blue">Filter</button>
                    <a href="feedback.php" class="btn-gray">Reset</a>
                </form>
                
                <div class="export-buttons">
                    <a href="../feedback/export.php" class="btn-blue">Export Feedback</a>
                </div>
            </div>
            
            <table id="feedbackTable" class="display">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Regular</th>
                        <th>Batch</th>
                        <th>Course</th>
                        <th>Class</th>
                        <th>Assign</th>
                        <th>Pract</th>
                        <th>Satis</th>
                        <th>Suggestions</th>
                        <th>Feedback</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feedback as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['date']) ?></td>
                        <td><?= htmlspecialchars($item['student_name']) ?></td>
                        <td><?= htmlspecialchars($item['is_regular']) ?></td>
                        <td><?= htmlspecialchars($item['batch_id']) ?></td>
                        <td><?= htmlspecialchars($item['course_name']) ?></td>
                        <td>
                            <div class="stars-inline" title="Class Rating: <?= $item['class_rating'] ?>/5">
                                <?= str_repeat('★', $item['class_rating']) ?><?= str_repeat('☆', 5 - $item['class_rating']) ?>
                            </div>
                        </td>
                        <td>
                            <div class="stars-inline" title="Assignment Understanding: <?= $item['assignment_understanding'] ?>/5">
                                <?= str_repeat('★', $item['assignment_understanding']) ?><?= str_repeat('☆', 5 - $item['assignment_understanding']) ?>
                            </div>
                        </td>
                        <td>
                            <div class="stars-inline" title="Practical Understanding: <?= $item['practical_understanding'] ?>/5">
                                <?= str_repeat('★', $item['practical_understanding']) ?><?= str_repeat('☆', 5 - $item['practical_understanding']) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($item['satisfied']) ?></td>
                        <td><?= htmlspecialchars($item['suggestions']) ?></td>
                        <td><?= htmlspecialchars($item['feedback_text']) ?></td>
                        <td>
                            <?php if (!empty($item['action_taken'])): ?>
                                <?= htmlspecialchars($item['action_taken']) ?>
                            <?php else: ?>
                                <form method="POST" action="../feedback/update_action.php" style="display: flex; gap: 5px;">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <input type="text" name="action_taken" placeholder="Action..." 
                                           class="minimal-input" style="flex-grow: 1; min-width: 0; padding: 6px;">
                                    <button type="submit" class="btn-blue" style="padding: 6px 10px;">✓</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable with export buttons
    $('#feedbackTable').DataTable({
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        columnDefs: [
            { targets: [5, 6, 7], orderable: false }
        ]
    });
    
    // Star rating interaction
    $('.star-rating span').on('click', function() {
        const rating = $(this).data('value');
        const target = $(this).parent().data('target');
        
        // Update stars appearance
        $(this).siblings().removeClass('active');
        $(this).prevAll().addBack().addClass('active');
        
        // Update hidden input value
        $('#' + target).val(rating);
    });
    
    // Hover effect for stars
    $('.star-rating span').on('mouseover', function() {
        $(this).prevAll().addBack().css('color', '#f39c12');
    }).on('mouseout', function() {
        const activeStars = $(this).parent().find('.active');
        if (activeStars.length > 0) {
            $(this).siblings().css('color', '#e0e6ed');
            activeStars.css('color', '#f39c12');
        } else {
            $(this).siblings().addBack().css('color', '#e0e6ed');
        }
    });
    
    // Word counter functionality
    function countWords(text) {
        // Trim whitespace and count non-empty sequences of characters separated by whitespace
        return text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
    }
    
    function updateWordCounter(textarea, counter) {
        const text = $(textarea).val();
        const wordCount = countWords(text);
        const maxWords = parseInt($(textarea).data('max-words'));
        
        $(counter).text(wordCount + '/' + maxWords + ' words');
        
        if (wordCount > maxWords) {
            $(counter).addClass('limit-reached');
            // Truncate the text if over limit
            const words = text.trim().split(/\s+/);
            const truncated = words.slice(0, maxWords).join(' ');
            $(textarea).val(truncated);
            $(counter).text(maxWords + '/' + maxWords + ' words (limit reached)');
        } else {
            $(counter).removeClass('limit-reached');
        }
    }
    
    // Initialize word counters
    $('textarea[minlength], textarea[maxlength], textarea[data-max-words]').each(function() {
        const textareaId = $(this).attr('name');
        $(this).on('input', function() {
            updateWordCounter(this, $('#' + textareaId + '-counter'));
        });
        
        // Initial count
        updateWordCounter(this, $('#' + textareaId + '-counter'));
    });
});
</script>
</body>
</html>