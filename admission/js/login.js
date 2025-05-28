document.addEventListener('DOMContentLoaded', function() {
    console.info('Login script loaded');
    
    const loginForm = document.getElementById('loginForm');
    const alertsContainer = document.getElementById('login-alerts');
    const twoFactorStatus = document.getElementById('two-factor-status');
    const twoFactorInput = document.getElementById('two-factor-input');
    const twoFactorMessage = document.getElementById('two-factor-message');
    const loginButton = document.getElementById('login-button');
    const verifyButton = document.getElementById('verify-button');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const verificationCodeInput = document.getElementById('verificationCode');
    
    // Function to show verification input fields 
    function showVerificationInputs() {
        console.log('Starting showVerificationInputs function...');
        
        // 1. Multiple class manipulations for two-factor status 
        twoFactorStatus.classList.remove('d-none');
        twoFactorStatus.style.display = 'block';
        twoFactorStatus.style.visibility = 'visible';
        twoFactorStatus.style.opacity = '1';
        
        // 2. Multiple class manipulations for input field
        twoFactorInput.classList.remove('d-none');
        twoFactorInput.classList.add('show-verification', 'force-show');
        twoFactorInput.style.display = 'block';
        twoFactorInput.style.visibility = 'visible';
        twoFactorInput.style.opacity = '1';
        
        // 3. Manipulate login and verify buttons
        loginButton.classList.add('d-none');
        loginButton.style.display = 'none';
        
        verifyButton.classList.remove('d-none');
        verifyButton.classList.add('show-verification', 'force-show');
        verifyButton.style.display = 'block';
        verifyButton.style.visibility = 'visible';
        verifyButton.style.opacity = '1';
        
        // 4. Set message if empty
        if (!twoFactorMessage.textContent || twoFactorMessage.textContent.trim() === '') {
            twoFactorMessage.textContent = 'Enter your verification code to complete login.';
        }
        
        // 5. Focus the verification code input
        setTimeout(() => {
            verificationCodeInput.focus();
        }, 100);
    }
    
    // Log DOM elements to verify they're properly found
    console.log('Form elements found:', {
        loginForm: !!loginForm,
        alertsContainer: !!alertsContainer,
        twoFactorStatus: !!twoFactorStatus,
        twoFactorInput: !!twoFactorInput,
        twoFactorMessage: !!twoFactorMessage,
        loginButton: !!loginButton,
        verifyButton: !!verifyButton,
        emailInput: !!emailInput,
        passwordInput: !!passwordInput,
        verificationCodeInput: !!verificationCodeInput
    });
    
    // Clear any previous alerts when the page loads
    alertsContainer.innerHTML = '';
    
    // Login form submission
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        // Clear previous alert messages
        alertsContainer.innerHTML = '';
        
        // Check if we're in verification mode (verification input is visible)
        if (!twoFactorInput.classList.contains('d-none')) {
            handleVerification();
            return;
        }
        
        // Regular login validation
        let isValid = true;
        const email = emailInput.value.trim();
        const password = passwordInput.value;
        
        // Check required fields
        if (!email || !password) {
            showAlert('Please enter your email and password.', 'danger');
            isValid = false;
        }
        
        // Check email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            showAlert('Please enter a valid email address.', 'danger');
            isValid = false;
        }
        
        // If validation passes, process login
        if (isValid) {
            showAlert('Authenticating...', 'info');
            console.log('Validation passed, sending login request');
            
            // Submit login request
            sendLoginRequest();
        }
    });
    
    // Verification button click
    verifyButton.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Verify button clicked');
        handleVerification();
    });
    
    // Handle Enter key in verification code input
    verificationCodeInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            console.log('Enter key pressed in verification code input');
            handleVerification();
        }
    });
    
    // Function to handle initial login
    function sendLoginRequest() {
        console.log('Sending login request');
        
        // Get form data
        const formData = new FormData();
        formData.append('email', emailInput.value.trim());
        formData.append('password', passwordInput.value);
        if (document.getElementById('rememberMe').checked) {
            formData.append('rememberMe', '1');
        }
        
        // Send the request
        fetch(loginForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Login response received:', response.status);
            
            // Check if the response is JSON
            const contentType = response.headers.get('content-type');
            console.log('Response content type:', contentType);
            
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                // Get the text response for debugging
                console.warn('Non-JSON response received. Will try to parse or use fallback...');
                return response.text().then(text => {
                    console.log('Raw response text:', text);
                    
                    // Try to parse as JSON anyway (some servers misconfigure content-type)
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // Use fallback approach - check for email verification code
                        console.warn('Could not parse as JSON, using fallback verification process');
                        
                        // If we detect this was likely supposed to be a 2FA request,
                        // manually activate the verification input
                        if (text.includes('verification') || text.includes('code') || 
                            text.includes('2FA') || text.includes('factor')) {
                            
                            // Return a synthetic response
                            return {
                                success: true,
                                require2FA: true,
                                message: 'Verification code sent. Please check your email.'
                            };
                        }
                        
                        // Fallback error response
                        throw new Error('Server returned a non-JSON response: ' + text);
                    }
                });
            }
        })
        .then(data => {
            // Clear existing alerts
            alertsContainer.innerHTML = '';
            console.log('Login response data:', data);
            
            if (data.success) {
                if (data.require2FA) {
                    // Show 2FA section
                    console.log('2FA required - showing verification inputs');
                    
                    // Use our dedicated function to show verification inputs
                    showVerificationInputs();
                    
                    // Show masked email
                    const maskedEmail = maskEmail(emailInput.value.trim());
                    twoFactorMessage.textContent = `A verification code has been sent to ${maskedEmail}. Please enter it below to complete login.`;
                    
                    // Show instructions
                    showAlert('Please enter the verification code sent to your email.', 'info');
                } else {
                    // Regular success - redirect
                    console.log('Regular login success - redirecting');
                    showAlert(data.message || 'Login successful! Redirecting...', 'success');
                    
                    // Use the redirect URL from the server if provided, otherwise default to dashboard
                    const redirectTo = data.redirect || 'dashboard.php';
                    
                    // Redirect to dashboard after short delay
                    setTimeout(function() {
                        window.location.href = redirectTo;
                    }, 2000);
                }
            } else {
                // Show error message
                console.log('Login failed:', data.errors || data.message);
                if (data.errors && data.errors.length > 0) {
                    data.errors.forEach(error => {
                        if (error.includes('not verified')) {
                            // Show error with resend button for unverified accounts
                            showAlertWithAction(
                                error,
                                'warning',
                                '<i class="fas fa-envelope me-1"></i> Resend Verification Email',
                                () => resendVerificationEmail(emailInput.value.trim())
                            );
                        } else {
                        showAlert(error, 'danger');
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            showAlert('An error occurred during login. Please try again.', 'danger');
        });
    }
    
    // Function to handle verification code submission
    function handleVerification() {
        console.log('Handling verification code submission');
        
        // Get verification code
        const verificationCode = verificationCodeInput.value.trim();
        
        // Validate
        if (!verificationCode) {
            showAlert('Please enter the verification code.', 'danger');
            return;
        }
        
        // Show loading
        showAlert('Verifying code...', 'info');
        
        // Build form data
        const formData = new FormData();
        formData.append('email', emailInput.value.trim());
        formData.append('verificationCode', verificationCode);
        
        // Send the verification request
        fetch(loginForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Verification response received:', response.status);
            
            // Check if the response is JSON
            const contentType = response.headers.get('content-type');
            console.log('Response content type:', contentType);
            
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                // Get the text response for debugging
                console.warn('Non-JSON response received in verification. Will try to parse or use fallback...');
                return response.text().then(text => {
                    console.log('Raw verification response text:', text);
                    
                    // Try to parse as JSON anyway (some servers misconfigure content-type)
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // Check if this seems like a success response
                        if (text.includes('success') || text.includes('login successful') || 
                            text.includes('welcome') || text.toLowerCase().includes('redirect')) {
                            
                            // Return synthetic success
                            return {
                                success: true,
                                message: 'Verification successful! Redirecting...'
                            };
                        }
                        
                        // Fallback error response
                        throw new Error('Server returned a non-JSON response during verification: ' + text);
                    }
                });
            }
        })
        .then(data => {
            // Clear existing alerts
            alertsContainer.innerHTML = '';
            console.log('Verification response data:', data);
            
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
                        if (error.includes('not verified')) {
                            // Show error with resend button for unverified accounts
                            showAlertWithAction(
                                error,
                                'warning',
                                '<i class="fas fa-envelope me-1"></i> Resend Verification Email',
                                () => resendVerificationEmail(emailInput.value.trim())
                            );
                        } else {
                        showAlert(error, 'danger');
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Verification error:', error);
            showAlert('An error occurred during verification.', 'danger');
        });
    }
    
    // Function to mask email for privacy
    function maskEmail(email) {
        const parts = email.split('@');
        let username = parts[0];
        const domain = parts[1];
        
        if (username.length <= 2) {
            return `${username[0]}${'*'.repeat(username.length - 1)}@${domain}`;
        } else {
            return `${username.substring(0, 2)}${'*'.repeat(username.length - 2)}@${domain}`;
        }
    }
    
    // Function to show alert with optional action button
    function showAlertWithAction(message, type, actionText, actionCallback) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.setAttribute('role', 'alert');
        
        let alertContent = message;
        if (actionText && actionCallback) {
            alertContent += `
                <div class="mt-2">
                    <button type="button" class="btn btn-${type} btn-sm" id="alertActionBtn">
                        ${actionText}
                    </button>
                </div>
            `;
        }
        
        alertDiv.innerHTML = alertContent;
        alertsContainer.appendChild(alertDiv);
        
        if (actionText && actionCallback) {
            document.getElementById('alertActionBtn').addEventListener('click', actionCallback);
        }
    }
    
    // Function to resend verification email
    function resendVerificationEmail(email) {
        // Show loading
        showAlert('Sending verification email...', 'info');
        
        // Build form data
        const formData = new FormData();
        formData.append('email', email);
        formData.append('resendVerification', 'true');
        
        // Send the request
        fetch(loginForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Clear existing alerts
            alertsContainer.innerHTML = '';
            
            if (data.success) {
                showAlert(data.message || 'Verification email sent successfully. Please check your inbox.', 'success');
            } else {
                if (data.message) {
                    showAlert(data.message, 'danger');
                } else {
                    showAlert('Failed to send verification email. Please try again.', 'danger');
                }
            }
        })
        .catch(error => {
            console.error('Error resending verification email:', error);
            showAlert('An error occurred. Please try again.', 'danger');
        });
    }
    
    // Function to show alerts
    function showAlert(message, type) {
        console.log(`Showing alert: ${message} (${type})`);
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
 
 
 
 
 
 