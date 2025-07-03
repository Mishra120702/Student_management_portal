<!-- sidebar.php -->
<div id="sidebar" class="w-64 bg-white border-r h-screen fixed transform transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 z-40">
    <div class="p-6 text-xl font-bold text-blue-600 border-b flex items-center space-x-2">
        <i class="fas fa-graduation-cap"></i>
        <span>ASD Admin</span>
    </div>
    <nav class="flex flex-col p-4 space-y-1">
        <a href="../dashboard/dashboard.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt w-5"></i>
            <span>Dashboard</span>
        </a>
        <a href="../batch/batch_list.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'batch_list.php' ? 'active' : '' ?>">
            <i class="fas fa-users w-5"></i>
            <span>Batch Management</span>
        </a>
        <a href="../student/students_list.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'students_list.php' ? 'active' : '' ?>">
            <i class="fas fa-user-graduate w-5"></i>
            <span>Student Management</span>
        </a>
        <a href="../attendance/attendance.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : '' ?>">
            <i class="fas fa-clipboard-check w-5"></i>
            <span>Attendance</span>
        </a>
        <a href="../exam/exams.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'exams.php' ? 'active' : '' ?>">
            <i class="fas fa-file-alt w-5"></i>
            <span>Exams</span>
        </a>
        <a href="#" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500">
            <i class="fas fa-book w-5"></i>
            <span>Content</span>
        </a>
        <a href="#" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500">
            <i class="fas fa-chart-bar w-5"></i>
            <span>Reporting</span>
        </a>
        <a href="#" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500">
            <i class="fas fa-comments w-5"></i>
            <span>Chat</span>
        </a>
        <a href="../feedback/feedback.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : '' ?>">
            <i class="fas fa-cog w-5"></i>
            <span>Feedback</span>
        </a>
    </nav>
</div>