document.addEventListener('DOMContentLoaded', function() {
    console.log('Verify script loaded');
    
    const verifyForm = document.getElementById('verifyForm');
    const alertsContainer = document.getElementById('verify-alerts');
    const resendCode = document.getElementById('resendCode');
    const returnButton = document.querySelector('a[href="login.html"]');
    
    // Update return button link to login.php
    if (returnButton) {
        returnButton.href = 'login.php';
    }
    
    // Clear any previous alerts when the page loads
    if (alertsContainer) {
        alertsContainer.innerHTML = '';
    }
    
    // Extract email from URL if present
    const urlParams = new URLSearchParams(window.location.search);
    const email = urlParams.get('email');
    
    // Handle verification form submission
    if (verifyForm) {
        verifyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleVerification();
        });
    }
    
    // Handle resend code link
    if (resendCode) {
        resendCode.addEventListener('click', function(e) {
            e.preventDefault();
            resendVerificationCode();
        });
    }
    
    // Function to handle verification submission
    function handleVerification() {
        // Get verification code
        const verificationCode = document.getElementById('verificationCode').value.trim();
        
        // Validate
        if (!verificationCode) {
            showAlert('Please enter the verification code.', 'danger');
            return;
        }
        
        // Show loading
        showAlert('Verifying code...', 'info');
        
        // Build form data
        const formData = new FormData();
        if (email) {
            formData.append('email', email);
        }
        formData.append('verificationCode', verificationCode);
        
        // Send the verification request
        fetch(verifyForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Clear existing alerts
            alertsContainer.innerHTML = '';
            
            if (data.success) {
                // Success - redirect to dashboard
                showAlert(data.message || 'Verification successful! Redirecting...', 'success');
                
                // Redirect to dashboard after short delay
                setTimeout(function() {
                    window.location.href = 'dashboard.php';
                }, 2000);
            } else {
                // Show error message
                if (data.message) {
                    showAlert(data.message, 'danger');
                }
                
                // Show individual errors if available
                if (data.errors && data.errors.length > 0) {
                    data.errors.forEach(error => {
                        showAlert(error, 'danger');
                    });
                }
            }
        })
        .catch(error => {
            console.error('Verification error:', error);
            showAlert('An error occurred during verification.', 'danger');
        });
    }
    
    // Function to resend verification code
    function resendVerificationCode() {
        if (!email) {
            showAlert('Email address not available. Please go back to login.', 'danger');
            return;
        }
        
        // Show loading
        showAlert('Sending new verification code...', 'info');
        
        // Build form data
        const formData = new FormData();
        formData.append('email', email);
        formData.append('resendCode', 'true');
        
        // Send the request
        fetch('includes/login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Clear existing alerts
            alertsContainer.innerHTML = '';
            
            if (data.success) {
                showAlert(data.message || 'New verification code sent.', 'success');
            } else {
                if (data.message) {
                    showAlert(data.message, 'danger');
                } else {
                    showAlert('Failed to send new verification code.', 'danger');
                }
            }
        })
        .catch(error => {
            console.error('Error resending code:', error);
            showAlert('An error occurred. Please try again.', 'danger');
        });
    }
    
    // Function to show alerts
    function showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        alertsContainer.appendChild(alert);
        
        // Auto-dismiss success and info alerts after 5 seconds
        if (type === 'success' || type === 'info') {
            setTimeout(function() {
                if (alert.parentNode) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
    }
}); 
 
 
 
 
 
 