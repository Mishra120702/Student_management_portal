<?php
include '../db_connection.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
include '../header.php';
include '../sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 ml-0 md:ml-64 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-user-times text-red-500"></i>
            <span>Absent Students Report</span>
        </h1>
        <div class="flex items-center space-x-4">
            <a href="dashboard.php" class="text-sm text-blue-600 hover:underline flex items-center space-x-1">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </header>

    <div class="p-4 md:p-6">
        <!-- Filter Section -->
        <div class="bg-white p-5 rounded-xl shadow mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Filter Absent Records</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" id="startDate" class="w-full p-2 border rounded-md">
                        <input type="date" id="endDate" class="w-full p-2 border rounded-md">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batch ID</label>
                    <select id="batchFilter" class="w-full p-2 border rounded-md">
                        <option value="">All Batches</option>
                        <?php
                        $batches = $db->query("SELECT batch_id, course_name FROM batches ORDER BY batch_id");
                        foreach ($batches as $batch) {
                            echo "<option value='{$batch['batch_id']}'>{$batch['batch_id']} - {$batch['course_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button id="applyFilters" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                    <button id="resetFilters" class="ml-2 bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition-colors">
                        <i class="fas fa-redo mr-2"></i>Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Absent Students Table -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="flex justify-between items-center p-4 border-b">
                <h2 class="text-lg font-semibold text-gray-800">Absent Students</h2>
                <div class="flex space-x-2">
                    <button id="exportCSV" class="text-sm bg-green-100 text-green-700 px-3 py-1 rounded hover:bg-green-200">
                        <i class="fas fa-file-csv mr-1"></i>Export CSV
                    </button>
                    <button id="printTable" class="text-sm bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200">
                        <i class="fas fa-print mr-1"></i>Print
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table id="absentTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="absentTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Data will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>
            <div id="loadingIndicator" class="p-4 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin mr-2"></i>Loading data...
            </div>
            <div id="noResults" class="p-4 text-center text-gray-500 hidden">
                No absent records found matching your criteria.
            </div>
            <div class="px-4 py-3 bg-gray-50 border-t flex items-center justify-between">
                <div class="text-sm text-gray-700" id="recordCount">Showing 0 records</div>
                <div class="flex space-x-2">
                    <button id="prevPage" class="px-3 py-1 border rounded text-sm disabled:opacity-50" disabled>Previous</button>
                    <span id="pageInfo" class="px-3 py-1 text-sm">Page 1 of 1</span>
                    <button id="nextPage" class="px-3 py-1 border rounded text-sm disabled:opacity-50" disabled>Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Absent Reason Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
        <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Edit Absent Reason</h3>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editAbsentForm" class="p-4">
            <input type="hidden" id="editRecordId" name="id">
            <div class="mb-4">
                <label for="editStatus" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="editStatus" name="status" class="w-full p-2 border rounded-md">
                    <option value="Absent">Absent</option>
                    <option value="Present">Present</option>
                    <option value="Late">Late</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="editRemarks" class="block text-sm font-medium text-gray-700 mb-1">Reason/Remarks</label>
                <textarea id="editRemarks" name="remarks" class="w-full p-2 border rounded-md" rows="3"></textarea>
            </div>
            <div class="flex justify-end space-x-2 pt-4 border-t">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables
    let currentPage = 1;
    const recordsPerPage = 10;
    let totalRecords = 0;
    
    // Load initial data
    loadAbsentData();
    
    // Apply filters button
    document.getElementById('applyFilters').addEventListener('click', function() {
        currentPage = 1;
        loadAbsentData();
    });
    
    // Reset filters button
    document.getElementById('resetFilters').addEventListener('click', function() {
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        document.getElementById('batchFilter').value = '';
        currentPage = 1;
        loadAbsentData();
    });
    
    // Pagination buttons
    document.getElementById('prevPage').addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            loadAbsentData();
        }
    });
    
    document.getElementById('nextPage').addEventListener('click', function() {
        if (currentPage * recordsPerPage < totalRecords) {
            currentPage++;
            loadAbsentData();
        }
    });
    
    // Export CSV button
    document.getElementById('exportCSV').addEventListener('click', exportToCSV);
    
    // Print button
    document.getElementById('printTable').addEventListener('click', printTable);
    
    // Function to load absent data
    function loadAbsentData() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const batchId = document.getElementById('batchFilter').value;
        
        // Show loading indicator
        document.getElementById('loadingIndicator').classList.remove('hidden');
        document.getElementById('absentTableBody').innerHTML = '';
        document.getElementById('noResults').classList.add('hidden');
        
        // Prepare request data
        const requestData = {
            startDate: startDate,
            endDate: endDate,
            batchId: batchId,
            page: currentPage,
            perPage: recordsPerPage
        };
        
        // Make AJAX request
        fetch('../attendance/get_absent_reasons.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            // Hide loading indicator
            document.getElementById('loadingIndicator').classList.add('hidden');
            
            if (data.success && data.data.length > 0) {
                totalRecords = data.total;
                updatePagination();
                renderTable(data.data);
            } else {
                document.getElementById('noResults').classList.remove('hidden');
                document.getElementById('recordCount').textContent = 'Showing 0 records';
                document.getElementById('prevPage').disabled = true;
                document.getElementById('nextPage').disabled = true;
                document.getElementById('pageInfo').textContent = 'Page 1 of 1';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('loadingIndicator').classList.add('hidden');
            document.getElementById('noResults').classList.remove('hidden');
            document.getElementById('noResults').textContent = 'Error loading data. Please try again.';
        });
    }
    
    // Function to render table data
    function renderTable(data) {
        const tableBody = document.getElementById('absentTableBody');
        tableBody.innerHTML = '';
        
        data.forEach(item => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.date}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">${item.batch_id}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.student_name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span class="px-2 py-1 ${getStatusColor(item.status)} rounded-full text-xs">${item.status}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.remarks || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <button class="text-blue-600 hover:text-blue-900 mr-2" onclick="openEditModal('${item.id}')">
                        <i class="fas fa-edit fa-lg"></i>
                    </button>
                    <a href="student_view.php?name=${encodeURIComponent(item.student_name)}&batch=${item.batch_id}" class="text-green-600 hover:text-green-900">
                        <i class="fas fa-eye fa-lg"></i>
                    </a>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
        
        document.getElementById('recordCount').textContent = `Showing ${Math.min(currentPage * recordsPerPage, totalRecords)} of ${totalRecords} records`;
    }
    
    // Function to update pagination controls
    function updatePagination() {
        const totalPages = Math.ceil(totalRecords / recordsPerPage);
        
        document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
        document.getElementById('prevPage').disabled = currentPage <= 1;
        document.getElementById('nextPage').disabled = currentPage >= totalPages;
    }
    
    // Function to get color based on status
    function getStatusColor(status) {
        switch(status) {
            case 'Present': return 'bg-green-100 text-green-800';
            case 'Absent': return 'bg-red-100 text-red-800';
            case 'Late': return 'bg-yellow-100 text-yellow-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    }
    
    // Function to export to CSV
    function exportToCSV() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const batchId = document.getElementById('batchFilter').value;
        
        // Create a temporary form to submit the export request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../attendance/export_absent_reasons.php';
        
        // Add parameters as hidden inputs
        const addInput = (name, value) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        };
        
        addInput('startDate', startDate);
        addInput('endDate', endDate);
        addInput('batchId', batchId);
        
        // Append form to body and submit
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
    
    // Function to print table
    function printTable() {
        const printWindow = window.open('', '_blank');
        const tableContent = document.getElementById('absentTable').outerHTML;
        const title = 'Absent Students Report';
        
        printWindow.document.write(`
            <html>
                <head>
                    <title>${title}</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .no-print { display: none; }
                    </style>
                </head>
                <body>
                    <h1>${title}</h1>
                    <p>Generated on: ${new Date().toLocaleString()}</p>
                    ${tableContent}
                    <script>
                        window.onload = function() {
                            window.print();
                            window.close();
                        };
                    <\/script>
                </body>
            </html>
        `);
    }
});

