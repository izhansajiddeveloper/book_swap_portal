<?php
session_start();
require_once "config/db.php";

// Get filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$genre = isset($_GET['genre']) ? $conn->real_escape_string($_GET['genre']) : '';
$location = isset($_GET['location']) ? $conn->real_escape_string($_GET['location']) : '';
$condition = isset($_GET['condition']) ? (int)$_GET['condition'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$sql = "SELECT b.book_id, b.title, b.author, b.genre, b.book_condition, b.image, b.created_at,
               u.name AS owner_name, u.location, u.user_id AS owner_id
        FROM books b
        JOIN users u ON b.user_id = u.user_id
        WHERE b.approved='yes' AND b.status='available'";

// Apply filters
if (!empty($search)) {
    $sql .= " AND (b.title LIKE '%$search%' OR b.author LIKE '%$search%' OR b.genre LIKE '%$search%')";
}
if (!empty($genre) && $genre != 'all') {
    $sql .= " AND b.genre = '$genre'";
}
if (!empty($location)) {
    $sql .= " AND u.location LIKE '%$location%'";
}
if ($condition > 0) {
    $sql .= " AND b.book_condition >= $condition";
}

// Apply sorting
switch ($sort) {
    case 'newest':
        $sql .= " ORDER BY b.created_at DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY b.created_at ASC";
        break;
    case 'condition_high':
        $sql .= " ORDER BY b.book_condition DESC";
        break;
    case 'condition_low':
        $sql .= " ORDER BY b.book_condition ASC";
        break;
    case 'title_asc':
        $sql .= " ORDER BY b.title ASC";
        break;
    case 'title_desc':
        $sql .= " ORDER BY b.title DESC";
        break;
    default:
        $sql .= " ORDER BY b.created_at DESC";
}

$result = $conn->query($sql);

// Get distinct genres for filter
$genres_sql = "SELECT DISTINCT genre FROM books WHERE approved='yes' ORDER BY genre";
$genres_result = $conn->query($genres_sql);

// Get distinct locations for filter
$locations_sql = "SELECT DISTINCT location FROM users ORDER BY location";
$locations_result = $conn->query($locations_sql);

// Count total books
$count_sql = "SELECT COUNT(*) as total FROM books WHERE approved='yes' AND status='available'";
$count_result = $conn->query($count_sql);
$total_books = $count_result->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Browse Books | Book Swap Portal</title>
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

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.2);
        }

        .hero-section h1 {
            font-size: 2.8rem;
            margin-bottom: 0.5rem;
        }

        .hero-section p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .filter-header h2 {
            color: var(--dark);
            font-size: 1.8rem;
        }

        .book-count {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            color: var(--dark);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-group label i {
            color: var(--primary);
        }

        .filter-input {
            padding: 0.8rem 1rem;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .filter-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            flex-wrap: wrap;
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
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: var(--gray-light);
            color: var(--dark);
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(0, 0, 0, 0.1);
        }

        /* Sort Section */
        .sort-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .sort-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .sort-label {
            color: var(--dark);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sort-select {
            padding: 0.6rem 1rem;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-size: 0.95rem;
            background: white;
            cursor: pointer;
            outline: none;
        }

        .sort-select:focus {
            border-color: var(--primary);
        }

        /* Books Grid */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
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

        .book-author {
            color: var(--gray);
            font-style: italic;
            margin-bottom: 1rem;
            font-size: 0.95rem;
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

        .condition-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-left: auto;
        }

        .condition-rating .stars {
            color: var(--warning);
        }

        .book-footer {
            padding: 1.5rem;
            background: var(--light);
            border-top: 1px solid var(--gray-light);
        }

        .owner-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
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

        .book-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-small {
            padding: 0.6rem 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-size: 0.85rem;
            flex: 1;
        }

        .btn-view {
            background: var(--info);
            color: white;
        }

        .btn-view:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .btn-swap {
            background: var(--success);
            color: white;
        }

        .btn-swap:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-swap:disabled {
            background: var(--gray-light);
            color: var(--gray);
            cursor: not-allowed;
            transform: none !important;
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            grid-column: 1 / -1;
        }

        .no-results i {
            font-size: 4rem;
            color: var(--gray-light);
            margin-bottom: 1.5rem;
        }

        .no-results h3 {
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .no-results p {
            color: var(--gray);
            max-width: 500px;
            margin: 0 auto 2rem;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 3rem;
            flex-wrap: wrap;
        }

        .page-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            color: var(--dark);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .page-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        @media (max-width: 768px) {
            .books-grid {
                grid-template-columns: 1fr;
            }

            .hero-section {
                padding: 2rem 1rem;
            }

            .hero-section h1 {
                font-size: 2rem;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                justify-content: center;
            }

            .book-actions {
                flex-direction: column;
            }

            .sort-section {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
        }
    </style>
</head>

<body>
    <?php include "includes/navbar.php"; ?>

    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1><i class="fas fa-search"></i> Browse Books</h1>
            <p>Discover thousands of books available for swapping from readers across the country</p>
        </div>

        <!-- Filter Section -->
        <form method="GET" action="browse.php">
            <div class="filter-section">
                <div class="filter-header">
                    <h2>Filter Books</h2>
                    <div class="book-count">
                        <i class="fas fa-book"></i> <?php echo $total_books; ?> Books Available
                    </div>
                </div>

                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="search"><i class="fas fa-search"></i> Search Books</label>
                        <input type="text" id="search" name="search" class="filter-input"
                            placeholder="Search by title, author, or genre..."
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="filter-group">
                        <label for="genre"><i class="fas fa-tags"></i> Genre</label>
                        <select id="genre" name="genre" class="filter-input">
                            <option value="all">All Genres</option>
                            <?php while ($genre_row = $genres_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($genre_row['genre']); ?>"
                                    <?php echo ($genre == $genre_row['genre']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($genre_row['genre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="location"><i class="fas fa-map-marker-alt"></i> Location</label>
                        <select id="location" name="location" class="filter-input">
                            <option value="">All Locations</option>
                            <?php while ($location_row = $locations_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($location_row['location']); ?>"
                                    <?php echo ($location == $location_row['location']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location_row['location']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="condition"><i class="fas fa-star"></i> Minimum Condition</label>
                        <input type="range" id="condition" name="condition" class="filter-input"
                            min="1" max="10" value="<?php echo $condition ? $condition : 1; ?>">
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--gray);">
                            <span>1 (Poor)</span>
                            <span id="condition-value"><?php echo $condition ? $condition : 1; ?>/10</span>
                            <span>10 (Excellent)</span>
                        </div>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="browse.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Clear Filters
                    </a>
                </div>
            </div>
        </form>

        <!-- Sort Section -->
        <div class="sort-section">
            <div class="sort-group">
                <span class="sort-label"><i class="fas fa-sort"></i> Sort by:</span>
                <form method="GET" action="browse.php" style="display: inline;">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="genre" value="<?php echo htmlspecialchars($genre); ?>">
                    <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                    <input type="hidden" name="condition" value="<?php echo $condition; ?>">
                    <select name="sort" class="sort-select" onchange="this.form.submit()">
                        <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo ($sort == 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="condition_high" <?php echo ($sort == 'condition_high') ? 'selected' : ''; ?>>Best Condition</option>
                        <option value="condition_low" <?php echo ($sort == 'condition_low') ? 'selected' : ''; ?>>Worst Condition</option>
                        <option value="title_asc" <?php echo ($sort == 'title_asc') ? 'selected' : ''; ?>>Title A-Z</option>
                        <option value="title_desc" <?php echo ($sort == 'title_desc') ? 'selected' : ''; ?>>Title Z-A</option>
                    </select>
                </form>
            </div>

            <div style="color: var(--gray); font-size: 0.95rem;">
                <i class="fas fa-info-circle"></i> Showing <?php echo $result ? $result->num_rows : 0; ?> books
            </div>
        </div>

        <!-- Books Grid -->
        <div class="books-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($book = $result->fetch_assoc()): ?>
                    <div class="book-card">
                        <?php if (!empty($book['image'])): ?>
                            <img src="uploads/book_images/<?php echo htmlspecialchars($book['image']); ?>"
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
                            <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>

                            <div class="book-meta">
                                <div class="meta-item">
                                    <i class="fas fa-tags"></i>
                                    <span><?php echo htmlspecialchars($book['genre']); ?></span>
                                    <span class="genre-badge"><?php echo htmlspecialchars($book['genre']); ?></span>
                                </div>

                                <div class="meta-item">
                                    <i class="fas fa-star"></i>
                                    <span>Condition: <?php echo $book['book_condition']; ?>/10</span>
                                    <div class="condition-rating">
                                        <?php
                                        $condition_stars = ceil($book['book_condition'] / 2);
                                        for ($i = 1; $i <= 5; $i++):
                                        ?>
                                            <i class="fas fa-star<?php echo ($i <= $condition_stars) ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="meta-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Added: <?php echo date("M d, Y", strtotime($book['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="book-footer">
                            <div class="owner-info">
                                <div class="owner-avatar">
                                    <?php echo strtoupper(substr($book['owner_name'], 0, 1)); ?>
                                </div>
                                <div class="owner-details">
                                    <h4><?php echo htmlspecialchars($book['owner_name']); ?></h4>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($book['location']); ?></p>
                                </div>
                            </div>

                            <div class="book-actions">
                                <a href="book_details.php?id=<?php echo $book['book_id']; ?>" class="btn-small btn-view">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $book['owner_id']): ?>
                                    <a href="swap/request_swap.php?book_id=<?php echo $book['book_id']; ?>" class="btn-small btn-swap">
                                        <i class="fas fa-exchange-alt"></i> Request Swap
                                    </a>
                                <?php elseif (!isset($_SESSION['user_id'])): ?>
                                    <a href="auth/login.php" class="btn-small btn-swap">
                                        <i class="fas fa-sign-in-alt"></i> Login to Swap
                                    </a>
                                <?php else: ?>
                                    <button class="btn-small btn-swap" disabled>
                                        <i class="fas fa-book"></i> Your Book
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-book-open"></i>
                    <h3>No Books Found</h3>
                    <p>Try adjusting your search filters or browse all available books</p>
                    <a href="browse.php" class="btn btn-primary" style="margin-top: 1.5rem;">
                        <i class="fas fa-redo"></i> Reset Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="pagination">
                <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($genre) ? '&genre=' . urlencode($genre) : ''; ?>"
                    class="page-btn">«</a>
                <span class="page-btn active">1</span>
                <a href="?page=2<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($genre) ? '&genre=' . urlencode($genre) : ''; ?>"
                    class="page-btn">2</a>
                <a href="?page=3<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($genre) ? '&genre=' . urlencode($genre) : ''; ?>"
                    class="page-btn">3</a>
                <a href="?page=4<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($genre) ? '&genre=' . urlencode($genre) : ''; ?>"
                    class="page-btn">4</a>
                <a href="?page=5<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($genre) ? '&genre=' . urlencode($genre) : ''; ?>"
                    class="page-btn">5</a>
                <a href="?page=2<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($genre) ? '&genre=' . urlencode($genre) : ''; ?>"
                    class="page-btn">»</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include "includes/footer.php"; ?>

    <script>
        // Update condition value display
        const conditionSlider = document.getElementById('condition');
        const conditionValue = document.getElementById('condition-value');

        conditionSlider.addEventListener('input', function() {
            conditionValue.textContent = this.value + '/10';
        });

        // Initialize tooltips
        document.querySelectorAll('.condition-rating i').forEach(star => {
            star.title = 'Book Condition Rating';
        });
    </script>
</body>

</html>