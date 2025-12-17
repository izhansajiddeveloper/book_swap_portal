<?php
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Fetch statistics
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$total_books = $conn->query("SELECT COUNT(*) AS total FROM books")->fetch_assoc()['total'];
$pending_books = $conn->query("SELECT COUNT(*) AS total FROM books WHERE approved='no'")->fetch_assoc()['total'];
$open_disputes = $conn->query("SELECT COUNT(*) AS total FROM disputes WHERE status='open'")->fetch_assoc()['total'];

// Fetch recent swaps (last 7 days)
$recent_swaps = $conn->query("SELECT COUNT(*) AS total FROM swap_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['total'];

// Fetch new users (last 7 days)
$new_users = $conn->query("SELECT COUNT(*) AS total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['total'];

// Fetch total swaps
$total_swaps = $conn->query("SELECT COUNT(*) AS total FROM swap_requests")->fetch_assoc()['total'];

// Fetch recent activities from swap requests and user registrations
$recent_activities = $conn->query("
    (SELECT 
        u.name,
        'Book Swap' as activity_type,
        sr.created_at as activity_date,
        CONCAT('Requested swap for book #', sr.book_id) as description
    FROM swap_requests sr
    JOIN users u ON sr.requester_id = u.user_id
    ORDER BY sr.created_at DESC
    LIMIT 5)
    UNION
    (SELECT 
        u.name,
        'New Registration' as activity_type,
        u.created_at as activity_date,
        'New user joined' as description
    FROM users u
    ORDER BY u.created_at DESC
    LIMIT 5)
    ORDER BY activity_date DESC
    LIMIT 5
");

// Fetch recent registrations
$recent_registrations = $conn->query("
    SELECT user_id, name, email, location, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Fetch recent swaps with details
$recent_swaps_list = $conn->query("
    SELECT sr.swap_id, u1.name as requester, u2.name as book_owner, b.title, sr.status, sr.created_at
    FROM swap_requests sr
    JOIN users u1 ON sr.requester_id = u1.user_id
    JOIN books b ON sr.book_id = b.book_id
    JOIN users u2 ON b.user_id = u2.user_id
    ORDER BY sr.created_at DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Book Swap Portal</title>
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

        /* Header Section */
        .admin-header {
            margin-bottom: 2.5rem;
        }

        .admin-title {
            font-size: 2.5rem;
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
            padding: 15px;
            border-radius: 15px;
        }

        .admin-subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        .current-date {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.8rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.users::before {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
        }

        .stat-card.books::before {
            background: linear-gradient(135deg, #51cf66, #2f9e44);
        }

        .stat-card.pending::before {
            background: linear-gradient(135deg, #ffd166, #ff9e6d);
        }

        .stat-card.disputes::before {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
        }

        .stat-card.swaps::before {
            background: linear-gradient(135deg, #748ffc, #3b5bdb);
        }

        .stat-card.new-users::before {
            background: linear-gradient(135deg, #63e6be, #20c997);
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

        .users .stat-icon {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
        }

        .books .stat-icon {
            background: linear-gradient(135deg, #51cf66, #2f9e44);
        }

        .pending .stat-icon {
            background: linear-gradient(135deg, #ffd166, #ff9e6d);
        }

        .disputes .stat-icon {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
        }

        .swaps .stat-icon {
            background: linear-gradient(135deg, #748ffc, #3b5bdb);
        }

        .new-users .stat-icon {
            background: linear-gradient(135deg, #63e6be, #20c997);
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

        .stat-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #4a6491;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.5rem 1rem;
            background: rgba(74, 100, 145, 0.1);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .stat-link:hover {
            background: rgba(74, 100, 145, 0.2);
            transform: translateX(5px);
        }

        /* Dashboard Layout */
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

        .activity-icon.register {
            background: linear-gradient(135deg, #51cf66, #2f9e44);
        }

        .activity-info {
            flex: 1;
        }

        .activity-user {
            font-weight: 600;
            color: #2c3e50;
        }

        .activity-action {
            color: #666;
            font-size: 0.95rem;
        }

        .activity-time {
            color: #888;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Users Table */
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            text-align: left;
            padding: 1rem;
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
        }

        .users-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .users-table tr:hover {
            background: #f8f9fa;
        }

        .user-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .user-email {
            color: #666;
            font-size: 0.9rem;
        }

        .user-location {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255, 209, 102, 0.1);
            color: #2c3e50;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .user-date {
            color: #888;
            font-size: 0.85rem;
        }

        /* Swaps Table */
        .swaps-table {
            width: 100%;
            border-collapse: collapse;
        }

        .swaps-table th {
            text-align: left;
            padding: 1rem;
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
        }

        .swaps-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .swap-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-pending {
            background: rgba(255, 209, 102, 0.1);
            color: #e67700;
        }

        .status-accepted {
            background: rgba(81, 207, 102, 0.1);
            color: #2f9e44;
        }

        .status-rejected {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
        }

        .status-completed {
            background: rgba(116, 143, 252, 0.1);
            color: #3b5bdb;
        }

        /* Quick Actions */
        .quick-actions {
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
            margin-bottom: 1rem;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }

            .admin-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-content {
                grid-template-columns: 1fr;
            }

            .data-card {
                padding: 1.5rem;
            }

            .quick-actions {
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
    <?php include '../includes/navbar.php'; ?>

    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <h1 class="admin-title">
                <i class="fas fa-tachometer-alt"></i>
                Admin Dashboard
            </h1>
            <p class="admin-subtitle">Manage your BookSwap platform efficiently</p>
            <div class="current-date">
                <i class="fas fa-calendar-alt"></i>
                <?php echo date('l, F j, Y'); ?>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <!-- Total Users -->
            <a href="manage_users.php" class="stat-card users">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Registered Users</div>
                    <div class="stat-link">
                        <i class="fas fa-user-cog"></i>
                        Manage Users
                    </div>
                </div>
            </a>

            <!-- Total Books -->
            <a href="approve_books.php" class="stat-card books">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_books; ?></div>
                    <div class="stat-label">Books in Library</div>
                    <div class="stat-link">
                        <i class="fas fa-check-circle"></i>
                        Manage Books
                    </div>
                </div>
            </a>

            <!-- Pending Approvals -->
            <a href="approve_books.php" class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $pending_books; ?></div>
                    <div class="stat-label">Pending Book Approvals</div>
                    <div class="stat-link">
                        <i class="fas fa-eye"></i>
                        Review Books
                    </div>
                </div>
            </a>

            <!-- Open Disputes -->
            <a href="disputes.php" class="stat-card disputes">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $open_disputes; ?></div>
                    <div class="stat-label">Open Disputes</div>
                    <div class="stat-link">
                        <i class="fas fa-gavel"></i>
                        Resolve Disputes
                    </div>
                </div>
            </a>

            <!-- Recent Swaps -->
            <a href="analytics.php" class="stat-card swaps">
                <div class="stat-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $recent_swaps; ?></div>
                    <div class="stat-label">Swaps This Week</div>
                    <div class="stat-link">
                        <i class="fas fa-chart-line"></i>
                        View Analytics
                    </div>
                </div>
            </a>

            <!-- New Users -->
            <a href="manage_users.php" class="stat-card new-users">
                <div class="stat-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $new_users; ?></div>
                    <div class="stat-label">New Users This Week</div>
                    <div class="stat-link">
                        <i class="fas fa-chart-pie"></i>
                        View Insights
                    </div>
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
                    <a href="analytics.php" class="view-all">
                        View All
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <?php if($recent_activities && $recent_activities->num_rows > 0): ?>
                    <ul class="activity-list">
                        <?php while($activity = $recent_activities->fetch_assoc()): 
                            $icon_class = $activity['activity_type'] == 'Book Swap' ? 'swap' : 'register';
                            $icon = $activity['activity_type'] == 'Book Swap' ? 'exchange-alt' : 'user-plus';
                        ?>
                            <li class="activity-item">
                                <div class="activity-icon <?php echo $icon_class; ?>">
                                    <i class="fas fa-<?php echo $icon; ?>"></i>
                                </div>
                                <div class="activity-info">
                                    <div class="activity-user"><?php echo htmlspecialchars($activity['name']); ?></div>
                                    <div class="activity-action"><?php echo htmlspecialchars($activity['description']); ?></div>
                                </div>
                                <div class="activity-time">
                                    <?php 
                                        $time_ago = strtotime($activity['activity_date']);
                                        $now = time();
                                        $diff = $now - $time_ago;
                                        
                                        if($diff < 60) echo 'Just now';
                                        elseif($diff < 3600) echo floor($diff/60) . ' min ago';
                                        elseif($diff < 86400) echo floor($diff/3600) . ' hours ago';
                                        else echo date('M j', $time_ago);
                                    ?>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-history"></i>
                        <p>No recent activities found</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Swaps -->
            <div class="data-card">
                <div class="data-card-header">
                    <h3 class="data-card-title">
                        <i class="fas fa-exchange-alt"></i>
                        Recent Swaps
                    </h3>
                    <a href="analytics.php" class="view-all">
                        View All
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <?php if($recent_swaps_list && $recent_swaps_list->num_rows > 0): ?>
                    <table class="swaps-table">
                        <thead>
                            <tr>
                                <th>Swap ID</th>
                                <th>Requester</th>
                                <th>Book</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($swap = $recent_swaps_list->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $swap['swap_id']; ?></td>
                                    <td><?php echo htmlspecialchars($swap['requester']); ?></td>
                                    <td><?php echo htmlspecialchars($swap['title']); ?></td>
                                    <td>
                                        <span class="swap-status status-<?php echo $swap['status']; ?>">
                                            <i class="fas fa-circle"></i>
                                            <?php echo ucfirst($swap['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-exchange-alt"></i>
                        <p>No recent swaps found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="approve_books.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="action-title">Approve Books</h3>
                <p class="action-desc">Review and approve pending book listings from users</p>
            </a>

            <a href="manage_users.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <h3 class="action-title">Manage Users</h3>
                <p class="action-desc">View, edit, or remove user accounts and manage permissions</p>
            </a>

            <a href="disputes.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-gavel"></i>
                </div>
                <h3 class="action-title">Dispute Resolution</h3>
                <p class="action-desc">Resolve conflicts between users and review swap issues</p>
            </a>

            <a href="analytics.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3 class="action-title">View Analytics</h3>
                <p class="action-desc">Access detailed platform statistics and growth metrics</p>
            </a>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Animate elements on scroll
        document.addEventListener('DOMContentLoaded', function() {
            // Animate counter numbers
            const counters = document.querySelectorAll('.stat-number');
            const speed = 200;
            
            counters.forEach(counter => {
                const updateCount = () => {
                    const target = +counter.textContent.replace(/,/g, '');
                    const count = +counter.textContent.replace(/,/g, '');
                    const increment = target / speed;
                    
                    if (count < target) {
                        counter.textContent = Math.ceil(count + increment).toLocaleString();
                        setTimeout(updateCount, 1);
                    } else {
                        counter.textContent = target.toLocaleString();
                    }
                };
                
                updateCount();
            });

            // Add click animation to cards
            document.querySelectorAll('.stat-card, .action-card').forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });
    </script>
</body>
</html>