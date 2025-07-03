<?php
session_start();
include '../db_connection.php'; // Database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Attendance Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 800px;
            margin-top: 50px;
        }
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
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Upload Attendance Data</h2>
        
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
                <form action="attendance_upload.php" method="POST" enctype="multipart/form-data">
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
                    <a href="adminpanel.php" class="btn btn-outline-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>