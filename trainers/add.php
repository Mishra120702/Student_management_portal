<?php
require_once '../db_connection.php';
require_once 'functions.php';

// Check admin permissions
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$errors = [];
$success = false;

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
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = 'Email already exists in the system';
        } else {
            // Create user and trainer records in a transaction
            $db->begin_transaction();
            
            try {
                // Create user
                $password = bin2hex(random_bytes(8)); // Temporary password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'trainer')");
                $stmt->bind_param('ss', $email, $hashedPassword);
                $stmt->execute();
                $userId = $db->insert_id;
                
                // Create trainer
                $stmt = $db->prepare("INSERT INTO trainers (user_id, name, specialization, years_of_experience, bio, is_active) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('issisi', $userId, $name, $specialization, $experience, $bio, $isActive);
                $stmt->execute();
                
                $db->commit();
                $success = true;
                
                // TODO: Send welcome email with temporary password
                
                // Redirect to view page
                header("Location: view.php?id=" . $userId);
                exit;
            } catch (Exception $e) {
                $db->rollback();
                $errors[] = 'Failed to create trainer: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="dark-theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Trainer | ASD Academy</title>
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
        
        .card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
            position: relative;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .card:hover::before {
            left: 100%;
        }
        
        .card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .fade-in-section {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .fade-in-section.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Form styles */
        .form-control, .form-select {
            border-radius: 0.75rem;
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #f8fafc;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            background-color: white;
            transform: scale(1.02);
        }
        
        /* Alert styles */
        .alert {
            border-radius: 1rem;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            animation: slideInDown 0.5s ease;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }
        
        @keyframes slideInDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Button styles */
        .btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #3f37c9 0%, #4361ee 100%);
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline-secondary {
            border: 2px solid #e2e8f0;
            background: transparent;
            color: #4b5563;
        }
        
        .btn-outline-secondary:hover {
            background: #f3f4f6;
            transform: translateY(-2px);
        }
        
        /* Checkbox styles */
        .form-check-input {
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-check-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        /* Textarea styles */
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    
    <div class="admin-container">
        <?php include '../sidebar.php'; ?>
        
        <main class="admin-content bg-gray-50">
            <!-- Header Section -->
            <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30 rounded-2xl mb-6">
                <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                    <div class="p-2 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl text-white">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <span>Add New Trainer</span>
                </h1>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="btn btn-outline-secondary hover:bg-gray-100 transition-all duration-300 transform hover:scale-105 rounded-xl px-6 py-3">
                        <i class="fas fa-arrow-left mr-2"></i> Back to List
                    </a>
                </div>
            </header>
            
            <div class="container-fluid py-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger animate__animated animate__shakeX">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="card bg-white rounded-2xl shadow-sm overflow-hidden fade-in-section">
                    <div class="card-header bg-gradient-to-r from-blue-500 to-purple-600 text-white">
                        <h3 class="text-lg font-semibold flex items-center">
                            <i class="fas fa-user-plus mr-2"></i>
                            Trainer Information
                        </h3>
                    </div>
                    <div class="card-body p-6">
                        <form method="POST">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Left Column -->
                                <div class="space-y-4">
                                    <div class="form-group">
                                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fas fa-user mr-2 text-blue-500"></i>Full Name
                                        </label>
                                        <input type="text" class="form-control w-full" id="name" name="name" 
                                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fas fa-envelope mr-2 text-blue-500"></i>Email
                                        </label>
                                        <input type="email" class="form-control w-full" id="email" name="email" 
                                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="specialization" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fas fa-certificate mr-2 text-blue-500"></i>Specialization
                                        </label>
                                        <input type="text" class="form-control w-full" id="specialization" name="specialization" 
                                               value="<?= htmlspecialchars($_POST['specialization'] ?? '') ?>">
                                    </div>
                                </div>
                                
                                <!-- Right Column -->
                                <div class="space-y-4">
                                    <div class="form-group">
                                        <label for="experience" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fas fa-briefcase mr-2 text-blue-500"></i>Years of Experience
                                        </label>
                                        <input type="number" class="form-control w-full" id="experience" name="experience" 
                                               value="<?= htmlspecialchars($_POST['experience'] ?? 0) ?>" min="0">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fas fa-info-circle mr-2 text-blue-500"></i>Bio
                                        </label>
                                        <textarea class="form-control w-full" id="bio" name="bio" rows="4"><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="flex items-center">
                                            <input type="checkbox" class="form-check-input mr-3" id="is_active" name="is_active" 
                                                   <?= isset($_POST['is_active']) ? 'checked' : 'checked' ?>>
                                            <label class="form-check-label text-sm font-medium text-gray-700" for="is_active">
                                                <i class="fas fa-check-circle mr-2 text-blue-500"></i>Active Trainer
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end mt-6">
                                <button type="submit" class="btn btn-primary px-6 py-3 rounded-xl">
                                    <i class="fas fa-save mr-2"></i> Save Trainer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/admin.js"></script>
    <script>
        // Enhanced animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Intersection Observer for staggered animations
            const fadeInSections = document.querySelectorAll('.fade-in-section');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.classList.add('is-visible');
                        }, index * 100);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });
            
            fadeInSections.forEach(section => {
                observer.observe(section);
            });
            
            // Form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    let isValid = true;
                    
                    // Validate name
                    const nameInput = document.getElementById('name');
                    if (!nameInput.value.trim()) {
                        isValid = false;
                        nameInput.classList.add('border-red-500');
                    } else {
                        nameInput.classList.remove('border-red-500');
                    }
                    
                    // Validate email
                    const emailInput = document.getElementById('email');
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailInput.value.trim() || !emailRegex.test(emailInput.value)) {
                        isValid = false;
                        emailInput.classList.add('border-red-500');
                    } else {
                        emailInput.classList.remove('border-red-500');
                    }
                    
                    // Validate experience
                    const expInput = document.getElementById('experience');
                    if (expInput.value < 0) {
                        isValid = false;
                        expInput.classList.add('border-red-500');
                    } else {
                        expInput.classList.remove('border-red-500');
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                        
                        // Scroll to first error
                        const firstError = document.querySelector('.border-red-500');
                        if (firstError) {
                            firstError.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }
                    }
                });
            }
            
            // Add loading state to submit button
            const submitBtn = document.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.addEventListener('click', function() {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
                });
            }
            
            // Enhanced input focus effects
            document.querySelectorAll('.form-control, .form-select').forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('ring-2', 'ring-blue-500', 'rounded-lg');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('ring-2', 'ring-blue-500', 'rounded-lg');
                });
            });
        });
    </script>
</body>
</html>