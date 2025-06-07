<?php
date_default_timezone_set('Asia/Jakarta');
/**
 * Helper functions for the attendance system
 */

// Include database connection
require_once 'database.php';

/**
 * Validates user login and returns user data if valid
 * 
 * @param string $email Email address
 * @param string $password Password
 * @return array|bool User data array or false if invalid
 */
function validateLogin($email, $password) {
    global $conn;
    
    // Use prepared statement to prevent SQL injection
    $sql = "SELECT * FROM employees WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            return $user;
        }
    }
    
    return false;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * 
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

/**
 * Check if user is supervisor
 * 
 * @return bool True if supervisor, false otherwise
 */
function isSupervisor() {
    return isset($_SESSION['is_supervisor']) && $_SESSION['is_supervisor'] == 1;
}

/**
 * Check if user can approve leave requests
 * 
 * @return bool True if user has permission to approve leave requests
 */
function canApproveLeave() {
    return isSupervisor(); // Only supervisors can approve leave requests
}

/**
 * Redirect to a specific page
 * 
 * @param string $location Page to redirect to
 */
function redirect($location) {
    header("Location: $location");
    exit;
}

/**
 * Record attendance for an employee
 * 
 * @param int $employee_id Employee ID
 * @param string $type Type of attendance record (in/out)
 * @return bool|array True/attendance data if recorded successfully, false otherwise
 */
function recordAttendance($employee_id, $type) {
    global $conn;
    
    $date = date('Y-m-d');
    $time = date('H:i:s');
    
    // Check if employee has already checked in today - use prepared statement
    $sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $employee_id, $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        error_log("Database error: " . mysqli_error($conn));
        return false;
    }
    
    if ($type == 'in') {
        // If no record for today, create new record
        if (mysqli_num_rows($result) == 0) {
            // Get employee's schedule for today
            $schedule = generateEmployeeSchedule($employee_id, $date);
            
            if (!$schedule['success']) {
                // If no schedule is found, use default (8 AM)
                $start_time = "08:00:00";
                $shift_name = "Default Shift";
            } else {
                $start_time = $schedule['start_time'];
                $shift_name = $schedule['shift_name'];
            }
            
            // Calculate if late based on shift start time
            $is_late = $time > $start_time;
            $late_minutes = $is_late ? calculateLateMinutes($time, $start_time) : 0;
            
            $status = $is_late ? 'late' : 'on-time';
            
            // Use prepared statement for insert
            $sql = "INSERT INTO attendance (employee_id, date, time_in, status, late_minutes) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isssi", $employee_id, $date, $time, $status, $late_minutes);
            
            if (mysqli_stmt_execute($stmt)) {
                // Format late message
                $late_message = '';
                if ($is_late) {
                    $hours = floor($late_minutes / 60);
                    $mins = $late_minutes % 60;
                    
                    $late_message = 'Anda terlambat ';
                    if ($hours > 0) {
                        $late_message .= "$hours jam ";
                    }
                    if ($mins > 0 || $hours === 0) {
                        $late_message .= "$mins menit ";
                    }
                    $late_message .= 'dari waktu mulai (' . date('h:i A', strtotime($start_time)) . ')';
                }
                
                // Return attendance data including late status and shift info
                return [
                    'success' => true,
                    'is_late' => $is_late,
                    'late_minutes' => $late_minutes,
                    'late_message' => $late_message,
                    'shift_name' => $shift_name,
                    'shift_start' => $start_time
                ];
            }
            error_log("Database error during check-in: " . mysqli_error($conn));
            return false;
        }
        return false; // Already checked in
    } else if ($type == 'out') {
        // Check if already checked in before checking out
        if (mysqli_num_rows($result) == 1) {
            $attendance = mysqli_fetch_assoc($result);
            
            // Only update if time_out is null
            if ($attendance['time_out'] == NULL) {
                // Use prepared statement for update
                $sql = "UPDATE attendance SET time_out = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $time, $attendance['id']);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Get shift information for the response
                    $schedule = generateEmployeeSchedule($employee_id, $date);
                    $shift_name = $schedule['success'] ? $schedule['shift_name'] : "Default Shift";
                    
                    // Calculate work hours
                    $time_in = $attendance['time_in'];
                    $work_hours = calculateWorkHours($time_in, $time);
                    
                    return [
                        'success' => true,
                        'shift_name' => $shift_name,
                        'work_hours' => $work_hours
                    ];
                }
                error_log("Database error during check-out: " . mysqli_error($conn));
                return false;
            }
        }
        return false; // Can't check out without checking in first or already checked out
    }
    
    return false;
}

