<?php
    
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    echo "Access denied. You do not have permission to view this page.";
    exit;
}

?><!-- sidebar.php -->
<div id="sidebar" class="w-64 bg-white border-r h-screen fixed transform transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 z-40">
    <div class="p-6 text-xl font-bold text-blue-600 border-b flex items-center space-x-2">
        <i class="fas fa-graduation-cap"></i>
       <a href="dashboard.php"> <span>ASD Admin</span></a>
    </div>
    <nav class="flex flex-col p-4 space-y-1">
        <a href="../dashboard/dashboard.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt fa-log"></i>
            <span>Dashboard</span>
        </a>
        <a href="../batch/batch_list.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'batch_list.php' ? 'active' : '' ?>">
            <i class="fas fa-users fa-log"></i>
            <span>Batch Management</span>
        </a>
        <a href="../trainers/index.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == '../trainers/index.php' ? 'active' : '' ?>">
            <i class="fas fa-trainer fa-log"></i>
            <span>Trainers</span>
        </a>
        <a href="../student/students_list.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'students_list.php' ? 'active' : '' ?>">
            <i class="fas fa-user-graduate fa-log"></i>
            <span>Student Management</span>
        </a>
        <a href="../attendance/attendance.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : '' ?>">
            <i class="fas fa-clipboard-check fa-log"></i>
            <span>Attendance</span>
        </a>
        <a href="../exam/exams.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'exams.php' ? 'active' : '' ?>">
            <i class="fas fa-file-alt fa-log"></i>
            <span>Exams</span>
        </a>
        <a href="../workshops/workshop_list.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == '../workshops/workshop_list.php' && basename(dirname($_SERVER['PHP_SELF'])) == 'workshops' ? 'active' : '' ?>">
            <i class="fas fa-file-alt fa-log"></i>
            <span>Workshops</span>
        </a>
        <a href="../content/upload_content.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'upload_content.php' ? 'active' : '' ?>">
            <i class="fas fa-book fa-log"></i>
            <span>Content</span>
        </a>
        <a href="../reports/index.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500">
            <i class="fas fa-chart-bar fa-log"></i>
            <span>Reporting</span>
        </a>
        <a href="../chat/index.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) == 'chat' ? 'active' : '' ?>">
            <i class="fas fa-comments fa-log"></i>
            <span>Chat</span>
        </a>
        <a href="../feedback/feedback.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : '' ?>">
            <i class="fas fa-cog fa-log"></i>
            <span>Feedback</span>
        </a>
        <a href="../dashboard/admin_settings.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'admin_settings.php' ? 'active' : '' ?>">
            <i class="fas fa-cog fa-log"></i>
            <span>Settings</span>
        </a>
    </nav>
</div>