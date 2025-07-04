<?php
require_once '../db_connection.php';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all batches for dropdown
    $batchStmt = $conn->query("SELECT batch_id, course_name FROM batches ORDER BY course_name");
    $batches = $batchStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    $fields = [
        'first_name' => 30,
        'last_name' => 30,
        'email' => 60,
        'phone_number' => 10,
        'date_of_birth' => null,
        'father_name' => 50,
        'father_phone_number' => 10,
        'father_email' => 60,
        'current_status' => 10,
        'batch_name' => 20
    ];

    foreach ($fields as $field => $maxLength) {
        $value = trim($_POST[$field] ?? '');
        if (empty($value)) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        } elseif ($maxLength && strlen($value) > $maxLength) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " must be under $maxLength characters.";
        }
    }

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

    if (empty($errors)) {
        try {
            $batch_id = $_POST['batch_name'];
            $batchQuery = $conn->prepare("SELECT course_name FROM batches WHERE batch_id = ?");
            $batchQuery->execute([$batch_id]);
            $batchData = $batchQuery->fetch(PDO::FETCH_ASSOC);

            if (!$batchData) {
                $errors[] = "Invalid batch selected.";
            } else {
                $student_id = 'STU' . strtoupper(substr($_POST['first_name'], 0, 1)) . strtoupper(substr($_POST['last_name'], 0, 1)) . date('YmdHis');
                $password_hash = password_hash('TempPass123', PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO students 
                    (student_id, first_name, last_name, email, phone_number, 
                    date_of_birth, enrollment_date, current_status, course_enrolled, 
                    batch_name, dropout_date, dropout_reason, father_name, 
                    father_phone_number, father_email, password_hash) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?)");

                $stmt->execute([
                    $student_id,
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['email'],
                    $_POST['phone_number'],
                    $_POST['date_of_birth'],
                    date('Y-m-d'),
                    $_POST['current_status'],
                    $batchData['course_name'],
                    $batch_id,
                    $_POST['father_name'],
                    $_POST['father_phone_number'],
                    $_POST['father_email'],
                    $password_hash
                ]);

               $conn = null;
$success = "Student added successfully.";
            }
       } catch (PDOException $e) {
  $error = "Something went wrong while saving the student details. Please try again later.";
} finally {
    $conn = null; // Always close connection
}
    }}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Student</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto">
        <!-- Blue Header -->
        <div class="bg-blue-600 text-white text-2xl font-bold p-4 rounded-t">
            Add New Student
        </div>

        <!-- White Form Container -->
        <div class="bg-white p-6 rounded-b shadow">
            <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?= $error ?></div>
            <?php endif; ?>
<?php if (isset($success)): ?>
    <div class="bg-green-100 text-green-700 p-4 rounded mb-4"><?= $success ?></div>
<?php endif; ?>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block">First Name *</label>
                    <input type="text" name="first_name" maxlength="30" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed" class="w-full border p-2 rounded" required>
                </div>
                <div>
                    <label class="block">Last Name *</label>
                    <input type="text" name="last_name" maxlength="30" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed" class="w-full border p-2 rounded" required>
                </div>
                <div>
                    <label class="block">Email *</label>
                    <input type="email" name="email" maxlength="60" class="w-full border p-2 rounded" required>
                </div>
                <div>
                    <label class="block">Phone Number *</label>
                    <input type="tel" name="phone_number" maxlength="10" pattern="\d{10}" title="Enter exactly 10 digits" class="w-full border p-2 rounded" required>
                </div>
                <div>
                    <label class="block">Date of Birth *</label>
                    <input type="date" name="date_of_birth" class="w-full border p-2 rounded" required>
                </div>
                <div>
                    <label class="block">Status *</label>
                    <select name="current_status" class="w-full border p-2 rounded" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="waiting">Waiting</option>
                    </select>
                </div>
                <div>
                    <label class="block">Batch *</label>
                    <select name="batch_name" class="w-full border p-2 rounded" required>
                        <option value="">Select a batch</option>
                        <?php foreach ($batches as $batch): ?>
                            <option value="<?= htmlspecialchars($batch['batch_id']) ?>">
                                <?= htmlspecialchars($batch['course_name'] . " (" . $batch['batch_id'] . ")") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Father Details Section -->
                <div class="col-span-2 mt-6">
                    <h2 class="text-xl font-semibold mb-2 text-gray-700">Father Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block">Father's Name *</label>
                            <input type="text" name="father_name" maxlength="50" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed" class="w-full border p-2 rounded" required>
                        </div>
                        <div>
                            <label class="block">Father's Phone Number *</label>
                            <input type="tel" name="father_phone_number" maxlength="10" pattern="\d{10}" title="Enter exactly 10 digits" class="w-full border p-2 rounded" required>
                        </div>
                        <div>
                            <label class="block">Father's Email *</label>
                            <input type="email" name="father_email" maxlength="60" class="w-full border p-2 rounded" required>
                        </div>
                    </div>
                </div>

                <div class="col-span-2 flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
         
