<?php
include '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $fileType = $_POST['file_type'] ?? '';
    $batchIds = $_POST['batch_ids'] ?? [];
    
    // Validate inputs
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        exit;
    }
    
    if (empty($batchIds)) {
        echo json_encode(['success' => false, 'message' => 'Please select at least one batch']);
        exit;
    }
    
    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $fileType = $_FILES['file']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Only PDF and DOCX files are allowed']);
            exit;
        }
        
        $uploadDir = '../uploads/content/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['file']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
            try {
                $db->beginTransaction();
                
                // Insert upload record
                $stmt = $db->prepare("INSERT INTO uploads (title, description, file_path, file_type, uploaded_by) 
                                     VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $filePath, $fileType, $_SESSION['user_id']]);
                $uploadId = $db->lastInsertId();
                
                // Insert batch associations
                $stmt = $db->prepare("INSERT INTO batch_uploads (upload_id, batch_id) VALUES (?, ?)");
                foreach ($batchIds as $batchId) {
                    $stmt->execute([$uploadId, $batchId]);
                }
                
                $db->commit();
                echo json_encode(['success' => true, 'message' => 'File uploaded successfully']);
                exit;
            } catch (PDOException $e) {
                $db->rollBack();
                // Delete the uploaded file if DB operation failed
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'File upload failed']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit;
    }
}

