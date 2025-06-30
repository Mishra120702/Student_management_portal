<?php
require_once 'db_config.php';

// Get batch ID from URL
$batch_id = isset($_GET['batch_id']) ? $_GET['batch_id'] : null;

if (!$batch_id) {
    header("Location: batch_list.html");
    exit();
}

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get batch details
    $stmt = $conn->prepare("SELECT 
                            b.*, 
                            u.name as mentor_name,
                            u.email as mentor_email
                        FROM batches b
                        LEFT JOIN users u ON b.batch_mentor_id = u.id
                        WHERE b.batch_id = ?");
    $stmt->execute([$batch_id]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$batch) {
        header("Location: batch_list.html");
        exit();
    }
    
    // Get students in this batch
    $stmt = $conn->prepare("SELECT 
                            u.id, 
                            u.name, 
                            u.email,
                            u.phone
                        FROM batch_students bs
                        JOIN users u ON bs.student_id = u.id
                        WHERE bs.batch_id = ?
                        ORDER BY u.name");
    $stmt->execute([$batch['id']]);
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
    <title>Batch <?= htmlspecialchars($batch['batch_id']) ?> | ASD Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Back button -->
            <a href="batch_list.html" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4">
                <i class="fas fa-arrow-left mr-2"></i> Back to Batch List
            </a>
            
            <!-- Batch Header -->
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($batch['course_name']) ?></h1>
                        <p class="text-gray-600">Batch ID: <?= htmlspecialchars($batch['batch_id']) ?></p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                            <?= $batch['status'] === 'Running' ? 'bg-blue-100 text-blue-800' : 
                               ($batch['status'] === 'Completed' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800') ?>">
                            <?= htmlspecialchars($batch['status']) ?>
                        </span>
                    </div>
                </div>
                
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-500">Start Date</p>
                        <p class="font-medium"><?= date('M j, Y', strtotime($batch['start_date'])) ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-500">End Date</p>
                        <p class="font-medium"><?= date('M j, Y', strtotime($batch['end_date'])) ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-500">Mode</p>
                        <p class="font-medium"><?= htmlspecialchars($batch['mode']) ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-500">Students</p>
                        <p class="font-medium"><?= htmlspecialchars($batch['num_students']) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Batch Details Sections -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Mentor Information -->
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Batch Mentor</h2>
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-user text-blue-500"></i>
                        </div>
                        <div>
                            <p class="font-medium"><?= htmlspecialchars($batch['mentor_name']) ?></p>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($batch['mentor_email']) ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Platform Information -->
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Platform Details</h2>
                    <?php if ($batch['mode'] === 'Online'): ?>
                        <p class="text-sm text-gray-500">Platform</p>
                        <p class="font-medium mb-3"><?= htmlspecialchars($batch['platform']) ?></p>
                        <p class="text-sm text-gray-500">Meeting Link</p>
                        <a href="<?= htmlspecialchars($batch['meeting_link']) ?>" target="_blank" class="font-medium text-blue-600 hover:underline break-all">
                            <?= htmlspecialchars($batch['meeting_link']) ?>
                        </a>
                    <?php else: ?>
                        <p class="text-gray-500">Offline batch - no platform information</p>
                    <?php endif; ?>
                </div>
                
                <!-- Batch Actions -->
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-800 mb-4">Actions</h2>
                    <div class="space-y-3">
                        <a href="#" class="block w-full px-4 py-2 border border-gray-300 rounded-md text-center text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-envelope mr-2"></i> Send Announcement
                        </a>
                        <a href="#" class="block w-full px-4 py-2 border border-gray-300 rounded-md text-center text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-users mr-2"></i> Manage Students
                        </a>
                        <a href="#" class="block w-full px-4 py-2 border border-gray-300 rounded-md text-center text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-calendar-alt mr-2"></i> View Schedule
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Students List -->
            <div class="bg-white shadow-md rounded-lg p-6 mt-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-medium text-gray-800">Students (<?= count($students) ?>)</h2>
                    <button class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Add Student
                    </button>
                </div>
                
                <?php if (count($students) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <i class="fas fa-user text-gray-500"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($student['name']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($student['email']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <a href="#" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fas fa-eye"></i></a>
                                            <a href="#" class="text-blue-600 hover:text-blue-900"><i class="fas fa-envelope"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-users-slash text-gray-400 text-4xl mb-2"></i>
                        <p class="text-gray-600">No students enrolled in this batch yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>