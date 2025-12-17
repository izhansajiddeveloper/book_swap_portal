<?php
session_start();
require_once "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '';

// Initialize messages
$success = "";
$error = "";

// Handle swap request submission
if (isset($_POST['request_swap'])) {
    $book_id = $_POST['book_id'];

    // Fetch user credits
    $credits_sql = "SELECT credits FROM users WHERE user_id='$user_id'";
    $credits_result = $conn->query($credits_sql);
    $user_credits = $credits_result->fetch_assoc()['credits'] ?? 0;

    if ($user_credits <= 0) {
        $error = "You do not have enough credits to request a swap!";
    } else {
        // Get owner of the book
        $book_sql = "SELECT user_id, approved FROM books WHERE book_id='$book_id'";
        $book_result = $conn->query($book_sql);

        if ($book_result->num_rows > 0) {
            $book = $book_result->fetch_assoc();
            $owner_id = $book['user_id'];
            $approved = $book['approved'];

            if ($owner_id == $user_id) {
                $error = "You cannot request your own book.";
            } elseif ($approved != 'yes') {
                $error = "Book is not approved for swap.";
            } else {
                // Check for duplicate request
                $check_sql = "SELECT * FROM swap_requests 
                              WHERE book_id='$book_id' 
                              AND requester_id='$user_id' 
                              AND status='pending'";
                $check_result = $conn->query($check_sql);

                if ($check_result->num_rows > 0) {
                    $error = "You have already requested this book.";
                } else {
                    // Insert swap request
                    $insert_sql = "INSERT INTO swap_requests (requester_id, book_id, status, created_at) 
                                   VALUES ('$user_id', '$book_id', 'pending', NOW())";
                    if ($conn->query($insert_sql)) {
                        // Deduct 1 credit
                        $update_credit = "UPDATE users SET credits = credits - 1 WHERE user_id='$user_id'";
                        $conn->query($update_credit);

                        $success = "Swap request sent successfully!";
                    } else {
                        $error = "Database error: " . $conn->error;
                    }
                }
            }
        } else {
            $error = "Book not found.";
        }
    }
}

// Fetch user credits
$credits_sql = "SELECT credits FROM users WHERE user_id='$user_id'";
$credits_result = $conn->query($credits_sql);
$user_credits = $credits_result->fetch_assoc()['credits'] ?? 0;

// Fetch all approved books not owned by current user
$books_sql = "SELECT b.book_id, b.title, b.author, b.genre, b.book_condition, b.image, b.created_at,
                     u.name AS owner_name, u.location
              FROM books b
              JOIN users u ON b.user_id = u.user_id
              WHERE b.approved='yes' AND b.user_id != '$user_id'
              ORDER BY b.created_at DESC";
$books_result = $conn->query($books_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Request Book Swap</title>
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
            max-width: 1400px;
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

        .credit-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            border-left: 5px solid var(--success);
        }

        .credit-card i {
            font-size: 2.5rem;
            color: var(--success);
        }

        .credit-info h3 {
            color: var(--dark);
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
        }

        .credit-count {
            font-size: 2rem;
            font-weight: 700;
            color: var(--success);
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
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .book-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .book-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .book-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid var(--gray-light);
        }

        .book-content {
            padding: 1.5rem;
            flex-grow: 1;
        }

        .book-content h3 {
            color: var(--dark);
            font-size: 1.3rem;
            margin-bottom: 0.8rem;
            line-height: 1.4;
        }

        .book-meta {
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

        .genre-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .owner-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 1rem 1.5rem;
            background: var(--light);
            border-top: 1px solid var(--gray-light);
        }

        .owner-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .owner-details h4 {
            font-size: 0.95rem;
            color: var(--dark);
            margin-bottom: 2px;
        }

        .owner-details p {
            font-size: 0.85rem;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 5px;
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
            width: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
            color: white;
        }

        .btn-success:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(16, 185, 129, 0.4);
        }

        .btn:disabled {
            background: var(--gray-light);
            color: var(--gray);
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .no-books {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            grid-column: 1 / -1;
        }

        .no-books i {
            font-size: 4rem;
            color: var(--gray-light);
            margin-bottom: 1.5rem;
        }

        .no-books h3 {
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .no-books p {
            color: var(--gray);
            max-width: 500px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .books-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                padding: 1.5rem 1rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <?php include "../includes/navbar.php"; ?>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-exchange-alt"></i> Request Book Swap</h1>
            <p>Browse available books from other readers and request swaps using your credits</p>

            <!-- Credit Card -->
            <div class="credit-card">
                <i class="fas fa-coins"></i>
                <div class="credit-info">
                    <h3>Available Credits</h3>
                    <div class="credit-count"><?php echo $user_credits['credits'] ?> <span style="font-size: 1rem;">credits</span></div>
                </div>
            </div>
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

        <div class="books-grid">
            <?php if ($books_result->num_rows > 0): ?>
                <?php while ($book = $books_result->fetch_assoc()): ?>
                    <div class="book-card">
                        <?php if (!empty($book['image'])): ?>
                            <img src="../uploads/book_images/<?php echo htmlspecialchars($book['image']); ?>"
                                alt="<?php echo htmlspecialchars($book['title']); ?>"
                                class="book-image">
                        <?php else: ?>
                            <div class="book-image" style="background: linear-gradient(135deg, var(--gray-light) 0%, var(--gray) 100%); 
                                                          display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-book" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>

                        <div class="book-content">
                            <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                            <div class="book-meta">
                                <div class="meta-item">
                                    <i class="fas fa-user-edit"></i>
                                    <span>Author: <?php echo htmlspecialchars($book['author']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-tags"></i>
                                    <span>Genre: <?php echo htmlspecialchars($book['genre']); ?></span>
                                    <span class="genre-badge"><?php echo htmlspecialchars($book['genre']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-star"></i>
                                    <span>Condition: <?php echo htmlspecialchars($book['book_condition']); ?>/10</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Added: <?php echo date("M d, Y", strtotime($book['created_at'])); ?></span>
                                </div>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                <button type="submit" name="request_swap"
                                    class="btn <?php echo ($user_credits['credits'] > 0) ? 'btn-success' : 'btn-primary'; ?>"
                                    <?php echo ($user_credits['credits'] <= 0) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-exchange-alt"></i>
                                    <?php echo ($user_credits['credits'] > 0) ? 'Request Swap (1 Credit)' : 'Insufficient Credits'; ?>
                                </button>
                            </form>
                        </div>

                        <div class="owner-info">
                            <div class="owner-avatar">
                                <?php echo strtoupper(substr($book['owner_name'], 0, 1)); ?>
                            </div>
                            <div class="owner-details">
                                <h4><?php echo htmlspecialchars($book['owner_name']); ?></h4>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($book['location']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-books">
                    <i class="fas fa-book-open"></i>
                    <h3>No Books Available</h3>
                    <p>There are currently no books available for swapping. Check back later or add your own books to the portal!</p>
                    <a href="../user/add_book.php" class="btn btn-primary" style="margin-top: 1.5rem; width: auto;">
                        <i class="fas fa-plus"></i> Add Your Books
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>
</body>

</html>