<?php
// Include direct access protection
define('INCLUDED', true);

// Include authentication check for admin
require_once '../includes/admin_auth.php';
require_once '../includes/db_connect.php';

// Get application ID from URL
$application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch application details with user information
$query = "SELECT a.*, u.first_name, u.last_name, u.email, u.phone, u.created_at as registration_date
          FROM applications a 
          JOIN users u ON a.user_id = u.id 
          WHERE a.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit;
}

$application = $result->fetch_assoc();

// Fetch application sections
$sections_query = "SELECT * FROM application_sections WHERE application_id = ?";
$sections_stmt = $conn->prepare($sections_query);
$sections_stmt->bind_param("i", $application_id);
$sections_stmt->execute();
$sections_result = $sections_stmt->get_result();
$sections = [];
while ($section = $sections_result->fetch_assoc()) {
    $sections[] = $section;
}

// Fetch uploaded documents
$documents_query = "SELECT * FROM documents WHERE application_id = ?";
$documents_stmt = $conn->prepare($documents_query);
$documents_stmt->bind_param("i", $application_id);
$documents_stmt->execute();
$documents_result = $documents_stmt->get_result();
$documents = [];
while ($document = $documents_result->fetch_assoc()) {
    $documents[] = $document;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>RIN Admin - View Application</title>
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
    <link href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css" rel="stylesheet">
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
                    <a href="dashboard.php" class="nav-item nav-link">Dashboard</a>
                    <a href="applications.php" class="nav-item nav-link active">Applications</a>
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
                        <h2><i class="fas fa-file-alt me-2"></i>Application Details</h2>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb justify-content-md-end mb-0">
                                <li class="breadcrumb-item"><a href="dashboard.php" class="text-white">Home</a></li>
                                <li class="breadcrumb-item"><a href="dashboard.php" class="text-white">Applications</a></li>
                                <li class="breadcrumb-item active text-white" aria-current="page">View Application</li>
                            </ol>
                        </nav>
                        <?php if($application['status'] == 'pending'): ?>
                        <div class="mt-2">
                            <button type="button" class="btn btn-light btn-sm me-2" onclick="approveApplication(<?php echo $application_id; ?>)">
                                <i class="fas fa-check-circle me-1"></i>Approve
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="rejectApplication(<?php echo $application_id; ?>)">
                                <i class="fas fa-times-circle me-1"></i>Reject
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- Header End -->

        <!-- Application Details Start -->
        <div class="container-xxl py-3">
            <div class="container">
                <div class="row g-3">
                    <!-- Application Status Card -->
                    <div class="col-md-4">
                        <div class="bg-light rounded p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Status</h5>
                                <span class="badge bg-<?php 
                                    echo match($application['status']) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'pending' => 'warning',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($application['status']); ?>
                                </span>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar" role="progressbar" 
                                    style="width: <?php echo $application['progress']; ?>%"
                                    aria-valuenow="<?php echo $application['progress']; ?>" 
                                    aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <div class="application-meta">
                                <div class="d-flex justify-content-between py-2 border-bottom">
                                    <small class="text-muted">Application ID:</small>
                                    <span class="fw-medium"><?php echo htmlspecialchars($application['application_id']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between py-2 border-bottom">
                                    <small class="text-muted">Submitted:</small>
                                    <span class="fw-medium"><?php echo date('M d, Y', strtotime($application['created_at'])); ?></span>
                                </div>
                                <div class="d-flex justify-content-between py-2">
                                    <small class="text-muted">Last Updated:</small>
                                    <span class="fw-medium"><?php echo date('M d, Y', strtotime($application['updated_at'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Applicant Information -->
                        <div class="bg-light rounded p-3 mt-3">
                            <h5 class="mb-3"><i class="fas fa-user me-2"></i>Applicant Details</h5>
                            <div class="applicant-meta">
                                <div class="mb-2">
                                    <small class="text-muted d-block">Full Name</small>
                                    <span class="fw-medium"><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted d-block">Email Address</small>
                                    <span class="fw-medium"><?php echo htmlspecialchars($application['email']); ?></span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted d-block">Phone Number</small>
                                    <span class="fw-medium"><?php echo htmlspecialchars($application['phone']); ?></span>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Registration Date</small>
                                    <span class="fw-medium"><?php echo date('M d, Y', strtotime($application['registration_date'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Application Sections -->
                    <div class="col-md-8">
                        <div class="bg-light rounded p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Application Sections</h5>
                                <span class="badge bg-primary"><?php 
                                    $completed = array_filter($sections, function($section) { return $section['is_completed']; });
                                    echo count($completed) . '/' . count($sections); 
                                ?> Completed</span>
                            </div>
                            <div class="application-timeline">
                                <?php foreach($sections as $section): ?>
                                <div class="timeline-item <?php echo $section['is_completed'] ? 'completed' : 'pending'; ?> mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="timeline-icon me-3">
                                            <i class="fas <?php echo $section['is_completed'] ? 'fa-check-circle text-success' : 'fa-clock text-warning'; ?>"></i>
                                        </div>
                                        <div class="timeline-content flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($section['section_name']); ?></h6>
                                                <?php if($section['is_completed']): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewSectionDetails(<?php echo $section['id']; ?>)">
                                                    <i class="fas fa-eye me-1"></i>View Details
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-<?php echo $section['is_completed'] ? 'success' : 'warning'; ?>">
                                                <?php echo $section['is_completed'] ? 'Completed' : 'Pending'; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Uploaded Documents -->
                        <div class="bg-light rounded p-3 mt-3">
                            <h5 class="mb-3"><i class="fas fa-file-alt me-2"></i>Documents</h5>
                            <div class="row g-3">
                                <?php foreach($documents as $document): ?>
                                <div class="col-sm-6">
                                    <div class="document-card border rounded p-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-alt fa-lg text-primary me-2"></i>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 text-truncate"><?php echo htmlspecialchars($document['document_type']); ?></h6>
                                                <small class="text-muted d-block">Uploaded: <?php echo date('M d, Y', strtotime($document['uploaded_at'])); ?></small>
                                            </div>
                                            <div class="ms-2">
                                                <a href="../uploads/<?php echo htmlspecialchars($document['file_path']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="../uploads/<?php echo htmlspecialchars($document['file_path']); ?>" 
                                                   class="btn btn-sm btn-outline-secondary" download>
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Application Details End -->
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
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>

    <!-- Section Details Modal -->
    <div class="modal fade" id="sectionDetailsModal" tabindex="-1" aria-labelledby="sectionDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sectionDetailsModalLabel">Section Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="sectionDetailsContent">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

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
        // Function to handle application approval
        function approveApplication(id) {
            if(confirm('Are you sure you want to approve this application?')) {
                $.post('actions/update-status.php', {
                    id: id,
                    status: 'approved'
                }, function(response) {
                    if(response.success) {
                        location.reload();
                    } else {
                        alert('Error updating application status');
                    }
                });
            }
        }

        // Function to handle application rejection
        function rejectApplication(id) {
            if(confirm('Are you sure you want to reject this application?')) {
                $.post('actions/update-status.php', {
                    id: id,
                    status: 'rejected'
                }, function(response) {
                    if(response.success) {
                        location.reload();
                    } else {
                        alert('Error updating application status');
                    }
                });
            }
        }

        // Function to handle section details view
        function viewSectionDetails(sectionId) {
            // Show loading state in modal
            $('#sectionDetailsModal').modal('show');
            $('#sectionDetailsContent').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);

            // Fetch section details using AJAX
            $.ajax({
                url: 'actions/get-section-details.php',
                method: 'GET',
                data: { section_id: sectionId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        let content = '<div class="section-details">';
                        
                        // Section header with status badge
                        content += `
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">${response.data.section_name}</h5>
                                <span class="badge bg-${response.data.is_completed ? 'success' : 'warning'}">
                                    ${response.data.is_completed ? 'Completed' : 'Pending'}
                                </span>
                            </div>
                        `;
                        
                        // Section metadata
                        content += `
                            <div class="mb-4">
                                <small class="text-muted">Last Updated: ${response.data.updated_at}</small>
                            </div>
                        `;
                        
                        // Section fields in table format
                        if (response.data.fields && response.data.fields.length > 0) {
                            content += '<div class="table-responsive">';
                            content += '<table class="table table-bordered table-striped">';
                            content += '<thead><tr><th style="width: 30%">Field</th><th>Value</th></tr></thead>';
                            content += '<tbody>';
                            response.data.fields.forEach(field => {
                                content += `
                                    <tr>
                                        <td class="fw-medium">${field.label}</td>
                                        <td>${field.value || '<span class="text-muted">Not provided</span>'}</td>
                                    </tr>
                                `;
                            });
                            content += '</tbody></table></div>';
                        } else {
                            content += `
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>No fields found for this section.
                                </div>
                            `;
                        }
                        
                        content += '</div>';
                        $('#sectionDetailsContent').html(content);
                    } else {
                        $('#sectionDetailsContent').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>Error:</strong> ${response.message || 'Failed to load section details'}
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    $('#sectionDetailsContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Error:</strong> Failed to load section details<br>
                            <small class="text-muted">Status: ${status}<br>Error: ${error}</small>
                        </div>
                    `);
                }
            });
        }

        // Initialize any DataTables if present
        $(document).ready(function() {
            if ($.fn.DataTable) {
                $('.table').DataTable({
                    responsive: true,
                    pageLength: 10,
                    order: [[0, 'desc']]
                });
            }
        });
    </script>
</body>
</html>