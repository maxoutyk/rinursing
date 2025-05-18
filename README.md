# Regional Institute of Nursing - Admission Portal

This repository contains the code for the Regional Institute of Nursing (RIN) Admission Portal, which allows students to apply for nursing programs offered by the institute.

## Features

- Multi-step application form with progress tracking
- Secure user authentication and registration
- Dynamic form validation
- Automatic saving of application progress
- Document upload and management
- Comprehensive student information collection
- Dashboard for application status monitoring

## Technical Details

- Built with PHP and MySQL
- Frontend: HTML5, CSS3, JavaScript, Bootstrap 5
- Ajax-powered form submission for seamless user experience
- Responsive design for desktop and mobile devices

## Getting Started

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Installation

1. Clone the repository
2. Import the database schema from `admission/includes/database_schema.sql`
3. Configure database connection in `admission/includes/db_connect.php`
4. Set up the web server to point to the project directory

### Configuration

Update the database connection settings in `admission/includes/db_connect.php`:

```php
$servername = "your_server";
$username = "your_username";
$password = "your_password";
$dbname = "your_database";
```

## Directory Structure

- `admission/` - Core application files for the admission system
- `css/` - Stylesheets
- `js/` - JavaScript files
- `img/` - Images and media files
- `uploads/` - User uploaded documents

## License

Copyright (c) 2023 Regional Institute of Nursing. All rights reserved. 