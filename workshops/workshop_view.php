
<?php
// workshop_view.php
require_once '../db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: workshop_list.php");
    exit;
}

$workshop_id = $_GET['id'];

// Get workshop details
$stmt = $db->prepare("
    SELECT w.*, t.name as trainer_name 
    FROM workshops w
    LEFT JOIN trainers t ON w.trainer_id = t.id
    WHERE w.workshop_id = ?
");
$stmt->execute([$workshop_id]);
$workshop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$workshop) {
    header("Location: workshop_list.php");
    exit;
}

// Get workshop schedule
$schedule = $db->prepare("
    SELECT * FROM workshop_schedule 
    WHERE workshop_id = ?
    ORDER BY start_time
");
$schedule->execute([$workshop_id]);
$sessions = $schedule->fetchAll(PDO::FETCH_ASSOC);

// Get workshop materials
$materials = $db->prepare("
    SELECT * FROM workshop_materials 
    WHERE workshop_id = ?
    ORDER BY uploaded_at DESC
");
$materials->execute([$workshop_id]);
$materials = $materials->fetchAll(PDO::FETCH_ASSOC);

// Get registration count
$registrations = $db->prepare("
    SELECT COUNT(*) as count FROM workshop_registrations 
    WHERE workshop_id = ?
");
$registrations->execute([$workshop_id]);
$registration_count = $registrations->fetch(PDO::FETCH_ASSOC)['count'];

// Check if current user is registered
$is_registered = false;
if ($_SESSION['user_role'] === 'student') {
    $check_reg = $db->prepare("
        SELECT 1 FROM workshop_registrations 
        WHERE workshop_id = ? AND student_id = ?
    ");
    $check_reg->execute([$workshop_id, $_SESSION['user_student_id']]);
    $is_registered = $check_reg->fetch() !== false;
}

// Get trainer details if available
$trainer = null;
if ($workshop['trainer_id']) {
    $trainer_stmt = $db->prepare("
        SELECT * FROM trainers 
        WHERE id = ?
    ");
    $trainer_stmt->execute([$workshop['trainer_id']]);
    $trainer = $trainer_stmt->fetch(PDO::FETCH_ASSOC);
}

// Format dates for display
$start_date = new DateTime($workshop['start_datetime']);
$end_date = new DateTime($workshop['end_datetime']);
$duration = $start_date->diff($end_date);
$duration_hours = $duration->h + ($duration->days * 24);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($workshop['title']) ?> - ASD Academy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e0e7ff;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --dark: #212529;
            --light: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #4a5568;
            line-height: 1.6;
        }
        
        .workshop-header {
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .workshop-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(30deg);
            animation: shine 8s infinite linear;
        }
        
        @keyframes shine {
            0% { transform: rotate(30deg) translateX(-100%); }
            100% { transform: rotate(30deg) translateX(100%); }
        }
        
        .workshop-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .workshop-subtitle {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
        }
        
        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        
        .badge i {
            margin-right: 0.25rem;
        }
        
        .badge-upcoming {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-ongoing {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-completed {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .badge-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
        }
        
        .card-title i {
            margin-right: 0.75rem;
            color: var(--primary);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            cursor: pointer;
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-light);
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
            border: none;
        }
        
        .btn-danger:hover {
            background-color: #d1145a;
            transform: translateY(-2px);
        }
        
        .workshop-cover {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .workshop-cover:hover {
            transform: scale(1.02);
        }
        
        .workshop-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .meta-item {
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .meta-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .meta-label {
            font-size: 0.875rem;
            color: #718096;
            margin-bottom: 0.5rem;
        }
        
        .meta-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .meta-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: var(--primary);
        }
        
        .session-item {
            display: flex;
            padding: 1rem;
            border-left: 4px solid var(--primary);
            margin-bottom: 1rem;
            background: white;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .session-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .session-time {
            min-width: 120px;
            margin-right: 1.5rem;
        }
        
        .session-start {
            font-weight: 600;
            color: var(--dark);
        }
        
        .session-end {
            font-size: 0.875rem;
            color: #718096;
        }
        
        .session-details {
            flex-grow: 1;
        }
        
        .session-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .session-desc {
            color: #718096;
            font-size: 0.875rem;
        }
        
        .session-break {
            border-left-color: var(--warning);
        }
        
        .session-break .session-title {
            color: var(--warning);
        }
        
        .material-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        
        .material-item:last-child {
            border-bottom: none;
        }
        
        .material-item:hover {
            background-color: #f8fafc;
        }
        
        .material-icon {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary);
        }
        
        .material-details {
            flex-grow: 1;
        }
        
        .material-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .material-meta {
            font-size: 0.75rem;
            color: #718096;
        }
        
        .material-actions a {
            color: var(--primary);
            margin-left: 1rem;
            transition: all 0.2s ease;
        }
        
        .material-actions a:hover {
            color: var(--secondary);
        }
        
        .trainer-card {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .trainer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .trainer-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1.5rem;
            border: 3px solid var(--primary-light);
        }
        
        .trainer-details {
            flex-grow: 1;
        }
        
        .trainer-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .trainer-title {
            color: #718096;
            margin-bottom: 0.5rem;
        }
        
        .trainer-bio {
            font-size: 0.875rem;
            color: #4a5568;
        }
        
        .progress-container {
            margin-top: 1rem;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 4px;
            transition: width 0.6s ease;
        }
        
        .floating-actions {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            display: flex;
            flex-direction: column;
            z-index: 50;
        }
        
        .floating-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .floating-btn:hover {
            transform: translateY(-5px) scale(1.1);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .floating-btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .floating-btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .floating-btn i {
            font-size: 1.25rem;
        }
        
        .floating-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0) 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .floating-btn:hover::after {
            opacity: 1;
        }
        
        .tooltip {
            position: absolute;
            right: 60px;
            background: var(--dark);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .floating-btn:hover .tooltip {
            opacity: 1;
            right: 70px;
        }
        
        .animate-delay-1 {
            animation-delay: 0.1s;
        }
        
        .animate-delay-2 {
            animation-delay: 0.2s;
        }
        
        .animate-delay-3 {
            animation-delay: 0.3s;
        }
        
        .animate-delay-4 {
            animation-delay: 0.4s;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .animate-pulse {
            animation: pulse 2s infinite;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .slide-up {
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .registration-modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 500px;
            animation: fadeInDown 0.4s;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .close-modal {
            color: #718096;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .close-modal:hover {
            color: #4a5568;
            transform: rotate(90deg);
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: var(--primary);
            opacity: 0;
            z-index: 9999;
            animation: confetti 5s ease-in-out;
        }
        
        @keyframes confetti {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(720deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>
    
    <div class="flex-1 ml-0 md:ml-64 min-h-screen p-4 md:p-6">
        <!-- Workshop Header -->
        <div class="workshop-header animate__animated animate__fadeIn">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="workshop-title animate__animated animate__fadeInDown">
                        <?= htmlspecialchars($workshop['title']) ?>
                    </h1>
                    <p class="workshop-subtitle animate__animated animate__fadeInDown animate-delay-1">
                        <?= htmlspecialchars($workshop['description']) ?>
                    </p>
                </div>
                <div class="animate__animated animate__fadeInDown animate-delay-2">
                    <?php 
                    $badgeClass = '';
                    if ($workshop['status'] === 'ongoing') $badgeClass = 'badge-ongoing';
                    else if ($workshop['status'] === 'completed') $badgeClass = 'badge-completed';
                    else if ($workshop['status'] === 'upcoming') $badgeClass = 'badge-upcoming';
                    else if ($workshop['status'] === 'cancelled') $badgeClass = 'badge-cancelled';
                    
                    $icon = '';
                    if ($workshop['status'] === 'ongoing') $icon = 'fa-play-circle';
                    else if ($workshop['status'] === 'completed') $icon = 'fa-check-circle';
                    else if ($workshop['status'] === 'upcoming') $icon = 'fa-clock';
                    else if ($workshop['status'] === 'cancelled') $icon = 'fa-times-circle';
                    ?>
                    <span class="badge <?= $badgeClass ?>">
                        <i class="fas <?= $icon ?>"></i>
                        <?= htmlspecialchars(ucfirst($workshop['status'])) ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Workshop Cover Image -->
        <?php if ($workshop['cover_image']): ?>
        <img src="<?= htmlspecialchars($workshop['cover_image']) ?>" 
             alt="<?= htmlspecialchars($workshop['title']) ?>" 
             class="workshop-cover animate__animated animate__fadeIn animate-delay-2">
        
        <!-- Workshop Meta Information -->
        <div class="workshop-meta animate__animated animate__fadeIn animate-delay-3">
            <div class="meta-item slide-up">
                <div class="meta-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="meta-label">Start Date</div>
                <div class="meta-value"><?= $start_date->format('F j, Y') ?></div>
            </div>
            
            <div class="meta-item slide-up animate-delay-1">
                <div class="meta-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="meta-label">Duration</div>
                <div class="meta-value"><?= $duration_hours ?> hours</div>
            </div>
            
            <div class="meta-item slide-up animate-delay-2">
                <div class="meta-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="meta-label">Participants</div>
                <div class="meta-value">
                    <?= $workshop['current_registrations'] ?>/<?= $workshop['max_participants'] ?>
                </div>
                <div class="progress-container">
                    <div class="progress-label">
                        <span>Capacity</span>
                        <span><?= round(($workshop['current_registrations'] / $workshop['max_participants']) * 100) ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= ($workshop['current_registrations'] / $workshop['max_participants']) * 100 ?>%"></div>
                    </div>
                </div>
            </div>
            
            <div class="meta-item slide-up animate-delay-3">
                <div class="meta-icon">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="meta-label">Fee</div>
                <div class="meta-value">₹<?= number_format($workshop['fee'], 2) ?></div>
            </div>
        </div>
        
        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column (Workshop Details) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- About Workshop -->
                <div class="card animate__animated animate__fadeIn animate-delay-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i>
                            About This Workshop
                        </h3>
                    </div>
                    <div class="prose max-w-none">
                        <?= nl2br(htmlspecialchars($workshop['description'])) ?>
                        
                        <?php if ($workshop['requirements']): ?>
                        <div class="mt-4">
                            <h4 class="font-semibold text-lg mb-2">Requirements</h4>
                            <?= nl2br(htmlspecialchars($workshop['requirements'])) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($workshop['certificate_available']): ?>
                        <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
                            <div class="flex items-center">
                                <i class="fas fa-certificate text-blue-500 mr-3 text-2xl"></i>
                                <div>
                                    <h4 class="font-semibold text-blue-800">Certificate of Completion</h4>
                                    <p class="text-sm text-blue-600">Participants who attend the full workshop will receive a certificate.</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Workshop Schedule -->
                <div class="card animate__animated animate__fadeIn animate-delay-5">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-day"></i>
                            Workshop Schedule
                        </h3>
                    </div>
                    <div>
                        <?php if (count($sessions) > 0): ?>
                            <?php foreach ($sessions as $session): ?>
                                <?php 
                                $session_start = new DateTime($session['start_time']);
                                $session_end = new DateTime($session['end_time']);
                                $is_break = $session['is_break'];
                                ?>
                                <div class="session-item <?= $is_break ? 'session-break' : '' ?>">
                                    <div class="session-time">
                                        <div class="session-start"><?= $session_start->format('h:i A') ?></div>
                                        <div class="session-end">to <?= $session_end->format('h:i A') ?></div>
                                    </div>
                                    <div class="session-details">
                                        <h4 class="session-title"><?= htmlspecialchars($session['session_title']) ?></h4>
                                        <?php if ($session['session_description']): ?>
                                        <p class="session-desc"><?= htmlspecialchars($session['session_description']) ?></p>
                                        <?php endif; ?>
                                        <?php if ($session['trainer_id'] && $session['trainer_id'] == $workshop['trainer_id']): ?>
                                        <div class="flex items-center mt-2 text-sm text-gray-500">
                                            <i class="fas fa-chalkboard-teacher mr-1"></i>
                                            <?= htmlspecialchars($workshop['trainer_name']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-gray-500">
                                Schedule details will be added soon
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Workshop Materials -->
                <?php if (count($materials) > 0): ?>
                <div class="card animate__animated animate__fadeIn animate-delay-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-file-download"></i>
                            Workshop Materials
                        </h3>
                    </div>
                    <div>
                        <?php foreach ($materials as $material): ?>
                            <div class="material-item">
                                <div class="material-icon">
                                    <?php 
                                    $icon = 'fa-file-alt';
                                    if (strpos($material['file_type'], 'slide') !== false) $icon = 'fa-file-powerpoint';
                                    elseif (strpos($material['file_type'], 'handout') !== false) $icon = 'fa-file-pdf';
                                    elseif (strpos($material['file_type'], 'exercise') !== false) $icon = 'fa-file-code';
                                    elseif (strpos($material['file_type'], 'recording') !== false) $icon = 'fa-file-video';
                                    ?>
                                    <i class="fas <?= $icon ?>"></i>
                                </div>
                                <div class="material-details">
                                    <h4 class="material-title"><?= htmlspecialchars($material['title']) ?></h4>
                                    <div class="material-meta">
                                        <?= strtoupper(pathinfo($material['file_path'], PATHINFO_EXTENSION)) ?> • 
                                        Uploaded on <?= date('M j, Y', strtotime($material['uploaded_at'])) ?>
                                    </div>
                                    <?php if ($material['description']): ?>
                                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($material['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="material-actions">
                                    <a href="<?= htmlspecialchars($material['file_path']) ?>" download>
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <a href="<?= htmlspecialchars($material['file_path']) ?>" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Column (Trainer & Actions) -->
            <div class="space-y-6">
                <!-- Trainer Card -->
                <?php if ($trainer): ?>
                <div class="card animate__animated animate__fadeIn animate-delay-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chalkboard-teacher"></i>
                            Workshop Trainer
                        </h3>
                    </div>
                    <div class="trainer-card">
                        <img src="<?= $trainer['profile_picture'] ? htmlspecialchars($trainer['profile_picture']) : '../images/default-trainer.jpg' ?>" 
                             alt="<?= htmlspecialchars($trainer['name']) ?>" 
                             class="trainer-avatar">
                        <div class="trainer-details">
                            <h3 class="trainer-name"><?= htmlspecialchars($trainer['name']) ?></h3>
                            <p class="trainer-title"><?= htmlspecialchars($trainer['specialization']) ?></p>
                            <?php if ($trainer['years_of_experience']): ?>
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-briefcase mr-1"></i>
                                <?= $trainer['years_of_experience'] ?>+ years experience
                            </p>
                            <?php endif; ?>
                            <?php if ($trainer['bio']): ?>
                            <p class="trainer-bio mt-2"><?= htmlspecialchars($trainer['bio']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Registration Card -->
                <div class="card animate__animated animate__fadeIn animate-delay-5">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-ticket-alt"></i>
                            Registration
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <div class="text-sm text-gray-500">Available Seats</div>
                                <div class="text-xl font-bold">
                                    <?= $workshop['max_participants'] - $workshop['current_registrations'] ?>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Fee</div>
                                <div class="text-xl font-bold text-blue-600">
                                    ₹<?= number_format($workshop['fee'], 2) ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($workshop['status'] === 'upcoming' || $workshop['status'] === 'ongoing'): ?>
                            <?php if ($is_registered): ?>
                                <div class="text-center p-4 bg-green-50 rounded-lg border border-green-100 mb-4">
                                    <i class="fas fa-check-circle text-green-500 text-4xl mb-2"></i>
                                    <h4 class="font-semibold text-green-800">You're Registered!</h4>
                                    <p class="text-sm text-green-600">We'll send you reminder emails as the workshop approaches.</p>
                                </div>
                                <button id="cancelRegistrationBtn" class="btn btn-danger w-full">
                                    <i class="fas fa-times"></i> Cancel Registration
                                </button>
                            <?php elseif ($workshop['current_registrations'] < $workshop['max_participants']): ?>
                                <button id="registerBtn" class="btn btn-primary w-full animate-pulse">
                                    <i class="fas fa-user-plus"></i> Register Now
                                </button>
                            <?php else: ?>
                                <div class="text-center p-4 bg-yellow-50 rounded-lg border border-yellow-100">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl mb-2"></i>
                                    <h4 class="font-semibold text-yellow-800">Workshop Full</h4>
                                    <p class="text-sm text-yellow-600">All seats have been taken for this workshop.</p>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($workshop['status'] === 'completed'): ?>
                            <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-100">
                                <i class="fas fa-check-circle text-blue-500 text-4xl mb-2"></i>
                                <h4 class="font-semibold text-blue-800">Workshop Completed</h4>
                                                                        <p class="text-sm text-blue-600">This workshop has concluded. Materials remain available for registered participants.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Workshop Progress -->
                <?php if ($is_registered): ?>
                <div class="card animate__animated animate__fadeIn animate-delay-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line"></i>
                            Your Progress
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="mb-4">
                            <div class="flex justify-between mb-1">
                                <span class="font-medium">Workshop Completion</span>
                                <span class="font-medium">25%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: 25%"></div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-blue-50 p-3 rounded-lg">
                                <div class="text-sm text-blue-800 mb-1">Sessions Attended</div>
                                <div class="text-xl font-bold text-blue-600">1/4</div>
                            </div>
                            <div class="bg-green-50 p-3 rounded-lg">
                                <div class="text-sm text-green-800 mb-1">Materials Viewed</div>
                                <div class="text-xl font-bold text-green-600">2/5</div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Floating Action Buttons -->
    <div class="floating-actions">
        <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'mentor'): ?>
            <a href="edit_workshop.php?id=<?= $workshop_id ?>" class="floating-btn floating-btn-primary">
                <i class="fas fa-edit"></i>
                <span class="tooltip">Edit Workshop</span>
            </a>
        <?php endif; ?>
        
        <?php if ($is_registered): ?>
            <button class="floating-btn floating-btn-danger" id="floatingCancelBtn">
                <i class="fas fa-user-minus"></i>
                <span class="tooltip">Cancel Registration</span>
            </button>
        <?php endif; ?>
    </div>
    
    <!-- Registration Modal -->
    <div id="registrationModal" class="registration-modal">
        <div class="modal-content animate__animated animate__fadeInDown">
            <div class="modal-header">
                <h3 class="text-xl font-bold">Confirm Registration</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>You are about to register for <strong><?= htmlspecialchars($workshop['title']) ?></strong>.</p>
                <p class="mt-2">Workshop Fee: <span class="font-bold">₹<?= number_format($workshop['fee'], 2) ?></span></p>
                
                <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                    <h4 class="font-bold text-blue-800 mb-2">Registration Terms</h4>
                    <ul class="list-disc pl-5 text-sm text-blue-600 space-y-1">
                        <li>Payment is required to confirm your spot</li>
                        <li>Cancellations must be made at least 48 hours before the workshop</li>
                        <li>Materials will be available after each session</li>
                        <?php if ($workshop['certificate_available']): ?>
                        <li>Certificate will be provided upon completion</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button id="confirmCancelBtn" class="btn btn-outline mr-2">Cancel</button>
                <button id="confirmRegisterBtn" class="btn btn-primary">Confirm Registration</button>
            </div>
        </div>
    </div>
    
    <!-- Cancellation Modal -->
    <div id="cancellationModal" class="registration-modal">
        <div class="modal-content animate__animated animate__fadeInDown">
            <div class="modal-header">
                <h3 class="text-xl font-bold">Cancel Registration</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel your registration for <strong><?= htmlspecialchars($workshop['title']) ?></strong>?</p>
                
                <div class="mt-4">
                    <label for="cancellationReason" class="block text-sm font-medium text-gray-700 mb-1">Reason for cancellation (optional)</label>
                    <textarea id="cancellationReason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <?php if ($workshop['fee'] > 0): ?>
                <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-100">
                    <h4 class="font-bold text-yellow-800 mb-2">Refund Policy</h4>
                    <p class="text-sm text-yellow-600">Cancellations made more than 48 hours before the workshop will receive a full refund. Later cancellations may incur a ₹500 processing fee.</p>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button id="cancelCancelBtn" class="btn btn-outline mr-2">Go Back</button>
                <button id="confirmCancellationBtn" class="btn btn-danger">Confirm Cancellation</button>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Registration Modal
        const registerBtn = document.getElementById('registerBtn');
        const registrationModal = document.getElementById('registrationModal');
        const closeModal = document.querySelectorAll('.close-modal');
        const confirmCancelBtn = document.getElementById('confirmCancelBtn');
        const confirmRegisterBtn = document.getElementById('confirmRegisterBtn');
        
        if (registerBtn) {
            registerBtn.addEventListener('click', () => {
                registrationModal.style.display = 'block';
            });
        }
        
        // Cancellation Modal
        const cancelRegistrationBtn = document.getElementById('cancelRegistrationBtn');
        const floatingCancelBtn = document.getElementById('floatingCancelBtn');
        const cancellationModal = document.getElementById('cancellationModal');
        const cancelCancelBtn = document.getElementById('cancelCancelBtn');
        const confirmCancellationBtn = document.getElementById('confirmCancellationBtn');
        
        if (cancelRegistrationBtn) {
            cancelRegistrationBtn.addEventListener('click', () => {
                cancellationModal.style.display = 'block';
            });
        }
        
        if (floatingCancelBtn) {
            floatingCancelBtn.addEventListener('click', () => {
                cancellationModal.style.display = 'block';
            });
        }
        
        // Close modals
        closeModal.forEach(btn => {
            btn.addEventListener('click', () => {
                registrationModal.style.display = 'none';
                cancellationModal.style.display = 'none';
            });
        });
        
        confirmCancelBtn.addEventListener('click', () => {
            registrationModal.style.display = 'none';
        });
        
        cancelCancelBtn.addEventListener('click', () => {
            cancellationModal.style.display = 'none';
        });
        
        // Close when clicking outside modal
        window.addEventListener('click', (e) => {
            if (e.target === registrationModal) {
                registrationModal.style.display = 'none';
            }
            if (e.target === cancellationModal) {
                cancellationModal.style.display = 'none';
            }
        });
        
        // Handle registration
        confirmRegisterBtn.addEventListener('click', () => {
            fetch('register_workshop.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `workshop_id=<?= $workshop_id ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert('success', 'Registration successful!');
                    
                    // Create confetti effect
                    createConfetti();
                    
                    // Close modal and reload page after delay
                    registrationModal.style.display = 'none';
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showAlert('error', data.message || 'Registration failed. Please try again.');
                }
            })
            .catch(error => {
                showAlert('error', 'An error occurred. Please try again.');
                console.error('Error:', error);
            });
        });
        
        // Handle cancellation
        confirmCancellationBtn.addEventListener('click', () => {
            const reason = document.getElementById('cancellationReason').value;
            
            fetch('cancel_registration.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `workshop_id=<?= $workshop_id ?>&reason=${encodeURIComponent(reason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Registration cancelled successfully.');
                    cancellationModal.style.display = 'none';
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('error', data.message || 'Cancellation failed. Please try again.');
                }
            })
            .catch(error => {
                showAlert('error', 'An error occurred. Please try again.');
                console.error('Error:', error);
            });
        });
        
        // Show alert message
        function showAlert(type, message) {
            const alert = document.createElement('div');
            alert.className = `fixed top-4 right-4 p-4 rounded-md shadow-md text-white animate__animated animate__fadeInRight ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
            alert.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.classList.remove('animate__fadeInRight');
                alert.classList.add('animate__fadeOutRight');
                setTimeout(() => {
                    alert.remove();
                }, 500);
            }, 3000);
        }
        
        // Create confetti effect
        function createConfetti() {
            const colors = ['#4361ee', '#3f37c9', '#4cc9f0', '#4895ef', '#f72585'];
            
            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-10px';
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = Math.random() * 10 + 5 + 'px';
                confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
                confetti.style.animationDelay = Math.random() * 2 + 's';
                document.body.appendChild(confetti);
                
                setTimeout(() => {
                    confetti.remove();
                }, 5000);
            }
        }
        
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Workshop Progress Chart
            const ctx = document.createElement('canvas');
            ctx.id = 'progressChart';
            document.querySelector('.card.animate__animated.animate__fadeIn.animate-delay-6 .p-4').appendChild(ctx);
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Remaining'],
                    datasets: [{
                        data: [25, 75],
                        backgroundColor: ['#4361ee', '#e2e8f0'],
                        borderWidth: 0
                    }]
                },
                options: {
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>