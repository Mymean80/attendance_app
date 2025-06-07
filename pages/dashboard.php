<?php
/**
 * Dashboard Page
 * Aplikasi Pencatatan Absensi Karyawan
 */

// Initialize session
session_start();

// Include functions
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../index.php');
}

// Get user data
$user_id = $_SESSION['user_id'];
$employee_id = $_SESSION['employee_id'];
$name = $_SESSION['name'];
$position = $_SESSION['position'];
$is_admin = $_SESSION['is_admin'] ?? 0;
$is_supervisor = $_SESSION['is_supervisor'] ?? 0;

// Get today's attendance status
$today = date('Y-m-d');
$attendance_status = '';
$time_in = '';
$time_out = '';

$query = "SELECT * FROM attendance WHERE employee_id = $user_id AND date = '$today'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $attendance = mysqli_fetch_assoc($result);
    $time_in = $attendance['time_in'];
    $time_out = $attendance['time_out'];
    
    if ($time_in && $time_out) {
        $attendance_status = 'checked-out';
    } else {
        $attendance_status = 'checked-in';
    }
} else {
    $attendance_status = 'not-checked-in';
}

// Get recent attendance records
$recent_records = getAttendanceRecords($user_id, null, null);
$recent_records = array_slice($recent_records, 0, 5); // Get only 5 most recent records

// Get employee's shift schedule for today and upcoming days
$today = date('Y-m-d');
$employee_schedule = generateEmployeeSchedule($user_id, $today);

// Get schedule for next 7 days (including today)
$upcoming_shifts = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    $shift = generateEmployeeSchedule($user_id, $date);
    
    if ($shift['success']) {
        $upcoming_shifts[] = [
            'date' => $date,
            'day' => date('l', strtotime($date)),
            'shift_name' => $shift['shift_name'],
            'start_time' => date('h:i A', strtotime($shift['start_time'])),
            'end_time' => date('h:i A', strtotime($shift['end_time'])),
            'is_overnight' => $shift['is_overnight']
        ];
    }
}

// Get stats for admin
$total_employees = 0;
$present_today = 0;
$absent_today = 0;

