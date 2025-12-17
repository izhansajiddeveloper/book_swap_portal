<?php
include "../config/db.php";
include "../includes/navbar.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user_name'];
$id = $_SESSION['user_id'];

// Fetch user credits
$credits_sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$credits_result = $conn->query($credits_sql);
$user = $credits_result->fetch_assoc();

// Fetch total books added by user
$books_sql = "SELECT COUNT(*) AS total_books FROM books WHERE user_id = '$user_id'";
$books_result = $conn->query($books_sql);
$books_count = $books_result->fetch_assoc()['total_books'];

// Fetch approved books
$approved_books_sql = "SELECT COUNT(*) AS approved_books FROM books WHERE user_id = '$user_id' AND approved='yes'";
$approved_books_result = $conn->query($approved_books_sql);
$approved_books = $approved_books_result->fetch_assoc()['approved_books'];

// Fetch pending books
$pending_books_sql = "SELECT COUNT(*) AS pending_books FROM books WHERE user_id = '$user_id' AND approved='no'";
$pending_books_result = $conn->query($pending_books_sql);
$pending_books = $pending_books_result->fetch_assoc()['pending_books'];

// Fetch swap requests statistics
$sent_requests_sql = "SELECT COUNT(*) AS sent_requests FROM swap_requests WHERE requester_id = '$user_id'";
$sent_requests_result = $conn->query($sent_requests_sql);
$sent_requests = $sent_requests_result->fetch_assoc()['sent_requests'];

$received_requests_sql = "
    SELECT COUNT(*) AS received_requests 
    FROM swap_requests sr
    JOIN books b ON sr.book_id = b.book_id
    WHERE b.user_id = '$user_id'
";
$received_requests_result = $conn->query($received_requests_sql);

$received_requests = 0;
if ($received_requests_result && $row = $received_requests_result->fetch_assoc()) {
    $received_requests = $row['received_requests'];
};

// Fetch completed/accepted swaps
$completed_sql = "SELECT COUNT(*) AS completed_swaps FROM swap_requests WHERE requester_id = '$user_id' AND status IN ('accepted','completed')";
$completed_result = $conn->query($completed_sql);
$completed_swaps = $completed_result->fetch_assoc()['completed_swaps'];

