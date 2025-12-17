<?php
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Delete a user
if (isset($_GET['delete_id'])) {
    $user_id = intval($_GET['delete_id']);
    // Prevent admin from deleting themselves
    if ($user_id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE user_id=$user_id");
        header("Location: manage_users.php");
        exit;
    }
}

// Fetch all users
$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

// Calculate stats
$total_users = $result->num_rows;
$total_admins = 0;
$total_regular = 0;
$total_credits = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['role'] == 'admin') $total_admins++;
        else $total_regular++;
        $total_credits += $row['credits'];
    }
    $result->data_seek(0); // Reset pointer
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin - Book Swap Portal</title>
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

        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .users .stat-icon {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
        }

        .admins .stat-icon {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
        }

        .regular .stat-icon {
            background: linear-gradient(135deg, #51cf66, #2f9e44);
        }

        .credits .stat-icon {
            background: linear-gradient(135deg, #ffd166, #ff9e6d);
        }

        .stat-content h3 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 0.2rem;
            font-weight: 800;
        }

        .stat-content p {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Search and Filters */
        .filters-bar {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .search-box {
            flex: 1;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 3rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #4a6491;
            box-shadow: 0 0 0 3px rgba(74, 100, 145, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .filter-btn {
            padding: 0.6rem 1rem;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .filter-btn.active {
            background: #4a6491;
            color: white;
            border-color: #4a6491;
        }

        .filter-btn:hover {
            border-color: #4a6491;
        }

        /* Users Table */
        .users-table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            margin-bottom: 3rem;
        }

        .table-header {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-actions {
            display: flex;
            gap: 1rem;
        }

        .export-btn {
            padding: 0.6rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .export-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            padding: 1.2rem 1.5rem;
            text-align: left;
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
            font-size: 0.95rem;
        }

        .users-table td {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .users-table tr:hover {
            background: #f8f9fa;
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
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

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .role-admin {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
        }

        .role-user {
            background: rgba(81, 207, 102, 0.1);
            color: #2f9e44;
        }

        .credits-badge {
            background: linear-gradient(135deg, rgba(255, 209, 102, 0.1), rgba(255, 158, 109, 0.1));
            color: #2c3e50;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .location-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem 0.8rem;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .delete-btn {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
        }

        .delete-btn:hover {
            background: linear-gradient(135deg, #e85959, #ff6b6b);
            transform: translateY(-2px);
        }

        .disable-btn {
            background: #6c757d;
            color: white;
        }

        .disable-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* No Users Message */
        .no-users {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .no-users i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1.5rem;
        }

        .no-users h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .no-users p {
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

            .filters-bar {
                flex-direction: column;
            }

            .users-table {
                display: block;
                overflow-x: auto;
            }

            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 0 0 16px 16px;
        }

        .page-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .page-btn.active {
            background: #4a6491;
            color: white;
            border-color: #4a6491;
        }

        .page-btn:hover:not(.active) {
            border-color: #4a6491;
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <h1 class="admin-title">
                <i class="fas fa-user-cog"></i>
                Manage Users
            </h1>
            <p class="admin-subtitle">View, manage, and monitor all user accounts</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-cards">
            <div class="stat-card users">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="stat-card admins">
                <div class="stat-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_admins; ?></h3>
                    <p>Administrators</p>
                </div>
            </div>

            <div class="stat-card regular">
                <div class="stat-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_regular; ?></h3>
                    <p>Regular Users</p>
                </div>
            </div>

            <div class="stat-card credits">
                <div class="stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_credits; ?></h3>
                    <p>Total Credits</p>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="filters-bar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search users by name, email, or location...">
            </div>
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">
                    <i class="fas fa-users"></i> All Users
                </button>
                <button class="filter-btn" data-filter="admin">
                    <i class="fas fa-crown"></i> Admins
                </button>
                <button class="filter-btn" data-filter="user">
                    <i class="fas fa-user"></i> Regular
                </button>
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="users-table-container">
                <div class="table-header">
                    <h3>
                        <i class="fas fa-list"></i>
                        User List (<?php echo $total_users; ?> users)
                    </h3>
                    <div class="table-actions">
                        <button class="export-btn" onclick="exportUsers()">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </div>
                </div>

                <table class="users-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Location</th>
                            <th>Credits</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $result->fetch_assoc()): ?>
                            <tr data-role="<?php echo $user['role']; ?>">
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
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <i class="fas fa-<?php echo $user['role'] == 'admin' ? 'crown' : 'user'; ?>"></i>
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="location-tag">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($user['location']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="credits-badge">
                                        <i class="fas fa-coins"></i>
                                        <?php echo $user['credits']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                            <a href="?delete_id=<?php echo $user['user_id']; ?>"
                                                class="action-btn delete-btn"
                                                onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                                Delete
                                            </a>
                                            <button class="action-btn disable-btn" onclick="disableUser(<?php echo $user['user_id']; ?>)">
                                                <i class="fas fa-ban"></i>
                                                Disable
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 0.9rem;">Current User</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-users">
                <i class="fas fa-users-slash"></i>
                <h3>No Users Found</h3>
                <p>There are currently no users registered in the system.</p>
                <a href="dashboard.php" class="back-dashboard">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const filter = this.dataset.filter;
                const rows = document.querySelectorAll('#usersTable tbody tr');

                rows.forEach(row => {
                    if (filter === 'all') {
                        row.style.display = '';
                    } else {
                        row.style.display = row.dataset.role === filter ? '' : 'none';
                    }
                });
            });
        });

        // Export users function
        function exportUsers() {
            alert('Export feature would generate a CSV file with all user data.');
            // In a real application, this would trigger a server-side CSV generation
        }

        // Disable user function
        function disableUser(userId) {
            if (confirm('Disable this user account? The user will not be able to login until re-enabled.')) {
                alert('User disable functionality would be implemented here. User ID: ' + userId);
                // In a real application, this would make an AJAX call to disable the user
            }
        }

        // Confirm delete
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>