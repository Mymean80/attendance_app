# Aplikasi Pencatatan Absensi Karyawan

A PHP-based employee attendance recording application.

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB
- Web server (Apache, Nginx, or PHP's built-in server)

## Installation Instructions

### 1. Setup Web Server

#### Option 1: Using XAMPP (Recommended for beginners)

1. Download and install XAMPP from https://www.apachefriends.org/
2. Start the Apache and MySQL services from the XAMPP Control Panel
3. Clone or copy this application to the `htdocs` folder (e.g., `C:\xampp\htdocs\attendance_app`)

#### Option 2: Using PHP's built-in server

1. Open a terminal/command prompt
2. Navigate to the application directory
3. Run the command: `php -S localhost:8000`

### 2. Database Configuration

The application will automatically create the database and tables when you first access it. However, you might need to modify the database configuration in `includes/database.php` if your setup is different:

```php
// Database configuration
define('DB_SERVER', 'localhost');   // Database server
define('DB_USERNAME', 'root');      // Database username
define('DB_PASSWORD', '');          // Database password
define('DB_NAME', 'attendance_db'); // Database name
```

### 3. Access the Application

- If using XAMPP: Open your browser and go to `http://localhost/attendance_app`
- If using PHP's built-in server: Open your browser and go to `http://localhost:8000`

## Default Admin Login

Username: `ADMIN001`  
Password: `admin123`

## Features

- Employee login system
- Check-in and check-out functionality
- View attendance history
- Filter attendance records by date
- Admin panel for managing employees
- Reporting and statistics

## Folder Structure

```
attendance_app/
├── assets/          # Static assets
│   ├── css/         # CSS files
│   ├── js/          # JavaScript files
│   └── images/      # Image files
├── includes/        # PHP includes
│   ├── database.php # Database connection
│   └── functions.php # Helper functions
├── pages/           # Application pages
└── index.php        # Entry point
``` 