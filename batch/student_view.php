<?php
require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$student_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$student_id) {
    header("Location: ../batch/batch_view.php");
    exit();
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get student details with profile picture
    $stmt = $conn->prepare("
        SELECT * 
        FROM students 
        WHERE student_id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        header("Location: ../batch/batch_view.php");
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --light-text: #6c757d;
            --success-color: #4bb543;
            --danger-color: #f94144;
        }
        
        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            object-fit: cover;
        }
        
        .stat-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
            color: black;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .attendance-present {
            background-color: rgba(75, 181, 67, 0.1);
            color: var(--success-color);
        }
        
        .attendance-absent {
            background-color: rgba(249, 65, 68, 0.1);
            color: var(--danger-color);
        }
        
        .attendance-late {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            font-weight: 500;
        }
        
        .nav-tabs .nav-link {
            color: var(--light-text);
            border: none;
            padding: 12px 20px;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link:hover {
            border: none;
            color: var(--primary-color);
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>
    
    <div class="flex-1 ml-0 md:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
            <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fas fa-user-graduate text-blue-500"></i>
                <span>Student Profile</span>
            </h1>
            <div class="flex items-center space-x-4">
                <a href="batch_view.php?batch_id=<?= $student['batch_name'] ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </a>
            </div>
        </header>
        
        <div class="p-4 md:p-6">
            <!-- Profile Card -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-6 text-white">
                    <div class="flex flex-col md:flex-row items-center">
                        <!-- Profile Picture -->
                        <div class="mb-4 md:mb-0 md:mr-6">
                            <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                                <img src="<?= htmlspecialchars($student['profile_picture']) ?>" 
                                     alt="Profile Picture" 
                                     class="profile-picture">
                            <?php else: ?>
                                <div class="profile-picture bg-white flex items-center justify-center">
                                    <i class="fas fa-user text-4xl text-blue-500"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Profile Info -->
                        <div class="flex-1 text-center md:text-left">
                            <h2 class="text-3xl font-bold mb-1"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h2>
                            <p class="text-blue-100 mb-4"><?= htmlspecialchars($student['student_id']) ?></p>
                            
                            <div class="flex flex-wrap justify-center md:justify-start gap-4 mb-4">
                                <div class="stat-card bg-white bg-opacity-20 p-3 rounded-lg text-center min-w-[120px]">
                                    <div class="text-2xl font-bold"><?= $total_classes ?></div>
                                    <div class="text-sm text-blue-300">Total Classes</div>
                                </div>
                                <div class="stat-card bg-white bg-opacity-20 p-3 rounded-lg text-center min-w-[120px]">
                                    <div class="text-2xl font-bold"><?= $present_count ?></div>
                                    <div class="text-sm text-blue-300">Present</div>
                                </div>
                                <div class="stat-card bg-white bg-opacity-20 p-3 rounded-lg text-center min-w-[120px]">
                                    <div class="text-2xl font-bold"><?= round(($present_count / max(1, $total_classes)) * 100) ?>%</div>
                                    <div class="text-sm text-blue-300">Attendance</div>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap justify-center md:justify-start gap-2">
                                <a href="mailto:<?= htmlspecialchars($student['email']) ?>" class="btn btn-sm btn-light">
                                    <i class="fas fa-envelope mr-2"></i> Email
                                </a>
                                <a href="tel:<?= htmlspecialchars($student['phone_number']) ?>" class="btn btn-sm btn-outline-light">
                                    <i class="fas fa-phone mr-2"></i> Call
                                </a>
                                <a href="../student/edit_student.php?id=<?= $student['student_id'] ?>" class="btn btn-sm btn-outline-light">
                                    <i class="fas fa-edit mr-2"></i> Edit
                                </a>
                                <form action="../student/drop_student.php" method="POST" class="inline">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($student['student_id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                            onclick="return confirm('Are you sure you want to drop this student?')">
                                        <i class="fas fa-user-minus mr-2"></i> Drop
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Details Section -->
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                                <i class="fas fa-user-circle mr-2 text-blue-500"></i> Personal Information
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <span class="text-gray-600 w-24 flex-shrink-0"><i class="fas fa-envelope mr-2"></i> Email:</span>
                                    <span><?= htmlspecialchars($student['email'] ?? 'N/A') ?></span>
                                </div>
                                <div class="flex items-start">
                                    <span class="text-gray-600 w-24 flex-shrink-0"><i class="fas fa-phone mr-2"></i> Phone:</span>
                                    <span><?= htmlspecialchars($student['phone_number'] ?? 'N/A') ?></span>
                                </div>
                                <div class="flex items-start">
                                    <span class="text-gray-600 w-24 flex-shrink-0"><i class="fas fa-birthday-cake mr-2"></i> DOB:</span>
                                    <span><?= $student['date_of_birth'] ? date('M j, Y', strtotime($student['date_of_birth'])) : 'N/A' ?></span>
                                </div>
                                <div class="flex items-start">
                                    <span class="text-gray-600 w-24 flex-shrink-0"><i class="fas fa-user-friends mr-2"></i> Father:</span>
                                    <span><?= htmlspecialchars($student['father_name'] ?? 'N/A') ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200">
                                <i class="fas fa-graduation-cap mr-2 text-blue-500"></i> Academic Information
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <span class="text-gray-600 w-24 flex-shrink-0"><i class="fas fa-users mr-2"></i> Batch:</span>
                                    <span><?= htmlspecialchars($batch['batch_id'] ?? 'N/A') ?> - <?= htmlspecialchars($batch['course_name'] ?? 'N/A') ?></span>
                                </div>
                                <div class="flex items-start">
                                    <span class="text-gray-600 w-24 flex-shrink-0"><i class="fas fa-calendar-alt mr-2"></i> Enrolled:</span>
                                    <span><?= date('M j, Y', strtotime($student['enrollment_date'])) ?></span>
                                </div>
                                <div class="flex items-start">
                                    <span class="text-gray-600 w-24 flex-shrink-0"><i class="fas fa-info-circle mr-2"></i> Status:</span>
                                    <span class="px-3 py-1 text-xs rounded-full 
                                        <?= $student['current_status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                           ($student['current_status'] === 'dropped' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                        <?= ucfirst($student['current_status']) ?>
                                    </span>
                                </div>
                                <?php if ($student['current_status'] === 'dropped'): ?>
                                    <div class="flex items-start">
                                        <span class="text-gray-600 w-24 flex-shrink-0"><i class="fas fa-calendar-times mr-2"></i> Dropout:</span>
                                        <span><?= date('M j, Y', strtotime($student['dropout_date'])) ?></span>
                                    </div>
                                    <div class="flex items-start">
                                        <span class="text-gray-600 w-24 flex-shrink-0"><i class="fas fa-comment mr-2"></i> Reason:</span>
                                        <span><?= htmlspecialchars($student['dropout_reason'] ?? 'N/A') ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Tabs -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <ul class="nav nav-tabs" id="studentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance-tab-pane" type="button" role="tab">
                            <i class="fas fa-calendar-check mr-2"></i> Attendance
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="exams-tab" data-bs-toggle="tab" data-bs-target="#exams-tab-pane" type="button" role="tab">
                            <i class="fas fa-clipboard-list mr-2"></i> Exams
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content p-4" id="studentTabsContent">
                    <!-- Attendance Tab -->
                    <div class="tab-pane fade show active" id="attendance-tab-pane" role="tabpanel">
                        <div class="mb-6">
                            <h4 class="font-bold text-gray-800 mb-4">Attendance Summary</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div class="stat-card p-4 attendance-present">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-bold">Present</span>
                                        <span class="text-lg font-bold"><?= $present_count ?></span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: <?= $total_classes ? ($present_count/$total_classes)*100 : 0 ?>%" aria-valuenow="<?= $present_count ?>" aria-valuemin="0" aria-valuemax="<?= $total_classes ?>"></div>
                                    </div>
                                </div>
                                <div class="stat-card p-4 attendance-absent">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-bold">Absent</span>
                                        <span class="text-lg font-bold"><?= $absent_count ?></span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $total_classes ? ($absent_count/$total_classes)*100 : 0 ?>%" aria-valuenow="<?= $absent_count ?>" aria-valuemin="0" aria-valuemax="<?= $total_classes ?>"></div>
                                    </div>
                                </div>
                                <div class="stat-card p-4 attendance-late">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-bold">Late</span>
                                        <span class="text-lg font-bold"><?= $late_count ?></span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $total_classes ? ($late_count/$total_classes)*100 : 0 ?>%" aria-valuenow="<?= $late_count ?>" aria-valuemin="0" aria-valuemax="<?= $total_classes ?>"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h4 class="font-bold text-gray-800 mb-4">Attendance Records</h4>
                        <?php if (count($attendance) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendance as $record): ?>
                                            <tr>
                                                <td><?= date('M j, Y', strtotime($record['date'])) ?></td>
                                                <td>
                                                    <span class="badge 
                                                        <?= $record['status'] === 'Present' ? 'bg-success' : 
                                                           ($record['status'] === 'Absent' ? 'bg-danger' : 'bg-warning') ?>">
                                                        <?= $record['status'] ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($record['remarks'] ?? '') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-calendar-times text-gray-400 fa-4x mb-3"></i>
                                <h5 class="text-gray-600">No attendance records found</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Exams Tab -->
                    <div class="tab-pane fade" id="exams-tab-pane" role="tabpanel">
                        <?php if (count($exams) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th>Exam ID</th>
                                            <th>Date</th>
                                            <th>Mode</th>
                                            <th>Score</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($exams as $exam): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($exam['exam_id']) ?></td>
                                                <td><?= date('M j, Y', strtotime($exam['exam_date'])) ?></td>
                                                <td><?= htmlspecialchars($exam['mode']) ?></td>
                                                <td>
                                                    <?php if ($exam['score'] !== null): ?>
                                                        <span class="badge <?= $exam['score'] >= 70 ? 'bg-success' : ($exam['score'] >= 50 ? 'bg-warning' : 'bg-danger') ?>">
                                                            <?= htmlspecialchars($exam['score']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($exam['is_malpractice']): ?>
                                                        <span class="badge bg-danger">Malpractice</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Clean</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-file-alt text-gray-400 fa-4x mb-3"></i>
                                <h5 class="text-gray-600">No exam records found</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        }
        
        // Initialize tooltips
        $(function () {
            $('[data-bs-toggle="tooltip"]').tooltip()
        })
    </script>
</body>
</html>