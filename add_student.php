<?php
require_once '../db_connection.php';
function generateStudentId(PDO $db): string {
    $stmt = $db->query("SELECT MAX(CAST(SUBSTRING(student_id, 4) AS UNSIGNED)) AS max_id FROM students");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $nextNumber = ($result && $result['max_id']) ? ((int)$result['max_id'] + 1) : 1;
    return 'STD' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
}


try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $generatedStudentId = generateStudentId($conn);
} catch (PDOException $e) {
    die("Something went wrong. Please try again later.");
}

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
        'password' => 255
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

    try {
        if (empty($errors)) {
            $student_id = $_POST['student_id'];
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

      $stmt = $conn->prepare("INSERT INTO students 
    (student_id, first_name, last_name, email, phone_number, 
    date_of_birth, enrollment_date, current_status, 
    dropout_date, dropout_reason, father_name, 
    father_phone_number, father_email, password_hash) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?)");


            $stmt->execute([
                $student_id,
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['phone_number'],
                $_POST['date_of_birth'],
                date('Y-m-d'),
                $_POST['current_status'],
                $_POST['father_name'],
                $_POST['father_phone_number'],
                $_POST['father_email'],
                $password_hash
            ]);

            $conn = null;
           header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
exit();

            exit();
        } else {
            $error = implode('<br>', $errors);
        }
    } catch (PDOException $e) {
     $error = "Something went wrong. Please try again later.";


    } finally {
        if (isset($conn)) {
            $conn = null;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Student</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
<h1 class="text-2xl font-bold mb-4 bg-blue-600 text-white p-3 rounded">Add New Student</h1>

<?php if (isset($error)): ?>
    <div class="bg-red-100 text-red-700 p-4 rounded mb-4"><?= $error ?></div>
<?php endif; ?>

<?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
    <div class="bg-green-100 text-green-800 p-4 rounded mb-4">
        ✅ Student added successfully.
    </div>
<?php endif; ?>



<form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block">Student ID</label>
        <input type="text" name="student_id" class="w-full border p-2 rounded bg-gray-100" readonly value="<?= htmlspecialchars($generatedStudentId) ?>">
    </div>
    <div>
        <label class="block">First Name *</label>
        <input type="text" name="first_name" class="w-full border p-2 rounded" required maxlength="30">
    </div>
    <div>
        <label class="block">Last Name *</label>
        <input type="text" name="last_name" class="w-full border p-2 rounded" required maxlength="30">
    </div>
    <div>
        <label class="block">Email *</label>
        <input type="email" name="email" class="w-full border p-2 rounded" required maxlength="60">
    </div>
    <div>
        <label class="block">Phone Number *</label>
        <input type="tel" name="phone_number" class="w-full border p-2 rounded" required maxlength="10">
    </div>
    <div>
        <label class="block">Date of Birth *</label>
        <input type="date" name="date_of_birth" class="w-full border p-2 rounded" required>
    </div>
    <div>
        <label class="block">Father's Name *</label>
        <input type="text" name="father_name" class="w-full border p-2 rounded" required maxlength="50">
    </div>
    <div>
        <label class="block">Father's Phone Number *</label>
        <input type="tel" name="father_phone_number" class="w-full border p-2 rounded" required maxlength="10">
    </div>
    <div>
        <label class="block">Father's Email *</label>
        <input type="email" name="father_email" class="w-full border p-2 rounded" required maxlength="60">
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
        <label class="block">Password *</label>
        <input type="password" name="password" class="w-full border p-2 rounded" required maxlength="255">
    </div>
    <div class="col-span-2 flex justify-end">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">Add Student</button>
    </div>
</form>

</div>
</body>
</html>
