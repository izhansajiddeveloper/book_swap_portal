<?php
session_start();
require_once "config/db.php";

$success = "";
$error = "";
$name = $email = $subject = $message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } else {
        // Save to database
        $sql = "INSERT INTO contact_messages (name, email, subject, message, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $subject, $message);

        if ($stmt->execute()) {
            $success = "Thank you for your message! We'll get back to you within 24 hours.";

            // Send email notification (optional)
            $admin_email = "admin@bookswapportal.com";
            $email_subject = "New Contact Form Submission: " . $subject;
            $email_body = "Name: $name\n";
            $email_body .= "Email: $email\n";
            $email_body .= "Subject: $subject\n";
            $email_body .= "Message:\n$message\n";
            $email_body .= "\n---\nThis message was sent from Book Swap Portal contact form.";

            // Uncomment to enable email sending
            // mail($admin_email, $email_subject, $email_body);

            // Clear form
            $name = $email = $subject = $message = "";
        } else {
            $error = "Something went wrong. Please try again later.";
        }
    }
}

// Fetch FAQ from database
$faq_sql = "SELECT * FROM faqs WHERE active='yes' ORDER BY display_order ASC";
$faq_result = $conn->query($faq_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Contact Us | Book Swap Portal</title>
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
            padding: 4rem 2rem;
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
            z-index: 1;
        }

        .hero-section p {
            font-size: 1.3rem;
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.9;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        /* Contact Layout */
        .contact-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        @media (max-width: 900px) {
            .contact-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Contact Form */
        .contact-form-section {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: var(--dark);
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid var(--gray-light);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 5px solid var(--success);
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 5px solid var(--danger);
        }

        /* Contact Info */
        .contact-info-section {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .info-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
        }

        .info-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1rem;
        }

        .info-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .info-header h3 {
            color: var(--dark);
            font-size: 1.4rem;
        }

        .info-content {
            color: var(--gray);
            line-height: 1.6;
        }

        .info-content p {
            margin-bottom: 0.5rem;
        }

        .info-content strong {
            color: var(--dark);
        }

        /* FAQ Section */
        .faq-section {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            margin-bottom: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .faq-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .faq-item {
            border: 2px solid var(--gray-light);
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-item.active {
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }

        .faq-question {
            padding: 1.5rem;
            background: var(--light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .faq-question:hover {
            background: var(--gray-light);
        }

        .faq-question h3 {
            color: var(--dark);
            font-size: 1.1rem;
            margin: 0;
        }

        .faq-toggle {
            color: var(--primary);
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .faq-item.active .faq-toggle {
            transform: rotate(45deg);
        }

        .faq-answer {
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-item.active .faq-answer {
            padding: 1.5rem;
            max-height: 500px;
        }

        .faq-answer p {
            color: var(--gray);
            line-height: 1.6;
            margin: 0;
        }

        /* Map Section */
        .map-section {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .map-container {
            border-radius: 10px;
            overflow: hidden;
            margin-top: 1.5rem;
            background: var(--light);
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
        }

        /* Contact Hours */
        .hours-section {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .hours-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .day-card {
            background: var(--light);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .day-card:hover {
            transform: translateY(-5px);
        }

        .day-card .day {
            color: var(--dark);
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .day-card .hours {
            color: var(--success);
            font-weight: 600;
            font-size: 1.2rem;
        }

        .day-card.closed {
            opacity: 0.7;
        }

        .day-card.closed .hours {
            color: var(--danger);
        }

        /* Social Media */
        .social-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 3rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.2);
        }

        .social-section h2 {
            font-size: 2.2rem;
            margin-bottom: 1rem;
        }

        .social-section p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .social-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .social-icon:hover {
            background: white;
            color: var(--primary);
            transform: translateY(-5px);
        }

        /* Button */
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1rem;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 3rem 1rem;
            }

            .hero-section h1 {
                font-size: 2.5rem;
            }

            .contact-form-section,
            .faq-section,
            .map-section,
            .hours-section,
            .social-section {
                padding: 1.5rem;
            }

            .social-icons {
                gap: 1rem;
            }

            .social-icon {
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>
    <?php include "includes/navbar.php"; ?>

    <div class="container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1><i class="fas fa-headset"></i> Contact Us</h1>
            <p>Have questions, feedback, or need assistance? We're here to help! Reach out to us through any of the channels below.</p>
        </div>

        <!-- Contact Layout -->
        <div class="contact-layout">
            <!-- Contact Form -->
            <div class="contact-form-section">
                <h2 class="section-title"><i class="fas fa-paper-plane"></i> Send us a Message</h2>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" id="name" name="name" class="form-control"
                            value="<?php echo htmlspecialchars($name); ?>"
                            placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control"
                            value="<?php echo htmlspecialchars($email); ?>"
                            placeholder="Enter your email address" required>
                    </div>

                    <div class="form-group">
                        <label for="subject" class="form-label">Subject *</label>
                        <input type="text" id="subject" name="subject" class="form-control"
                            value="<?php echo htmlspecialchars($subject); ?>"
                            placeholder="What is this regarding?" required>
                    </div>

                    <div class="form-group">
                        <label for="message" class="form-label">Message *</label>
                        <textarea id="message" name="message" class="form-control"
                            placeholder="Type your message here..."
                            required><?php echo htmlspecialchars($message); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>

            <!-- Contact Information -->
            <div class="contact-info-section">
                <div class="info-card">
                    <div class="info-header">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3>Visit Our Office</h3>
                    </div>
                    <div class="info-content">
                        <p><strong>Book Swap Portal Headquarters</strong></p>
                        <p>123 Knowledge Street, Library District</p>
                        <p>Bookville, BK 10001</p>
                        <p>United States</p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-header">
                        <div class="info-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <h3>Call Us</h3>
                    </div>
                    <div class="info-content">
                        <p><strong>Customer Support:</strong> +1 (555) 123-4567</p>
                        <p><strong>Technical Support:</strong> +1 (555) 987-6543</p>
                        <p><strong>Business Inquiries:</strong> +1 (555) 456-7890</p>
                        <p><em>Available Monday to Friday, 9 AM - 6 PM EST</em></p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-header">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Email Us</h3>
                    </div>
                    <div class="info-content">
                        <p><strong>General Inquiries:</strong> info@bookswapportal.com</p>
                        <p><strong>Support:</strong> support@bookswapportal.com</p>
                        <p><strong>Partnerships:</strong> partnerships@bookswapportal.com</p>
                        <p><strong>Press:</strong> press@bookswapportal.com</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section">
            <h2 class="section-title"><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
            <p style="color: var(--gray); margin-bottom: 1rem;">Find quick answers to common questions below</p>

            <div class="faq-container">
                <?php if ($faq_result && $faq_result->num_rows > 0): ?>
                    <?php while ($faq = $faq_result->fetch_assoc()): ?>
                        <div class="faq-item">
                            <div class="faq-question">
                                <h3><?php echo htmlspecialchars($faq['question']); ?></h3>
                                <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                            </div>
                            <div class="faq-answer">
                                <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Default FAQs if database is empty -->
                    <div class="faq-item active">
                        <div class="faq-question">
                            <h3>How does the credit system work?</h3>
                            <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>When you list a book for swapping, you earn 1 credit. You can then use these credits to request books from other users. Each swap request costs 1 credit. When someone accepts your swap request and you complete the exchange, the book owner receives 1 credit.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>How do I arrange book delivery?</h3>
                            <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>Once a swap is accepted, you can use our in-app messaging system to coordinate with the other user. Most users arrange local meetups in public places like coffee shops or libraries. For long-distance swaps, you can discuss shipping options and costs directly with the other user.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>What if a book arrives damaged?</h3>
                            <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>We recommend thoroughly inspecting books before accepting swaps. If you receive a damaged book, contact our support team immediately. We'll mediate the situation and may issue a credit refund or facilitate a return. Always check book condition ratings and photos before requesting swaps.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>How long does book approval take?</h3>
                            <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>Our team reviews new book listings within 24-48 hours. We check that books are in acceptable condition and not prohibited content. Once approved, your book will be visible to all users. You'll receive an email notification when your book is approved or if we need more information.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>Can I cancel a swap request?</h3>
                            <span class="faq-toggle"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="faq-answer">
                            <p>You can cancel a swap request anytime before it's accepted by the book owner. Once accepted, you'll need to contact the book owner through our messaging system to discuss cancellation. If both parties agree to cancel, the swap will be terminated and your credit will be refunded.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Map Section -->
        <div class="map-section">
            <h2 class="section-title"><i class="fas fa-map-marked-alt"></i> Find Us</h2>
            <p style="color: var(--gray); margin-bottom: 1rem;">Visit our headquarters or find local book swap events</p>

            <div class="map-container">
                <!-- Replace with actual Google Maps embed code -->
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-map" style="font-size: 4rem; margin-bottom: 1rem; color: var(--primary);"></i>
                    <h3 style="color: var(--dark); margin-bottom: 0.5rem;">Interactive Map</h3>
                    <p style="color: var(--gray);">Map integration would show here</p>
                    <p style="color: var(--gray); font-size: 0.9rem; margin-top: 1rem;">
                        <i class="fas fa-info-circle"></i> Replace with Google Maps embed code
                    </p>
                </div>
            </div>
        </div>

        <!-- Contact Hours -->
        <div class="hours-section">
            <h2 class="section-title"><i class="fas fa-clock"></i> Our Hours</h2>
            <p style="color: var(--gray); margin-bottom: 1rem;">We're here to help you during these hours</p>

            <div class="hours-grid">
                <div class="day-card">
                    <div class="day">Monday</div>
                    <div class="hours">9:00 AM - 6:00 PM</div>
                </div>
                <div class="day-card">
                    <div class="day">Tuesday</div>
                    <div class="hours">9:00 AM - 6:00 PM</div>
                </div>
                <div class="day-card">
                    <div class="day">Wednesday</div>
                    <div class="hours">9:00 AM - 6:00 PM</div>
                </div>
                <div class="day-card">
                    <div class="day">Thursday</div>
                    <div class="hours">9:00 AM - 6:00 PM</div>
                </div>
                <div class="day-card">
                    <div class="day">Friday</div>
                    <div class="hours">9:00 AM - 6:00 PM</div>
                </div>
                <div class="day-card closed">
                    <div class="day">Saturday</div>
                    <div class="hours">Closed</div>
                </div>
                <div class="day-card closed">
                    <div class="day">Sunday</div>
                    <div class="hours">Closed</div>
                </div>
            </div>
        </div>

        <!-- Social Media -->
        <div class="social-section">
            <h2>Connect With Us</h2>
            <p>Follow us on social media for updates, book recommendations, and community events</p>

            <div class="social-icons">
                <a href="#" class="social-icon" title="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="social-icon" title="Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="social-icon" title="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="#" class="social-icon" title="LinkedIn">
                    <i class="fab fa-linkedin-in"></i>
                </a>
                <a href="#" class="social-icon" title="YouTube">
                    <i class="fab fa-youtube"></i>
                </a>
                <a href="#" class="social-icon" title="Goodreads">
                    <i class="fab fa-goodreads-g"></i>
                </a>
            </div>

            <p style="margin-top: 2rem; font-size: 0.9rem; opacity: 0.8;">
                <i class="fas fa-hashtag"></i> #BookSwapPortal #ShareBooks #ReadMore
            </p>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>

    <script>
        // FAQ Toggle Functionality
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const faqItem = question.parentElement;
                const isActive = faqItem.classList.contains('active');

                // Close all FAQ items
                document.querySelectorAll('.faq-item').forEach(item => {
                    item.classList.remove('active');
                });

                // If the clicked item wasn't active, open it
                if (!isActive) {
                    faqItem.classList.add('active');
                }
            });
        });

        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', (e) => {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message').value.trim();

            if (!name || !email || !subject || !message) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });

        // Initialize first FAQ as open
        document.querySelector('.faq-item')?.classList.add('active');
    </script>
</body>

</html>