/**
 * Calculate late minutes
 * 
 * @param string $check_in_time Check-in time
 * @param string $start_time Expected start time
 * @return int Minutes late
 */
function calculateLateMinutes($check_in_time, $start_time) {
    $check_in_timestamp = strtotime($check_in_time);
    $start_timestamp = strtotime($start_time);
    
    // Calculate difference in minutes
    $diff_minutes = round(($check_in_timestamp - $start_timestamp) / 60);
    
    return max(0, $diff_minutes); // Ensure we don't return negative values
}

/**
 * Get attendance records for an employee
 * 
 * @param int $employee_id Employee ID
 * @param string $from_date Start date (optional)
 * @param string $to_date End date (optional)
 * @return array Array of attendance records
 */
function getAttendanceRecords($employee_id, $from_date = null, $to_date = null) {
    global $conn;
    
    $records = [];
    
    if ($from_date && $to_date) {
        // SQL with date range filter
        $sql = "SELECT * FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ? ORDER BY date DESC, time_in DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iss", $employee_id, $from_date, $to_date);
    } else {
        // SQL without date range filter
        $sql = "SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC, time_in DESC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $employee_id);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $records[] = $row;
    }
    
    return $records;
}

/**
 * Get all employees
 * 
 * @return array Array of employees
 */
function getAllEmployees() {
    global $conn;
    
    $sql = "SELECT * FROM employees ORDER BY name";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $employees = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $employees[] = $row;
    }
    
    return $employees;
}

/**
 * Add a new employee
 * 
 * @param array $data Employee data
 * @return bool True if added successfully, false otherwise
 */
function addEmployee($data) {
    global $conn;
    
    // Validate required fields
    if (empty($data['employee_id']) || empty($data['name']) || empty($data['position']) || 
        empty($data['department']) || empty($data['email']) || empty($data['password'])) {
        return false;
    }
    
    // Check if employee ID or email already exists
    $check_sql = "SELECT id FROM employees WHERE employee_id = ? OR email = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ss", $data['employee_id'], $data['email']);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        error_log('Employee ID or email already exists');
        return false;
    }
    
    // Hash password 
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Set admin and supervisor status
    $is_admin = isset($data['is_admin']) && ($data['is_admin'] === 1 || $data['is_admin'] === '1') ? 1 : 0;
    $is_supervisor = isset($data['is_supervisor']) && ($data['is_supervisor'] === 1 || $data['is_supervisor'] === '1') ? 1 : 0;
    
    // Insert new employee
    $sql = "INSERT INTO employees (employee_id, name, position, department, email, password, is_admin, is_supervisor) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssii", 
        $data['employee_id'], 
        $data['name'], 
        $data['position'], 
        $data['department'], 
        $data['email'], 
        $password,
        $is_admin,
        $is_supervisor
    );
    
    $result = mysqli_stmt_execute($stmt);
    
    if (!$result) {
        error_log('MySQL Error: ' . mysqli_error($conn));
    }
    
    return $result;
}

/**
 * Update employee data
 * 
 * @param int $id Employee ID
 * @param array $data Employee data
 * @return bool True if updated successfully, false otherwise
 */
