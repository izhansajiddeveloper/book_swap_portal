<?php

require_once '../config/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password']; // plain password

    $sql = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: ../admin/dashboard.php");
            exit;
        } else {
            header("Location: ../user/dashboard.php");
            exit;
        }
    } else {
        $message = "Invalid email or password!";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Book Swap Portal</title>
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
            max-width: 500px;
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
            margin-bottom: 1.8rem;
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
            margin-top: 20px;
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
            /* margin-top: 2rem; */
            /* padding-top: 1.5rem; */
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

        /* Additional Links */
        .additional-links {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            font-size: 0.95rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember-me input {
            width: 18px;
            height: 18px;
        }

        .forgot-password {
            color: #4a6491;
            text-decoration: none;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        /* Social Login */
        .social-login {
            margin-top: 2rem;
        }

        .social-divider {
            text-align: center;
            position: relative;
            margin: 1.5rem 0;
        }

        .social-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #eee;
        }

        .social-divider span {
            background: white;
            padding: 0 1rem;
            color: #666;
            font-size: 0.9rem;
        }

        .social-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .social-btn {
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            border-color: #4a6491;
            transform: translateY(-2px);
        }

        .social-btn.google {
            color: #db4437;
        }

        .social-btn.facebook {
            color: #4267B2;
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

            .social-buttons {
                grid-template-columns: 1fr;
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
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <h1>Welcome Back</h1>
                <p>Sign in to continue to your BookSwap account</p>
            </div>

            <div class="auth-body">
                <?php if ($message != ""): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $message; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
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
                            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                    </div>

                    <div class="additional-links">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>

                    <!-- Social Login Section (Optional) -->

            </div>

            <div class="form-footer">
                Don't have an account? <a href="register.php">Create one here</a>
            </div>
            </form>
        </div>
    </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Add focus effect to form inputs
        document.addEventListener('DOMContentLoaded', function() {
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

            // Social login buttons demo
            const socialButtons = document.querySelectorAll('.social-btn');
            socialButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const platform = this.classList.contains('google') ? 'Google' : 'Facebook';
                    alert(platform + ' login would be implemented here.');
                });
            });
        });
    </script>
</body>

</html>