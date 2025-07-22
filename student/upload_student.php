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
                    
                    $successCount = 0;
                    $skipped = [];
                    
                    // Start from row 1 (assuming first row is headers)
                    for ($i = 1; $i < count($data); $i++) {
                        $row = $data[$i];
                        
                        // Validate row has enough columns (adjust as needed)
                        if (count($row) < 8) {
                            $skipped[] = "Row " . ($i + 1) . ": Insufficient data columns";
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
                        $status = trim($row[7] ?? 'active');
                        
                        // Validate required fields
                        if (empty($studentId)) {
                            $skipped[] = "Row " . ($i + 1) . ": Student ID is required";
                            continue;
                        }
                        
                        if (empty($firstName)) {
                            $skipped[] = "Row " . ($i + 1) . ": First name is required";
                            continue;
                        }
                        
                        if (empty($lastName)) {
                            $skipped[] = "Row " . ($i + 1) . ": Last name is required";
                            continue;
                        }
                        
                        if (empty($email)) {
                            $skipped[] = "Row " . ($i + 1) . ": Email is required";
                            continue;
                        }
                        
                        // Validate email format
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $skipped[] = "Row " . ($i + 1) . ": Invalid email format";
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
                        if (!in_array(strtolower($status), ['active', 'dropped', 'transferred', 'completed'])) {
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
                             date_of_birth, enrollment_date, current_status, batch_name)
                            VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)");
                        
                        if ($stmt->execute([
                            $studentId, 
                            $firstName, 
                            $lastName, 
                            $email, 
                            $phone, 
                            $dobFormatted, 
                            $status, 
                            $batchId
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
 
    
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
                <a href="students_list.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Students
                </a>
            </div>
        </header>

        <div class="container mx-auto px-4 py-8">
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Upload Student Data</h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-6 p-4 border border-blue-200 bg-blue-50 rounded-lg">
                        <h3 class="font-medium text-blue-800 mb-2">Excel File Format Requirements:</h3>
                        <ul class="list-disc pl-5 text-blue-700">
                            <li>First row should be headers (will be skipped)</li>
                            <li>Required columns in order: Student ID, First Name, Last Name, Email, Phone, Date of Birth (YYYY-MM-DD), Batch ID, Status</li>
                            <li>Supported file formats: .xlsx, .xls</li>
                            <li><a href="../uploads/student_template.xlsx" class="text-blue-600 hover:underline">Download template file</a></li>
                        </ul>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label for="excel_file" class="block text-sm font-medium text-gray-700 mb-1">Excel File</label>
                            <input type="file" id="excel_file" name="excel_file" 
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   accept=".xlsx,.xls" required>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="upload" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                                <i class="fas fa-upload mr-2"></i> Upload Students
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Recent Uploads</h2>
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
                            <tbody class="bg-white divide-y divide-gray-200">
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

    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("-translate-x-full");
            document.getElementById("sidebar").classList.toggle("md:translate-x-0");
        }
    </script>
</body>
</html>