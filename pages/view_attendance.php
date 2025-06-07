<?php
/**
 * View Attendance Page
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
$name = $_SESSION['name'];
$position = $_SESSION['position'];
$is_admin = $_SESSION['is_admin'] ?? 0;
$is_supervisor = $_SESSION['is_supervisor'] ?? 0;

// Process filter form
$from_date = $_GET['from_date'] ?? date('Y-m-01'); // Default to first day of current month
$to_date = $_GET['to_date'] ?? date('Y-m-t'); // Default to last day of current month
$employee_id = $_GET['employee_id'] ?? $user_id;

// Only admin can view other employees' attendance
if (!$is_admin) {
    $employee_id = $user_id;
}

// Get employee list for admin
$employees = [];
if ($is_admin) {
    $employees = getAllEmployees();
}

// Get employee name
$selected_employee_name = $name;
if ($is_admin && $employee_id != $user_id) {
    $employee = getEmployeeById($employee_id);
    if ($employee) {
        $selected_employee_name = $employee['name'];
    }
}

// Get attendance records
$attendance_records = getAttendanceRecords($employee_id, $from_date, $to_date);

// Calculate statistics
$total_days = count($attendance_records);
$total_hours = 0;
$on_time_count = 0;
$late_count = 0;

foreach ($attendance_records as $record) {
    // Calculate work hours
    if ($record['time_in'] && $record['time_out']) {
        $in = strtotime($record['time_in']);
        $out = strtotime($record['time_out']);
        $diff = $out - $in;
        $total_hours += $diff / 3600; // Convert seconds to hours
        
        // Check if late (after 8:00 AM)
        $work_start = strtotime('08:00:00');
        $day_start = strtotime(date('Y-m-d', strtotime($record['date'])) . ' 00:00:00');
        
        if ($in > ($day_start + $work_start)) {
            $late_count++;
        } else {
            $on_time_count++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Absensi - Aplikasi Pencatatan Absensi Karyawan</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
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
                <a href="dashboard.php" class="sidebar-link">
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
                <a href="view_attendance.php" class="sidebar-link active">
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
                
                <?php if ($is_admin): ?>
                <div class="sidebar-item">
                    <a href="attendance_statistics.php" class="sidebar-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistik Absensi</span>
                    </a>
                </div>
                <?php endif; ?>
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
                                <span class="user-name"><?php echo $name; ?></span>
                                <span class="badge bg-primary"><?php echo $position; ?></span>
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
        
        <!-- Main Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Lihat Absensi</h1>
                
                <div>
                    <button class="btn btn-sm btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Cetak
                    </button>
                    
                    <button class="btn btn-sm btn-success" id="export-btn">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>
            
            <!-- Filter Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">Filter Data</h6>
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="row g-3">
                        <?php if ($is_admin): ?>
                            <div class="col-md-3">
                                <label for="employee-select" class="form-label">Karyawan</label>
                                <select class="form-select" id="employee-select" name="employee_id">
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee['id']; ?>" <?php echo ($employee_id == $employee['id']) ? 'selected' : ''; ?>>
                                            <?php echo $employee['name']; ?> (<?php echo $employee['employee_id']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="col-md-3">
                            <label for="from-date" class="form-label">Dari Tanggal</label>
                            <input type="date" class="form-control" id="from-date" name="from_date" value="<?php echo $from_date; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="to-date" class="form-label">Sampai Tanggal</label>
                            <input type="date" class="form-control" id="to-date" name="to_date" value="<?php echo $to_date; ?>">
                        </div>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Summary Card -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card stat-card-primary h-100">
                        <div class="card-body stat-card-body">
                            <div>
                                <div class="stat-card-text">Total Hari</div>
                                <div class="stat-card-number"><?php echo $total_days; ?></div>
                            </div>
                            <div class="stat-card-icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card stat-card-success h-100">
                        <div class="card-body stat-card-body">
                            <div>
                                <div class="stat-card-text">Total Jam Kerja</div>
                                <div class="stat-card-number"><?php echo number_format($total_hours, 1); ?></div>
                            </div>
                            <div class="stat-card-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card stat-card-success h-100">
                        <div class="card-body stat-card-body">
                            <div>
                                <div class="stat-card-text">Tepat Waktu</div>
                                <div class="stat-card-number"><?php echo $on_time_count; ?></div>
                            </div>
                            <div class="stat-card-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card stat-card-warning h-100">
                        <div class="card-body stat-card-body">
                            <div>
                                <div class="stat-card-text">Terlambat</div>
                                <div class="stat-card-number"><?php echo $late_count; ?></div>
                            </div>
                            <div class="stat-card-icon">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Records Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">Data Absensi: <?php echo $selected_employee_name; ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="attendanceTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Check-In</th>
                                    <th>Check-Out</th>
                                    <th>Status</th>
                                    <th>Durasi Kerja</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($attendance_records) > 0): ?>
                                    <?php foreach ($attendance_records as $record): ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($record['date'])); ?></td>
                                            <td>
                                                <?php if ($record['time_in']): ?>
                                                    <?php echo formatTime($record['time_in']); ?>
                                                    <?php if (isset($record['status']) && $record['status'] === 'late'): ?>
                                                        <span class="badge bg-warning text-dark">
                                                            Terlambat <?php echo $record['late_minutes']; ?> menit
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $record['time_out'] ? formatTime($record['time_out']) : '-'; ?></td>
                                            <td>
                                                <?php if (isset($record['status'])): ?>
                                                    <?php if ($record['status'] === 'late'): ?>
                                                        <span class="badge bg-warning text-dark">Terlambat</span>
                                                    <?php elseif ($record['status'] === 'on-time'): ?>
                                                        <span class="badge bg-success">Tepat Waktu</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info"><?php echo ucfirst($record['status']); ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Hadir</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo calculateWorkHours($record['time_in'], $record['time_out']); ?></td>
                                            <td><?php echo $record['notes'] ?? '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/script.js"></script>
    
    <script>
        $(document).ready(function() {
            // DataTable initialization is handled by initDataTables() in script.js

            // Export to Excel functionality
            $('#export-btn').on('click', function() {
                // Get table data
                const table = $('#attendanceTable').DataTable();
                const data = [];
                
                // Table headers
                const headers = [];
                $('#attendanceTable thead th').each(function() {
                    headers.push($(this).text());
                });
                data.push(headers);
                
                // Table data
                table.rows().every(function() {
                    const rowData = this.data();
                    const row = [];
                    rowData.forEach(cell => {
                        // Strip HTML from cell data
                        const div = document.createElement('div');
                        div.innerHTML = cell;
                        row.push(div.textContent.trim());
                    });
                    data.push(row);
                });
                
                // Create CSV content
                let csvContent = "data:text/csv;charset=utf-8,";
                data.forEach(row => {
                    csvContent += row.join(',') + '\r\n';
                });
                
                // Encode and download
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement('a');
                link.setAttribute('href', encodedUri);
                link.setAttribute('download', 'Absensi_<?php echo $selected_employee_name ?>_<?php echo date('Y-m-d') ?>.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    </script>
</body>
</html> 