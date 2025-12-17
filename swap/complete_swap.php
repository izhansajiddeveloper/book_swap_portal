<?php
session_start();
require_once "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$swap_id = isset($_GET['swap_id']) ? (int)$_GET['swap_id'] : 0;
$success = "";
$error = "";

// Check if swap_id is provided
if ($swap_id === 0) {
    header("Location: swap_requests.php");
    exit;
}

// Fetch swap details with book and user information
$swap_sql = "SELECT 
                sr.swap_id,
                sr.book_id,
                sr.requester_id,
                sr.status,
                sr.created_at,
                b.title AS book_title,
                b.user_id AS book_owner_id,
                b.book_condition,
                u1.name AS requester_name,
                u1.email AS requester_email,
                u1.location AS requester_location,
                u2.name AS owner_name,
                u2.email AS owner_email,
                u2.credits AS owner_credits
            FROM swap_requests sr
            JOIN books b ON sr.book_id = b.book_id
            JOIN users u1 ON sr.requester_id = u1.user_id
            JOIN users u2 ON b.user_id = u2.user_id
            WHERE sr.swap_id = '$swap_id' 
            AND (sr.requester_id = '$user_id' OR b.user_id = '$user_id')
            AND sr.status IN ('accepted', 'pending')";

$swap_result = $conn->query($swap_sql);

if ($swap_result->num_rows === 0) {
    header("Location: swap_requests.php");
    exit;
}

$swap = $swap_result->fetch_assoc();
$is_owner = ($user_id == $swap['book_owner_id']);
$is_requester = ($user_id == $swap['requester_id']);

// Handle swap completion
if (isset($_POST['complete_swap'])) {
    if (!$is_owner && !$is_requester) {
        $error = "You are not authorized to complete this swap.";
    } elseif ($swap['status'] !== 'accepted') {
        $error = "This swap needs to be accepted before it can be completed.";
    } else {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Update swap status to completed
            $update_swap = "UPDATE swap_requests 
                           SET status = 'completed', 
                               completed_at = NOW() 
                           WHERE swap_id = '$swap_id'";
            $conn->query($update_swap);

            // Mark book as swapped
            $update_book = "UPDATE books 
                           SET status = 'swapped' 
                           WHERE book_id = '{$swap['book_id']}'";
            $conn->query($update_book);

            // Give credit to book owner
            $update_owner_credit = "UPDATE users 
                                   SET credits = credits + 1 
                                   WHERE user_id = '{$swap['book_owner_id']}'";
            $conn->query($update_owner_credit);

            // Create a swap history record
            $insert_history = "INSERT INTO swap_history 
                              (swap_id, book_id, requester_id, owner_id, completed_at) 
                              VALUES ('$swap_id', '{$swap['book_id']}', 
                                      '{$swap['requester_id']}', '{$swap['book_owner_id']}', NOW())";
            $conn->query($insert_history);

            // Record the book transfer
            $insert_transfer = "INSERT INTO book_transfers 
                               (book_id, from_user_id, to_user_id, transfer_date) 
                               VALUES ('{$swap['book_id']}', '{$swap['book_owner_id']}', 
                                       '{$swap['requester_id']}', NOW())";
            $conn->query($insert_transfer);

            // Commit transaction
            $conn->commit();

            $success = "Swap completed successfully! The book owner has received 1 credit.";

            // Refresh swap data
            $swap['status'] = 'completed';
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error completing swap: " . $e->getMessage();
        }
    }
}

// Handle swap cancellation
if (isset($_POST['cancel_swap'])) {
    if (!$is_owner && !$is_requester) {
        $error = "You are not authorized to cancel this swap.";
    } elseif ($swap['status'] === 'completed') {
        $error = "This swap has already been completed and cannot be cancelled.";
    } else {
        $conn->begin_transaction();

        try {
            // Update swap status to cancelled
            $update_swap = "UPDATE swap_requests 
                           SET status = 'cancelled', 
                               cancelled_at = NOW() 
                           WHERE swap_id = '$swap_id'";
            $conn->query($update_swap);

            // Mark book as available again
            $update_book = "UPDATE books 
                           SET status = 'available' 
                           WHERE book_id = '{$swap['book_id']}'";
            $conn->query($update_book);

            // Return credit to requester if they had spent one
            if ($swap['status'] === 'accepted') {
                $return_credit = "UPDATE users 
                                 SET credits = credits + 1 
                                 WHERE user_id = '{$swap['requester_id']}'";
                $conn->query($return_credit);
            }

            $conn->commit();
            $success = "Swap cancelled successfully!";

            // Refresh swap data
            $swap['status'] = 'cancelled';
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error cancelling swap: " . $e->getMessage();
        }
    }
}

