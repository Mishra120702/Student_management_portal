<div class="flex mb-6 border-b border-gray-200">
    <a href="index.php" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-user mr-2"></i> Stundents
        </a>    
    <a href="trainers.php" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= basename($_SERVER['PHP_SELF']) === 'trainers.php' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-chalkboard-teacher mr-2"></i> Teachers
        </a>
        <a href="batches.php" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= basename($_SERVER['PHP_SELF']) === 'batches.php' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-users mr-2"></i> Batches
        </a>
        <a href="exams.php" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= basename($_SERVER['PHP_SELF']) === 'exams.php' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-graduation-cap mr-2"></i> Exams
        </a>
        <a href="workshops.php" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= basename($_SERVER['PHP_SELF']) === 'workshops.php' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-laptop-code mr-2"></i> Workshops
        </a>
        <a href="attendance.php" class="px-4 py-2 font-medium text-sm rounded-t-lg mr-2 transition-all duration-300 <?= basename($_SERVER['PHP_SELF']) === 'attendance.php' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-calendar-check mr-2"></i> Attendance
        </a>
        <a href="feedbacks.php" class="px-4 py-2 font-medium text-sm rounded-t-lg transition-all duration-300 <?= basename($_SERVER['PHP_SELF']) === 'feedbacks.php' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="fas fa-comment-alt mr-2"></i> Feedbacks
        </a>
    </div>