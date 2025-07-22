<?php
require_once '../db_connection.php';
require_once 'functions.php';
require_once 'filters.php';

// Check admin permissions
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
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
?>

<!DOCTYPE html>
<html lang="en" class="dark-theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainers Management | ASD Academy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --sidebar-width: 16rem;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .admin-content {
                margin-left: 0;
            }
        }
        
        .page-title {
            font-weight: 700;
            color: #2c3e50;
            position: relative;
            display: inline-block;
        }
        
        .page-title::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #3498db, #9b59b6);
            border-radius: 3px;
            animation: underlineGrow 0.5s ease-out forwards;
        }
        
        @keyframes underlineGrow {
            from { width: 0; opacity: 0; }
            to { width: 50px; opacity: 1; }
        }
        
        .trainer-card {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            transform: translateY(0);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .trainer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            background-color: rgba(52, 152, 219, 0.03);
        }
        
        .stat-card {
            border-radius: 10px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255,255,255,0.1);
            transform: rotate(30deg);
            transition: all 0.5s ease;
        }
        
        .stat-card:hover::before {
            right: 100%;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .stat-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2.5rem;
            opacity: 0.2;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .stat-icon {
            opacity: 0.4;
            transform: scale(1.1);
        }
        
        .trainer-avatar {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .trainer-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .badge-pill {
            transition: all 0.2s ease;
        }
        
        .badge-pill:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .star-rating i {
            transition: all 0.2s ease;
        }
        
        .star-rating:hover i {
            transform: scale(1.2);
        }
        
        .btn-float {
            position: relative;
            overflow: hidden;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .btn-float:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .btn-float::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255,255,255,0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%, -50%);
            transform-origin: 50% 50%;
        }
        
        .btn-float:focus:not(:active)::after {
            animation: ripple 0.6s ease-out;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.25rem 1.5rem;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            background-color: #f8f9fa;
        }
        
        .table td {
            vertical-align: middle;
            border-color: rgba(0,0,0,0.03);
        }
        
        .pagination .page-item.active .page-link {
            background-color: #3498db;
            border-color: #3498db;
        }
        
        .pagination .page-link {
            color: #3498db;
            border-radius: 8px;
            margin: 0 3px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .pagination .page-link:hover {
            background-color: #f8f9fa;
        }
        
        .filter-section {
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
        
        .btn-group .btn {
            transition: all 0.2s ease;
        }
        
        .btn-group .btn:hover {
            transform: translateY(-2px);
        }
        
        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: none;
            padding: 0.5rem;
        }
        
        .dropdown-item {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        
        /* Performance chart container */
        .chart-container {
            position: relative;
            height: 300px;
            padding: 1rem;
            transition: opacity 0.5s ease;
        }
        
        /* Loading animation */
        .loading-spinner {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .loading-spinner.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="admin-container">
        <?php include '../sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="loading-spinner">
                <div class="spinner"></div>
            </div>
            
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4 animate__animated animate__fadeIn">
                    <h1 class="page-title">Trainers Management</h1>
                    <a href="add.php" class="btn btn-primary btn-float" data-bs-toggle="tooltip" title="Add New Trainer">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
                
                <!-- Filters Section -->
                <div class="card mb-4 animate__animated animate__fadeIn animate-delay-1">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-filter me-2"></i>
                            Filters
                        </h5>
                        <button class="btn btn-sm btn-link" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="collapse show" id="filterCollapse">
                        <div class="card-body">
                            <form id="filterForm" method="get" class="row g-3">
                                <div class="col-md-3">
                                    <label for="search" class="form-label">
                                        <i class="fas fa-search me-1"></i>
                                        Search
                                    </label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>" 
                                           placeholder="Name, email, specialization...">
                                </div>
                                <div class="col-md-2">
                                    <label for="status" class="form-label">
                                        <i class="fas fa-user-check me-1"></i>
                                        Status
                                    </label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">All</option>
                                        <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="specialization" class="form-label">
                                        <i class="fas fa-certificate me-1"></i>
                                        Specialization
                                    </label>
                                    <select class="form-select" id="specialization" name="specialization">
                                        <option value="">All</option>
                                        <?php foreach (getTrainerSpecializations() as $spec): ?>
                                            <option value="<?= htmlspecialchars($spec) ?>" 
                                                <?= ($filters['specialization'] ?? '') === $spec ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($spec) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="sort" class="form-label">
                                        <i class="fas fa-sort me-1"></i>
                                        Sort By
                                    </label>
                                    <select class="form-select" id="sort" name="sort">
                                        <option value="name_asc" <?= ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                                        <option value="name_desc" <?= ($filters['sort'] ?? '') === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                                        <option value="newest" <?= ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest First</option>
                                        <option value="oldest" <?= ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Apply
                                    </button>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-undo me-1"></i>
                                        Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Overview -->
                <div class="row mb-4 animate__animated animate__fadeIn animate-delay-2">
                    <div class="col-md-12">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Trainers Performance Overview
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="stat-card bg-primary text-white">
                                            <div class="stat-value"><?= count($trainers) ?></div>
                                            <div class="stat-label">Total Trainers</div>
                                            <i class="stat-icon fas fa-chalkboard-teacher"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-card bg-success text-white">
                                            <div class="stat-value"><?= $performanceStats['active_count'] ?></div>
                                            <div class="stat-label">Active Trainers</div>
                                            <i class="stat-icon fas fa-user-check"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-card bg-info text-white">
                                            <div class="stat-value"><?= $performanceStats['avg_rating'] ? round($performanceStats['avg_rating'], 1) : 'N/A' ?></div>
                                            <div class="stat-label">Avg. Rating</div>
                                            <i class="stat-icon fas fa-star"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-card bg-warning text-dark">
                                            <div class="stat-value"><?= $performanceStats['total_batches'] ?></div>
                                            <div class="stat-label">Active Batches</div>
                                            <i class="stat-icon fas fa-users-class"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Performance Chart -->
                                <?php if (!empty($performanceStats['top_trainers'])): ?>
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="chart-container">
                                            <canvas id="performanceChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Trainers List -->
                <div class="card animate__animated animate__fadeIn animate-delay-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Trainers List
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                    id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download me-1"></i> Export
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                <li><a class="dropdown-item" href="#" id="exportCSV"><i class="fas fa-file-csv me-2"></i> CSV</a></li>
                                <li><a class="dropdown-item" href="#" id="exportExcel"><i class="fas fa-file-excel me-2"></i> Excel</a></li>
                                <li><a class="dropdown-item" href="#" id="exportPDF"><i class="fas fa-file-pdf me-2"></i> PDF</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($trainers)): ?>
                            <div class="alert alert-info animate__animated animate__fadeIn">
                                <i class="fas fa-info-circle me-2"></i>
                                No trainers found matching your criteria.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="trainersTable">
                                    <thead>
                                        <tr>
                                            <th>Photo</th>
                                            <th>Name</th>
                                            <th>Specialization</th>
                                            <th>Experience</th>
                                            <th>Batches</th>
                                            <th>Rating</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($trainers as $index => $trainer): 
                                            $batchCount = getTrainerBatchCount($trainer['id']);
                                            $avgRating = getTrainerAverageRating($trainer['id']);
                                            ?>
                                            <tr class="trainer-card animate__animated animate__fadeIn" 
                                                data-trainer-id="<?= $trainer['id'] ?>"
                                                style="animation-delay: <?= ($index % 10) * 0.05 ?>s">
                                                <td>
                                                    <img src="<?= getTrainerPhoto($trainer) ?>" 
                                                         class="rounded-circle trainer-avatar" 
                                                         width="40" height="40" 
                                                         alt="<?= htmlspecialchars($trainer['name']) ?>"
                                                         onerror="this.src='../assets/images/default-avatar.svg'">
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($trainer['name']) ?></strong>
                                                    <div class="text-muted small"><?= htmlspecialchars($trainer['email']) ?></div>
                                                </td>
                                                <td>
                                                    <?php if ($trainer['specialization']): ?>
                                                        <span class="badge bg-info bg-opacity-10 text-info">
                                                            <?= htmlspecialchars($trainer['specialization']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not specified</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= $trainer['years_of_experience'] ?? 0 ?> year<?= ($trainer['years_of_experience'] ?? 0) != 1 ? 's' : '' ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary rounded-pill">
                                                        <?= $batchCount ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($avgRating): ?>
                                                        <div class="star-rating">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <i class="fas fa-star <?= $i <= round($avgRating) ? 'text-warning' : 'text-secondary' ?>"></i>
                                                            <?php endfor; ?>
                                                            <small class="text-muted ms-1">(<?= round($avgRating, 1) ?>)</small>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">No ratings</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill bg-<?= $trainer['is_active'] ? 'success' : 'secondary' ?>">
                                                        <?= $trainer['is_active'] ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="view.php?id=<?= $trainer['id'] ?>" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           data-bs-toggle="tooltip" title="View Profile">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit.php?id=<?= $trainer['id'] ?>" 
                                                           class="btn btn-sm btn-outline-secondary" 
                                                           data-bs-toggle="tooltip" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-outline-<?= $trainer['is_active'] ? 'danger' : 'success' ?> toggle-status" 
                                                                data-id="<?= $trainer['id'] ?>" 
                                                                data-status="<?= $trainer['is_active'] ? 1 : 0 ?>"
                                                                data-bs-toggle="tooltip" 
                                                                title="<?= $trainer['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                            <i class="fas fa-power-off"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            // Show loading spinner
            $('.loading-spinner').addClass('active');
            
            // Hide loading spinner after everything is loaded
            $(window).on('load', function() {
                setTimeout(function() {
                    $('.loading-spinner').removeClass('active');
                }, 500);
            });
            
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip({
                animation: true,
                delay: { "show": 100, "hide": 50 }
            });
            
            // Toggle trainer status
            $('.toggle-status').click(function() {
                const trainerId = $(this).data('id');
                const currentStatus = $(this).data('status');
                const newStatus = currentStatus ? 0 : 1;
                const action = currentStatus ? 'deactivate' : 'activate';
                
                Swal.fire({
                    title: `Are you sure you want to ${action} this trainer?`,
                    text: "This will affect their access to the system.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: `Yes, ${action}`,
                    showClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutUp'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('.loading-spinner').addClass('active');
                        
                        $.ajax({
                            url: 'status.php',
                            method: 'POST',
                            data: {
                                id: trainerId,
                                status: newStatus
                            },
                            success: function(response) {
                                $('.loading-spinner').removeClass('active');
                                
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: `Trainer has been ${action}d.`,
                                        icon: 'success',
                                        showClass: {
                                            popup: 'animate__animated animate__fadeIn'
                                        }
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: response.message || 'Something went wrong.',
                                        icon: 'error',
                                        showClass: {
                                            popup: 'animate__animated animate__headShake'
                                        }
                                    });
                                }
                            },
                            error: function() {
                                $('.loading-spinner').removeClass('active');
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Failed to update trainer status.',
                                    icon: 'error',
                                    showClass: {
                                        popup: 'animate__animated animate__headShake'
                                    }
                                });
                            }
                        });
                    }
                });
            });
            
            // Export buttons
            $('#exportCSV').click(function(e) {
                e.preventDefault();
                exportTrainers('csv');
            });
            
            $('#exportExcel').click(function(e) {
                e.preventDefault();
                exportTrainers('excel');
            });
            
            $('#exportPDF').click(function(e) {
                e.preventDefault();
                exportTrainers('pdf');
            });
            
            function exportTrainers(format) {
                $('.loading-spinner').addClass('active');
                const filters = <?= json_encode($filters) ?>;
                filters.export = format;
                
                let url = 'export.php?' + $.param(filters);
                window.open(url, '_blank');
                
                setTimeout(function() {
                    $('.loading-spinner').removeClass('active');
                }, 1000);
            }
            
            // Initialize performance chart
            const ctx = document.getElementById('performanceChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_column($performanceStats['top_trainers'], 'name')) ?>,
                        datasets: [{
                            label: 'Average Rating',
                            data: <?= json_encode(array_column($performanceStats['top_trainers'], 'avg_rating')) ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            borderRadius: 6,
                            borderSkipped: false
                        }, {
                            label: 'Batches',
                            data: <?= json_encode(array_column($performanceStats['top_trainers'], 'batch_count')) ?>,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0,0,0,0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuad'
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 12
                                },
                                padding: 12,
                                cornerRadius: 8,
                                usePointStyle: true
                            }
                        }
                    }
                });
            }
            
            // Add ripple effect to buttons
            $('.btn').on('click', function(e) {
                const btn = $(this);
                const x = e.pageX - btn.offset().left;
                const y = e.pageY - btn.offset().top;
                
                const ripple = $('<span class="ripple-effect"></span>').css({
                    left: x,
                    top: y
                });
                
                btn.append(ripple);
                
                setTimeout(function() {
                    ripple.remove();
                }, 1000);
            });
            
            // Filter form submission with loading
            $('#filterForm').on('submit', function() {
                $('.loading-spinner').addClass('active');
            });
        });
    </script>
</body>
</html> 