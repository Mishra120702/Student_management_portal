<?php
require_once '../db_connection.php';
require_once '../header.php';
require_once '../sidebar.php';

// Get filter parameters
$status = $_GET['status'] ?? 'upcoming';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$trainer_id = $_GET['trainer_id'] ?? '';

// Get all trainers for filter dropdown
$trainers_query = $db->query("SELECT id, name FROM trainers ORDER BY name");
$trainers = $trainers_query->fetchAll(PDO::FETCH_ASSOC);

// Get workshops based on filters
$query = "SELECT w.*, t.name as trainer_name 
          FROM workshops w
          LEFT JOIN trainers t ON w.trainer_id = t.id
          WHERE 1=1";

$params = [];
$types = '';

if (!empty($status)) {
    $query .= " AND w.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($start_date)) {
    $query .= " AND w.start_datetime >= ?";
    $params[] = $start_date;
    $types .= 's';
}

if (!empty($end_date)) {
    $query .= " AND w.end_datetime <= ?";
    $params[] = $end_date;
    $types .= 's';
}

if (!empty($trainer_id)) {
    $query .= " AND w.trainer_id = ?";
    $params[] = $trainer_id;
    $types .= 'i';
}

$query .= " ORDER BY w.start_datetime ASC";

$stmt = $db->prepare($query);

if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}

$workshops = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="ml-64 p-8 transition-all duration-300">
    <!-- Main Navigation Tabs -->
    <?php include 'navbar.php'?>
</div>

<div class="ml-64 p-8 transition-all duration-300">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Workshop Management</h1>
        <div class="flex space-x-4">
            <a href="add_workshop.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors transform hover:scale-105">
                <i class="fas fa-plus mr-2"></i> Add New Workshop
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8 transition-all hover:shadow-lg animate-fade-in">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Filter Workshops</h2>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Trainer</label>
                <select name="trainer_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <option value="">All Trainers</option>
                    <?php foreach ($trainers as $trainer): ?>
                        <option value="<?= $trainer['id'] ?>" <?= $trainer_id == $trainer['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($trainer['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="<?= $start_date ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="<?= $end_date ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
            </div>
            
            <div class="md:col-span-4 flex justify-end space-x-4">
                <button type="reset" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition-colors transform hover:scale-105">
                    <i class="fas fa-redo mr-2"></i> Reset
                </button>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors transform hover:scale-105">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Workshops List -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8 transition-all hover:shadow-lg animate-slide-up">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trainer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registrations</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($workshops)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No workshops found matching your criteria
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($workshops as $workshop): ?>
                            <tr class="hover:bg-blue-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php if ($workshop['cover_image']): ?>
                                                <img class="h-10 w-10 rounded-md object-cover" src="<?= htmlspecialchars($workshop['cover_image']) ?>" alt="Workshop cover">
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-md bg-gray-200 flex items-center justify-center">
                                                    <i class="fas fa-laptop-code text-gray-500"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($workshop['title']) ?></div>
                                            <div class="text-sm text-gray-500">Fee: $<?= number_format($workshop['fee'], 2) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($workshop['trainer_name'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= date('M d, Y', strtotime($workshop['start_datetime'])) ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?= date('h:i A', strtotime($workshop['start_datetime'])) ?> - <?= date('h:i A', strtotime($workshop['end_datetime'])) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($workshop['location']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                        $status_color = [
                                            'upcoming' => 'blue',
                                            'ongoing' => 'green',
                                            'completed' => 'gray',
                                            'cancelled' => 'red'
                                        ][$workshop['status']] ?? 'gray';
                                    ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-<?= $status_color ?>-100 text-<?= $status_color ?>-800">
                                        <?= ucfirst($workshop['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= $workshop['current_registrations'] ?> / <?= $workshop['max_participants'] ?>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                        <div class="bg-blue-600 h-1.5 rounded-full" 
                                             style="width: <?= $workshop['max_participants'] > 0 ? ($workshop['current_registrations'] / $workshop['max_participants'] * 100) : 0 ?>%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="view_workshop.php?id=<?= $workshop['workshop_id'] ?>" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_workshop.php?id=<?= $workshop['workshop_id'] ?>" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="workshop_registrations.php?id=<?= $workshop['workshop_id'] ?>" class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        <?php if ($workshop['status'] !== 'cancelled'): ?>
                                            <a href="#" onclick="confirmCancel('<?= $workshop['workshop_id'] ?>')" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
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

<script>
function confirmCancel(workshopId) {
    if (confirm('Are you sure you want to cancel this workshop?')) {
        window.location.href = 'cancel_workshop.php?id=' + workshopId;
    }
    return false;
}
</script>

<?php require_once '../footer.php'; ?>