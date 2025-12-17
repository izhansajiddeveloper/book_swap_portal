<?php

require_once '../config/db.php';

$message = "";
$messageType = ""; // 'error' or 'success'

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize input
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password']; // plain password
    $location = $_POST['location'];

    // Check if email exists
    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $message = "Email already registered!";
        $messageType = "error";
    } else {
        // Insert user as normal user
        $sql = "INSERT INTO users (name, email, password, location, role) 
                VALUES ('$name','$email','$password','$location','user')";
        if ($conn->query($sql)) {
            // Set session variables
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'user';
            // Redirect to user dashboard
            header("Location: ../user/dashboard.php");
            exit;
        } else {
            $message = "Registration failed: " . $conn->error;
            $messageType = "error";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Book Swap Portal</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Auth Container */
        .auth-container {
            display: flex;
            min-height: calc(100vh - 200px);
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .auth-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 550px;
            animation: fadeInUp 0.6s ease-out;
        }

        .auth-header {
            background: linear-gradient(135deg, #2c3e50 0%, #4a6491 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }

        .auth-header::before {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 40px;
            background: white;
            border-radius: 20px 20px 0 0;
        }

        .auth-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            color: #ffd166;
        }

        .auth-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: #b0b0b0;
            font-size: 1.1rem;
        }

        .auth-body {
            padding: 2.5rem 2rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
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
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
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

        /* Two Column Layout for Larger Screens */
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

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(to right, #ff9e6d, #ffd166);
            color: #2c3e50;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 158, 109, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .form-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .form-footer a {
            color: #4a6491;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .form-footer a:hover {
            color: #2c3e50;
            text-decoration: underline;
        }

        /* Message Alert */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .alert-error {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
        }

        .alert-success {
            background: linear-gradient(135deg, #51cf66, #69db7c);
            color: white;
        }

        .alert i {
            font-size: 1.2rem;
        }

        /* Password Strength Indicator */
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

        /* Terms and Conditions */
        .terms {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .terms input {
            margin-top: 3px;
            width: 18px;
            height: 18px;
        }

        .terms a {
            color: #4a6491;
            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        /* Feature List */
        .features-list {
            background: linear-gradient(135deg, rgba(255, 209, 102, 0.1), rgba(255, 158, 109, 0.1));
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .features-list h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .features-list ul {
            list-style: none;
            padding: 0;
        }

        .features-list li {
            margin-bottom: 0.8rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .features-list li i {
            color: #ff9e6d;
            margin-top: 3px;
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
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
        @media (max-width: 768px) {
            .auth-card {
                max-width: 100%;
            }

            .auth-header {
                padding: 2rem 1.5rem;
            }

            .auth-body {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1>Join BookSwap</h1>
                <p>Create your account and start swapping books today</p>
            </div>

            <div class="auth-body">
                <?php if ($message != ""): ?>
                    <div class="alert <?php echo $messageType === 'error' ? 'alert-error' : 'alert-success'; ?>">
                        <i class="fas <?php echo $messageType === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                        <span><?php echo $message; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="registerForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <div class="input-group">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Location</label>
                            <div class="input-group">
                                <i class="fas fa-map-marker-alt input-icon"></i>
                                <input type="text" name="location" class="form-control" placeholder="City, State" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Create a strong password" required>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="strength-text" id="strengthText">Password strength</div>
                        </div>
                    </div>

                    <div class="terms">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>

                    <div class="features-list">
                        <h3>Why join BookSwap?</h3>
                        <ul>
                            <li><i class="fas fa-check-circle"></i> <span>Swap books with readers near you</span></li>
                            <li><i class="fas fa-check-circle"></i> <span>Build your reading credits</span></li>
                            <li><i class="fas fa-check-circle"></i> <span>Discover new books and authors</span></li>
                            <li><i class="fas fa-check-circle"></i> <span>Save money and reduce waste</span></li>
                        </ul>
                    </div>

                    <div class="form-footer">
                        Already have an account? <a href="login.php">Sign in here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password strength indicator
            const passwordInput = document.getElementById('password');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');

            passwordInput.addEventListener('input', function() {
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

            // Form validation
            const form = document.getElementById('registerForm');
            form.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const terms = document.getElementById('terms');

                // Check password strength
                if (password.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long.');
                    passwordInput.focus();
                    return;
                }

                // Check terms agreement
                if (!terms.checked) {
                    e.preventDefault();
                    alert('You must agree to the Terms of Service and Privacy Policy.');
                    return;
                }
            });

            // Add focus effect to form inputs
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                input.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });
        });
    </script>
</body>

</html>