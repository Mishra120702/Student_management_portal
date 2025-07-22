<?php
// exams_marks.php
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get exam details
$exam_id = $_GET['exam_id'] ?? '';
$exam = [];
$batch_id = '';
$students = [];

if ($exam_id) {
    try {
        // Get exam info
        $stmt = $db->prepare("SELECT * FROM proctored_exams WHERE exam_id = ?");
        $stmt->execute([$exam_id]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exam) {
            $batch_id = $exam['batch_id'];
            
            // Get students in this batch
            // Get students in the batch
$stmt = $db->prepare("
    SELECT 
        s.student_id, 
        s.first_name, 
        s.last_name, 
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        s.email,
        s.current_status
    FROM 
        students s 
    WHERE 
        s.batch_name = :batch_id 
        AND s.current_status = 'active'
    ORDER BY 
        s.last_name, 
        s.first_name
");
$stmt->execute([':batch_id' => $batch_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get existing marks if any
            $stmt = $db->prepare("SELECT * FROM exam_students WHERE exam_id = ?");
            $stmt->execute([$exam_id]);
            $existing_marks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Map existing marks by student name for easy lookup
            $marks_map = [];
            foreach ($existing_marks as $mark) {
                $marks_map[$mark['student_name']] = $mark;
            }
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Include header and sidebar
include '../header.php';
include '../sidebar.php';
?>

<style>
    .marks-container {
        margin-left: 0;
        transition: margin-left 0.3s ease;
    }
    
    @media (min-width: 768px) {
        .marks-container {
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
    
    .file-upload {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px;
        border: 2px dashed #d1d5db;
        border-radius: 6px;
        margin-bottom: 20px;
        transition: border-color 0.3s;
    }
    
    .file-upload:hover {
        border-color: #3b82f6;
    }
    
    .file-upload input {
        display: none;
    }
    
    .file-upload label {
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .file-upload i {
        font-size: 48px;
        color: #3b82f6;
        margin-bottom: 10px;
    }
    
    .file-upload p {
        margin: 5px 0;
        color: #6b7280;
    }
    
    .file-upload .btn {
        margin-top: 10px;
    }
    
    .tab-container {
        display: flex;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 20px;
    }
    
    .tab {
        padding: 10px 20px;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.3s;
    }
    
    .tab.active {
        border-bottom-color: #3b82f6;
        color: #3b82f6;
        font-weight: 600;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
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
    
    @media (max-width: 768px) {
        .marks-container {
            margin-left: 0;
        }
        
        .modal-content {
            width: 95%;
            margin: 10% auto;
            padding: 15px;
        }
    }
</style>

<!-- Main Content -->
<div class="flex-1 ml-0 md:ml-64 min-h-screen marks-container">
    <!-- Header -->
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-file-alt text-blue-500"></i>
            <span>Exam Marks Entry</span>
        </h1>
    </header>

    <div class="p-4 md:p-6">
        <?php if (!$exam_id || !$exam): ?>
            <div class="card">
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-circle text-yellow-500 text-4xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-800">No exam selected</h3>
                    <p class="text-gray-600 mt-2">Please select an exam from the exam list to enter marks.</p>
                    <a href="exams.php" class="btn-primary inline-block mt-4">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Exams
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Exam Info Card -->
            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">
                            <?= htmlspecialchars($exam['exam_id']) ?> - Marks Entry
                        </h3>
                        <p class="text-gray-600">
                            Batch: <?= htmlspecialchars($batch_id) ?> | 
                            Date: <?= date('d M Y', strtotime($exam['exam_date'])) ?> | 
                            Mode: <?= htmlspecialchars($exam['mode']) ?>
                        </p>
                    </div>
                    <div>
                        <span class="badge <?= $exam['malpractice_cases'] > 0 ? 'bg-danger' : 'bg-success' ?>">
                            <?= $exam['malpractice_cases'] > 0 ? $exam['malpractice_cases'] . ' malpractice cases' : 'No malpractice cases' ?>
                        </span>
                    </div>
                </div>
                
                <?php if (count($students) > 0): ?>
    <div class="mb-4">
        <p class="text-gray-600">
            Showing <?= count($students) ?> active students in batch <?= htmlspecialchars($batch_id) ?>
            <?php if (count($existing_marks) > 0): ?>
                (<?= count($existing_marks) ?> with existing marks)
            <?php endif; ?>
        </p>
    </div>
    
    <div class="table-container">
                    <div class="tab active" data-tab="manual">Manual Entry</div>
                    <div class="tab" data-tab="upload">Upload Excel</div>
                </div>
                
                <!-- Manual Entry Tab -->
                <div class="tab-content active" id="manual-tab">
                    <form id="marksForm">
                        <input type="hidden" name="exam_id" value="<?= htmlspecialchars($exam_id) ?>">
                        
                        <div class="table-container">
                            <table id="marksTable">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Score (%)</th>
                                        <th>Malpractice</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                <?php foreach ($students as $student): 
                    $existing_mark = $marks_map[$student['student_name']] ?? null;
                    $hasExistingMark = $existing_mark !== null;
                ?>
                    <tr class="<?= $hasExistingMark ? 'bg-blue-50' : '' ?>">
                        <td><?= htmlspecialchars($student['student_id']) ?></td>
                        <td><?= htmlspecialchars($student['student_name']) ?></td>
                        <td>
                            <input type="number" 
                                   name="scores[]" 
                                   class="form-input <?= $hasExistingMark ? 'border-blue-300' : '' ?>" 
                                   min="0" 
                                   max="100" 
                                   step="0.01"
                                   value="<?= $existing_mark ? htmlspecialchars($existing_mark['score']) : '' ?>">
                        </td>
                        <td>
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox" 
                                                           name="malpractices[]" 
                                                           value="1" 
                                                           class="form-checkbox h-5 w-5 text-blue-600"
                                                           <?= $existing_mark && $existing_mark['is_malpractice'] ? 'checked' : '' ?>>
                                                    <span class="ml-2">Yes</span>
                                                    <input type="hidden" name="malpractices[]" value="0">
                                                </label>
                                            </td>
                                            <td>
                                                <input type="text" 
                                                       name="notes[]" 
                                                       class="form-input" 
                                                       placeholder="Notes"
                                                       value="<?= $existing_mark ? htmlspecialchars($existing_mark['notes']) : '' ?>">
                                                <input type="hidden" 
                                                       name="students[]" 
                                                       value="<?= htmlspecialchars($student['student_name']) ?>">
                                            </td>
                                        </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-warning p-4 mb-4">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        No active students found in batch <?= htmlspecialchars($batch_id) ?>.
    </div>
<?php endif; ?>
                        
                        <div class="flex justify-end space-x-3">
                            <a href="exams.php" class="btn-secondary">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Back to Exams
                            </a>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save mr-2"></i>
                                Save Marks
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Excel Upload Tab -->
                <div class="tab-content" id="upload-tab">
                    <div class="file-upload">
                        <input type="file" id="excelFile" accept=".xlsx, .xls, .csv">
                        <label for="excelFile">
                            <i class="fas fa-file-excel"></i>
                            <p class="font-semibold">Upload Excel File</p>
                            <p class="text-sm">Supports .xlsx, .xls, .csv files</p>
                            <button class="btn-primary">
                                <i class="fas fa-upload mr-2"></i>
                                Choose File
                            </button>
                        </label>
                    </div>
                    
                    <div class="text-center mb-4">
                        <p class="text-gray-600 mb-2">Download template file:</p>
                        <button id="downloadTemplate" class="btn-secondary">
                            <i class="fas fa-download mr-2"></i>
                            Download Excel Template
                        </button>
                    </div>
                    
                    <div id="uploadPreview" class="table-container" style="display: none;">
                        <h4 class="text-lg font-semibold mb-3">Preview (First 5 rows)</h4>
                        <table id="previewTable">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Score (%)</th>
                                    <th>Malpractice</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        
                        <div class="flex justify-end mt-4">
                            <button id="cancelUpload" class="btn-secondary mr-3">
                                <i class="fas fa-times mr-2"></i>
                                Cancel
                            </button>
                            <button id="confirmUpload" class="btn-primary">
                                <i class="fas fa-check mr-2"></i>
                                Confirm Upload
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>
$(document).ready(function() {
    // Tab switching
    $('.tab').click(function() {
        $('.tab').removeClass('active');
        $(this).addClass('active');
        
        const tabId = $(this).data('tab');
        $('.tab-content').removeClass('active');
        $(`#${tabId}-tab`).addClass('active');
    });
    
    // Handle manual form submission
    $('#marksForm').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'save_exam_marks.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showSuccess('Marks saved successfully!');
                } else {
                    showError(response.error || 'Failed to save marks');
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.error || 'An error occurred while saving marks');
            }
        });
    });
    
    // Handle Excel file upload
    $('#excelFile').change(function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = XLSX.utils.sheet_to_json(firstSheet);
                
                if (jsonData.length === 0) {
                    showError('The Excel file is empty');
                    return;
                }
                
                // Validate required columns
                const requiredColumns = ['Student ID', 'Student Name', 'Score (%)', 'Malpractice'];
                const firstRow = jsonData[0];
                const missingColumns = requiredColumns.filter(col => !(col in firstRow));
                
                if (missingColumns.length > 0) {
                    showError(`Missing required columns: ${missingColumns.join(', ')}`);
                    return;
                }
                
                // Display preview (first 5 rows)
                const previewRows = jsonData.slice(0, 5);
                const previewTable = $('#previewTable tbody');
                previewTable.empty();
                
                previewRows.forEach(row => {
                    previewTable.append(`
                        <tr>
                            <td>${row['Student ID'] || ''}</td>
                            <td>${row['Student Name'] || ''}</td>
                            <td>${row['Score (%)'] || ''}</td>
                            <td>${row['Malpractice'] ? 'Yes' : 'No'}</td>
                            <td>${row['Notes'] || ''}</td>
                        </tr>
                    `);
                });
                
                // Store the full data for confirmation
                $('#uploadPreview').data('excelData', jsonData);
                $('#uploadPreview').show();
            } catch (error) {
                showError('Error processing Excel file: ' + error.message);
            }
        };
        reader.readAsArrayBuffer(file);
    });
    
    // Handle template download
    $('#downloadTemplate').click(function() {
        // Create template data
        const templateData = [
            {
                'Student ID': 'STD001',
                'Student Name': 'John Doe',
                'Score (%)': 85.5,
                'Malpractice': 0,
                'Notes': 'Good performance'
            },
            {
                'Student ID': 'STD002',
                'Student Name': 'Jane Smith',
                'Score (%)': 92,
                'Malpractice': 0,
                'Notes': 'Excellent work'
            }
        ];
        
        // Create worksheet
        const ws = XLSX.utils.json_to_sheet(templateData);
        
        // Create workbook
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Marks Template");
        
        // Generate file and download
        const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
        saveAs(new Blob([wbout], { type: 'application/octet-stream' }), 'Exam_Marks_Template.xlsx');
    });
    
    // Handle cancel upload
    $('#cancelUpload').click(function() {
        $('#excelFile').val('');
        $('#uploadPreview').hide();
    });
    
    // Handle confirm upload
    $('#confirmUpload').click(function() {
        const excelData = $('#uploadPreview').data('excelData');
        if (!excelData || excelData.length === 0) {
            showError('No data to upload');
            return;
        }
        
        // Prepare data for submission
        const students = [];
        const scores = [];
        const malpractices = [];
        const notes = [];
        
        excelData.forEach(row => {
            students.push(row['Student Name']);
            scores.push(row['Score (%)']);
            malpractices.push(row['Malpractice'] ? 1 : 0);
            notes.push(row['Notes'] || '');
        });
        
        // Submit via AJAX
        $.ajax({
            url: 'save_exam_marks.php',
            method: 'POST',
            data: {
                exam_id: '<?= $exam_id ?>',
                students: students,
                scores: scores,
                malpractices: malpractices,
                notes: notes
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Marks uploaded successfully!');
                    $('#excelFile').val('');
                    $('#uploadPreview').hide();
                } else {
                    showError(response.error || 'Failed to upload marks');
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.error || 'An error occurred while uploading marks');
            }
        });
    });
    
    // Modal close handlers
    $('.modal .close').click(function() {
        $(this).closest('.modal').hide();
    });
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