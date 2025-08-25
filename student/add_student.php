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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Student</title>
    <link rel="stylesheet" href="../assets/css/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        }
        
        .form-container {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }
        
        .form-container:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .profile-avatar {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .profile-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .input-field {
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary {
            transition: all 0.3s ease;
            background-image: linear-gradient(to right, #3b82f6, #6366f1);
            background-size: 200% auto;
        }
        
        .btn-primary:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .floating-label {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
            transition: all 0.2s ease;
            transform-origin: left top;
            color: #6b7280;
        }
        
        .input-container {
            position: relative;
            padding-top: 1.5rem;
        }
        
        .input-container input:focus + .floating-label,
        .input-container input:not(:placeholder-shown) + .floating-label {
            transform: translateY(-0.5rem) scale(0.85);
            color: #3b82f6;
        }
        
        .section-title {
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(to right, #3b82f6, #6366f1);
            border-radius: 3px;
        }
        
        .success-message {
            animation: fadeInUp 0.5s ease;
        }
        
        .error-message {
            animation: shake 0.5s ease;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>
    
    <div class="ml-64 p-6">
        <div class="container mx-auto max-w-5xl animate__animated animate__fadeIn">
            <div class="bg-white rounded-xl form-container overflow-hidden p-8">
                <!-- Header with gradient background -->
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-6 rounded-t-xl -mx-8 -mt-8 mb-8">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold">Add New Student</h2>
                            <p class="text-blue-100">Fill in the student details below</p>
                        </div>
                        <div class="flex space-x-3">
                            <a href="upload_student.php" class="bg-white text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg flex items-center transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-file-import mr-2"></i> Upload Excel
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded error-message" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium">There were errors with your submission</h3>
                                <ul class="mt-2 text-sm list-disc list-inside">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form id="studentForm" method="post" enctype="multipart/form-data" class="space-y-8">
                    <input type="hidden" name="add_student" value="1">
                    
                    <!-- Profile Picture Section -->
                    <div class="flex items-center space-x-6">
                        <div class="relative group">
                            <div class="w-32 h-32 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center overflow-hidden profile-avatar">
                                <img id="profilePreview" class="hidden w-full h-full object-cover" src="" alt="Profile Preview">
                                <i class="fas fa-user-graduate text-5xl text-blue-500" id="defaultAvatar"></i>
                            </div>
                            <label for="profile_picture" class="absolute bottom-0 right-0 bg-blue-600 text-white rounded-full p-3 cursor-pointer hover:bg-blue-700 transition-all duration-300 transform hover:scale-110 shadow-lg group-hover:shadow-xl">
                                <i class="fas fa-camera"></i>
                                <input type="file" id="profile_picture" name="profile_picture" class="hidden" accept="image/*" onchange="previewImage(this)">
                            </label>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-800">Profile Photo</h3>
                            <p class="text-sm text-gray-500 mt-1">Upload a clear photo of the student (JPEG, PNG)</p>
                            <p class="text-xs text-gray-400 mt-2">Max. file size: 5MB</p>
                        </div>
                    </div>
                    
                    <!-- Student Information Section -->
                    <div class="space-y-6">
                        <h3 class="text-xl font-semibold text-gray-800 section-title">Student Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Student ID -->
                            <div class="input-container">
                                <input type="text" id="student_id" name="student_id" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field bg-gray-50"
                                       value="<?= htmlspecialchars($nextStudentId) ?>" readonly
                                       placeholder=" ">
                                <label for="student_id" class="floating-label">Student ID</label>
                            </div>
                            
                            <!-- First Name -->
                            <div class="input-container">
                                <input type="text" id="first_name" name="first_name" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field"
                                       required placeholder=" ">
                                <label for="first_name" class="floating-label">First Name*</label>
                            </div>
                            
                            <!-- Last Name -->
                            <div class="input-container">
                                <input type="text" id="last_name" name="last_name" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field"
                                       required placeholder=" ">
                                <label for="last_name" class="floating-label">Last Name*</label>
                            </div>
                            
                            <!-- Email -->
                            <div class="input-container">
                                <input type="email" id="email" name="email" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field"
                                       required placeholder=" ">
                                <label for="email" class="floating-label">Email*</label>
                            </div>
                            
                            <!-- Password -->
                            <div class="input-container">
                                <input type="password" id="password" name="password" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field"
                                       required placeholder=" ">
                                <label for="password" class="floating-label">Password*</label>
                                <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                    <i class="far fa-eye-slash text-gray-400 cursor-pointer" id="togglePassword"></i>
                                </div>
                            </div>
                            
                            <!-- Phone Number -->
                            <div class="input-container">
                                <input type="tel" id="phone_number" name="phone_number" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field"
                                       placeholder=" ">
                                <label for="phone_number" class="floating-label">Phone Number</label>
                            </div>
                            
                            <!-- Date of Birth -->
                            <div class="input-container">
                                <input type="date" id="date_of_birth" name="date_of_birth" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field"
                                       placeholder=" ">
                                <label for="date_of_birth" class="floating-label">Date of Birth</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Parent/Guardian Information Section -->
                    <div class="space-y-6 pt-6">
                        <h3 class="text-xl font-semibold text-gray-800 section-title">Parent/Guardian Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Father's Name -->
                            <div class="input-container">
                                <input type="text" id="father_name" name="father_name" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field"
                                       placeholder=" ">
                                <label for="father_name" class="floating-label">Father's Name</label>
                            </div>
                            
                            <!-- Father's Phone -->
                            <div class="input-container">
                                <input type="tel" id="father_phone_number" name="father_phone_number" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field"
                                       placeholder=" ">
                                <label for="father_phone_number" class="floating-label">Father's Phone</label>
                            </div>
                            
                            <!-- Father's Email -->
                            <div class="input-container md:col-span-2">
                                <input type="email" id="father_email" name="father_email" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg input-field"
                                       placeholder=" ">
                                <label for="father_email" class="floating-label">Father's Email</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-4 pt-8 border-t border-gray-200">
                        <a href="students_list.php" class="px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 btn-secondary">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </a>
                        <button type="submit" 
                                class="px-6 py-3 rounded-lg text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 btn-primary pulse-animation">
                            <i class="fas fa-user-plus mr-2"></i> Create Student
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Floating notification for success -->
            <div id="successNotification" class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg hidden transform transition-all duration-300 translate-y-4 opacity-0">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>Student created successfully!</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Profile picture preview
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#profilePreview').attr('src', e.target.result).removeClass('hidden');
                    $('#defaultAvatar').addClass('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Toggle password visibility
        $('#togglePassword').click(function() {
            const passwordField = $('#password');
            const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
            passwordField.attr('type', type);
            $(this).toggleClass('fa-eye-slash fa-eye');
        });
        
        // Form submission animation
        $('#studentForm').submit(function(e) {
            const form = $(this);
            if (form[0].checkValidity()) {
                $('button[type="submit"]').html('<i class="fas fa-spinner fa-spin mr-2"></i> Processing...').prop('disabled', true);
                
                // Simulate success notification (in a real app, this would be after AJAX success)
                setTimeout(() => {
                    $('#successNotification').removeClass('hidden').removeClass('translate-y-4').removeClass('opacity-0').addClass('translate-y-0').addClass('opacity-100');
                    
                    setTimeout(() => {
                        $('#successNotification').addClass('translate-y-4').addClass('opacity-0');
                    }, 3000);
                }, 1500);
            }
        });
        
        // Floating label functionality
        $('.input-container input').each(function() {
            if ($(this).val() !== '') {
                $(this).next('.floating-label').addClass('transformed');
            }
        });
        
        $('.input-container input').on('input change', function() {
            if ($(this).val() !== '') {
                $(this).next('.floating-label').addClass('transformed');
            } else {
                $(this).next('.floating-label').removeClass('transformed');
            }
        });
        
        // Animate form elements on load
        $(document).ready(function() {
            $('.input-container').each(function(index) {
                $(this).delay(100 * index).queue(function() {
                    $(this).addClass('animate__animated animate__fadeInUp').dequeue();
                });
            });
        });
    </script>
</body>
</html>