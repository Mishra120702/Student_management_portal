<?php
    
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    echo "Access denied. You do not have permission to view this page.";
    exit;
}

?><!-- Enhanced Scrollable Sidebar -->
<div id="sidebar" class="w-64 bg-white border-r h-screen fixed transform transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 z-40 shadow-lg">
    <!-- Sidebar Header -->
    <div class="p-6 text-xl font-bold text-blue-600 border-b flex items-center space-x-2 bg-blue-50">
        <i class="fas fa-graduation-cap text-2xl"></i>
        <a href="dashboard.php" class="hover:text-blue-700 transition-colors duration-200">
            <span>ASD Admin</span>
        </a>
        <button id="sidebarToggle" class="md:hidden ml-auto text-gray-500 hover:text-blue-600">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="flex flex-col p-4 space-y-2 overflow-y-auto scrollbar-hide" style="max-height: calc(100vh - 80px)">
        <!-- Dashboard -->
        <a href="../dashboard/dashboard.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-tachometer-alt w-5 text-center"></i>
            <span>Dashboard</span>
        </a>
        
        <!-- User Management Group -->
        <div class="mt-4 mb-2 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">User Management</div>
        
        <a href="../batch/batch_list.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'batch_list.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-users w-5 text-center"></i>
            <span>Batch Management</span>
        </a>
        
        <a href="../trainers/index.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == '../trainers/index.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-chalkboard-teacher w-5 text-center"></i>
            <span>Trainers</span>
        </a>
        
        <a href="../student/students_list.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'students_list.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-user-graduate w-5 text-center"></i>
            <span>Student Management</span>
        </a>
        
        <!-- Academic Group -->
        <div class="mt-4 mb-2 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Academic</div>
        
        <a href="../attendance/attendance.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-clipboard-check w-5 text-center"></i>
            <span>Attendance</span>
        </a>
        
        <a href="../exam/exams.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'exams.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-file-alt w-5 text-center"></i>
            <span>Exams</span>
        </a>
        
        <a href="../workshops/workshop_list.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == '../workshops/workshop_list.php' && basename(dirname($_SERVER['PHP_SELF'])) == 'workshops' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-tools w-5 text-center"></i>
            <span>Workshops</span>
        </a>
        
        <a href="../content/upload_content.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'upload_content.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-book w-5 text-center"></i>
            <span>Content</span>
        </a>
        
        <!-- Communication Group -->
        <div class="mt-4 mb-2 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Communication</div>
        
        <a href="../chat/index.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) == 'chat' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-comments w-5 text-center"></i>
            <span>Chat</span>
            <span class="ml-auto bg-blue-100 text-blue-800 text-xs font-medium px-2 py-0.5 rounded-full">New</span>
        </a>
        
        <a href="../feedback/feedback.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-comment-dots w-5 text-center"></i>
            <span>Feedback</span>
        </a>
        
        <!-- System Group -->
        <div class="mt-4 mb-2 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">System</div>
        
        <a href="../reports/index.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">
            <i class="fas fa-chart-bar w-5 text-center"></i>
            <span>Reporting</span>
        </a>
        
        <a href="../dashboard/admin_settings.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 <?= basename($_SERVER['PHP_SELF']) == 'admin_settings.php' ? 'bg-blue-100 text-blue-600' : '' ?>">
            <i class="fas fa-cog w-5 text-center"></i>
            <span>Settings</span>
        </a>
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="absolute bottom-0 w-full p-4 border-t bg-white">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                <i class="fas fa-user text-blue-600"></i>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-700"><?= $_SESSION['username'] ?? 'Admin' ?></div>
                <div class="text-xs text-gray-500">Administrator</div>
            </div>
        </div>
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
</script>