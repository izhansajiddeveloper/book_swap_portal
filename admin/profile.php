<?php

require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$message = "";
$messageType = "";

// Fetch admin info
$adminResult = $conn->query("SELECT * FROM users WHERE user_id=$admin_id");
$admin = $adminResult->fetch_assoc();

// Handle Edit Profile
if (isset($_POST['update_profile'])) {
    $name = ($_POST['name']);
    $email = ($_POST['email']);
    $location = ($_POST['location']);

    // Check if email is used by another user
    $check = $conn->query("SELECT * FROM users WHERE email='$email' AND user_id != $admin_id");
    if ($check->num_rows > 0) {
        $message = "Email already in use by another account.";
        $messageType = "error";
    } else {
        $conn->query("UPDATE users SET name='$name', email='$email', location='$location' WHERE user_id=$admin_id");
        $_SESSION['user_name'] = $name;
        $message = "Profile updated successfully!";
        $messageType = "success";
        $admin = $conn->query("SELECT * FROM users WHERE user_id=$admin_id")->fetch_assoc();
    }
}

// Handle Change Password
if (isset($_POST['change_password'])) {
    $current = ($_POST['current_password']);
    $new = ($_POST['new_password']);
    $confirm = ($_POST['confirm_password']);

    if ($current != $admin['password']) {
        $message = "Current password is incorrect.";
        $messageType = "error";
    } elseif ($new != $confirm) {
        $message = "New password and confirm password do not match.";
        $messageType = "error";
    } else {
        $conn->query("UPDATE users SET password='$new' WHERE user_id=$admin_id");
        $message = "Password changed successfully!";
        $messageType = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Book Swap Portal</title>
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
            max-width: 1000px;
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

        /* Profile Layout */
        .profile-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        /* Sidebar */
        .profile-sidebar {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .profile-header {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffd166, #ff9e6d);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 auto 1rem;
            border: 4px solid white;
        }

        .profile-name {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .profile-role {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .profile-stats {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .stat-value {
            color: #2c3e50;
            font-weight: 600;
        }

        .profile-menu {
            padding: 1rem 0;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 1rem 1.5rem;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid transparent;
        }

        .menu-item:hover {
            background: #f8f9fa;
            color: #4a6491;
            padding-left: 2rem;
        }

        .menu-item.active {
            background: linear-gradient(90deg, rgba(74, 100, 145, 0.1), transparent);
            color: #4a6491;
            border-left: 4px solid #4a6491;
            font-weight: 500;
        }

        .menu-item i {
            width: 20px;
            font-size: 1.1rem;
        }

        /* Main Content */
        .profile-content {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .content-header {
            background: #f8f9fa;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e9ecef;
        }

        .content-header h2 {
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .content-body {
            padding: 2rem;
        }

        /* Message Alert */
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

        /* Form Styles */
        .form-group {
            margin-bottom: 1.8rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .input-group {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 3rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
            color: #333;
        }

        .form-control:focus {
            outline: none;
            border-color: #4a6491;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(74, 100, 145, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 1.2rem;
        }

        .form-control:focus+.input-icon {
            color: #4a6491;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
        }

        .btn {
            padding: 0.9rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(to right, #4a6491, #2c3e50);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, #3b5275, #2c3e50);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(74, 100, 145, 0.3);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #2c3e50;
            border: 2px solid #e9ecef;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        /* Password Strength */
        .password-strength {
            margin-top: 0.5rem;
        }

        .strength-bar {
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .strength-fill {
            height: 100%;
            width: 0;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .strength-text {
            font-size: 0.85rem;
            color: #666;
        }

        /* Danger Zone */
        .danger-zone {
            margin-top: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border-radius: 12px;
            border-left: 4px solid #ff6b6b;
        }

        .danger-zone h3 {
            color: #721c24;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .danger-zone p {
            color: #721c24;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            padding: 0.8rem 1.5rem;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #e85959, #ff6b6b);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }

            .profile-layout {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        /* Animations */
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

        .profile-content {
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
                <i class="fas fa-user-cog"></i>
                Admin Profile
            </h1>
            <p class="admin-subtitle">Manage your account settings and preferences</p>
        </div>

        <!-- Profile Layout -->
        <div class="profile-layout">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($admin['name'], 0, 1)); ?>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($admin['name']); ?></div>
                    <div class="profile-role">
                        <i class="fas fa-crown"></i>
                        Administrator
                    </div>
                </div>

                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-label">User ID</span>
                        <span class="stat-value">#<?php echo $admin['user_id']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Credits</span>
                        <span class="stat-value"><?php echo $admin['credits']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Member Since</span>
                        <span class="stat-value"><?php echo date('M Y', strtotime($admin['created_at'])); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Location</span>
                        <span class="stat-value"><?php echo htmlspecialchars($admin['location']); ?></span>
                    </div>
                </div>

                <div class="profile-menu">
                    <div class="menu-item active" data-tab="profile">
                        <i class="fas fa-user-edit"></i>
                        Edit Profile
                    </div>
                    <div class="menu-item" data-tab="security">
                        <i class="fas fa-lock"></i>
                        Security
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="profile-content">
                <!-- Messages -->
                <?php if ($message != ""): ?>
                    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <span><?php echo $message; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Profile Tab -->
                <div class="tab-content active" id="profileTab">
                    <div class="content-header">
                        <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
                    </div>
                    <div class="content-body">
                        <form method="POST" id="profileForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Full Name</label>
                                    <div class="input-group">
                                        <i class="fas fa-user input-icon"></i>
                                        <input type="text" name="name" class="form-control"
                                            value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Location</label>
                                    <div class="input-group">
                                        <i class="fas fa-map-marker-alt input-icon"></i>
                                        <input type="text" name="location" class="form-control"
                                            value="<?php echo htmlspecialchars($admin['location']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input type="email" name="email" class="form-control"
                                        value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                </div>
                                <small style="color: #666; font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                                    <i class="fas fa-info-circle"></i> This email will be used for account notifications
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Account Type</label>
                                <div class="input-group">
                                    <i class="fas fa-crown input-icon"></i>
                                    <input type="text" class="form-control" value="Administrator" readonly disabled
                                        style="background: linear-gradient(135deg, rgba(255, 107, 107, 0.1), rgba(255, 142, 142, 0.1)); color: #ff6b6b; font-weight: 500;">
                                </div>
                            </div>

                            <div class="action-buttons">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Save Changes
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security Tab -->
                <div class="tab-content" id="securityTab" style="display: none;">
                    <div class="content-header">
                        <h2><i class="fas fa-lock"></i> Change Password</h2>
                    </div>
                    <div class="content-body">
                        <form method="POST" id="securityForm">
                            <div class="form-group">
                                <label class="form-label">Current Password</label>
                                <div class="input-group">
                                    <i class="fas fa-key input-icon"></i>
                                    <input type="password" name="current_password" class="form-control"
                                        id="currentPassword" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" name="new_password" class="form-control"
                                        id="newPassword" required>
                                </div>
                                <div class="password-strength">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                    <div class="strength-text" id="strengthText">Password strength</div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>

                            <div class="action-buttons">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key"></i>
                                    Change Password
                                </button>
                            </div>
                        </form>

                        <!-- Danger Zone -->
                        <div class="danger-zone">
                            <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                            <p>Deleting your account will remove all your data permanently. This action cannot be undone.</p>
                            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                <i class="fas fa-trash"></i>
                                Delete My Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Tab switching
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function() {
                // Update active menu item
                document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');

                // Show corresponding tab
                const tab = this.dataset.tab;
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.style.display = 'none';
                });

                document.getElementById(tab + 'Tab').style.display = 'block';
            });
        });

        // Password strength indicator
        const newPasswordInput = document.getElementById('newPassword');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');

        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let color = '#ff6b6b';
                let text = 'Very Weak';

                // Check password strength
                if (password.length >= 8) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;

                // Update display
                switch (strength) {
                    case 1:
                        color = '#ff6b6b';
                        text = 'Weak';
                        break;
                    case 2:
                        color = '#ffa94d';
                        text = 'Fair';
                        break;
                    case 3:
                        color = '#51cf66';
                        text = 'Good';
                        break;
                    case 4:
                        color = '#2f9e44';
                        text = 'Strong';
                        break;
                }

                strengthFill.style.width = (strength * 25) + '%';
                strengthFill.style.backgroundColor = color;
                strengthText.textContent = text;
                strengthText.style.color = color;
            });
        }

        // Form validation
        const profileForm = document.getElementById('profileForm');
        const securityForm = document.getElementById('securityForm');

        if (securityForm) {
            securityForm.addEventListener('submit', function(e) {
                const newPassword = document.querySelector('input[name="new_password"]').value;
                const confirmPassword = document.querySelector('input[name="confirm_password"]').value;

                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long.');
                    return;
                }

                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New password and confirm password do not match.');
                    return;
                }
            });
        }

        // Confirm account deletion
        function confirmDelete() {
            if (confirm('⚠️ WARNING: Are you sure you want to delete your account?\n\nThis action cannot be undone. All your data including:\n• Your profile\n• Your listed books\n• Your swap history\n• Your messages\n\nwill be permanently deleted.')) {
                alert('For security reasons, account deletion must be processed through customer support. Please contact support@bookswap-portal.com to proceed.');
                // In a real application, this would redirect to account deletion
            }
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            // Add focus effect to form inputs
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });
        });
    </script>
</body>

</html>