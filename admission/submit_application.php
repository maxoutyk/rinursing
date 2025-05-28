<?php
session_start();
require_once 'includes/db_connect.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to submit your application.']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get application ID
    $userId = $_SESSION['user_id'];
    $query = "SELECT id FROM applications WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        throw new Exception('Application not found.');
    }
    
    $application = $result->fetch_assoc();
    $applicationId = $application['id'];

    // Save declaration data
    $guardianName = $_POST['parentName'] ?? null;
    $guardianAddress = $_POST['parentAddress'] ?? null;
    $policeStation = $_POST['policeStation'] ?? null;
    $guardianDate = $_POST['guardianDeclarationDate'] ?? null;
    $guardianConsent = isset($_POST['guardianSignatureConsent']) ? 1 : 0;
    $candidateDate = $_POST['candidateDeclarationDate'] ?? null;
    $candidatePlace = $_POST['candidateDeclarationPlace'] ?? null;
    $candidateConsent = isset($_POST['candidateSignatureConsent']) ? 1 : 0;

    // Insert or update declaration
    $declarationQuery = "INSERT INTO declarations 
        (application_id, guardian_name, guardian_address, guardian_police_station, 
         guardian_declaration_date, guardian_consent, candidate_declaration_date, 
         candidate_declaration_place, candidate_consent, agreed_terms, agreed_authenticity, agreed_rules) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, 1)
        ON DUPLICATE KEY UPDATE 
        guardian_name = VALUES(guardian_name),
        guardian_address = VALUES(guardian_address),
        guardian_police_station = VALUES(guardian_police_station),
        guardian_declaration_date = VALUES(guardian_declaration_date),
        guardian_consent = VALUES(guardian_consent),
        candidate_declaration_date = VALUES(candidate_declaration_date),
        candidate_declaration_place = VALUES(candidate_declaration_place),
        candidate_consent = VALUES(candidate_consent),
        agreed_terms = 1,
        agreed_authenticity = 1,
        agreed_rules = 1";

    $stmt = $conn->prepare($declarationQuery);
    $stmt->bind_param("isssssssi", 
        $applicationId, $guardianName, $guardianAddress, $policeStation,
        $guardianDate, $guardianConsent, $candidateDate, 
        $candidatePlace, $candidateConsent
    );
    $stmt->execute();

    // Save witness data
    // Witness 1
    $witness1Query = "INSERT INTO witnesses (application_id, witness_number, name, father_name, address)
                     VALUES (?, 1, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                     name = VALUES(name),
                     father_name = VALUES(father_name),
                     address = VALUES(address)";
    
    $stmt = $conn->prepare($witness1Query);
    $stmt->bind_param("isss", 
        $applicationId, 
        $_POST['witness1Name'], 
        $_POST['witness1FatherName'],
        $_POST['witness1Address']
    );
    $stmt->execute();

    // Witness 2
    $witness2Query = "INSERT INTO witnesses (application_id, witness_number, name, father_name, address)
                     VALUES (?, 2, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                     name = VALUES(name),
                     father_name = VALUES(father_name),
                     address = VALUES(address)";
    
    $stmt = $conn->prepare($witness2Query);
    $stmt->bind_param("isss", 
        $applicationId, 
        $_POST['witness2Name'], 
        $_POST['witness2FatherName'],
        $_POST['witness2Address']
    );
    $stmt->execute();

    // Update application status to submitted
    $updateQuery = "UPDATE applications 
                   SET status = 'submitted', 
                       submitted_at = NOW(),
                       last_updated = NOW()
                   WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();

    // Add notification
    $notificationQuery = "INSERT INTO notifications (user_id, title, message)
                         VALUES (?, 'Application Submitted Successfully', 
                         'Your application has been submitted successfully and is now under review.')";
    $stmt = $conn->prepare($notificationQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully! Redirecting to dashboard...',
        'redirect' => 'dashboard.php?submission=success'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting application: ' . $e->getMessage()
    ]);
}
?> 