if ($is_admin) {
    // Count total employees
    $query = "SELECT COUNT(*) as total FROM employees";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $total_employees = $row['total'];
    
    // Count present employees today
    $query = "SELECT COUNT(DISTINCT employee_id) as present FROM attendance WHERE date = '$today'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $present_today = $row['present'];
    
    // Calculate absent employees
    $absent_today = $total_employees - $present_today;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Aplikasi Pencatatan Absensi Karyawan</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <a href="dashboard.php">
                <img src="../assets/images/clokin-removebg-preview.png" alt="Clokin Logo" class="img-fluid" style="width: 100%;">
            </a>
        </div>
        
        <div class="sidebar-menu">
            <div class="sidebar-heading">Menu</div>
            
            <div class="sidebar-item">
                <a href="dashboard.php" class="sidebar-link active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="sidebar-item">
                <a href="record_attendance.php" class="sidebar-link">
                    <i class="fas fa-clock"></i>
                    <span>Catat Absensi</span>
                </a>
            </div>
            
            <div class="sidebar-item">
                <a href="view_attendance.php" class="sidebar-link">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Lihat Absensi</span>
                </a>
            </div>
            
            <div class="sidebar-item">
                <a href="request_leave.php" class="sidebar-link">
                    <i class="fas fa-calendar-minus"></i>
                    <span>Ajukan Cuti</span>
                </a>
            </div>
            
            <?php if ($is_supervisor): ?>
                <div class="sidebar-heading">Supervisor</div>
                
                <div class="sidebar-item">
                    <a href="leave_approval.php" class="sidebar-link">
                        <i class="fas fa-check-circle"></i>
                        <span>Persetujuan Cuti</span>
                    </a>
                </div>
                
                <div class="sidebar-item">
                    <a href="attendance_statistics.php" class="sidebar-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistik Absensi</span>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($is_admin): ?>
                <div class="sidebar-heading">Admin</div>
                
                <div class="sidebar-item">
                    <a href="manage_employees.php" class="sidebar-link">
                        <i class="fas fa-users"></i>
                        <span>Kelola Karyawan</span>
                    </a>
                </div>
                
                <div class="sidebar-item">
                    <a href="manage_shifts.php" class="sidebar-link">
                        <i class="fas fa-user-clock"></i>
                        <span>Kelola Shift</span>
                    </a>
                </div>
                
                <div class="sidebar-item">
                    <a href="attendance_statistics.php" class="sidebar-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistik Absensi</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Content -->
    <div class="content">
        <!-- Topbar -->
        <nav class="topbar navbar navbar-expand navbar-light bg-white mb-4">
            <div class="container-fluid">
                <button id="sidebarToggle" class="btn btn-link d-md-none">
                    <i class="fas fa-bars"></i>
                </button>
                
                <ul class="navbar-nav ms-auto">
                    <div class="topbar-divider d-none d-sm-block"></div>
                    
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($name); ?></span>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($position); ?></span>
                            </div>
                            <img class="img-profile rounded-circle ms-2" src="https://ui-avatars.com/api/?name=<?php echo urlencode($name); ?>&background=random" width="32" height="32">
                        </a>
                        
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw me-2 text-gray-400"></i>
                                    Profil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
        
        <!-- Alert Container -->
        <div id="alert-container"></div>
        
        <!-- Main Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
            </div>
            
            <?php if ($is_admin): ?>
                <!-- Admin Stats -->
                <div class="row">
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card stat-card stat-card-primary h-100">
                            <div class="card-body stat-card-body">
                                <div>
                                    <div class="stat-card-text">Total Karyawan</div>
                                    <div class="stat-card-number"><?php echo $total_employees; ?></div>
                                </div>
                                <div class="stat-card-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card stat-card stat-card-success h-100">
                            <div class="card-body stat-card-body">
                                <div>
                                    <div class="stat-card-text">Hadir Hari Ini</div>
                                    <div class="stat-card-number"><?php echo $present_today; ?></div>
                                </div>
                                <div class="stat-card-icon">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-4 col-md-6 mb-4">
                        <div class="card stat-card stat-card-danger h-100">
                            <div class="card-body stat-card-body">
                                <div>
                                    <div class="stat-card-text">Tidak Hadir</div>
                                    <div class="stat-card-number"><?php echo $absent_today; ?></div>
                                </div>
                                <div class="stat-card-icon">
                                    <i class="fas fa-user-times"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Attendance Form Card -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Absensi Hari Ini</h6>
                        </div>
                        <div class="card-body">
                            <div class="attendance-clock">
                                <div id="current-time" class="current-time" style="font-size: 3rem; font-weight: bold;">--:--:--</div>
                                <div id="current-date" class="current-date" style="font-size: 1.2rem;">--</div>
                                
                                <div class="attendance-status mb-4">
                                    <?php if ($attendance_status === 'not-checked-in'): ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Anda belum check-in hari ini
                                        </div>
                                    <?php elseif ($attendance_status === 'checked-in'): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle"></i> Check-in: <?php echo formatTime($time_in); ?>
                                            
                                            <?php
                                            // Get late status from database
                                            $query = "SELECT status, late_minutes FROM attendance WHERE employee_id = $user_id AND date = '$today'";
                                            $status_result = mysqli_query($conn, $query);
                                            if ($status_result && mysqli_num_rows($status_result) > 0) {
                                                $status_data = mysqli_fetch_assoc($status_result);
                                                if ($status_data['status'] === 'late' && $status_data['late_minutes'] > 0) {
                                                    echo '<div class="mt-2 text-warning"><i class="fas fa-exclamation-triangle"></i> Terlambat ' . $status_data['late_minutes'] . ' menit dari waktu mulai (08:00 AM)</div>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> Check-in: <?php echo formatTime($time_in); ?> | Check-out: <?php echo formatTime($time_out); ?>
                                            <div class="mt-2">
                                                <strong>Durasi Kerja:</strong> <?php echo calculateWorkHours($time_in, $time_out); ?>
                                            </div>
                                            
                                            <?php
                                            // Get late status from database
                                            $query = "SELECT status, late_minutes FROM attendance WHERE employee_id = $user_id AND date = '$today'";
                                            $status_result = mysqli_query($conn, $query);
                                            if ($status_result && mysqli_num_rows($status_result) > 0) {
                                                $status_data = mysqli_fetch_assoc($status_result);
                                                if ($status_data['status'] === 'late' && $status_data['late_minutes'] > 0) {
                                                    echo '<div class="mt-2 text-warning"><i class="fas fa-exclamation-triangle"></i> Terlambat ' . $status_data['late_minutes'] . ' menit dari waktu mulai (08:00 AM)</div>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="attendance-buttons">
                                    <?php if ($attendance_status === 'not-checked-in'): ?>
                                        <button id="check-in-btn" class="btn btn-primary btn-lg">
                                            <i class="fas fa-sign-in-alt"></i> Check-In
                                        </button>
                                    <?php elseif ($attendance_status === 'checked-in'): ?>
                                        <button id="check-out-btn" class="btn btn-danger btn-lg">
                                            <i class="fas fa-sign-out-alt"></i> Check-Out
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-lg" disabled>
                                            <i class="fas fa-check"></i> Absensi Selesai
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Attendance Card -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Riwayat Absensi Terakhir</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Check-In</th>
                                            <th>Check-Out</th>
                                            <th>Durasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($recent_records) > 0): ?>
                                            <?php foreach ($recent_records as $record): ?>
                                                <tr>
                                                    <td><?php echo date('d-m-Y', strtotime(htmlspecialchars($record['date']))); ?></td>
                                                    <td><?php echo formatTime(htmlspecialchars($record['time_in'])); ?></td>
                                                    <td>
                                                        <?php echo $record['time_out'] ? formatTime(htmlspecialchars($record['time_out'])) : '-'; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo calculateWorkHours(htmlspecialchars($record['time_in']), htmlspecialchars($record['time_out'])); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Belum ada data absensi</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="text-end mt-3">
                                <a href="view_attendance.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-calendar-alt"></i> Lihat Semua
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Shift Schedule Card -->
            <div class="row">
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Jadwal Shift Anda</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($employee_schedule['success']): ?>
                                <div class="alert alert-info">
                                    <strong>Shift Hari Ini:</strong> 
                                    <?php echo $employee_schedule['shift_name']; ?> 
                                    (<?php echo date('h:i A', strtotime($employee_schedule['start_time'])); ?> - 
                                    <?php echo date('h:i A', strtotime($employee_schedule['end_time'])); ?>)
                                    <?php if ($employee_schedule['is_overnight']): ?>
                                        <span class="badge bg-warning ms-2">Overnight</span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Tidak ada jadwal shift yang ditetapkan untuk hari ini.
                                </div>
                            <?php endif; ?>
                            
                            <h6 class="mb-3 mt-4">Jadwal Mendatang:</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Hari</th>
                                            <th>Shift</th>
                                            <th>Waktu Mulai</th>
                                            <th>Waktu Selesai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($upcoming_shifts) > 0): ?>
                                            <?php foreach ($upcoming_shifts as $shift): ?>
                                                <tr <?php echo ($shift['date'] === $today) ? 'class="table-primary"' : ''; ?>>
                                                    <td><?php echo date('d-m-Y', strtotime($shift['date'])); ?></td>
                                                    <td><?php echo $shift['day']; ?></td>
                                                    <td><?php echo $shift['shift_name']; ?></td>
                                                    <td><?php echo $shift['start_time']; ?></td>
                                                    <td>
                                                        <?php echo $shift['end_time']; ?>
                                                        <?php if ($shift['is_overnight']): ?>
                                                            <span class="badge bg-warning ms-2">+1 hari</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Belum ada jadwal shift yang ditetapkan</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if ($is_admin): ?>
                                <div class="text-end mt-3">
                                    <a href="manage_shifts.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-cog"></i> Kelola Shift
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Keluar dari Aplikasi?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin keluar dari aplikasi?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="../logout.php" class="btn btn-primary">Logout</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/script.js"></script>
    
    <!-- Direct clock script -->
    <script>
    // Immediately run clock update
    document.addEventListener('DOMContentLoaded', function() {
        // Get clock elements
        const clockElement = document.getElementById('current-time');
        const dateElement = document.getElementById('current-date');
        
        if (clockElement && dateElement) {
            // Initial update
            updateTime();
            
            // Update every second
            setInterval(updateTime, 1000);
        }
        
        function updateTime() {
            const now = new Date();
            
            // Format time: HH:MM:SS
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const timeString = `${hours}:${minutes}:${seconds}`;
            
            // Format date: Day, DD Month YYYY
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            const dateString = now.toLocaleDateString('id-ID', options);
            
            // Update elements
            clockElement.textContent = timeString;
            dateElement.textContent = dateString;
        }
    });
    </script>
</body>
</html> 