<?php
// Main file: student_view.php
// This file displays a single student's profile, including personal details,
// attendance, exam results, and documents, with an enhanced, modern design.

require_once '../db_connection.php';
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get student ID from URL
$student_id = isset($_GET['id']) ? $_GET['id'] : null;
$from_batch = isset($_GET['from_batch']) ? $_GET['from_batch'] : null;

// Redirect if student ID is missing
if (!$student_id) {
    header("Location: ../student_list.php");
    exit();
}

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch student details with profile picture
    $stmt = $conn->prepare("
        SELECT * FROM students 
        WHERE student_id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Redirect if student not found
    if (!$student) {
        header("Location: ../batch_list.php");
        exit();
    }    
    
    // Fetch batch details
    $stmt = $conn->prepare("SELECT * FROM batches WHERE batch_id = ?");
    $stmt->execute([$student['batch_name']]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fetch attendance records
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
    $present_percentage = $total_classes > 0 ? round(($present_count / $total_classes) * 100) : 0;
    $absent_percentage = $total_classes > 0 ? round(($absent_count / $total_classes) * 100) : 0;
    $late_percentage = $total_classes > 0 ? round(($late_count / $total_classes) * 100) : 0;
    
    // Fetch exam results
    $stmt = $conn->prepare("SELECT pe.exam_id, pe.exam_date, pe.mode, es.score, es.is_malpractice 
                          FROM proctored_exams pe
                          JOIN exam_students es ON pe.exam_id = es.exam_id
                          WHERE es.student_name = ? AND pe.batch_id = ?
                          ORDER BY pe.exam_date DESC");
    $stmt->execute([$student['first_name'] . ' ' . $student['last_name'], $student['batch_name']]);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch student documents
    $stmt = $conn->prepare("SELECT * FROM student_documents WHERE student_id = ? ORDER BY document_type");
    $stmt->execute([$student_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle document upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
        $document_type = $_POST['document_type'];
        $allowed_types = ['aadhaar', 'pancard', 'tenth_marksheet', 'twelfth_marksheet', 'other'];
        
        if (in_array($document_type, $allowed_types)) {
            $upload_dir = '../uploads/student_documents/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = $student_id . '_' . $document_type . '_' . time() . '_' . basename($_FILES['document_file']['name']);
            $target_file = $upload_dir . $file_name;
            
            $stmt = $conn->prepare("SELECT * FROM student_documents WHERE student_id = ? AND document_type = ?");
            $stmt->execute([$student_id, $document_type]);
            $existing_doc = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_doc) {
                if (file_exists($existing_doc['file_path'])) {
                    unlink($existing_doc['file_path']);
                }
                $stmt = $conn->prepare("UPDATE student_documents SET file_path = ? WHERE document_id = ?");
                $stmt->execute([$target_file, $existing_doc['document_id']]);
            } else {
                $stmt = $conn->prepare("INSERT INTO student_documents (student_id, document_type, file_path) VALUES (?, ?, ?)");
                $stmt->execute([$student_id, $document_type, $target_file]);
            }
            
            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $target_file)) {
                $_SESSION['success_message'] = "Document uploaded successfully!";
                header("Location: student_view.php?id=$student_id");
                exit();
            } else {
                $_SESSION['error_message'] = "Sorry, there was an error uploading your file.";
                $_SESSION['show_upload_modal'] = true;
                header("Location: student_view.php?id=$student_id");
                exit();
            }
        }
    }
    
    // Handle document deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_document'])) {
        $document_id = $_POST['document_id'];
        
        $stmt = $conn->prepare("SELECT * FROM student_documents WHERE document_id = ? AND student_id = ?");
        $stmt->execute([$document_id, $student_id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($doc) {
            if (file_exists($doc['file_path'])) {
                unlink($doc['file_path']);
            }
            $stmt = $conn->prepare("DELETE FROM student_documents WHERE document_id = ?");
            $stmt->execute([$document_id]);
            $_SESSION['success_message'] = "Document deleted successfully!";
            header("Location: student_view.php?id=$student_id");
            exit();
        }
    }
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?> | ASD Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #4f46e5;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9fc;
            color: var(--text-secondary);
        }
        
        h1, h2, h3, h4 {
            font-family: 'Poppins', sans-serif;
            color: var(--text-primary);
        }

        .scroll-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.3s ease-in-out;
        }
        
        .scroll-to-top.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 400px;
        }

        .profile-picture {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 6px solid #fff;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            object-fit: cover;
            animation: fadeInScale 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
            opacity: 0;
            transform: scale(0.8);
        }

        @keyframes fadeInScale {
            to { opacity: 1; transform: scale(1); }
        }

        .card-container {
            transform: translateY(-80px);
        }

        .stat-card {
            background-color: #ffffff;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: 1px solid #e5e7eb;
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .progress-bar-container {
            height: 8px;
            border-radius: 9999px;
            background-color: #e5e7eb;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.6s ease-out;
        }

        .tab-button {
            position: relative;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .tab-button::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background-color: var(--secondary-color);
            border-radius: 2px;
            transition: width 0.4s ease-in-out;
        }

        .tab-button.active::after {
            width: 80%;
        }

        .tab-pane {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .document-card {
            transition: all 0.3s ease-in-out;
        }
        
        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .action-button {
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .action-button:hover {
            transform: translateY(-3px) scale(1.05);
        }

        .table-row {
            transition: background-color 0.2s ease-in-out;
        }
        .table-row:hover {
            background-color: #f3f4f6;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background-color: white;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Sidebar adjustment */
        .main-content {
            margin-left: 16rem; /* Match sidebar width */
            transition: margin 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }

    </style>
</head>

<body class="bg-gray-100 text-gray-800">
    <!-- Sidebar -->
    <?php include '../sidebar.php'; ?>
    
    <!-- Mobile header -->
    <div class="md:hidden bg-white shadow-sm fixed w-full z-30">
        <div class="flex items-center justify-between p-4">
            <button id="mobileSidebarToggle" class="text-gray-600 hover:text-blue-600">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 class="text-xl font-bold text-blue-600">ASD Academy</h1>
        </div>
    </div>

    <!-- Main content wrapper -->
    <div class="flex flex-col min-h-screen relative main-content">
        <!-- Hero Section -->
        <div class="hero-section text-white flex justify-center items-center relative py-20 md:py-32 mt-16 md:mt-0">
            <div class="container mx-auto px-6 md:px-12 flex flex-col md:flex-row items-center space-y-8 md:space-y-0 md:space-x-12">
                <!-- Profile Picture -->
                <div class="relative z-10">
                    <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                        <img src="<?= htmlspecialchars($student['profile_picture']) ?>" 
                             alt="Profile Picture" 
                             class="profile-picture">
                    <?php else: ?>
                        <div class="profile-picture bg-white flex items-center justify-center">
                            <i class="fas fa-user text-6xl text-purple-600"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Profile Info -->
                <div class="text-center md:text-left relative z-10">
                    <h1 class="text-4xl md:text-5xl font-extrabold mb-2 drop-shadow-lg">
                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                    </h1>
                    <p class="text-indigo-200 text-xl font-medium drop-shadow"><?= htmlspecialchars($student['student_id']) ?></p>
                    <div class="mt-6 flex flex-wrap justify-center md:justify-start gap-4">
                        <a href="mailto:<?= htmlspecialchars($student['email']) ?>" class="action-button bg-white bg-opacity-30 text-white font-semibold py-2 px-6 rounded-full hover:bg-opacity-50">
                            <i class="fas fa-envelope mr-2"></i> Email
                        </a>
                        <a href="tel:<?= htmlspecialchars($student['phone_number']) ?>" class="action-button bg-transparent border border-white text-white font-semibold py-2 px-6 rounded-full hover:bg-white hover:text-purple-700">
                            <i class="fas fa-phone mr-2"></i> Call
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content area -->
        <div class="container mx-auto px-6 md:px-12 card-container">
            <div class="bg-white rounded-3xl shadow-2xl p-6 md:p-10 mb-10 border border-gray-200">
                <!-- Navigation Tabs -->
                <div class="border-b border-gray-200 mb-8">
                    <nav class="flex space-x-2 md:space-x-8" role="tablist">
                        <a href="#overview" class="tab-button active py-4 px-4 md:px-6 text-base font-semibold text-gray-700 hover:text-purple-600 focus:outline-none">
                            <i class="fas fa-chart-bar mr-2"></i> Overview
                        </a>
                        <a href="#attendance" class="tab-button py-4 px-4 md:px-6 text-base font-semibold text-gray-700 hover:text-purple-600 focus:outline-none">
                            <i class="fas fa-calendar-check mr-2"></i> Attendance
                        </a>
                        <a href="#exams" class="tab-button py-4 px-4 md:px-6 text-base font-semibold text-gray-700 hover:text-purple-600 focus:outline-none">
                            <i class="fas fa-clipboard-list mr-2"></i> Exams
                        </a>
                        <a href="#documents" class="tab-button py-4 px-4 md:px-6 text-base font-semibold text-gray-700 hover:text-purple-600 focus:outline-none">
                            <i class="fas fa-file-alt mr-2"></i> Documents
                        </a>
                    </nav>
                </div>
                
                <!-- Tab Panes -->
                <div class="space-y-12">
                    <!-- Overview Section -->
                    <div id="overview" class="tab-pane">
                        <h2 class="text-2xl font-bold mb-6 text-gray-900 flex items-center space-x-2">
                            <i class="fas fa-info-circle text-purple-600"></i>
                            <span>Student Details</span>
                        </h2>
                        
                        <!-- Personal and Academic Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200 shadow-sm">
                                <h3 class="text-xl font-semibold mb-4 text-gray-900">Personal Information</h3>
                                <ul class="space-y-3 text-gray-600">
                                    <li class="flex items-center space-x-3">
                                        <i class="fas fa-envelope text-lg text-gray-500"></i>
                                        <span>Email: <span class="font-medium"><?= htmlspecialchars($student['email'] ?? 'N/A') ?></span></span>
                                    </li>
                                    <li class="flex items-center space-x-3">
                                        <i class="fas fa-phone text-lg text-gray-500"></i>
                                        <span>Phone: <span class="font-medium"><?= htmlspecialchars($student['phone_number'] ?? 'N/A') ?></span></span>
                                    </li>
                                    <li class="flex items-center space-x-3">
                                        <i class="fas fa-birthday-cake text-lg text-gray-500"></i>
                                        <span>DOB: <span class="font-medium"><?= $student['date_of_birth'] ? date('M j, Y', strtotime($student['date_of_birth'])) : 'N/A' ?></span></span>
                                    </li>
                                    <li class="flex items-center space-x-3">
                                        <i class="fas fa-user-friends text-lg text-gray-500"></i>
                                        <span>Father: <span class="font-medium"><?= htmlspecialchars($student['father_name'] ?? 'N/A') ?></span></span>
                                    </li>
                                </ul>
                            </div>
                            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200 shadow-sm">
                                <h3 class="text-xl font-semibold mb-4 text-gray-900">Academic Information</h3>
                                <ul class="space-y-3 text-gray-600">
                                    <li class="flex items-center space-x-3">
                                        <i class="fas fa-users text-lg text-gray-500"></i>
                                        <span>Batch: <span class="font-medium"><?= htmlspecialchars($batch['batch_id'] ?? 'N/A') ?> - <?= htmlspecialchars($batch['course_name'] ?? 'N/A') ?></span></span>
                                    </li>
                                    <li class="flex items-center space-x-3">
                                        <i class="fas fa-calendar-alt text-lg text-gray-500"></i>
                                        <span>Enrolled: <span class="font-medium"><?= date('M j, Y', strtotime($student['enrollment_date'])) ?></span></span>
                                    </li>
                                    <li class="flex items-center space-x-3">
                                        <i class="fas fa-clipboard-check text-lg text-gray-500"></i>
                                        <span>Status: <span class="font-medium px-3 py-1 text-sm rounded-full 
                                            <?= $student['current_status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                               ($student['current_status'] === 'dropped' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                            <?= ucfirst($student['current_status']) ?>
                                        </span></span>
                                    </li>
                                    <?php if ($student['current_status'] === 'dropped'): ?>
                                        <li class="flex items-center space-x-3">
                                            <i class="fas fa-calendar-times text-lg text-gray-500"></i>
                                            <span>Dropout Date: <span class="font-medium"><?= date('M j, Y', strtotime($student['dropout_date'])) ?></span></span>
                                        </li>
                                        <li class="flex items-center space-x-3">
                                            <i class="fas fa-comment text-lg text-gray-500"></i>
                                            <span>Reason: <span class="font-medium"><?= htmlspecialchars($student['dropout_reason'] ?? 'N/A') ?></span></span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Actions Section -->
                        <div class="mt-8">
                            <h3 class="text-xl font-semibold mb-4 text-gray-900">Account Actions</h3>
                            <div class="flex flex-wrap gap-4">
                                <a href="edit_student.php?id=<?= $student['student_id'] ?>" class="action-button flex items-center px-6 py-3 bg-indigo-600 text-white rounded-full shadow-lg hover:bg-indigo-700">
                                    <i class="fas fa-edit mr-2"></i> Edit Profile
                                </a>
                                <form action="drop_student.php" method="POST" class="inline">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($student['student_id']) ?>">
                                    <button type="submit" class="action-button flex items-center px-6 py-3 bg-red-500 text-white rounded-full shadow-lg hover:bg-red-600" 
                                            onclick="return confirm('Are you sure you want to drop this student? This action cannot be undone.')">
                                        <i class="fas fa-user-minus mr-2"></i> Drop Student
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Section -->
                    <div id="attendance" class="tab-pane hidden">
                        <h2 class="text-2xl font-bold mb-6 text-gray-900 flex items-center space-x-2">
                            <i class="fas fa-calendar-check text-purple-600"></i>
                            <span>Attendance Summary</span>
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <div class="stat-card bg-green-50 text-green-800">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-xl font-semibold">Present</span>
                                    <span class="text-4xl font-bold"><?= $present_count ?></span>
                                </div>
                                <p class="text-sm font-medium mb-2">Total classes: <?= $total_classes ?></p>
                                <div class="progress-bar-container">
                                    <div class="progress-bar bg-green-500" style="width: <?= $present_percentage ?>%"></div>
                                </div>
                                <p class="text-right text-sm font-bold mt-2"><?= $present_percentage ?>%</p>
                            </div>
                            <div class="stat-card bg-red-50 text-red-800">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-xl font-semibold">Absent</span>
                                    <span class="text-4xl font-bold"><?= $absent_count ?></span>
                                </div>
                                <p class="text-sm font-medium mb-2">Total classes: <?= $total_classes ?></p>
                                <div class="progress-bar-container">
                                    <div class="progress-bar bg-red-500" style="width: <?= $absent_percentage ?>%"></div>
                                </div>
                                <p class="text-right text-sm font-bold mt-2"><?= $absent_percentage ?>%</p>
                            </div>
                            <div class="stat-card bg-yellow-50 text-yellow-800">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-xl font-semibold">Late</span>
                                    <span class="text-4xl font-bold"><?= $late_count ?></span>
                                </div>
                                <p class="text-sm font-medium mb-2">Total classes: <?= $total_classes ?></p>
                                <div class="progress-bar-container">
                                    <div class="progress-bar bg-yellow-500" style="width: <?= $late_percentage ?>%"></div>
                                </div>
                                <p class="text-right text-sm font-bold mt-2"><?= $late_percentage ?>%</p>
                            </div>
                        </div>

                        <h3 class="text-xl font-bold mb-4 text-gray-900">Detailed Attendance History</h3>
                        <?php if (count($attendance) > 0): ?>
                            <div class="overflow-hidden rounded-2xl shadow-lg border border-gray-200">
                                <table class="min-w-full table-auto text-sm md:text-base bg-white">
                                    <thead class="bg-gray-100 text-gray-600 uppercase text-left">
                                        <tr>
                                            <th class="py-4 px-6 font-semibold">Date</th>
                                            <th class="py-4 px-6 font-semibold">Status</th>
                                            <th class="py-4 px-6 font-semibold">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($attendance as $record): ?>
                                            <tr class="table-row">
                                                <td class="py-4 px-6"><?= date('M j, Y', strtotime($record['date'])) ?></td>
                                                <td class="py-4 px-6">
                                                    <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                                        <?= $record['status'] === 'Present' ? 'bg-green-100 text-green-800' : 
                                                           ($record['status'] === 'Absent' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                                        <?= $record['status'] ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-6 text-gray-500"><?= htmlspecialchars($record['remarks'] ?? 'N/A') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12 text-gray-500">
                                <i class="fas fa-calendar-times text-7xl mb-4 text-gray-300"></i>
                                <h5 class="text-xl font-semibold">No attendance records available.</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Exams Section -->
                    <div id="exams" class="tab-pane hidden">
                        <h2 class="text-2xl font-bold mb-6 text-gray-900 flex items-center space-x-2">
                            <i class="fas fa-clipboard-list text-purple-600"></i>
                            <span>Exam Results</span>
                        </h2>
                        <?php if (count($exams) > 0): ?>
                            <div class="overflow-hidden rounded-2xl shadow-lg border border-gray-200">
                                <table class="min-w-full table-auto text-sm md:text-base bg-white">
                                    <thead class="bg-gray-100 text-gray-600 uppercase text-left">
                                        <tr>
                                            <th class="py-4 px-6 font-semibold">Exam ID</th>
                                            <th class="py-4 px-6 font-semibold">Date</th>
                                            <th class="py-4 px-6 font-semibold">Mode</th>
                                            <th class="py-4 px-6 font-semibold">Score</th>
                                            <th class="py-4 px-6 font-semibold">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($exams as $exam): ?>
                                            <tr class="table-row">
                                                <td class="py-4 px-6"><?= htmlspecialchars($exam['exam_id']) ?></td>
                                                <td class="py-4 px-6"><?= date('M j, Y', strtotime($exam['exam_date'])) ?></td>
                                                <td class="py-4 px-6"><?= htmlspecialchars($exam['mode']) ?></td>
                                                <td class="py-4 px-6">
                                                    <?php if ($exam['score'] !== null): ?>
                                                        <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                                            <?= $exam['score'] >= 70 ? 'bg-green-100 text-green-800' : 
                                                               ($exam['score'] >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                                            <?= htmlspecialchars($exam['score']) ?>%
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <?php if ($exam['is_malpractice']): ?>
                                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Malpractice</span>
                                                    <?php else: ?>
                                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Clean</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12 text-gray-500">
                                <i class="fas fa-file-alt text-7xl mb-4 text-gray-300"></i>
                                <h5 class="text-xl font-semibold">No exam records found.</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Documents Section -->
                    <div id="documents" class="tab-pane hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-900 flex items-center space-x-2">
                                <i class="fas fa-file-alt text-purple-600"></i>
                                <span>Student Documents</span>
                            </h2>
                            <button onclick="showModal()" class="action-button bg-indigo-600 text-white py-3 px-6 rounded-full shadow-lg hover:bg-indigo-700">
                                <i class="fas fa-upload mr-2"></i> Upload Document
                            </button>
                        </div>
                        
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-4">
                                <span class="block sm:inline"><?= $_SESSION['success_message'] ?></span>
                            </div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4">
                                <span class="block sm:inline"><?= $_SESSION['error_message'] ?></span>
                            </div>
                            <?php unset($_SESSION['error_message']); ?>
                        <?php endif; ?>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php if (count($documents) > 0): ?>
                                <?php foreach ($documents as $doc): ?>
                                    <div class="document-card bg-white p-6 rounded-2xl flex flex-col items-center text-center shadow-md border border-gray-200">
                                        <?php 
                                            $icon = 'fa-file';
                                            if (strpos($doc['file_path'], '.pdf') !== false) $icon = 'fa-file-pdf';
                                            elseif (strpos($doc['file_path'], '.jpg') !== false || strpos($doc['file_path'], '.jpeg') !== false || strpos($doc['file_path'], '.png') !== false) $icon = 'fa-file-image';
                                            elseif (strpos($doc['file_path'], '.doc') !== false || strpos($doc['file_path'], '.docx') !== false) $icon = 'fa-file-word';
                                        ?>
                                        <i class="fas <?= $icon ?> text-6xl text-indigo-500 mb-4 transition-transform duration-300 hover:scale-110"></i>
                                        <h5 class="text-xl font-bold text-gray-900 mb-1"><?= ucfirst(str_replace('_', ' ', $doc['document_type'])) ?></h5>
                                        <small class="text-gray-500 mb-4">Uploaded: <?= date('M j, Y', strtotime($doc['uploaded_at'])) ?></small>
                                        
                                        <div class="flex space-x-2">
                                            <a href="<?= $doc['file_path'] ?>" target="_blank" class="px-4 py-2 bg-purple-100 text-purple-700 rounded-full text-sm font-medium hover:bg-purple-200 transition">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </a>
                                            <a href="<?= $doc['file_path'] ?>" download class="px-4 py-2 bg-gray-200 text-gray-700 rounded-full text-sm font-medium hover:bg-gray-300 transition">
                                                <i class="fas fa-download mr-1"></i> Download
                                            </a>
                                            <form action="" method="POST" class="inline">
                                                <input type="hidden" name="document_id" value="<?= $doc['document_id'] ?>">
                                                <button type="submit" name="delete_document" class="px-4 py-2 bg-red-100 text-red-700 rounded-full text-sm font-medium hover:bg-red-200 transition" onclick="return confirm('Are you sure you want to delete this document?')">
                                                    <i class="fas fa-trash-alt mr-1"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-span-full text-center py-12 text-gray-500">
                                    <i class="fas fa-folder-open text-7xl mb-4 text-gray-300"></i>
                                    <h5 class="text-xl font-semibold">No documents uploaded yet.</h5>
                                    <p class="text-sm">Upload documents using the button above.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back to Top Button -->
        <button id="scrollToTopBtn" class="scroll-to-top bg-purple-600 text-white rounded-full p-4 shadow-xl hover:bg-purple-700 focus:outline-none">
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>

    <!-- Upload Document Modal -->
    <div id="uploadDocumentModal" class="modal">
        <div class="modal-content bg-white p-6 shadow-2xl rounded-2xl">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="flex justify-between items-center mb-6">
                    <h5 class="text-2xl font-bold text-gray-900">Upload Document</h5>
                    <button type="button" class="text-gray-400 hover:text-gray-600 transition" onclick="hideModal()" aria-label="Close">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="space-y-5">
                    <div class="mb-3">
                        <label for="document_type" class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                        <select class="form-select w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" id="document_type" name="document_type" required>
                            <option value="">Select document type</option>
                            <option value="aadhaar">Aadhaar Card</option>
                            <option value="pancard">PAN Card</option>
                            <option value="tenth_marksheet">10th Marksheet</option>
                            <option value="twelfth_marksheet">12th Marksheet</option>
                            <option value="other">Other Document</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="document_file" class="block text-sm font-medium text-gray-700 mb-2">Document File</label>
                        <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:px-4 file:py-2 file:border-0 file:text-sm file:font-semibold file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300 transition" type="file" id="document_file" name="document_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                        <small class="text-gray-500 mt-1 block">Allowed formats: PDF, JPG, JPEG, PNG, DOC, DOCX</small>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-8">
                    <button type="button" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition" onclick="hideModal()">Cancel</button>
                    <button type="submit" name="upload_document" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Tab switching logic
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabPanes = document.querySelectorAll('.tab-pane');

        // Function to handle tab state
        const switchTab = (targetId) => {
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('href') === `#${targetId}`) {
                    btn.classList.add('active');
                }
            });
            tabPanes.forEach(pane => {
                pane.classList.add('hidden');
            });
            document.getElementById(targetId).classList.remove('hidden');
        };

        // Event listener for tab clicks
        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = e.currentTarget.getAttribute('href').substring(1);
                switchTab(targetId);
            });
        });

        // Initial tab based on URL hash
        const initialTab = window.location.hash ? window.location.hash.substring(1) : 'overview';
        switchTab(initialTab);
        
        // Handle Back to Top button
        const scrollToTopBtn = document.getElementById('scrollToTopBtn');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                scrollToTopBtn.classList.add('show');
            } else {
                scrollToTopBtn.classList.remove('show');
            }
        });

        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Modal functions
        function showModal() {
            const modal = document.getElementById('uploadDocumentModal');
            modal.classList.add('show');
        }

        function hideModal() {
            const modal = document.getElementById('uploadDocumentModal');
            modal.classList.remove('show');
        }

        // Mobile sidebar toggle
        document.getElementById('mobileSidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('sidebarOverlay').classList.toggle('hidden');
        });

        // Show modal if there was an error and we need to show it again
        <?php if (isset($_SESSION['show_upload_modal'])): ?>
            document.addEventListener('DOMContentLoaded', () => {
                showModal();
                <?php unset($_SESSION['show_upload_modal']); ?>
            });
        <?php endif; ?>

        // Close modal when clicking outside
        window.addEventListener('click', (event) => {
            const modal = document.getElementById('uploadDocumentModal');
            if (event.target === modal) {
                hideModal();
            }
        });
    </script>
</body>
</html>