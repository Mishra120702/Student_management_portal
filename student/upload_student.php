<?php
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$message = '';
$error = '';
$successCount = 0;
$skipped = [];

if (isset($_POST['upload'])) {
    if (isset($_FILES['excel_file'])) {
        $file = $_FILES['excel_file'];
        
        // Check for errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = "Error uploading file. Error code: " . $file['error'];
        } else {
            // Validate file type
            $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($fileType != 'xlsx' && $fileType != 'xls') {
                $error = "Only Excel files are allowed (XLSX or XLS).";
            } else {
                try {
                    $spreadsheet = IOFactory::load($file['tmp_name']);
                    $sheet = $spreadsheet->getActiveSheet();
                    $data = $sheet->toArray();
                    
                    // Start from row 1 (assuming first row is headers)
                    for ($i = 1; $i < count($data); $i++) {
                        $row = $data[$i];
                        
                        // Validate row has enough columns (11 required fields)
                        if (count($row) < 11) {
                            $skipped[] = "Row " . ($i + 1) . ": Insufficient data columns (need 11 fields)";
                            continue;
                        }
                        
                        // Extract data from Excel columns
                        $studentId = trim($row[0] ?? '');
                        $firstName = trim($row[1] ?? '');
                        $lastName = trim($row[2] ?? '');
                        $email = trim($row[3] ?? '');
                        $phone = trim($row[4] ?? '');
                        $dob = trim($row[5] ?? '');
                        $batchId = trim($row[6] ?? '');
                        $enrollmentDate = trim($row[7] ?? date('Y-m-d'));
                        $status = strtolower(trim($row[8] ?? 'active'));
                        $fatherName = trim($row[9] ?? '');
                        $fatherPhone = trim($row[10] ?? '');
                        
                        // Validate required fields
                        $validationErrors = [];
                        
                        if (empty($studentId)) {
                            $validationErrors[] = "Student ID is required";
                        }
                        
                        if (empty($firstName)) {
                            $validationErrors[] = "First name is required";
                        }
                        
                        if (empty($lastName)) {
                            $validationErrors[] = "Last name is required";
                        }
                        
                        if (empty($email)) {
                            $validationErrors[] = "Email is required";
                        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $validationErrors[] = "Invalid email format";
                        }
                        
                        if (!empty($validationErrors)) {
                            $skipped[] = "Row " . ($i + 1) . ": " . implode(", ", $validationErrors);
                            continue;
                        }
                        
                        // Validate batch exists
                        $batchCheck = $db->prepare("SELECT batch_id FROM batches WHERE batch_id = ?");
                        $batchCheck->execute([$batchId]);
                        
                        if ($batchCheck->rowCount() === 0) {
                            $skipped[] = "Row " . ($i + 1) . ": Batch ID $batchId not found";
                            continue;
                        }
                        
                        // Validate status
                        if (!in_array($status, ['active', 'dropped', 'transferred', 'completed'])) {
                            $status = 'active';
                        }
                        
                        // Format date of birth if provided
                        $dobFormatted = null;
                        if (!empty($dob)) {
                            try {
                                $dobFormatted = date('Y-m-d', strtotime($dob));
                            } catch (Exception $e) {
                                $skipped[] = "Row " . ($i + 1) . ": Invalid date format for DOB (use YYYY-MM-DD)";
                                continue;
                            }
                        }
                        
                        // Format enrollment date if provided
                        $enrollmentDateFormatted = date('Y-m-d');
                        if (!empty($enrollmentDate)) {
                            try {
                                $enrollmentDateFormatted = date('Y-m-d', strtotime($enrollmentDate));
                            } catch (Exception $e) {
                                $skipped[] = "Row " . ($i + 1) . ": Invalid date format for enrollment date (use YYYY-MM-DD)";
                                continue;
                            }
                        }
                        
                        // Check if student already exists
                        $studentCheck = $db->prepare("SELECT student_id FROM students WHERE student_id = ? OR email = ?");
                        $studentCheck->execute([$studentId, $email]);
                        
                        if ($studentCheck->rowCount() > 0) {
                            $skipped[] = "Row " . ($i + 1) . ": Student with ID $studentId or email $email already exists";
                            continue;
                        }
                        
                        // Insert into students table
                        $stmt = $db->prepare("INSERT INTO students 
                            (student_id, first_name, last_name, email, phone_number, 
                             date_of_birth, enrollment_date, current_status, batch_name,
                             father_name, father_phone_number)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        
                        if ($stmt->execute([
                            $studentId, 
                            $firstName, 
                            $lastName, 
                            $email, 
                            $phone, 
                            $dobFormatted, 
                            $enrollmentDateFormatted, 
                            $status, 
                            $batchId,
                            $fatherName,
                            $fatherPhone
                        ])) {
                            // Create user account for the student
                            $password = bin2hex(random_bytes(4)); // Generate random password
                            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                            
                            $userStmt = $db->prepare("INSERT INTO users 
                                (name, email, password_hash, role, status)
                                VALUES (?, ?, ?, 'student', 'active')");
                            
                            $fullName = $firstName . ' ' . $lastName;
                            $userStmt->execute([$fullName, $email, $passwordHash]);
                            $userId = $db->lastInsertId();
                            
                            // Update student record with user_id
                            $updateStmt = $db->prepare("UPDATE students SET user_id = ? WHERE student_id = ?");
                            $updateStmt->execute([$userId, $studentId]);
                            
                            $successCount++;
                        } else {
                            $skipped[] = "Row " . ($i + 1) . ": " . implode(" ", $stmt->errorInfo());
                        }
                    }
                    
                    $message = "Student data imported successfully. $successCount records added.";
                    if (!empty($skipped)) {
                        $message .= " Skipped rows: " . implode(', ', $skipped);
                    }
                    
                    $_SESSION['upload_message'] = $message;
                    $_SESSION['upload_success_count'] = $successCount;
                    $_SESSION['upload_skipped_rows'] = $skipped;
                    header("Location: students_list.php");
                    exit;
                    
                } catch (Exception $e) {
                    $error = "Error processing file: " . $e->getMessage();
                }
            }
        }
    } else {
        $error = "No file uploaded.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Students</title>
    <!-- Primary Tailwind CDN with fallback -->
    <link rel="stylesheet" href="../assets/css/tailwind.min.css">
    <script>
        window.Tailwind || document.write('<script src="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.js"><\/script>')
    </script>
    <!-- Font Awesome from jsDelivr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        
        .file-upload-container {
            transition: all 0.3s ease;
        }
        
        .file-upload-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .progress-bar {
            height: 6px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            border-radius: 3px;
            transition: width 0.4s ease;
        }
        
        .success-pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(74, 222, 128, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(74, 222, 128, 0);
            }
        }
        
        .file-input-label {
            transition: all 0.3s ease;
        }
        
        .file-input-label:hover {
            background-color: #f3f4f6;
        }
        
        .upload-button {
            transition: all 0.3s ease;
            transform-style: preserve-3d;
        }
        
        .upload-button:hover {
            transform: translateY(-2px) scale(1.02);
        }
        
        .upload-button:active {
            transform: translateY(1px);
        }
        
        .floating-notification {
            animation: slideIn 0.5s forwards, fadeOut 0.5s 4.5s forwards;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
        
        .file-drop-area {
            border: 2px dashed #d1d5db;
            transition: all 0.3s ease;
        }
        
        .file-drop-area.active {
            border-color: #3b82f6;
            background-color: #f0f7ff;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
<?php include '../sidebar.php'; ?>
<!-- Main Content -->
    <div class="md:ml-64">
        <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
            <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fas fa-upload text-blue-500"></i>
                <span>Upload Students</span>
            </h1>
            <div class="flex items-center space-x-4">
                <a href="students_list.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg flex items-center transition-all duration-300 hover:shadow-md">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Students
                </a>
            </div>
        </header>

        <div class="container mx-auto px-4 py-8">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6 mb-8 file-upload-container animate__animated animate__fadeIn">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Upload Student Data</h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 animate__animated animate__shakeX">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-6 p-4 border border-blue-200 bg-blue-50 rounded-lg animate__animated animate__fadeIn">
                        <h3 class="font-medium text-blue-800 mb-2 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i> Excel File Format Requirements:
                        </h3>
                        <ul class="list-disc pl-5 text-blue-700 space-y-1">
                            <li>First row should be headers (will be skipped)</li>
                            <li><strong class="text-blue-900">Required columns in order:</strong> 
                                <ol class="list-decimal pl-5 mt-1 space-y-1">
                                    <li>Student ID</li>
                                    <li>First Name</li>
                                    <li>Last Name</li>
                                    <li>Email</li>
                                    <li>Phone Number</li>
                                    <li>Date of Birth (YYYY-MM-DD)</li>
                                    <li>Batch ID</li>
                                    <li>Enrollment Date (YYYY-MM-DD)</li>
                                    <li>Current Status (active/dropped/transferred/completed)</li>
                                    <li>Father's Name</li>
                                    <li>Father's Phone Number</li>
                                </ol>
                            </li>
                            <li>Supported file formats: .xlsx, .xls</li>
                            <li class="flex items-center">
                                <i class="fas fa-file-excel text-green-600 mr-2"></i>
                                <a href="../uploads/student_template.xlsx" class="text-blue-600 hover:underline flex items-center">
                                    Download template file
                                    <i class="fas fa-download ml-1"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-4" id="uploadForm">
                        <div id="fileDropArea" class="file-drop-area rounded-lg border-2 border-dashed p-8 text-center cursor-pointer transition-all duration-300">
                            <input type="file" id="excel_file" name="excel_file" class="hidden" accept=".xlsx,.xls" required>
                            <label for="excel_file" class="file-input-label block cursor-pointer">
                                <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-file-excel text-blue-500 text-2xl"></i>
                                </div>
                                <h4 class="text-lg font-medium text-gray-700 mb-1">Drag & drop your Excel file here</h4>
                                <p class="text-gray-500 mb-3">or click to browse files</p>
                                <span class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md inline-flex items-center">
                                    <i class="fas fa-folder-open mr-2"></i> Choose file
                                </span>
                            </label>
                            <div id="fileNameDisplay" class="mt-3 text-sm text-gray-600 hidden">
                                <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                <span id="selectedFileName"></span>
                            </div>
                        </div>
                        
                        <div id="uploadProgress" class="hidden">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Uploading...</span>
                                <span id="progressPercent">0%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div id="progressBar" class="progress-bar h-2 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="upload" class="upload-button bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg flex items-center shadow-md hover:shadow-lg">
                                <i class="fas fa-upload mr-2"></i> Upload Students
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 animate__animated animate__fadeInUp">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Recent Uploads</h2>
                        <button id="refreshUploads" class="text-blue-600 hover:text-blue-800 transition-colors">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Records</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="uploadsTableBody">
                                <!-- This would be populated from a log table if implemented -->
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No upload history available
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating notification (hidden by default) -->
    <div id="successNotification" class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg floating-notification hidden">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span id="notificationMessage">Students uploaded successfully!</span>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("-translate-x-full");
            document.getElementById("sidebar").classList.toggle("md:translate-x-0");
        }
        
        // File drop area functionality
        const fileDropArea = document.getElementById('fileDropArea');
        const fileInput = document.getElementById('excel_file');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        const selectedFileName = document.getElementById('selectedFileName');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            fileDropArea.classList.add('active');
        }
        
        function unhighlight() {
            fileDropArea.classList.remove('active');
        }
        
        fileDropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length) {
                fileInput.files = files;
                updateFileNameDisplay(files[0].name);
            }
        }
        
        fileInput.addEventListener('change', function() {
            if (this.files.length) {
                updateFileNameDisplay(this.files[0].name);
            }
        });
        
        function updateFileNameDisplay(name) {
            selectedFileName.textContent = name;
            fileNameDisplay.classList.remove('hidden');
        }
        
        // Form submission with progress animation
        const uploadForm = document.getElementById('uploadForm');
        const uploadProgress = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('progressBar');
        const progressPercent = document.getElementById('progressPercent');
        
        uploadForm.addEventListener('submit', function(e) {
            // Show progress bar
            uploadProgress.classList.remove('hidden');
            
            // Simulate progress (in a real app, you'd use XMLHttpRequest with progress events)
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 10;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                }
                progressBar.style.width = progress + '%';
                progressPercent.textContent = Math.round(progress) + '%';
            }, 200);
        });
        
        // Show success notification if coming from a successful upload
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('upload_success')) {
                const notification = document.getElementById('successNotification');
                notification.classList.remove('hidden');
                setTimeout(() => {
                    notification.classList.add('hidden');
                }, 5000);
            }
        });
        
        // Refresh button animation
        const refreshButton = document.getElementById('refreshUploads');
        refreshButton.addEventListener('click', function() {
            this.classList.add('animate-spin');
            setTimeout(() => {
                this.classList.remove('animate-spin');
            }, 1000);
            // Here you would typically fetch new data via AJAX
        });
    </script>
</body>
</html>