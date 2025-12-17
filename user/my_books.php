<?php

require_once "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// Handle deletion of book
if (isset($_GET['delete'])) {
    $book_id = intval($_GET['delete']);
    // Only allow deletion of books added by the user and not approved yet
    $check_sql = "SELECT * FROM books WHERE book_id='$book_id' AND user_id='$user_id' AND approved='no'";
    $check_result = $conn->query($check_sql);
    if ($check_result->num_rows > 0) {
        $conn->query("DELETE FROM books WHERE book_id='$book_id'");
        $success = "Book deleted successfully.";
    } else {
        $error = "Cannot delete this book.";
    }
}

// Fetch all books added by user
$sql = "SELECT * FROM books WHERE user_id='$user_id' ORDER BY created_at DESC";
$result = $conn->query($sql);

// Calculate stats
$total_books = $result->num_rows;
$approved_books = 0;
$pending_books = 0;
$swapped_books = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['approved'] == 'yes') $approved_books++;
        else $pending_books++;
        if ($row['status'] == 'swapped') $swapped_books++;
    }
    $result->data_seek(0); // Reset pointer
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Books - Book Swap Portal</title>
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
        .page-header {
            margin-bottom: 2.5rem;
        }

        .page-title {
            font-size: 2.2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title i {
            color: #ff9e6d;
            background: linear-gradient(135deg, rgba(255, 158, 109, 0.1), rgba(255, 209, 102, 0.1));
            padding: 12px;
            border-radius: 12px;
        }

        .page-subtitle {
            color: #666;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Stats Banner */
        .stats-banner {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-item {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .stat-item i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #4a6491;
        }

        .stat-item h3 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 800;
        }

        .stat-item p {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Books Grid */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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
        }

        .book-card.pending {
            border-color: #ffd166;
        }

        .book-card.approved {
            border-color: #51cf66;
        }

        .book-card.swapped {
            border-color: #ff6b6b;
        }

        .book-header {
            padding: 1.5rem;
            position: relative;
        }

        .book-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: linear-gradient(135deg, #ffd166, #ff9e6d);
            color: #2c3e50;
        }

        .status-approved {
            background: linear-gradient(135deg, #51cf66, #2f9e44);
            color: white;
        }

        .status-swapped {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
        }

        .book-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .book-author {
            color: #666;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .book-body {
            padding: 0 1.5rem 1.5rem;
        }

        .book-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            color: #888;
            font-size: 0.8rem;
            margin-bottom: 0.3rem;
        }

        .meta-value {
            color: #2c3e50;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .genre-tag {
            background: linear-gradient(135deg, rgba(74, 100, 145, 0.1), rgba(44, 62, 80, 0.1));
            color: #4a6491;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .condition-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .stars {
            color: #ffd166;
            font-size: 1rem;
        }

        .condition-text {
            color: #666;
            font-size: 0.9rem;
        }

        .book-image {
            text-align: center;
            margin-bottom: 1rem;
        }

        .book-image img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .book-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        .action-btn {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .delete-btn {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
        }

        .delete-btn:hover {
            background: linear-gradient(135deg, #e85959, #ff6b6b);
            transform: translateY(-2px);
        }

        .view-btn {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
        }

        .view-btn:hover {
            background: linear-gradient(135deg, #3b5275, #2c3e50);
            transform: translateY(-2px);
        }

        /* Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #51cf66;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #ff6b6b;
        }

        .alert i {
            font-size: 1.2rem;
        }

        /* No Books Message */
        .no-books {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            grid-column: 1 / -1;
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

        /* Responsive */
        @media (max-width: 768px) {
            .user-container {
                padding: 1rem;
            }

            .books-grid {
                grid-template-columns: 1fr;
            }

            .stats-banner {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Animation */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

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
    <?php include "../includes/navbar.php"; ?>

    <div class="user-container">
        <!-- Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-book"></i>
                My Books
            </h1>
            <p class="page-subtitle">Manage all the books you've listed for swapping</p>
        </div>

        <!-- Messages -->
        <?php if ($success != ""): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error != ""): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <!-- Stats Banner -->
        <div class="stats-banner">
            <div class="stat-item">
                <i class="fas fa-book"></i>
                <h3><?php echo $total_books; ?></h3>
                <p>Total Books</p>
            </div>
            <div class="stat-item">
                <i class="fas fa-check-circle"></i>
                <h3><?php echo $approved_books; ?></h3>
                <p>Approved</p>
            </div>
            <div class="stat-item">
                <i class="fas fa-clock"></i>
                <h3><?php echo $pending_books; ?></h3>
                <p>Pending Review</p>
            </div>
            <div class="stat-item">
                <i class="fas fa-exchange-alt"></i>
                <h3><?php echo $swapped_books; ?></h3>
                <p>Currently Swapped</p>
            </div>
        </div>

        <!-- Add Book Button -->
        <div style="text-align: center; margin-bottom: 2rem;">
            <a href="add_book.php" class="action-btn view-btn" style="display: inline-flex; padding: 1rem 2rem; font-size: 1.1rem;">
                <i class="fas fa-plus-circle"></i>
                Add New Book
            </a>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="books-grid">
                <?php while ($row = $result->fetch_assoc()):
                    $status_class = '';
                    if ($row['status'] == 'swapped') {
                        $status_class = 'swapped';
                    } elseif ($row['approved'] == 'yes') {
                        $status_class = 'approved';
                    } else {
                        $status_class = 'pending';
                    }
                ?>
                    <div class="book-card <?php echo $status_class; ?>">
                        <div class="book-header">
                            <span class="book-status status-<?php echo $status_class; ?>">
                                <?php
                                if ($row['status'] == 'swapped') {
                                    echo 'Swapped';
                                } elseif ($row['approved'] == 'yes') {
                                    echo 'Approved';
                                } else {
                                    echo 'Pending Review';
                                }
                                ?>
                            </span>
                            <h3 class="book-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p class="book-author">by <?php echo htmlspecialchars($row['author']); ?></p>

                            <span class="genre-tag"><?php echo htmlspecialchars($row['genre']); ?></span>
                        </div>

                        <div class="book-body">
                            <?php if (!empty($row['image'])): ?>
                                <div class="book-image">
                                    <img src="../uploads/book_images/<?php echo htmlspecialchars($row['image']); ?>"
                                        alt="<?php echo htmlspecialchars($row['title']); ?>">
                                </div>
                            <?php endif; ?>

                            <div class="book-meta">
                                <div class="meta-item">
                                    <span class="meta-label">Condition</span>
                                    <div class="condition-rating">
                                        <div class="stars">
                                            <?php
                                            $rating = intval($row['book_condition']);
                                            $full_stars = floor($rating / 2);
                                            for ($i = 0; $i < 5; $i++):
                                            ?>
                                                <i class="fas fa-star<?php echo $i < $full_stars ? '' : '-o'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="condition-text"><?php echo $rating; ?>/10</span>
                                    </div>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Added On</span>
                                    <span class="meta-value"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></span>
                                </div>
                            </div>

                            <?php if (!empty($row['description'])): ?>
                                <div style="margin-bottom: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border-left: 3px solid #4a6491;">
                                    <p style="color: #666; font-size: 0.9rem; line-height: 1.5;">
                                        <?php echo htmlspecialchars($row['description']); ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <div class="book-actions">
                                <?php if ($row['approved'] == 'no'): ?>
                                    <a href="?delete=<?php echo $row['book_id']; ?>"
                                        class="action-btn delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this book? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i>
                                        Delete
                                    </a>
                                <?php endif; ?>
                                <a href="../browse.php?book=<?php echo $row['book_id']; ?>" class="action-btn view-btn">
                                    <i class="fas fa-eye"></i>
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-books">
                <i class="fas fa-book-open"></i>
                <h3>No Books Added Yet</h3>
                <p>You haven't listed any books for swapping. Start by adding your first book!</p>
                <a href="add_book.php" class="action-btn view-btn" style="display: inline-flex; padding: 1rem 2rem;">
                    <i class="fas fa-plus-circle"></i>
                    Add Your First Book
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include "../includes/footer.php"; ?>

    <script>
        // Confirm deletion
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this book? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>