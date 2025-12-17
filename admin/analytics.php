<?php
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Fetch analytics data
// User growth (last 6 months)
$user_growth = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");

// Book categories distribution
$book_genres = $conn->query("
    SELECT genre, COUNT(*) as count
    FROM books
    WHERE approved = 'yes'
    GROUP BY genre
    ORDER BY count DESC
    LIMIT 10
");

// Swap statistics
$swap_stats = $conn->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM swap_requests
    GROUP BY status
");

// Active users (users with swaps in last 30 days)
$active_users = $conn->query("
    SELECT COUNT(DISTINCT u.user_id) as count
    FROM users u
    LEFT JOIN swap_requests sr ON u.user_id = sr.requester_id
    WHERE sr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch_assoc()['count'];

// Top users by swaps
$top_users = $conn->query("
    SELECT 
        u.name,
        u.email,
        COUNT(sr.swap_id) as swap_count,
        u.credits
    FROM users u
    LEFT JOIN swap_requests sr ON u.user_id = sr.requester_id
    GROUP BY u.user_id
    ORDER BY swap_count DESC
    LIMIT 10
");

// Monthly swaps
$monthly_swaps = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM swap_requests
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");

// Get today's stats
$today_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
$today_swaps = $conn->query("SELECT COUNT(*) as count FROM swap_requests WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
$today_books = $conn->query("SELECT COUNT(*) as count FROM books WHERE DATE(created_at) = CURDATE() AND approved = 'yes'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Admin - Book Swap Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .date-range {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            margin-bottom: 2rem;
        }

        /* Today's Stats */
        .today-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .today-stat {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
        }

        .today-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .today-stat i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #4a6491;
        }

        .today-stat h3 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 800;
        }

        .today-stat p {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f3f5;
        }

        .chart-title {
            font-size: 1.3rem;
            color: #2c3e50;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-title i {
            color: #ff9e6d;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Top Users Table */
        .top-users {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            margin-bottom: 3rem;
        }

        .top-users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f3f5;
        }

        .top-users-title {
            font-size: 1.3rem;
            color: #2c3e50;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .top-users-title i {
            color: #ff9e6d;
        }

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

        .user-rank {
            font-weight: bold;
            color: #4a6491;
            font-size: 1.2rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .user-details h4 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0.2rem;
        }

        .user-details p {
            color: #666;
            font-size: 0.9rem;
        }

        .swap-count {
            background: linear-gradient(135deg, rgba(255, 209, 102, 0.1), rgba(255, 158, 109, 0.1));
            color: #2c3e50;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .credits-badge {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Export Section */
        .export-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            margin-bottom: 3rem;
        }

        .export-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f3f5;
        }

        .export-title {
            font-size: 1.3rem;
            color: #2c3e50;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .export-title i {
            color: #ff9e6d;
        }

        .export-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .export-option {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .export-option:hover {
            background: #e9ecef;
            transform: translateY(-3px);
        }

        .export-option i {
            font-size: 2.5rem;
            color: #4a6491;
            margin-bottom: 1rem;
        }

        .export-option h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .export-option p {
            color: #666;
            font-size: 0.9rem;
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

        .today-stat,
        .chart-card,
        .top-users,
        .export-section {
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
                <i class="fas fa-chart-bar"></i>
                Analytics Dashboard
            </h1>
            <p class="admin-subtitle">Comprehensive insights and statistics about your BookSwap platform</p>
            <div class="date-range">
                <i class="fas fa-calendar-alt"></i>
                Last 6 Months Analytics
            </div>
        </div>

        <!-- Today's Stats -->
        <div class="today-stats">
            <div class="today-stat">
                <i class="fas fa-user-plus"></i>
                <h3><?php echo $today_users; ?></h3>
                <p>New Users Today</p>
            </div>
            <div class="today-stat">
                <i class="fas fa-exchange-alt"></i>
                <h3><?php echo $today_swaps; ?></h3>
                <p>Swaps Today</p>
            </div>
            <div class="today-stat">
                <i class="fas fa-book"></i>
                <h3><?php echo $today_books; ?></h3>
                <p>Books Added Today</p>
            </div>
            <div class="today-stat">
                <i class="fas fa-users"></i>
                <h3><?php echo $active_users; ?></h3>
                <p>Active Users (30 days)</p>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- User Growth Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        User Growth
                    </h3>
                </div>
                <div class="chart-container">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>

            <!-- Swap Status Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-exchange-alt"></i>
                        Swap Status Distribution
                    </h3>
                </div>
                <div class="chart-container">
                    <canvas id="swapStatusChart"></canvas>
                </div>
            </div>

            <!-- Book Genres Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-book"></i>
                        Top Book Genres
                    </h3>
                </div>
                <div class="chart-container">
                    <canvas id="bookGenresChart"></canvas>
                </div>
            </div>

            <!-- Monthly Swaps Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-calendar"></i>
                        Monthly Swaps
                    </h3>
                </div>
                <div class="chart-container">
                    <canvas id="monthlySwapsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Users -->
        <div class="top-users">
            <div class="top-users-header">
                <h3 class="top-users-title">
                    <i class="fas fa-trophy"></i>
                    Top Users by Swaps
                </h3>
            </div>

            <?php if ($top_users->num_rows > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th width="50">Rank</th>
                            <th>User</th>
                            <th>Total Swaps</th>
                            <th>Credits</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rank = 1;
                        while ($user = $top_users->fetch_assoc()):
                        ?>
                            <tr>
                                <td>
                                    <span class="user-rank">#<?php echo $rank; ?></span>
                                </td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div class="user-details">
                                            <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="swap-count">
                                        <i class="fas fa-exchange-alt"></i>
                                        <?php echo $user['swap_count']; ?> swaps
                                    </span>
                                </td>
                                <td>
                                    <span class="credits-badge">
                                        <i class="fas fa-coins"></i>
                                        <?php echo $user['credits']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php $rank++; ?>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: #888;">
                    <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <p>No user data available</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Export Section -->
        <div class="export-section">
            <div class="export-header">
                <h3 class="export-title">
                    <i class="fas fa-download"></i>
                    Export Data
                </h3>
            </div>
            <div class="export-options">
                <div class="export-option" onclick="exportData('users')">
                    <i class="fas fa-users"></i>
                    <h4>Export Users</h4>
                    <p>Download all user data as CSV</p>
                </div>
                <div class="export-option" onclick="exportData('books')">
                    <i class="fas fa-book"></i>
                    <h4>Export Books</h4>
                    <p>Download all book listings as CSV</p>
                </div>
                <div class="export-option" onclick="exportData('swaps')">
                    <i class="fas fa-exchange-alt"></i>
                    <h4>Export Swaps</h4>
                    <p>Download all swap history as CSV</p>
                </div>
                <div class="export-option" onclick="exportData('analytics')">
                    <i class="fas fa-chart-pie"></i>
                    <h4>Export Analytics</h4>
                    <p>Download analytics report as PDF</p>
                </div>
            </div>
        </div>

        <a href="dashboard.php" class="back-dashboard">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Prepare data for charts
        const userGrowthData = {
            months: [],
            counts: []
        };

        <?php
        if ($user_growth->num_rows > 0) {
            while ($row = $user_growth->fetch_assoc()) {
                echo "userGrowthData.months.push('" . $row['month'] . "');";
                echo "userGrowthData.counts.push(" . $row['count'] . ");";
            }
        }
        ?>

        const swapStatusData = {
            labels: [],
            counts: []
        };

        <?php
        if ($swap_stats->num_rows > 0) {
            while ($row = $swap_stats->fetch_assoc()) {
                echo "swapStatusData.labels.push('" . ucfirst($row['status']) . "');";
                echo "swapStatusData.counts.push(" . $row['count'] . ");";
            }
        }
        ?>

        const bookGenresData = {
            labels: [],
            counts: []
        };

        <?php
        if ($book_genres->num_rows > 0) {
            while ($row = $book_genres->fetch_assoc()) {
                echo "bookGenresData.labels.push('" . $row['genre'] . "');";
                echo "bookGenresData.counts.push(" . $row['count'] . ");";
            }
        }
        ?>

        const monthlySwapsData = {
            months: [],
            counts: []
        };

        <?php
        if ($monthly_swaps->num_rows > 0) {
            while ($row = $monthly_swaps->fetch_assoc()) {
                echo "monthlySwapsData.months.push('" . $row['month'] . "');";
                echo "monthlySwapsData.counts.push(" . $row['count'] . ");";
            }
        }
        ?>

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // User Growth Chart
            const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
            new Chart(userGrowthCtx, {
                type: 'line',
                data: {
                    labels: userGrowthData.months,
                    datasets: [{
                        label: 'New Users',
                        data: userGrowthData.counts,
                        borderColor: '#4a6491',
                        backgroundColor: 'rgba(74, 100, 145, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Swap Status Chart
            const swapStatusCtx = document.getElementById('swapStatusChart').getContext('2d');
            new Chart(swapStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: swapStatusData.labels,
                    datasets: [{
                        data: swapStatusData.counts,
                        backgroundColor: [
                            '#ffd166',
                            '#51cf66',
                            '#ff6b6b',
                            '#4a6491'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });

            // Book Genres Chart
            const bookGenresCtx = document.getElementById('bookGenresChart').getContext('2d');
            new Chart(bookGenresCtx, {
                type: 'bar',
                data: {
                    labels: bookGenresData.labels,
                    datasets: [{
                        label: 'Number of Books',
                        data: bookGenresData.counts,
                        backgroundColor: '#ff9e6d',
                        borderColor: '#ff9e6d',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Monthly Swaps Chart
            const monthlySwapsCtx = document.getElementById('monthlySwapsChart').getContext('2d');
            new Chart(monthlySwapsCtx, {
                type: 'bar',
                data: {
                    labels: monthlySwapsData.months,
                    datasets: [{
                        label: 'Monthly Swaps',
                        data: monthlySwapsData.counts,
                        backgroundColor: '#51cf66',
                        borderColor: '#2f9e44',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });

        // Export data function
        function exportData(type) {
            switch (type) {
                case 'users':
                    alert('Exporting users data...');
                    // In production: window.location.href = 'export_users.php';
                    break;
                case 'books':
                    alert('Exporting books data...');
                    // In production: window.location.href = 'export_books.php';
                    break;
                case 'swaps':
                    alert('Exporting swaps data...');
                    // In production: window.location.href = 'export_swaps.php';
                    break;
                case 'analytics':
                    alert('Exporting analytics report...');
                    // In production: window.location.href = 'export_analytics.php';
                    break;
            }
        }
    </script>
</body>

</html>