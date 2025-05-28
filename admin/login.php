<?php
// Start session
session_start();

// If already logged in as admin, redirect to dashboard
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: dashboard.php");
    exit;
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../admission/includes/db_connect.php';
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            // Check credentials
            $query = "SELECT id, password_hash, first_name, last_name, is_admin 
                     FROM users 
                     WHERE email = ? AND is_admin = 1";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }

            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                throw new Exception("Query execution failed: " . $stmt->error);
            }

            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password_hash'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['is_admin'] = true;
                    $_SESSION['admin_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    
                    // Clean up
                    $stmt->close();
                    
                    // Redirect to requested page or dashboard
                    $redirect = isset($_SESSION['redirect_after_login']) 
                        ? $_SESSION['redirect_after_login'] 
                        : 'dashboard.php';
                    unset($_SESSION['redirect_after_login']);
                    
                    header("Location: " . $redirect);
                    exit;
                }
            }
            
            $error = 'Invalid email or password.';
            $stmt->close();

        } catch (Exception $e) {
            $error = "Login failed: " . $e->getMessage();
            if (isset($stmt)) {
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>RIN Admin - Login</title>
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

    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .main-content {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .footer {
            flex-shrink: 0;
        }
        .login-container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        @media (max-width: 576px) {
            .login-container {
                padding: 10px;
            }
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    <!-- Content Wrapper -->
    <div class="content-wrapper flex-grow-1">
        <!-- Login Form Start -->
        <div class="container-xxl py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-5">
                        <div class="bg-light rounded p-4 p-sm-5">
                            <div class="text-center mb-4">
                                <h3 class="mb-2">Admin Login</h3>
                                <p class="text-muted">Sign in to access admin panel</p>
                            </div>
                            <?php if(!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                            <?php endif; ?>
                            <form action="login.php" method="POST">
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                                    <label for="email">Email address</label>
                                </div>
                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                    <label for="password">Password</label>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary py-3">Login</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Login Form End -->
    </div>

    <!-- Footer Start -->
    <footer class="footer bg-dark text-light py-2 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <small class="mb-0">&copy; <?php echo date('Y'); ?> Regional Institute of Nursing. All rights reserved.</small>
                </div>
            </div>
        </div>
    </footer>
    <!-- Footer End -->

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../lib/wow/wow.min.js"></script>
    <script src="../lib/easing/easing.min.js"></script>
    <script src="../lib/waypoints/waypoints.min.js"></script>
    <script src="../lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="../js/main.js"></script>
</body>
</html> 