// Get all batches for dropdown
$batches = $db->query("SELECT batch_id, course_name FROM batches ORDER BY batch_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get all uploaded content
$uploads = $db->query("
    SELECT u.*, users.name as uploaded_by_name 
    FROM uploads u
    JOIN users ON u.uploaded_by = users.id
    ORDER BY u.uploaded_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get batch associations for each upload
foreach ($uploads as &$upload) {
    $stmt = $db->prepare("
        SELECT b.batch_id, b.course_name 
        FROM batch_uploads bu
        JOIN batches b ON bu.batch_id = b.batch_id
        WHERE bu.upload_id = ?
    ");
    $stmt->execute([$upload['id']]);
    $upload['batches'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($upload);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Upload - ASD Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.0.0/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.0.0/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Add animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Add AOS for scroll animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Add font-awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Add custom styles -->
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --secondary: #f9fafb;
            --accent: #10b981;
            --danger: #ef4444;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            transition: all 0.3s ease;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: var(--primary);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3), 0 2px 4px -1px rgba(79, 70, 229, 0.1);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .file-upload-container {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .file-upload-container:hover {
            border-color: var(--primary);
            background: rgba(79, 70, 229, 0.05);
        }
        
        .file-upload-container.dragover {
            border-color: var(--primary);
            background: rgba(79, 70, 229, 0.1);
            transform: scale(1.01);
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .badge-test {
            background-color: #f3e8ff;
            color: #7e22ce;
        }
        
        .badge-assignment {
            background-color: #dbeafe;
            color: #1d4ed8;
        }
        
        .badge-notes {
            background-color: #d1fae5;
            color: #047857;
        }
        
        .badge-other {
            background-color: #e5e7eb;
            color: #4b5563;
        }
        
        .batch-tag {
            display: inline-flex;
            align-items: center;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            background-color: #f3f4f6;
            color: #4b5563;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .batch-tag:hover {
            background-color: #e5e7eb;
            transform: translateY(-1px);
        }
        
        .action-btn {
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(79, 70, 229, 0); }
            100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
        
        /* Table row hover effect */
        tr {
            transition: all 0.2s ease;
        }
        
        tr:hover {
            background-color: #f9fafb;
            transform: translateX(4px);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
<?php include '../header.php'; ?>
<?php include '../sidebar.php'; ?>

<div class="flex-1 ml-0 md:ml-64 min-h-screen transition-all duration-300 ease-in-out">
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30 backdrop-blur-sm bg-opacity-90">
        <button class="md:hidden text-xl text-gray-600 hover:text-gray-900 transition-colors" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-cloud-upload-alt text-blue-500 animate-pulse"></i>
            <span>Content Management</span>
        </h1>
    </header>

    <div class="p-4 md:p-6">
        <!-- Upload Form -->
        <div class="card p-6 mb-6 animate__animated animate__fadeInUp" data-aos="fade-up">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <i class="fas fa-upload mr-2 text-blue-500"></i>
                <span>Upload New Content</span>
            </h2>
            <form id="uploadForm" enctype="multipart/form-data" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label for="title" class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                        <input type="text" id="title" name="title" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                               placeholder="Enter content title">
                    </div>
                    
                    <div class="space-y-1">
                        <label for="file_type" class="block text-sm font-medium text-gray-700">File Type <span class="text-red-500">*</span></label>
                        <select id="file_type" name="file_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <option value="">Select a type</option>
                            <option value="Test">Test</option>
                            <option value="Assignment">Assignment</option>
                            <option value="Notes">Notes</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2 space-y-1">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                  placeholder="Enter a brief description (optional)"></textarea>
                    </div>
                    
                    <div class="md:col-span-2 space-y-1">
                        <label for="batch_ids" class="block text-sm font-medium text-gray-700">Associated Batch(es) <span class="text-red-500">*</span></label>
                        <select id="batch_ids" name="batch_ids[]" multiple required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <?php foreach ($batches as $batch): ?>
                                <option value="<?= htmlspecialchars($batch['batch_id']) ?>">
                                    <?= htmlspecialchars($batch['batch_id'] . ' - ' . $batch['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2 space-y-1">
                        <label class="block text-sm font-medium text-gray-700">File Upload <span class="text-red-500">*</span></label>
                        <div id="fileDropArea" class="file-upload-container p-8 text-center cursor-pointer">
                            <input type="file" id="file" name="file" required class="hidden"
                                   accept=".pdf,.doc,.docx">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <i class="fas fa-cloud-upload-alt text-4xl text-blue-400 mb-2"></i>
                                <p class="text-sm text-gray-600">Drag & drop your file here or click to browse</p>
                                <p class="text-xs text-gray-500 mt-1">Supports: PDF, DOC, DOCX (Max 10MB)</p>
                                <div id="fileNameDisplay" class="mt-2 text-sm font-medium text-blue-600 hidden"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end pt-4">
                    <button type="submit" class="btn-primary px-6 py-2 text-white rounded-lg shadow-md flex items-center space-x-2">
                        <i class="fas fa-upload"></i>
                        <span>Upload Content</span>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Uploaded Content Table -->
        <div class="card p-6 animate__animated animate__fadeIn" data-aos="fade-up" data-aos-delay="100">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-folder-open mr-2 text-blue-500"></i>
                    <span>Uploaded Content</span>
                </h2>
                <div class="relative">
                    <input type="text" id="contentSearch" placeholder="Search content..." 
                           class="px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           onkeyup="searchContent()">
                    <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                </div>
            </div>
            
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batches</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="contentTableBody">
                        <?php if (empty($uploads)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-2 text-gray-400">
                                        <i class="fas fa-box-open text-4xl"></i>
                                        <p class="text-sm">No content uploaded yet</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($uploads as $index => $upload): ?>
                                <tr class="fade-in" style="animation-delay: <?= $index * 0.05 ?>s">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($upload['title']) ?>
                                            <?php if ($upload['description']): ?>
                                                <p class="text-xs text-gray-500 mt-1 truncate max-w-xs"><?= htmlspecialchars($upload['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="badge badge-<?= strtolower($upload['file_type']) ?>">
                                            <i class="fas <?= $upload['file_type'] === 'Test' ? 'fa-question-circle' : 
                                                           ($upload['file_type'] === 'Assignment' ? 'fa-tasks' : 
                                                           ($upload['file_type'] === 'Notes' ? 'fa-book' : 'fa-file')) ?> mr-1"></i>
                                            <?= htmlspecialchars($upload['file_type']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap">
                                            <?php foreach ($upload['batches'] as $batch): ?>
                                                <span class="batch-tag">
                                                    <i class="fas fa-users mr-1 text-xs"></i>
                                                    <?= htmlspecialchars($batch['batch_id']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                                                <i class="fas fa-user text-blue-500 text-sm"></i>
                                            </div>
                                            <?= htmlspecialchars($upload['uploaded_by_name']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <i class="far fa-calendar-alt mr-2 text-gray-400"></i>
                                            <?= date('M j, Y', strtotime($upload['uploaded_at'])) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="<?= htmlspecialchars($upload['file_path']) ?>" 
                                               download
                                               class="action-btn text-blue-600 hover:text-blue-900 px-3 py-1 rounded-md bg-blue-50 hover:bg-blue-100">
                                                <i class="fas fa-download mr-1"></i> Download
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($uploads)): ?>
            <div class="flex justify-between items-center mt-4 px-2">
                <div class="text-sm text-gray-500">
                    Showing <span class="font-medium">1</span> to <span class="font-medium"><?= count($uploads) ?></span> of <span class="font-medium"><?= count($uploads) ?></span> results
                </div>
                <div class="flex space-x-2">
                    <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Previous
                    </button>
                    <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Next
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- AOS initialization -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS animations
    AOS.init({
        duration: 600,
        easing: 'ease-out-quad',
        once: true
    });
    
    // Initialize multi-select dropdown
    new TomSelect('#batch_ids', {
        plugins: ['remove_button'],
        create: false,
        maxItems: null,
        placeholder: 'Select batch(es)',
        render: {
            option: function(data, escape) {
                return '<div class="flex items-center">' +
                       '<span class="inline-block w-3 h-3 rounded-full bg-blue-500 mr-2"></span>' +
                       escape(data.text) +
                       '</div>';
            }
        }
    });

    // File upload drag and drop
    const fileDropArea = document.getElementById('fileDropArea');
    const fileInput = document.getElementById('file');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    
    fileDropArea.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) {
            fileNameDisplay.textContent = fileInput.files[0].name;
            fileNameDisplay.classList.remove('hidden');
            fileDropArea.classList.add('border-blue-500', 'bg-blue-50');
        }
    });
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        fileDropArea.classList.add('dragover');
    }
    
    function unhighlight() {
        fileDropArea.classList.remove('dragover');
    }
    
    fileDropArea.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        
        if (files.length) {
            fileNameDisplay.textContent = files[0].name;
            fileNameDisplay.classList.remove('hidden');
            fileDropArea.classList.add('border-blue-500', 'bg-blue-50');
        }
    }
    
    // Handle form submission with AJAX
    const uploadForm = document.getElementById('uploadForm');
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Client-side validation
        const title = document.getElementById('title').value.trim();
        const fileType = document.getElementById('file_type').value;
        const batchIds = document.getElementById('batch_ids').value;
        
        if (!title || !fileType || !batchIds || !fileInput.files.length) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Please fill all required fields',
                confirmButtonColor: '#4f46e5',
                backdrop: 'rgba(79, 70, 229, 0.1)'
            });
            return;
        }
        
        // Check file size (max 10MB)
        if (fileInput.files[0].size > 10 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'File too large',
                text: 'File size exceeds 10MB limit',
                confirmButtonColor: '#4f46e5',
                backdrop: 'rgba(79, 70, 229, 0.1)'
            });
            return;
        }
        
        // Show loading animation
        const submitBtn = uploadForm.querySelector('button[type="submit"]');
        const originalBtnContent = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Uploading...';
        submitBtn.disabled = true;
        
        // Submit form with AJAX
        const formData = new FormData(uploadForm);
        
        fetch('upload_content.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message,
                    confirmButtonColor: '#4f46e5',
                    backdrop: 'rgba(79, 70, 229, 0.1)',
                    showClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutUp'
                    }
                }).then(() => {
                    location.reload(); // Refresh to show new upload
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#4f46e5',
                    backdrop: 'rgba(79, 70, 229, 0.1)'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred during upload',
                confirmButtonColor: '#4f46e5',
                backdrop: 'rgba(79, 70, 229, 0.1)'
            });
            console.error('Error:', error);
        })
        .finally(() => {
            submitBtn.innerHTML = originalBtnContent;
            submitBtn.disabled = false;
        });
    });
});

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('-translate-x-full');
    document.body.classList.toggle('overflow-hidden');
}

function searchContent() {
    const input = document.getElementById('contentSearch');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('contentTableBody');
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 0; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td')[0]; // Search only in title column
        if (td) {
            const txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
                tr[i].classList.add('animate__animated', 'animate__fadeIn');
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
</script>

<?php include '../footer.php'; ?>
</body>
</html>