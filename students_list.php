<?php
require_once '../db_connection.php';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all students with their current batch info
    $stmt = $conn->prepare("
        SELECT s.student_id, s.first_name, s.last_name, s.email, s.phone_number, 
               s.date_of_birth, s.enrollment_date, s.current_status,
               b.batch_id, b.course_name, b.start_date, b.end_date, b.status as batch_status
        FROM students s
        LEFT JOIN batches b ON s.batch_name = b.batch_id
        ORDER BY s.first_name, s.last_name
    ");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student_Management</title>
    <!-- Primary Tailwind CDN with fallback -->
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <!-- Add this before your custom script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.Tailwind || document.write('<script src="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.js"><\/script>')
    </script>
    <!-- Font Awesome from jsDelivr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-link:hover {
            background-color: #f0f7ff;
        }
        .sidebar-link.active {
            background-color: #e1f0ff;
            border-left: 4px solid #3b82f6;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .info-card {
            transition: all 0.2s ease;
        }
        .info-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("-translate-x-full");
            document.getElementById("sidebar").classList.toggle("md:translate-x-0");
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800">
    <!-- Sidebar -->
<?php
include '../sidebar.php';
?>
    <!-- Main Content -->
    <div class="md:ml-64">
        <!-- Mobile header -->
        <div class="md:hidden bg-white shadow-sm sticky top-0 z-30 p-4 flex items-center">
            <button onclick="toggleSidebar()" class="text-gray-500 focus:outline-none">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="ml-4 text-xl font-semibold">Student Directory</h1>
        </div>

        <div class="container mx-auto px-4 py-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800 hidden md:block">Student Directory</h1>
                <div class="relative">
                    <input type="text" id="searchInput" placeholder="Search students..." 
                           class="pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="studentsContainer">
                <?php foreach ($students as $student): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 student-card" 
                         data-student-id="<?= htmlspecialchars($student['student_id']) ?>">
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                                    <i class="fas fa-user text-2xl text-blue-500"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800 hover:text-blue-600 cursor-pointer student-name">
                                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                    </h3>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($student['student_id']) ?></p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <?php if ($student['batch_id']): ?>
                                    <p class="text-sm">
                                        <span class="font-medium">Batch:</span> 
                                        <?= htmlspecialchars($student['batch_id']) ?> - <?= htmlspecialchars($student['course_name']) ?>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-medium">Dates:</span> 
                                        <?= date('M Y', strtotime($student['start_date'])) ?> - <?= date('M Y', strtotime($student['end_date'])) ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-sm text-gray-500">No current batch</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Student Profile Modal -->
    <div id="studentModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-2xl leading-6 font-bold text-gray-900" id="modalStudentName"></h3>
                                    <p class="text-sm text-gray-500" id="modalStudentId"></p>
                                </div>
                                <button type="button" id="closeModal" class="text-gray-400 hover:text-gray-500">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Personal Info -->
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 border-b pb-2 mb-3">Contact Information</h4>
                                    <div class="space-y-2">
                                        <p><span class="font-medium">Email:</span> <span id="modalStudentEmail"></span></p>
                                        <p><span class="font-medium">Phone:</span> <span id="modalStudentPhone"></span></p>
                                        <p><span class="font-medium">DOB:</span> <span id="modalStudentDob"></span></p>
                                        <p><span class="font-medium">Status:</span> <span id="modalStudentStatus"></span></p>
                                    </div>
                                </div>
                                
                                <!-- Current Batch -->
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900 border-b pb-2 mb-3">Current Batch</h4>
                                    <div id="currentBatchInfo">
                                        <p class="text-gray-500">Loading...</p>
                                    </div>
                                </div>
                                
                                <!-- Previous Batches -->
                                <div class="md:col-span-2">
                                    <h4 class="text-lg font-medium text-gray-900 border-b pb-2 mb-3">Batch History</h4>
                                    <div id="batchHistory">
                                        <p class="text-gray-500">Loading...</p>
                                    </div>
                                </div>
                                
                                <!-- Performance -->
                                <div class="md:col-span-2">
                                    <h4 class="text-lg font-medium text-gray-900 border-b pb-2 mb-3">Performance</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="bg-blue-50 p-4 rounded-lg text-center">
                                            <p class="text-2xl font-bold text-blue-600" id="attendancePercent">0%</p>
                                            <p class="text-sm text-gray-600">Attendance</p>
                                        </div>
                                        <div class="bg-green-50 p-4 rounded-lg text-center">
                                            <p class="text-2xl font-bold text-green-600" id="avgScore">0</p>
                                            <p class="text-sm text-gray-600">Avg Score</p>
                                        </div>
                                        <div class="bg-purple-50 p-4 rounded-lg text-center">
                                            <p class="text-2xl font-bold text-purple-600" id="completedBatches">0</p>
                                            <p class="text-sm text-gray-600">Batches Completed</p>
                                        </div>
                                        <div class="bg-yellow-50 p-4 rounded-lg text-center">
                                            <p class="text-2xl font-bold text-yellow-600" id="activeBatches">0</p>
                                            <p class="text-sm text-gray-600">Active Batches</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="viewFullProfile" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        View Full Profile
                    </button>
                    <button type="button" id="closeModalBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const cards = document.querySelectorAll('.student-card');
            
            cards.forEach(card => {
                const name = card.querySelector('.student-name').textContent.toLowerCase();
                if (name.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Modal functionality
        const modal = document.getElementById('studentModal');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const closeModalIcon = document.getElementById('closeModal');
        const viewFullProfileBtn = document.getElementById('viewFullProfile');
        
        // Open modal when student card is clicked
        document.querySelectorAll('.student-card').forEach(card => {
            card.addEventListener('click', function() {
                const studentId = this.getAttribute('data-student-id');
                loadStudentProfile(studentId);
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            });
        });
        
        // Close modal
        function closeModal() {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
        
        closeModalBtn.addEventListener('click', closeModal);
        closeModalIcon.addEventListener('click', closeModal);
        
        // View full profile
        viewFullProfileBtn.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            window.location.href = `student_view.php?id=<?= $student['student_id'] ?>`;
        });
        
        // Load student profile data via AJAX
        function loadStudentProfile(studentId) {
            fetch(`get_student_data.php?id=${studentId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Basic info
                    document.getElementById('modalStudentName').textContent = `${data.student.first_name} ${data.student.last_name}`;
                    document.getElementById('modalStudentId').textContent = data.student.student_id;
                    document.getElementById('modalStudentEmail').textContent = data.student.email || 'N/A';
                    document.getElementById('modalStudentPhone').textContent = data.student.phone_number || 'N/A';
                    document.getElementById('modalStudentDob').textContent = data.student.date_of_birth ? 
                        new Date(data.student.date_of_birth).toLocaleDateString() : 'N/A';
                    document.getElementById('modalStudentStatus').textContent = data.student.current_status || 'N/A';
                    
                    // Current batch
                    const currentBatchContainer = document.getElementById('currentBatchInfo');
                    if (data.current_batch) {
                        currentBatchContainer.innerHTML = `
                            <p><span class="font-medium">Course:</span> ${data.current_batch.course_name || 'N/A'}</p>
                            <p><span class="font-medium">Batch ID:</span> ${data.current_batch.batch_id || 'N/A'}</p>
                            <p><span class="font-medium">Dates:</span> ${formatDate(data.current_batch.start_date)} - ${formatDate(data.current_batch.end_date)}</p>
                            <p><span class="font-medium">Status:</span> <span class="px-2 py-1 text-xs rounded-full ${getStatusClass(data.current_batch.status)}">${capitalizeFirstLetter(data.current_batch.status || 'unknown')}</span></p>
                        `;
                    } else {
                        currentBatchContainer.innerHTML = '<p class="text-gray-500">No current batch</p>';
                    }
                    
                    // Batch history
                    const batchHistoryContainer = document.getElementById('batchHistory');
                    if (data.batch_history && data.batch_history.length > 0) {
                        let historyHTML = '<div class="space-y-4">';
                        data.batch_history.forEach(batch => {
                            historyHTML += `
                                <div class="border-l-4 border-blue-200 pl-4 py-2">
                                    <p><span class="font-medium">${batch.course_name || 'Unknown Course'}</span> (${batch.batch_id || 'N/A'})</p>
                                    <p class="text-sm text-gray-600">${formatDate(batch.start_date)} - ${formatDate(batch.end_date)}</p>
                                    <p class="text-sm">Status: <span class="px-1 py-0.5 text-xs rounded-full ${getStatusClass(batch.status)}">${capitalizeFirstLetter(batch.status || 'unknown')}</span></p>
                                </div>
                            `;
                        });
                        historyHTML += '</div>';
                        batchHistoryContainer.innerHTML = historyHTML;
                    } else {
                        batchHistoryContainer.innerHTML = '<p class="text-gray-500">No previous batches</p>';
                    }
                    
                    // Performance stats
                    document.getElementById('attendancePercent').textContent = `${data.attendance_percent || 0}%`;
                    document.getElementById('avgScore').textContent = data.avg_score ? data.avg_score.toFixed(2) : 'N/A';
                    document.getElementById('completedBatches').textContent = data.completed_batches || 0;
                    document.getElementById('activeBatches').textContent = data.active_batches || 0;
                    
                    // Set student ID for view full profile button
                    viewFullProfileBtn.setAttribute('data-student-id', studentId);
                })
                .catch(error => {
                    console.error('Error loading student profile:', error);
                    // Show error message in modal
                    document.getElementById('currentBatchInfo').innerHTML = '<p class="text-red-500">Error loading data</p>';
                    document.getElementById('batchHistory').innerHTML = '<p class="text-red-500">Error loading data</p>';
                });
        }
        
        // Helper functions
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString(undefined, options);
        }
        
        function getStatusClass(status) {
            if (!status) return 'bg-gray-100 text-gray-800';
            switch(status.toLowerCase()) {
                case 'active':
                case 'ongoing':
                case 'present': 
                    return 'bg-green-100 text-green-800';
                case 'completed': 
                    return 'bg-blue-100 text-blue-800';
                case 'cancelled':
                case 'absent': 
                    return 'bg-red-100 text-red-800';
                case 'upcoming': 
                    return 'bg-yellow-100 text-yellow-800';
                default: 
                    return 'bg-gray-100 text-gray-800';
            }
        }
        
        function capitalizeFirstLetter(string) {
            if (!string) return 'Unknown';
            return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
        }
    </script>
</body>
</html>