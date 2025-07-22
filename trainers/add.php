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
                header("Location: view.php?id=" . $db->insert_id);
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
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="admin-container">
        <?php// include '../sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="page-title">Add New Trainer</h1>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger animate__animated animate__shakeX">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="card animate__animated animate__fadeIn">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="specialization" class="form-label">Specialization</label>
                                        <input type="text" class="form-control" id="specialization" name="specialization" 
                                               value="<?= htmlspecialchars($_POST['specialization'] ?? '') ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="experience" class="form-label">Years of Experience</label>
                                        <input type="number" class="form-control" id="experience" name="experience" 
                                               value="<?= htmlspecialchars($_POST['experience'] ?? 0) ?>" min="0">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="bio" class="form-label">Bio</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="3"><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                               <?= isset($_POST['is_active']) ? 'checked' : 'checked' ?>>
                                        <label class="form-check-label" for="is_active">Active Trainer</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Trainer
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
</body>
</html>