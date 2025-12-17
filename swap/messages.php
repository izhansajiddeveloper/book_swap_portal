<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$swap_id = isset($_GET['swap_id']) ? (int)$_GET['swap_id'] : 0;

// Fetch swap details
$swap_sql = "
    SELECT 
        sr.swap_id,
        sr.status,
        b1.title AS requested_book,
        b1.user_id AS book_owner_id,
        u1.name AS book_owner_name,
        u2.name AS requester_name,
        sr.created_at AS request_date
    FROM swap_requests sr
    JOIN books b1 ON sr.book_id = b1.book_id
    JOIN users u1 ON b1.user_id = u1.user_id
    JOIN users u2 ON sr.requester_id = u2.user_id
    WHERE sr.swap_id = '$swap_id'
    AND (sr.requester_id = '$user_id' OR b1.user_id = '$user_id')
";

$swap_result = $conn->query($swap_sql);
if (!$swap_result || $swap_result->num_rows == 0) {
    header("Location: swap_requests.php");
    exit;
}

$swap = $swap_result->fetch_assoc();

// Handle new message
if (isset($_POST['send_message'])) {
    $message = $conn->real_escape_string($_POST['message']);
    $insert_sql = "
        INSERT INTO messages (swap_id, sender_id, message, timestamp)
        VALUES ('$swap_id', '$user_id', '$message', NOW())
    ";
    $conn->query($insert_sql);
}

// Fetch messages
$messages_sql = "
    SELECT 
        m.message_id,
        m.sender_id,
        m.message,
        m.timestamp,
        u.name AS sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.user_id
    WHERE m.swap_id = '$swap_id'
    ORDER BY m.timestamp ASC
";
$messages = $conn->query($messages_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Book Swap Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .chat-container {
            display: flex;
            flex-direction: column;
            height: 80vh;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chat-header h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .swap-info {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.2);
        }

        .messages-container {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 1rem;
            max-width: 70%;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.sent {
            margin-left: auto;
        }

        .message.received {
            margin-right: auto;
        }

        .message-content {
            padding: 1rem;
            border-radius: 15px;
            position: relative;
        }

        .message.sent .message-content {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-top-right-radius: 5px;
        }

        .message.received .message-content {
            background: white;
            color: var(--dark);
            border-top-left-radius: 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }

        .message.sent .message-header {
            color: rgba(255, 255, 255, 0.8);
        }

        .message.received .message-header {
            color: var(--gray);
        }

        .message-text {
            line-height: 1.5;
        }

        .message-time {
            font-size: 0.75rem;
            margin-top: 0.5rem;
            text-align: right;
        }

        .input-container {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid var(--gray-light);
        }

        .message-form {
            display: flex;
            gap: 1rem;
        }

        .message-input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: 2px solid var(--gray-light);
            border-radius: 50px;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .message-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .send-btn {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .empty-messages {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }

        .empty-messages i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            margin-bottom: 1rem;
            opacity: 0.9;
            transition: opacity 0.3s ease;
        }

        .back-btn:hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .chat-container {
                height: 85vh;
            }

            .message {
                max-width: 85%;
            }

            .message-form {
                flex-direction: column;
            }

            .send-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <?php include "../includes/navbar.php"; ?>

    <div class="container">
        <div class="chat-container">
            <div class="chat-header">
                <a href="swap_requests.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Swaps
                </a>
                <h1><i class="fas fa-comments"></i> Swap Messages</h1>
                <div class="swap-info">
                    <div class="info-item">
                        <i class="fas fa-book"></i>
                        <span>Book: <?= htmlspecialchars($swap['requested_book']) ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Swap ID: #<?= $swap_id ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-user"></i>
                        <span>Parties: <?= htmlspecialchars($swap['book_owner_name']) ?> ↔ <?= htmlspecialchars($swap['requester_name']) ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-circle"></i>
                        <span>Status: <span class="status-badge"><?= ucfirst($swap['status']) ?></span></span>
                    </div>
                </div>
            </div>

            <div class="messages-container" id="messagesContainer">
                <?php if ($messages && $messages->num_rows > 0): ?>
                    <?php while ($msg = $messages->fetch_assoc()): ?>
                        <div class="message <?= $msg['sender_id'] == $user_id ? 'sent' : 'received' ?>">
                            <div class="message-content">
                                <div class="message-header">
                                    <span class="sender-name"><?= htmlspecialchars($msg['sender_name']) ?></span>
                                    <span class="message-date"><?= date("M d, Y", strtotime($msg['timestamp'])) ?></span>
                                </div>
                                <div class="message-text">
                                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                </div>
                                <div class="message-time">
                                    <?= date("h:i A", strtotime($msg['timestamp'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-messages">
                        <i class="fas fa-comments"></i>
                        <h3>No Messages Yet</h3>
                        <p>Start the conversation by sending a message below</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="input-container">
                <form method="POST" class="message-form">
                    <input type="text" name="message" class="message-input"
                        placeholder="Type your message here..." required
                        autocomplete="off">
                    <button type="submit" name="send_message" class="send-btn">
                        <i class="fas fa-paper-plane"></i> Send
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>

    <script>
        // Auto-scroll to bottom of messages
        window.onload = function() {
            const container = document.getElementById('messagesContainer');
            container.scrollTop = container.scrollHeight;
        };

        // Auto-refresh messages every 5 seconds
        setInterval(function() {
            const container = document.getElementById('messagesContainer');
            const scrollPos = container.scrollTop;
            const scrollHeight = container.scrollHeight;

            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    const newMessages = tempDiv.querySelector('#messagesContainer');
                    if (newMessages) {
                        container.innerHTML = newMessages.innerHTML;
                        // Stay at bottom if user was at bottom
                        if (Math.abs(scrollHeight - container.scrollHeight - scrollPos) < 50) {
                            container.scrollTop = container.scrollHeight;
                        }
                    }
                });
        }, 5000);
    </script>
</body>

</html>