// Handle dispute reporting
if (isset($_POST['report_dispute'])) {
    $dispute_reason = $conn->real_escape_string($_POST['dispute_reason']);
    $additional_info = $conn->real_escape_string($_POST['additional_info']);

    if (empty($dispute_reason)) {
        $error = "Please select a dispute reason.";
    } else {
        $insert_dispute = "INSERT INTO disputes 
                          (swap_id, reporter_id, reason, additional_info, status, created_at) 
                          VALUES ('$swap_id', '$user_id', '$dispute_reason', 
                                  '$additional_info', 'open', NOW())";

        if ($conn->query($insert_dispute)) {
            // Update swap status to disputed
            $conn->query("UPDATE swap_requests SET status = 'disputed' WHERE swap_id = '$swap_id'");
            $success = "Dispute reported successfully! Our team will review it within 24 hours.";
            $swap['status'] = 'disputed';
        } else {
            $error = "Error reporting dispute: " . $conn->error;
        }
    }
}

// Fetch messages for this swap
$messages_sql = "SELECT m.*, u.name AS sender_name 
                 FROM messages m
                 JOIN users u ON m.sender_id = u.user_id
                 WHERE m.swap_id = '$swap_id'
                 ORDER BY m.timestamp ASC";
$messages_result = $conn->query($messages_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Complete Swap | Book Swap Portal</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2.5rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: linear-gradient(135deg, var(--warning) 0%, #fbbf24 100%);
        }

        .status-accepted {
            background: linear-gradient(135deg, var(--info) 0%, #60a5fa 100%);
        }

        .status-completed {
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
        }

        .status-cancelled {
            background: linear-gradient(135deg, var(--danger) 0%, #f87171 100%);
        }

        .status-disputed {
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 5px solid var(--success);
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 5px solid var(--danger);
        }

        /* Swap Details */
        .swap-details-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 900px) {
            .swap-details-container {
                grid-template-columns: 1fr;
            }
        }

        .detail-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }

        .detail-card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-light);
        }

        .card-header i {
            font-size: 1.8rem;
            color: var(--primary);
        }

        .card-header h3 {
            color: var(--dark);
            font-size: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .info-item {
            margin-bottom: 1rem;
        }

        .info-label {
            color: var(--gray);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            color: var(--dark);
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* User Cards */
        .user-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .user-cards {
                grid-template-columns: 1fr;
            }
        }

        .user-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            position: relative;
            border-top: 4px solid var(--primary);
        }

        .user-card.owner {
            border-top-color: var(--success);
        }

        .user-card.requester {
            border-top-color: var(--info);
        }

        .user-role {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .user-card.owner .user-role {
            background: var(--success);
        }

        .user-card.requester .user-role {
            background: var(--info);
        }

        .user-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 1.5rem;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }

        .user-card.owner .user-avatar {
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
        }

        .user-card.requester .user-avatar {
            background: linear-gradient(135deg, var(--info) 0%, #60a5fa 100%);
        }

        .user-details h3 {
            color: var(--dark);
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
        }

        .user-details p {
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .user-stat {
            text-align: center;
            padding: 1rem;
            background: var(--light);
            border-radius: 10px;
        }

        .user-stat .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }

        .user-card.owner .user-stat .value {
            color: var(--success);
        }

        .user-card.requester .user-stat .value {
            color: var(--info);
        }

        .user-stat .label {
            font-size: 0.9rem;
            color: var(--gray);
            margin-top: 0.5rem;
        }

        /* Actions Section */
        .actions-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .action-card {
            background: var(--light);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .action-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }

        .action-card.success .action-icon {
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
        }

        .action-card.danger .action-icon {
            background: linear-gradient(135deg, var(--danger) 0%, #f87171 100%);
        }

        .action-card.warning .action-icon {
            background: linear-gradient(135deg, var(--warning) 0%, #fbbf24 100%);
        }

        .action-card h4 {
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .action-card p {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        /* Buttons */
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.95rem;
            text-decoration: none;
        }

        .btn-block {
            width: 100%;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
            color: white;
        }

        .btn-success:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #f87171 100%);
            color: white;
        }

        .btn-danger:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(239, 68, 68, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning) 0%, #fbbf24 100%);
            color: white;
        }

        .btn-warning:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(245, 158, 11, 0.4);
        }

        .btn:disabled {
            background: var(--gray-light);
            color: var(--gray);
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        /* Dispute Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            color: var(--dark);
            font-size: 1.5rem;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--gray);
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: var(--danger);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* Completed State */
        .completed-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .completed-icon {
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 1.5rem;
        }

        .completed-state h2 {
            color: var(--dark);
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .completed-state p {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto 2rem;
            line-height: 1.6;
        }

        /* Quick Links */
        .quick-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .link-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: 10px;
            text-decoration: none;
            color: var(--dark);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .link-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            color: var(--primary);
        }

        .link-card i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .link-card span {
            font-weight: 600;
        }
    </style>
</head>

<body>
    <?php include "../includes/navbar.php"; ?>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-handshake"></i> Complete Swap</h1>
            <p>Finalize your book swap transaction</p>
            <div class="status-badge status-<?php echo $swap['status']; ?>">
                <?php echo ucfirst($swap['status']); ?>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($swap['status'] == 'completed'): ?>
            <!-- Completed State -->
            <div class="completed-state">
                <div class="completed-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Swap Completed Successfully!</h2>
                <p>This book swap has been marked as completed. The book owner has received 1 credit for the successful transaction. You can view the transaction details below.</p>

                <div class="quick-links">
                    <a href="messages.php?swap_id=<?php echo $swap_id; ?>" class="link-card">
                        <i class="fas fa-comments"></i>
                        <span>View Messages</span>
                    </a>
                    <a href="../user/dashboard.php" class="link-card">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Go to Dashboard</span>
                    </a>
                    <a href="../user/my_books.php" class="link-card">
                        <i class="fas fa-book"></i>
                        <span>My Books</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Swap Details -->
        <div class="swap-details-container">
            <!-- Book Information -->
            <div class="detail-card">
                <div class="card-header">
                    <i class="fas fa-book"></i>
                    <h3>Book Information</h3>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Book Title</div>
                        <div class="info-value"><?php echo htmlspecialchars($swap['book_title']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Condition</div>
                        <div class="info-value"><?php echo $swap['book_condition']; ?>/10</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Swap ID</div>
                        <div class="info-value">#<?php echo $swap_id; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Request Date</div>
                        <div class="info-value"><?php echo date('M d, Y', strtotime($swap['created_at'])); ?></div>
                    </div>
                </div>
            </div>

            <!-- Transaction Details -->
            <div class="detail-card">
                <div class="card-header">
                    <i class="fas fa-exchange-alt"></i>
                    <h3>Transaction Details</h3>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status-badge status-<?php echo $swap['status']; ?>" style="font-size: 0.8rem; padding: 0.3rem 1rem;">
                                <?php echo ucfirst($swap['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Credits Involved</div>
                        <div class="info-value">1 Credit</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Your Role</div>
                        <div class="info-value">
                            <?php if ($is_owner): ?>
                                <span style="color: var(--success); font-weight: 600;">
                                    <i class="fas fa-user-check"></i> Book Owner
                                </span>
                            <?php else: ?>
                                <span style="color: var(--info); font-weight: 600;">
                                    <i class="fas fa-user-clock"></i> Requester
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Action Required</div>
                        <div class="info-value">
                            <?php if ($swap['status'] == 'pending' && $is_owner): ?>
                                <span style="color: var(--warning); font-weight: 600;">Accept/Reject Request</span>
                            <?php elseif ($swap['status'] == 'accepted'): ?>
                                <span style="color: var(--success); font-weight: 600;">Complete the Swap</span>
                            <?php elseif ($swap['status'] == 'completed'): ?>
                                <span style="color: var(--success); font-weight: 600;">Completed</span>
                            <?php else: ?>
                                <span style="color: var(--gray);">None</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Information -->
        <div class="user-cards">
            <!-- Book Owner -->
            <div class="user-card owner">
                <div class="user-role">Book Owner</div>
                <div class="user-header">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($swap['owner_name'], 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <h3><?php echo htmlspecialchars($swap['owner_name']); ?></h3>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($swap['owner_email']); ?></p>
                    </div>
                </div>
                <div class="user-stats">
                    <div class="user-stat">
                        <div class="value"><?php echo $swap['owner_credits']; ?></div>
                        <div class="label">Current Credits</div>
                    </div>
                    <div class="user-stat">
                        <div class="value">
                            <?php if ($swap['status'] == 'completed'): ?>
                                +1
                            <?php else: ?>
                                0
                            <?php endif; ?>
                        </div>
                        <div class="label">From This Swap</div>
                    </div>
                </div>
            </div>

            <!-- Requester -->
            <div class="user-card requester">
                <div class="user-role">Requester</div>
                <div class="user-header">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($swap['requester_name'], 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <h3><?php echo htmlspecialchars($swap['requester_name']); ?></h3>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($swap['requester_email']); ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($swap['requester_location']); ?></p>
                    </div>
                </div>
                <div class="user-stats">
                    <div class="user-stat">
                        <div class="value">
                            <?php
                            // Fetch requester credits
                            $requester_credits_sql = "SELECT credits FROM users WHERE user_id = '{$swap['requester_id']}'";
                            $requester_credits_result = $conn->query($requester_credits_sql);
                            $requester_credits = $requester_credits_result->fetch_assoc()['credits'];
                            echo $requester_credits;
                            ?>
                        </div>
                        <div class="label">Current Credits</div>
                    </div>
                    <div class="user-stat">
                        <div class="value">
                            <?php if ($swap['status'] == 'pending'): ?>
                                0
                            <?php elseif ($swap['status'] == 'accepted'): ?>
                                -1
                            <?php else: ?>
                                -1
                            <?php endif; ?>
                        </div>
                        <div class="label">For This Swap</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($swap['status'] !== 'completed' && $swap['status'] !== 'cancelled' && $swap['status'] !== 'disputed'): ?>
            <!-- Actions Section -->
            <div class="actions-section">
                <h2 style="color: var(--dark); margin-bottom: 1rem; font-size: 1.8rem;">
                    <i class="fas fa-cogs"></i> Available Actions
                </h2>

                <div class="actions-grid">
                    <?php if ($swap['status'] == 'accepted'): ?>
                        <!-- Complete Swap -->
                        <div class="action-card success">
                            <div class="action-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h4>Complete Swap</h4>
                            <p>Mark this swap as completed when you've received the book</p>
                            <form method="POST" style="margin: 0;">
                                <button type="submit" name="complete_swap" class="btn btn-success btn-block">
                                    <i class="fas fa-check-circle"></i> Mark as Completed
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Cancel Swap -->
                    <div class="action-card danger">
                        <div class="action-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <h4>Cancel Swap</h4>
                        <p>Cancel this swap request</p>
                        <form method="POST" style="margin: 0;">
                            <button type="submit" name="cancel_swap" class="btn btn-danger btn-block"
                                onclick="return confirm('Are you sure you want to cancel this swap?');">
                                <i class="fas fa-times-circle"></i> Cancel Swap
                            </button>
                        </form>
                    </div>

                    <!-- Report Dispute -->
                    <div class="action-card warning">
                        <div class="action-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4>Report Issue</h4>
                        <p>Report a problem with this swap</p>
                        <button type="button" class="btn btn-warning btn-block" onclick="openDisputeModal()">
                            <i class="fas fa-exclamation-triangle"></i> Report Dispute
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Links -->
        <div class="quick-links" style="margin-top: 2rem;">
            <a href="messages.php?swap_id=<?php echo $swap_id; ?>" class="link-card">
                <i class="fas fa-comments"></i>
                <span>View Messages</span>
            </a>
            <a href="swap_requests.php" class="link-card">
                <i class="fas fa-exchange-alt"></i>
                <span>My Swap Requests</span>
            </a>
            <a href="../user/dashboard.php" class="link-card">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Dispute Modal -->
    <div class="modal-overlay" id="disputeModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Report a Dispute</h3>
                <button type="button" class="close-modal" onclick="closeDisputeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="dispute_reason" class="form-label">Dispute Reason *</label>
                        <select id="dispute_reason" name="dispute_reason" class="form-control" required>
                            <option value="">Select a reason...</option>
                            <option value="book_not_as_described">Book not as described</option>
                            <option value="damaged_book">Book arrived damaged</option>
                            <option value="wrong_book">Wrong book received</option>
                            <option value="no_communication">No communication from other party</option>
                            <option value="meetup_issues">Issues with meetup arrangement</option>
                            <option value="other">Other issue</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="additional_info" class="form-label">Additional Information</label>
                        <textarea id="additional_info" name="additional_info" class="form-control"
                            placeholder="Please provide details about the issue..."></textarea>
                    </div>

                    <div style="display: flex; gap: 1rem;">
                        <button type="button" class="btn" style="background: var(--gray-light); color: var(--dark); flex: 1;"
                            onclick="closeDisputeModal()">
                            Cancel
                        </button>
                        <button type="submit" name="report_dispute" class="btn btn-warning" style="flex: 1;">
                            <i class="fas fa-paper-plane"></i> Submit Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>

    <script>
        // Dispute Modal Functions
        function openDisputeModal() {
            document.getElementById('disputeModal').style.display = 'flex';
        }

        function closeDisputeModal() {
            document.getElementById('disputeModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('disputeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDisputeModal();
            }
        });

        // Confirm before cancellation
        document.querySelectorAll('form').forEach(form => {
            if (form.querySelector('button[name="cancel_swap"]')) {
                form.querySelector('button[name="cancel_swap"]').addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to cancel this swap? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            }
        });

        // Status update animation
        const statusBadge = document.querySelector('.status-badge');
        if (statusBadge) {
            statusBadge.style.animation = 'slideIn 0.5s ease';
        }
    </script>
</body>

</html>