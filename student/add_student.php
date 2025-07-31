<?php
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    // Prepare student data
    $studentData = [
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'email' => $_POST['email'],
        'phone_number' => $_POST['phone_number'] ?? null,
        'date_of_birth' => $_POST['date_of_birth'] ?? null,
        'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
        'father_name' => $_POST['father_name'] ?? null,
        'father_phone_number' => $_POST['father_phone_number'] ?? null,
        'father_email' => $_POST['father_email'] ?? null,
        'profile_picture' => null
    ];

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/profile_pictures/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
        $new_filename = "student_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                $studentData['profile_picture'] = $target_file;
            }
        }
    }
    
    // Validate data
    $errors = validateStudentData($studentData);
    
    if (empty($errors)) {
        if (createStudent($db, $studentData)) {
            // Send welcome email to student
            $emailSent = sendWelcomeEmail($studentData);
            
            // Redirect to dashboard with success message
            $redirectParams = ['success' => 'student_created'];
            if (!$emailSent) {
                $redirectParams['email_status'] = 'failed';
            }
            header("Location: ../dashboard/dashboard.php?" . http_build_query($redirectParams));
            exit();
        } else {
            $errors[] = 'Failed to create student. Please try again.';
        }
    }
}

function sendWelcomeEmail(array $studentData): bool {
    $to = $studentData['email'];
    $subject = 'Welcome to Our Institution';
    
    $message = "
    <html>
    <head>
        <title>Welcome to Our Institution</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .header { background-color: #3b82f6; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { background-color: #f3f4f6; padding: 10px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Welcome to Our Institution</h1>
        </div>
        <div class='content'>
            <p>Dear {$studentData['first_name']} {$studentData['last_name']},</p>
            <p>We are pleased to inform you that your student account has been successfully created.</p>
            <p>Here are your login details:</p>
            <ul>
                <li><strong>Email:</strong> {$studentData['email']}</li>
                <li><strong>Password:</strong> The password you provided during registration</li>
            </ul>
            <p>Please keep this information secure and do not share it with anyone.</p>
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
            <p>Best regards,<br>The Administration Team</p>
        </div>
        <div class='footer'>
            <p>This is an automated message. Please do not reply directly to this email.</p>
        </div>
    </body>
    </html>
    ";
    
    // Always set content-type when sending HTML email
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    
    // Additional headers
    $headers .= "From: Your Institution <noreply@yourinstitution.com>\r\n";
    $headers .= "Reply-To: support@yourinstitution.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    try {
        return mail($to, $subject, $message, $headers);
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

function generateStudentId(PDO $db): string {
    $lastStudent = $db->query("SELECT student_id FROM students ORDER BY student_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $nextStudentId = 'STD001'; // Default if no students exist

    if ($lastStudent) {
        // Extract the numeric part and increment
        $lastNumber = (int) substr($lastStudent['student_id'], 3);
        $nextNumber = $lastNumber + 1;
        $nextStudentId = 'STD' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    return $nextStudentId;
}

function createStudent(PDO $db, array $studentData): bool {
    try {
        // Start transaction
        $db->beginTransaction();
        
        // First create user
        $userStmt = $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'student')");
        $userStmt->execute([
            $studentData['first_name'] . ' ' . $studentData['last_name'],
            $studentData['email'],
            $studentData['password']
        ]);
        
        $userId = $db->lastInsertId();
        
        // Generate student ID
        $studentId = generateStudentId($db);
        
        // Create student record (now including password_hash)
        $studentStmt = $db->prepare("INSERT INTO students (
            student_id, user_id, first_name, last_name, email, phone_number, 
            date_of_birth, enrollment_date, current_status, password_hash,
            father_name, father_phone_number, father_email, profile_picture
        ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), 'active', ?, ?, ?, ?, ?)");
        
        $result = $studentStmt->execute([
            $studentId,
            $userId,
            $studentData['first_name'],
            $studentData['last_name'],
            $studentData['email'],
            $studentData['phone_number'],
            $studentData['date_of_birth'],
            $studentData['password'], // Insert the hashed password here
            $studentData['father_name'],
            $studentData['father_phone_number'],
            $studentData['father_email'],
            $studentData['profile_picture']
        ]);
        
        $db->commit();
        return $result;
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Error creating student: " . $e->getMessage());
        return false;
    }
}

function validateStudentData(array $data): array {
    $errors = [];
    
    if (empty($data['first_name'])) {
        $errors[] = 'First name is required';
    }
    
    if (empty($data['last_name'])) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($_POST['password'])) {
        $errors[] = 'Password is required';
    } elseif (strlen($_POST['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    return $errors;
}

$nextStudentId = generateStudentId($db);
?>
<?php
include '../header.php'; // Include your header file
include '../sidebar.php'; // Include your sidebar file
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Student</title>
    <link rel="stylesheet" href="../assets/css/tailwind.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6 max-w-4xl">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New Student</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form id="studentForm" method="post" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="add_student" value="1">
                
                <!-- Profile Picture Section -->
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <div class="w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-user text-4xl text-blue-500"></i>
                        </div>
                        <label for="profile_picture" class="absolute bottom-0 right-0 bg-blue-500 text-white rounded-full p-2 cursor-pointer hover:bg-blue-600">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="profile_picture" name="profile_picture" class="hidden" accept="image/*">
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Add profile picture</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Student ID -->
                    <div>
                        <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">Student ID</label>
                        <input type="text" id="student_id" name="student_id" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100"
                               value="<?= htmlspecialchars($nextStudentId) ?>" readonly>
                    </div>
                    
                    <!-- First Name -->
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name*</label>
                        <input type="text" id="first_name" name="first_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Last Name -->
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name*</label>
                        <input type="text" id="last_name" name="last_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>
                    
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email*</label>
                        <input type="email" id="email" name="email" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password*</label>
                        <input type="password" id="password" name="password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>
                    
                    <!-- Phone Number -->
                    <div>
                        <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Date of Birth -->
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Parent/Guardian Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Father's Name -->
                        <div>
                            <label for="father_name" class="block text-sm font-medium text-gray-700 mb-1">Father's Name</label>
                            <input type="text" id="father_name" name="father_name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <!-- Father's Phone -->
                        <div>
                            <label for="father_phone_number" class="block text-sm font-medium text-gray-700 mb-1">Father's Phone</label>
                            <input type="tel" id="father_phone_number" name="father_phone_number" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <!-- Father's Email -->
                        <div>
                            <label for="father_email" class="block text-sm font-medium text-gray-700 mb-1">Father's Email</label>
                            <input type="email" id="father_email" name="father_email" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-4 pt-4">
                    <a href="students.php" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Create Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>