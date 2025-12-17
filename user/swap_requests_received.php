<?php
session_start();
require_once "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// Handle accept/reject actions
if (isset($_POST['action']) && isset($_POST['swap_id'])) {
    $swap_id = $_POST['swap_id'];
    $action = $_POST['action']; // accept or reject

    // Fetch request and book owner
    $request_sql = "SELECT sr.*, b.user_id AS owner_id 
                    FROM swap_requests sr
                    JOIN books b ON sr.book_id = b.book_id
                    WHERE sr.swap_id='$swap_id'";
    $request_result = $conn->query($request_sql);

    if ($request_result->num_rows > 0) {
        $request = $request_result->fetch_assoc();

        if ($request['owner_id'] != $user_id) {
            $error = "You are not authorized to manage this request.";
        } else {
            if ($action == 'accept') {
                $update_sql = "UPDATE swap_requests SET status='accepted' WHERE swap_id='$swap_id'";
                if ($conn->query($update_sql)) {
                    // Insert into user_books for the requester
                    $book_id = $request['book_id'];
                    $requester_id = $request['requester_id'];
                    $conn->query("INSERT INTO user_books (user_id, book_id, status, swapped_at) 
                                  VALUES ('$requester_id', '$book_id', 'borrowed', NOW())");
                    $success = "Request accepted! The book is now available to the requester.";
                } else {
                    $error = "Database error: " . $conn->error;
                }
            } elseif ($action == 'reject') {
                $update_sql = "UPDATE swap_requests SET status='rejected' WHERE swap_id='$swap_id'";
                if ($conn->query($update_sql)) {
                    $success = "Request rejected successfully!";
                } else {
                    $error = "Database error: " . $conn->error;
                }
            }
        }
    } else {
        $error = "Request not found.";
    }
}

// Fetch all pending swap requests for user's books
$requests_sql = "SELECT sr.swap_id, sr.status, sr.created_at, 
                        b.title, b.book_id, b.image,
                        u.name AS requester_name, u.email AS requester_email, u.location
                 FROM swap_requests sr
                 JOIN books b ON sr.book_id = b.book_id
                 JOIN users u ON sr.requester_id = u.user_id
                 WHERE b.user_id='$user_id' AND sr.status='pending'
                 ORDER BY sr.created_at DESC";
$requests_result = $conn->query($requests_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Swap Requests Received</title>
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
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert.success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 5px solid var(--success);
        }

        .alert.error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 5px solid var(--danger);
        }

        .requests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .request-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .request-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 1.5rem;
            position: relative;
        }

        .request-header h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .request-content {
            padding: 1.5rem;
            flex-grow: 1;
        }

        .requester-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .requester-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .requester-details h4 {
            color: var(--dark);
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
        }

        .requester-details p {
            color: var(--gray);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .book-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .book-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            background: var(--gray-light);
        }

        .book-details h4 {
            color: var(--dark);
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .book-details .book-id {
            color: var(--gray);
            font-size: 0.85rem;
        }

        .request-meta {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .meta-item i {
            color: var(--primary);
            width: 20px;
        }

        .request-actions {
            display: flex;
            gap: 1rem;
            padding: 1.5rem;
            background: var(--light);
            border-top: 1px solid var(--gray-light);
        }

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
            flex: 1;
        }

        .btn-accept {
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
            color: white;
        }

        .btn-accept:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-reject {
            background: linear-gradient(135deg, var(--danger) 0%, #f87171 100%);
            color: white;
        }

        .btn-reject:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(239, 68, 68, 0.4);
        }

        .no-requests {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            grid-column: 1 / -1;
        }

        .no-requests i {
            font-size: 4rem;
            color: var(--gray-light);
            margin-bottom: 1.5rem;
        }

        .no-requests h3 {
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .no-requests p {
            color: var(--gray);
            max-width: 500px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .requests-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                padding: 1.5rem 1rem;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .request-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <?php include "../includes/navbar.php"; ?>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-inbox"></i> Swap Requests Received</h1>
            <p>Manage incoming swap requests for your books</p>
        </div>

        <?php if ($success): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <div class="requests-grid">
            <?php if ($requests_result->num_rows > 0): ?>
                <?php while ($req = $requests_result->fetch_assoc()): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <h3><?php echo htmlspecialchars($req['title']); ?></h3>
                            <span class="status-badge">Pending</span>
                        </div>
                        
                        <div class="request-content">
                            <div class="requester-info">
                                <div class="requester-avatar">
                                    <?php echo strtoupper(substr($req['requester_name'], 0, 1)); ?>
                                </div>
                                <div class="requester-details">
                                    <h4><?php echo htmlspecialchars($req['requester_name']); ?></h4>
                                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($req['requester_email']); ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($req['location']); ?></p>
                                </div>
                            </div>
                            
                            <div class="book-info">
                                <?php if (!empty($req['image'])): ?>
                                    <img src="../uploads/book_images/<?php echo htmlspecialchars($req['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($req['title']); ?>" 
                                         class="book-image">
                                <?php else: ?>
                                    <div class="book-image" style="display: flex; align-items: center; justify-content: center; 
                                                                  background: linear-gradient(135deg, var(--gray-light) 0%, var(--gray) 100%); 
                                                                  color: white;">
                                        <i class="fas fa-book" style="font-size: 2rem;"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="book-details">
                                    <h4><?php echo htmlspecialchars($req['title']); ?></h4>
                                    <p class="book-id">Book ID: #<?php echo $req['book_id']; ?></p>
                                </div>
                            </div>
                            
                            <div class="request-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Requested: <?php echo date('M d, Y', strtotime($req['created_at'])); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Time: <?php echo date('h:i A', strtotime($req['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" class="request-actions">
                            <input type="hidden" name="swap_id" value="<?php echo $req['swap_id']; ?>">
                            <button type="submit" name="action" value="accept" class="btn btn-accept">
                                <i class="fas fa-check-circle"></i> Accept
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-reject">
                                <i class="fas fa-times-circle"></i> Reject
                            </button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-requests">
                    <i class="fas fa-inbox"></i>
                    <h3>No Pending Requests</h3>
                    <p>You don't have any pending swap requests. Your requests will appear here when other users want to swap your books.</p>
                    <a href="../user/my_books.php" class="btn" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); 
                                                                      color: white; margin-top: 1.5rem; width: auto;">
                        <i class="fas fa-book"></i> View My Books
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>
</body>

</html>