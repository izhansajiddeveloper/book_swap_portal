<?php
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Mark dispute as resolved
if (isset($_GET['resolve_id'])) {
    $dispute_id = intval($_GET['resolve_id']);
    $conn->query("UPDATE disputes SET status='resolved' WHERE dispute_id=$dispute_id");
    header("Location: disputes.php");
    exit;
}

// Fetch all disputes with user and book info - Fixed query
$result = $conn->query("
    SELECT d.dispute_id, d.reason, d.status, d.created_at,
           u.name AS user_name, u.email AS user_email,
           b.title AS book_title, b.author AS book_author,
           sr.swap_id
    FROM disputes d
    JOIN users u ON d.user_id = u.user_id
    JOIN swap_requests sr ON d.swap_id = sr.swap_id
    JOIN books b ON sr.book_id = b.book_id
    ORDER BY d.created_at DESC
");

$open_disputes = 0;
$resolved_disputes = 0;
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] == 'open') $open_disputes++;
        else $resolved_disputes++;
    }
    $result->data_seek(0); // Reset pointer
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disputes - Admin - Book Swap Portal</title>
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

        .open .stat-icon {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
        }

        .resolved .stat-icon {
            background: linear-gradient(135deg, #51cf66, #2f9e44);
        }

        .total .stat-icon {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
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

        /* Disputes Grid */
        .disputes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .dispute-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .dispute-card.open {
            border-left-color: #ff6b6b;
        }

        .dispute-card.resolved {
            border-left-color: #51cf66;
        }

        .dispute-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }

        .dispute-header {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
            padding: 1.5rem;
        }

        .dispute-id {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
        }

        .dispute-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .status-open {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }

        .status-resolved {
            background: rgba(81, 207, 102, 0.2);
            color: #2f9e44;
        }

        .dispute-body {
            padding: 1.5rem;
        }

        .dispute-info {
            margin-bottom: 1.5rem;
        }

        .info-section {
            margin-bottom: 1rem;
        }

        .info-section h4 {
            color: #2c3e50;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-section p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            padding-left: 1.8rem;
        }

        .user-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .user-name {
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 0.5rem;
        }

        .user-email {
            color: #666;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
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

        .resolve-btn {
            background: linear-gradient(135deg, #51cf66, #2f9e44);
            color: white;
        }

        .resolve-btn:hover {
            background: linear-gradient(135deg, #40a757, #218838);
            transform: translateY(-2px);
        }

        .contact-btn {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
        }

        .contact-btn:hover {
            background: linear-gradient(135deg, #3b5275, #2c3e50);
            transform: translateY(-2px);
        }

        /* No Disputes Message */
        .no-disputes {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            grid-column: 1 / -1;
        }

        .no-disputes i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1.5rem;
        }

        .no-disputes h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .no-disputes p {
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

            .disputes-grid {
                grid-template-columns: 1fr;
            }

            .stats-cards {
                grid-template-columns: 1fr;
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

        .dispute-card {
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
                <i class="fas fa-gavel"></i>
                Dispute Resolution
            </h1>
            <p class="admin-subtitle">Review and resolve conflicts between users</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-cards">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $open_disputes + $resolved_disputes; ?></h3>
                    <p>Total Disputes</p>
                </div>
            </div>

            <div class="stat-card open">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $open_disputes; ?></h3>
                    <p>Open Disputes</p>
                </div>
            </div>

            <div class="stat-card resolved">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $resolved_disputes; ?></h3>
                    <p>Resolved Disputes</p>
                </div>
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="disputes-grid">
                <?php while ($dispute = $result->fetch_assoc()): ?>
                    <div class="dispute-card <?php echo $dispute['status']; ?>">
                        <div class="dispute-header">
                            <div class="dispute-id">ID: <?php echo $dispute['dispute_id']; ?> | Swap ID: <?php echo $dispute['swap_id']; ?></div>
                            <span class="dispute-status status-<?php echo $dispute['status']; ?>">
                                <i class="fas fa-<?php echo $dispute['status'] == 'open' ? 'clock' : 'check-circle'; ?>"></i>
                                <?php echo ucfirst($dispute['status']); ?>
                            </span>
                            <div class="dispute-date">
                                <small><?php echo date('M j, Y, g:i A', strtotime($dispute['created_at'])); ?></small>
                            </div>
                        </div>

                        <div class="dispute-body">
                            <div class="dispute-info">
                                <div class="info-section">
                                    <h4><i class="fas fa-book"></i> Book Involved</h4>
                                    <p><?php echo htmlspecialchars($dispute['book_title']); ?> by <?php echo htmlspecialchars($dispute['book_author']); ?></p>
                                </div>

                                <div class="info-section">
                                    <h4><i class="fas fa-exclamation-circle"></i> Reason</h4>
                                    <p><?php echo htmlspecialchars($dispute['reason']); ?></p>
                                </div>

                                <div class="user-details">
                                    <div class="user-name">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($dispute['user_name']); ?>
                                    </div>
                                    <div class="user-email">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo htmlspecialchars($dispute['user_email']); ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($dispute['status'] == 'open'): ?>
                                <div class="action-buttons">
                                    <a href="?resolve_id=<?php echo $dispute['dispute_id']; ?>"
                                        class="action-btn resolve-btn"
                                        onclick="return confirm('Mark this dispute as resolved?')">
                                        <i class="fas fa-check"></i>
                                        Mark as Resolved
                                    </a>
                                    <a href="mailto:<?php echo $dispute['user_email']; ?>?subject=Dispute%20Resolution%20-%20BookSwap&body=Dear%20<?php echo urlencode($dispute['user_name']); ?>%2C%0A%0AWe%20are%20reviewing%20your%20dispute%20regarding%20%22<?php echo urlencode($dispute['book_title']); ?>%22.%0A%0A"
                                        class="action-btn contact-btn">
                                        <i class="fas fa-envelope"></i>
                                        Contact User
                                    </a>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; padding: 1rem; background: #d4edda; color: #155724; border-radius: 8px;">
                                    <i class="fas fa-check-circle"></i> This dispute has been resolved.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-disputes">
                <i class="fas fa-check-circle"></i>
                <h3>No Disputes Found</h3>
                <p>There are currently no disputes to resolve.</p>
                <a href="dashboard.php" class="back-dashboard">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>

</html>