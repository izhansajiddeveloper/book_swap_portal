<?php
session_start();
require_once "config/db.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>About Us | Book Swap Portal</title>
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
            color: var(--dark);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 5rem 2rem;
            border-radius: 20px;
            margin-bottom: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.2);
        }

        .hero-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.1;
        }

        .hero-section h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
        }

        .hero-section h1::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 2px;
        }

        .hero-section p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 1.5rem auto;
            line-height: 1.6;
            opacity: 0.9;
        }

        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            border-top: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-10px);
        }

        .stat-card i {
            font-size: 3rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
             background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray);
            font-size: 1.1rem;
        }

        /* Features Section */
        .features-section {
            margin-bottom: 3rem;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .section-title p {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
            font-size: 1.1rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .feature-icon i {
            font-size: 1.8rem;
            color: white;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: var(--gray);
            line-height: 1.6;
        }

        /* How It Works */
        .how-it-works {
            margin-bottom: 3rem;
        }

        .steps-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 2rem;
            position: relative;
        }

        .step {
            flex: 1;
            min-width: 250px;
            text-align: center;
            position: relative;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 1.5rem;
            position: relative;
            z-index: 2;
        }

        .step h3 {
            font-size: 1.4rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .step p {
            color: var(--gray);
            line-height: 1.6;
        }

        /* Team Section */
        .team-section {
            margin-bottom: 3rem;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .team-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }

        .team-card:hover {
            transform: translateY(-5px);
        }

        .team-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: bold;
        }

        .team-card h3 {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .team-card .role {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .team-card p {
            color: var(--gray);
            font-style: italic;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
            color: white;
            padding: 4rem 2rem;
            border-radius: 20px;
            text-align: center;
            margin-top: 3rem;
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.2);
        }

        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .cta-section p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto 2rem;
            opacity: 0.9;
        }

        .btn {
            display: inline-block;
            padding: 1rem 2.5rem;
            background: white;
            color: var(--success);
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 3rem 1rem;
            }

            .hero-section h1 {
                font-size: 2.5rem;
            }

            .steps-container {
                flex-direction: column;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .cta-section {
                padding: 3rem 1rem;
            }
        }
    </style>
</head>

<body>
    <?php include "includes/navbar.php"; ?>

    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1><i class="fas fa-book-open"></i> About Book Swap Portal</h1>
            <p>We're building a community of book lovers who believe in sharing knowledge, reducing waste, and connecting through the power of stories. Join us in revolutionizing how we read!</p>
        </div>

        <!-- Stats Section -->
        <div class="stats-section">
            <?php

            $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
            $total_books = $conn->query("SELECT COUNT(*) as count FROM books WHERE approved='yes'")->fetch_assoc()['count'];
            $total_swaps = $conn->query("SELECT COUNT(*) as count FROM swap_requests WHERE status='completed'")->fetch_assoc()['count'];
            $active_users = $conn->query("SELECT COUNT(DISTINCT requester_id) as count FROM swap_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'];
            ?>

            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-number"><?php echo $total_users ? $total_users : '500+'; ?></div>
                <div class="stat-label">Active Readers</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-book"></i>
                <div class="stat-number"><?php echo $total_books ? $total_books : '1200+'; ?></div>
                <div class="stat-label">Books Available</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-exchange-alt"></i>
                <div class="stat-number"><?php echo $total_swaps ? $total_swaps : '850+'; ?></div>
                <div class="stat-label">Successful Swaps</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-globe"></i>
                <div class="stat-number"><?php echo $active_users ? $active_users : '50+'; ?></div>
                <div class="stat-label">Cities Covered</div>
            </div>
        </div>

        <!-- Our Mission -->
        <div class="features-section">
            <div class="section-title">
                <h2>Our Mission & Vision</h2>
                <p>We're on a mission to make reading accessible, sustainable, and social for everyone</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-recycle"></i>
                    </div>
                    <h3>Sustainable Reading</h3>
                    <p>Reduce paper waste and carbon footprint by reusing books instead of buying new ones. Every swap saves trees and reduces landfill waste.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Community Building</h3>
                    <p>Connect with fellow book lovers in your area. Share stories, recommendations, and build lasting friendships through your shared love of reading.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Accessible Education</h3>
                    <p>Make books accessible to everyone regardless of their financial situation. Knowledge should be shared, not hoarded behind paywalls.</p>
                </div>
            </div>
        </div>

        <!-- How It Works -->
        <div class="how-it-works">
            <div class="section-title">
                <h2>How Book Swapping Works</h2>
                <p>Simple steps to start swapping books today</p>
            </div>
            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Sign Up & List Books</h3>
                    <p>Create your free account and list the books you're willing to share. Each book you list earns you swap credits.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Browse & Request</h3>
                    <p>Browse thousands of books from other readers. Use your credits to request books that interest you.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Connect & Swap</h3>
                    <p>Connect with book owners through our secure messaging system. Arrange meetups or delivery to exchange books.</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Rate & Repeat</h3>
                    <p>After successful swaps, rate your experience and earn trust badges. Keep swapping to build your reading collection!</p>
                </div>
            </div>
        </div>

        <!-- Our Team -->
        <div class="team-section">
            <div class="section-title">
                <h2>Meet Our Team</h2>
                <p>The passionate people behind Book Swap Portal</p>
            </div>
            <div class="team-grid">
                <div class="team-card">
                    <div class="team-avatar">AJ</div>
                    <h3>Alex Johnson</h3>
                    <div class="role">Founder & CEO</div>
                    <p>"I started Book Swap Portal to combine my love for reading with sustainable living."</p>
                </div>
                <div class="team-card">
                    <div class="team-avatar">SP</div>
                    <h3>Sarah Patel</h3>
                    <div class="role">Head of Community</div>
                    <p>"Building connections between readers brings me joy every single day."</p>
                </div>
                <div class="team-card">
                    <div class="team-avatar">MR</div>
                    <h3>Michael Roberts</h3>
                    <div class="role">Tech Lead</div>
                    <p>"Creating seamless swapping experiences through technology is my passion."</p>
                </div>
                <div class="team-card">
                    <div class="team-avatar">ED</div>
                    <h3>Emma Davis</h3>
                    <div class="role">Content Curator</div>
                    <p>"Every book has a story, and every swap creates a new chapter in someone's life."</p>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="cta-section">
            <h2>Ready to Start Swapping?</h2>
            <p>Join our community of thousands of readers who are already enjoying free books and making new friends.</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="user/dashboard.php" class="btn">Go to Dashboard</a>
            <?php else: ?>
                <a href="auth/register.php" class="btn">Join Free Today</a>
            <?php endif; ?>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>
</body>

</html>