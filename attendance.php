<?php
// Database connection
$db = new PDO('mysql:host=localhost;dbname=asd_academy1', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get all batches for the filter dropdown
$stmt = $db->query("SELECT batch_id, course_name FROM batches");
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASD Academy - Attendance Tracking</title>
    
    <!-- Include your existing CSS files here -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        
        /* Table styles */
        #attendanceTable {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        #attendanceTable th {
            background-color: #f8fafc;
            color: #3498db;
            font-weight: 600;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #e0e6ed;
        }
        
        #attendanceTable td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e6ed;
        }
        
        #attendanceTable tr:hover {
            background-color: #f8fafc;
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
        .status-dropdown {
            margin-left: 8px;
        }
        
        .remarks-input {
            border: 1px solid #d3dce6;
            border-radius: 6px;
            padding: 8px 12px;
            width: 200px;
            font-family: 'Segoe UI', sans-serif;
            font-size: 13px;
            transition: border-color 0.3s;
        }
        
        .remarks-input:focus {
            border-color: #3498db;
            outline: none;
        }
        
        /* Tooltip styles */
        .tooltiptext {
            font-size: 12px;
            line-height: 1.4;
        }
        
        /* Status badges */
        .badge {
            display: inline-block;
            min-width: 70px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Include your dashboard header/sidebar here -->
    
    <div class="container">
        <h2>Attendance Tracking</h2>
        
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
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded via AJAX -->
                </tbody>
            </table>
            
            <div style="margin-top: 20px; text-align: right;">
                <button id="saveAttendance" class="btn-blue">Save Changes</button>
            </div>
        </div>
    </div>
    
    <!-- Include your existing JS files here -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
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
                url: 'attendance/attendance_api.php?action=fetch',
                data: function(d) {
                    return {
                        batch_id: $('#batchFilter').val(),
                        date: $('#dateFilter').val()
                    };
                },
                dataSrc: 'data'
            },
            columns: [
                { data: 'student_name' },
                { data: 'batch_id' },
                { 
                    data: null,
                    render: function(data, type, row) {
                        let statusOptions = `
                            <select class="status-dropdown" data-id="${row.id}">
                                <option value="Present" ${row.status === 'Present' ? 'selected' : ''}>Present</option>
                                <option value="Absent" ${row.status === 'Absent' ? 'selected' : ''}>Absent</option>
                                <option value="Late" ${row.status === 'Late' ? 'selected' : ''}>Late</option>
                            </select>
                        `;
                        
                        if (row.status === 'Present') {
                            return `<span class="badge bg-success">Present</span> ${statusOptions}`;
                        } else if (row.status === 'Absent') {
                            return `<span class="badge bg-danger">Absent</span> ${statusOptions}`;
                        } else if (row.status === 'Late') {
                            return `<span class="badge bg-warning">Late</span> ${statusOptions}`;
                        }
                        return statusOptions;
                    }
                },
                { 
                    data: 'remarks',
                    render: function(data, type, row) {
                        if (data && (row.status === 'Absent' || row.status === 'Late')) {
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
            responsive: true
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
            
            $('.status-dropdown').each(function() {
                let id = $(this).data('id');
                let status = $(this).val();
                let remarks = $(`.remarks-input[data-id="${id}"]`).val();
                
                updates.push({
                    id: id,
                    status: status,
                    remarks: remarks
                });
            });
            
            // Send updates in batches if there are many
            Promise.all(updates.map(update => {
                return $.post('attendance_api.php', {
                    action: 'update',
                    id: update.id,
                    status: update.status,
                    remarks: update.remarks
                });
            })).then(() => {
                alert('Attendance updated successfully');
                table.ajax.reload();
            });
        });
        
        // Show remarks input when status changes to Absent/Late
        $(document).on('change', '.status-dropdown', function() {
            let status = $(this).val();
            let id = $(this).data('id');
            let remarksInput = $(`.remarks-input[data-id="${id}"]`);
            
            if (status === 'Absent' || status === 'Late') {
                remarksInput.show();
            } else {
                remarksInput.hide();
            }
        });
    });
    </script>
</body>
</html>