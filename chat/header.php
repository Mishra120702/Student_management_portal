<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASD Academy - Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --light-text: #6c757d;
            --success-color: #4bb543;
            --danger-color: #f94144;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .chat-container {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .chat-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.2rem;
            font-weight: 600;
        }
        
        .chat-messages {
            background-color: white;
            padding: 20px;
            height: 500px;
            overflow-y: auto;
            scroll-behavior: smooth;
        }
        
        .message {
            margin-bottom: 15px;
            animation: fadeIn 0.3s ease;
            transition: all 0.2s;
        }
        
        .message-in {
            display: flex;
            justify-content: flex-start;
        }
        
        .message-out {
            display: flex;
            justify-content: flex-end;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 12px 15px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message-in .message-bubble {
            background-color: #f1f3f9;
            color: var(--dark-text);
            border-bottom-left-radius: 5px;
        }
        
        .message-out .message-bubble {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-bottom-right-radius: 5px;
        }
        
        .message-info {
            font-size: 0.75rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
        }
        
        .message-in .message-info {
            color: var(--light-text);
        }
        
        .message-out .message-info {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .conversation-item {
            transition: all 0.2s;
            border-left: 3px solid transparent;
            cursor: pointer;
        }
        
        .conversation-item:hover {
            background-color: rgba(67, 97, 238, 0.05);
            border-left-color: var(--primary-color);
        }
        
        .conversation-item.active {
            background-color: rgba(67, 97, 238, 0.1);
            border-left-color: var(--primary-color);
        }
        
        .unread-badge {
            background-color: var(--danger-color);
            animation: pulse 1.5s infinite;
        }
        
        .chat-input {
            border-top: 1px solid #eee;
            background-color: white;
            padding: 15px;
        }
        
        .chat-input .form-control {
            border-radius: 20px;
            padding: 10px 20px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        
        .chat-input .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .chat-input .btn {
            border-radius: 20px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
        }
        
        .search-box input {
            padding-left: 40px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .typing-indicator {
            display: inline-flex;
            padding: 8px 12px;
            background-color: #f1f3f9;
            border-radius: 18px;
            margin-bottom: 15px;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            background-color: var(--light-text);
            border-radius: 50%;
            margin: 0 2px;
            animation: typingAnimation 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) { animation-delay: 0s; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typingAnimation {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-5px); }
        }
        
        .sidebar-link.active {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color) !important;
            font-weight: 500;
            border-left: 3px solid var(--primary-color);
        }
        
        .sidebar-link.active i {
            color: var(--primary-color) !important;
        }
        
        .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 20px rgba(67, 97, 238, 0.3);
            z-index: 1000;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .floating-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .conversation-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            font-size: 1.2rem;
        }
        
        .conversation-name {
            font-weight: 500;
            color: black;
            margin-bottom: 3px;
        }
        
        .conversation-preview {
            font-size: 0.85rem;
            color: var(--light-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
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
            background: #a8a8a8;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
<?php include 'navbar.php'; ?>
<?php include '../header.php';?>
<?php include '../sidebar.php'; ?>
<div class="flex-1 ml-0 md:ml-64 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-30">
        <button class="md:hidden text-xl text-gray-600" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
            <i class="fas fa-comments text-blue-500"></i>
            <span>Chat</span>
        </h1>
        <div class="flex items-center space-x-4">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </div>
            <a href="../logout.php" class="text-sm text-red-600 hover:underline flex items-center space-x-1">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>    
        </div>
    </header>
    <div class="p-4 md:p-6">