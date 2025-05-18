/**
 * Dashboard JavaScript - Handles user session and dashboard functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard script loaded');
    
    // Get DOM elements
    const userNameDisplay = document.getElementById('user-name-display');
    const welcomeMessage = document.querySelector('.alert-heading');
    const lastLoginDate = document.getElementById('last-login-date');
    
    // Function to fetch user session data
    function fetchUserSession() {
        fetch('includes/get_session.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Session data:', data);
                
                if (data.logged_in) {
                    // Update user name in the navigation
                    if (userNameDisplay) {
                        userNameDisplay.textContent = data.user_name || 'User';
                    }
                    
                    // Update welcome message
                    if (welcomeMessage) {
                        welcomeMessage.innerHTML = `<i class="fas fa-bell me-2"></i>Welcome, ${data.user_name || 'Student'}!`;
                    }
                    
                    // Update last login date if available
                    if (lastLoginDate && data.last_login) {
                        lastLoginDate.textContent = formatDate(new Date(data.last_login));
                    }
                    
                    // Check if the user has an application in progress
                    updateApplicationProgress(data);
                } else {
                    // If not logged in, redirect to login page
                    window.location.href = 'login.php';
                }
            })
            .catch(error => {
                console.error('Error fetching session data:', error);
                // If there's an error, redirect to login
                window.location.href = 'login.php?error=session_expired';
            });
    }
    
    // Function to update application progress based on user data
    function updateApplicationProgress(userData) {
        // This would be expanded with real data from the backend
        const progressElement = document.querySelector('.application-status.status-incomplete');
        
        if (progressElement && userData.progress) {
            progressElement.textContent = `${userData.progress}%`;
        }
    }
    
    // Helper function to format date
    function formatDate(date) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }
    
    // Initialize dashboard
    fetchUserSession();
    
    // Active link highlighting
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.portal-nav .nav-link');
    
    navLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        if (currentPath.includes(linkPath)) {
            link.classList.add('active');
        } else if (link.classList.contains('active') && !currentPath.includes(linkPath)) {
            link.classList.remove('active');
        }
    });
});

// Function to fetch dashboard data from server
function fetchDashboardData() {
    // Show loading indicator
    showLoadingIndicator(true);
    
    // Fetch data from server
    fetch('includes/dashboard_data.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update UI with fetched data
            updateDashboardUI(data.data);
        } else {
            showAlert('Error loading dashboard data: ' + (data.message || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error fetching dashboard data:', error);
        
        // For demo/development, use mock data if server request fails
        useMockData();
    })
    .finally(() => {
        // Hide loading indicator
        showLoadingIndicator(false);
    });
}

// Function to update the dashboard UI with fetched data
function updateDashboardUI(data) {
    if (!data) return;
    
    // Update user info
    if (data.user) {
        updateUserInfo(data.user);
    }
    
    // Update application data
    if (data.application) {
        updateApplicationProgress(data.application);
        updateApplicationTimeline(data.application.form_sections);
    }
    
    // Update notifications
    if (data.notifications) {
        updateNotifications(data.notifications);
    }
    
    // Update important dates
    if (data.important_dates) {
        updateImportantDates(data.important_dates);
    }
}

// Function to use mock data for development/demo
function useMockData() {
    // Mock data for development/demo purposes
    const mockData = {
        user: {
            name: 'John Doe',
            email: 'john.doe@example.com',
            phone: '+91 9876543210'
        },
        application: {
            id: null,
            application_id: null,
            progress: 40,
            application_status: 'in_progress',
            last_updated: '2023-06-11T14:32:00',
            form_sections: [
                {id: 1, name: 'Basic Information', completed: true, updated_at: '2023-06-10T11:23:00'},
                {id: 2, name: 'Parent/Guardian Details', completed: true, updated_at: '2023-06-10T15:45:00'},
                {id: 3, name: 'Address Details', completed: true, updated_at: '2023-06-11T09:30:00'},
                {id: 4, name: 'Personal Details', completed: false, updated_at: null},
                {id: 5, name: 'Academic Information (10th)', completed: false, updated_at: null},
                {id: 6, name: 'Academic Information (12th)', completed: false, updated_at: null},
                {id: 7, name: 'Other Qualifications', completed: false, updated_at: null},
                {id: 8, name: 'Documents Upload', completed: false, updated_at: null},
                {id: 9, name: 'Declaration', completed: false, updated_at: null}
            ]
        },
        notifications: [
            {
                id: 1,
                title: 'Application Started',
                message: 'You have successfully started your application.',
                date: '2023-06-10T11:23:00',
                read: true
            },
            {
                id: 2,
                title: 'Remember to Complete Your Application',
                message: 'Your application is partially complete. Please finish all sections before the deadline.',
                date: '2023-06-12T09:00:00',
                read: false
            }
        ],
        important_dates: [
            {id: 1, title: 'Application Deadline', date: '2023-07-15', description: 'Last date to submit applications'},
            {id: 2, title: 'Document Verification', date: '2023-07-20', description: 'Verification of submitted documents'},
            {id: 3, title: 'Entrance Examination', date: '2023-07-30', description: 'Entrance test for all applicants'},
            {id: 4, title: 'Results Declaration', date: '2023-08-10', description: 'Announcement of selected candidates'},
            {id: 5, title: 'Commencement of Session', date: '2023-09-01', description: 'Start of the academic session'}
        ]
    };
    
    // Update UI with mock data
    updateDashboardUI(mockData);
}

// Function to update user info in the navbar
function updateUserInfo(user) {
    // Find the user name element in the navbar
    const navLinks = document.querySelectorAll('.navbar .dropdown-toggle');
    let userNameElement = null;
    
    navLinks.forEach(link => {
        if (link.innerHTML.includes('fa-user-circle')) {
            userNameElement = link;
        }
    });
    
    if (userNameElement) {
        userNameElement.innerHTML = `<i class="fas fa-user-circle me-1"></i>${user.name}`;
    }
    
    // Update welcome message if present
    const welcomeMessage = document.querySelector('.alert-primary h4');
    if (welcomeMessage) {
        welcomeMessage.innerHTML = `<i class="fas fa-bell me-2"></i>Welcome, ${user.name}!`;
    }
}

// Function to update application timeline with completion status
function updateApplicationTimeline(formSections) {
    const timelineItems = document.querySelectorAll('.timeline-item');
    
    // Update each timeline item based on form progress
    timelineItems.forEach((item, index) => {
        if (index < formSections.length) {
            const section = formSections[index];
            
            // Remove all status classes first
            item.classList.remove('complete', 'current');
            
            // Add appropriate status class
            if (section.completed) {
                item.classList.add('complete');
                
                // Update completion date if available
                const dateElement = item.querySelector('.timeline-date');
                if (dateElement && section.updated_at) {
                    const formattedDate = formatDate(section.updated_at);
                    dateElement.textContent = `Completed on ${formattedDate}`;
                }
            } else if (index > 0 && formSections[index - 1].completed || index === 0) {
                if (!item.classList.contains('complete')) {
                    item.classList.add('current');
                    const dateElement = item.querySelector('.timeline-date');
                    if (dateElement) {
                        dateElement.textContent = index === 0 || section.updated_at ? 'In progress' : 'Not started';
                    }
                }
            }
            
            // Update section name
            const titleElement = item.querySelector('.timeline-title');
            if (titleElement) {
                titleElement.textContent = section.name;
            }
        }
    });
}

// Function to check for unread notifications
function updateNotifications(notifications) {
    const unreadCount = notifications.filter(notif => !notif.read).length;
    
    // If there's a notification link in the navbar, update it
    const notificationLink = document.querySelector('a[href="notifications.html"]');
    if (notificationLink && unreadCount > 0) {
        notificationLink.innerHTML = `Notifications <span class="badge bg-danger rounded-pill">${unreadCount}</span>`;
    }
    
    // Update notifications in the sidebar if present
    const noticesContainer = document.querySelector('.bg-light h5 + .border-bottom');
    if (noticesContainer && notifications.length > 0) {
        const noticesParent = noticesContainer.parentElement;
        
        // Clear existing notices
        while (noticesParent.querySelector('.border-bottom')) {
            noticesParent.querySelector('.border-bottom').remove();
        }
        
        // Add notifications as notices (up to 3)
        notifications.slice(0, 3).forEach(notification => {
            const noticeElement = document.createElement('div');
            noticeElement.className = 'border-bottom pb-3 mb-3';
            
            const titleElement = document.createElement('h6');
            titleElement.className = 'text-primary';
            titleElement.textContent = notification.title;
            
            const messageElement = document.createElement('p');
            messageElement.className = 'small mb-0';
            messageElement.textContent = notification.message;
            
            noticeElement.appendChild(titleElement);
            noticeElement.appendChild(messageElement);
            
            noticesParent.appendChild(noticeElement);
        });
    }
}

// Function to update important dates
function updateImportantDates(dates) {
    // Update important dates in the sidebar if present
    const datesContainer = document.querySelector('.bg-light h5 + .border-bottom');
    if (datesContainer && dates.length > 0) {
        const datesParent = datesContainer.parentElement;
        
        // Clear existing dates
        while (datesParent.querySelector('.border-bottom')) {
            datesParent.querySelector('.border-bottom').remove();
        }
        
        // Add important dates (up to 5)
        dates.slice(0, 5).forEach((date, index) => {
            const dateElement = document.createElement('div');
            dateElement.className = index < dates.length - 1 ? 'border-bottom pb-3 mb-3' : '';
            
            const titleElement = document.createElement('h6');
            titleElement.className = 'text-primary';
            titleElement.textContent = date.title;
            
            const dateTextElement = document.createElement('p');
            dateTextElement.className = 'small mb-0';
            dateTextElement.textContent = date.description + ' - ' + formatDate(date.date);
            
            dateElement.appendChild(titleElement);
            dateElement.appendChild(dateTextElement);
            
            datesParent.appendChild(dateElement);
        });
    }
}

// Function to show a loading indicator
function showLoadingIndicator(show) {
    // You could add a loading indicator to the dashboard
    // For simplicity, we'll just use the spinner that's already in the page
    const spinner = document.getElementById('spinner');
    if (spinner) {
        if (show) {
            spinner.classList.add('show');
        } else {
            setTimeout(() => {
                spinner.classList.remove('show');
            }, 500);
        }
    }
}

// Function to show alerts
function showAlert(message, type) {
    // Create alert container if it doesn't exist
    let alertContainer = document.getElementById('dashboard-alerts');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'dashboard-alerts';
        alertContainer.className = 'container mt-3';
        
        // Insert after header
        const header = document.querySelector('.page-header');
        header.parentNode.insertBefore(alertContainer, header.nextSibling);
    }
    
    // Create alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
} 
 
 
 
 