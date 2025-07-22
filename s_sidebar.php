<!-- sidebar.php for Student Dashboard -->
<div id="sidebar" class="w-64 bg-white border-r h-screen fixed transform transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 z-40">
    <div class="p-6 text-xl font-bold text-blue-600 border-b flex items-center space-x-2">
        <i class="fas fa-graduation-cap"></i>
        <a href="dashboard.php"> <span>ASD Student</span></a>
    </div>
    <nav class="flex flex-col p-4 space-y-1">
        <a href="../stu_dash/dashboard.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'student_dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt w-5"></i>
            <span>Dashboard</span>
        </a>
        <a href="../stu_dash/my_batches.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'my_batches.php' ? 'active' : '' ?>">
            <i class="fas fa-users w-5"></i>
            <span>My Batches</span>
        </a>
        <a href="../stu_dash/upcoming.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'upcoming.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt w-5"></i>
            <span>Upcoming Schedule</span>
        </a>
        <a href="../stu_dash/my_content.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'my_content.php' ? 'active' : '' ?>">
            <i class="fas fa-book w-5"></i>
            <span>My Content</span>
        </a>
        <a href="../stu_dash/my_performance.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'my_performance.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line w-5"></i>
            <span>My Performance</span>
        </a>
        <a href="../stu_dash/student_feedback.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'student_feedback.php' ? 'active' : '' ?>">
            <i class="fas fa-comment-dots w-5"></i>
            <span>Feedback</span>
        </a>
        <a href="../stu_chat/index.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) == 'chat' ? 'active' : '' ?>">
            <i class="fas fa-comments w-5"></i>
            <span>Chat</span>
        </a>
        <a href="../stu_dash/student_profile.php" class="sidebar-link py-3 px-4 rounded-lg flex items-center space-x-3 hover:text-blue-500 <?= basename($_SERVER['PHP_SELF']) == 'student_profile.php' ? 'active' : '' ?>">
            <i class="fas fa-user-circle w-5"></i>
            <span>My Profile</span>
        </a>
    </nav>
</div>