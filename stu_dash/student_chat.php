<?php

require_once '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) ) {
    header("Location: ../login.php");
    exit;
}
// Get student information
$student_id = $_SESSION['user_id'];
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

$student_name = $student['first_name'] . ' ' . $student['last_name'];

// Get messages for this student (both personal and batch messages)
$messages_query = $db->prepare("
    SELECT m.*, u.name as sender_name, 
           CASE WHEN m.receiver_id IS NULL THEN 'batch' ELSE 'personal' END as message_type
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (m.receiver_id = :user_id OR (m.batch_id = :batch_id AND m.receiver_id IS NULL))
    ORDER BY m.timestamp DESC
");
$messages_query->execute([':user_id' => $student_id, ':batch_id' => $student['batch_id']]);
$messages = $messages_query->fetchAll(PDO::FETCH_ASSOC);

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message_text = trim($_POST['message_text']);
    $receiver_id = $_POST['receiver_id'] ?? null;
    $batch_id = $_POST['batch_id'] ?? null;

    if (!empty($message_text)) {
        $stmt = $db->prepare("
            INSERT INTO messages (sender_id, receiver_id, batch_id, message_text, timestamp)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$student_id, $receiver_id, $batch_id, $message_text]);
        header("Refresh:0");
        exit();
    }
}

// Get list of mentors for dropdown
$mentors_query = $db->query("
    SELECT u.id, u.name 
    FROM users u
    JOIN trainers t ON u.id = t.user_id
    WHERE u.role = 'mentor'
");
$mentors = $mentors_query->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../header.php'; ?>
<?php include '../s_sidebar.php'; ?>

<!-- Main Content -->
<div class="flex-1 ml-0 md:ml-64 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-comments text-blue-500"></i>
            <span>Student Chat</span>
        </h1>
    </header>

    <div class="p-4 md:p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Message List -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Messages</h2>
                
                <?php if (count($messages) > 0): ?>
                    <div class="space-y-4 max-h-[600px] overflow-y-auto">
                        <?php foreach ($messages as $message): ?>
                            <div class="border border-gray-200 rounded-lg p-4 <?= $message['sender_id'] == $student_id ? 'bg-blue-50' : '' ?>">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="font-medium">
                                            <?= htmlspecialchars($message['sender_name']) ?>
                                            <?php if ($message['message_type'] === 'batch'): ?>
                                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded ml-2">Batch Message</span>
                                            <?php endif; ?>
                                        </span>
                                        <p class="text-gray-600 text-sm mt-1">
                                            <?= date('M j, g:i A', strtotime($message['timestamp'])) ?>
                                        </p>
                                    </div>
                                    <?php if ($message['sender_id'] == $student_id): ?>
                                        <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Sent</span>
                                    <?php endif; ?>
                                </div>
                                <p class="mt-2 text-gray-700"><?= htmlspecialchars($message['message_text']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">No messages yet.</p>
                <?php endif; ?>
            </div>

            <!-- Send Message Form -->
            <div class="bg-white p-6 rounded-xl shadow">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Send Message</h2>
                <form method="POST">
                    <div class="mb-4">
                        <label for="receiver_id" class="block text-gray-700 mb-2">Send To</label>
                        <select id="receiver_id" name="receiver_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Batch Message (All Students)</option>
                            <?php foreach ($mentors as $mentor): ?>
                                <option value="<?= $mentor['id'] ?>"><?= htmlspecialchars($mentor['name']) ?> (Mentor)</option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="batch_id" value="<?= $student['batch_id'] ?>">
                    </div>

                    <div class="mb-4">
                        <label for="message_text" class="block text-gray-700 mb-2">Message</label>
                        <textarea id="message_text" name="message_text" rows="5" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                            placeholder="Type your message here..."
                            required></textarea>
                    </div>

                    <button type="submit" name="send_message" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full">
                        Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>