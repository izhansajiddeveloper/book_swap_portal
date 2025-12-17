 <?php ob_start();
    include_once 'includes/navbar.php'; ?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Swap Portal - Exchange Books, Share Knowledge</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, rgba(44, 62, 80, 0.9), rgba(74, 100, 145, 0.9)),
                url('https://images.unsplash.com/photo-1497636577773-f1231844b336?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 5rem 1rem;
            text-align: center;
            border-radius: 0 0 20px 20px;
            margin-bottom: 3rem;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, #ffd166, #ff9e6d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            color: #e0e0e0;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.9rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .btn-primary {
            background: linear-gradient(to right, #ff9e6d, #ffd166);
            color: #2c3e50;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 158, 109, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
        }

        /* Stats Section */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin: 4rem 0;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: #4a6491;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.8rem;
            font-weight: 800;
            color: #2c3e50;
            display: block;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Features Section */
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .section-title p {
            color: #666;
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 4rem 0;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-top: 4px solid transparent;
        }

        .feature-card:hover {
            border-top-color: #ff9e6d;
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: #ff9e6d;
            margin-bottom: 1.5rem;
            background: rgba(255, 158, 109, 0.1);
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        /* How It Works Section */
        .how-it-works {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 4rem 0;
            margin: 4rem 0;
            border-radius: 20px;
        }

        .steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-top: 3rem;
        }

        .steps::before {
            content: '';
            position: absolute;
            top: 40px;
            left: 10%;
            right: 10%;
            height: 3px;
            background: #ddd;
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
            padding: 0 1rem;
        }

        .step-number {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 1.5rem;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .step h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .step p {
            color: #666;
        }

        /* Featured Books */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }

        .book-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .book-image {
            height: 250px;
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #4a6491;
        }

        .book-info {
            padding: 1.5rem;
        }

        .book-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .book-author {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .book-meta {
            display: flex;
            justify-content: space-between;
            color: #888;
            font-size: 0.9rem;
        }

        /* Testimonials */
        .testimonials {
            background: linear-gradient(135deg, #2c3e50, #4a6491);
            color: white;
            padding: 4rem 0;
            margin: 4rem 0;
            border-radius: 20px;
        }

        .testimonial-slider {
            max-width: 800px;
            margin: 0 auto;
        }

        .testimonial {
            text-align: center;
            padding: 2rem;
        }

        .testimonial-text {
            font-size: 1.3rem;
            font-style: italic;
            margin-bottom: 2rem;
            line-height: 1.8;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff9e6d, #ffd166);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .author-info h4 {
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
        }

        .author-info p {
            color: #b0b0b0;
            font-size: 0.9rem;
        }

        /* Call to Action */
        .cta-section {
            background: linear-gradient(135deg, rgba(255, 209, 102, 0.1), rgba(255, 158, 109, 0.1));
            padding: 5rem 2rem;
            text-align: center;
            border-radius: 20px;
            margin: 4rem 0;
        }

        .cta-section h2 {
            font-size: 2.8rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .cta-section p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2.5rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .steps {
                flex-direction: column;
                gap: 3rem;
            }

            .steps::before {
                display: none;
            }

            .step {
                margin-bottom: 2rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
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

        .animate {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>

<body>
   

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content animate">
                <h1>Exchange Books. Share Knowledge.</h1>
                <p>Join thousands of book lovers in swapping books, saving money, and discovering new stories. Start your reading journey today!</p>
                <div class="cta-buttons">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/book-swap-portal/user/add_book.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Add Your Books
                        </a>
                        <a href="/book-swap-portal/browse.php" class="btn btn-secondary">
                            <i class="fas fa-search"></i> Browse Books
                        </a>
                    <?php else: ?>
                        <a href="/book-swap-portal/auth/register.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Join Free Today
                        </a>
                        <a href="/book-swap-portal/how-it-works.php" class="btn btn-secondary">
                            <i class="fas fa-play-circle"></i> See How It Works
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Stats Section -->
        <section class="stats animate">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <span class="stat-number" id="bookCount">10,245</span>
                <span class="stat-label">Books Available</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <span class="stat-number" id="userCount">5,678</span>
                <span class="stat-label">Active Members</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <span class="stat-number" id="swapCount">3,452</span>
                <span class="stat-label">Successful Swaps</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <span class="stat-number" id="cityCount">127</span>
                <span class="stat-label">Cities Covered</span>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section">
            <div class="section-title animate">
                <h2>Why Choose BookSwap?</h2>
                <p>Discover the benefits of joining our community of book lovers</p>
            </div>

            <div class="features">
                <div class="feature-card animate">
                    <div class="feature-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3>Save Money</h3>
                    <p>Get new books without spending money. Exchange books you've read for ones you want to read next.</p>
                </div>

                <div class="feature-card animate">
                    <div class="feature-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3>Eco-Friendly</h3>
                    <p>Reduce your carbon footprint by reusing books. Each swap helps save trees and reduce waste.</p>
                </div>

                <div class="feature-card animate">
                    <div class="feature-icon">
                        <i class="fas fa-people-arrows"></i>
                    </div>
                    <h3>Smart Matching</h3>
                    <p>Our intelligent system suggests perfect book matches based on your location and reading preferences.</p>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="how-it-works">
            <div class="container">
                <div class="section-title animate">
                    <h2>How BookSwap Works</h2>
                    <p>Swap books in four simple steps</p>
                </div>

                <div class="steps">
                    <div class="step animate">
                        <div class="step-number">1</div>
                        <h3>Create Account</h3>
                        <p>Sign up for free and set up your reading preferences</p>
                    </div>

                    <div class="step animate">
                        <div class="step-number">2</div>
                        <h3>List Your Books</h3>
                        <p>Add books you're willing to swap with photos and details</p>
                    </div>

                    <div class="step animate">
                        <div class="step-number">3</div>
                        <h3>Browse & Request</h3>
                        <p>Find books you want and send swap requests</p>
                    </div>

                    <div class="step animate">
                        <div class="step-number">4</div>
                        <h3>Swap & Enjoy</h3>
                        <p>Arrange meetups and exchange books with other readers</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Books Section -->
        <section class="featured-books">
            <div class="section-title animate">
                <h2>Recently Added Books</h2>
                <p>Discover the latest additions to our collection</p>
            </div>

            <div class="books-grid">
                <!-- Book 1 -->
                <div class="book-card animate">
                    <div class="book-image">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="book-info">
                        <h3 class="book-title">The Silent Patient</h3>
                        <p class="book-author">Alex Michaelides</p>
                        <div class="book-meta">
                            <span><i class="fas fa-map-marker-alt"></i> New York</span>
                            <span><i class="fas fa-user"></i> Sarah M.</span>
                        </div>
                    </div>
                </div>

                <!-- Book 2 -->
                <div class="book-card animate">
                    <div class="book-image">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="book-info">
                        <h3 class="book-title">Project Hail Mary</h3>
                        <p class="book-author">Andy Weir</p>
                        <div class="book-meta">
                            <span><i class="fas fa-map-marker-alt"></i> Chicago</span>
                            <span><i class="fas fa-user"></i> John D.</span>
                        </div>
                    </div>
                </div>

                <!-- Book 3 -->
                <div class="book-card animate">
                    <div class="book-image">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="book-info">
                        <h3 class="book-title">Atomic Habits</h3>
                        <p class="book-author">James Clear</p>
                        <div class="book-meta">
                            <span><i class="fas fa-map-marker-alt"></i> Los Angeles</span>
                            <span><i class="fas fa-user"></i> Maria L.</span>
                        </div>
                    </div>
                </div>

                <!-- Book 4 -->
                <div class="book-card animate">
                    <div class="book-image">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="book-info">
                        <h3 class="book-title">The Midnight Library</h3>
                        <p class="book-author">Matt Haig</p>
                        <div class="book-meta">
                            <span><i class="fas fa-map-marker-alt"></i> Miami</span>
                            <span><i class="fas fa-user"></i> Robert K.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center animate" style="margin-top: 2rem;">
                <a href="/book-swap-portal/browse.php" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Browse All Books
                </a>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="testimonials">
            <div class="container">
                <div class="section-title animate">
                    <h2>What Our Members Say</h2>
                    <p>Join thousands of satisfied book lovers</p>
                </div>

                <div class="testimonial-slider">
                    <div class="testimonial animate">
                        <div class="testimonial-text">
                            "BookSwap has completely changed how I read. I've discovered amazing books and made friends with fellow readers in my area. It's eco-friendly and saves me so much money!"
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">JM</div>
                            <div class="author-info">
                                <h4>Jessica Morgan</h4>
                                <p>BookSwap Member for 2 years</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="cta-section animate">
            <h2>Ready to Start Swapping?</h2>
            <p>Join our community today and transform your bookshelf. Discover new stories, meet fellow readers, and enjoy reading without the cost.</p>
            <div class="cta-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/book-swap-portal/user/add_book.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 1rem 2.5rem;">
                        <i class="fas fa-plus-circle"></i> Add Your First Book
                    </a>
                <?php else: ?>
                    <a href="/book-swap-portal/auth/register.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 1rem 2.5rem;">
                        <i class="fas fa-user-plus"></i> Join Free Now
                    </a>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Animate elements on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const animateElements = document.querySelectorAll('.animate');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1
            });

            animateElements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });

            // Animate counter numbers
            const counters = document.querySelectorAll('.stat-number');
            const speed = 200;

            counters.forEach(counter => {
                const updateCount = () => {
                    const target = +counter.getAttribute('data-target') ||
                        +counter.textContent.replace(/,/g, '');
                    const count = +counter.textContent.replace(/,/g, '');
                    const increment = target / speed;

                    if (count < target) {
                        counter.textContent = Math.ceil(count + increment).toLocaleString();
                        setTimeout(updateCount, 1);
                    } else {
                        counter.textContent = target.toLocaleString();
                    }
                };

                // Set data-target attributes for animation
                const currentText = counter.textContent;
                counter.setAttribute('data-target', currentText.replace(/,/g, ''));
                counter.textContent = '0';

                // Start counter animation when element is in view
                const counterObserver = new IntersectionObserver((entries) => {
                    if (entries[0].isIntersecting) {
                        updateCount();
                        counterObserver.unobserve(counter);
                    }
                });

                counterObserver.observe(counter);
            });
        });
    </script>
</body>

</html>
<?php  ?>