function updateEmployee($id, $data) {
    global $conn;
    
    // Validate required fields
    if (empty($data['name']) || empty($data['position']) || 
        empty($data['department']) || empty($data['email'])) {
        return false;
    }
    
    // Set admin and supervisor status
    $is_admin = isset($data['is_admin']) && ($data['is_admin'] === 1 || $data['is_admin'] === '1') ? 1 : 0;
    $is_supervisor = isset($data['is_supervisor']) && ($data['is_supervisor'] === 1 || $data['is_supervisor'] === '1') ? 1 : 0;
    
    // Update with or without password
    if (!empty($data['password'])) {
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $sql = "UPDATE employees SET 
                name = ?, 
                position = ?, 
                department = ?, 
                email = ?, 
                password = ?,
                is_admin = ?,
                is_supervisor = ?
                WHERE id = ?";
                
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssssiii", 
            $data['name'], 
            $data['position'], 
            $data['department'], 
            $data['email'], 
            $password,
            $is_admin,
            $is_supervisor,
            $id
        );
    } else {
        $sql = "UPDATE employees SET 
                name = ?, 
                position = ?, 
                department = ?, 
                email = ?,
                is_admin = ?,
                is_supervisor = ?
                WHERE id = ?";
                
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssiii", 
            $data['name'], 
            $data['position'], 
            $data['department'], 
            $data['email'],
            $is_admin,
            $is_supervisor,
            $id
        );
    }
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Delete an employee
 * 
 * @param int $id Employee ID
 * @return bool True if deleted successfully, false otherwise
 */
function deleteEmployee($id) {
    global $conn;
    
    $sql = "DELETE FROM employees WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Get employee by ID
 * 
 * @param int $id Employee ID
 * @return array|bool Employee data or false if not found
 */
function getEmployeeById($id) {
    global $conn;
    
    $sql = "SELECT * FROM employees WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    
    return false;
}

/**
 * Format time to readable format
 * 
 * @param string $time Time in H:i:s format
 * @return string Formatted time
 */
function formatTime($time) {
    return date('h:i A', strtotime($time));
}

/**
 * Calculate work hours for a day
 * 
 * @param string $time_in Check-in time
 * @param string $time_out Check-out time
 * @return string Work hours
 */
function calculateWorkHours($time_in, $time_out) {
    if (empty($time_in) || empty($time_out)) {
        return '0';
    }
    
    $in = strtotime($time_in);
    $out = strtotime($time_out);
    
    $diff = $out - $in;
    
    // Format as hours and minutes
    return sprintf('%02d:%02d', floor($diff / 3600), ($diff / 60) % 60);
}

/**
 * Display alert message with proper XSS protection
 * 
 * @param string $message Message to display
 * @param string $type Type of alert (success, danger, warning, info)
 * @return string HTML alert
 */
function displayAlert($message, $type = 'info') {
    $type = htmlspecialchars($type);
    $message = htmlspecialchars($message);
    
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * Submit a leave request
 * 
 * @param array $data Leave request data
 * @param array $file Uploaded file
 * @return int|bool ID of created request or false if failed
 */
function submitLeaveRequest($data, $file = null) {
    global $conn;
    
    // Validate required fields
    if (empty($data['employee_id']) || empty($data['start_date']) || empty($data['end_date']) || empty($data['reason'])) {
        return false;
    }
    
    $attachment = null;
    
    // Handle file upload if present
    if ($file && $file['error'] == 0) {
        // Specify allowed file types for security
        $allowed_types = ['application/pdf'];
        
        // Secure directory path (using absolute path outside web root is better)
        $upload_dir = dirname(__DIR__) . '/uploads/leave_requests/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                error_log("Failed to create upload directory: $upload_dir");
                return false;
            }
        }
        
        // Check file size
        $max_file_size = 8 * 1024 * 1024; // 8MB in bytes
        if ($file['size'] > $max_file_size) {
            error_log("File too large: {$file['size']} bytes");
            return false;
        }
        
        // Generate secure random filename to prevent path traversal
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_ext != 'pdf') {
            error_log("Invalid file type: $file_ext");
            return false;
        }
        
        // Check MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $file_mime = $finfo->file($file['tmp_name']);
        if (!in_array($file_mime, $allowed_types)) {
            error_log("Invalid MIME type: $file_mime");
            return false;
        }
        
        // Create a secure filename
        $file_name = time() . '_' . bin2hex(random_bytes(8)) . '.pdf';
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Set permissions to prevent execution
            chmod($upload_path, 0644);
            $attachment = $file_name;
        } else {
            error_log("Failed to move uploaded file to: $upload_path");
            return false;
        }
    }
    
    // Use prepared statement to prevent SQL injection
    $sql = "INSERT INTO leave_requests (employee_id, start_date, end_date, reason, description, attachment) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    $description = $data['description'] ?? '';
    mysqli_stmt_bind_param($stmt, "isssss", $data['employee_id'], $data['start_date'], $data['end_date'], 
                           $data['reason'], $description, $attachment);
    
    if (mysqli_stmt_execute($stmt)) {
        return mysqli_insert_id($conn);
    }
    
    error_log("Database error: " . mysqli_error($conn));
    return false;
}