// Fetch recent activities (swaps, messages, book additions)
$recent_activities = $conn->query("
    (SELECT 
        'swap_request' as type,
        'Swap Request Sent' as activity,
        sr.created_at as date,
        b.title as book_title
    FROM swap_requests sr
    JOIN books b ON sr.book_id = b.book_id
    WHERE sr.requester_id = '$user_id'
    ORDER BY sr.created_at DESC
    LIMIT 3)
    UNION
    (SELECT 
        'book_added' as type,
        'Book Added' as activity,
        b.created_at as date,
        b.title as book_title
    FROM books b
    WHERE b.user_id = '$user_id'
    ORDER BY b.created_at DESC
    LIMIT 3)
    ORDER BY date DESC
    LIMIT 5
");

// Fetch recommended books
$recommended_books = $conn->query("
    SELECT b.book_id, b.title, b.author, b.genre, u.name as owner, u.location
    FROM books b
    JOIN users u ON b.user_id = u.user_id
    WHERE b.approved = 'yes' 
    AND b.user_id != '$user_id'
    AND b.status = 'available'
    ORDER BY b.created_at DESC
    LIMIT 4
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Book Swap Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #333;
            min-height: 100vh;
        }

        .user-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header */
        .dashboard-header {
            margin-bottom: 3rem;
        }

        .welcome-section {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
            padding: 2.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .welcome-title i {
            color: #ffd166;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 15px;
        }

        .welcome-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
        }

        .stats-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .credits-display {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .credits-count {
            font-size: 2.8rem;
            font-weight: 800;
            color: #ffd166;
        }

        .credits-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        .quick-action {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn {
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(to right, #ff9e6d, #ffd166);
            color: #2c3e50;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 158, 109, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.8rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            color: white;
        }

        .stat-card.books .stat-icon {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
        }

        .stat-card.swaps .stat-icon {
            background: linear-gradient(135deg, #ff9e6d, #ffd166);
        }

        .stat-card.requests .stat-icon {
            background: linear-gradient(135deg, #51cf66, #2f9e44);
        }

        .stat-card.completed .stat-icon {
            background: linear-gradient(135deg, #748ffc, #3b5bdb);
        }

        .stat-content {
            margin-top: 1rem;
        }

        .stat-number {
            font-size: 2.8rem;
            font-weight: 800;
            color: #2c3e50;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: #666;
            margin-bottom: 1.2rem;
            font-weight: 500;
        }

        .stat-subtext {
            font-size: 0.9rem;
            color: #888;
            margin-top: 0.5rem;
        }

        /* Dashboard Content */
        .dashboard-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        /* Data Cards */
        .data-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .data-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f3f5;
        }

        .data-card-title {
            font-size: 1.4rem;
            color: #2c3e50;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .data-card-title i {
            color: #ff9e6d;
        }

        .view-all {
            color: #4a6491;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        /* Activity List */
        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 0.8rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
            font-size: 1.2rem;
        }

        .activity-icon.swap {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
        }

        .activity-icon.book {
            background: linear-gradient(135deg, #51cf66, #2f9e44);
        }

        .activity-info {
            flex: 1;
        }

        .activity-text {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .activity-time {
            color: #888;
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 0.3rem;
        }

        /* Books Grid */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.5rem;
        }

        .book-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .book-image {
            height: 150px;
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #4a6491;
        }

        .book-info {
            padding: 1.2rem;
        }

        .book-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.3rem;
            line-height: 1.3;
        }

        .book-author {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }

        .book-meta {
            display: flex;
            justify-content: space-between;
            color: #888;
            font-size: 0.85rem;
        }

        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 3rem;
        }

        .action-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }

        .action-icon {
            font-size: 2.5rem;
            color: #4a6491;
            background: rgba(74, 100, 145, 0.1);
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
        }

        .action-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .action-desc {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #888;
            font-style: italic;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .user-container {
                padding: 1rem;
            }

            .stats-banner {
                flex-direction: column;
                gap: 1.5rem;
                align-items: flex-start;
            }

            .quick-action {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-content {
                grid-template-columns: 1fr;
            }

            .books-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card,
        .data-card,
        .action-card {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>

<body>


    <div class="user-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="welcome-section">
                <h1 class="welcome-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Welcome Back, <?php echo htmlspecialchars($username); ?>!
                </h1>
                <p class="welcome-subtitle">Manage your books, swaps, and connections in one place</p>

                <div class="stats-banner">
                    <div class="credits-display">
                        <div class="credits-count"><?php echo $user["credits"]; ?></div>
                        <div class="credits-label">
                            <div>Swap Credits</div>
                            <small style="opacity: 0.7;">Use credits to request books</small>
                        </div>
                    </div>
                    <div class="quick-action">
                        <a href="add_book.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i>
                            Add New Book
                        </a>
                        <a href="my_books.php" class="btn btn-secondary">
                            <i class="fas fa-book"></i>
                            View My Books
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <a href="my_books.php" class="stat-card books">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $books_count; ?></div>
                    <div class="stat-label">Total Books Added</div>
                    <div class="stat-subtext">
                        <?php echo $approved_books; ?> approved • <?php echo $pending_books; ?> pending
                    </div>
                </div>
            </a>

            <a href="request_swap.php" class="stat-card swaps">
                <div class="stat-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $sent_requests; ?></div>
                    <div class="stat-label">Swap Requests Sent</div>
                </div>
            </a>

            <a href="swap_requests_received.php" class="stat-card requests">
                <div class="stat-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $received_requests; ?></div>
                    <div class="stat-label">Requests Received</div>
                </div>
            </a>

            <a href="/book-swap-portal/swap/complete_swap.php" class="stat-card completed">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $completed_swaps; ?></div>
                    <div class="stat-label">Successful Swaps</div>
                </div>
            </a>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Recent Activities -->
            <div class="data-card">
                <div class="data-card-header">
                    <h3 class="data-card-title">
                        <i class="fas fa-history"></i>
                        Recent Activities
                    </h3>
                    <a href="#" class="view-all">
                        View All
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <?php if ($recent_activities && $recent_activities->num_rows > 0): ?>
                    <ul class="activity-list">
                        <?php while ($activity = $recent_activities->fetch_assoc()):
                            $icon_class = $activity['type'] == 'swap_request' ? 'swap' : 'book';
                            $icon = $activity['type'] == 'swap_request' ? 'exchange-alt' : 'book';
                        ?>
                            <li class="activity-item">
                                <div class="activity-icon <?php echo $icon_class; ?>">
                                    <i class="fas fa-<?php echo $icon; ?>"></i>
                                </div>
                                <div class="activity-info">
                                    <div class="activity-text">
                                        <?php
                                        if ($activity['type'] == 'swap_request') {
                                            echo 'Requested swap for <strong>' . htmlspecialchars($activity['book_title']) . '</strong>';
                                        } else {
                                            echo 'Added book <strong>' . htmlspecialchars($activity['book_title']) . '</strong>';
                                        }
                                        ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php
                                        $time_ago = strtotime($activity['date']);
                                        $now = time();
                                        $diff = $now - $time_ago;

                                        if ($diff < 60) echo 'Just now';
                                        elseif ($diff < 3600) echo floor($diff / 60) . ' min ago';
                                        elseif ($diff < 86400) echo floor($diff / 3600) . ' hours ago';
                                        else echo date('M j', $time_ago);
                                        ?>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-history"></i>
                        <p>No recent activities</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recommended Books -->
            <div class="data-card">
                <div class="data-card-header">
                    <h3 class="data-card-title">
                        <i class="fas fa-star"></i>
                        Recommended For You
                    </h3>
                    <a href="../browse.php" class="view-all">
                        Browse All
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <?php if ($recommended_books && $recommended_books->num_rows > 0): ?>
                    <div class="books-grid">
                        <?php while ($book = $recommended_books->fetch_assoc()): ?>
                            <a href="../browse.php?book=<?php echo $book['book_id']; ?>" class="book-card">
                                <div class="book-image">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="book-info">
                                    <h4 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h4>
                                    <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                                    <div class="book-meta">
                                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($book['owner']); ?></span>
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($book['location']); ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-book-open"></i>
                        <p>No recommended books available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions-grid">
            <a href="add_book.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h3 class="action-title">Add New Book</h3>
                <p class="action-desc">List your books for others to discover and request</p>
            </a>

            <a href="my_books.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-book"></i>
                </div>
                <h3 class="action-title">Manage Books</h3>
                <p class="action-desc">View, edit, or remove your listed books</p>
            </a>

            <a href="swap_requests.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h3 class="action-title">Swap Requests</h3>
                <p class="action-desc">Manage incoming and outgoing swap requests</p>
            </a>

            <a href="../swap/messages.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3 class="action-title">Messages</h3>
                <p class="action-desc">Chat with other users about swaps</p>
            </a>
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>

    <script>
        // Animate counter numbers
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.stat-number');
            const speed = 200;

            counters.forEach(counter => {
                const updateCount = () => {
                    const target = +counter.textContent;
                    const count = +counter.textContent;
                    const increment = target / speed;

                    if (count < target) {
                        counter.textContent = Math.ceil(count + increment);
                        setTimeout(updateCount, 1);
                    } else {
                        counter.textContent = target;
                    }
                };

                updateCount();
            });
        });
    </script>
</body>

</html>