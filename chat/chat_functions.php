<?php
require_once '../db_connection.php';

function getAdminConversations($admin_id) {
    global $db;
    
    $conversations = [];
    
    // Get individual student conversations
    $query = "SELECT c.id, c.conversation_type, 
                     CONCAT(s.first_name, ' ', s.last_name) as name,
                     (SELECT COUNT(*) FROM chat_messages m 
                      WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.is_read = 0) as unread,
                     c.is_active
              FROM chat_conversations c
              JOIN students s ON c.student_id = s.student_id
              JOIN chat_participants p ON c.id = p.conversation_id
              WHERE c.admin_id = ? 
              AND c.conversation_type = 'admin_student'
              AND p.user_id = ?
              AND p.is_active = TRUE
              AND c.is_active = TRUE
              ORDER BY c.updated_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$admin_id, $admin_id, $admin_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($result as $row) {
        $conversations[] = $row;
    }
    
    // Get batch conversations
    $query = "SELECT c.id, c.conversation_type, 
                     CONCAT('Batch: ', b.batch_id, ' - ', b.course_name) as name,
                     (SELECT COUNT(*) FROM chat_messages m 
                      WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.is_read = 0) as unread,
                     c.is_active
              FROM chat_conversations c
              JOIN batches b ON c.batch_id = b.batch_id
              JOIN chat_participants p ON c.id = p.conversation_id
              WHERE c.admin_id = ? 
              AND c.conversation_type = 'admin_batch'
              AND p.user_id = ?
              AND p.is_active = TRUE
              AND c.is_active = TRUE
              ORDER BY c.updated_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$admin_id, $admin_id, $admin_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($result as $row) {
        $conversations[] = $row;
    }
    
    return $conversations;
}

function getConversationMessages($conversation_id) {
    global $db;
    
    $query = "SELECT m.*, u.name as sender_name 
              FROM chat_messages m
              JOIN users u ON m.sender_id = u.id
              WHERE m.conversation_id = ?
              ORDER BY m.sent_at ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$conversation_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function markMessagesAsRead($conversation_id, $user_id) {
    global $db;
    
    $query = "UPDATE chat_messages m
              JOIN chat_participants p ON m.conversation_id = p.conversation_id
              SET m.is_read = 1
              WHERE m.conversation_id = ? AND p.user_id = ? AND m.sender_id != ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$conversation_id, $user_id, $user_id]);
}

function getConversationName($conversation_id) {
    global $db;
    
    $query = "SELECT 
                CASE 
                    WHEN c.conversation_type = 'admin_student' THEN 
                        CONCAT(s.first_name, ' ', s.last_name)
                    WHEN c.conversation_type = 'admin_batch' THEN 
                        CONCAT('Batch: ', b.batch_id, ' - ', b.course_name)
                END as name
              FROM chat_conversations c
              LEFT JOIN students s ON c.student_id = s.student_id
              LEFT JOIN batches b ON c.batch_id = b.batch_id
              WHERE c.id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$conversation_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['name'] : 'Unknown';
}

function sendMessage($conversation_id, $sender_id, $message) {
    global $db;
    
    $query = "INSERT INTO chat_messages (conversation_id, sender_id, message) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$conversation_id, $sender_id, $message]);
    
    // Update conversation timestamp
    $query = "UPDATE chat_conversations SET updated_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$conversation_id]);
    
    return $stmt->rowCount() > 0;
}

function getNewMessages($conversation_id, $last_message_id) {
    global $db;
    
    $query = "SELECT m.*, u.name as sender_name 
              FROM chat_messages m
              JOIN users u ON m.sender_id = u.id
              WHERE m.conversation_id = ? AND m.id > ?
              ORDER BY m.sent_at ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$conversation_id, $last_message_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOrCreateStudentConversation($admin_id, $student_id) {
    global $db;
    
    // First verify the student exists
    $query = "SELECT student_id FROM students WHERE student_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        throw new Exception("Student with ID $student_id does not exist");
    }
    
    // Check if conversation exists
    $query = "SELECT id FROM chat_conversations 
              WHERE admin_id = ? AND student_id = ? AND conversation_type = 'admin_student'";
    $stmt = $db->prepare($query);
    $stmt->execute([$admin_id, $student_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Reactivate if it was deactivated
        $query = "UPDATE chat_conversations SET is_active = TRUE WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$result['id']]);
        
        // Reactivate participants
        $query = "UPDATE chat_participants SET is_active = TRUE WHERE conversation_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$result['id']]);
        
        return $result['id'];
    }
    
    // Create new conversation
    $query = "INSERT INTO chat_conversations (conversation_type, admin_id, student_id) 
              VALUES ('admin_student', ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$admin_id, $student_id]);
    $conversation_id = $db->lastInsertId();
    
    // Add participants
    $student_user_id = getStudentUserId($student_id);
    if ($student_user_id) {
        addParticipant($conversation_id, $admin_id);
        addParticipant($conversation_id, $student_user_id);
    }
    
    return $conversation_id;
}