/**
 * Get leave requests for an employee
 * 
 * @param int $employee_id Employee ID
 * @return array Array of leave requests
 */
function getEmployeeLeaveRequests($employee_id) {
    global $conn;
    
    $requests = [];
    
    $sql = "SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employee_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
    
    return $requests;
}

/**
 * Get all leave requests (admin only)
 * 
 * @return array Array of leave requests with employee details
 */
function getAllLeaveRequests() {
    global $conn;
    
    $sql = "SELECT lr.*, e.name, e.employee_id as emp_id
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.id 
            ORDER BY lr.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $requests = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
    
    return $requests;
}

/**
 * Update leave request status (admin only)
 * 
 * @param int $request_id Leave request ID
 * @param string $status New status ('approved' or 'rejected')
 * @return bool True if updated successfully, false otherwise
 */
function updateLeaveRequestStatus($request_id, $status) {
    global $conn;
    
    if (!in_array($status, ['approved', 'rejected'])) {
        return false;
    }
    
    $sql = "UPDATE leave_requests SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $request_id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Get leave request by ID
 * 
 * @param int $request_id Leave request ID
 * @return array|bool Leave request data or false if not found
 */
function getLeaveRequestById($request_id) {
    global $conn;
    
    $sql = "SELECT lr.*, e.name 
            FROM leave_requests lr 
            JOIN employees e ON lr.employee_id = e.id 
            WHERE lr.id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    
    return false;
}

/**
 * Get attendance statistics for all employees or a specific employee
 * 
 * @param string $from_date Start date
 * @param string $to_date End date
 * @param int $employee_id Employee ID (optional, 0 for all employees)
 * @return array Array of attendance records with employee details
 */
function getEmployeeAttendanceStats($from_date, $to_date, $employee_id = 0) {
    global $conn;
    
    $records = [];
    
    $sql = "SELECT e.id, e.employee_id as emp_id, e.name, e.position, e.department, 
            a.date, a.time_in, a.time_out, a.status, a.late_minutes
            FROM attendance a
            JOIN employees e ON a.employee_id = e.id
            WHERE a.date BETWEEN ? AND ?";
    
    if ($employee_id > 0) {
        $sql .= " AND a.employee_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $from_date, $to_date, $employee_id);
    } else {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $from_date, $to_date);
    }
    
    $sql .= " ORDER BY a.date DESC, e.name ASC";
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $records[] = $row;
    }
    
    return $records;
}

/**
 * Get attendance summary statistics grouped by employee
 * 
 * @param string $from_date Start date
 * @param string $to_date End date
 * @param int $employee_id Employee ID (optional, 0 for all employees)
 * @return array Array of employee statistics
 */
