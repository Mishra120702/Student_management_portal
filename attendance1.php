<?php
// Database connection
$db = new PDO('mysql:host=localhost;dbname=asd_academy1', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get all batches for the filter dropdown
$stmt = $db->query("SELECT batch_id, course_name FROM batches");
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle file upload if submitted
if (isset($_POST['import'])) {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == UPLOAD_ERR_OK) {
        require_once 'attendance_upload.php'; // Include the processing script
        // The processing script will set session messages
        header("Location: attendance.php"); // Redirect back to prevent form resubmission
        exit();
    }
}
?>

<?php include '../header.php'; // Include header with CSS and JS links
    include '../sidebar.php'; // Include sidebar
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASD Academy - Attendance Tracking</title>
    
    <!-- Include your existing CSS files here -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
            margin-right: 10px;
            font-size: 14px;
            font-family: 'Segoe UI', sans-serif;
            transition: border-color 0.3s;
        }
        
        .minimal-input:focus {
            border-color: #3498db;
            outline: none;
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
        
        .badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .bg-success {
            background-color: #27ae60;
            color: white;
        }
        
        .bg-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .bg-warning {
            background-color: #f39c12;
            color: white;
        }
        
        .filter-card {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .status-dropdown {
            border: 1px solid #d3dce6;
            border-radius: 6px;
            padding: 8px;
            font-family: 'Segoe UI', sans-serif;
            background-color: white;
            cursor: pointer;
        }
        
        .remarks-tooltip {
            position: relative;
            display: inline-block;
        }
        
        .remarks-tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #2c3e50;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            font-family: 'Segoe UI', sans-serif;
        }
        
        .remarks-tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
        
        /* New Table styles to match the image */
        #attendanceTable {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px;
        }
        
        #attendanceTable thead th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e6ed;
            position: sticky;
            top: 0;
        }
        
        #attendanceTable tbody td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e6ed;
            vertical-align: middle;
        }
        
        #attendanceTable tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        #attendanceTable tbody tr:hover {
            background-color: #f1f5f9;
        }
        
        /* Status badges */
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }
        
        .status-present {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-absent {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .status-upcoming {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-completed {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        
        /* Action buttons */
        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
            margin: 0 3px;
            color: #64748b;
            transition: color 0.2s;
        }
        
        .action-btn:hover {
            color: #334155;
        }
        
        .action-btn.edit {
            color: #3b82f6;
        }
        
        .action-btn.delete {
            color: #ef4444;
        }
        
        /* Toggle switch styles */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin: 0 5px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #10b981;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .camera-slider input:checked + .slider {
            background-color: #3b82f6;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .filter-card {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-card select, 
            .filter-card input, 
            .filter-card button {
                width: 100%;
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            #attendanceTable th, 
            #attendanceTable td {
                padding: 8px 10px;
            }
        }
        
        /* Attendance-specific styles */
        .remarks-input {
            border: 1px solid #d3dce6;
            border-radius: 4px;
            padding: 8px 12px;
            width: 100%;
            max-width: 200px;
            font-family: 'Segoe UI', sans-serif;
            font-size: 13px;
            transition: border-color 0.3s;
        }
        
        .remarks-input:focus {
            border-color: #3498db;
            outline: none;
        }
        
        /* Show entries style */
        .dataTables_length {
            margin-bottom: 15px;
        }
        
        .dataTables_info {
            margin-top: 15px;
        }
        
        /* Save button container */
        .save-container {
            margin-top: 20px;
            text-align: right;
        }
        
        /* Upload form styles */
        .instructions {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert {
            margin-top: 20px;
        }
        
        .download-sample {
            margin-top: 15px;
        }
        
        .upload-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e6ed;
        }
        
        .upload-toggle {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .upload-toggle button {
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Attendance Tracking</h2>
        
        <!-- Upload toggle buttons -->
        <div class="upload-toggle">
            <button id="showManualBtn" class="btn-blue">Manual Attendance</button>
            <button id="showUploadBtn" class="btn-gray">Upload Excel</button>
        </div>
        
        <!-- Manual Attendance Section -->
        <div id="manualAttendanceSection">
            <!-- Filters Card -->
            <div class="card filter-card">
                <select id="batchFilter" class="minimal-input">
                    <option value="">All Batches</option>
                    <?php foreach ($batches as $batch): ?>
                    <option value="<?= htmlspecialchars($batch['batch_id']) ?>">
                        <?= htmlspecialchars($batch['batch_id'] . ' - ' . $batch['course_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" id="dateFilter" class="minimal-input date-picker" placeholder="Select date">
                
                <button id="markAllPresent" class="btn-gray">Mark All Present</button>
            </div>
            
            <!-- Attendance Table Card -->
            <div class="card">
                <table id="attendanceTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Batch ID</th>
                            <th>Status</th>
                            <th>Camera</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
                
                <div class="save-container">
                    <button id="saveAttendance" class="btn-blue">Save Changes</button>
                </div>
            </div>
        </div>
        
        <!-- Upload Excel Section (initially hidden) -->
        <div id="uploadExcelSection" style="display: none;">
            <?php if (isset($_SESSION['import_message'])): ?>
                <div class="alert alert-info">
                    <?php 
                    echo $_SESSION['import_message']; 
                    unset($_SESSION['import_message']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="instructions">
                <h5>Instructions:</h5>
                <ol>
                    <li>Download the sample Excel template below</li>
                    <li>Fill in the attendance data following the format</li>
                    <li>Upload the completed file</li>
                </ol>
                <p class="text-danger">Important: Do not modify the column headers in the template.</p>
                
                <div class="download-sample">
                    <a href="download_sample.php" class="btn btn-secondary">Download Sample Template</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="attendance.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="excel_file" class="form-label">Select Excel File</label>
                            <input class="form-control" type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                            <div class="form-text">Only .xlsx or .xls files are accepted</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-text">Expected columns in order:</div>
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Batch ID</th>
                                        <th>Student Name</th>
                                        <th>Status</th>
                                        <th>Camera Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>YYYY-MM-DD</td>
                                        <td>B001</td>
                                        <td>Alice Williams</td>
                                        <td>Present/Absent/Late</td>
                                        <td>On/Off</td>
                                        <td>Optional notes</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <button type="submit" name="import" class="btn btn-primary">Upload Attendance</button>
                        <a href="attendance.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include your existing JS files here -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize date picker (default to today)
        flatpickr("#dateFilter", {
            dateFormat: "Y-m-d",
            defaultDate: new Date(),
            allowInput: true
        });
        
        // Initialize DataTable
        var table = $('#attendanceTable').DataTable({
            ajax: {
                url: 'attendance_api.php?action=fetch',
                data: function(d) {
                    return {
                        batch_id: $('#batchFilter').val(),
                        date: $('#dateFilter').val()
                    };
                },
                dataSrc: 'data'
            },
            columns: [
                { 
                    data: 'student_name',
                    render: function(data, type, row) {
                        return `<span class="student-name">${data}</span>`;
                    }
                },
                { 
                    data: 'batch_id',
                    render: function(data, type, row) {
                        return `<span class="batch-id">${data}</span>`;
                    }
                },
                { 
                    data: null,
                    render: function(data, type, row) {
                        let statusClass = row.status === 'Present' ? 'status-present' : 'status-absent';
                        let statusText = row.status === 'Present' ? 'Present' : 'Absent';
                        
                        return `
                            <div class="status-container">
                                <span class="status-badge ${statusClass}">${statusText}</span>
                                <label class="switch">
                                    <input type="checkbox" class="status-toggle" data-id="${row.id}" 
                                        ${row.status === 'Present' ? 'checked' : ''}>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        `;
                    }
                },
                { 
                    data: null,
                    render: function(data, type, row) {
                        return `
                            <label class="switch camera-slider">
                                <input type="checkbox" class="camera-toggle" data-id="${row.id}" 
                                    ${row.camera_status === 'On' ? 'checked' : ''}>
                                <span class="slider"></span>
                            </label>
                        `;
                    }
                },
                { 
                    data: 'remarks',
                    render: function(data, type, row) {
                        if (data && row.status === 'Absent') {
                            return `
                                <div class="remarks-tooltip">
                                    ${data || 'N/A'}
                                    <span class="tooltiptext">${data || 'No remarks'}</span>
                                </div>
                                <input type="text" class="remarks-input" data-id="${row.id}" 
                                       value="${data || ''}" placeholder="Add remarks" style="display: none;">
                            `;
                        }
                        return `
                            <input type="text" class="remarks-input" data-id="${row.id}" 
                                   value="${data || ''}" placeholder="Add remarks">
                        `;
                    }
                }
            ],
            responsive: true,
            language: {
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                search: "Search:",
                paginate: {
                    previous: "Previous",
                    next: "Next"
                }
            }
        });
        
        // Reload table when filters change
        $('#batchFilter, #dateFilter').change(function() {
            table.ajax.reload();
        });
        
        // Mark all present
        $('#markAllPresent').click(function() {
            let batchId = $('#batchFilter').val();
            let date = $('#dateFilter').val();
            
            if (!date) {
                alert('Please select a date first');
                return;
            }
            
            if (!batchId) {
                alert('Please select a batch first');
                return;
            }
            
            if (confirm('Mark all students as Present for this batch on ' + date + '?')) {
                $.post('attendance_api.php', {
                    action: 'mark_all_present',
                    batch_id: batchId,
                    date: date
                }, function(response) {
                    if (response.success) {
                        table.ajax.reload();
                    }
                }, 'json');
            }
        });
        
        // Save attendance changes
        $('#saveAttendance').click(function() {
            let updates = [];
            
            $('.status-toggle').each(function() {
                let id = $(this).data('id');
                let status = $(this).is(':checked') ? 'Present' : 'Absent';
                let cameraStatus = $(`.camera-toggle[data-id="${id}"]`).is(':checked') ? 'On' : 'Off';
                let remarks = $(`.remarks-input[data-id="${id}"]`).val();
                
                updates.push({
                    id: id,
                    status: status,
                    camera_status: cameraStatus,
                    remarks: remarks
                });
            });
            
            // Send updates in batches if there are many
            Promise.all(updates.map(update => {
                return $.post('attendance_api.php', {
                    action: 'update',
                    id: update.id,
                    status: update.status,
                    camera_status: update.camera_status,
                    remarks: update.remarks
                });
            })).then(() => {
                alert('Attendance updated successfully');
                table.ajax.reload();
            });
        });
        
        // Show remarks input when status is Absent
        $(document).on('change', '.status-toggle', function() {
            let isPresent = $(this).is(':checked');
            let id = $(this).data('id');
            let remarksInput = $(`.remarks-input[data-id="${id}"]`);
            let statusBadge = $(this).closest('.status-container').find('.status-badge');
            
            if (!isPresent) {
                remarksInput.show();
                statusBadge.removeClass('status-present').addClass('status-absent').text('Absent');
            } else {
                remarksInput.hide();
                statusBadge.removeClass('status-absent').addClass('status-present').text('Present');
            }
        });
        
        // Toggle between manual and upload sections
        $('#showManualBtn').click(function() {
            $('#manualAttendanceSection').show();
            $('#uploadExcelSection').hide();
            $(this).removeClass('btn-gray').addClass('btn-blue');
            $('#showUploadBtn').removeClass('btn-blue').addClass('btn-gray');
        });
        
        $('#showUploadBtn').click(function() {
            $('#manualAttendanceSection').hide();
            $('#uploadExcelSection').show();
            $(this).removeClass('btn-gray').addClass('btn-blue');
            $('#showManualBtn').removeClass('btn-blue').addClass('btn-gray');
        });
    });
    </script>
</body>
</html>