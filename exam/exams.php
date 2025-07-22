<?php
// Database connection
$db = new PDO('mysql:host=localhost;dbname=asd_academy1', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
// Get active batches for dropdown
$batches = $db->query("SELECT batch_id, course_name FROM batches WHERE status = 'ongoing'")->fetchAll(PDO::FETCH_ASSOC);
// Get all exams for the table
$exams = $db->query("SELECT * FROM proctored_exams ORDER BY exam_date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Include header
include '../header.php';
include '../sidebar.php';
?>

<style>
    .exam-container {
        margin-left: 0;
        transition: margin-left 0.3s ease;
    }
    
    @media (min-width: 768px) {
        .exam-container {
            margin-left: 16rem;
        }
    }
    
    .card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        padding: 20px;
        margin-bottom: 25px;
        border: 1px solid #e0e6ed;
    }
    
    .form-input {
        border: 1px solid #d3dce6;
        border-radius: 6px;
        padding: 10px 15px;
        font-size: 14px;
        width: 100%;
        margin-bottom: 15px;
        font-family: 'Inter', sans-serif;
        transition: border-color 0.3s;
    }
    
    .form-input:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .btn-primary {
        background-color: #3b82f6;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 10px 18px;
        cursor: pointer;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        transition: background-color 0.3s;
        font-weight: 500;
    }
    
    .btn-primary:hover {
        background-color: #2563eb;
    }
    
    .btn-secondary {
        background-color: #6b7280;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 10px 18px;
        cursor: pointer;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        transition: background-color 0.3s;
        font-weight: 500;
    }
    
    .btn-secondary:hover {
        background-color: #4b5563;
    }
    
    .badge {
        padding: 6px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .bg-danger {
        background-color: #ef4444;
        color: white;
    }
    
    .bg-success {
        background-color: #10b981;
        color: white;
    }
    
    .table-container {
        overflow-x: auto;
        margin-bottom: 20px;
    }
    
    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    th {
        background-color: #f9fafb;
        color: #3b82f6;
        font-weight: 600;
        padding: 12px 15px;
        text-align: left;
        border-bottom: 2px solid #e5e7eb;
    }
    
    td {
        padding: 12px 15px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    tr:hover {
        background-color: #f9fafb;
    }
    
    .malpractice-row {
        background-color: #fef2f2;
    }
    
    .grid-2cols {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.4);
        overflow: auto;
    }
    
    .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 25px;
        width: 80%;
        max-width: 800px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        border: 1px solid #e0e6ed;
    }
    
    .close {
        float: right;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6b7280;
        transition: color 0.3s;
    }
    
    .close:hover {
        color: #4b5563;
    }
    
    .modal-header {
        padding-bottom: 15px;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 15px;
    }
    
    .modal-footer {
        padding-top: 15px;
        border-top: 1px solid #e5e7eb;
        margin-top: 15px;
        display: flex;
        justify-content: flex-end;
    }
    
    .info-row {
        display: flex;
        margin-bottom: 10px;
    }
    
    .info-label {
        font-weight: 600;
        width: 150px;
        color: #374151;
    }
    
    @media (max-width: 768px) {
        .exam-container {
            margin-left: 0;
        }
        
        .grid-2cols {
            grid-template-columns: 1fr;
        }
        
        .modal-content {
            width: 95%;
            margin: 10% auto;
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

<!-- Main Content -->
<div class="flex-1 ml-0 md:ml-64 min-h-screen exam-container">
    <!-- Header -->
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-clipboard-list text-blue-500"></i>
            <span>Exam Management</span>
        </h1>
    </header>

    <div class="p-4 md:p-6">
        <!-- Exam Scheduling Card -->
        <div class="card">
            <div class="flex items-center mb-6">
                <i class="fas fa-plus-circle text-blue-500 text-xl mr-3"></i>
                <h4 class="text-xl font-semibold text-gray-800 m-0">Schedule New Exam</h4>
            </div>
            <form id="examForm">
                <select id="batchSelect" class="form-input" required>
                    <option value="">Select Batch</option>
                    <?php foreach ($batches as $batch): ?>
                        <option value="<?= htmlspecialchars($batch['batch_id']) ?>">
                            <?= htmlspecialchars($batch['batch_id']) ?> - <?= htmlspecialchars($batch['course_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div class="grid-2cols">
                    <input type="date" id="examDate" class="form-input date-picker" placeholder="Exam Date" required>
                    <input type="number" id="duration" class="form-input" placeholder="Duration (minutes)" min="1" required>
                </div>
                
                <div class="grid-2cols">
                    <select id="examMode" class="form-input" required>
                        <option value="">Select Mode</option>
                        <option value="Online">Online</option>
                        <option value="Offline">Offline</option>
                    </select>
                    <input type="text" id="proctorName" class="form-input" placeholder="Proctor Name">
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-calendar-plus mr-2"></i>
                        Create Exam
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Exam Records Table -->
        <div class="card">
            <div class="flex items-center mb-6">
                <i class="fas fa-list-alt text-blue-500 text-xl mr-3"></i>
                <h4 class="text-xl font-semibold text-gray-800 m-0">Exam Records</h4>
            </div>
            <div class="table-container">
                <table id="examTable">
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
                                <div class="flex space-x-2">
                                    <button class="btn-primary view-details" 
                                            data-exam-id="<?= htmlspecialchars($exam['exam_id']) ?>"
                                            data-batch-id="<?= htmlspecialchars($exam['batch_id']) ?>"
                                            data-exam-date="<?= date('d M Y', strtotime($exam['exam_date'])) ?>"
                                            data-mode="<?= htmlspecialchars($exam['mode']) ?>"
                                            data-duration="<?= htmlspecialchars($exam['duration']) ?>"
                                            data-proctor="<?= htmlspecialchars($exam['proctor_name']) ?>"
                                            data-malpractice="<?= htmlspecialchars($exam['malpractice_cases']) ?>">
                                        <i class="fas fa-eye mr-1"></i>
                                        Details
                                    </button>
                                    <a href="exam_marks.php?exam_id=<?= htmlspecialchars($exam['exam_id']) ?>" class="btn-secondary">
                                        <i class="fas fa-edit mr-1"></i>
                                        Marks
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Exam Details Modal -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-header">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-500 text-xl mr-3"></i>
                <h3 class="text-xl font-semibold text-gray-800">Exam Details - <span id="modalExamId"></span></h3>
            </div>
        </div>
        
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
        
        <div class="flex items-center mt-6 mb-4">
            <i class="fas fa-users text-blue-500 text-lg mr-3"></i>
            <h4 class="text-lg font-semibold text-gray-800">Student Results</h4>
        </div>
        
        <div class="table-container">
            <table id="studentResultsTable">
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
        
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal('detailsModal')">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-header">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                Success
            </h3>
        </div>
        <div class="modal-body">
            <p id="successMessage" class="text-gray-700"></p>
        </div>
        <div class="modal-footer">
            <button class="btn-primary" onclick="closeModal('successModal')">
                OK
            </button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-header">
            <h3 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                Error
            </h3>
        </div>
        <div class="modal-body">
            <p id="errorMessage" class="text-gray-700"></p>
        </div>
        <div class="modal-footer">
            <button class="btn-primary" onclick="closeModal('errorModal')">
                OK
            </button>
        </div>
    </div>
</div>

<!-- Include JavaScript libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script>
$(document).ready(function() {
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
                showSuccess('Exam created successfully!');
                setTimeout(() => {
                    location.reload(); // Refresh to show new exam
                }, 1500);
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.error || 'Error creating exam');
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
        // Change this part (around line 450):
$.ajax({
    url: 'get_exam_students.php',
    method: 'GET',
    data: { exam_id: examId },
    success: function(students) {
        const tableBody = $('#studentResultsTable tbody');
        tableBody.empty();
        
        // Make sure students is an array
        if (!Array.isArray(students)) {
            showError('Invalid student data received');
            return;
        }
        
        students.forEach(student => {
            const row = `
                <tr ${student.is_malpractice ? 'class="malpractice-row"' : ''}>
                    <td>${student.student_name}</td>
                    <td>${student.score ? student.score + '%' : '-'}</td>
                    <td>${student.is_malpractice ? '<span class="text-red-600 font-semibold">Yes</span>' : '<span class="text-green-600 font-semibold">No</span>'}</td>
                    <td>${student.notes || '-'}</td>
                </tr>
            `;
            tableBody.append(row);
        });
        
        // Show the modal
        $('#detailsModal').show();
    },
    error: function(xhr) {
        try {
            const error = JSON.parse(xhr.responseText);
            showError(error.error || 'Error loading student results');
        } catch (e) {
            showError('Error loading student results');
        }
    }
});

function showSuccess(message) {
    $('#successMessage').text(message);
    $('#successModal').show();
}

function showError(message) {
    $('#errorMessage').text(message);
    $('#errorModal').show();
}

function closeModal(id) {
    $(`#${id}`).hide();
}

// Close modal when clicking outside
$(window).click(function(event) {
    if ($(event.target).hasClass('modal')) {
        $(event.target).hide();
    }
});
</script>

</body>
</html>