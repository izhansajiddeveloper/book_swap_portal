<footer class="footer">
    <div class="footer-container">
        <div class="footer-main">
            <div class="footer-section">
                <div class="footer-logo">
                    <i class="fas fa-book-swap"></i>
                    <h3>BookSwap Portal</h3>
                </div>
                <p class="footer-description">
                    A community-driven platform for book lovers to exchange, share, and discover new stories without the cost of buying.
                </p>
                <div class="social-links">
                    <a href="#" class="social-link">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-github"></i>
                    </a>
                </div>
            </div>

            <div class="footer-section">
                <h4 class="footer-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="/book-swap-portal/index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> How It Works</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Browse Books</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> Community</a></li>
                    <li><a href="#"><i class="fas fa-chevron-right"></i> FAQ</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4 class="footer-title">Account</h4>
                <ul class="footer-links">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="/book-swap-portal/user/dashboard.php"><i class="fas fa-chevron-right"></i> Dashboard</a></li>
                        <li><a href="/book-swap-portal/user/profile.php"><i class="fas fa-chevron-right"></i> My Profile</a></li>
                        <li><a href="/book-swap-portal/user/my_books.php"><i class="fas fa-chevron-right"></i> My Books</a></li>
                        <li><a href="/book-swap-portal/swap/swap_requests.php"><i class="fas fa-chevron-right"></i> Swap Requests</a></li>
                        <li><a href="/book-swap-portal/auth/logout.php"><i class="fas fa-chevron-right"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a href="/book-swap-portal/auth/login.php"><i class="fas fa-chevron-right"></i> Login</a></li>
                        <li><a href="/book-swap-portal/auth/register.php"><i class="fas fa-chevron-right"></i> Register</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Forgot Password</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-section">
                <h4 class="footer-title">Contact Us</h4>
                <ul class="footer-contact">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 Book Street, Library City, 12345</span>
                    </li>
                    <li>
                        <i class="fas fa-phone"></i>
                        <span>+1 (555) 123-4567</span>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <span>support@bookswap-portal.com</span>
                    </li>
                </ul>
                <div class="newsletter">
                    <h5>Stay Updated</h5>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Your email address" required>
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="copyright">
                © <?php echo date("Y"); ?> Book Swap Portal. All rights reserved. |
                Built with <i class="fas fa-heart" style="color: #ff6b6b;"></i> using PHP & MySQL
            </p>
            <div class="footer-bottom-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Cookie Policy</a>
            </div>
        </div>
    </div>
</footer>

<style>
    /* Footer Styles */
    .footer {
        background: linear-gradient(135deg, #2c3e50 0%, #1a2530 100%);
        color: #e0e0e0;
        padding: 3.5rem 0 1.5rem;
        margin-top: 4rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1.5rem;
    }

    .footer-main {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2.5rem;
        margin-bottom: 2.5rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .footer-section {
        padding: 0 1rem;
    }

    .footer-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 1.2rem;
    }

    .footer-logo i {
        font-size: 2.5rem;
        color: #ffd166;
        background: rgba(255, 255, 255, 0.05);
        padding: 10px;
        border-radius: 10px;
    }

    .footer-logo h3 {
        color: white;
        font-size: 1.8rem;
        margin: 0;
        font-weight: 700;
    }

    .footer-description {
        line-height: 1.6;
        margin-bottom: 1.5rem;
        color: #b0b0b0;
        font-size: 0.95rem;
    }

    .social-links {
        display: flex;
        gap: 12px;
    }

    .social-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        color: #e0e0e0;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .social-link:hover {
        background: linear-gradient(135deg, #ff9e6d, #ffd166);
        color: #2c3e50;
        transform: translateY(-3px);
    }

    .footer-title {
        color: white;
        font-size: 1.3rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        position: relative;
        font-weight: 600;
    }

    .footer-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 40px;
        height: 3px;
        background: linear-gradient(to right, #ff9e6d, #ffd166);
        border-radius: 2px;
    }

    .footer-links {
        list-style: none;
        padding: 0;
    }

    .footer-links li {
        margin-bottom: 0.8rem;
    }

    .footer-links a {
        color: #b0b0b0;
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .footer-links a:hover {
        color: #ffd166;
        padding-left: 5px;
    }

    .footer-links a i {
        font-size: 0.8rem;
        color: #ff9e6d;
    }

    .footer-contact {
        list-style: none;
        padding: 0;
        margin-bottom: 1.5rem;
    }

    .footer-contact li {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 1rem;
        line-height: 1.5;
    }

    .footer-contact i {
        color: #ff9e6d;
        margin-top: 3px;
        font-size: 1.1rem;
    }

    .newsletter h5 {
        color: white;
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }

    .newsletter-form {
        display: flex;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 30px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .newsletter-form input {
        flex: 1;
        padding: 0.8rem 1.2rem;
        background: transparent;
        border: none;
        color: white;
        outline: none;
    }

    .newsletter-form input::placeholder {
        color: #a0a0a0;
    }

    .newsletter-form button {
        background: linear-gradient(to right, #ff9e6d, #ffd166);
        border: none;
        color: #2c3e50;
        padding: 0 1.5rem;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .newsletter-form button:hover {
        background: linear-gradient(to right, #ff8a5c, #ffc857);
    }

    .footer-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        padding-top: 1.5rem;
    }

    .copyright {
        color: #a0a0a0;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .footer-bottom-links {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1rem;
    }

    .footer-bottom-links a {
        color: #b0b0b0;
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s ease;
    }

    .footer-bottom-links a:hover {
        color: #ffd166;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .footer-main {
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        .footer-section {
            padding: 0;
        }

        .footer-bottom {
            flex-direction: column;
            text-align: center;
        }

        .footer-bottom-links {
            justify-content: center;
        }
    }
</style>
</body>

</html>