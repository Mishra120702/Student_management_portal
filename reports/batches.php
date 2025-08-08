<?php
require_once '../db_connection.php';
require_once '../header.php';
require_once '../sidebar.php';

// Get filter parameters
$status = $_GET['status'] ?? '';
$course = $_GET['course'] ?? '';
$mode = $_GET['mode'] ?? '';
$mentor = $_GET['mentor'] ?? '';
$search = $_GET['search'] ?? '';

// Get all courses for filter dropdown
$courses_query = $db->query("SELECT DISTINCT course_name FROM batches ORDER BY course_name");
$courses = $courses_query->fetchAll(PDO::FETCH_COLUMN);

// Get all mentors for filter dropdown
$mentors_query = $db->query("SELECT id, name FROM trainers ORDER BY name");
$mentors = $mentors_query->fetchAll(PDO::FETCH_ASSOC);

// Build the base query
$query = "SELECT b.*, t.name as mentor_name 
          FROM batches b 
          LEFT JOIN trainers t ON b.batch_mentor_id = t.id 
          WHERE 1=1";

$params = [];

// Apply filters
if (!empty($status)) {
    $query .= " AND b.status = ?";
    $params[] = $status;
}

if (!empty($course)) {
    $query .= " AND b.course_name = ?";
    $params[] = $course;
}

if (!empty($mode)) {
    $query .= " AND b.mode = ?";
    $params[] = $mode;
}

if (!empty($mentor)) {
    $query .= " AND b.batch_mentor_id = ?";
    $params[] = $mentor;
}

if (!empty($search)) {
    $query .= " AND (b.batch_id LIKE ? OR b.course_name LIKE ? OR t.name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$query .= " ORDER BY b.start_date DESC";

// Prepare and execute the query
$stmt = $db->prepare($query);
$stmt->execute($params);
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count batches by status for the summary cards
$status_counts = $db->query("SELECT status, COUNT(*) as count FROM batches GROUP BY status")
                   ->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="ml-64 p-8 transition-all duration-300">
    <!-- Main Navigation Tabs -->
    <?php include 'navbar.php' ?>
</div>

<div class="ml-64 p-8 transition-all duration-300">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Batches Management</h1>
        <div class="flex space-x-4">
            <button onclick="window.print()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors transform hover:scale-105">
                <i class="fas fa-print mr-2"></i> Print
            </button>
            <a href="add_batch.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors transform hover:scale-105">
                <i class="fas fa-plus mr-2"></i> Add New Batch
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-1">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Batches</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= array_sum($status_counts) ?></h3>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-users text-blue-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-1">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-500">Ongoing</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $status_counts['ongoing'] ?? 0 ?></h3>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-play-circle text-green-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-1">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-500">Upcoming</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $status_counts['upcoming'] ?? 0 ?></h3>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-4 transition-all hover:shadow-md hover:-translate-y-1">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-medium text-gray-500">Completed</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $status_counts['completed'] ?? 0 ?></h3>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-check-circle text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8 transition-all hover:shadow-lg animate-fade-in">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Filter Batches</h2>
        <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <option value="">All Statuses</option>
                    <option value="upcoming" <?= $status === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                    <option value="ongoing" <?= $status === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                <select name="course" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course_name): ?>
                        <option value="<?= $course_name ?>" <?= $course === $course_name ? 'selected' : '' ?>>
                            <?= $course_name ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mode</label>
                <select name="mode" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <option value="">All Modes</option>
                    <option value="online" <?= $mode === 'online' ? 'selected' : '' ?>>Online</option>
                    <option value="offline" <?= $mode === 'offline' ? 'selected' : '' ?>>Offline</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mentor</label>
                <select name="mentor" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <option value="">All Mentors</option>
                    <?php foreach ($mentors as $mentor_data): ?>
                        <option value="<?= $mentor_data['id'] ?>" <?= $mentor == $mentor_data['id'] ? 'selected' : '' ?>>
                            <?= $mentor_data['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by batch ID, course or mentor..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
            </div>
            
            <div class="md:col-span-4 flex justify-end space-x-4">
                <a href="batches.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition-colors transform hover:scale-105">
                    <i class="fas fa-redo mr-2"></i> Reset
                </a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors transform hover:scale-105">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Batches Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8 transition-all hover:shadow-lg animate-slide-up">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Slot</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mentor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($batches)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                                No batches found matching your criteria
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($batches as $batch): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= $batch['batch_id'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $batch['course_name'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= date('M d, Y', strtotime($batch['start_date'])) ?> - <?= date('M d, Y', strtotime($batch['end_date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $batch['time_slot'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <a href="../trainers/view.php?id=<?= $batch['batch_mentor_id'] ?>"><?= $batch['mentor_name'] ?? 'Not assigned' ?></a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $batch['current_enrollment'] ?>/<?= $batch['max_students'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= ucfirst($batch['mode']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php 
                                        $status_colors = [
                                            'upcoming' => 'bg-blue-100 text-blue-800',
                                            'ongoing' => 'bg-green-100 text-green-800',
                                            'completed' => 'bg-purple-100 text-purple-800',
                                            'cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        $color = $status_colors[$batch['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= $color ?>">
                                        <?= ucfirst($batch['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex space-x-2">
                                        <a href="../batch/batch_view.php?id=<?= $batch['batch_id'] ?>" class="text-blue-600 hover:text-blue-900" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../batch/edit_batch.php?id=<?= $batch['batch_id'] ?>" class="text-yellow-600 hover:text-yellow-900" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../student/manage_students.php?batch_id=<?= $batch['batch_id'] ?>" class="text-green-600 hover:text-green-900" title="Manage Students">
                                            <i class="fas fa-user-plus"></i>
                                        </a>
                                        <a href="../batch/delete_batch.php?id=<?= $batch['batch_id'] ?>" class="text-red-600 hover:text-red-900" title="Delete" onclick="return confirm('Are you sure you want to delete this batch?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>