<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type header
header('Content-Type: application/json');

// Include database connection
require_once 'db_connect.php';

try {
    // Fetch important dates from database
    $query = "SELECT title, event_date, status FROM important_dates WHERE YEAR(event_date) = YEAR(CURRENT_TIMESTAMP) ORDER BY event_date ASC";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $dates = [];
    while ($row = $result->fetch_assoc()) {
        // Determine status based on date
        $dateObj = new DateTime($row['event_date']);
        $today = new DateTime();
        
        if ($today > $dateObj) {
            $status = 'passed';
        } elseif ($today->format('Y-m-d') === $dateObj->format('Y-m-d')) {
            $status = 'today';
        } else {
            $status = 'upcoming';
        }
        
        $dates[] = [
            'title' => $row['title'],
            'date' => $row['event_date'],
            'status' => $status
        ];
    }
    
    // If no dates found, add default dates
    if (empty($dates)) {
        $currentYear = date('Y');
        $dates = [
            [
                'title' => 'Application Start',
                'date' => "$currentYear-06-01",
                'status' => 'upcoming'
            ],
            [
                'title' => 'Application Deadline',
                'date' => "$currentYear-07-31",
                'status' => 'upcoming'
            ],
            [
                'title' => 'Document Verification',
                'date' => "$currentYear-08-15",
                'status' => 'upcoming'
            ],
            [
                'title' => 'Entrance Examination',
                'date' => "$currentYear-08-30",
                'status' => 'upcoming'
            ],
            [
                'title' => 'Result Declaration',
                'date' => "$currentYear-09-15",
                'status' => 'upcoming'
            ],
            [
                'title' => 'Admission Confirmation',
                'date' => "$currentYear-09-30",
                'status' => 'upcoming'
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $dates
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_important_dates.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching important dates: ' . $e->getMessage()
    ]);
}

// Close the database connection
$conn->close();
?> 