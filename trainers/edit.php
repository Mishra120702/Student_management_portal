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
$errors = [];
$success = false;

// Fetch trainer data
$stmt = $db->prepare("SELECT t.*, u.email 
                       FROM trainers t 
                       JOIN users u ON t.user_id = u.id 
                       WHERE t.id = ?");
$stmt->bind_param('i', $trainerId);
$stmt->execute();
$result = $stmt->get_result();
$trainer = $result->fetch_assoc();

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
        $stmt->bind_param('si', $email, $trainer['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = 'Email already exists for another user';
        } else {
            // Update records in a transaction
            $db->begin_transaction();
            
            try {
                // Update user email
                $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->bind_param('si', $email, $trainer['user_id']);
                $stmt->execute();
                
                // Update trainer
                $stmt = $db->prepare("UPDATE trainers 
                                      SET name = ?, specialization = ?, years_of_experience = ?, 
                                          bio = ?, is_active = ?, updated_at = NOW() 
                                      WHERE id = ?");
                $stmt->bind_param('ssisii', $name, $specialization, $experience, $bio, $isActive, $trainerId);
                $stmt->execute();
                
                $db->commit();
                $success = true;
                
                // Refresh trainer data
                $stmt = $db->prepare("SELECT t.*, u.email 
                                      FROM trainers t 
                                      JOIN users u ON t.user_id = u.id 
                                      WHERE t.id = ?");
                $stmt->bind_param('i', $trainerId);
                $stmt->execute();
                $result = $stmt->get_result();
                $trainer = $result->fetch_assoc();
                
                // Show success message
                $_SESSION['success_message'] = 'Trainer updated successfully';
            } catch (Exception $e) {
                $db->rollback();
                $errors[] = 'Failed to update trainer: ' . $e->getMessage();
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
    <title>Edit Trainer | ASD Academy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <?php include '../../includes/admin-nav.php'; ?>
    
    <div class="admin-container">
        <?php include '../../includes/admin-sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="page-title">Edit Trainer</h1>
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
                <?php elseif (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success animate__animated animate__fadeIn">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <div class="card animate__animated animate__fadeIn">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?= htmlspecialchars($trainer['name']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?= htmlspecialchars($trainer['email']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="specialization" class="form-label">Specialization</label>
                                        <input type="text" class="form-control" id="specialization" name="specialization" 
                                               value="<?= htmlspecialchars($trainer['specialization']) ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="experience" class="form-label">Years of Experience</label>
                                        <input type="number" class="form-control" id="experience" name="experience" 
                                               value="<?= htmlspecialchars($trainer['years_of_experience']) ?>" min="0">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="bio" class="form-label">Bio</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="3"><?= htmlspecialchars($trainer['bio']) ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                               <?= $trainer['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">Active Trainer</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-save me-1"></i> Save Changes
                                </button>
                                <a href="view.php?id=<?= $trainerId ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-eye me-1"></i> View Profile
                                </a>
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