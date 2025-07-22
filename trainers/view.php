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
                            <i class="fas fa-edit mr-2"></i> Edit Profile
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
                    </div>
                </div>
            </div>
        </main>
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
    </script>
</body>
</html>