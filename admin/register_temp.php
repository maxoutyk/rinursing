<?php
// This is a temporary file for admin registration
// TODO: Remove this file before production deployment

session_start();

// Include database connection
require_once '../includes/db_connect.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'Email address already registered.';
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new admin user
            $insert_query = "INSERT INTO users (first_name, last_name, email, password_hash, is_admin, created_at) 
                           VALUES (?, ?, ?, ?, 1, NOW())";
            
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ssss", $first_name, $last_name, $email, $password_hash);
            
            if ($insert_stmt->execute()) {
                $success = 'Admin account created successfully. You can now login.';
            } else {
                $error = 'Error creating account. Please try again.';
            }
            
            $insert_stmt->close();
        }
        
        $check_stmt->close();
    }
}

// Close database connection at the end of the script
if (isset($conn)) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>RIN Admin - Temporary Registration</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Favicon -->
    <link href="../img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="../lib/animate/animate.min.css" rel="stylesheet">
    <link href="../lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../css/style.css" rel="stylesheet">
    
    <!-- Admin CSS -->
    <link href="css/admin.css" rel="stylesheet">
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    <!-- Registration Form Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="text-center mb-5">
                        <h1 class="mb-3">Create Admin Account</h1>
                        <p class="text-muted">This is a temporary registration page for creating admin accounts.</p>
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Warning: This page should be removed before deploying to production!
                        </div>
                    </div>
                    <div class="bg-light rounded p-4 p-sm-5">
                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                            <hr>
                            <a href="login.php" class="btn btn-success">Go to Login</a>
                        </div>
                        <?php else: ?>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-4">
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               placeholder="John" required
                                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                                        <label for="first_name">First Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-4">
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               placeholder="Doe" required
                                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                                        <label for="last_name">Last Name</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="name@example.com" required
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <label for="email">Email address</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Password" required
                                       pattern=".{8,}" title="Password must be at least 8 characters long">
                                <label for="password">Password</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm Password" required>
                                <label for="confirm_password">Confirm Password</label>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary py-3">
                                    <i class="fas fa-user-plus me-2"></i>Create Admin Account
                                </button>
                                <a href="login.php" class="btn btn-light py-3">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                                </a>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Registration Form End -->

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-12 text-center">
                    <p class="mb-0">&copy; <a class="border-bottom" href="#">Regional Institute of Nursing</a>, All Right Reserved.</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../lib/wow/wow.min.js"></script>
    <script src="../lib/easing/easing.min.js"></script>
    <script src="../lib/waypoints/waypoints.min.js"></script>
    <script src="../lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="../js/main.js"></script>

    <script>
        // Password match validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            var password = document.getElementById('password');
            var confirm = this;
            
            if (password.value !== confirm.value) {
                confirm.setCustomValidity('Passwords do not match');
            } else {
                confirm.setCustomValidity('');
            }
        });
    </script>
</body>
</html> 