<?php
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$student_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$student_id) {
    header("Location: ../student/student_list.php");
    exit();
}

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get student details with profile picture
    $stmt = $db->prepare("
        SELECT s.*, u.password_hash 
        FROM students s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header("Location: ../student/student_list.php");
        exit();
    }
    
    // Get all batches for dropdown
    $stmt = $db->prepare("SELECT batch_id, course_name FROM batches ORDER BY start_date DESC");
    $stmt->execute();
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $phone_number = $_POST['phone_number'];
        $date_of_birth = $_POST['date_of_birth'];
        $current_status = $_POST['current_status'];
        $batch_name = $_POST['batch_name'];
        $father_name = $_POST['father_name'];
        $father_phone = $_POST['father_phone'];
        $father_email = $_POST['father_email'];
        
        // Handle password change if provided
        $password_changed = false;
        if (!empty($_POST['new_password'])) {
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $password_changed = true;
        }
        
        // Handle dropout fields if status is dropped
        $dropout_date = null;
        $dropout_reason = null;
        
        if ($current_status === 'dropped') {
            $dropout_date = $_POST['dropout_date'] ?? date('Y-m-d');
            $dropout_reason = $_POST['dropout_reason'] ?? '';
        }
        
        // Handle profile picture upload
        $profile_picture = $student['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../uploads/profile_pictures/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
            $new_filename = "student_" . $student_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // Check if image file is a actual image
            $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
            if ($check !== false) {
                // Only proceed if file is an image
                if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                    // Delete old profile picture if exists
                    if (!empty($profile_picture) && file_exists($profile_picture)) {
                        unlink($profile_picture);
                    }
                    $profile_picture = $target_file;
                }
            }
        }
        
        // Update student record
        $stmt = $db->prepare("UPDATE students SET 
                              first_name = ?, 
                              last_name = ?, 
                              email = ?, 
                              phone_number = ?, 
                              date_of_birth = ?, 
                              current_status = ?, 
                              batch_name = ?, 
                              father_name = ?, 
                              father_phone_number = ?, 
                              father_email = ?,
                              dropout_date = ?,
                              dropout_reason = ?
                              WHERE student_id = ?");
        
        $stmt->execute([
            $first_name,
            $last_name,
            $email,
            $phone_number,
            $date_of_birth,
            $current_status,
            $batch_name,
            $father_name,
            $father_phone,
            $father_email,
            $dropout_date,
            $dropout_reason,
            $student_id
        ]);
        
        // Update user password if changed
        if ($password_changed && !empty($student['user_id'])) {
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$new_password, $student['user_id']]);
        }
        
        // Update user profile picture if changed
        if ($profile_picture !== $student['profile_picture']) {
            $stmt = $db->prepare("UPDATE students SET profile_picture = ? WHERE student_id = ?");
            $stmt->execute([$profile_picture, $student['student_id']]);
        }
        
        // Update attendance records if batch changed
        if ($batch_name !== $student['batch_name']) {
            $stmt = $db->prepare("UPDATE attendance SET batch_id = ? WHERE student_name = ? AND batch_id = ?");
            $stmt->execute([$batch_name, $student['first_name'] . ' ' . $student['last_name'], $student['batch_name']]);
        }
        
        // Redirect to student view
        header("Location: student_view.php?id=$student_id");
        exit();
    }
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student | ASD Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="max-w-4xl mx-auto p-4">
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-blue-600 p-4 text-white">
                <div class="flex items-center">
                    <a href="student_view.php?id=<?= $student_id ?>" class="mr-4 text-white">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold">Edit Student</h1>
                </div>
            </div>
            
            <!-- Form -->
            <form method="POST" enctype="multipart/form-data" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Profile Picture Section -->
                    <div class="md:col-span-2">
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                                    <img src="<?= htmlspecialchars($student['profile_picture']) ?>" 
                                         alt="Profile Picture" 
                                         class="w-24 h-24 rounded-full object-cover border-4 border-blue-100">
                                <?php else: ?>
                                    <div class="w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center">
                                        <i class="fas fa-user text-4xl text-blue-500"></i>
                                    </div>
                                <?php endif; ?>
                                <label for="profile_picture" class="absolute bottom-0 right-0 bg-blue-500 text-white rounded-full p-2 cursor-pointer hover:bg-blue-600">
                                    <i class="fas fa-camera"></i>
                                </label>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Change profile picture</p>
                                <input type="file" id="profile_picture" name="profile_picture" class="hidden" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-medium text-gray-900 border-b pb-2">Personal Information</h2>
                        
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($student['first_name']) ?>" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($student['last_name']) ?>" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <input type="tel" id="phone_number" name="phone_number" value="<?= htmlspecialchars($student['phone_number']) ?>" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="date_of_birth" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($student['date_of_birth']) ?>" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
                    <!-- Academic Information -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-medium text-gray-900 border-b pb-2">Academic Information</h2>
                        
                        <div>
                            <label for="current_status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="current_status" name="current_status" 
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="active" <?= $student['current_status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="dropped" <?= $student['current_status'] === 'dropped' ? 'selected' : '' ?>>Dropped</option>
                                <option value="on_hold" <?= $student['current_status'] === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                            </select>
                        </div>
                        
                        <div id="dropoutFields" style="<?= $student['current_status'] !== 'dropped' ? 'display: none;' : '' ?>">
                            <div class="mb-4">
                                <label for="dropout_date" class="block text-sm font-medium text-gray-700">Dropout Date</label>
                                <input type="date" id="dropout_date" name="dropout_date" 
                                       value="<?= $student['dropout_date'] ? htmlspecialchars($student['dropout_date']) : date('Y-m-d') ?>" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="dropout_reason" class="block text-sm font-medium text-gray-700">Dropout Reason</label>
                                <textarea id="dropout_reason" name="dropout_reason" rows="3" 
                                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($student['dropout_reason'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div>
                            <label for="batch_name" class="block text-sm font-medium text-gray-700">Batch</label>
                            <select id="batch_name" name="batch_name" 
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <?php foreach ($batches as $batch): ?>
                                    <option value="<?= htmlspecialchars($batch['batch_id']) ?>" <?= $batch['batch_id'] === $student['batch_name'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($batch['batch_id']) ?> - <?= htmlspecialchars($batch['course_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Password Change Section -->
                        <div class="mt-4">
                            <h2 class="text-lg font-medium text-gray-900 border-b pb-2">Change Password</h2>
                            <div class="mt-2">
                                <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                <div class="relative mt-1">
                                    <input type="password" id="new_password" name="new_password" 
                                           class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <button type="button" onclick="togglePassword('new_password')" 
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Leave blank to keep current password</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Parent Information -->
                    <div class="space-y-4 md:col-span-2">
                        <h2 class="text-lg font-medium text-gray-900 border-b pb-2">Parent/Guardian Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="father_name" class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" id="father_name" name="father_name" value="<?= htmlspecialchars($student['father_name'] ?? '') ?>" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label for="father_phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="tel" id="father_phone" name="father_phone" value="<?= htmlspecialchars($student['father_phone_number'] ?? '') ?>" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label for="father_email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="father_email" name="father_email" value="<?= htmlspecialchars($student['father_email'] ?? '') ?>" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="mt-8 flex justify-end space-x-3">
                    <a href="student_view.php?id=<?= $student_id ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show/hide dropout fields based on status
        document.getElementById('current_status').addEventListener('change', function() {
            const dropoutFields = document.getElementById('dropoutFields');
            if (this.value === 'dropped') {
                dropoutFields.style.display = 'block';
            } else {
                dropoutFields.style.display = 'none';
            }
        });

        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>