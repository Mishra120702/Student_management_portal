<?php
include '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle form submission to update feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_id'], $_POST['action_taken'])) {
    $feedback_id = $_POST['feedback_id'];
    $action_taken = trim($_POST['action_taken']);
    
    try {
        $stmt = $db->prepare("UPDATE feedback SET action_taken = ? WHERE id = ?");
        $stmt->execute([$action_taken, $feedback_id]);
        
        $_SESSION['feedback_message'] = [
            'type' => 'success',
            'text' => 'Feedback marked as addressed successfully!'
        ];
        
        // Redirect to prevent form resubmission
        header("Location: pending_feedbacks.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['feedback_message'] = [
            'type' => 'error',
            'text' => 'Error updating feedback: ' . $e->getMessage()
        ];
    }
}

include '../header.php';
include '../sidebar.php';

// Get pending feedback (where action_taken is empty)
$pending_feedback = $db->query("
    SELECT f.*, b.course_name 
    FROM feedback f
    LEFT JOIN batches b ON f.batch_id = b.batch_id
    WHERE f.action_taken IS NULL OR f.action_taken = ''
    ORDER BY f.date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Check for any feedback messages
$feedback_message = $_SESSION['feedback_message'] ?? null;
unset($_SESSION['feedback_message']);
?>

<div class="flex-1 ml-0 md:ml-64 min-h-screen">
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-comment-dots text-red-500"></i>
            <span>Pending Feedback</span>
        </h1>
    </header>

    <div class="p-4 md:p-6">
        <?php if ($feedback_message): ?>
        <div class="mb-4 p-4 rounded-md <?= $feedback_message['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
            <?= htmlspecialchars($feedback_message['text']) ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feedback</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($pending_feedback as $feedback): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('M d, Y', strtotime($feedback['date'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($feedback['student_name']) ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= htmlspecialchars($feedback['email']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($feedback['batch_id'] ?? 'N/A') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($feedback['course_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="stars-inline">
                                    <?= str_repeat('★', $feedback['class_rating']) ?><?= str_repeat('☆', 5 - $feedback['class_rating']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= htmlspecialchars(substr($feedback['feedback_text'], 0, 50)) ?>...
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <form method="POST" action="pending_feedbacks.php" class="flex items-center space-x-2">
                                    <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                    <input type="text" name="action_taken" placeholder="Action taken..." class="text-xs p-2 border rounded" required>
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">
                                        Mark Addressed
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pending_feedback)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                No pending feedback found. Great job!
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>