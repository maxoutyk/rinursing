document.addEventListener('DOMContentLoaded', function() {
    const registrationForm = document.getElementById('registrationForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const progressBar = document.querySelector('.password-strength .progress-bar');
    const alertsContainer = document.getElementById('registration-alerts');
    const captchaError = document.querySelector('.captcha-error');
    const formOverlay = document.getElementById('formOverlay');
    
    // Clear any existing alerts when the page loads
    if (alertsContainer) {
        alertsContainer.innerHTML = '';
    }
    
    console.log('Registration form script loaded');
    
    // Password strength checker
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        // Length check
        if (password.length >= 8) {
            strength += 25;
        }
        
        // Contains lowercase
        if (/[a-z]/.test(password)) {
            strength += 25;
        }
        
        // Contains uppercase
        if (/[A-Z]/.test(password)) {
            strength += 25;
        }
        
        // Contains number or special character
        if (/[0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
            strength += 25;
        }
        
        // Update progress bar
        progressBar.style.width = strength + '%';
        progressBar.setAttribute('aria-valuenow', strength);
        
        // Update color based on strength
        if (strength < 50) {
            progressBar.className = 'progress-bar bg-danger';
        } else if (strength < 75) {
            progressBar.className = 'progress-bar bg-warning';
        } else {
            progressBar.className = 'progress-bar bg-success';
        }
    });
    
    // Form submission
    registrationForm.addEventListener('submit', function(e) {
        // Clear previous alert messages
        alertsContainer.innerHTML = '';
        captchaError.style.display = 'none';
        
        // Basic form validation
        let isValid = validateForm();
        
        // Check CAPTCHA
        const captchaResponse = grecaptcha.getResponse();
        
        if (!captchaResponse) {
            captchaError.style.display = 'block';
            isValid = false;
        }
        
        // If validation fails, prevent form submission
        if (!isValid) {
            e.preventDefault();
            showAlert('Please correct the errors before submitting.', 'danger');
            return false;
        }
        
        // If validation passes, show loading message and overlay, then allow form to submit naturally
        showAlert('Processing your registration...', 'info');
        
        // Show the overlay with a slight delay to allow the form to submit
        setTimeout(() => {
            if (formOverlay) {
                formOverlay.style.display = 'flex !important';
                // Use this workaround for style.display with !important
                formOverlay.setAttribute('style', 'display: flex !important; z-index: 9999;');
            }
        }, 100);
    });
    
    // Validate the form
    function validateForm() {
        let isValid = true;
        
        // Check required fields
        const requiredFields = registrationForm.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // Check email format
        const email = document.getElementById('email').value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            document.getElementById('email').classList.add('is-invalid');
            showAlert('Please enter a valid email address.', 'danger');
            isValid = false;
        }
        
        // Check phone format
        const phone = document.getElementById('phone').value.trim();
        if (phone) {
            const phoneRegex = /^[0-9]{10}$/;  // Basic 10-digit format
            if (!phoneRegex.test(phone.replace(/[- )(]/g, ''))) {
                document.getElementById('phone').classList.add('is-invalid');
                showAlert('Please enter a valid 10-digit phone number.', 'danger');
                isValid = false;
            }
        }
        
        // Check password strength
        const password = passwordInput.value;
        const strength = parseInt(progressBar.getAttribute('aria-valuenow'));
        if (strength < 50) {
            passwordInput.classList.add('is-invalid');
            showAlert('Please use a stronger password.', 'danger');
            isValid = false;
        }
        
        // Check passwords match
        if (password !== confirmPasswordInput.value) {
            confirmPasswordInput.classList.add('is-invalid');
            showAlert('Passwords do not match.', 'danger');
            isValid = false;
        }
        
        // Check terms agreement
        const termsAgreed = document.getElementById('termsAgree').checked;
        if (!termsAgreed) {
            document.getElementById('termsAgree').classList.add('is-invalid');
            showAlert('You must agree to the Terms and Conditions.', 'danger');
            isValid = false;
        }
        
        return isValid;
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
 
 
 
 
 
 