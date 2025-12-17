<?php
// session_start();
require_once "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";
$active_tab = $_GET['tab'] ?? 'profile';

// Fetch current user info
$user_sql = "SELECT * FROM users WHERE id='$user_id'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

// Get user statistics
$stats_sql = "SELECT 
    (SELECT COUNT(*) FROM books WHERE user_id = '$user_id') as total_books,
    (SELECT COUNT(*) FROM books WHERE user_id = '$user_id' AND approved = 'yes') as approved_books,
    (SELECT COUNT(*) FROM swap_request WHERE requester_id = '$user_id') as swaps_requested,
    (SELECT COUNT(*) FROM swap_request sr JOIN books b ON sr.book_id = b.id WHERE b.user_id = '$user_id') as swaps_received";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $location = $conn->real_escape_string($_POST['location']);
    $bio = $conn->real_escape_string($_POST['bio'] ?? '');

    if (!empty($name)) {
        $update_sql = "UPDATE users SET name='$name', location='$location', bio='$bio' WHERE id='$user_id'";
        if ($conn->query($update_sql)) {
            $success = "Profile updated successfully!";
            $_SESSION['user_name'] = $name;
            $user['name'] = $name;
            $user['location'] = $location;
            $user['bio'] = $bio;
            $active_tab = 'profile';
        } else {
            $error = "Database error: " . $conn->error;
        }
    } else {
        $error = "Name cannot be empty.";
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) < 6) {
                $error = "Password must be at least 6 characters long.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $pass_sql = "UPDATE users SET password='$hashed_password' WHERE id='$user_id'";
                if ($conn->query($pass_sql)) {
                    $success = "Password changed successfully!";
                    $active_tab = 'security';
                } else {
                    $error = "Database error: " . $conn->error;
                }
            }
        } else {
            $error = "New password and confirm password do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}

// Handle email preferences update
if (isset($_POST['update_preferences'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $swap_notifications = isset($_POST['swap_notifications']) ? 1 : 0;
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;

    $pref_sql = "UPDATE users SET 
                 email_notifications = '$email_notifications',
                 swap_notifications = '$swap_notifications',
                 newsletter = '$newsletter'
                 WHERE id = '$user_id'";

    if ($conn->query($pref_sql)) {
        $success = "Preferences updated successfully!";
        $user['email_notifications'] = $email_notifications;
        $user['swap_notifications'] = $swap_notifications;
        $user['newsletter'] = $newsletter;
        $active_tab = 'preferences';
    } else {
        $error = "Database error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile & Settings - Book Swap Portal</title>
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

        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header */
        .settings-header {
            margin-bottom: 3rem;
        }

        .page-title-section {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
            padding: 2.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title i {
            color: #ffd166;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 15px;
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Messages */
        .message {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.5s ease-out;
        }

        .message.success {
            background: linear-gradient(135deg, #d3f9d8, #b2f2bb);
            color: #2b8a3e;
            border-left: 4px solid #51cf66;
        }

        .message.error {
            background: linear-gradient(135deg, #ffe3e3, #ffc9c9);
            color: #c92a2a;
            border-left: 4px solid #ff6b6b;
        }

        .message i {
            font-size: 1.2rem;
        }

        /* Content Layout */
        .settings-content {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
        }

        /* Sidebar */
        .settings-sidebar {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            height: fit-content;
        }

        .profile-card {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ff9e6d, #ffd166);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: 600;
        }

        .profile-name {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .profile-email {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4a6491;
            display: block;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
        }

        /* Settings Navigation */
        .settings-nav {
            list-style: none;
        }

        .settings-nav li {
            margin-bottom: 0.5rem;
        }

        .settings-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 1rem;
            color: #666;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .settings-nav a:hover {
            background: #f8f9fa;
            color: #4a6491;
        }

        .settings-nav a.active {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
        }

        .settings-nav i {
            width: 20px;
            text-align: center;
        }

        /* Settings Panels */
        .settings-panel {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            animation: fadeIn 0.5s ease-out;
        }

        .panel-header {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f1f3f5;
        }

        .panel-title {
            font-size: 1.8rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .panel-title i {
            color: #4a6491;
        }

        .panel-subtitle {
            color: #666;
            margin-top: 0.5rem;
            font-size: 1rem;
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #495057;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-label i {
            color: #4a6491;
            margin-right: 8px;
            width: 20px;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #4a6491;
            box-shadow: 0 0 0 3px rgba(74, 100, 145, 0.1);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-input[disabled] {
            background: #f8f9fa;
            color: #868e96;
            cursor: not-allowed;
        }

        .form-help {
            display: block;
            margin-top: 0.5rem;
            color: #868e96;
            font-size: 0.85rem;
        }

        /* Checkboxes and Switches */
        .preferences-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .preference-item {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .preference-info h4 {
            color: #2c3e50;
            margin-bottom: 0.3rem;
        }

        .preference-info p {
            color: #666;
            font-size: 0.9rem;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #dee2e6;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: #4a6491;
        }

        input:checked+.toggle-slider:before {
            transform: translateX(30px);
        }

        /* Buttons */
        .btn {
            padding: 0.9rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(to right, #4a6491, #2c3e50);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(74, 100, 145, 0.3);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #495057;
            border: 2px solid #e9ecef;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(to right, #fa5252, #e03131);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(250, 82, 82, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f1f3f5;
        }

        /* Danger Zone */
        .danger-zone {
            background: #fff5f5;
            border: 2px solid #ffc9c9;
            border-radius: 12px;
            padding: 2rem;
            margin-top: 3rem;
        }

        .danger-zone h3 {
            color: #e03131;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .danger-zone p {
            color: #868e96;
            margin-bottom: 1.5rem;
        }

        /* Animations */
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

        /* Responsive */
        @media (max-width: 992px) {
            .settings-content {
                grid-template-columns: 1fr;
            }

            .settings-sidebar {
                order: 2;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .preferences-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .settings-container {
                padding: 1rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <?php include "../includes/navbar.php"; ?>

    <div class="settings-container">
        <!-- Header -->
        <div class="settings-header">
            <div class="page-title-section">
                <h1 class="page-title">
                    <i class="fas fa-user-cog"></i>
                    Profile & Settings
                </h1>
                <p class="page-subtitle">Manage your account settings and preferences</p>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <div><?php echo $success; ?></div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo $error; ?></div>
            </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="settings-content">
            <!-- Sidebar -->
            <div class="settings-sidebar">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                    </div>
                    <h3 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>

                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['total_books']; ?></span>
                            <span class="stat-label">Books</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['approved_books']; ?></span>
                            <span class="stat-label">Approved</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['swaps_requested']; ?></span>
                            <span class="stat-label">Sent</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['swaps_received']; ?></span>
                            <span class="stat-label">Received</span>
                        </div>
                    </div>
                </div>

                <ul class="settings-nav">
                    <li>
                        <a href="?tab=profile" class="<?php echo $active_tab == 'profile' ? 'active' : ''; ?>">
                            <i class="fas fa-user"></i>
                            Profile Information
                        </a>
                    </li>
                    <li>
                        <a href="?tab=security" class="<?php echo $active_tab == 'security' ? 'active' : ''; ?>">
                            <i class="fas fa-shield-alt"></i>
                            Security
                        </a>
                    </li>
                    <li>
                        <a href="?tab=preferences" class="<?php echo $active_tab == 'preferences' ? 'active' : ''; ?>">
                            <i class="fas fa-bell"></i>
                            Notifications
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Settings Panels -->
            <div class="settings-panel">
                <?php if ($active_tab == 'profile'): ?>
                    <!-- Profile Information -->
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-user"></i>
                            Profile Information
                        </h2>
                        <p class="panel-subtitle">Update your personal details and bio</p>
                    </div>

                    <form method="POST" id="profileForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user"></i>
                                    Full Name
                                </label>
                                <input type="text" name="name" class="form-input"
                                    value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                <span class="form-help">This is how your name appears on the platform</span>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    Email Address
                                </label>
                                <input type="email" class="form-input"
                                    value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <span class="form-help">Email cannot be changed</span>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Location
                                </label>
                                <input type="text" name="location" class="form-input"
                                    value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>"
                                    placeholder="Enter your city or region">
                                <span class="form-help">Helps others find books near you</span>
                            </div>

                            <div class="form-group full-width">
                                <label class="form-label">
                                    <i class="fas fa-edit"></i>
                                    Bio
                                </label>
                                <textarea name="bio" class="form-textarea"
                                    placeholder="Tell other users about yourself, your reading preferences, or favorite genres..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                <span class="form-help">Maximum 500 characters</span>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Changes
                            </button>
                            <a href="../user/dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancel
                            </a>
                        </div>
                    </form>

                <?php elseif ($active_tab == 'security'): ?>
                    <!-- Security -->
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-shield-alt"></i>
                            Security Settings
                        </h2>
                        <p class="panel-subtitle">Manage your password and account security</p>
                    </div>

                    <form method="POST" id="securityForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-key"></i>
                                    Current Password
                                </label>
                                <input type="password" name="current_password" class="form-input" required>
                                <span class="form-help">Enter your current password</span>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-lock"></i>
                                    New Password
                                </label>
                                <input type="password" name="new_password" class="form-input" required>
                                <span class="form-help">Minimum 6 characters</span>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-lock"></i>
                                    Confirm New Password
                                </label>
                                <input type="password" name="confirm_password" class="form-input" required>
                                <span class="form-help">Re-enter your new password</span>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i>
                                Update Password
                            </button>
                        </div>
                    </form>

                    <!-- Password Requirements -->
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 12px; margin-top: 2rem;">
                        <h4 style="color: #2c3e50; margin-bottom: 0.8rem; font-size: 1rem;">
                            <i class="fas fa-info-circle"></i> Password Requirements
                        </h4>
                        <ul style="color: #666; font-size: 0.9rem; list-style: none; padding-left: 0;">
                            <li style="margin-bottom: 0.3rem;"><i class="fas fa-check" style="color: #51cf66;"></i> At least 6 characters long</li>
                            <li style="margin-bottom: 0.3rem;"><i class="fas fa-check" style="color: #51cf66;"></i> Use a mix of letters and numbers</li>
                            <li><i class="fas fa-check" style="color: #51cf66;"></i> Avoid common words or sequences</li>
                        </ul>
                    </div>

                <?php elseif ($active_tab == 'preferences'): ?>
                    <!-- Notifications & Preferences -->
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-bell"></i>
                            Notification Preferences
                        </h2>
                        <p class="panel-subtitle">Choose how you want to be notified</p>
                    </div>

                    <form method="POST" id="preferencesForm">
                        <div class="preferences-grid">
                            <div class="preference-item">
                                <div class="preference-info">
                                    <h4>Email Notifications</h4>
                                    <p>Receive notifications via email</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="email_notifications"
                                        <?php echo ($user['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="preference-item">
                                <div class="preference-info">
                                    <h4>Swap Requests</h4>
                                    <p>Get notified about new swap requests</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="swap_notifications"
                                        <?php echo ($user['swap_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="preference-item">
                                <div class="preference-info">
                                    <h4>Newsletter</h4>
                                    <p>Receive our monthly newsletter</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="newsletter"
                                        <?php echo ($user['newsletter'] ?? 0) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" name="update_preferences" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Preferences
                            </button>
                        </div>
                    </form>

                    <!-- Danger Zone -->
                    <div class="danger-zone">
                        <h3>
                            <i class="fas fa-exclamation-triangle"></i>
                            Danger Zone
                        </h3>
                        <p>Once you delete your account, there is no going back. Please be certain.</p>

                        <div class="action-buttons">
                            <button type="button" class="btn btn-danger" onclick="confirmDeleteAccount()">
                                <i class="fas fa-trash-alt"></i>
                                Delete Account
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const profileForm = document.getElementById('profileForm');
            const securityForm = document.getElementById('securityForm');
            const preferencesForm = document.getElementById('preferencesForm');

            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    const nameInput = this.querySelector('input[name="name"]');
                    if (nameInput.value.trim().length < 2) {
                        e.preventDefault();
                        alert('Name must be at least 2 characters long');
                        nameInput.focus();
                    }
                });
            }

            if (securityForm) {
                securityForm.addEventListener('submit', function(e) {
                    const newPass = this.querySelector('input[name="new_password"]');
                    const confirmPass = this.querySelector('input[name="confirm_password"]');

                    if (newPass.value.length < 6) {
                        e.preventDefault();
                        alert('New password must be at least 6 characters long');
                        newPass.focus();
                        return false;
                    }

                    if (newPass.value !== confirmPass.value) {
                        e.preventDefault();
                        alert('New password and confirm password do not match');
                        confirmPass.focus();
                        return false;
                    }
                });
            }

            // Character counter for bio
            const bioTextarea = document.querySelector('textarea[name="bio"]');
            if (bioTextarea) {
                const charCounter = document.createElement('div');
                charCounter.style.cssText = 'text-align: right; color: #868e96; font-size: 0.85rem; margin-top: 0.5rem;';
                charCounter.textContent = `${bioTextarea.value.length}/500`;
                bioTextarea.parentNode.appendChild(charCounter);

                bioTextarea.addEventListener('input', function() {
                    charCounter.textContent = `${this.value.length}/500`;
                    if (this.value.length > 500) {
                        charCounter.style.color = '#fa5252';
                    } else {
                        charCounter.style.color = '#868e96';
                    }
                });
            }
        });

        function confirmDeleteAccount() {
            if (confirm('Are you sure you want to delete your account?\n\nThis action cannot be undone. All your books, swaps, and data will be permanently removed.')) {
                alert('Account deletion is not implemented in this demo. In a real application, this would delete your account.');
            }
        }

        // Tab switching with animation
        const navLinks = document.querySelectorAll('.settings-nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href').startsWith('?')) {
                    e.preventDefault();
                    const tab = this.getAttribute('href').split('=')[1];
                    window.location.href = `?tab=${tab}`;
                }
            });
        });
    </script>
</body>

</html>