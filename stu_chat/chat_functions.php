<?php
// chat_functions.php

require_once '../db_connection.php';

/**
 * Get or create a conversation between admin and student
 */
function getOrCreateStudentConversation($admin_id, $student_id) {
    global $db;
    
    // Check if conversation already exists
    $query = $db->prepare("
        SELECT id FROM chat_conversations 
        WHERE conversation_type = 'admin_student' 
        AND admin_id = ? 
        AND student_id = ?
    ");
    $query->execute([$admin_id, $student_id]);
    $conversation = $query->fetch(PDO::FETCH_ASSOC);
    
    if ($conversation) {
        return $conversation['id'];
    }
    
    // Create new conversation
    $insert = $db->prepare("
        INSERT INTO chat_conversations (conversation_type, admin_id, student_id)
        VALUES ('admin_student', ?, ?)
    ");
    $insert->execute([$admin_id, $student_id]);
    
    return $db->lastInsertId();
}

/**
 * Get or create a conversation between admin and batch
 */
function getOrCreateBatchConversation($admin_id, $batch_id) {
    global $db;
    
    // Check if conversation already exists
    $query = $db->prepare("
        SELECT id FROM chat_conversations 
        WHERE conversation_type = 'admin_batch' 
        AND admin_id = ? 
        AND batch_id = ?
    ");
    $query->execute([$admin_id, $batch_id]);
    $conversation = $query->fetch(PDO::FETCH_ASSOC);
    
    if ($conversation) {
        return $conversation['id'];
    }
    
    // Create new conversation
    $insert = $db->prepare("
        INSERT INTO chat_conversations (conversation_type, admin_id, batch_id)
        VALUES ('admin_batch', ?, ?)
    ");
    $insert->execute([$admin_id, $batch_id]);
    
    return $db->lastInsertId();
}

/**
 * Get messages for a conversation with access control
 */
function getConversationMessages($conversation_id, $user_id) {
    global $db;
    
    // First verify user has access to this conversation
    $query = $db->prepare("
        SELECT c.id 
        FROM chat_conversations c
        WHERE c.id = ? 
        AND (
            (c.conversation_type = 'admin_student' AND c.student_id = (SELECT student_id FROM students WHERE user_id = ?))
            OR 
            (c.conversation_type = 'admin_batch' AND c.batch_id = (SELECT batch_name FROM students WHERE user_id = ?))
        )
    ");
    $query->execute([$conversation_id, $user_id, $user_id]);
    $conversation = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        return [];
    }
    
    $query = $db->prepare("
        SELECT m.id, m.sender_id, m.message, m.sent_at, m.is_read, u.name as sender_name
        FROM chat_messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = ?
        ORDER BY m.sent_at ASC
    ");
    $query->execute([$conversation_id]);
    
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get conversation name/display title with access control
 */
function getConversationName($conversation_id, $user_id) {
    global $db;
    
    // First verify user has access to this conversation
    $query = $db->prepare("
        SELECT c.id 
        FROM chat_conversations c
        WHERE c.id = ? 
        AND (
            (c.conversation_type = 'admin_student' AND c.student_id = (SELECT student_id FROM students WHERE user_id = ?))
            OR 
            (c.conversation_type = 'admin_batch' AND c.batch_id = (SELECT batch_name FROM students WHERE user_id = ?))
        )
    ");
    $query->execute([$conversation_id, $user_id, $user_id]);
    $conversation = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        return 'Unauthorized Conversation';
    }
    
    $query = $db->prepare("
        SELECT 
            c.id,
            c.conversation_type,
            CASE 
                WHEN c.conversation_type = 'admin_student' THEN 
                    CONCAT('Admin: ', u.name)
                WHEN c.conversation_type = 'admin_batch' THEN 
                    CONCAT('Batch: ', b.batch_id, ' - ', b.course_name)
            END as name
        FROM chat_conversations c
        LEFT JOIN users u ON c.admin_id = u.id
        LEFT JOIN batches b ON c.batch_id = b.batch_id
        WHERE c.id = ?
    ");
    $query->execute([$conversation_id]);
    $result = $query->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['name'] : 'Unknown Conversation';
}

/**
 * Mark messages as read for a user in a conversation with access control
 */
function markMessagesAsRead($conversation_id, $user_id) {
    global $db;
    
    // First verify user has access to this conversation
    $query = $db->prepare("
        SELECT c.id 
        FROM chat_conversations c
        WHERE c.id = ? 
        AND (
            (c.conversation_type = 'admin_student' AND c.student_id = (SELECT student_id FROM students WHERE user_id = ?))
            OR 
            (c.conversation_type = 'admin_batch' AND c.batch_id = (SELECT batch_name FROM students WHERE user_id = ?))
        )
    ");
    $query->execute([$conversation_id, $user_id, $user_id]);
    $conversation = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        return 0;
    }
    
    $update = $db->prepare("
        UPDATE chat_messages 
        SET is_read = 1 
        WHERE conversation_id = ? 
        AND sender_id != ?
        AND is_read = 0
    ");
    $update->execute([$conversation_id, $user_id]);
    
    return $update->rowCount();
}

/**
 * Send a new message to a conversation with access control
 */
function sendMessage($conversation_id, $sender_id, $message) {
    global $db;
    
    // First verify user has access to this conversation
    $query = $db->prepare("
        SELECT c.id 
        FROM chat_conversations c
        WHERE c.id = ? 
        AND (
            (c.conversation_type = 'admin_student' AND c.student_id = (SELECT student_id FROM students WHERE user_id = ?))
            OR 
            (c.conversation_type = 'admin_batch' AND c.batch_id = (SELECT batch_name FROM students WHERE user_id = ?))
        )
    ");
    $query->execute([$conversation_id, $sender_id, $sender_id]);
    $conversation = $query->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        return false;
    }
    
    $insert = $db->prepare("
        INSERT INTO chat_messages (conversation_id, sender_id, message, sent_at)
        VALUES (?, ?, ?, NOW())
    ");
    $success = $insert->execute([$conversation_id, $sender_id, $message]);
    
    if ($success) {
        return $db->lastInsertId();
    }
    
    return false;
}

/**
 * Check if student conversation exists (without creating)
 */
function getStudentConversation($admin_id, $student_id) {
    global $db;
    
    $query = $db->prepare("
        SELECT id FROM chat_conversations 
        WHERE conversation_type = 'admin_student' 
        AND admin_id = ? 
        AND student_id = ?
    ");
    $query->execute([$admin_id, $student_id]);
    $conversation = $query->fetch(PDO::FETCH_ASSOC);
    
    return $conversation ? $conversation['id'] : null;
}

/**
 * Check if batch conversation exists (without creating)
 */
function getBatchConversation($admin_id, $batch_id) {
    global $db;
    
    $query = $db->prepare("
        SELECT id FROM chat_conversations 
        WHERE conversation_type = 'admin_batch' 
        AND admin_id = ? 
        AND batch_id = ?
    ");
    $query->execute([$admin_id, $batch_id]);
    $conversation = $query->fetch(PDO::FETCH_ASSOC);
    
    return $conversation ? $conversation['id'] : null;
}