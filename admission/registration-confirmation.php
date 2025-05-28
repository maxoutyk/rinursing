<?php
// Start session
session_start();

// Define constant to indicate this file was included properly
define('INCLUDED', true);

// Check if user came from registration process
if (!isset($_SESSION['registration_status'])) {
    // If no registration status in session, redirect to registration page
    header('Location: register.php');
    exit;
}

// Get status from session
$status = $_SESSION['registration_status'];

// Clear the session data after retrieving it
unset($_SESSION['registration_status']);

// Make status available to the HTML template
$statusJson = json_encode($status);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Regional Institute of Nursing - Registration Confirmation</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Registration Confirmation" name="keywords">
    <meta content="Registration Confirmation for Regional Institute of Nursing" name="description">

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
    
    <!-- Admission CSS -->
    <link href="css/admission.css" rel="stylesheet">
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
        <a href="../index.html" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
            <img class="d-none d-md-block d-lg-block" src="../img/rin_logo_new.png" width="75" height="73" alt="Logo">
            <h2 class="m-0 text-primary">Regional Institute of Nursing</h2>
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                <a href="../index.html" class="nav-item nav-link">Home</a>
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">About</a>
                    <div class="dropdown-menu fade-down m-0">
                        <a href="../about.html" class="dropdown-item">About Us</a>
                        <a href="../about.html#Objectives" class="dropdown-item">Objectives</a>
                        <a href="../about.html#Vision" class="dropdown-item">Vision</a>
                        <a href="../about.html#Mission" class="dropdown-item">Mission</a>
                        <a href="../about.html#Features" class="dropdown-item">Programme Features</a>
                        <a href="../index.html#chairperson" class="dropdown-item">Chairperson</a>
                        <a href="../index.html#principal" class="dropdown-item">Principal</a>
                    </div>
                </div>
                <a href="../facilities.html" class="nav-item nav-link">Facilities</a>
                <a href="../courses.html" class="nav-item nav-link">Courses</a>
                <a href="../admission.html" class="nav-item nav-link active">Admission</a>
                <a href="../fees.html" class="nav-item nav-link">Fees</a>
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Connect</a>
                    <div class="dropdown-menu fade-down m-0">
                        <a href="../news.html" class="dropdown-item">News & Events</a>
                        <a href="../docs/prospectus.pdf" target="_blank" class="dropdown-item">Prospectus</a>
                        <a href="../photo-gallery.html" class="dropdown-item">Photo Gallery</a>
                        <a href="../video-gallery.html" class="dropdown-item">Video Gallery</a>
                        <a href="#" class="dropdown-item">Downloads</a>
                    </div>
                </div>
                <a href="../contact.html" class="nav-item nav-link">Contact</a>
            </div>
        </div>
    </nav>
    <!-- Navbar End -->

    <!-- Portal Header Start -->
    <div class="portal-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2><i class="fas fa-check-circle me-2"></i>Registration Status</h2>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="text-white">Already have an account? <a href="login.php" class="text-white text-decoration-underline">Sign In</a></span>
                </div>
            </div>
        </div>
    </div>
    <!-- Portal Header End -->

    <!-- Confirmation Content Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-4 justify-content-center">
                <div class="col-lg-8 col-md-10 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="confirmation-container text-center">
                        <div class="confirmation-message">
                            <!-- Status message will be inserted here by JavaScript -->
                        </div>
                        <div class="mt-5">
                            <a href="login.php" class="btn btn-primary btn-proceed">
                                <i class="fas fa-sign-in-alt me-2"></i>Proceed to Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Confirmation Content End -->

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Quick Link</h4>
                    <a class="btn btn-link" href="../about.html">About Us</a>
                    <a class="btn btn-link" href="../contact.html">Contact Us</a>
                    <a class="btn btn-link" href="../courses.html">Our Courses</a>
                    <a class="btn btn-link" href="../admission.html">Admission</a>
                    <a class="btn btn-link" href="../facilities.html">Facilities</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Contact</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>G-Extension, Near Model English School, Itanagar, Arunachal Pradesh</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+91 9862245330, +91 9208922995</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>regionalinstituteofnursing@gmail.com</p>
                    <div class="d-flex pt-2">
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Gallery</h4>
                    <div class="row g-2 pt-2">
                        <div class="col-4">
                            <img class="img-fluid bg-light p-1" src="../img/course-1.jpg" alt="">
                        </div>
                        <div class="col-4">
                            <img class="img-fluid bg-light p-1" src="../img/course-2.jpg" alt="">
                        </div>
                        <div class="col-4">
                            <img class="img-fluid bg-light p-1" src="../img/course-3.jpg" alt="">
                        </div>
                        <div class="col-4">
                            <img class="img-fluid bg-light p-1" src="../img/course-2.jpg" alt="">
                        </div>
                        <div class="col-4">
                            <img class="img-fluid bg-light p-1" src="../img/course-3.jpg" alt="">
                        </div>
                        <div class="col-4">
                            <img class="img-fluid bg-light p-1" src="../img/course-1.jpg" alt="">
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Newsletter</h4>
                    <p>Subscribe to our newsletter for updates</p>
                    <div class="position-relative mx-auto" style="max-width: 400px;">
                        <input class="form-control border-0 w-100 py-3 ps-4 pe-5" type="text" placeholder="Your email">
                        <button type="button" class="btn btn-primary py-2 position-absolute top-0 end-0 mt-2 me-2">SignUp</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; <a class="border-bottom" href="#">Regional Institute of Nursing</a>, All Right Reserved.
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <div class="footer-menu">
                            <a href="../index.html">Home</a>
                            <a href="#">Cookies</a>
                            <a href="#">Help</a>
                            <a href="#">FQAs</a>
                        </div>
                    </div>
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
    // Handle registration status message
    document.addEventListener('DOMContentLoaded', function() {
        const status = <?php echo $statusJson; ?>;
        const messageDiv = document.querySelector('.confirmation-message');
        
        if (status.success) {
            messageDiv.innerHTML = `
                <div class="confirmation-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="alert alert-success" role="alert">
                    <h4 class="alert-heading mb-3">Registration Successful!</h4>
                    <p class="mb-0">${status.message}</p>
                </div>`;
        } else {
            messageDiv.innerHTML = `
                <div class="confirmation-icon error">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="alert alert-danger" role="alert">
                    <h4 class="alert-heading mb-3">Registration Failed</h4>
                    <p>${status.message}</p>`;
            
            if (status.errors && status.errors.length > 0) {
                messageDiv.innerHTML += `
                    <ul class="mt-3 text-start">
                        ${status.errors.map(error => `<li>${error}</li>`).join('')}
                    </ul>`;
            }
            
            messageDiv.innerHTML += '</div>';
        }
    });
    </script>
</body>
</html> 