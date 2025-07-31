<?php
require_once '../db_connection.php';
require_once 'functions.php';

// Check admin permissions
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../unauthorized.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$trainerId = (int)$_GET['id'];
$errors = [];
$success = false;

// Fetch trainer data
$stmt = $db->prepare("SELECT t.*, u.email 
                       FROM trainers t 
                       JOIN users u ON t.user_id = u.id 
                       WHERE t.id = ?");
$stmt->execute([$trainerId]);
$trainer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trainer) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and process form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $experience = (int)($_POST['experience'] ?? 0);
    $bio = trim($_POST['bio'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Basic validation
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if ($experience < 0) $errors[] = 'Experience cannot be negative';

    if (empty($errors)) {
        // Check if email already exists for another user
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $trainer['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $errors[] = 'Email already exists for another user';
        } else {
            // Update records in a transaction
            $db->beginTransaction();
            
            try {
                // Update user email
                $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$email, $trainer['user_id']]);
                
                // Update trainer
                $stmt = $db->prepare("UPDATE trainers 
                                      SET name = ?, specialization = ?, years_of_experience = ?, 
                                          bio = ?, is_active = ?, updated_at = NOW() 
                                      WHERE id = ?");
                $stmt->execute([$name, $specialization, $experience, $bio, $isActive, $trainerId]);
                
                $db->commit();
                $success = true;
                
                // Refresh trainer data
                $stmt = $db->prepare("SELECT t.*, u.email 
                                      FROM trainers t 
                                      JOIN users u ON t.user_id = u.id 
                                      WHERE t.id = ?");
                $stmt->execute([$trainerId]);
                $trainer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Show success message
                $_SESSION['success_message'] = 'Trainer updated successfully';
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Failed to update trainer: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Trainer | ASD Academy</title>
    
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <link rel="stylesheet" href="../assets/css/tailwind.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    
    <style>
        :root {
            --primary: #3b82f6;
            --primary-light: #93c5fd;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #6366f1;
            --dark: #1f2937;
            --light: #f3f4f6;
        }
        
        .animate__animated {
            animation-duration: 0.5s;
        }
        
        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background-color: white;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-check-input {
            width: 1rem;
            height: 1rem;
            margin-top: 0.15rem;
            margin-right: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.25rem;
            transition: all 0.2s;
        }
        
        .form-check-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: 1px solid var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            color: white;
        }
        
        .btn-outline-secondary {
            background-color: white;
            color: var(--dark);
            border: 1px solid #d1d5db;
        }
        
        .btn-outline-secondary:hover {
            background-color: #f3f4f6;
            transform: translateY(-1px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            color: var(--dark);
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }
        
        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border-left: 4px solid var(--success);
        }
        
        .alert i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }
        
        .trainer-avatar {
            width: 100px;
            height: 100px;
            border-radius: 0.5rem;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .trainer-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        
        .loading-spinner {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        .loading-spinner.active {
            display: flex;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 50rem;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        
        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .floating-label-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .floating-label {
            position: absolute;
            top: 0.5rem;
            left: 0.75rem;
            font-size: 0.75rem;
            color: #6b7280;
            background: white;
            padding: 0 0.25rem;
            transition: all 0.2s;
            pointer-events: none;
            transform-origin: left center;
        }
        
        .form-control:focus ~ .floating-label,
        .form-control:not(:placeholder-shown) ~ .floating-label {
            transform: translateY(-1rem) scale(0.85);
            color: var(--primary);
        }
        
        .form-control:focus ~ .floating-label {
            color: var(--primary);
        }
        
        .animate-bounce {
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% {
                transform: translateY(-5px);
            }
            50% {
                transform: translateY(5px);
            }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
            }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <?php include '../header.php'; ?>
    
    <!-- Loading Spinner -->
    <div class="loading-spinner">
        <div class="spinner"></div>
        <div class="text-gray-600">Processing...</div>
    </div>
    
    <div class="flex-1 ml-0 md:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
            <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fas fa-user-edit text-blue-500"></i>
                <span>Edit Trainer</span>
            </h1>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
            </div>
        </header>

        <div class="p-4 md:p-6">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger animate__animated animate__shakeX">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Error!</strong>
                        <ul class="mt-1 ml-4 list-disc">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php elseif (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success animate__animated animate__fadeIn">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Success!</strong> <?= htmlspecialchars($_SESSION['success_message']) ?>
                    </div>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="card animate__animated animate__fadeIn">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-user-edit text-blue-500 mr-2"></i>
                                Trainer Information
                            </h2>
                            <span class="badge <?= $trainer['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $trainer['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="trainerForm">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="floating-label-group">
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?= htmlspecialchars($trainer['name']) ?>" 
                                               placeholder=" " required>
                                        <label class="floating-label">Full Name</label>
                                    </div>
                                    
                                    <div class="floating-label-group">
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?= htmlspecialchars($trainer['email']) ?>" 
                                               placeholder=" " required>
                                        <label class="floating-label">Email Address</label>
                                    </div>
                                    
                                    <div class="floating-label-group">
                                        <input type="text" class="form-control" id="specialization" name="specialization" 
                                               value="<?= htmlspecialchars($trainer['specialization']) ?>" 
                                               placeholder=" ">
                                        <label class="floating-label">Specialization</label>
                                    </div>
                                    
                                    <div class="floating-label-group">
                                        <input type="number" class="form-control" id="experience" name="experience" 
                                               value="<?= htmlspecialchars($trainer['years_of_experience']) ?>" 
                                               min="0" placeholder=" ">
                                        <label class="floating-label">Years of Experience</label>
                                    </div>
                                    
                                    <div class="md:col-span-2 floating-label-group">
                                        <textarea class="form-control" id="bio" name="bio" rows="3" 
                                                  placeholder=" "><?= htmlspecialchars($trainer['bio']) ?></textarea>
                                        <label class="floating-label">Bio/Description</label>
                                    </div>
                                    
                                    <div class="md:col-span-2 flex items-center">
                                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                               <?= $trainer['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label ml-2" for="is_active">Mark as Active Trainer</label>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end mt-6 space-x-3">
                                    <a href="view.php?id=<?= $trainerId ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-eye mr-2"></i> View Profile
                                    </a>
                                    <button type="submit" class="btn btn-primary pulse">
                                        <i class="fas fa-save mr-2"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="card animate__animated animate__fadeIn animate__delay-1s">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-id-card text-blue-500 mr-2"></i>
                                Trainer Photo
                            </h2>
                        </div>
                        <div class="card-body flex flex-col items-center">
                            <img src="<?= getTrainerPhoto($trainer) ?>" 
                                 class="trainer-avatar animate-bounce" 
                                 alt="<?= htmlspecialchars($trainer['name']) ?>"
                                 onerror="this.src='../assets/images/default-avatar.svg'">
                            <button class="btn btn-outline-secondary mt-2">
                                <i class="fas fa-camera mr-2"></i> Change Photo
                            </button>
                            <p class="text-sm text-gray-500 mt-3 text-center">
                                Recommended size: 500x500px<br>
                                Max file size: 2MB
                            </p>
                        </div>
                    </div>
                    
                    <div class="card animate__animated animate__fadeIn animate__delay-2s mt-6">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-chart-line text-blue-500 mr-2"></i>
                                Performance Stats
                            </h2>
                        </div>
                        <div class="card-body">
                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium text-gray-700">Rating</span>
                                        <span class="text-sm font-bold text-blue-600">4.8/5</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: 96%"></div>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium text-gray-700">Active Batches</span>
                                        <span class="text-sm font-bold text-blue-600">3</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-green-500 h-2.5 rounded-full" style="width: 60%"></div>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium text-gray-700">Completion Rate</span>
                                        <span class="text-sm font-bold text-blue-600">92%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-purple-500 h-2.5 rounded-full" style="width: 92%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize loading spinner
        const loadingSpinner = $('.loading-spinner');
        
        // Show loading spinner on form submission
        $('#trainerForm').on('submit', function() {
            loadingSpinner.addClass('active');
        });
        
        // Hide loading spinner when page is fully loaded
        $(window).on('load', function() {
            setTimeout(function() {
                loadingSpinner.removeClass('active');
            }, 500);
        });
        
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Sidebar toggle for mobile
        function toggleSidebar() {
            $('.sidebar').toggleClass('hidden');
        }
        
        // Add animation to form elements on focus
        $('.form-control').on('focus', function() {
            $(this).parent().addClass('animate__animated animate__pulse');
        }).on('blur', function() {
            $(this).parent().removeClass('animate__animated animate__pulse');
        });
        
        // Confirmation before leaving page with unsaved changes
        let formChanged = false;
        $('input, textarea, select').on('change keyup', function() {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        // Success message animation
        <?php if ($success): ?>
            Swal.fire({
                title: 'Success!',
                text: 'Trainer updated successfully',
                icon: 'success',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
        <?php endif; ?>
    });
    </script>
</body>
</html>