function getOrCreateBatchConversation($admin_id, $batch_id) {
    global $db;
    
    // First verify the batch exists
    $query = "SELECT batch_id FROM batches WHERE batch_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$batch_id]);
    $batch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$batch) {
        throw new Exception("Batch with ID $batch_id does not exist");
    }
    
    // Check if conversation exists
    $query = "SELECT id FROM chat_conversations 
              WHERE admin_id = ? AND batch_id = ? AND conversation_type = 'admin_batch'";
    $stmt = $db->prepare($query);
    $stmt->execute([$admin_id, $batch_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Reactivate if it was deactivated
        $query = "UPDATE chat_conversations SET is_active = TRUE WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$result['id']]);
        
        // Reactivate participants
        $query = "UPDATE chat_participants SET is_active = TRUE WHERE conversation_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$result['id']]);
        
        return $result['id'];
    }
    
    // Create new conversation
    $query = "INSERT INTO chat_conversations (conversation_type, admin_id, batch_id) 
              VALUES ('admin_batch', ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$admin_id, $batch_id]);
    $conversation_id = $db->lastInsertId();
    
    // Add admin as participant
    addParticipant($conversation_id, $admin_id);
    
    // Add all students in batch as participants
    $students = getBatchStudents($batch_id);
    foreach ($students as $student) {
        $student_user_id = getStudentUserId($student['student_id']);
        if ($student_user_id) {
            addParticipant($conversation_id, $student_user_id);
        }
    }
    
    return $conversation_id;
}

function addParticipant($conversation_id, $user_id) {
    global $db;
    
    $query = "INSERT INTO chat_participants (conversation_id, user_id) 
              VALUES (?, ?)
              ON DUPLICATE KEY UPDATE is_active = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute([$conversation_id, $user_id]);
    
    return $stmt->rowCount() > 0;
}

function getStudentUserId($student_id) {
    global $db;
    
    $query = "SELECT user_id FROM students WHERE student_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$student_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['user_id'] : null;
}

function getBatchStudents($batch_id) {
    global $db;
    
    $query = "SELECT student_id FROM students WHERE batch_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$batch_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLastMessagePreview($conversation_id) {
    global $db;
    
    $query = "SELECT message FROM chat_messages 
              WHERE conversation_id = ? 
              ORDER BY sent_at DESC 
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$conversation_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['message'] : null;
}

function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function deleteConversation($conversation_id, $user_id) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Verify user is a participant in this conversation
        $query = "SELECT 1 FROM chat_participants 
                  WHERE conversation_id = ? AND user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$conversation_id, $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            throw new Exception("Unauthorized to delete this conversation");
        }
        
        // For individual conversations, deactivate the conversation
        $query = "SELECT conversation_type, admin_id FROM chat_conversations 
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$conversation_id]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conversation) {
            throw new Exception("Conversation not found");
        }
        
        if ($conversation['conversation_type'] === 'admin_student') {
            // For admin-student conversations, deactivate the conversation
            $query = "UPDATE chat_conversations SET is_active = FALSE WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$conversation_id]);
            
            // Deactivate all participants
            $query = "UPDATE chat_participants SET is_active = FALSE WHERE conversation_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$conversation_id]);
        } else {
            // For batch conversations, just remove the current user
            $query = "UPDATE chat_participants SET is_active = FALSE 
                      WHERE conversation_id = ? AND user_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$conversation_id, $user_id]);
            
            // Check if admin is leaving
            if ($user_id == $conversation['admin_id']) {
                // If admin is leaving, deactivate the whole conversation
                $query = "UPDATE chat_conversations SET is_active = FALSE WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$conversation_id]);
                
                // Deactivate all participants
                $query = "UPDATE chat_participants SET is_active = FALSE WHERE conversation_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$conversation_id]);
            }
        }
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}