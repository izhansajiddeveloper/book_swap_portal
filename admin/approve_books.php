<?php
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Approve a book
if (isset($_GET['approve_id'])) {
    $book_id = intval($_GET['approve_id']);
    $conn->query("UPDATE books SET approved='yes' WHERE book_id=$book_id");
    header("Location: approve_books.php");
    exit;
}

// Reject a book
if (isset($_GET['reject_id'])) {
    $book_id = intval($_GET['reject_id']);
    $conn->query("DELETE FROM books WHERE book_id=$book_id");
    header("Location: approve_books.php");
    exit;
}

// Fetch unapproved books with correct column names
$result = $conn->query("
    SELECT books.book_id, books.title, books.author, books.genre, books.book_condition, 
           books.image, books.status, books.created_at,
           users.name AS user_name, users.location, users.email
    FROM books
    JOIN users ON books.user_id = users.user_id
    WHERE books.approved='no'
    ORDER BY books.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Books - Admin - Book Swap Portal</title>
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

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header */
        .admin-header {
            margin-bottom: 2.5rem;
        }

        .admin-title {
            font-size: 2.2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-title i {
            color: #ff9e6d;
            background: linear-gradient(135deg, rgba(255, 158, 109, 0.1), rgba(255, 209, 102, 0.1));
            padding: 12px;
            border-radius: 12px;
        }

        .admin-subtitle {
            color: #666;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .stats-banner {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Books Grid */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .book-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
            border-color: #ff9e6d;
        }

        .book-header {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
            padding: 1.5rem;
        }

        .book-id {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
        }

        .book-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            line-height: 1.3;
        }

        .book-author {
            font-size: 1rem;
            opacity: 0.9;
        }

        .book-body {
            padding: 1.5rem;
        }

        .book-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-label {
            color: #666;
            font-size: 0.9rem;
            min-width: 80px;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }

        .genre-tag {
            background: linear-gradient(135deg, rgba(255, 209, 102, 0.1), rgba(255, 158, 109, 0.1));
            color: #2c3e50;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .condition-badge {
            background: linear-gradient(135deg, #51cf66, #2f9e44);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-available {
            background: rgba(81, 207, 102, 0.1);
            color: #2f9e44;
        }

        .status-swapped {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
        }

        .user-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .user-name {
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 0.5rem;
        }

        .user-location {
            color: #666;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .book-image {
            margin-top: 1rem;
            text-align: center;
        }

        .book-image img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        .action-btn {
            flex: 1;
            padding: 0.8rem 1rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .approve-btn {
            background: linear-gradient(135deg, #51cf66, #2f9e44);
            color: white;
        }

        .approve-btn:hover {
            background: linear-gradient(135deg, #40a757, #218838);
            transform: translateY(-2px);
        }

        .reject-btn {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
        }

        .reject-btn:hover {
            background: linear-gradient(135deg, #e85959, #ff6b6b);
            transform: translateY(-2px);
        }

        /* No Books Message */
        .no-books {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .no-books i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1.5rem;
        }

        .no-books h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .no-books p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        /* Back to Dashboard */
        .back-dashboard {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #4a6491;
            text-decoration: none;
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            background: rgba(74, 100, 145, 0.1);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .back-dashboard:hover {
            background: rgba(74, 100, 145, 0.2);
            transform: translateX(-5px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }

            .books-grid {
                grid-template-columns: 1fr;
            }

            .stats-banner {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .action-buttons {
                flex-direction: column;
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

        .book-card {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <h1 class="admin-title">
                <i class="fas fa-check-circle"></i>
                Approve Books
            </h1>
            <p class="admin-subtitle">Review and approve book submissions from users</p>

            <div class="stats-banner">
                <div class="stat-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <div class="stat-number"><?php echo $result->num_rows; ?></div>
                        <div class="stat-label">Pending Approvals</div>
                    </div>
                </div>
                <a href="dashboard.php" class="back-dashboard">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="books-grid">
                <?php while ($book = $result->fetch_assoc()): ?>
                    <div class="book-card">
                        <div class="book-header">
                            <div class="book-id">ID: <?php echo $book['book_id']; ?></div>
                            <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                        </div>

                        <div class="book-body">
                            <div class="book-info">
                                <div class="info-row">
                                    <span class="info-label">Genre:</span>
                                    <span class="genre-tag"><?php echo htmlspecialchars($book['genre']); ?></span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Condition:</span>
                                    <span class="condition-badge"><?php echo $book['book_condition']; ?>/10</span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Status:</span>
                                    <span class="status-badge status-<?php echo $book['status']; ?>">
                                        <i class="fas fa-circle"></i>
                                        <?php echo ucfirst($book['status']); ?>
                                    </span>
                                </div>

                                <div class="info-row">
                                    <span class="info-label">Added:</span>
                                    <span class="info-value"><?php echo date('M j, Y', strtotime($book['created_at'])); ?></span>
                                </div>
                            </div>

                            <?php if (!empty($book['image'])): ?>
                                <div class="book-image">
                                    <img src="../uploads/book_images/<?php echo htmlspecialchars($book['image']); ?>"
                                        alt="<?php echo htmlspecialchars($book['title']); ?>">
                                </div>
                            <?php endif; ?>

                            <div class="user-info">
                                <div class="user-name">
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($book['user_name']); ?>
                                </div>
                                <div class="user-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($book['location']); ?>
                                </div>
                                <div class="user-location">
                                    <i class="fas fa-envelope"></i>
                                    <?php echo htmlspecialchars($book['email']); ?>
                                </div>
                            </div>

                            <div class="action-buttons">
                                <a href="?approve_id=<?php echo $book['book_id']; ?>"
                                    class="action-btn approve-btn"
                                    onclick="return confirm('Approve this book?')">
                                    <i class="fas fa-check"></i>
                                    Approve
                                </a>
                                <a href="?reject_id=<?php echo $book['book_id']; ?>"
                                    class="action-btn reject-btn"
                                    onclick="return confirm('Reject this book? This action cannot be undone.')">
                                    <i class="fas fa-times"></i>
                                    Reject
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-books">
                <i class="fas fa-check-circle"></i>
                <h3>No Pending Approvals</h3>
                <p>All books have been reviewed and approved.</p>
                <a href="dashboard.php" class="back-dashboard">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Add confirmation for reject action
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to reject this book? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>