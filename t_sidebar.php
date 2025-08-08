
<!-- Enhanced Trainer Sidebar -->
<div id="sidebar" class="w-64 bg-white border-r h-screen fixed transform transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 z-40 shadow-lg">
    <!-- Sidebar Header -->
    <div class="p-6 text-xl font-bold text-blue-600 border-b flex items-center space-x-2 bg-blue-50">
        <i class="fas fa-chalkboard-teacher text-2xl"></i>
        <a href="dashboard.php" class="hover:text-blue-700 transition-colors duration-200">
            <span>ASD Trainer Portal</span>
        </a>
        <button id="sidebarToggle" class="md:hidden ml-auto text-gray-500 hover:text-blue-600">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <!-- User Profile Quick View -->
    <div class="p-4 border-b flex items-center space-x-3 bg-gray-50">
        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
            <i class="fas fa-user"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Trainer') ?></p>
            <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></p>
        </div>
    </div>
    
    <!-- Main Navigation -->
    <nav class="flex flex-col p-4 space-y-2 overflow-y-auto scrollbar-hide" style="max-height: calc(100vh - 160px)">
        <!-- Teaching Section -->
        <div class="mt-4 mb-2 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Teaching</div>
        <a href="../trainer_dash/dashboard.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-tachometer-alt w-5 text-center"></i>
            <span>Dashboard</span>
        </a>
        <a href="../trainer_dash/my_batches.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'my_batches.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-users w-5 text-center"></i>
            <span>My Batches</span>
            <span class="ml-auto bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full"><?= getBatchCount() ?></span>
        </a>
        <a href="../trainer_dash/schedule.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-calendar-alt w-5 text-center"></i>
            <span>Schedule</span>
            <?php if (hasUpcomingSession()): ?>
                <span class="ml-auto w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
            <?php endif; ?>
        </a>
        
        <!-- Student Management Section -->
        <div class="mt-4 mb-2 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Student Management</div>
        <a href="../trainer_dash/attendance.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-clipboard-check w-5 text-center"></i>
            <span>Attendance</span>
        </a>
        <a href="../trainer_dash/student_progress.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'student_progress.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-chart-line w-5 text-center"></i>
            <span>Student Progress</span>
        </a>
        <a href="../trainer_dash/feedback.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-comment-dots w-5 text-center"></i>
            <span>Feedback</span>
            <?php if (hasNewFeedback()): ?>
                <span class="ml-auto bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded-full">New</span>
            <?php endif; ?>
        </a>
        
        <!-- Content Section -->
        <div class="mt-4 mb-2 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Content</div>
        <a href="../trainer_dash/content.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'content.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-book w-5 text-center"></i>
            <span>Content</span>
        </a>
        <a href="../trainer_dash/assessments.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'assessments.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-file-alt w-5 text-center"></i>
            <span>Assessments</span>
            <?php if (hasPendingAssessments()): ?>
                <span class="ml-auto bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded-full">Pending</span>
            <?php endif; ?>
        </a>
        
        <!-- Communication Section -->
        <div class="mt-4 mb-2 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Communication</div>
        <a href="../trainer_chat/index.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= (basename($_SERVER['PHP_SELF']) == 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) == 'trainer_chat') ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-comments w-5 text-center"></i>
            <span>Chat</span>
            <?php if (hasUnreadMessages()): ?>
                <span class="ml-auto bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full"><?= getUnreadMessageCount() ?></span>
            <?php endif; ?>
        </a>
    </nav>
    
    <!-- Bottom Section -->
    <div class="absolute bottom-0 w-full p-4 border-t bg-white">
        <a href="../trainer_dash/profile.php" class="sidebar-link py-2 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-user-circle w-5 text-center"></i>
            <span>My Profile</span>
        </a>
        <a href="../logout.php" class="sidebar-link py-2 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors duration-200 mt-2">
            <i class="fas fa-sign-out-alt w-5 text-center"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Mobile Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden md:hidden"></div>

<style>
    /* Hide scrollbar but keep functionality */
    .scrollbar-hide {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
    .scrollbar-hide::-webkit-scrollbar {
        display: none;  /* Chrome, Safari and Opera */
    }
    
    /* Mobile Toggle Button */
    .mobile-toggle {
        position: fixed;
        bottom: 1rem;
        right: 1rem;
        z-index: 50;
    }
    
    @media (min-width: 768px) {
        .mobile-toggle {
            display: none;
        }
    }
</style>

<script>
// Toggle sidebar on mobile
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
    document.getElementById('sidebarOverlay').classList.toggle('hidden');
});

// Close sidebar when clicking overlay
document.getElementById('sidebarOverlay').addEventListener('click', function() {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    this.classList.add('hidden');
});

// Mobile toggle button (for when sidebar is completely hidden)
document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.createElement('button');
    mobileToggle.id = 'mobileSidebarToggle';
    mobileToggle.className = 'mobile-toggle p-3 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition-colors';
    mobileToggle.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.appendChild(mobileToggle);
    
    mobileToggle.addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
        document.getElementById('sidebarOverlay').classList.toggle('hidden');
    });
});
</script>