<?php

require_once '../db_connection.php';

// Get batch ID from URL
$batch_id = isset($_GET['batch_id']) ? $_GET['batch_id'] : null;

if (!$batch_id) {
    header("Location: ../batch/batch_list.php");
    exit();
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get batch details
    $stmt = $conn->prepare("SELECT batch_id, course_name FROM batches WHERE batch_id = ?");
    $stmt->execute([$batch_id]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$batch) {
        header("Location: ../batch/batch_list.php");
        exit();
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Required fields and length limits
    $fields = [
        'first_name' => 30,
        'last_name' => 30,
        'email' => 60,
        'phone_number' => 10,
        'date_of_birth' => null,
        'father_name' => 50,
        'father_phone_number' => 10,
        'father_email' => 60,
        'current_status' => 10
    ];

    foreach ($fields as $field => $maxLength) {
        $value = trim($_POST[$field] ?? '');
        if (empty($value)) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        } elseif ($maxLength && strlen($value) > $maxLength) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " must be under $maxLength characters.";
        }
    }

    // Validate formats
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (!filter_var($_POST['father_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid father's email format.";
    }

    if (!preg_match('/^\d{10}$/', $_POST['phone_number'])) {
        $errors[] = "Phone number must be exactly 10 digits.";
    }

    if (!preg_match('/^\d{10}$/', $_POST['father_phone_number'])) {
        $errors[] = "Father's phone number must be exactly 10 digits.";
    }

    if (strtotime($_POST['date_of_birth']) >= time()) {
        $errors[] = "Date of birth must be in the past.";
    }

    // Process insert if no errors
    if (empty($errors)) {
        try {
            $student_id = 'STU' . strtoupper(substr($_POST['first_name'], 0, 1)) . strtoupper(substr($_POST['last_name'], 0, 1)) . date('YmdHis');
            $password_hash = password_hash('TempPass123', PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO students 
                (student_id, first_name, last_name, email, phone_number, 
                date_of_birth, enrollment_date, current_status, course_enrolled, 
                batch_name, dropout_date, dropout_reason, father_name, 
                father_phone_number, father_email, password_hash) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $student_id,
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['phone_number'],
                $_POST['date_of_birth'],
                date('Y-m-d'),
                $_POST['current_status'],
                $batch['course_name'],
                $batch_id,
                null,
                null,
                $_POST['father_name'],
                $_POST['father_phone_number'],
                $_POST['father_email'],
                $password_hash
            ]);

            // ✅ Close DB before redirect
            $conn = null;

            header("Location: batch_view.php?batch_id=$batch_id&success=Student added successfully");
            exit();

        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// ✅ Close DB if open
if (isset($conn)) {
    $conn = null;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student | ASD Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Back button -->
            <a href="batch_view.php?batch_id=<?= $batch_id ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4">
                <i class="fas fa-arrow-left mr-2"></i> Back to Batch
            </a>
            
            <!-- Header -->
            <div class="bg-blue-600 shadow-md rounded-lg p-6 mb-6">
                <h1 class="text-2xl font-bold text-white">Add New Student</h1>
                <p class="text-white">Batch: <?= htmlspecialchars($batch['course_name']) ?> (<?= htmlspecialchars($batch['batch_id']) ?>)</p>
            </div>
            
            <!-- Add Student Form -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Personal Information -->
                        <div class="md:col-span-2">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Personal Information</h3>
                        </div>
                        
                        <div>
                            <label for="first_name" required maxlength="30" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="last_name" required maxlength="30" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="email" required maxlength="100" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" id="email" name="email" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="phone_number"required maxlength="10" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                            <input type="tel" id="phone_number" name="phone_number" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="date_of_birth"required class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <!-- Parent/Guardian Information -->
                        <div class="md:col-span-2 mt-4">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Parent/Guardian Information</h3>
                        </div>
                        
                        <div>
                            <label for="father_name" required maxlength="50" class="block text-sm font-medium text-gray-700 mb-1">Father's Name</label>
                            <input type="text" id="father_name" name="father_name"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="father_phone_number" required maxlength="10" class="block text-sm font-medium text-gray-700 mb-1">Father's Phone</label>
                            <input type="tel" id="father_phone_number" name="father_phone_number"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="father_email" required maxlength="60" class="block text-sm font-medium text-gray-700 mb-1">Father's Email</label>
                            <input type="email" id="father_email" name="father_email"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <!-- Academic Information -->
                        <div class="md:col-span-2 mt-4">
                            <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Academic Information</h3>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Course Enrolled</label>
                            <div class="px-3 py-2 bg-gray-100 rounded-md"><?= htmlspecialchars($batch['course_name']) ?></div>
                            <input type="hidden" name="course_enrolled" value="<?= htmlspecialchars($batch['course_name']) ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Batch</label>
                            <div class="px-3 py-2 bg-gray-100 rounded-md"><?= htmlspecialchars($batch['batch_id']) ?></div>
                        </div>
                        
                        <div>
                            <label for="current_status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select id="current_status" name="current_status" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="waiting">Waiting</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <a href="batch_view.php?batch_id=<?= $batch_id ?>" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Add Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Auto-generate user ID when names are entered
        $('#first_name, #last_name').on('blur', function() {
            if($('#first_name').val() && $('#last_name').val()) {
                const userId = $('#first_name').val().toLowerCase() + '.' + $('#last_name').val().toLowerCase();
                // You can display this or store it in a hidden field if needed
                console.log('Generated User ID:', userId);
            }
        });
    });
    </script>
    <script>
$(document).ready(function () {
    $("form").on("submit", function (e) {
        const emailPattern = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
        const phonePattern = /^[0-9]{10}$/;
        let valid = true;
        let messages = [];

        const firstName = $("#first_name").val().trim();
        const lastName = $("#last_name").val().trim();
        const email = $("#email").val().trim();
        const phone = $("#phone_number").val().trim();
        const dob = $("#date_of_birth").val();
        const fName = $("#father_name").val().trim();
        const fPhone = $("#father_phone_number").val().trim();
        const fEmail = $("#father_email").val().trim();

        // Required field check
        if (!firstName || !lastName || !email || !phone || !dob || !fName || !fPhone || !fEmail) {
            messages.push("All fields are required.");
            valid = false;
        }

        // Length checks
        if (firstName.length > 30 || lastName.length > 30) {
            messages.push("First and Last names must be under 30 characters.");
            valid = false;
        }

        if (email.length > 60 || !emailPattern.test(email)) {
            messages.push("Enter a valid email under 60 characters.");
            valid = false;
        }

        if (!phonePattern.test(phone)) {
            messages.push("Phone number must be exactly 10 digits.");
            valid = false;
        }

        if (fName.length > 50) {
            messages.push("Father's name must be under 50 characters.");
            valid = false;
        }

        if (!phonePattern.test(fPhone)) {
            messages.push("Father's phone must be exactly 10 digits.");
            valid = false;
        }

        if (fEmail.length > 60 || !emailPattern.test(fEmail)) {
            messages.push("Enter a valid father's email under 60 characters.");
            valid = false;
        }

        // DOB in the past
        if (dob) {
            const dobDate = new Date(dob);
            if (dobDate >= new Date()) {
                messages.push("Date of birth must be in the past.");
                valid = false;
            }
        }

        if (!valid) {
            e.preventDefault();
            alert(messages.join("\n"));
        }
    });
});
</script>


</body>
</html>