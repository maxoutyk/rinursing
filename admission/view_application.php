<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/required_auth.php';

// Get application ID from URL
$application_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];

// Initialize variables
$application_data = null;
$personal_details = null;
$contact_details = null;
$education_details = null;
$documents = null;
$error_message = null;
$userData = null;

// First fetch user data
try {
    $user_query = "SELECT first_name, last_name, email, last_login FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $userId);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result && $user_result->num_rows > 0) {
        $userData = $user_result->fetch_assoc();
    } else {
        $error_message = "User data not found.";
    }
    $user_stmt->close();
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    $error_message = "An error occurred while fetching user data.";
}

try {
    // First verify this application belongs to the logged-in user
    $verify_query = "SELECT * FROM applications WHERE id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $application_id, $userId);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error_message = "Application not found or you don't have permission to view it.";
    } else {
        $application_data = $result->fetch_assoc();
        
        // Fetch personal details
        $personal_query = "SELECT * FROM personal_details WHERE application_id = ?";
        $personal_stmt = $conn->prepare($personal_query);
        $personal_stmt->bind_param("i", $application_id);
        $personal_stmt->execute();
        $personal_details = $personal_stmt->get_result()->fetch_assoc();
        
        // Fetch addresses (permanent and present)
        $addresses_query = "SELECT * FROM addresses WHERE application_id = ? ORDER BY type";
        $addresses_stmt = $conn->prepare($addresses_query);
        $addresses_stmt->bind_param("i", $application_id);
        $addresses_stmt->execute();
        $addresses_result = $addresses_stmt->get_result();
        $addresses = [];
        while ($row = $addresses_result->fetch_assoc()) {
            $addresses[$row['type']] = $row;
        }
        
        // Fetch education details
        $education_query = "SELECT 
            level,
            board_university,
            year_of_passing,
            percentage,
            subjects,
            remarks,
            mode
            FROM education 
            WHERE application_id = ? 
            ORDER BY FIELD(level, '10th', '12th', 'graduation', 'other')";
        $education_stmt = $conn->prepare($education_query);
        $education_stmt->bind_param("i", $application_id);
        $education_stmt->execute();
        $education_result = $education_stmt->get_result();
        $education_details = [];
        while ($row = $education_result->fetch_assoc()) {
            $education_details[] = $row;
        }
        
        // Fetch uploaded documents
        $documents_query = "SELECT * FROM documents WHERE application_id = ?";
        $documents_stmt = $conn->prepare($documents_query);
        $documents_stmt->bind_param("i", $application_id);
        $documents_stmt->execute();
        $documents_result = $documents_stmt->get_result();
        $documents = [];
        while ($row = $documents_result->fetch_assoc()) {
            $documents[] = $row;
        }
    }
} catch (Exception $e) {
    $error_message = "An error occurred while fetching application details.";
    error_log("Error in view_application.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View Application - Regional Institute of Nursing</title>
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
    
    <!-- Admission CSS -->
    <link href="css/admission.css" rel="stylesheet">
    
    <style>
        .application-section {
            margin-bottom: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .application-section .section-header {
            background: #06BBCC;
            color: white;
            padding: 1rem;
            border-radius: 8px 8px 0 0;
        }
        .application-section .section-body {
            padding: 1.5rem;
        }
        .detail-row {
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        .detail-label {
            font-weight: 600;
            color: #444;
        }
        .document-link {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border-radius: 4px;
            margin: 0.25rem;
            color: #444;
            text-decoration: none;
            transition: all 0.3s;
        }
        .document-link:hover {
            background: #e9ecef;
            color: #06BBCC;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 600;
        }
        .print-button {
            background: #06BBCC;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .print-button:hover {
            background: #0aa3b3;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .application-section {
                box-shadow: none;
                margin-bottom: 1rem;
                page-break-inside: avoid;
            }
            .section-header {
                background: #f8f9fa !important;
                color: #000 !important;
            }
        }
    </style>
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
            <h2 class="m-0 text-primary">
                Regional Institute of Nursing
            </h2>
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                <a href="../index.html" class="nav-item nav-link">Home</a>
                <a href="../about.html" class="nav-item nav-link">About</a>
                <a href="../facilities.html" class="nav-item nav-link">Facilities</a>
                <a href="../courses.html" class="nav-item nav-link">Courses</a>
                <a href="../admission.html" class="nav-item nav-link active">Admission</a>
                <a href="../fees.html" class="nav-item nav-link">Fees</a>
                <a href="../contact.html" class="nav-item nav-link">Contact</a>
                <a href="dashboard.php" class="nav-item nav-link">Dashboard</a>
                <a href="includes/logout.php" class="nav-item nav-link text-danger">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    <!-- Navbar End -->

    <!-- Portal Header Start -->
    <div class="portal-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2><i class="fas fa-file-alt me-2"></i>View Application</h2>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="text-white"><i class="fas fa-calendar-alt me-2"></i>Last Login: <span id="last-login-date">Loading...</span></span>
                </div>
            </div>
        </div>
    </div>
    <!-- Portal Header End -->

    <?php if ($error_message): ?>
        <div class="container mt-5">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    <?php else: ?>
        <!-- Application View Container -->
        <div class="container-xxl py-5">
            <div class="container">
                <!-- Header Section -->
                <div class="row mb-4 align-items-center">
                    <div class="col-md-6">
                        <h2><i class="fas fa-file-alt me-2"></i>Application Details</h2>
                        <p class="text-muted mb-0">Application ID: <?php echo htmlspecialchars($application_data['application_id']); ?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <button onclick="window.print()" class="btn btn-primary print-button no-print">
                            <i class="fas fa-print me-2"></i>Print Application
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-primary ms-2 no-print">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>

                <!-- Status Section -->
                <div class="application-section">
                    <div class="section-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Application Status</h5>
                    </div>
                    <div class="section-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="detail-row">
                                    <div class="detail-label">Status</div>
                                    <div class="status-badge bg-<?php echo $application_data['status'] === 'submitted' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($application_data['status'])); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="detail-row">
                                    <div class="detail-label">Submitted On</div>
                                    <div><?php echo $application_data['submitted_at'] ? date('d M Y, h:i A', strtotime($application_data['submitted_at'])) : 'Not submitted'; ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="detail-row">
                                    <div class="detail-label">Created On</div>
                                    <div><?php echo date('d M Y', strtotime($application_data['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="detail-row">
                                    <div class="detail-label">Last Updated</div>
                                    <div><?php echo date('d M Y', strtotime($application_data['last_updated'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal Details Section -->
                <div class="application-section">
                    <div class="section-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Personal Details</h5>
                    </div>
                    <div class="section-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="detail-row">
                                    <div class="detail-label">Full Name</div>
                                    <div><?php echo htmlspecialchars($personal_details['full_name']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-row">
                                    <div class="detail-label">Date of Birth</div>
                                    <div><?php echo date('d M Y', strtotime($personal_details['dob'])); ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-row">
                                    <div class="detail-label">Gender</div>
                                    <div><?php echo ucfirst(htmlspecialchars($personal_details['gender'])); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="detail-row">
                                    <div class="detail-label">Category</div>
                                    <div><?php echo htmlspecialchars($personal_details['category']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-row">
                                    <div class="detail-label">Religion</div>
                                    <div><?php echo htmlspecialchars($personal_details['religion']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-row">
                                    <div class="detail-label">Nationality</div>
                                    <div><?php echo htmlspecialchars($personal_details['nationality']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Details Section -->
                <div class="application-section">
                    <div class="section-header">
                        <h5 class="mb-0"><i class="fas fa-address-book me-2"></i>Contact Details</h5>
                    </div>
                    <div class="section-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-row">
                                    <div class="detail-label">Email</div>
                                    <div><?php echo htmlspecialchars($personal_details['email'] ?? 'Not provided'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-row">
                                    <div class="detail-label">Phone</div>
                                    <div><?php echo htmlspecialchars($personal_details['phone'] ?? 'Not provided'); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Permanent Address -->
                        <?php if (isset($addresses['permanent'])): ?>
                        <div class="address-section mb-4">
                            <h6 class="text-primary mb-3">Permanent Address</h6>
                            <div class="row">
                                <div class="col-12">
                                    <div class="detail-row">
                                        <div class="detail-label">Address</div>
                                        <div>
                                            <?php 
                                                $permanent = $addresses['permanent'];
                                                $address_lines = array_filter([
                                                    $permanent['address_line1'],
                                                    $permanent['address_line2'],
                                                    $permanent['city'],
                                                    $permanent['district'],
                                                    $permanent['state'] . ' - ' . $permanent['pincode'],
                                                    $permanent['country']
                                                ]);
                                                echo nl2br(htmlspecialchars(implode("\n", $address_lines)));
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Present Address -->
                        <?php if (isset($addresses['present'])): ?>
                        <div class="address-section">
                            <h6 class="text-primary mb-3">Present Address</h6>
                            <div class="row">
                                <div class="col-12">
                                    <div class="detail-row">
                                        <div class="detail-label">Address</div>
                                        <div>
                                            <?php 
                                                $present = $addresses['present'];
                                                $address_lines = array_filter([
                                                    $present['address_line1'],
                                                    $present['address_line2'],
                                                    $present['city'],
                                                    $present['district'],
                                                    $present['state'] . ' - ' . $present['pincode'],
                                                    $present['country']
                                                ]);
                                                echo nl2br(htmlspecialchars(implode("\n", $address_lines)));
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Education Details Section -->
                <div class="application-section">
                    <div class="section-header">
                        <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Education Details</h5>
                    </div>
                    <div class="section-body">
                        <?php foreach ($education_details as $education): ?>
                            <div class="education-entry mb-4">
                                <h6 class="text-primary"><?php echo htmlspecialchars($education['level']); ?></h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="detail-row">
                                            <div class="detail-label">Board/University</div>
                                            <div><?php echo htmlspecialchars($education['board_university'] ?? 'Not provided'); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="detail-row">
                                            <div class="detail-label">Year of Passing</div>
                                            <div><?php echo htmlspecialchars($education['year_of_passing'] ?? 'Not provided'); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="detail-row">
                                            <div class="detail-label">Percentage/CGPA</div>
                                            <div><?php echo $education['percentage'] ? htmlspecialchars($education['percentage']) . '%' : 'Not provided'; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="detail-row">
                                            <div class="detail-label">Subjects</div>
                                            <div><?php echo htmlspecialchars($education['subjects'] ?? 'Not provided'); ?></div>
                                        </div>
                                    </div>
                                    <?php if (!empty($education['remarks'])): ?>
                                    <div class="col-12 mt-2">
                                        <div class="detail-row">
                                            <div class="detail-label">Remarks</div>
                                            <div><?php echo htmlspecialchars($education['remarks']); ?></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Documents Section -->
                <div class="application-section">
                    <div class="section-header">
                        <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i>Uploaded Documents</h5>
                    </div>
                    <div class="section-body">
                        <div class="row">
                            <?php foreach ($documents as $document): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="detail-row">
                                        <div class="detail-label"><?php echo htmlspecialchars($document['document_type']); ?></div>
                                        <a href="<?php echo htmlspecialchars($document['file_path']); ?>" target="_blank" class="document-link">
                                            <i class="fas fa-file-pdf me-2"></i>View Document
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

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

    <!-- Last Login Update Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lastLoginDate = document.getElementById('last-login-date');
            if (lastLoginDate) {
                <?php if ($userData && isset($userData['last_login']) && $userData['last_login']): ?>
                    try {
                        lastLoginDate.textContent = new Date('<?php echo $userData['last_login']; ?>').toLocaleString();
                    } catch (e) {
                        console.error('Error formatting date:', e);
                        lastLoginDate.textContent = '<?php echo $userData['last_login']; ?>';
                    }
                <?php else: ?>
                    lastLoginDate.textContent = 'Not available';
                <?php endif; ?>
            }
        });
    </script>
</body>
</html>