function getEmployeeSummaryStats($from_date, $to_date, $employee_id = 0) {
    global $conn;
    
    $records = getEmployeeAttendanceStats($from_date, $to_date, $employee_id);
    $employee_stats = [];
    
    foreach ($records as $record) {
        $emp_id = $record['id'];
        
        if (!isset($employee_stats[$emp_id])) {
            $employee_stats[$emp_id] = [
                'name' => $record['name'],
                'emp_id' => $record['emp_id'],
                'position' => $record['position'],
                'department' => $record['department'],
                'total_days' => 0,
                'late_days' => 0,
                'total_late_minutes' => 0,
                'avg_check_in' => 0,
                'check_in_times' => [],
                'complete_days' => 0, // Days with both check-in and check-out
                'work_hours' => 0
            ];
        }
        
        $employee_stats[$emp_id]['total_days']++;
        
        if ($record['status'] == 'late') {
            $employee_stats[$emp_id]['late_days']++;
            $employee_stats[$emp_id]['total_late_minutes'] += $record['late_minutes'];
        }
        
        if ($record['time_in']) {
            $employee_stats[$emp_id]['check_in_times'][] = strtotime($record['time_in']);
        }
        
        if ($record['time_in'] && $record['time_out']) {
            $employee_stats[$emp_id]['complete_days']++;
            
            // Calculate work hours
            $time_in = strtotime($record['time_in']);
            $time_out = strtotime($record['time_out']);
            $diff_hours = ($time_out - $time_in) / 3600;
            $employee_stats[$emp_id]['work_hours'] += $diff_hours;
        }
    }
    
    // Calculate average check-in time and other statistics
    foreach ($employee_stats as $emp_id => &$stats) {
        if (!empty($stats['check_in_times'])) {
            $avg_timestamp = array_sum($stats['check_in_times']) / count($stats['check_in_times']);
            $stats['avg_check_in'] = date('H:i:s', $avg_timestamp);
        }
        
        if ($stats['complete_days'] > 0) {
            $stats['avg_work_hours'] = round($stats['work_hours'] / $stats['complete_days'], 1);
        } else {
            $stats['avg_work_hours'] = 0;
        }
        
        // Calculate on-time percentage
        if ($stats['total_days'] > 0) {
            $stats['ontime_percentage'] = round((($stats['total_days'] - $stats['late_days']) / $stats['total_days']) * 100, 1);
        } else {
            $stats['ontime_percentage'] = 0;
        }
    }
    
    return $employee_stats;
}

/**
 * Get all available shifts
 * 
 * @return array Array of shift data
 */
function getAllShifts() {
    global $conn;
    
    $shifts = [];
    $sql = "SELECT * FROM shifts ORDER BY name";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $shifts[] = $row;
        }
    }
    
    return $shifts;
}

/**
 * Get shift by ID
 * 
 * @param int $shift_id Shift ID
 * @return array|bool Shift data array or false if not found
 */
function getShiftById($shift_id) {
    global $conn;
    
    $sql = "SELECT * FROM shifts WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $shift_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    
    return false;
}

/**
 * Add new shift
 * 
 * @param array $data Shift data
 * @return bool True if added successfully, false otherwise
 */
