<?php
session_start();
require_once '../db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get student information including batch
$student_query = $db->prepare("
    SELECT s.*, b.batch_id, b.course_name 
    FROM students s
    JOIN batches b ON s.batch_name = b.batch_id
    WHERE s.user_id = :user_id
");
$student_query->execute([':user_id' => $student_id]);
$student = $student_query->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student information not found");
}

// Get content for the student's batch
$content_query = $db->prepare("
    SELECT u.* 
    FROM uploads u
    JOIN batch_uploads bu ON u.id = bu.upload_id
    WHERE bu.batch_id = :batch_id
    ORDER BY u.uploaded_at DESC
");
$content_query->execute([':batch_id' => $student['batch_name']]);
$content_items = $content_query->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../header.php'; ?>
<?php include '../s_sidebar.php'; ?>

<div class="flex-1 ml-0 md:ml-64 min-h-screen">
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-book text-blue-500"></i>
            <span>My Course Content</span>
        </h1>
    </header>

    <div class="p-4 md:p-6">
        <div class="bg-white p-6 rounded-xl shadow mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4"><?= htmlspecialchars($student['course_name']) ?> (Batch <?= htmlspecialchars($student['batch_name']) ?>)</h2>
            
            <?php if (count($content_items) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($content_items as $content): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($content['title']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $content['file_type'] === 'Test' ? 'bg-purple-100 text-purple-800' : 
                                               ($content['file_type'] === 'Assignment' ? 'bg-blue-100 text-blue-800' : 
                                               ($content['file_type'] === 'Notes' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')) ?>">
                                            <?= htmlspecialchars($content['file_type']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?= htmlspecialchars($content['description']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y', strtotime($content['uploaded_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="<?= htmlspecialchars($content['file_path']) ?>" 
                                           download
                                           class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No course content available yet for your batch.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('-translate-x-full');
}
</script>

<?php include '../footer.php'; ?>