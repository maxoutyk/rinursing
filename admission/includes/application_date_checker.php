<?php
/**
 * Application Date Checker
 * 
 * This file provides functions to check application dates and restrictions
 * based on the important dates defined in the system.
 */

class ApplicationDateChecker {
    private $conn;
    private $dates = null;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->loadDates();
    }
    
    /**
     * Load all important dates from the database
     */
    private function loadDates() {
        if ($this->dates === null) {
            $query = "SELECT title, event_date FROM important_dates WHERE YEAR(event_date) = YEAR(CURRENT_TIMESTAMP)";
            $result = $this->conn->query($query);
            
            $this->dates = [];
            while ($row = $result->fetch_assoc()) {
                $this->dates[$row['title']] = new DateTime($row['event_date']);
            }
        }
    }
    
    /**
     * Check if applications are currently open
     */
    public function isApplicationOpen() {
        $today = new DateTime();
        return $today >= $this->dates['Application Start'] && $today <= $this->dates['Application Deadline'];
    }
    
    /**
     * Check if application payment window is open
     */
    public function isPaymentWindowOpen() {
        $today = new DateTime();
        return $today >= $this->dates['Application Deadline'] && $today <= $this->dates['Document Verification'];
    }
    
    /**
     * Check if document verification is allowed
     */
    public function isDocumentVerificationOpen() {
        $today = new DateTime();
        return $today >= $this->dates['Document Verification'] && $today <= $this->dates['Entrance Examination'];
    }
    
    /**
     * Check if entrance exam is scheduled for today
     */
    public function isEntranceExamDay() {
        $today = new DateTime();
        return $today->format('Y-m-d') === $this->dates['Entrance Examination']->format('Y-m-d');
    }
    
    /**
     * Check if results are published
     */
    public function areResultsPublished() {
        $today = new DateTime();
        return $today >= $this->dates['Result Declaration'];
    }
    
    /**
     * Check if admission confirmation is open
     */
    public function isAdmissionConfirmationOpen() {
        $today = new DateTime();
        return $today >= $this->dates['Result Declaration'] && $today <= $this->dates['Admission Confirmation'];
    }
    
    /**
     * Get days remaining for next deadline
     */
    public function getDaysToNextDeadline() {
        $today = new DateTime();
        $nextDeadline = null;
        $nextDeadlineTitle = null;
        
        foreach ($this->dates as $title => $date) {
            if ($date > $today) {
                if ($nextDeadline === null || $date < $nextDeadline) {
                    $nextDeadline = $date;
                    $nextDeadlineTitle = $title;
                }
            }
        }
        
        if ($nextDeadline) {
            $interval = $today->diff($nextDeadline);
            return [
                'title' => $nextDeadlineTitle,
                'days' => $interval->days,
                'date' => $nextDeadline->format('Y-m-d')
            ];
        }
        
        return null;
    }
    
    /**
     * Get current application phase
     */
    public function getCurrentPhase() {
        $today = new DateTime();
        
        if ($today < $this->dates['Application Start']) {
            return 'not_started';
        } elseif ($today <= $this->dates['Application Deadline']) {
            return 'application_submission';
        } elseif ($today <= $this->dates['Application Payment']) {
            return 'payment';
        } elseif ($today <= $this->dates['Document Verification']) {
            return 'document_verification';
        } elseif ($today <= $this->dates['Entrance Examination']) {
            return 'pre_examination';
        } elseif ($today <= $this->dates['Result Declaration']) {
            return 'awaiting_results';
        } elseif ($today <= $this->dates['Admission Confirmation']) {
            return 'admission_confirmation';
        } else {
            return 'closed';
        }
    }
    
    /**
     * Check if a specific action is allowed based on the current phase
     */
    public function isActionAllowed($action) {
        $currentPhase = $this->getCurrentPhase();
        
        $allowedActions = [
            'not_started' => [],
            'application_submission' => ['edit_application', 'submit_application'],
            'payment' => ['make_payment', 'view_application'],
            'document_verification' => ['upload_documents', 'view_application'],
            'pre_examination' => ['download_admit_card', 'view_application'],
            'awaiting_results' => ['view_application'],
            'admission_confirmation' => ['confirm_admission', 'view_application'],
            'closed' => ['view_application']
        ];
        
        return in_array($action, $allowedActions[$currentPhase]);
    }
    
    /**
     * Get error message for why an action is not allowed
     */
    public function getActionRestrictedMessage($action) {
        $currentPhase = $this->getCurrentPhase();
        $nextDeadline = $this->getDaysToNextDeadline();
        
        $messages = [
            'not_started' => "Applications are not yet open. Applications will start from " . 
                           $this->dates['Application Start']->format('d M Y'),
            'application_submission' => "Application submission is currently open. Deadline: " . 
                                     $this->dates['Application Deadline']->format('d M Y'),
            'payment' => "Only payment submission is allowed at this time. Deadline: " . 
                       $this->dates['Application Payment']->format('d M Y'),
            'document_verification' => "Document verification phase is ongoing until " . 
                                    $this->dates['Document Verification']->format('d M Y'),
            'pre_examination' => "Entrance examination is scheduled for " . 
                               $this->dates['Entrance Examination']->format('d M Y'),
            'awaiting_results' => "Results will be declared on " . 
                                $this->dates['Result Declaration']->format('d M Y'),
            'admission_confirmation' => "Admission confirmation is open until " . 
                                     $this->dates['Admission Confirmation']->format('d M Y'),
            'closed' => "Admission process is closed for this academic year"
        ];
        
        return $messages[$currentPhase];
    }
}
?> 