// Global functions for modal handling
window.openEditModal = function(id) {
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editAbsentForm');
    
    // Show loading state
    form.innerHTML = '<div class="p-8 text-center"><i class="fas fa-spinner fa-spin text-blue-500 text-2xl"></i></div>';
    modal.classList.remove('hidden');
    
    // Fetch record data
    fetch('../attendance/get_absent_record.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Populate form
            form.innerHTML = `
                <input type="hidden" id="editRecordId" name="id" value="${data.record.id}">
                <div class="mb-4">
                    <label for="editStatus" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="editStatus" name="status" class="w-full p-2 border rounded-md">
                        <option value="Present" ${data.record.status === 'Present' ? 'selected' : ''}>Present</option>
                        <option value="Absent" ${data.record.status === 'Absent' ? 'selected' : ''}>Absent</option>
                        <option value="Late" ${data.record.status === 'Late' ? 'selected' : ''}>Late</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="editRemarks" class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                    <textarea id="editRemarks" name="remarks" class="w-full p-2 border rounded-md" rows="3">${data.record.remarks || ''}</textarea>
                </div>
                <div class="flex justify-end space-x-2 pt-4 border-t">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Changes</button>
                </div>
            `;
            
            // Add form submit handler
            form.onsubmit = function(e) {
                e.preventDefault();
                saveAbsentRecord();
            };
        } else {
            alert('Error loading record: ' + data.message);
            closeEditModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading record');
        closeEditModal();
    });
};

window.closeEditModal = function() {
    document.getElementById('editModal').classList.add('hidden');
};

function saveAbsentRecord() {
    const form = document.getElementById('editAbsentForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    fetch('../attendance/update_absent_record.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Record updated successfully');
            closeEditModal();
            loadAbsentData(); // Refresh the table
        } else {
            alert('Error updating record: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating record');
    });
}
</script>

<?php include '../footer.php'; ?>