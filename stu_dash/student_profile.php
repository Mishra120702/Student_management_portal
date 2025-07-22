<?php
session_start();
require_once '../db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get student information
$student_id = $_SESSION['user_id'];
$student_query = $db->prepare("
    SELECT s.*, b.batch_id, b.course_name, b.start_date, b.end_date, b.time_slot, b.mode
    FROM students s
    JOIN batches b ON s.batch_name = b.batch_id
    WHERE s.user_id = :user_id
");
$student_query->execute([':user_id' => $student_id]);
$student = $student_query->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student information not found");
}

// Get attendance stats
$attendance_query = $db->prepare("
    SELECT 
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
        COUNT(*) as total_attendance
    FROM attendance 
    WHERE student_name = :student_name AND batch_id = :batch_id
");
$attendance_query->execute([
    ':student_name' => $student['first_name'] . ' ' . $student['last_name'],
    ':batch_id' => $student['batch_id']
]);
$attendance = $attendance_query->fetch(PDO::FETCH_ASSOC);

// Calculate attendance percentage
$attendance_percentage = $attendance['total_attendance'] > 0 
    ? round(($attendance['present_count'] / $attendance['total_attendance']) * 100) 
    : 0;
?>

<?php include '../header.php'; ?>
<?php include '../s_sidebar.php'; ?>

<!-- Main Content -->
<div class="flex-1 ml-0 md:ml-64 min-h-screen transition-all duration-300 ease-in-out">
    <!-- Header -->
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30 transition-all duration-300 ease-in-out">
        <button class="md:hidden text-xl text-gray-600 hover:text-blue-600 transition-colors duration-200" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-user-circle text-blue-500 transition-transform duration-500 hover:rotate-360"></i>
            <span>My Profile</span>
        </h1>
    </header>

    <div class="p-4 md:p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profile Info -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg transform transition-all duration-500 hover:shadow-xl hover:-translate-y-1">
                <div class="flex flex-col md:flex-row items-start md:items-center gap-6 mb-6">
                    <!-- Profile Picture -->
                    <div class="relative group">
                        <div class="relative transition-all duration-300 group-hover:scale-105">
                            <?php if (!empty($student['profile_picture']) && file_exists($student['profile_picture'])): ?>
                                <img src="<?= htmlspecialchars($student['profile_picture']) ?>" 
                                     alt="Profile Picture" 
                                     class="w-32 h-32 rounded-full object-cover border-4 border-blue-100 shadow-md transition-all duration-300 hover:border-blue-300">
                            <?php else: ?>
                                <div class="w-32 h-32 rounded-full bg-blue-100 flex items-center justify-center shadow-md transition-all duration-300 hover:bg-blue-200">
                                    <i class="fas fa-user text-5xl text-blue-500 transition-transform duration-500 hover:scale-110"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Basic Info -->
                    <div class="animate-fade-in">
                        <h2 class="text-2xl font-bold text-gray-800 transition-colors duration-300 hover:text-blue-600">
                            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                        </h2>
                        <p class="text-gray-600 mb-2 transition-colors duration-300 hover:text-gray-800">
                            <?= htmlspecialchars($student['student_id']) ?>
                        </p>
                        
                        <div class="flex space-x-4">
                            <div class="text-center p-3 bg-blue-50 rounded-lg transition-all duration-300 hover:bg-blue-100 hover:shadow">
                                <span class="font-bold block text-blue-600"><?= $attendance['present_count'] ?>/<?= $attendance['total_attendance'] ?></span>
                                <span class="text-gray-500 text-sm">Attendance</span>
                            </div>
                            <div class="text-center p-3 bg-green-50 rounded-lg transition-all duration-300 hover:bg-green-100 hover:shadow">
                                <span class="font-bold block text-green-600"><?= $attendance_percentage ?>%</span>
                                <span class="text-gray-500 text-sm">Attendance Rate</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Personal Info -->
                    <div class="animate-slide-in-left">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2 flex items-center">
                            <i class="fas fa-user-tag mr-2 text-blue-500"></i>
                            Personal Information
                        </h3>
                        <div class="space-y-3">
                            <p class="flex items-center transition-colors duration-300 hover:text-blue-600">
                                <i class="fas fa-envelope text-gray-400 mr-2 w-5"></i>
                                <span class="text-gray-600 w-24">Email:</span> 
                                <span class="font-medium"><?= htmlspecialchars($student['email']) ?></span>
                            </p>
                            <p class="flex items-center transition-colors duration-300 hover:text-blue-600">
                                <i class="fas fa-phone text-gray-400 mr-2 w-5"></i>
                                <span class="text-gray-600 w-24">Phone:</span> 
                                <span class="font-medium"><?= htmlspecialchars($student['phone_number'] ?? 'Not set') ?></span>
                            </p>
                            <p class="flex items-center transition-colors duration-300 hover:text-blue-600">
                                <i class="fas fa-birthday-cake text-gray-400 mr-2 w-5"></i>
                                <span class="text-gray-600 w-24">Date of Birth:</span> 
                                <span class="font-medium"><?= $student['date_of_birth'] ? date('M j, Y', strtotime($student['date_of_birth'])) : 'Not set' ?></span>
                            </p>
                            <p class="flex items-center transition-colors duration-300 hover:text-blue-600">
                                <i class="fas fa-calendar-plus text-gray-400 mr-2 w-5"></i>
                                <span class="text-gray-600 w-24">Enrollment Date:</span> 
                                <span class="font-medium"><?= date('M j, Y', strtotime($student['enrollment_date'])) ?></span>
                            </p>
                        </div>
                    </div>

                    <!-- Academic Info -->
                    <div class="animate-slide-in-right">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2 flex items-center">
                            <i class="fas fa-graduation-cap mr-2 text-blue-500"></i>
                            Academic Information
                        </h3>
                        <div class="space-y-3">
                            <p class="flex items-center transition-colors duration-300 hover:text-blue-600">
                                <i class="fas fa-users text-gray-400 mr-2 w-5"></i>
                                <span class="text-gray-600 w-24">Batch:</span> 
                                <span class="font-medium"><?= htmlspecialchars($student['batch_id']) ?></span>
                            </p>
                            <p class="flex items-center transition-colors duration-300 hover:text-blue-600">
                                <i class="fas fa-book text-gray-400 mr-2 w-5"></i>
                                <span class="text-gray-600 w-24">Course:</span> 
                                <span class="font-medium"><?= htmlspecialchars($student['course_name']) ?></span>
                            </p>
                            <p class="flex items-center transition-colors duration-300 hover:text-blue-600">
                                <i class="fas fa-clock text-gray-400 mr-2 w-5"></i>
                                <span class="text-gray-600 w-24">Schedule:</span> 
                                <span class="font-medium"><?= htmlspecialchars($student['time_slot']) ?> (<?= ucfirst($student['mode']) ?>)</span>
                            </p>
                            <p class="flex items-center transition-colors duration-300 hover:text-blue-600">
                                <i class="fas fa-calendar-alt text-gray-400 mr-2 w-5"></i>
                                <span class="text-gray-600 w-24">Duration:</span> 
                                <span class="font-medium"><?= date('M j, Y', strtotime($student['start_date'])) ?> - <?= date('M j, Y', strtotime($student['end_date'])) ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View Only Form -->
            <div class="bg-white p-6 rounded-xl shadow-lg transform transition-all duration-500 hover:shadow-xl hover:-translate-y-1">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Profile Details
                </h2>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-1">Phone Number</label>
                    <div class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-md">
                        <?= htmlspecialchars($student['phone_number'] ?? 'Not provided') ?>
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b pb-2 flex items-center">
                    <i class="fas fa-users text-blue-500 mr-2"></i>
                    Parent Information
                </h3>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-1">Father's Name</label>
                    <div class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-md">
                        <?= htmlspecialchars($student['father_name'] ?? 'Not provided') ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 mb-1">Father's Phone</label>
                    <div class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-md">
                        <?= htmlspecialchars($student['father_phone_number'] ?? 'Not provided') ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 mb-1">Father's Email</label>
                    <div class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-md">
                        <?= htmlspecialchars($student['father_email'] ?? 'Not provided') ?>
                    </div>
                </div>

                <!-- View Only Password Section -->
                <div class="mt-6 pt-6 border-t">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-lock text-blue-500 mr-2"></i>
                        Password Information
                    </h3>
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <p class="text-blue-800 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            To change your password, please contact the administration.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideInLeft {
        from { transform: translateX(-20px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideInRight {
        from { transform: translateX(20px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .animate-fade-in {
        animation: fadeIn 0.6s ease-out forwards;
    }
    
    .animate-slide-in-left {
        animation: slideInLeft 0.6s ease-out forwards;
    }
    
    .animate-slide-in-right {
        animation: slideInRight 0.6s ease-out forwards;
    }
    
    .attendance-progress {
        width: 100%;
        height: 8px;
        background-color: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6, #10b981);
        border-radius: 4px;
        transition: width 1s ease-in-out;
    }
</style>

<script>
    // Animate progress bars on page load
    document.addEventListener('DOMContentLoaded', function() {
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(bar => {
            const targetWidth = bar.getAttribute('data-percent');
            bar.style.width = targetWidth + '%';
        });
        
        // Add hover effect to all cards
        const cards = document.querySelectorAll('.shadow-lg');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.classList.add('shadow-xl', '-translate-y-1');
            });
            card.addEventListener('mouseleave', () => {
                card.classList.remove('shadow-xl', '-translate-y-1');
            });
        });
    });
</script>

<?php include '../footer.php'; ?>