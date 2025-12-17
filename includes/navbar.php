 <?php

    require __DIR__ . '/../config/db.php';

    // Check user role from session
    $user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
    $username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    // Fetch user credits
    $credits_sql = "SELECT * FROM users WHERE user_id = '$user_id'";
    $credits_result = $conn->query($credits_sql);
    $user_credits = $credits_result->fetch_assoc();
    ?>


 <!DOCTYPE html>
 <html lang="en">

 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <style>
         body {
             overflow-x: hidden;
         }

         /* Navbar Styles */
         .navbar {
             background: linear-gradient(135deg, #2c3e50 0%, #4a6491 100%);
             padding: 0.8rem 1.5rem;
             box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
             position: sticky;
             top: 0;
             z-index: 1000;
             font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
         }

         .nav-container {
             display: flex;
             justify-content: space-between;
             align-items: center;
             max-width: 1200px;
             margin: 0 auto;
         }

         .logo {
             display: flex;
             align-items: center;
             text-decoration: none;
             color: white;
             font-size: 1.8rem;
             font-weight: 700;
             transition: transform 0.3s ease;
         }

         .logo:hover {
             transform: scale(1.05);
         }

         .logo-icon {
             font-size: 2.2rem;
             margin-right: 10px;
             color: #ffd166;
         }

         .logo-text {
             background: linear-gradient(to right, #ffd166, #ff9e6d);
             -webkit-background-clip: text;
             -webkit-text-fill-color: transparent;
             background-clip: text;
         }

         .nav-links {
             display: flex;
             align-items: center;
             gap: 0.8rem;
         }

         .nav-link {
             color: #e0e0e0;
             text-decoration: none;
             font-weight: 500;
             font-size: 1rem;
             padding: 0.5rem 0.8rem;
             border-radius: 4px;
             transition: all 0.3s ease;
             position: relative;
             display: flex;
             align-items: center;
             gap: 6px;
             white-space: nowrap;
         }

         .nav-link:hover {
             color: #ffffff;
             background-color: rgba(255, 255, 255, 0.1);
         }

         .nav-link i {
             font-size: 1.1rem;
         }

         .nav-link.active {
             color: #ffd166;
         }

         .nav-link.active::after {
             content: '';
             position: absolute;
             bottom: -5px;
             left: 10%;
             width: 80%;
             height: 3px;
             background: #ffd166;
             border-radius: 2px;
         }

         /* Dropdown Menu */
         .dropdown {
             position: relative;
         }

         .dropdown-toggle {
             display: flex;
             align-items: center;
             gap: 5px;
         }

         .dropdown-toggle i.fa-chevron-down {
             font-size: 0.8rem;
             transition: transform 0.3s ease;
         }

         .dropdown:hover .dropdown-toggle i.fa-chevron-down {
             transform: rotate(180deg);
         }

         .dropdown-menu {
             position: absolute;
             top: 100%;
             left: -40px;
             background: white;
             min-width: 220px;
             border-radius: 8px;
             box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
             opacity: 0;
             visibility: hidden;
             transform: translateY(10px);
             transition: all 0.3s ease;
             z-index: 1001;
             padding: 0.5rem 0;
         }

         .dropdown:hover .dropdown-menu {
             opacity: 1;
             visibility: visible;
             transform: translateY(0);
         }

         .dropdown-item {
             display: block;
             padding: 0.7rem 1.2rem;
             color: #2c3e50;
             text-decoration: none;
             font-size: 0.95rem;
             transition: all 0.2s ease;
             border-left: 3px solid transparent;
         }

         .dropdown-item:hover {
             background-color: #f8f9fa;
             color: #ff9e6d;
             border-left: 3px solid #ff9e6d;
             padding-left: 1.5rem;
         }

         .dropdown-item i {
             width: 20px;
             color: #4a6491;
             font-size: 0.9rem;
         }

         .dropdown-divider {
             height: 1px;
             background-color: #eaeaea;
             margin: 0.5rem 0;
         }

         /* Credits badge */
         .credits-badge {
             background: linear-gradient(135deg, #ffd166, #ff9e6d);
             color: #2c3e50;
             font-weight: bold;
             padding: 0.3rem 0.8rem;
             border-radius: 20px;
             font-size: 0.9rem;
             display: flex;
             align-items: center;
             gap: 5px;
         }

         .mobile-menu-btn {
             display: none;
             background: none;
             border: none;
             color: white;
             font-size: 1.5rem;
             cursor: pointer;
         }

         /* Badge for notifications */
         .badge {
             background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
             color: white;
             font-size: 0.7rem;
             padding: 0.1rem 0.4rem;
             border-radius: 10px;
             margin-left: 5px;
             font-weight: bold;
         }

         /* Special buttons */
         .btn-primary {
             background: linear-gradient(to right, #ff9e6d, #ffd166);
             color: #2c3e50;
             padding: 0.6rem 1.2rem;
             border-radius: 30px;
             font-weight: 600;
             text-decoration: none;
             display: flex;
             align-items: center;
             gap: 8px;
             transition: all 0.3s ease;
             border: none;
             cursor: pointer;
         }

         .btn-primary:hover {
             transform: translateY(-2px);
             box-shadow: 0 5px 15px rgba(255, 158, 109, 0.3);
         }

         /* Responsive Design */
         @media (max-width: 1024px) {
             .nav-links {
                 gap: 0.6rem;
             }

             .nav-link {
                 font-size: 0.95rem;
                 padding: 0.4rem 0.6rem;
             }
         }

         @media (max-width: 768px) {
             .mobile-menu-btn {
                 display: block;
             }

             .nav-links {
                 position: fixed;
                 top: 70px;
                 left: 0;
                 right: 0;
                 background: linear-gradient(135deg, #2c3e50 0%, #4a6491 100%);
                 flex-direction: column;
                 padding: 1.5rem;
                 gap: 1rem;
                 box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
                 transform: translateY(-100%);
                 opacity: 0;
                 visibility: hidden;
                 transition: all 0.3s ease;
                 z-index: 999;
                 max-height: 80vh;
                 overflow-y: auto;
             }

             .nav-links.active {
                 transform: translateY(0);
                 opacity: 1;
                 visibility: visible;
             }

             .nav-link {
                 width: 100%;
                 justify-content: flex-start;
                 padding: 0.8rem;
                 font-size: 1.1rem;
             }

             .dropdown-menu {
                 position: static;
                 opacity: 1;
                 visibility: visible;
                 transform: none;
                 background: rgba(255, 255, 255, 0.05);
                 box-shadow: none;
                 margin-top: 0.5rem;
                 margin-left: 1rem;
                 display: none;
             }

             .dropdown:hover .dropdown-menu {
                 display: block;
             }

             .dropdown-item {
                 color: #e0e0e0;
                 padding: 0.6rem 1rem;
             }

             .dropdown-item:hover {
                 background-color: rgba(255, 255, 255, 0.1);
                 color: #ffd166;
             }
         }

         /* Animation for navbar */
         @keyframes fadeInDown {
             from {
                 opacity: 0;
                 transform: translateY(-20px);
             }

             to {
                 opacity: 1;
                 transform: translateY(0);
             }
         }

         .navbar {
             animation: fadeInDown 0.5s ease-out;
         }
     </style>
 </head>

 <body>
     <nav class="navbar">
         <div class="nav-container">
             <a href="/book-swap-portal/index.php" class="logo">
                 <i class="fas fa-book-swap logo-icon"></i>
                 <span class="logo-text">BookSwap</span>
             </a>

             <button class="mobile-menu-btn" id="mobileMenuBtn">
                 <i class="fas fa-bars"></i>
             </button>

             <div class="nav-links" id="navLinks">
                 <!-- Home Link (Visible to all) -->
                 <a href="/book-swap-portal/index.php" class="nav-link">
                     <i class="fas fa-home"></i>
                     <span>Home</span>
                 </a>

                 <!-- Browse Books Link (Visible to all) -->
                 <a href="/book-swap-portal/browse.php" class="nav-link">
                     <i class="fas fa-search"></i>
                     <span>Browse Books</span>
                 </a>






                 <?php if (isset($_SESSION['user_id'])): ?>

                     <?php if ($user_role == 'admin'): ?>
                         <!-- ADMIN NAVIGATION -->

                         <!-- Admin Dashboard -->
                         <a href="/book-swap-portal/admin/dashboard.php" class="nav-link">
                             <i class="fas fa-tachometer-alt"></i>
                             <span>Admin Dashboard</span>
                         </a>

                         <!-- Admin Dropdown -->
                         <div class="dropdown">
                             <a href="#" class="nav-link dropdown-toggle">
                                 <i class="fas fa-cog"></i>
                                 <span>Manage</span>
                                 <i class="fas fa-chevron-down"></i>
                             </a>
                             <div class="dropdown-menu">
                                 <a href="/book-swap-portal/admin/manage_users.php" class="dropdown-item">
                                     <i class="fas fa-users"></i>
                                     Manage Users
                                 </a>
                                 <a href="/book-swap-portal/admin/approve_books.php" class="dropdown-item">
                                     <i class="fas fa-book"></i>
                                     Approve Books
                                 </a>
                                 <a href="/book-swap-portal/admin/disputes.php" class="dropdown-item">
                                     <i class="fas fa-exclamation-triangle"></i>
                                     Dispute Resolution
                                 </a>
                                 <div class="dropdown-divider"></div>
                                 <a href="/book-swap-portal/admin/analytics.php" class="dropdown-item">
                                     <i class="fas fa-chart-bar"></i>
                                     Analytics
                                 </a>
                             </div>
                         </div>

                         <!-- View as User -->
                         <!-- <a href="/book-swap-portal/user/dashboard.php" class="nav-link">
                            <i class="fas fa-eye"></i>
                            <span>View User Panel</span>
                        </a> -->

                     <?php else: ?>
                         <!-- REGULAR USER NAVIGATION -->

                         <!-- Credits Display -->
                         <!-- <?php if ($user_credits > 0): ?>
                            <div class="credits-badge">
                                <i class="fas fa-coins"></i>
                                <span><?php echo $user_credits['credits']; ?> Credits</span>
                            </div>
                        <?php endif; ?> -->

                         <!-- Dashboard -->
                         <a href="/book-swap-portal/user/dashboard.php" class="nav-link">
                             <i class="fas fa-tachometer-alt"></i>
                             <span>Dashboard</span>
                         </a>

                         <!-- My Books -->
                         <a href="/book-swap-portal/user/my_books.php" class="nav-link">
                             <i class="fas fa-book"></i>
                             <span>My Books</span>
                         </a>

                         <!-- Swap Requests -->
                         <a href="/book-swap-portal/swap/swap_requests.php" class="nav-link">
                             <i class="fas fa-exchange-alt"></i>
                             <span>Swap Requests</span>
                             <span class="badge">3</span>
                         </a>

                         <!-- Messages -->
                         <a href="../swap/messages.php" class="nav-link">
                             <i class="fas fa-envelope"></i>
                             <span>Messages</span>
                             <span class="badge">2</span>
                         </a>

                         <!-- Add Book Button -->
                         <!-- <a href="/book-swap-portal/user/add_book.php" class="btn-primary">
                            <i class="fas fa-plus-circle"></i>
                            <span>Add Book</span>
                        </a> -->
                     <?php endif; ?>

                     <!-- Profile Dropdown (Role-Based) -->
                     <div class="dropdown">
                         <a href="#" class="nav-link dropdown-toggle">
                             <i class="fas fa-user-circle"></i>
                             <span>Profile</span>
                             <i class="fas fa-chevron-down"></i>
                         </a>
                         <div class="dropdown-menu">
                             <!-- User Info Header -->
                             <div style="padding: 0.7rem 1.2rem; color: #2c3e50; font-weight: 500; border-bottom: 1px solid #eaeaea; margin-bottom: 0.5rem;">
                                 <div style="display: flex; align-items: center; gap: 10px;">
                                     <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #4a6491, #2c3e50); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                         <?php echo strtoupper(substr($username, 0, 1)); ?>
                                     </div>
                                     <div>
                                         <div style="font-size: 1rem; font-weight: 600;"><?php echo htmlspecialchars($username); ?></div>
                                         <div style="font-size: 0.8rem; color: #666; margin-top: 2px;">
                                             <?php if ($user_role == 'admin'): ?>
                                                 <span style="background: #ff6b6b; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;">Administrator</span>
                                             <?php else: ?>
                                                 <span style="background: #4a6491; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;">Member</span>
                                             <?php endif; ?>
                                         </div>
                                     </div>
                                 </div>
                             </div>

                             <div class="dropdown-divider"></div>

                             <!-- Profile Link (Role-Based) -->
                             <?php if ($user_role == 'admin'): ?>
                                 <a href="/book-swap-portal/admin/profile.php" class="dropdown-item">
                                     <i class="fas fa-user-cog"></i>
                                     Admin Profile
                                 </a>
                             <?php else: ?>
                                 <a href="/book-swap-portal/user/profile.php" class="dropdown-item">
                                     <i class="fas fa-user-edit"></i>
                                     Edit Profile
                                 </a>
                                 <a href="/book-swap-portal/user/credits.php" class="dropdown-item">
                                     <i class="fas fa-coins"></i>
                                     My Credits (<?php echo $user_credits['credits']; ?>)
                                 </a>
                             <?php endif; ?>

                             <!-- Common Links for Both Roles -->
                             <a href="/book-swap-portal/user/settings.php" class="dropdown-item">
                                 <i class="fas fa-cog"></i>
                                 Settings
                             </a>

                             <?php if ($user_role == 'admin'): ?>
                                 <a href="/book-swap-portal/user/dashboard.php" class="dropdown-item">
                                     <i class="fas fa-eye"></i>
                                     Switch to User View
                                 </a>
                             <?php endif; ?>

                             <div class="dropdown-divider"></div>

                             <!-- Logout -->
                             <a href="/book-swap-portal/auth/logout.php" class="dropdown-item" style="color: #ff6b6b; font-weight: 600;">
                                 <i class="fas fa-sign-out-alt"></i>
                                 Logout
                             </a>
                         </div>
                     </div>

                 <?php else: ?>
                     <!-- GUEST NAVIGATION -->

                     <!-- How It Works -->
                     <a href="/book-swap-portal/how-it-works.php" class="nav-link">
                         <i class="fas fa-info-circle"></i>
                         <span>How It Works</span>
                     </a>

                     <!-- About -->
                     <a href="/book-swap-portal/about.php" class="nav-link">
                         <i class="fas fa-info-circle"></i>
                         <span>About</span>
                     </a>
                        <!-- Contact -->
                     <a href="/book-swap-portal/contact.php" class="nav-link">
                         <i class="fas fa-envelope"></i>
                         <span>Contact</span>
                     </a>
                     <!-- Login Link -->
                     <a href="/book-swap-portal/auth/login.php" class="nav-link">
                         <i class="fas fa-sign-in-alt"></i>
                         <span>Login</span>
                     </a>

                     <!-- Register Button -->
                     <a href="/book-swap-portal/auth/register.php" class="btn-primary">
                         <i class="fas fa-user-plus"></i>
                         <span>Join Free</span>
                     </a>
                 <?php endif; ?>
             </div>
         </div>
     </nav>

     <script>
         // Mobile menu toggle
         document.getElementById('mobileMenuBtn').addEventListener('click', function() {
             document.getElementById('navLinks').classList.toggle('active');
             const icon = this.querySelector('i');
             if (icon.classList.contains('fa-bars')) {
                 icon.classList.remove('fa-bars');
                 icon.classList.add('fa-times');
             } else {
                 icon.classList.remove('fa-times');
                 icon.classList.add('fa-bars');
             }
         });

         // Close mobile menu when clicking a link
         document.querySelectorAll('.nav-link').forEach(link => {
             link.addEventListener('click', () => {
                 document.getElementById('navLinks').classList.remove('active');
                 const icon = document.querySelector('#mobileMenuBtn i');
                 icon.classList.remove('fa-times');
                 icon.classList.add('fa-bars');
             });
         });

         // Set active link based on current page
         document.addEventListener('DOMContentLoaded', function() {
             const currentPage = window.location.pathname;
             const navLinks = document.querySelectorAll('.nav-link');

             navLinks.forEach(link => {
                 if (link.getAttribute('href') === currentPage) {
                     link.classList.add('active');
                 }
             });

             // Handle dropdown toggle on mobile
             const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
             dropdownToggles.forEach(toggle => {
                 toggle.addEventListener('click', function(e) {
                     if (window.innerWidth <= 768) {
                         e.preventDefault();
                         const dropdown = this.closest('.dropdown');
                         const menu = dropdown.querySelector('.dropdown-menu');
                         menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                     }
                 });
             });
         });

         // Close dropdowns when clicking outside
         document.addEventListener('click', function(event) {
             if (!event.target.closest('.dropdown') && window.innerWidth > 768) {
                 document.querySelectorAll('.dropdown-menu').forEach(menu => {
                     menu.style.opacity = '0';
                     menu.style.visibility = 'hidden';
                     menu.style.transform = 'translateY(10px)';
                 });
             }
         });
     </script>