<?php
require_once '../db_connection.php';
require_once 'functions.php';

// Check admin permissions
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$trainerId = (int)$_GET['id'];

// Fetch trainer data
$stmt = $db->prepare("SELECT t.*, u.email, u.created_at as user_created 
                      FROM trainers t 
                      JOIN users u ON t.user_id = u.id 
                      WHERE t.id = :id");
$stmt->bindParam(':id', $trainerId, PDO::PARAM_INT);
$stmt->execute();
$trainer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trainer) {
    header('Location: index.php');
    exit;
}

// Get trainer stats
$batchCount = getTrainerBatchCount($trainerId);
$avgRating = getTrainerAverageRating($trainerId);

// Get recent batches
$stmt = $db->prepare("SELECT b.*, c.name as course_name 
                      FROM batches b
                      JOIN courses c ON b.course_name = c.name
                      WHERE b.batch_mentor_id = :id
                      ORDER BY b.start_date DESC
                      LIMIT 5");
$stmt->bindParam(':id', $trainerId, PDO::PARAM_INT);
$stmt->execute();
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent feedback
$stmt = $db->prepare("SELECT f.*, s.first_name as student_name, b.course_name 
                      FROM feedback f
                      JOIN students s ON f.student_name = CONCAT(s.first_name, ' ', s.last_name)
                      JOIN batches b ON f.batch_id = b.batch_id
                      WHERE b.batch_mentor_id = :id
                      ORDER BY f.date DESC
                      LIMIT 5");
$stmt->bindParam(':id', $trainerId, PDO::PARAM_INT);
$stmt->execute();
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get trainer documents
$stmt = $db->prepare("SELECT * FROM trainer_documents WHERE trainer_id = ? ORDER BY document_type");
$stmt->execute([$trainerId]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    $document_type = $_POST['document_type'];
    $allowed_types = ['resume', 'certification', 'degree', 'id_proof', 'other'];
    
    if (in_array($document_type, $allowed_types)) {
        $upload_dir = '../uploads/trainer_documents/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Check if file was uploaded without errors
        if ($_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error_message'] = "File upload error: " . $_FILES['document_file']['error'];
            $_SESSION['show_upload_modal'] = true;
            header("Location: view.php?id=$trainerId");
            exit();
        }
        
        // Validate file type
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $file_extension = strtolower(pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $_SESSION['error_message'] = "Invalid file type. Allowed types: PDF, JPG, JPEG, PNG, DOC, DOCX";
            $_SESSION['show_upload_modal'] = true;
            header("Location: view.php?id=$trainerId");
            exit();
        }
        
        $file_name = $trainerId . '_' . $document_type . '_' . time() . '_' . basename($_FILES['document_file']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Check if file already exists for this document type
        $stmt = $db->prepare("SELECT * FROM trainer_documents WHERE trainer_id = ? AND document_type = ?");
        $stmt->execute([$trainerId, $document_type]);
        $existing_doc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_doc) {
            // Delete old file
            if (file_exists($existing_doc['file_path'])) {
                unlink($existing_doc['file_path']);
            }
            
            // Update record
            $stmt = $db->prepare("UPDATE trainer_documents SET file_path = ? WHERE document_id = ?");
            $stmt->execute([$target_file, $existing_doc['document_id']]);
        } else {
            // Insert new record
            $stmt = $db->prepare("INSERT INTO trainer_documents (trainer_id, document_type, file_path) VALUES (?, ?, ?)");
            $stmt->execute([$trainerId, $document_type, $target_file]);
        }
        
        if (move_uploaded_file($_FILES['document_file']['tmp_name'], $target_file)) {
            $_SESSION['success_message'] = "Document uploaded successfully!";
            header("Location: view.php?id=$trainerId");
            exit();
        } else {
            $_SESSION['error_message'] = "Sorry, there was an error uploading your file.";
            $_SESSION['show_upload_modal'] = true;
            header("Location: view.php?id=$trainerId");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Invalid document type selected.";
        $_SESSION['show_upload_modal'] = true;
    }
}
// Handle document deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_document'])) {
    $document_id = $_POST['document_id'];
    
    $stmt = $db->prepare("SELECT * FROM trainer_documents WHERE document_id = ? AND trainer_id = ?");
    $stmt->execute([$document_id, $trainerId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($doc) {
        if (file_exists($doc['file_path'])) {
            unlink($doc['file_path']);
        }
        
        $stmt = $db->prepare("DELETE FROM trainer_documents WHERE document_id = ?");
        $stmt->execute([$document_id]);
        
        $_SESSION['success_message'] = "Document deleted successfully!";
        header("Location: view.php?id=$trainerId");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="dark-theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($trainer['name']) ?> | ASD Academy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --sidebar-width: 16rem;
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --light-text: #6c757d;
            --success-color: #4bb543;
            --danger-color: #f94144;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .admin-content {
                margin-left: 0;
            }
        }
        
        .trainer-avatar {
            object-fit: cover;
            border: 4px solid #e2e8f0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .trainer-avatar:hover {
            transform: scale(1.05);
            border-color: #93c5fd;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .card {
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
        }
        
        .badge {
            transition: all 0.2s ease;
        }
        
        .star-rating i {
            transition: all 0.2s ease;
        }
        
        .progress-ring {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 1rem;
        }
        
        .progress-ring__circle {
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
            transition: stroke-dashoffset 0.8s ease;
        }
        
        .progress-ring__text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .hover-grow {
            transition: all 0.2s ease;
        }
        
        .hover-grow:hover {
            transform: scale(1.03);
        }
        
        .fade-in-section {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .fade-in-section.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .feedback-item {
            transition: all 0.3s ease;
            padding: 1rem;
            border-radius: 0.5rem;
            background-color: #f8fafc;
            margin-bottom: 1rem;
        }
        
        .feedback-item:hover {
            background-color: #f1f5f9;
            transform: translateX(5px);
        }
        
        .batch-row {
            transition: all 0.3s ease;
        }
        
        .batch-row:hover {
            background-color: #f8fafc !important;
        }
        
        /* Document styles */
        .document-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .document-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }
        
        .document-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .document-card:hover .document-icon {
            transform: scale(1.1);
            color: var(--secondary-color);
        }
        
        .document-actions a {
            margin-right: 8px;
            transition: all 0.2s ease;
        }
        
        .document-actions a:hover {
            transform: translateY(-2px);
        }
        
        .document-type-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            transform: translateY(-10px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .document-card:hover .document-type-badge {
            transform: translateY(0);
            opacity: 1;
        }
        
        /* Tab styles */
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
            transition: all 0.2s ease;
        }
        
        .nav-tabs .nav-link:hover {
            border: none;
            color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        /* Animation classes */
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .animate-pulse-slow {
            animation: pulse 4s ease infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    
    <div class="admin-container">
        <?php include '../sidebar.php'; ?>
        
        <main class="admin-content bg-gray-50">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-6 animate__animated animate__fadeIn">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Trainer Profile</h1>
                        <nav class="flex" aria-label="Breadcrumb">
                            <ol class="inline-flex items-center space-x-1 md:space-x-2">
                                <li class="inline-flex items-center">
                                    <a href="../dashboard/dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-blue-600">
                                        <i class="fas fa-home mr-2"></i>
                                        Dashboard
                                    </a>
                                </li>
                                <li>
                                    <div class="flex items-center">
                                        <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                                        <a href="index.php" class="ml-1 text-sm font-medium text-gray-500 hover:text-blue-600 md:ml-2">Trainers</a>
                                    </div>
                                </li>
                                <li aria-current="page">
                                    <div class="flex items-center">
                                        <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                                        <span class="ml-1 text-sm font-medium text-blue-500 md:ml-2"><?= htmlspecialchars($trainer['name']) ?></span>
                                    </div>
                                </li>
                            </ol>
                        </nav>
                    </div>
                    <div class="flex space-x-2">
                        <a href="edit.php?id=<?= $trainerId ?>" class="btn btn-primary hover:bg-blue-700 transition-colors duration-300">
                            <i class="fas fa-edit fa-log"></i> Edit Profile
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary hover:bg-gray-100 transition-colors duration-300">
                            <i class="fas fa-arrow-left mr-2"></i> Back to List
                        </a>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Left Column -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Profile Card -->
                        <div class="card bg-white rounded-xl shadow-sm overflow-hidden fade-in-section">
                            <div class="p-6">
                                <div class="text-center">
                                    <div class="relative inline-block">
                                        <img src="<?= getTrainerPhoto($trainer) ?>" 
                                             class="rounded-full mb-4 trainer-avatar" 
                                             width="150" height="150" 
                                             alt="<?= htmlspecialchars($trainer['name']) ?>">
                                        <span class="absolute bottom-4 right-4 w-6 h-6 rounded-full border-2 border-white bg-<?= $trainer['is_active'] ? 'green' : 'gray' ?>-500"></span>
                                    </div>
                                    
                                    <h2 class="text-2xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($trainer['name']) ?></h2>
                                    <p class="text-gray-600 mb-4"><?= htmlspecialchars($trainer['email']) ?></p>
                                    
                                    <div class="flex justify-center mb-4">
                                        <span class="px-4 py-1 rounded-full text-sm font-medium bg-<?= $trainer['is_active'] ? 'green' : 'gray' ?>-100 text-<?= $trainer['is_active'] ? 'green' : 'gray' ?>-800">
                                            <?= $trainer['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Stats -->
                                    <div class="grid grid-cols-3 gap-4 mb-6">
                                        <div class="stats-card hover-grow">
                                            <div class="text-2xl font-bold text-blue-600"><?= $batchCount ?></div>
                                            <div class="text-xs text-gray-500 uppercase tracking-wider">Batches</div>
                                        </div>
                                        <div class="stats-card hover-grow">
                                            <div class="text-2xl font-bold text-yellow-600">
                                                <?= $avgRating ? round($avgRating, 1) : 'N/A' ?>
                                            </div>
                                            <div class="text-xs text-gray-500 uppercase tracking-wider">Avg Rating</div>
                                        </div>
                                        <div class="stats-card hover-grow">
                                            <div class="text-2xl font-bold text-purple-600"><?= $trainer['years_of_experience'] ?></div>
                                            <div class="text-xs text-gray-500 uppercase tracking-wider">Years Exp</div>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-4 text-left">
                                        <?php if ($trainer['specialization']): ?>
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-500">Specialization</h4>
                                                <p class="mt-1 text-gray-800"><?= htmlspecialchars($trainer['specialization']) ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-500">Member Since</h4>
                                            <p class="mt-1 text-gray-800"><?= date('M d, Y', strtotime($trainer['user_created'])) ?></p>
                                        </div>
                                        
                                        <?php if ($trainer['bio']): ?>
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-500">About</h4>
                                                <p class="mt-1 text-gray-800"><?= nl2br(htmlspecialchars($trainer['bio'])) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Batches Card -->
                        <div class="card bg-white rounded-xl shadow-sm overflow-hidden fade-in-section">
                            <div class="card-header">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-800">Recent Batches</h3>
                                    <a href="batches.php?id=<?= $trainerId ?>" class="text-sm text-blue-600 hover:text-blue-800 transition-colors duration-300">
                                        View All <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="p-6">
                                <?php if (empty($batches)): ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-users-slash text-4xl text-gray-300 mb-4"></i>
                                        <h4 class="text-lg font-medium text-gray-500">No batches assigned yet</h4>
                                        <p class="text-gray-400 mt-1">This trainer hasn't been assigned to any batches.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php foreach ($batches as $batch): ?>
                                                    <tr class="batch-row hover:bg-gray-50">
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($batch['batch_id']) ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($batch['course_name']) ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('M d, Y', strtotime($batch['start_date'])) ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <span class="px-2 py-1 text-xs rounded-full font-medium 
                                                                <?= $batch['status'] === 'upcoming' ? 'bg-blue-100 text-blue-800' : 
                                                                   ($batch['status'] === 'ongoing' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') ?>">
                                                                <?= ucfirst($batch['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                            <a href="/admin/batches/view.php?id=<?= $batch['batch_id'] ?>" 
                                                               class="text-blue-600 hover:text-blue-900 transition-colors duration-300">
                                                                <i class="fas fa-eye mr-1"></i> View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Feedback Card -->
                        <div class="card bg-white rounded-xl shadow-sm overflow-hidden fade-in-section">
                            <div class="card-header">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-800">Recent Feedback</h3>
                                    <a href="../feedback/feedback.php?trainer_id=<?= $trainerId ?>" class="text-sm text-blue-600 hover:text-blue-800 transition-colors duration-300">
                                        View All <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="p-6">
                                <?php if (empty($feedbacks)): ?>
                                    <div class="text-center py-8">
                                        <i class="fas fa-comment-slash text-4xl text-gray-300 mb-4"></i>
                                        <h4 class="text-lg font-medium text-gray-500">No feedback received yet</h4>
                                        <p class="text-gray-400 mt-1">This trainer hasn't received any feedback from students.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($feedbacks as $feedback): ?>
                                            <div class="feedback-item hover:shadow-sm">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <h4 class="font-medium text-gray-800"><?= htmlspecialchars($feedback['student_name']) ?></h4>
                                                        <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($feedback['course_name']) ?></p>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <?php if ($feedback['rating']): ?>
                                                            <div class="star-rating mr-2">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <i class="fas fa-star <?= $i <= $feedback['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                                                                <?php endfor; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <span class="text-xs text-gray-400"><?= date('M d, Y', strtotime($feedback['date'])) ?></span>
                                                    </div>
                                                </div>
                                                <?php if ($feedback['feedback_text']): ?>
                                                    <div class="mt-3 p-3 bg-white rounded-lg border border-gray-100">
                                                        <p class="text-gray-700 italic">"<?= htmlspecialchars($feedback['feedback_text']) ?>"</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Documents Card -->
                        <div class="card bg-white rounded-xl shadow-sm overflow-hidden fade-in-section">
                            <div class="card-header">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-800">Trainer Documents</h3>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                                        <i class="fas fa-upload mr-2"></i> Upload
                                    </button>
                                </div>
                            </div>
                            <div class="p-6">
                                <?php if (isset($_SESSION['success_message'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                                        <?= $_SESSION['success_message'] ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                    <?php unset($_SESSION['success_message']); ?>
                                <?php endif; ?>
                                
                                <?php if (isset($_SESSION['error_message'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                                        <?= $_SESSION['error_message'] ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                    <?php unset($_SESSION['error_message']); ?>
                                <?php endif; ?>
                                
                                <?php if (empty($documents)): ?>
                                    <div class="text-center py-8">
                                        <div class="animate-float inline-block mb-4">
                                            <i class="fas fa-folder-open text-4xl text-gray-300"></i>
                                        </div>
                                        <h4 class="text-lg font-medium text-gray-500">No documents uploaded yet</h4>
                                        <p class="text-gray-400 mt-1">Upload documents to showcase certifications, resumes, and other credentials.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <?php foreach ($documents as $doc): ?>
                                            <div class="document-card p-4">
                                                <span class="document-type-badge badge bg-primary">
                                                    <?= ucfirst(str_replace('_', ' ', $doc['document_type'])) ?>
                                                </span>
                                                
                                                <div class="text-center mb-3">
                                                    <?php 
                                                        $icon = 'fa-file';
                                                        if (strpos($doc['file_path'], '.pdf') !== false) $icon = 'fa-file-pdf';
                                                        elseif (strpos($doc['file_path'], '.jpg') !== false || strpos($doc['file_path'], '.jpeg') !== false || strpos($doc['file_path'], '.png') !== false) $icon = 'fa-file-image';
                                                        elseif (strpos($doc['file_path'], '.doc') !== false) $icon = 'fa-file-word';
                                                    ?>
                                                    <i class="fas <?= $icon ?> document-icon mb-2"></i>
                                                    <h5 class="mb-1 font-medium"><?= ucfirst(str_replace('_', ' ', $doc['document_type'])) ?></h5>
                                                    <small class="text-muted">Uploaded: <?= date('M j, Y', strtotime($doc['uploaded_at'])) ?></small>
                                                </div>
                                                
                                                <div class="document-actions text-center">
                                                    <a href="<?= $doc['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye mr-1"></i> View
                                                    </a>
                                                    <a href="<?= $doc['file_path'] ?>" download class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-download mr-1"></i> Download
                                                    </a>
                                                    <form action="" method="POST" class="d-inline">
                                                        <input type="hidden" name="document_id" value="<?= $doc['document_id'] ?>">
                                                        <button type="submit" name="delete_document" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this document?')">
                                                            <i class="fas fa-trash mr-1"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Upload Document Modal -->
    <div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-labelledby="uploadDocumentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadDocumentModalLabel">Upload Document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="document_type" class="form-label">Document Type</label>
                            <select class="form-select" id="document_type" name="document_type" required>
                                <option value="">Select document type</option>
                                <option value="resume">Resume/CV</option>
                                <option value="certification">Certification</option>
                                <option value="degree">Degree</option>
                                <option value="id_proof">ID Proof</option>
                                <option value="other">Other Document</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="document_file" class="form-label">Document File</label>
                            <input class="form-control" type="file" id="document_file" name="document_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <small class="text-muted">Allowed formats: PDF, JPG, JPEG, PNG, DOC, DOCX</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="upload_document" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/admin.js"></script>
    <script>
        // Intersection Observer for fade-in animations
        const fadeInSections = document.querySelectorAll('.fade-in-section');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, {
        threshold: 0.1
    });
    
    fadeInSections.forEach(section => {
        observer.observe(section);
    });
    
    // Add delay to each card for staggered animation
    document.querySelectorAll('.fade-in-section').forEach((el, index) => {
        el.style.transitionDelay = `${index * 0.1}s`;
    });
    
    // Initialize Bootstrap modal
    document.addEventListener('DOMContentLoaded', function() {
        // Show modal if there was an error or if we need to show it
        <?php if (isset($_SESSION['show_upload_modal'])): ?>
            var uploadModal = new bootstrap.Modal(document.getElementById('uploadDocumentModal'));
            uploadModal.show();
            <?php unset($_SESSION['show_upload_modal']); ?>
        <?php endif; ?>
        
        // Handle modal dismiss to clear any form data
        document.getElementById('uploadDocumentModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });
    });
    </script>
</body>
</html>