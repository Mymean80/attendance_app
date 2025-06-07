<?php
date_default_timezone_set('Asia/Jakarta');
/**
 * Database Connection
 * Handles database connection for the attendance system
 */

// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'attendance_db');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if (!$conn) {
    die("ERROR: Could not connect to MySQL. " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($conn, $sql)) {
    // Select the database
    mysqli_select_db($conn, DB_NAME);
    
    // Create tables if they don't exist
    
    // Employees table
    $sql = "CREATE TABLE IF NOT EXISTS employees (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        position VARCHAR(100) NOT NULL,
        department VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        is_admin TINYINT(1) DEFAULT 0,
        is_supervisor TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);
    
    // Check if is_supervisor column exists, add it if not
    $check_column = "SHOW COLUMNS FROM employees LIKE 'is_supervisor'";
    $result = mysqli_query($conn, $check_column);
    if (mysqli_num_rows($result) == 0) {
        $sql = "ALTER TABLE employees ADD is_supervisor TINYINT(1) DEFAULT 0 AFTER is_admin";
        mysqli_query($conn, $sql);
    }
    
    // Attendance table - Modified to fix foreign key constraint
    $sql = "CREATE TABLE IF NOT EXISTS attendance (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id INT(11) UNSIGNED NOT NULL,
        date DATE NOT NULL,
        time_in TIME,
        time_out TIME,
        status VARCHAR(20) DEFAULT 'present',
        late_minutes INT DEFAULT 0,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);

    // Add foreign key constraint separately to avoid errors with existing table
    $check_fk = "SELECT * 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = '" . DB_NAME . "' 
                AND CONSTRAINT_NAME = 'fk_employee_attendance'
                AND TABLE_NAME = 'attendance'";
    $result = mysqli_query($conn, $check_fk);
    if (mysqli_num_rows($result) == 0) {
        $sql = "ALTER TABLE attendance 
                ADD CONSTRAINT fk_employee_attendance
                FOREIGN KEY (employee_id) REFERENCES employees(id) 
                ON DELETE CASCADE";
        mysqli_query($conn, $sql);
    }

    // Check if late_minutes column exists, add it if not
    $check_column = "SHOW COLUMNS FROM attendance LIKE 'late_minutes'";
    $result = mysqli_query($conn, $check_column);
    if (mysqli_num_rows($result) == 0) {
        $sql = "ALTER TABLE attendance ADD late_minutes INT DEFAULT 0 AFTER status";
        mysqli_query($conn, $sql);
    }
    
    // Leave requests table - Modified to fix foreign key constraint
    $sql = "CREATE TABLE IF NOT EXISTS leave_requests (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id INT(11) UNSIGNED NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        reason VARCHAR(100) NOT NULL,
        description TEXT,
        attachment VARCHAR(255),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);
    
    // Add foreign key constraint separately
    $check_fk = "SELECT * 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = '" . DB_NAME . "' 
                AND CONSTRAINT_NAME = 'fk_employee_leave'
                AND TABLE_NAME = 'leave_requests'";
    $result = mysqli_query($conn, $check_fk);
    if (mysqli_num_rows($result) == 0) {
        $sql = "ALTER TABLE leave_requests 
                ADD CONSTRAINT fk_employee_leave
                FOREIGN KEY (employee_id) REFERENCES employees(id) 
                ON DELETE CASCADE";
        mysqli_query($conn, $sql);
    }
    
    // Create shifts table
    $sql = "CREATE TABLE IF NOT EXISTS shifts (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $sql);
    
    // Create employee_shifts table
    $sql = "CREATE TABLE IF NOT EXISTS employee_shifts (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id INT(11) UNSIGNED NOT NULL,
        shift_id INT(11) UNSIGNED NOT NULL,
        assigned_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_employee_shift FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        CONSTRAINT fk_shift_type FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $sql);
    
    // Insert default shifts if they don't exist
    $check_shifts = "SELECT id FROM shifts LIMIT 1";
    $result = mysqli_query($conn, $check_shifts);
    
    if (mysqli_num_rows($result) == 0) {
        // Insert default day shift (8 AM to 4 PM)
        $sql = "INSERT INTO shifts (name, start_time, end_time) 
                VALUES ('Day Shift', '08:00:00', '16:00:00')";
        mysqli_query($conn, $sql);
        
        // Insert default night shift (10 PM to 6 AM)
        $sql = "INSERT INTO shifts (name, start_time, end_time) 
                VALUES ('Night Shift', '22:00:00', '06:00:00')";
        mysqli_query($conn, $sql);
    }
    
    // Insert default admin if not exists
    $check_admin = "SELECT id FROM employees WHERE is_admin = 1 LIMIT 1";
    $result = mysqli_query($conn, $check_admin);
    
    if (mysqli_num_rows($result) == 0) {
        // Hash password for admin (default: admin123)
        $default_password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO employees (employee_id, name, position, department, email, password, is_admin) 
                VALUES ('ADMIN001', 'Administrator', 'System Administrator', 'IT Department', 'rianwijaya2705@gmail.com', '$default_password', 1)";
        mysqli_query($conn, $sql);
    } else {
        // Update admin email if it's still the default
        $sql = "UPDATE employees SET email = 'rianwijaya2705@gmail.com' WHERE employee_id = 'ADMIN001' AND email = 'admin@example.com'";
        mysqli_query($conn, $sql);
    }
} else {
    echo "Error creating database: " . mysqli_error($conn);
}

// Global connection variable
$GLOBALS['conn'] = $conn;
?> 