<?php
include '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Get selected month, year, and batch from GET parameters
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;
$selectedYear = isset($_GET['year']) ? $_GET['year'] : $currentYear;
$selectedBatch = isset($_GET['batch']) ? $_GET['batch'] : null;

// Validate month and year
if (!is_numeric($selectedMonth)) $selectedMonth = $currentMonth;
if (!is_numeric($selectedYear)) $selectedYear = $currentYear;

// Get all batches for dropdown
$batchesQuery = $db->query("SELECT batch_id, course_name FROM batches ORDER BY batch_id");
$batches = $batchesQuery->fetchAll(PDO::FETCH_ASSOC);

// Build query for classes dates
$classesQueryParams = [$selectedMonth, $selectedYear];
$classesQuerySql = "
    SELECT DISTINCT date, DAYNAME(date) as day_name 
    FROM attendance 
    WHERE MONTH(date) = ? AND YEAR(date) = ?
";

if ($selectedBatch) {
    $classesQuerySql .= " AND batch_id = ?";
    $classesQueryParams[] = $selectedBatch;
}

$classesQuerySql .= " ORDER BY date DESC";

$classesQuery = $db->prepare($classesQuerySql);
$classesQuery->execute($classesQueryParams);
$classesDates = $classesQuery->fetchAll(PDO::FETCH_ASSOC);

// Get attendance summary for each class date
foreach ($classesDates as &$classDate) {
    $summaryQueryParams = [$classDate['date']];
    $summaryQuerySql = "
        SELECT 
            COUNT(*) as total_students,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count
        FROM attendance 
        WHERE date = ?
    ";
    
    if ($selectedBatch) {
        $summaryQuerySql .= " AND batch_id = ?";
        $summaryQueryParams[] = $selectedBatch;
    }
    
    $summaryQuery = $db->prepare($summaryQuerySql);
    $summaryQuery->execute($summaryQueryParams);
    $summary = $summaryQuery->fetch(PDO::FETCH_ASSOC);
    
    $classDate['total_students'] = $summary['total_students'];
    $classDate['present_count'] = $summary['present_count'];
    $classDate['absent_count'] = $summary['absent_count'];
    $classDate['late_count'] = $summary['late_count'];
}

// Get months for dropdown
$months = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];

// Get years for dropdown (last 5 years and next 2 years)
$currentYear = date('Y');
$years = [];
for ($i = $currentYear - 5; $i <= $currentYear + 2; $i++) {
    $years[$i] = $i;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes Occurred - ASD Academy</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <span>Classes Occurred</span>
            </h1>
            <div class="flex items-center space-x-4">
                <a href="../logout.php" class="text-sm text-red-600 hover:underline flex items-center space-x-1">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>    
            </div>
        </header>

        <div class="p-4 md:p-6">
            <!-- Filter Section -->
            <div class="bg-white p-5 rounded-xl shadow mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Filter Classes</h2>
                <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                        <select name="month" class="w-full border rounded px-3 py-2 bg-gray-50">
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?= $num ?>" <?= $num == $selectedMonth ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                        <select name="year" class="w-full border rounded px-3 py-2 bg-gray-50">
                            <?php foreach ($years as $year): ?>
                                <option value="<?= $year ?>" <?= $year == $selectedYear ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Batch</label>
                        <select name="batch" class="w-full border rounded px-3 py-2 bg-gray-50">
                            <option value="">All Batches</option>
                            <?php foreach ($batches as $batch): ?>
                                <option value="<?= $batch['batch_id'] ?>" <?= $batch['batch_id'] == $selectedBatch ? 'selected' : '' ?>>
                                    <?= $batch['batch_id'] ?> - <?= $batch['course_name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                 </form>
            </div>

            <!-- Summary Card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-5 rounded-xl shadow border-l-4 border-blue-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Classes</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= count($classesDates) ?></h3>
                        </div>
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-calendar-day text-lg"></i>
                        </div>
                    </div>
                </div>
                
                <?php
                // Calculate total attendance stats
                $totalPresent = 0;
                $totalAbsent = 0;
                $totalLate = 0;
                $totalStudents = 0;
                
                foreach ($classesDates as $class) {
                    $totalPresent += $class['present_count'];
                    $totalAbsent += $class['absent_count'];
                    $totalLate += $class['late_count'];
                    $totalStudents += $class['total_students'];
                }
                
                $attendanceRate = $totalStudents > 0 ? round(($totalPresent / $totalStudents) * 100, 1) : 0;
                ?>
                
                <div class="bg-white p-5 rounded-xl shadow border-l-4 border-green-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Attendance Rate</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $attendanceRate ?>%</h3>
                        </div>
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-user-check text-lg"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-5 rounded-xl shadow border-l-4 border-purple-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Students</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1"><?= $totalStudents ?></h3>
                        </div>
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-users text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Classes List -->
            <div class="bg-white p-5 rounded-xl shadow">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Class Dates</h2>
                    <?php if ($selectedBatch): ?>
                        <span class="text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded-full">
                            Batch: <?= $selectedBatch ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($classesDates)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-calendar-times text-4xl mb-3"></i>
                        <p>No classes found for the selected period.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Present</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Absent</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Late</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($classesDates as $class): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?= date('M j, Y', strtotime($class['date'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?= $class['day_name'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-green-600">
                                            <?= $class['present_count'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-red-600">
                                            <?= $class['absent_count'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-yellow-600">
                                            <?= $class['late_count'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?= $class['total_students'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="../attendance/attendance.php?batch_id=<?= $selectedBatch ?>&date=<?= $class['date'] ?>" class="text-blue-500 hover:text-blue-700 mr-3">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Attendance Chart -->
            <?php if (!empty($classesDates)): ?>
                <div class="bg-white p-5 rounded-xl shadow mt-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Attendance Overview</h2>
                    <div class="h-64">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        <?php if (!empty($classesDates)): ?>
            // Prepare data for chart
            const dates = <?= json_encode(array_map(function($class) {
                return date('M j', strtotime($class['date']));
            }, $classesDates)) ?>;
            
            const presentData = <?= json_encode(array_column($classesDates, 'present_count')) ?>;
            const absentData = <?= json_encode(array_column($classesDates, 'absent_count')) ?>;
            const lateData = <?= json_encode(array_column($classesDates, 'late_count')) ?>;
            
            // Attendance Chart
            const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
            const attendanceChart = new Chart(attendanceCtx, {
                type: 'bar',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Present',
                            data: presentData,
                            backgroundColor: '#10B981',
                        },
                        {
                            label: 'Late',
                            data: lateData,
                            backgroundColor: '#F59E0B',
                        },
                        {
                            label: 'Absent',
                            data: absentData,
                            backgroundColor: '#EF4444',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true,
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>

    <?php include '../footer.php'; ?>
</body>
</html>