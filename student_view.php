<?php
require_once '../db_connection.php';

$student_id = isset($_GET['id']) ? $_GET['id'] : null;
$from_batch = isset($_GET['from_batch']) ? $_GET['from_batch'] : null;

if (!$student_id) {
    header("Location: ../batch_list.php");
    exit();
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get student details
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header("Location: ../batch_list.php");
        exit();
    }
    
    // Get batch details
    $stmt = $conn->prepare("SELECT * FROM batches WHERE batch_id = ?");
    $stmt->execute([$student['batch_name']]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get attendance records
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE student_name = ? AND batch_id = ? ORDER BY date DESC");
    $stmt->execute([$student['first_name'] . ' ' . $student['last_name'], $student['batch_name']]);
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate attendance stats
    $total_classes = count($attendance);
    $present_count = 0;
    $absent_count = 0;
    $late_count = 0;
    
    foreach ($attendance as $record) {
        if ($record['status'] === 'Present') $present_count++;
        elseif ($record['status'] === 'Absent') $absent_count++;
        elseif ($record['status'] === 'Late') $late_count++;
    }
    
    // Get exam results
    $stmt = $conn->prepare("SELECT pe.exam_id, pe.exam_date, pe.mode, es.score, es.is_malpractice 
                          FROM proctored_exams pe
                          JOIN exam_students es ON pe.exam_id = es.exam_id
                          WHERE es.student_name = ? AND pe.batch_id = ?
                          ORDER BY pe.exam_date DESC");
    $stmt->execute([$student['first_name'] . ' ' . $student['last_name'], $student['batch_name']]);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?> | ASD Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="max-w-4xl mx-auto">
        <!-- Header with back button -->
        <div class="bg-white shadow-sm sticky top-0 z-10 p-4 flex items-center">
            <?php if ($from_batch && $student['batch_name']): ?>
                <a href="batch_view.php?batch_id=<?= $student['batch_name'] ?>" class="mr-4">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
            <?php else: ?>
                <a href="students_list.php" class="mr-4">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
            <?php endif; ?>
            <h1 class="text-xl font-bold">Student Profile</h1>
        </div>
        
        <!-- Profile Section -->
        <div class="bg-white shadow rounded-lg mb-4">
            <div class="p-6">
                <div class="flex flex-col md:flex-row items-center">
                    <!-- Profile Picture -->
                    <div class="w-32 h-32 rounded-full bg-blue-100 flex items-center justify-center mb-4 md:mb-0 md:mr-6">
                        <i class="fas fa-user text-5xl text-blue-500"></i>
                    </div>
                    
                    <!-- Profile Info -->
                    <div class="flex-1 text-center md:text-left">
                        <h2 class="text-2xl font-bold"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h2>
                        <p class="text-gray-600 mb-2"><?= htmlspecialchars($student['student_id']) ?></p>
                        
                        <div class="flex justify-center md:justify-start space-x-4 mb-4">
                            <div class="text-center">
                                <span class="font-bold block"><?= $total_classes ?></span>
                                <span class="text-gray-500 text-sm">Classes</span>
                            </div>
                            <div class="text-center">
                                <span class="font-bold block"><?= $present_count ?></span>
                                <span class="text-gray-500 text-sm">Present</span>
                            </div>
                            <div class="text-center">
                                <span class="font-bold block"><?= round(($present_count / max(1, $total_classes)) * 100) ?>%</span>
                                <span class="text-gray-500 text-sm">Attendance</span>
                            </div>
                        </div>
                        
                        <div class="flex space-x-2 justify-center md:justify-start">
                            <a href="mailto:<?= htmlspecialchars($student['email']) ?>" class="px-3 py-1 bg-blue-500 text-white rounded-full text-sm">
                                <i class="fas fa-envelope mr-1"></i> Email
                            </a>
                            <a href="tel:<?= htmlspecialchars($student['phone_number']) ?>" class="px-3 py-1 bg-gray-200 text-gray-800 rounded-full text-sm">
                                <i class="fas fa-phone mr-1"></i> Call
                            </a>
                            <a href="edit_student.php?id=<?= $student['student_id'] ?>" class="px-3 py-1 bg-gray-200 text-gray-800 rounded-full text-sm">
                                <i class="fas fa-edit mr-1"></i> Edit
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Details Section -->
            <div class="border-t border-gray-200 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h3 class="font-medium text-gray-900 mb-2">Personal Information</h3>
                        <div class="space-y-2">
                            <p><span class="text-gray-600">Email:</span> <?= htmlspecialchars($student['email'] ?? 'N/A') ?></p>
                            <p><span class="text-gray-600">Phone:</span> <?= htmlspecialchars($student['phone_number'] ?? 'N/A') ?></p>
                            <p><span class="text-gray-600">DOB:</span> <?= $student['date_of_birth'] ? date('M j, Y', strtotime($student['date_of_birth'])) : 'N/A' ?></p>
                            <p><span class="text-gray-600">Father:</span> <?= htmlspecialchars($student['father_name'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="font-medium text-gray-900 mb-2">Academic Information</h3>
                        <div class="space-y-2">
                            <p><span class="text-gray-600">Batch:</span> <?= htmlspecialchars($batch['batch_id'] ?? 'N/A') ?> - <?= htmlspecialchars($batch['course_name'] ?? 'N/A') ?></p>
                            <p><span class="text-gray-600">Enrolled:</span> <?= date('M j, Y', strtotime($student['enrollment_date'])) ?></p>
                            <p><span class="text-gray-600">Status:</span> 
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?= $student['current_status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                       ($student['current_status'] === 'dropped' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                    <?= ucfirst($student['current_status']) ?>
                                </span>
                            </p>
                            <?php if ($student['current_status'] === 'dropped'): ?>
                                <p><span class="text-gray-600">Dropout Date:</span> <?= date('M j, Y', strtotime($student['dropout_date'])) ?></p>
                                <p><span class="text-gray-600">Reason:</span> <?= htmlspecialchars($student['dropout_reason'] ?? 'N/A') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Tabs -->
        <div class="bg-white shadow rounded-lg mb-4">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button id="attendanceTab" class="py-4 px-6 text-center border-b-2 font-medium text-sm border-blue-500 text-blue-600">
                        Attendance
                    </button>
                    <button id="examsTab" class="py-4 px-6 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Exams
                    </button>
                </nav>
            </div>
            
            <!-- Attendance Content -->
            <div id="attendanceContent" class="p-4">
                <?php if (count($attendance) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($attendance as $record): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?= date('M j, Y', strtotime($record['date'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs rounded-full 
                                                <?= $record['status'] === 'Present' ? 'bg-green-100 text-green-800' : 
                                                   ($record['status'] === 'Absent' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                                <?= $record['status'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($record['remarks'] ?? '') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-calendar-times text-gray-400 text-4xl mb-2"></i>
                        <p class="text-gray-600">No attendance records found</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Exams Content -->
            <div id="examsContent" class="p-4 hidden">
                <?php if (count($exams) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exam ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mode</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($exams as $exam): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?= htmlspecialchars($exam['exam_id']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?= date('M j, Y', strtotime($exam['exam_date'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?= htmlspecialchars($exam['mode']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?= $exam['score'] !== null ? htmlspecialchars($exam['score']) : 'N/A' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($exam['is_malpractice']): ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Malpractice</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Clean</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-file-alt text-gray-400 text-4xl mb-2"></i>
                        <p class="text-gray-600">No exam records found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        document.getElementById('attendanceTab').addEventListener('click', function() {
            document.getElementById('attendanceTab').classList.add('border-blue-500', 'text-blue-600');
            document.getElementById('attendanceTab').classList.remove('border-transparent', 'text-gray-500');
            document.getElementById('examsTab').classList.add('border-transparent', 'text-gray-500');
            document.getElementById('examsTab').classList.remove('border-blue-500', 'text-blue-600');
            
            document.getElementById('attendanceContent').classList.remove('hidden');
            document.getElementById('examsContent').classList.add('hidden');
        });
        
        document.getElementById('examsTab').addEventListener('click', function() {
            document.getElementById('examsTab').classList.add('border-blue-500', 'text-blue-600');
            document.getElementById('examsTab').classList.remove('border-transparent', 'text-gray-500');
            document.getElementById('attendanceTab').classList.add('border-transparent', 'text-gray-500');
            document.getElementById('attendanceTab').classList.remove('border-blue-500', 'text-blue-600');
            
            document.getElementById('examsContent').classList.remove('hidden');
            document.getElementById('attendanceContent').classList.add('hidden');
        });
    </script>
</body>
</html>