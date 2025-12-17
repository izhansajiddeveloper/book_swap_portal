<?php

require_once "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// Handle form submission
if (isset($_POST['add_book'])) {
    $title = ($_POST['title']);
    $author = ($_POST['author']);
    $genre = ($_POST['genre']);
    $condition = isset($_POST['condition']) ? intval($_POST['condition']) : 5;
    $description = ($_POST['description']);

    // Handle image upload
    $image_name = "";
    if(isset($_FILES['book_image']) && $_FILES['book_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = $_FILES['book_image']['type'];
        
        if(in_array($file_type, $allowed_types)) {
            $image_name = time() . '_' . basename($_FILES['book_image']['name']);
            $target_dir = "../uploads/book_images/";
            $target_file = $target_dir . $image_name;
            
            // Create directory if it doesn't exist
            if(!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            if(move_uploaded_file($_FILES['book_image']['tmp_name'], $target_file)) {
                // Image uploaded successfully
            } else {
                $error = "Sorry, there was an error uploading your image.";
            }
        } else {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    }

    if (!empty($title) && !empty($author) && !empty($genre) && $error == "") {
        $sql = "INSERT INTO books (user_id, title, author, genre, book_condition, image, approved, created_at) 
                VALUES ('$user_id', '$title', '$author', '$genre', '$condition',  '$image_name', 'no', NOW())";
        if ($conn->query($sql)) {
            $success = "Book added successfully! Waiting for admin approval.";
        } else {
            $error = "Database error: " . $conn->error;
        }
    } else {
        if($error == "") {
            $error = "Please fill in all required fields.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book - Book Swap Portal</title>
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

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header */
        .form-header {
            margin-bottom: 2.5rem;
        }

        .form-title {
            font-size: 2.2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .form-title i {
            color: #ff9e6d;
            background: linear-gradient(135deg, rgba(255, 158, 109, 0.1), rgba(255, 209, 102, 0.1));
            padding: 12px;
            border-radius: 12px;
        }

        .form-subtitle {
            color: #666;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        /* Messages */
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

        .form-label span {
            color: #ff6b6b;
        }

        .form-control {
            width: 100%;
            padding: 0.9rem 1rem;
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

        /* File Upload */
        .file-upload {
            position: relative;
        }

        .file-upload-input {
            display: none;
        }

        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-label:hover {
            border-color: #4a6491;
            background: #e9ecef;
        }

        .file-upload-label i {
            font-size: 3rem;
            color: #4a6491;
            margin-bottom: 1rem;
        }

        .file-upload-label span {
            color: #666;
            font-weight: 500;
        }

        .file-preview {
            margin-top: 1rem;
            text-align: center;
        }

        .file-preview img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }

        /* Condition Rating */
        .condition-rating {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .condition-option {
            flex: 1;
            text-align: center;
        }

        .condition-option input {
            display: none;
        }

        .condition-label {
            display: block;
            padding: 0.8rem;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .condition-option input:checked + .condition-label {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
            border-color: #4a6491;
        }

        .condition-label:hover {
            border-color: #4a6491;
        }

        /* Textarea */
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* Buttons */
        .form-buttons {
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
            flex: 1;
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

        /* Genre Tags */
        .genre-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .genre-tag {
            padding: 0.3rem 0.8rem;
            background: #e9ecef;
            border: 1px solid #dee2e6;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .genre-tag:hover {
            background: #dee2e6;
        }

        .genre-tag.active {
            background: linear-gradient(135deg, #4a6491, #2c3e50);
            color: white;
            border-color: #4a6491;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-container {
                padding: 1rem;
            }

            .form-card {
                padding: 1.5rem;
            }

            .form-buttons {
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

        .form-card {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>

<body>
    <?php include "../includes/navbar.php"; ?>

    <div class="form-container">
        <!-- Header -->
        <div class="form-header">
            <h1 class="form-title">
                <i class="fas fa-plus-circle"></i>
                Add New Book
            </h1>
            <p class="form-subtitle">Share your books with the community. Your book will be reviewed by admin before being listed.</p>
        </div>

        <!-- Form Card -->
        <div class="form-card">
            <!-- Messages -->
            <?php if ($success != ""): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error != ""): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" id="addBookForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Book Title <span>*</span></label>
                        <input type="text" name="title" class="form-control" required 
                               placeholder="Enter book title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Author <span>*</span></label>
                        <input type="text" name="author" class="form-control" required 
                               placeholder="Enter author name" value="<?php echo isset($_POST['author']) ? htmlspecialchars($_POST['author']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Genre <span>*</span></label>
                    <input type="text" name="genre" class="form-control" required 
                           placeholder="e.g., Fiction, Science, Biography" value="<?php echo isset($_POST['genre']) ? htmlspecialchars($_POST['genre']) : ''; ?>">
                    <div class="genre-tags">
                        <span class="genre-tag" data-genre="Fiction">Fiction</span>
                        <span class="genre-tag" data-genre="Science">Science</span>
                        <span class="genre-tag" data-genre="Biography">Biography</span>
                        <span class="genre-tag" data-genre="History">History</span>
                        <span class="genre-tag" data-genre="Fantasy">Fantasy</span>
                        <span class="genre-tag" data-genre="Mystery">Mystery</span>
                        <span class="genre-tag" data-genre="Romance">Romance</span>
                        <span class="genre-tag" data-genre="Self-Help">Self-Help</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Book Condition <span>*</span></label>
                    <div class="condition-rating">
                        <div class="condition-option">
                            <input type="radio" id="condition1" name="condition" value="1" <?php echo (isset($_POST['condition']) && $_POST['condition'] == 1) ? 'checked' : ''; ?>>
                            <label for="condition1" class="condition-label">Poor (1)</label>
                        </div>
                        <div class="condition-option">
                            <input type="radio" id="condition3" name="condition" value="3" <?php echo (isset($_POST['condition']) && $_POST['condition'] == 3) ? 'checked' : ''; ?>>
                            <label for="condition3" class="condition-label">Fair (3)</label>
                        </div>
                        <div class="condition-option">
                            <input type="radio" id="condition5" name="condition" value="5" <?php echo (isset($_POST['condition']) && $_POST['condition'] == 5) ? 'checked' : ''; ?> checked>
                            <label for="condition5" class="condition-label">Good (5)</label>
                        </div>
                        <div class="condition-option">
                            <input type="radio" id="condition8" name="condition" value="8" <?php echo (isset($_POST['condition']) && $_POST['condition'] == 8) ? 'checked' : ''; ?>>
                            <label for="condition8" class="condition-label">Very Good (8)</label>
                        </div>
                        <div class="condition-option">
                            <input type="radio" id="condition10" name="condition" value="10" <?php echo (isset($_POST['condition']) && $_POST['condition'] == 10) ? 'checked' : ''; ?>>
                            <label for="condition10" class="condition-label">Excellent (10)</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Book Cover Image</label>
                    <div class="file-upload">
                        <input type="file" name="book_image" id="book_image" class="file-upload-input" accept="image/*">
                        <label for="book_image" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Click to upload book cover</span>
                            <small style="color: #888; margin-top: 0.5rem;">Max size: 2MB • JPG, PNG, GIF</small>
                        </label>
                    </div>
                    <div class="file-preview" id="filePreview"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" 
                              placeholder="Tell us about the book, any notes, or special features..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="form-buttons">
                    <button type="submit" name="add_book" class="btn btn-primary">
                        <i class="fas fa-book-medical"></i>
                        Add Book for Review
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>

                <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 10px; border-left: 4px solid #4a6491;">
                    <small style="color: #666;">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Note:</strong> All books go through admin approval before being listed. 
                        You'll be notified once your book is approved.
                    </small>
                </div>
            </form>
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>

    <script>
        // File preview
        document.getElementById('book_image').addEventListener('change', function(e) {
            const preview = document.getElementById('filePreview');
            preview.innerHTML = '';
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Genre tag selection
        document.querySelectorAll('.genre-tag').forEach(tag => {
            tag.addEventListener('click', function() {
                const genreInput = document.querySelector('input[name="genre"]');
                genreInput.value = this.dataset.genre;
                
                // Highlight selected tag
                document.querySelectorAll('.genre-tag').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Form validation
        document.getElementById('addBookForm').addEventListener('submit', function(e) {
            const title = document.querySelector('input[name="title"]').value.trim();
            const author = document.querySelector('input[name="author"]').value.trim();
            const genre = document.querySelector('input[name="genre"]').value.trim();
            
            if (!title || !author || !genre) {
                e.preventDefault();
                alert('Please fill in all required fields (marked with *).');
                return false;
            }
            
            return true;
        });

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