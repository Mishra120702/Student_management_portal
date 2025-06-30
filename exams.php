<?php
// Database connection
$db = new PDO('mysql:host=localhost;dbname=asd_academy1', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get active batches for dropdown
$batches = $db->query("SELECT batch_id, course_name FROM batches WHERE status = 'Running'")->fetchAll(PDO::FETCH_ASSOC);

// Get all exams for the table
$exams = $db->query("SELECT * FROM proctored_exams ORDER BY exam_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASD Academy - Exam Tracking</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Base styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .dashboard-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e0e6ed;
        }
        
        h4 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .minimal-input {
            border: 1px solid #d3dce6;
            border-radius: 6px;
            padding: 10px 15px;
            font-size: 14px;
            width: 100%;
            margin-bottom: 15px;
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
            padding: 8px 15px;
            cursor: pointer;
            font-size: 13px;
            font-family: 'Segoe UI', sans-serif;
            transition: background-color 0.3s;
        }
        
        .btn-gray:hover {
            background-color: #7f8c8d;
        }
        
        .grid-2cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .bg-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .bg-success {
            background-color: #27ae60;
            color: white;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 25px;
            width: 80%;
            max-width: 900px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border: 1px solid #e0e6ed;
        }
        
        .close {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
            color: #95a5a6;
            transition: color 0.3s;
        }
        
        .close:hover {
            color: #7f8c8d;
        }
        
        /* Exam info styles */
        .exam-info {
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: 600;
            width: 150px;
            color: #2c3e50;
        }
        
        /* Student table styles */
        .malpractice-row {
            background-color: #ffebee;
        }
        
        /* Table styles */
        #examTable, #studentResultsTable {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        #examTable th, #studentResultsTable th {
            background-color: #f8fafc;
            color: #3498db;
            font-weight: 600;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #e0e6ed;
        }
        
        #examTable td, #studentResultsTable td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e6ed;
        }
        
        #examTable tr:hover, #studentResultsTable tr:hover {
            background-color: #f8fafc;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .grid-2cols {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 2% auto;
                padding: 15px;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Include your dashboard header/sidebar here -->
    
    <div class="dashboard-container">
        <!-- Exam Scheduling Card -->
        <div class="dashboard-card">
            <h4>Schedule New Exam</h4>
            <form id="examForm">
                <select id="batchSelect" class="minimal-input" required>
                    <option value="">Select Batch</option>
                    <?php foreach ($batches as $batch): ?>
                        <option value="<?= htmlspecialchars($batch['batch_id']) ?>">
                            <?= htmlspecialchars($batch['batch_id']) ?> - <?= htmlspecialchars($batch['course_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div class="grid-2cols">
                    <input type="text" id="examDate" class="minimal-input date-picker" placeholder="Exam Date" required>
                    <input type="number" id="duration" class="minimal-input" placeholder="Duration (minutes)" min="1" required>
                </div>
                
                <div class="grid-2cols">
                    <select id="examMode" class="minimal-input" required>
                        <option value="">Select Mode</option>
                        <option value="Online">Online</option>
                        <option value="Offline">Offline</option>
                    </select>
                    <input type="text" id="proctorName" class="minimal-input" placeholder="Proctor Name">
                </div>
                
                <button type="submit" class="btn-blue">Create Exam</button>
            </form>
        </div>
        
        <!-- Exam Records Table -->
        <div class="dashboard-card">
            <h4>Exam Records</h4>
            <table id="examTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Exam ID</th>
                        <th>Batch</th>
                        <th>Date</th>
                        <th>Mode</th>
                        <th>Duration</th>
                        <th>Proctor</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exams as $exam): ?>
                    <tr>
                        <td><?= htmlspecialchars($exam['exam_id']) ?></td>
                        <td><?= htmlspecialchars($exam['batch_id']) ?></td>
                        <td><?= date('d M Y', strtotime($exam['exam_date'])) ?></td>
                        <td><?= htmlspecialchars($exam['mode']) ?></td>
                        <td><?= htmlspecialchars($exam['duration']) ?> mins</td>
                        <td><?= htmlspecialchars($exam['proctor_name']) ?></td>
                        <td>
                            <span class="badge <?= $exam['malpractice_cases'] > 0 ? 'bg-danger' : 'bg-success' ?>">
                                <?= $exam['malpractice_cases'] > 0 ? $exam['malpractice_cases'] . ' cases' : 'Clean' ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-gray view-details" 
                                    data-exam-id="<?= htmlspecialchars($exam['exam_id']) ?>"
                                    data-batch-id="<?= htmlspecialchars($exam['batch_id']) ?>"
                                    data-exam-date="<?= date('d M Y', strtotime($exam['exam_date'])) ?>"
                                    data-mode="<?= htmlspecialchars($exam['mode']) ?>"
                                    data-duration="<?= htmlspecialchars($exam['duration']) ?>"
                                    data-proctor="<?= htmlspecialchars($exam['proctor_name']) ?>"
                                    data-malpractice="<?= htmlspecialchars($exam['malpractice_cases']) ?>">
                                Details
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Exam Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content dashboard-card">
            <span class="close">&times;</span>
            <h4>Exam Details - <span id="modalExamId"></span></h4>
            
            <div class="exam-info">
                <div class="info-row">
                    <span class="info-label">Batch:</span>
                    <span id="modalBatchId"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span id="modalExamDate"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Mode:</span>
                    <span id="modalExamMode"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Duration:</span>
                    <span id="modalDuration"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Proctor:</span>
                    <span id="modalProctor"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Malpractice Cases:</span>
                    <span id="modalMalpractice" class="badge"></span>
                </div>
            </div>
            
            <h5>Student Results</h5>
            <table id="studentResultsTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Score</th>
                        <th>Malpractice</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    
    <!-- Include JavaScript libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#examTable').DataTable();
        
        // Initialize date picker
        flatpickr("#examDate", {
            dateFormat: "Y-m-d",
            minDate: "today"
        });
        
        // Handle form submission
        $('#examForm').submit(function(e) {
            e.preventDefault();
            const formData = {
                batch_id: $('#batchSelect').val(),
                exam_date: $('#examDate').val(),
                duration: $('#duration').val(),
                mode: $('#examMode').val(),
                proctor_name: $('#proctorName').val()
            };
            
            $.ajax({
                url: 'save_exam.php',
                method: 'POST',
                data: formData,
                success: function(response) {
                    alert('Exam created successfully!');
                    location.reload(); // Refresh to show new exam
                },
                error: function() {
                    alert('Error creating exam');
                }
            });
        });
        
        // Handle Details button click
        $(document).on('click', '.view-details', function() {
            const examId = $(this).data('exam-id');
            const batchId = $(this).data('batch-id');
            const examDate = $(this).data('exam-date');
            const mode = $(this).data('mode');
            const duration = $(this).data('duration');
            const proctor = $(this).data('proctor');
            const malpractice = $(this).data('malpractice');
            
            // Set modal content
            $('#modalExamId').text(examId);
            $('#modalBatchId').text(batchId);
            $('#modalExamDate').text(examDate);
            $('#modalExamMode').text(mode);
            $('#modalDuration').text(duration + ' minutes');
            $('#modalProctor').text(proctor || 'Not specified');
            
            // Set malpractice cases
            const malpracticeBadge = $('#modalMalpractice');
            malpracticeBadge.text(malpractice > 0 ? malpractice + ' cases' : 'Clean');
            malpracticeBadge.removeClass('bg-danger bg-success')
                .addClass(malpractice > 0 ? 'bg-danger' : 'bg-success');
            
            // Fetch and display student results
            $.ajax({
                url: 'get_exam_students.php',
                method: 'GET',
                data: { exam_id: examId },
                success: function(students) {
                    const table = $('#studentResultsTable').DataTable({
                        data: students,
                        destroy: true, // Destroy previous instance
                        columns: [
                            { data: 'student_name' },
                            { 
                                data: 'score',
                                render: function(data) {
                                    return data ? data : '-';
                                }
                            },
                            { 
                                data: 'is_malpractice',
                                render: function(data) {
                                    return data ? 'Yes' : 'No';
                                }
                            },
                            { 
                                data: 'notes',
                                render: function(data) {
                                    return data ? data : '-';
                                }
                            }
                        ],
                        createdRow: function(row, data) {
                            if (data.is_malpractice) {
                                $(row).addClass('malpractice-row');
                            }
                        }
                    });
                    
                    // Show the modal
                    $('#detailsModal').show();
                }
            });
        });
        
        // Close modal
        $('.close').click(function() {
            $('#detailsModal').hide();
        });
        
        // Close modal when clicking outside
        $(window).click(function(event) {
            if (event.target == document.getElementById('detailsModal')) {
                $('#detailsModal').hide();
            }
        });
    });
    </script>
</body>
</html>