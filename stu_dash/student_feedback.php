<?php

require_once '../db_connection.php';

// Check if student is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

try {
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

    // Handle feedback submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
        $stmt = $db->prepare("INSERT INTO feedback (date, student_name, email, batch_id, course_name, 
                             class_rating, assignment_understanding, practical_understanding, satisfied, 
                             suggestions, feedback_text, is_regular) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            date('Y-m-d'),
            $student_name,
            $student['email'],
            $student['batch_id'],
            $student['course_name'],
            $_POST['class_rating'],
            $_POST['assignment_understanding'],
            $_POST['practical_understanding'],
            $_POST['satisfied'],
            $_POST['suggestions'],
            $_POST['feedback_text'],
            $_POST['regular_in_class']
        ]);
        $success = "Feedback submitted successfully!";
    }

    // Get student's previous feedback
    $feedback_query = $db->prepare("
        SELECT * FROM feedback 
        WHERE student_name = :student_name AND batch_id = :batch_id
        ORDER BY date DESC
    ");
    $feedback_query->execute([':student_name' => $student_name, ':batch_id' => $student['batch_id']]);
    $feedback_history = $feedback_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<?php include '../header.php'; ?>
<?php include '../s_sidebar.php'; ?>

<!-- Main Content -->
<div class="flex-1 ml-0 md:ml-64 min-h-screen transition-all duration-300 ease-in-out">
    <!-- Header -->
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30 transition-shadow duration-300 hover:shadow-md">
        <button class="md:hidden text-xl text-gray-600 hover:text-blue-600 transition-colors" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-comment-dots text-blue-500 transition-transform hover:scale-110"></i>
            <span>Student Feedback</span>
        </h1>
    </header>

    <div class="p-4 md:p-6">
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 animate-fade-in-up">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Feedback Form -->
        <div class="bg-white p-6 rounded-xl shadow mb-6 transform transition-all hover:shadow-lg hover:-translate-y-1">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-pencil-alt mr-2 text-blue-500"></i>
                Submit Your Feedback
            </h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="email" value="<?= htmlspecialchars($student['email']) ?>">
                <input type="hidden" name="student_name" value="<?= htmlspecialchars($student_name) ?>">
                <input type="hidden" name="batch_id" value="<?= htmlspecialchars($student['batch_id']) ?>">
                <input type="hidden" name="course_name" value="<?= htmlspecialchars($student['course_name']) ?>">
                
                <div class="mb-4 transition-all duration-300 hover:shadow-sm hover:border-blue-300">
                    <label for="regular_in_class" class="block text-gray-700 mb-2 font-medium">
                        <i class="fas fa-calendar-check mr-1 text-blue-500"></i>
                        Are you regular in class?
                    </label>
                    <select name="regular_in_class" id="regular_in_class" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300 hover:border-blue-400">
                        <option value="">Select an option</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                        <option value="Sometimes">Sometimes</option>
                    </select>
                </div>

                <div class="space-y-6">
                    <!-- Rating Sections -->
                    <div class="rating-section transition-all duration-300 hover:bg-gray-50 p-3 rounded-lg">
                        <h4 class="block text-gray-700 mb-2 font-medium">
                            <i class="fas fa-chalkboard-teacher mr-1 text-blue-500"></i>
                            Class Rating
                        </h4>
                        <div class="flex items-center">
                            <div class="star-rating mr-4" data-target="class_rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span data-value="<?= $i ?>" class="text-3xl cursor-pointer transition-all duration-200 hover:scale-125">★</span>
                                <?php endfor; ?>
                            </div>
                            <div class="rating-description text-sm text-gray-500 italic opacity-0 transition-opacity duration-300" id="class_rating_desc"></div>
                        </div>
                        <input type="hidden" name="class_rating" id="class_rating" required>
                    </div>

                    <div class="rating-section transition-all duration-300 hover:bg-gray-50 p-3 rounded-lg">
                        <h4 class="block text-gray-700 mb-2 font-medium">
                            <i class="fas fa-tasks mr-1 text-blue-500"></i>
                            Assignment Understanding
                        </h4>
                        <div class="flex items-center">
                            <div class="star-rating mr-4" data-target="assignment_understanding">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span data-value="<?= $i ?>" class="text-3xl cursor-pointer transition-all duration-200 hover:scale-125">★</span>
                                <?php endfor; ?>
                            </div>
                            <div class="rating-description text-sm text-gray-500 italic opacity-0 transition-opacity duration-300" id="assignment_rating_desc"></div>
                        </div>
                        <input type="hidden" name="assignment_understanding" id="assignment_understanding" required>
                    </div>

                    <div class="rating-section transition-all duration-300 hover:bg-gray-50 p-3 rounded-lg">
                        <h4 class="block text-gray-700 mb-2 font-medium">
                            <i class="fas fa-laptop-code mr-1 text-blue-500"></i>
                            Practical Understanding
                        </h4>
                        <div class="flex items-center">
                            <div class="star-rating mr-4" data-target="practical_understanding">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span data-value="<?= $i ?>" class="text-3xl cursor-pointer transition-all duration-200 hover:scale-125">★</span>
                                <?php endfor; ?>
                            </div>
                            <div class="rating-description text-sm text-gray-500 italic opacity-0 transition-opacity duration-300" id="practical_rating_desc"></div>
                        </div>
                        <input type="hidden" name="practical_understanding" id="practical_understanding" required>
                    </div>
                </div>

                <div class="mb-4 transition-all duration-300 hover:shadow-sm hover:border-blue-300">
                    <label for="satisfied" class="block text-gray-700 mb-2 font-medium">
                        <i class="fas fa-smile mr-1 text-blue-500"></i>
                        Are you satisfied with the course?
                    </label>
                    <select name="satisfied" id="satisfied" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300 hover:border-blue-400">
                        <option value="">Select an option</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>

                <div class="mb-4 transition-all duration-300 hover:shadow-sm">
                    <label for="suggestions" class="block text-gray-700 mb-2 font-medium">
                        <i class="fas fa-lightbulb mr-1 text-blue-500"></i>
                        Your suggestions or issues
                    </label>
                    <textarea id="suggestions" name="suggestions" rows="3" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300 hover:border-blue-400" 
                        placeholder="Share your suggestions or any issues you faced..."
                        maxlength="500" data-max-words="100"></textarea>
                    <div class="word-counter text-sm text-right mt-1 text-gray-500 transition-colors duration-300" id="suggestions-counter">0/100 words</div>

                <div class="mb-4 transition-all duration-300 hover:shadow-sm">
                    <label for="feedback_text" class="block text-gray-700 mb-2 font-medium">
                        <i class="fas fa-comment-dots mr-1 text-blue-500"></i>
                        Additional Feedback
                    </label>
                    <textarea id="feedback_text" name="feedback_text" rows="5" 
    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300 hover:border-blue-400" 
    placeholder="Share your thoughts about the course, instructor, or any suggestions for improvement..."
    maxlength="1000" data-max-words="200"></textarea>
<div class="word-counter text-sm text-right mt-1 text-gray-500 transition-colors duration-300" id="feedback-counter">0/200 words</div>

                <button type="submit" name="submit_feedback" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition-all duration-300 hover:shadow-lg hover:-translate-y-1 transform flex items-center justify-center space-x-2">
                    <i class="fas fa-paper-plane"></i>
                    <span>Submit Feedback</span>
                </button>
            </form>
        </div>

        <!-- Feedback History -->
        <div class="bg-white p-6 rounded-xl shadow transform transition-all hover:shadow-lg hover:-translate-y-1">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-history mr-2 text-blue-500"></i>
                Your Feedback History
            </h2>
            
            <?php if (count($feedback_history) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($feedback_history as $feedback): ?>
                        <div class="border border-gray-200 rounded-lg p-4 transition-all duration-300 hover:border-blue-300 hover:shadow-md feedback-card">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="text-gray-600 text-sm font-medium bg-blue-50 px-2 py-1 rounded-full">
                                        <i class="far fa-calendar-alt mr-1"></i>
                                        <?= date('M j, Y', strtotime($feedback['date'])) ?>
                                    </span>
                                    <div class="mt-3 space-y-2">
                                        <div class="flex items-center">
                                            <span class="text-sm font-medium text-gray-700 w-24">Class:</span>
                                            <div class="flex">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?= $i <= $feedback['class_rating'] ? 'text-yellow-400 animate-bounce' : 'text-gray-300' ?>" 
                                                        style="animation-delay: <?= $i * 0.1 ?>s"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="text-sm font-medium text-gray-700 w-24">Assignments:</span>
                                            <div class="flex">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?= $i <= $feedback['assignment_understanding'] ? 'text-yellow-400 animate-bounce' : 'text-gray-300' ?>" 
                                                        style="animation-delay: <?= $i * 0.1 ?>s"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="text-sm font-medium text-gray-700 w-24">Practical:</span>
                                            <div class="flex">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?= $i <= $feedback['practical_understanding'] ? 'text-yellow-400 animate-bounce' : 'text-gray-300' ?>" 
                                                        style="animation-delay: <?= $i * 0.1 ?>s"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-sm px-2 py-1 rounded-full <?= $feedback['satisfied'] === 'Yes' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= htmlspecialchars($feedback['satisfied']) ?> with course
                                </span>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Class Regularity:</p>
                                    <p class="text-sm text-gray-700 mt-1 px-2 py-1 bg-gray-50 rounded-full inline-block">
                                        <?= htmlspecialchars($feedback['is_regular']) ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Response Status:</p>
                                    <p class="text-sm mt-1 px-2 py-1 rounded-full inline-block 
                                        <?= !empty($feedback['action_taken']) ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= !empty($feedback['action_taken']) ? 'Responded' : 'Pending' ?>
                                    </p>
                                </div>
                            </div>
                            <?php if (!empty($feedback['suggestions'])): ?>
                                <div class="mt-3 bg-gray-50 p-3 rounded-lg transition-all duration-300 hover:bg-blue-50">
                                    <p class="text-sm font-medium text-gray-800 flex items-center">
                                        <i class="fas fa-lightbulb mr-1 text-yellow-500"></i>
                                        Your Suggestions:
                                    </p>
                                    <p class="text-sm text-gray-700 mt-1"><?= htmlspecialchars($feedback['suggestions']) ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="mt-3 bg-gray-50 p-3 rounded-lg transition-all duration-300 hover:bg-blue-50">
                                <p class="text-sm font-medium text-gray-800 flex items-center">
                                    <i class="fas fa-comment mr-1 text-blue-500"></i>
                                    Your Feedback:
                                </p>
                                <p class="text-sm text-gray-700 mt-1"><?= htmlspecialchars($feedback['feedback_text']) ?></p>
                            </div>
                            <?php if (!empty($feedback['action_taken'])): ?>
                                <div class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-100 transition-all duration-300 hover:shadow-inner">
                                    <p class="text-sm font-medium text-blue-800 flex items-center">
                                        <i class="fas fa-reply mr-1 text-blue-500"></i>
                                        Instructor Response:
                                    </p>
                                    <p class="text-sm text-blue-700 mt-1"><?= htmlspecialchars($feedback['action_taken']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 animate-pulse">
                    <i class="fas fa-comment-slash text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-500">You haven't submitted any feedback yet.</p>
                    <p class="text-sm text-gray-400 mt-1">Your feedback helps us improve the learning experience.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    /* Custom animations and styles */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes bounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-5px);
        }
    }
    
    .animate-fade-in-up {
        animation: fadeInUp 0.5s ease-out forwards;
    }
    
    .animate-bounce {
        animation: bounce 0.5s ease-in-out;
    }
    
    .star-rating span {
        color: #e0e6ed;
        margin-right: 2px;
        transition: all 0.2s;
    }
    
    .star-rating span.active {
        color: #f39c12;
    }
    
    .word-counter.limit-reached {
        color: #e53e3e;
        font-weight: bold;
    }
    
    .feedback-card:hover {
        transform: translateY(-3px);
    }
    
    .rating-description.show {
        opacity: 1;
    }
    
    /* Smooth transitions for form elements */
    input, select, textarea {
        transition: all 0.3s ease;
    }
    
    /* Hover effects for buttons */
    button:hover {
        transform: translateY(-2px);
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Rating descriptions
    const ratingDescriptions = {
        'class_rating': {
            1: "Very poor - The classes didn't meet my expectations at all",
            2: "Below average - There's significant room for improvement",
            3: "Average - The classes were okay but could be better",
            4: "Good - I learned a lot from the classes",
            5: "Excellent - The classes exceeded my expectations"
        },
        'assignment_understanding': {
            1: "Very difficult - I couldn't understand most assignments",
            2: "Somewhat difficult - I struggled with many assignments",
            3: "Moderate - Some assignments were clear, others weren't",
            4: "Mostly clear - I understood most assignments well",
            5: "Very clear - All assignments were well explained"
        },
        'practical_understanding': {
            1: "Very poor - I didn't gain practical skills",
            2: "Below average - Practical skills were hard to grasp",
            3: "Average - I gained some practical understanding",
            4: "Good - I can apply most concepts practically",
            5: "Excellent - I'm confident in my practical skills"
        }
    };
    
    // Enhanced star rating interaction
    $('.star-rating span').on('click', function() {
        const rating = $(this).data('value');
        const target = $(this).parent().data('target');
        const descElement = $('#' + target + '_desc');
        
        // Update stars appearance
        $(this).siblings().removeClass('active');
        $(this).prevAll().addBack().addClass('active');
        
        // Update hidden input value
        $('#' + target).val(rating);
        
        // Show rating description
        descElement.text(ratingDescriptions[target][rating]).css('opacity', 1);
    });
    
    // Hover effect for stars with description preview
    $('.star-rating span').on('mouseover', function() {
        const rating = $(this).data('value');
        const target = $(this).parent().data('target');
        const descElement = $('#' + target + '_desc');
        
        $(this).prevAll().addBack().css('color', '#f39c12');
        descElement.text(ratingDescriptions[target][rating]).css('opacity', 0.7);
    }).on('mouseout', function() {
        const target = $(this).parent().data('target');
        const descElement = $('#' + target + '_desc');
        const activeStars = $(this).parent().find('.active');
        
        if (activeStars.length > 0) {
            $(this).siblings().css('color', '#e0e6ed');
            activeStars.css('color', '#f39c12');
            descElement.css('opacity', 1);
        } else {
            $(this).siblings().addBack().css('color', '#e0e6ed');
            descElement.css('opacity', 0);
        }
    });
    
// Word counter functionality with improved handling
function countWords(text) {
    text = text.trim();
    if (text === '') return 0;
    // Handle multiple spaces, newlines, and tabs between words
    return text.split(/[\s\n\t]+/).filter(function(word) {
        return word.length > 0;
    }).length;
}

function updateWordCounter(textarea, counterId) {
    const text = $(textarea).val();
    const wordCount = countWords(text);
    const maxWords = parseInt($(textarea).data('max-words'));
    const counterElement = $('#' + counterId);
    
    counterElement.text(wordCount + '/' + maxWords + ' words');
    
    if (wordCount > maxWords) {
        counterElement.addClass('text-red-500 font-medium');
        // Trim the text to max words
        const words = text.split(/[\s\n\t]+/).filter(w => w.length > 0);
        const truncated = words.slice(0, maxWords).join(' ');
        $(textarea).val(truncated);
        counterElement.text(maxWords + '/' + maxWords + ' words (limit reached)');
        
        // Add visual feedback for limit reached
        counterElement.addClass('animate-shake');
        setTimeout(() => {
            counterElement.removeClass('animate-shake');
        }, 500);
    } else {
        counterElement.removeClass('text-red-500 font-medium');
        
        // Visual feedback when approaching limit
        if (wordCount > maxWords * 0.8) {
            counterElement.addClass('text-yellow-500');
        } else {
            counterElement.removeClass('text-yellow-500');
        }
    }
}

// Initialize word counters for both textareas
$('#suggestions').on('input', function() {
    updateWordCounter(this, 'suggestions-counter');
});

$('#feedback_text').on('input', function() {
    updateWordCounter(this, 'feedback-counter');
});

// Initial count on page load
updateWordCounter($('#suggestions')[0], 'suggestions-counter');
updateWordCounter($('#feedback_text')[0], 'feedback-counter');
    // Add shake animation for validation errors
    $('form').on('submit', function(e) {
        let isValid = true;
        
        // Check required fields
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('border-red-500 animate-shake');
                setTimeout(() => {
                    $(this).removeClass('animate-shake');
                }, 500);
                isValid = false;
            } else {
                $(this).removeClass('border-red-500');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            
            // Show error message
            const errorMsg = $('<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 animate-fade-in-up">' +
                              '<div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i> ' +
                              'Please fill in all required fields</div></div>');
            
            $('.bg-green-100').remove();
            $(this).prepend(errorMsg);
            
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $(this).find('.border-red-500').first().offset().top - 100
            }, 500);
        }
    });
    
    // Add custom shake animation
    $.keyframe.define([{
        name: 'shake',
        '0%': {transform: 'translateX(0)'},
        '25%': {transform: 'translateX(-5px)'},
        '50%': {transform: 'translateX(5px)'},
        '75%': {transform: 'translateX(-5px)'},
        '100%': {transform: 'translateX(0)'}
    }]);
});
</script>

<?php include '../footer.php'; ?>