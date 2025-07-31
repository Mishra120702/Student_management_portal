
<?php
require_once '../db_connection.php';
require_once 'functions.php';
require_once 'filters.php';

// Check admin permissions
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../unauthorized.php");
    exit;
}

// Get filters from request
$filters = getTrainerFilters($_GET);

// Get pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get trainers with filters
$trainers = getFilteredTrainers($filters, $perPage, $offset);
$totalTrainers = getTotalFilteredTrainers($filters);
$totalPages = ceil($totalTrainers / $perPage);

// Get performance stats for all trainers
$performanceStats = getTrainersPerformanceStats();

// Get all specializations for filter dropdown
$allSpecializations = getTrainerSpecializations();

// Get status distribution for chart
$statusDistribution = getTrainerStatusDistribution();
function getTrainerStatusDistribution(): array {
    global $db;
    $active = 0;
    $inactive = 0;

    $stmt = $db->query("SELECT is_active, COUNT(*) as count FROM trainers GROUP BY is_active");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['is_active']) {
            $active = (int)$row['count'];
        } else {
            $inactive = (int)$row['count'];
        }
    }
    return [
        'active' => $active,
        'inactive' => $inactive
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainers Management - ASD Academy</title>
    
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.3.4/css/select.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    
    <style>
        :root {
            --primary: #3b82f6;
            --primary-light: #93c5fd;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #6366f1;
            --dark: #1f2937;
            --light: #f3f4f6;
        }
        
        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 50rem;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        
        .badge-primary {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        
        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .stat-card {
            border-radius: 0.5rem;
            padding: 1.5rem;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid;
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stat-card.primary {
            border-left-color: var(--primary);
        }
        
        .stat-card.success {
            border-left-color: var(--success);
        }
        
        .stat-card.warning {
            border-left-color: var(--warning);
        }
        
        .stat-card.info {
            border-left-color: var(--info);
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--dark);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.1;
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            color: inherit;
        }
        
        .stat-change {
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            margin-top: 0.25rem;
        }
        
        .stat-change.positive {
            color: var(--success);
        }
        
        .stat-change.negative {
            color: var(--danger);
        }
        
        .trainer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .trainer-avatar:hover {
            transform: scale(1.1);
        }
        .detail{
            transition: transform 0.2s;
        }
        .detail:hover {
            background-color: #bfdbfe;
            shadow: 0 2px 4px rgba(0,0,0,0.1);
            transform: scale(1.01);
         }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .star-rating i {
            font-size: 0.9rem;
        }
        
        .star-rating .filled {
            color: #f59e0b;
        }
        
        .star-rating .empty {
            color: #d1d5db;
        }
        
        .loading-spinner {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        .loading-spinner.active {
            display: flex;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
        
        .filter-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .filter-title {
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
        }
        
        .filter-title i {
            margin-right: 0.5rem;
            color: var(--primary);
        }
        
        .filter-toggle {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 0.875rem;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .filter-toggle i {
            margin-left: 0.25rem;
            transition: transform 0.2s;
        }
        
        .filter-toggle.collapsed i {
            transform: rotate(180deg);
        }
        
        .filter-body {
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .filter-body.collapsed {
            height: 0 !important;
            opacity: 0;
        }
        
        .choices__list--multiple .choices__item {
            background-color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .dt-buttons .dt-button {
            background: white;
            border: 1px solid #d1d5db;
            color: var(--dark);
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            margin-right: 0.5rem;
            transition: all 0.2s;
        }
        
        .dt-buttons .dt-button:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .bulk-actions {
            display: none;
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            align-items: center;
            justify-content: space-between;
        }
        
        .bulk-actions.active {
            display: flex;
        }
        
        .bulk-selected-count {
            font-weight: 600;
            color: var(--dark);
        }
        
        .bulk-action-btn {
            background: white;
            border: 1px solid #d1d5db;
            color: var(--dark);
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            margin-left: 0.5rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
        }
        
        .bulk-action-btn i {
            margin-right: 0.25rem;
        }
        
        .bulk-action-btn:hover {
            background: var(--primary-light);
            border-color: var(--primary);
        }
        
        .bulk-action-btn.danger:hover {
            background: #fee2e2;
            border-color: var(--danger);
            color: var(--danger);
        }
        
        .dataTables_wrapper .dataTables_info {
            padding-top: 1rem !important;
        }
        
        .dataTables_wrapper .dataTables_paginate {
            padding-top: 1rem !important;
        }
        
        .minimal-input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background-color: white;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }
        
        .minimal-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            color: white;
        }
        
        .btn-primary i {
            margin-right: 0.5rem;
        }
        
        .btn-gray {
            background-color: white;
            color: var(--dark);
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s;
            border: 1px solid #d1d5db;
        }
        
        .btn-gray:hover {
            background-color: #f3f4f6;
            transform: translateY(-1px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            color: var(--dark);
        }
        
        .btn-gray i {
            margin-right: 0.5rem;
        }
        
        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .progress-bar {
            height: 0.5rem;
            background-color: #e5e7eb;
            border-radius: 0.25rem;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 0.25rem;
        }
        
        .progress-primary {
            background-color: var(--primary);
        }
        
        .progress-success {
            background-color: var(--success);
        }
        
        .progress-warning {
            background-color: var(--warning);
        }
        
        .progress-danger {
            background-color: var(--danger);
        }
        
        .tooltip {
            position: relative;
            display: inline-block;
        }
        
        .tooltip .tooltip-text {
            visibility: hidden;
            width: 120px;
            background-color: var(--dark);
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.75rem;
        }
        
        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .stat-card {
                padding: 1rem;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .stat-icon {
                font-size: 2rem;
                right: 1rem;
                top: 1rem;
            }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>
    
    <div class="flex-1 ml-0 md:ml-64 min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
            <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fas fa-chalkboard-teacher text-blue-500"></i>
                <span>Trainers Management</span>
            </h1>
            <div class="flex items-center space-x-4">
                <a href="add.php" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>Add Trainer
                </a>
            </div>
        </header>

        <div class="p-4 md:p-6">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="stat-card primary">
                    <div class="stat-value"><?= count($trainers) ?></div>
                    <div class="stat-label">Current Trainers</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up mr-1"></i>
                        <span>12% from last month</span>
                    </div>
                    <i class="stat-icon fas fa-chalkboard-teacher"></i>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-value"><?= $performanceStats['active_count'] ?></div>
                    <div class="stat-label">Active Trainers</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up mr-1"></i>
                        <span>5% from last month</span>
                    </div>
                    <i class="stat-icon fas fa-user-check"></i>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-value"><?= $performanceStats['avg_rating'] ? round($performanceStats['avg_rating'], 1) : 'N/A' ?></div>
                    <div class="stat-label">Avg. Rating</div>
                    <div class="stat-change <?= $performanceStats['rating_change'] >= 0 ? 'positive' : 'negative' ?>">
                        <i class="fas fa-arrow-<?= $performanceStats['rating_change'] >= 0 ? 'up' : 'down' ?> mr-1"></i>
                        <span><?= abs($performanceStats['rating_change']) ?>% from last month</span>
                    </div>
                    <i class="stat-icon fas fa-star"></i>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-value"><?= $performanceStats['total_batches'] ?></div>
                    <div class="stat-label">Active Batches</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up mr-1"></i>
                        <span>8% from last month</span>
                    </div>
                    <i class="stat-icon fas fa-users-class"></i>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Trainers by Status</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Rating Distribution</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="ratingChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Bulk Actions Panel (hidden by default) -->
            <div class="bulk-actions" id="bulkActionsPanel">
                <div class="bulk-selected-count" id="selectedCount">0 trainers selected</div>
                <div>
                    <button class="bulk-action-btn" id="bulkActivateBtn">
                        <i class="fas fa-check-circle"></i> Activate
                    </button>
                    <button class="bulk-action-btn" id="bulkDeactivateBtn">
                        <i class="fas fa-ban"></i> Deactivate
                    </button>
                    <button class="bulk-action-btn danger" id="bulkDeleteBtn">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                    <button class="bulk-action-btn" id="clearSelectionBtn">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
            
            <!-- Filter Card -->
            <div class="filter-card">
                <div class="filter-header">
                    <div class="filter-title">
                        <i class="fas fa-filter"></i>
                        <span>Filter Trainers</span>
                    </div>
                    <button class="filter-toggle" id="filterToggle">
                        <span>Show Filters</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                
                <div class="filter-body" id="filterBody">
                    <form method="GET" action="" id="filterForm">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" name="search" placeholder="Search trainers..." 
                                       class="minimal-input" value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                                       id="searchInput">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" class="minimal-input" id="statusSelect">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Specialization</label>
                                <select name="specialization" class="minimal-input" id="specializationSelect">
                                    <option value="">All Specializations</option>
                                    <?php foreach ($allSpecializations as $spec): ?>
                                        <option value="<?= htmlspecialchars($spec) ?>" 
                                            <?= ($filters['specialization'] ?? '') === $spec ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($spec) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Experience</label>
                                <select name="experience" class="minimal-input" id="experienceSelect">
                                    <option value="">Any Experience</option>
                                    <option value="1-3" <?= ($filters['experience'] ?? '') === '1-3' ? 'selected' : '' ?>>1-3 years</option>
                                    <option value="4-6" <?= ($filters['experience'] ?? '') === '4-6' ? 'selected' : '' ?>>4-6 years</option>
                                    <option value="7+" <?= ($filters['experience'] ?? '') === '7+' ? 'selected' : '' ?>>7+ years</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
                                <select name="rating" class="minimal-input" id="ratingSelect">
                                    <option value="">Any Rating</option>
                                    <option value="4+" <?= ($filters['rating'] ?? '') === '4+' ? 'selected' : '' ?>>4+ Stars</option>
                                    <option value="3-4" <?= ($filters['rating'] ?? '') === '3-4' ? 'selected' : '' ?>>3-4 Stars</option>
                                    <option value="1-3" <?= ($filters['rating'] ?? '') === '1-3' ? 'selected' : '' ?>>1-3 Stars</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Join Date</label>
                                <select name="join_date" class="minimal-input" id="joinDateSelect">
                                    <option value="">Any Time</option>
                                    <option value="last-week" <?= ($filters['join_date'] ?? '') === 'last-week' ? 'selected' : '' ?>>Last Week</option>
                                    <option value="last-month" <?= ($filters['join_date'] ?? '') === 'last-month' ? 'selected' : '' ?>>Last Month</option>
                                    <option value="last-year" <?= ($filters['join_date'] ?? '') === 'last-year' ? 'selected' : '' ?>>Last Year</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                                <select name="sort" class="minimal-input" id="sortSelect">
                                    <option value="">Default</option>
                                    <option value="name_asc" <?= ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                                    <option value="name_desc" <?= ($filters['sort'] ?? '') === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                                    <option value="rating_high" <?= ($filters['sort'] ?? '') === 'rating_high' ? 'selected' : '' ?>>Rating (High-Low)</option>
                                    <option value="rating_low" <?= ($filters['sort'] ?? '') === 'rating_low' ? 'selected' : '' ?>>Rating (Low-High)</option>
                                    <option value="newest" <?= ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                    <option value="oldest" <?= ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                </select>
                            </div>
                            
                            <div class="flex items-end space-x-2">
                                <button type="submit" class="btn-primary flex-1">
                                    <i class="fas fa-filter mr-2"></i>Apply
                                </button>
                                <a href="index.php" class="btn-gray">
                                    <i class="fas fa-undo mr-2"></i>Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Trainers Table Card -->
            <div class="card">
                <?php if (empty($trainers)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-user-slash text-gray-400 text-5xl mb-4"></i>
                        <h3 class="text-xl font-medium text-gray-700">No Trainers Found</h3>
                        <p class="text-gray-500 mb-4">No trainers match your current filters. Try adjusting your search criteria.</p>
                        <a href="index.php" class="btn-primary inline-block">
                            <i class="fas fa-undo mr-2"></i>Reset Filters
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="trainersTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Trainer</th>
                                    <th>Specialization</th>
                                    <th>Experience</th>
                                    <th>Batches</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trainers as $trainer): 
                                    $batchCount = getTrainerBatchCount($trainer['id']);
                                    $avgRating = getTrainerAverageRating($trainer['id']);
                                    $joinDate = isset($trainer['join_date']) && $trainer['join_date'] ? new DateTime($trainer['join_date']) : null;
                                    ?>
                                    <tr class="detail"><a href="view.php?id=<?= $trainer['id'] ?>">
                                        <td>
                                            <div class="flex items-center">
                                                
                                                <img src="<?= getTrainerPhoto($trainer) ?>" 
                                                     class="trainer-avatar mr-3" 
                                                     alt="<?= htmlspecialchars($trainer['name']) ?>"
                                                     onerror="this.src='../assets/images/default-avatar.svg'">
                                                <div>
                                                    <div class="font-medium"><?= htmlspecialchars($trainer['name']) ?></div>
                                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($trainer['email']) ?></div>
                                                    <div class="text-xs text-gray-400 mt-1">
                                                        <?php if ($joinDate): ?>
                                                            Joined <?= $joinDate->format('M Y') ?>
                                                        <?php else: ?>
                                                            <span class="text-gray-300">Join date N/A</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div></a>
                                        </td>
                                        <td>
                                            <?php if ($trainer['specialization']): ?>
                                                <span class="badge badge-primary">
                                                    <?= htmlspecialchars($trainer['specialization']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="flex items-center">
                                                <span class="mr-2"><?= $trainer['years_of_experience'] ?? 0 ?> year<?= ($trainer['years_of_experience'] ?? 0) != 1 ? 's' : '' ?></span>
                                                <div class="tooltip">
                                                    <i class="fas fa-info-circle text-gray-400"></i>
                                                    <span class="tooltip-text"><?= $trainer['years_of_experience'] ?? 0 ?> years of experience</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center">
                                                <span class="badge badge-info mr-2">
                                                    <?= $batchCount ?>
                                                </span>
                                                <?php if ($batchCount > 0): ?>
                                                    <div class="progress-bar w-16">
                                                        <div class="progress-fill progress-primary" style="width: <?= min(100, ($batchCount / 5) * 100) ?>%"></div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($avgRating): ?>
                                                <div class="star-rating flex items-center">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?= $i <= round($avgRating) ? 'filled' : 'empty' ?>"></i>
                                                    <?php endfor; ?>
                                                    <span class="text-sm text-gray-500 ml-1">(<?= round($avgRating, 1) ?>)</span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $trainer['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                                <?= $trainer['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex space-x-2">
                                                <!-- <a href="view.php?id=<?= $trainer['id'] ?>" 
                                                   class="action-btn bg-blue-100 text-blue-600 hover:bg-blue-200 tooltip"
                                                   data-tooltip="View">
                                                    <i class="fas fa-eye"></i>
                                                </a> -->
                                                <a href="edit.php?id=<?= $trainer['id'] ?>" 
                                                   class="action-btn bg-gray-100 text-gray-600 hover:bg-gray-200 tooltip"
                                                   data-tooltip="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="action-btn <?= $trainer['is_active'] ? 'bg-red-100 text-red-600 hover:bg-red-200' : 'bg-green-100 text-green-600 hover:bg-green-200' ?> toggle-status tooltip" 
                                                        data-id="<?= $trainer['id'] ?>" 
                                                        data-status="<?= $trainer['is_active'] ? 1 : 0 ?>"
                                                        data-tooltip="<?= $trainer['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                            </div>
                                        </td></a>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Loading Spinner -->
    <div class="loading-spinner">
        <div class="spinner"></div>
        <div class="text-gray-600">Processing...</div>
    </div>
    
    <!-- Include JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.3.4/js/dataTables.select.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize loading spinner
        const loadingSpinner = $('.loading-spinner');
        
        // Show loading spinner initially
        loadingSpinner.addClass('active');
        
        // Hide loading spinner after everything is loaded
        $(window).on('load', function() {
            setTimeout(function() {
                loadingSpinner.removeClass('active');
            }, 500);
        });
        
        // Initialize filter toggle
        const filterToggle = $('#filterToggle');
        const filterBody = $('#filterBody');
        
        filterToggle.click(function() {
            $(this).toggleClass('collapsed');
            filterBody.toggleClass('collapsed');
            
            if ($(this).hasClass('collapsed'))
            filterToggle.text($(this).hasClass('collapsed') ? 'Show Filters' : 'Hide Filters');
        });

        // Initialize DataTable
        const table = $('#trainersTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'csv',
                    text: '<i class="fas fa-file-csv mr-1"></i> CSV',
                    className: 'btn-gray',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6] // Exclude checkbox and actions columns
                    }
                },
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel mr-1"></i> Excel',
                    className: 'btn-gray',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6]
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf mr-1"></i> PDF',
                    className: 'btn-gray',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6]
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print mr-1"></i> Print',
                    className: 'btn-gray',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6]
                    }
                }
            ],
            columnDefs: [
                {
                    orderable: false,
                    targets: [0, 7] // Make checkbox and actions columns not sortable
                },
                {
                    searchable: false,
                    targets: [0, 7] // Make checkbox and actions columns not searchable
                }
            ],
            responsive: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
                paginate: {
                    previous: '<i class="fas fa-chevron-left"></i>',
                    next: '<i class="fas fa-chevron-right"></i>'
                }
            },
            initComplete: function() {
                $('.dataTables_filter input').addClass('minimal-input');
                $('.dt-buttons').addClass('mb-3');
            }
        });

        // Initialize select inputs with Choices.js
        new Choices('#statusSelect', {
            removeItemButton: true,
            searchEnabled: false
        });

        new Choices('#specializationSelect', {
            removeItemButton: true
        });

        new Choices('#experienceSelect', {
            removeItemButton: true,
            searchEnabled: false
        });

        new Choices('#ratingSelect', {
            removeItemButton: true,
            searchEnabled: false
        });

        new Choices('#joinDateSelect', {
            removeItemButton: true,
            searchEnabled: false
        });

        new Choices('#sortSelect', {
            removeItemButton: true,
            searchEnabled: false
        });

        // Bulk actions functionality
        const bulkActionsPanel = $('#bulkActionsPanel');
        const selectAllCheckbox = $('#selectAllCheckbox');
        const trainerCheckboxes = $('.trainer-checkbox');
        const selectedCount = $('#selectedCount');
        const bulkActivateBtn = $('#bulkActivateBtn');
        const bulkDeactivateBtn = $('#bulkDeactivateBtn');
        const bulkDeleteBtn = $('#bulkDeleteBtn');
        const clearSelectionBtn = $('#clearSelectionBtn');

        // Toggle bulk actions panel based on selections
        function updateBulkActions() {
            const selectedCountValue = $('.trainer-checkbox:checked').length;
            if (selectedCountValue > 0) {
                bulkActionsPanel.addClass('active');
                selectedCount.text(selectedCountValue + ' trainer' + (selectedCountValue !== 1 ? 's' : '') + ' selected');
            } else {
                bulkActionsPanel.removeClass('active');
            }
        }

        // Select all/none functionality
        selectAllCheckbox.on('change', function() {
            trainerCheckboxes.prop('checked', this.checked);
            updateBulkActions();
        });

        trainerCheckboxes.on('change', function() {
            selectAllCheckbox.prop('checked', false);
            updateBulkActions();
        });

        // Clear selection
        clearSelectionBtn.on('click', function() {
            trainerCheckboxes.prop('checked', false);
            selectAllCheckbox.prop('checked', false);
            updateBulkActions();
        });

        // Bulk activate
        bulkActivateBtn.on('click', function() {
            const selectedIds = $('.trainer-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedIds.length > 0) {
                updateTrainerStatus(selectedIds, 1);
            }
        });

        // Bulk deactivate
        bulkDeactivateBtn.on('click', function() {
            const selectedIds = $('.trainer-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedIds.length > 0) {
                updateTrainerStatus(selectedIds, 0);
            }
        });

        // Bulk delete
        bulkDeleteBtn.on('click', function() {
            const selectedIds = $('.trainer-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedIds.length > 0) {
                deleteTrainers(selectedIds);
            }
        });

        // Individual status toggle
        $('.toggle-status').on('click', function() {
            const trainerId = $(this).data('id');
            const newStatus = $(this).data('status') ? 0 : 1;
            updateTrainerStatus([trainerId], newStatus);
        });

        // Update trainer status function
        function updateTrainerStatus(ids, status) {
            loadingSpinner.addClass('active');
            
            Swal.fire({
                title: 'Are you sure?',
                text: status ? 'This will activate the selected trainers.' : 'This will deactivate the selected trainers.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, proceed'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'update_trainer_status.php',
                        method: 'POST',
                        data: {
                            ids: ids,
                            status: status
                        },
                        success: function(response) {
                            loadingSpinner.removeClass('active');
                            if (response.success) {
                                Swal.fire(
                                    'Success!',
                                    response.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message || 'Something went wrong',
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            loadingSpinner.removeClass('active');
                            Swal.fire(
                                'Error!',
                                'Failed to update trainer status',
                                'error'
                            );
                        }
                    });
                } else {
                    loadingSpinner.removeClass('active');
                }
            });
        }

        // Delete trainers function
        function deleteTrainers(ids) {
            loadingSpinner.addClass('active');
            
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will permanently delete the selected trainers.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete them!',
                dangerMode: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'delete_trainers.php',
                        method: 'POST',
                        data: {
                            ids: ids
                        },
                        success: function(response) {
                            loadingSpinner.removeClass('active');
                            if (response.success) {
                                Swal.fire(
                                    'Deleted!',
                                    response.message,
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error!',
                                    response.message || 'Something went wrong',
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            loadingSpinner.removeClass('active');
                            Swal.fire(
                                'Error!',
                                'Failed to delete trainers',
                                'error'
                            );
                        }
                    });
                } else {
                    loadingSpinner.removeClass('active');
                }
            });
        }

        // Initialize charts
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const ratingCtx = document.getElementById('ratingChart').getContext('2d');

        // Status distribution chart
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Inactive'],
                datasets: [{
                    data: [<?= $statusDistribution['active'] ?>, <?= $statusDistribution['inactive'] ?>],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderColor: [
                        'rgba(16, 185, 129, 1)',
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Rating distribution chart
        const ratingChart = new Chart(ratingCtx, {
            type: 'bar',
            data: {
                labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                datasets: [{
                    label: 'Number of Trainers',
                    data: [
                        <?= $performanceStats['rating_1'] ?>,
                        <?= $performanceStats['rating_2'] ?>,
                        <?= $performanceStats['rating_3'] ?>,
                        <?= $performanceStats['rating_4'] ?>,
                        <?= $performanceStats['rating_5'] ?>
                    ],
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Debounced search
        let searchTimeout;
        $('#searchInput').on('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                $('#filterForm').submit();
            }, 500);
        });

        // Sidebar toggle for mobile
        function toggleSidebar() {
            $('.sidebar').toggleClass('hidden');
        }
    });
</script>

</body>
</html>