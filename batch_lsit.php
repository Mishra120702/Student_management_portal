<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Batch</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Create New Batch</h1>
                
                <form id="batchForm" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Batch ID -->
                        <div>
                            <label for="batch_id" class="block text-sm font-medium text-gray-700 mb-1">Batch ID*</label>
                            <input type="text" id="batch_id" name="batch_id" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            <p id="batch_id_error" class="mt-1 text-sm text-red-600 hidden">Batch ID is required</p>
                        </div>
                        
                        <!-- Course Name -->
                        <div>
                            <label for="course_name" class="block text-sm font-medium text-gray-700 mb-1">Course Name*</label>
                            <input type="text" id="course_name" name="course_name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            <p id="course_name_error" class="mt-1 text-sm text-red-600 hidden">Course name is required</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date*</label>
                            <input type="date" id="start_date" name="start_date" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            <p id="start_date_error" class="mt-1 text-sm text-red-600 hidden">Start date is required</p>
                        </div>
                        
                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date*</label>
                            <input type="date" id="end_date" name="end_date" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            <p id="end_date_error" class="mt-1 text-sm text-red-600 hidden">End date must be after start date</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Number of Students -->
                        <div>
                            <label for="num_students" class="block text-sm font-medium text-gray-700 mb-1">Number of Students*</label>
                            <input type="number" id="num_students" name="num_students" min="1" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   required>
                            <p id="num_students_error" class="mt-1 text-sm text-red-600 hidden">Number must be greater than 0</p>
                        </div>
                        
                        <!-- Batch Mentor -->
                        <div>
                            <label for="batch_mentor" class="block text-sm font-medium text-gray-700 mb-1">Batch Mentor*</label>
                            <select id="batch_mentor" name="batch_mentor" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                                <option value="">Select a mentor</option>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                            <p id="batch_mentor_error" class="mt-1 text-sm text-red-600 hidden">Please select a mentor</p>
                        </div>
                    </div>

                    <!-- Mode (Online/Offline) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mode*</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="mode" value="Online" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500" checked>
                                <span class="ml-2 text-gray-700">Online</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="mode" value="Offline" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-gray-700">Offline</span>
                            </label>
                        </div>
                    </div>

                    <!-- Platform (conditional on mode) -->
                    <div id="platformField">
                        <label for="platform" class="block text-sm font-medium text-gray-700 mb-1">Platform*</label>
                        <select id="platform" name="platform" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select a platform</option>
                            <option value="Google Meet">Google Meet</option>
                            <option value="Zoom">Zoom</option>
                            <option value="Microsoft Teams">Microsoft Teams</option>
                        </select>
                        <p id="platform_error" class="mt-1 text-sm text-red-600 hidden">Please select a platform</p>
                    </div>

                    <!-- Meeting Link (conditional on mode) -->
                    <div id="meetingLinkField">
                        <label for="meeting_link" class="block text-sm font-medium text-gray-700 mb-1">Meeting Link*</label>
                        <input type="url" id="meeting_link" name="meeting_link" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="https://meet.google.com/abc-xyz">
                        <p id="meeting_link_error" class="mt-1 text-sm text-red-600 hidden">Please enter a valid URL</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status*</label>
                        <select id="status" name="status" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                            <option value="">Select status</option>
                            <option value="Running">Running</option>
                            <option value="Completed">Completed</option>
                            <option value="Upcoming">Upcoming</option>
                        </select>
                        <p id="status_error" class="mt-1 text-sm text-red-600 hidden">Please select a status</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="button" onclick="window.history.back()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Create Batch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Load mentors from server
            $.ajax({
                url: 'get_mentors.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    const mentorSelect = $('#batch_mentor');
                    mentorSelect.empty();
                    mentorSelect.append('<option value="">Select a mentor</option>');
                    
                    data.forEach(function(mentor) {
                        mentorSelect.append(`<option value="${mentor.id}">${mentor.name}</option>`);
                    });
                },
                error: function() {
                    console.error('Failed to load mentors');
                }
            });

            // Toggle platform fields based on mode selection
            $('input[name="mode"]').change(function() {
                if ($(this).val() === 'Online') {
                    $('#platformField, #meetingLinkField').show();
                    $('#platform, #meeting_link').prop('required', true);
                } else {
                    $('#platformField, #meetingLinkField').hide();
                    $('#platform, #meeting_link').prop('required', false);
                }
            });

            // Initialize visibility
            if ($('input[name="mode"]:checked').val() === 'Online') {
                $('#platformField, #meetingLinkField').show();
            } else {
                $('#platformField, #meetingLinkField').hide();
            }

            // Form validation
            $('#batchForm').submit(function(e) {
                e.preventDefault();
                let isValid = true;

                // Reset error states
                $('.text-red-600').addClass('hidden');
                $('input, select').removeClass('border-red-500').addClass('border-gray-300');

                // Validate Batch ID
                if (!$('#batch_id').val()) {
                    $('#batch_id_error').removeClass('hidden');
                    $('#batch_id').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                }

                // Validate Course Name
                if (!$('#course_name').val()) {
                    $('#course_name_error').removeClass('hidden');
                    $('#course_name').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                }

                // Validate Dates
                const startDate = new Date($('#start_date').val());
                const endDate = new Date($('#end_date').val());
                
                if (!$('#start_date').val()) {
                    $('#start_date_error').removeClass('hidden');
                    $('#start_date').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                }
                
                if (!$('#end_date').val()) {
                    $('#end_date_error').removeClass('hidden');
                    $('#end_date').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                } else if (endDate <= startDate) {
                    $('#end_date_error').removeClass('hidden');
                    $('#end_date').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                }

                // Validate Number of Students
                if (!$('#num_students').val() || $('#num_students').val() <= 0) {
                    $('#num_students_error').removeClass('hidden');
                    $('#num_students').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                }

                // Validate Batch Mentor
                if (!$('#batch_mentor').val()) {
                    $('#batch_mentor_error').removeClass('hidden');
                    $('#batch_mentor').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                }

                // Validate Status
                if (!$('#status').val()) {
                    $('#status_error').removeClass('hidden');
                    $('#status').removeClass('border-gray-300').addClass('border-red-500');
                    isValid = false;
                }

                // Validate Platform and Meeting Link if Online
                if ($('input[name="mode"]:checked').val() === 'Online') {
                    if (!$('#platform').val()) {
                        $('#platform_error').removeClass('hidden');
                        $('#platform').removeClass('border-gray-300').addClass('border-red-500');
                        isValid = false;
                    }
                    
                    if (!$('#meeting_link').val()) {
                        $('#meeting_link_error').removeClass('hidden');
                        $('#meeting_link').removeClass('border-gray-300').addClass('border-red-500');
                        isValid = false;
                    } else if (!isValidUrl($('#meeting_link').val())) {
                        $('#meeting_link_error').removeClass('hidden');
                        $('#meeting_link').removeClass('border-gray-300').addClass('border-red-500');
                        isValid = false;
                    }
                }

                if (isValid) {
                    // Submit form via AJAX
                    $.ajax({
                        url: 'create_batch.php',
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(response) {
                            try {
                                const result = JSON.parse(response);
                                if (result.success) {
                                    alert('Batch created successfully!');
                                    window.location.href = 'batches_list.php'; // Redirect to batches list
                                } else {
                                    alert('Error: ' + result.message);
                                }
                            } catch (e) {
                                alert('An error occurred. Please try again.');
                            }
                        },
                        error: function() {
                            alert('Failed to submit form. Please try again.');
                        }
                    });
                }
            });

            function isValidUrl(string) {
                try {
                    new URL(string);
                    return true;
                } catch (_) {
                    return false;
                }
            }
        });
    </script>
</body>
</html>