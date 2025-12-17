<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

/*
---------------------------------------
FETCH USER CREDITS (REAL-TIME)
---------------------------------------
*/
$credit_sql = "SELECT credits FROM users WHERE user_id='$user_id'";
$credit_res = $conn->query($credit_sql);

$user_credits = 0;
if ($credit_res && $credit_res->num_rows > 0) {
    $row = $credit_res->fetch_assoc();
    $user_credits = (int)$row['credits'];
}

/*
---------------------------------------
HANDLE SWAP REQUEST
---------------------------------------
*/
if (isset($_POST['request_swap'])) {
    $book_id = (int)$_POST['book_id'];

    // Prevent duplicate request
    $check_sql = "
        SELECT swap_id 
        FROM swap_requests 
        WHERE requester_id='$user_id' 
          AND book_id='$book_id'
    ";
    $check = $conn->query($check_sql);

    if ($check->num_rows > 0) {
        $error = "You have already requested this book.";
    } elseif ($user_credits <= 0) {
        $error = "You do not have enough swap credits.";
    } else {
        // Insert request
        $insert_sql = "
            INSERT INTO swap_requests (requester_id, book_id, status, created_at)
            VALUES ('$user_id', '$book_id', 'pending', NOW())
        ";
        if ($conn->query($insert_sql)) {
            // Deduct 1 credit
            $conn->query("
                UPDATE users 
                SET credits = credits - 1 
                WHERE user_id='$user_id'
            ");
            $success = "Swap request sent successfully!";
            $user_credits--;
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

/*
---------------------------------------
FETCH ALL AVAILABLE BOOKS
---------------------------------------
*/
$books_sql = "
SELECT 
    b.book_id,
    b.title,
    b.author,
    b.genre,
    b.book_condition,
    b.created_at,
    u.name AS owner_name,
    u.location
FROM books b
JOIN users u ON b.user_id = u.user_id
WHERE b.approved='yes'
  AND b.status='available'
  AND b.user_id != '$user_id'
ORDER BY b.created_at DESC
";
$books = $conn->query($books_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Swap | Book Swap Portal</title>
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
            padding-bottom: 100px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Styles */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2.5rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.3;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }

        .page-header h1::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 80px;
            height: 4px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 2px;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 1rem;
        }

        /* Credit Card */
        .credit-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            display: inline-flex;
            align-items: center;
            gap: 15px;
            margin-top: 1.5rem;
            border-left: 5px solid var(--success);
        }

        .credit-card i {
            font-size: 2.5rem;
            color: var(--success);
        }

        .credit-card .credit-info h3 {
            color: var(--dark);
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
        }

        .credit-card .credit-info .credit-count {
            font-size: 2rem;
            font-weight: 700;
            color: var(--success);
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

        /* Book Grid */
        .book-grid {
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
            position: relative;
            border: 1px solid var(--gray-light);
        }

        .book-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .book-header {
            background: linear-gradient(135deg, #f0f4ff 0%, #e6eeff 100%);
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .book-header h3 {
            color: var(--dark);
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .book-header .author {
            color: var(--gray);
            font-style: italic;
            font-size: 0.95rem;
        }

        .book-body {
            padding: 1.5rem;
        }

        .book-details {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--dark);
        }

        .detail-item i {
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
            margin-top: 5px;
        }

        .condition-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
        }

        .condition-rating .stars {
            color: var(--warning);
        }

        .book-footer {
            padding: 1.5rem;
            background: var(--light);
            border-top: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .owner-info {
            display: flex;
            align-items: center;
            gap: 10px;
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

        /* Button Styles */
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
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
            color: white;
        }

        .btn-success:hover {
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

        /* Responsive */
        @media (max-width: 768px) {
            .book-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                padding: 1.5rem 1rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .book-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
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
                    <div class="credit-count"><?= $user_credits['credits'] ?> <span style="font-size: 1rem;">credits</span></div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= $success ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <!-- Book Grid -->
        <?php if ($books && $books->num_rows > 0): ?>
            <div class="book-grid">
                <?php while ($b = $books->fetch_assoc()): ?>
                    <div class="book-card">
                        <div class="book-header">
                            <h3><?= htmlspecialchars($b['title']) ?></h3>
                            <p class="author">by <?= htmlspecialchars($b['author']) ?></p>
                        </div>

                        <div class="book-body">
                            <div class="book-details">
                                <div class="detail-item">
                                    <i class="fas fa-book-open"></i>
                                    <span>Genre: <strong><?= htmlspecialchars($b['genre']) ?></strong></span>
                                    <span class="genre-badge"><?= htmlspecialchars($b['genre']) ?></span>
                                </div>

                                <div class="detail-item">
                                    <i class="fas fa-star"></i>
                                    <span>Condition: <strong><?= $b['book_condition'] ?>/10</strong></span>
                                    <div class="condition-rating">
                                        <?php
                                        $condition = (int)$b['book_condition'];
                                        $filled = floor($condition / 2);
                                        for ($i = 1; $i <= 5; $i++):
                                        ?>
                                            <i class="fas fa-star<?= $i <= $filled ? '' : '-o' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="detail-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Added: <?= date("M d, Y", strtotime($b['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="book-footer">
                            <div class="owner-info">
                                <div class="owner-avatar">
                                    <?= strtoupper(substr($b['owner_name'], 0, 1)) ?>
                                </div>
                                <div class="owner-details">
                                    <h4><?= htmlspecialchars($b['owner_name']) ?></h4>
                                    <p><i class="fas fa-map-marker-alt"></i> <?= $b['location'] ?></p>
                                </div>
                            </div>

                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="book_id" value="<?= $b['book_id'] ?>">
                                <button type="submit" name="request_swap" class="btn <?= ($user_credits <= 0) ? 'btn-primary' : 'btn-success' ?>"
                                    <?= ($user_credits <= 0) ? 'disabled' : '' ?>>
                                    <i class="fas fa-exchange-alt"></i>
                                    <?= ($user_credits <= 0) ? 'No Credits' : 'Request Swap' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-books">
                <i class="fas fa-book-open"></i>
                <h3>No Books Available</h3>
                <p>There are currently no books available for swapping. Check back later or add your own books to the portal!</p>
                <a href="../user/add_book.php" class="btn btn-primary" style="margin-top: 1.5rem;">
                    <i class="fas fa-plus"></i> Add Your Books
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include "../includes/footer.php"; ?>
</body>

</html>