function addShift($data) {
    global $conn;
    
    $sql = "INSERT INTO shifts (name, start_time, end_time) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $data['name'], $data['start_time'], $data['end_time']);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Update shift
 * 
 * @param int $shift_id Shift ID
 * @param array $data Shift data
 * @return bool True if updated successfully, false otherwise
 */
function updateShift($shift_id, $data) {
    global $conn;
    
    $sql = "UPDATE shifts SET name = ?, start_time = ?, end_time = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssi", $data['name'], $data['start_time'], $data['end_time'], $shift_id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Delete shift
 * 
 * @param int $shift_id Shift ID
 * @return bool True if deleted successfully, false otherwise
 */
function deleteShift($shift_id) {
    global $conn;
    
    $sql = "DELETE FROM shifts WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $shift_id);
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Assign shift to employee
 * 
 * @param int $employee_id Employee ID
 * @param int $shift_id Shift ID
 * @param string $assigned_date Date to assign the shift (YYYY-MM-DD)
 * @return bool True if assigned successfully, false otherwise
 */
function assignShift($employee_id, $shift_id, $assigned_date) {
    global $conn;
    
    // Check if there's already a shift assigned for this date
    $sql = "SELECT id FROM employee_shifts WHERE employee_id = ? AND assigned_date = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $employee_id, $assigned_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Update existing assignment
        $row = mysqli_fetch_assoc($result);
        $sql = "UPDATE employee_shifts SET shift_id = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $shift_id, $row['id']);
    } else {
        // Create new assignment
        $sql = "INSERT INTO employee_shifts (employee_id, shift_id, assigned_date) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $employee_id, $shift_id, $assigned_date);
    }
    
    return mysqli_stmt_execute($stmt);
}

/**
 * Get employee's shift for a specific date
 * 
 * @param int $employee_id Employee ID
 * @param string $date Date to check (YYYY-MM-DD)
 * @return array|bool Shift data array or false if no shift assigned
 */
function getEmployeeShift($employee_id, $date = null) {
    global $conn;
    
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $sql = "SELECT s.* FROM employee_shifts es
            JOIN shifts s ON es.shift_id = s.id
            WHERE es.employee_id = ? AND es.assigned_date = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $employee_id, $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    
    // If no specific shift is assigned, try to find the default shift
    // Default shift is the most frequently assigned shift to this employee
    $sql = "SELECT s.*, COUNT(*) as frequency 
            FROM employee_shifts es
            JOIN shifts s ON es.shift_id = s.id
            WHERE es.employee_id = ?
            GROUP BY s.id
            ORDER BY frequency DESC
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employee_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    
    // If no shift is ever assigned to this employee, return the day shift
    $sql = "SELECT * FROM shifts WHERE name = 'Day Shift' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) == 1) {
        return mysqli_fetch_assoc($result);
    }
    
    return false;
}

/**
 * Generate schedule for an employee based on their assigned shift
 * 
 * @param int $employee_id Employee ID
 * @param string $date Date to generate schedule for (YYYY-MM-DD)
 * @return array Schedule information
 */
function generateEmployeeSchedule($employee_id, $date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    // Get employee shift
    $shift = getEmployeeShift($employee_id, $date);
    
    if (!$shift) {
        return [
            'success' => false,
            'message' => 'No shift assigned for this employee.'
        ];
    }
    
    $start_time = $shift['start_time'];
    $end_time = $shift['end_time'];
    $shift_name = $shift['name'];
    
    // Handle overnight shifts (where end time is earlier than start time)
    $is_overnight = false;
    if ($start_time > $end_time) {
        $is_overnight = true;
        $end_date = date('Y-m-d', strtotime($date . ' +1 day'));
    } else {
        $end_date = $date;
    }
    
    // Format the full schedule datetime
    $schedule_start = date('Y-m-d H:i:s', strtotime($date . ' ' . $start_time));
    $schedule_end = date('Y-m-d H:i:s', strtotime($end_date . ' ' . $end_time));
    
    // Calculate hours
    $start_datetime = new DateTime($schedule_start);
    $end_datetime = new DateTime($schedule_end);
    $interval = $start_datetime->diff($end_datetime);
    $hours = $interval->h + ($interval->days * 24);
    $minutes = $interval->i;
    
    return [
        'success' => true,
        'employee_id' => $employee_id,
        'shift_name' => $shift_name,
        'date' => $date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'schedule_start' => $schedule_start,
        'schedule_end' => $schedule_end,
        'is_overnight' => $is_overnight,
        'hours' => $hours,
        'minutes' => $minutes,
        'total_minutes' => ($hours * 60) + $minutes
    ];
}

/**
 * Get all employee shifts for a specific date range
 * 
 * @param int $employee_id Employee ID (optional, 0 for all employees)
 * @param string $from_date Start date (YYYY-MM-DD)
 * @param string $to_date End date (YYYY-MM-DD)
 * @return array Array of shift assignments
 */
function getEmployeeShiftSchedule($from_date, $to_date, $employee_id = 0) {
    global $conn;
    
    $shifts = [];
    
    // SQL conditions based on parameters
    $conditions = [];
    $params = [];
    $types = "";
    
    if ($employee_id > 0) {
        $conditions[] = "es.employee_id = ?";
        $params[] = $employee_id;
        $types .= "i";
    }
    
    if ($from_date) {
        $conditions[] = "es.assigned_date >= ?";
        $params[] = $from_date;
        $types .= "s";
    }
    
    if ($to_date) {
        $conditions[] = "es.assigned_date <= ?";
        $params[] = $to_date;
        $types .= "s";
    }
    
    $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
    
    $sql = "SELECT es.*, s.name as shift_name, s.start_time, s.end_time, e.name as employee_name 
            FROM employee_shifts es
            JOIN shifts s ON es.shift_id = s.id
            JOIN employees e ON es.employee_id = e.id
            $where_clause
            ORDER BY es.assigned_date DESC, e.name ASC";
    
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $shifts[] = $row;
        }
    }
    
    return $shifts;
}
?> 