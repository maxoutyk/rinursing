<?php
// Include direct access protection
define('INCLUDED', true);

// Include authentication check for admin
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';

// Fetch all applications with user details
$query = "SELECT a.*, u.first_name, u.last_name, u.email, u.phone,
          (SELECT COUNT(*) FROM application_sections WHERE application_id = a.id AND is_completed = 1) as completed_sections,
          (SELECT COUNT(*) FROM application_sections WHERE application_id = a.id) as total_sections
          FROM applications a 
          JOIN users u ON a.user_id = u.id 
          ORDER BY a.created_at DESC
          LIMIT 10";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>RIN Admin - Applications Dashboard</title>
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

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>

<body class="d-flex flex-column min-vh-100">
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    <!-- Main Content Wrapper -->
    <div class="content-wrapper flex-grow-1">
        <!-- Navbar Start -->
        <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
            <a href="dashboard.php" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
                <h2 class="m-0 text-primary">RIN Admin</h2>
            </a>
            <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto p-4 p-lg-0">
                    <a href="dashboard.php" class="nav-item nav-link active">Dashboard</a>
                    <a href="applications.php" class="nav-item nav-link">Applications</a>
                    <a href="users.php" class="nav-item nav-link">Users</a>
                    <a href="settings.php" class="nav-item nav-link">Settings</a>
                    <a href="logout.php" class="nav-item nav-link text-danger">Logout</a>
                </div>
            </div>
        </nav>
        <!-- Navbar End -->

        <!-- Header Start -->
        <div class="portal-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2><i class="fas fa-tachometer-alt me-2"></i>Applications Dashboard</h2>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb justify-content-md-end mb-0">
                                <li class="breadcrumb-item"><a href="dashboard.php" class="text-white">Home</a></li>
                                <li class="breadcrumb-item active text-white" aria-current="page">Applications</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- Header End -->

        <!-- Dashboard Stats Start -->
        <div class="container-xxl py-4">
            <div class="container">
                <div class="row g-3">
                    <div class="col-3 wow fadeInUp" data-wow-delay="0.1s">
                        <div class="fact-item bg-light rounded text-center h-100">
                            <i class="fa fa-file-alt text-primary"></i>
                            <h1 class="display-4 fw-bold" data-toggle="counter-up">
                                <?php
                                $total_query = "SELECT COUNT(*) as total FROM applications";
                                $total_result = $conn->query($total_query);
                                $total_row = $total_result->fetch_assoc();
                                echo $total_row['total'];
                                ?>
                            </h1>
                            <p>Total</p>
                        </div>
                    </div>
                    <div class="col-3 wow fadeInUp" data-wow-delay="0.3s">
                        <div class="fact-item bg-light rounded text-center h-100">
                            <i class="fa fa-clock text-primary"></i>
                            <h1 class="display-4 fw-bold" data-toggle="counter-up">
                                <?php
                                $pending_query = "SELECT COUNT(*) as pending FROM applications WHERE status = 'pending'";
                                $pending_result = $conn->query($pending_query);
                                $pending_row = $pending_result->fetch_assoc();
                                echo $pending_row['pending'];
                                ?>
                            </h1>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="col-3 wow fadeInUp" data-wow-delay="0.5s">
                        <div class="fact-item bg-light rounded text-center h-100">
                            <i class="fa fa-check-circle text-primary"></i>
                            <h1 class="display-4 fw-bold" data-toggle="counter-up">
                                <?php
                                $approved_query = "SELECT COUNT(*) as approved FROM applications WHERE status = 'approved'";
                                $approved_result = $conn->query($approved_query);
                                $approved_row = $approved_result->fetch_assoc();
                                echo $approved_row['approved'];
                                ?>
                            </h1>
                            <p>Approved</p>
                        </div>
                    </div>
                    <div class="col-3 wow fadeInUp" data-wow-delay="0.7s">
                        <div class="fact-item bg-light rounded text-center h-100">
                            <i class="fa fa-times-circle text-primary"></i>
                            <h1 class="display-4 fw-bold" data-toggle="counter-up">
                                <?php
                                $rejected_query = "SELECT COUNT(*) as rejected FROM applications WHERE status = 'rejected'";
                                $rejected_result = $conn->query($rejected_query);
                                $rejected_row = $rejected_result->fetch_assoc();
                                echo $rejected_row['rejected'];
                                ?>
                            </h1>
                            <p>Rejected</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Dashboard Stats End -->

        <!-- Recent Applications Start -->
        <div class="container-xxl py-3">
            <div class="container">
                <div class="bg-light rounded p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Recent Applications</h5>
                            <small class="text-muted">Showing latest application submissions</small>
                        </div>
                        <div>
                            <a href="applications.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-external-link-alt me-1"></i>View All
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="applicationsTable" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Applicant</th>
                                    <th>Contact</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): 
                                    $progress = ($row['total_sections'] > 0) 
                                        ? round(($row['completed_sections'] / $row['total_sections']) * 100) 
                                        : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="fw-medium"><?php echo htmlspecialchars($row['application_id']); ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="fw-medium"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-phone-alt text-muted me-2"></i>
                                            <?php echo htmlspecialchars($row['phone']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar" role="progressbar" 
                                                    style="width: <?php echo $progress; ?>%"
                                                    aria-valuenow="<?php echo $progress; ?>" 
                                                    aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                            <span class="ms-2 small"><?php echo $progress; ?>%</span>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo $row['completed_sections'] . '/' . $row['total_sections']; ?> sections
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($row['status']) {
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'pending' => 'warning',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                                            <small class="text-muted"><?php echo date('h:i A', strtotime($row['created_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="view-application.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if($row['status'] == 'pending'): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-success" 
                                                    onclick="updateStatus(<?php echo $row['id']; ?>, 'approved')"
                                                    title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    onclick="updateStatus(<?php echo $row['id']; ?>, 'rejected')"
                                                    title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Recent Applications End -->
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

    <!-- Back to Top -->
    <a href="#" class="btn btn-sm btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../lib/wow/wow.min.js"></script>
    <script src="../lib/easing/easing.min.js"></script>
    <script src="../lib/waypoints/waypoints.min.js"></script>
    <script src="../lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>

    <!-- Template Javascript -->
    <script src="../js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable with responsive feature
            $('#applicationsTable').DataTable({
                responsive: true,
                order: [[5, 'desc']], // Sort by submitted date by default
                pageLength: 10,
                language: {
                    search: "Search applications:"
                },
                columnDefs: [
                    { responsivePriority: 1, targets: [0, 1, 4, 6] }, // These columns will be prioritized on smaller screens
                    { responsivePriority: 2, targets: [2, 3] },
                    { responsivePriority: 3, targets: 5 }
                ]
            });
        });

        // Function to handle status updates
        function updateStatus(id, status) {
            if(confirm('Are you sure you want to ' + status + ' this application?')) {
                $.post('actions/update-status.php', {
                    id: id,
                    status: status
                }, function(response) {
                    if(response.success) {
                        location.reload();
                    } else {
                        alert('Error updating application status');
                    }
                });
            }
        }
    </script>
</body>
</html>
<?php
// Close the database connection
$conn->close();
?> 