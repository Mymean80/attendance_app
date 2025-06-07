<?php
/**
 * Attendance Statistics for Supervisors
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

// Check if user is a supervisor or admin
if (!isSupervisor() && !isAdmin()) {
    redirect('dashboard.php');
}

// Get user data
$user_id = $_SESSION['user_id'];
$employee_id = $_SESSION['employee_id'];
$name = $_SESSION['name'];
$position = $_SESSION['position'];
$is_admin = $_SESSION['is_admin'] ?? 0;
$is_supervisor = $_SESSION['is_supervisor'] ?? 0;

// Process filter parameters
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01'); // Default to first day of current month
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d'); // Default to today
$filter_employee = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0; // Default to all employees

// Get all employees for the filter dropdown
$all_employees = getAllEmployees();

// Get attendance statistics
$attendance_records = getEmployeeAttendanceStats($from_date, $to_date, $filter_employee);
$employee_stats = getEmployeeSummaryStats($from_date, $to_date, $filter_employee);

// Calculate summary statistics
$total_records = count($attendance_records);
$late_count = 0;
$total_late_minutes = 0;

foreach ($attendance_records as $record) {
    if ($record['status'] == 'late') {
        $late_count++;
        $total_late_minutes += $record['late_minutes'];
    }
}

// Pagination
$records_per_page = 20;
$total_pages = ceil($total_records / $records_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Get records for current page
$current_records = array_slice($attendance_records, $offset, $records_per_page);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Absensi - Aplikasi Pencatatan Absensi Karyawan</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .summary-card {
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        .attendance-table th {
            background-color: #4e73df;
            color: white;
        }
        .late-badge {
            background-color: #e74a3b;
        }
        .on-time-badge {
            background-color: #1cc88a;
        }
        .filter-section {
            background-color: #f8f9fc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .detail-item {
            display: flex;
            align-items: center;
        }
        .detail-label {
            min-width: 150px;
            font-weight: 600;
            color: #4e73df;
        }
        .detail-value {
            font-size: 1.05rem;
        }
        .chart-container {
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            padding: 15px;
            border-radius: 8px;
            background-color: white;
        }
    </style>
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
                
                <?php if ($is_admin): ?>
                <div class="sidebar-item">
                    <a href="attendance_statistics.php" class="sidebar-link active">
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
                    <a href="attendance_statistics.php" class="sidebar-link active">
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
                <h1 class="h3 mb-0 text-gray-800">Statistik Absensi</h1>
                <button class="btn btn-sm btn-primary shadow-sm" onclick="window.print()">
                    <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
                </button>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-section mb-4">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="from_date" class="form-label">Dari Tanggal</label>
                        <input type="date" class="form-control" id="from_date" name="from_date" value="<?php echo $from_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="to_date" class="form-label">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="to_date" name="to_date" value="<?php echo $to_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="employee_id" class="form-label">Karyawan</label>
                        <select class="form-select" id="employee_id" name="employee_id">
                            <option value="0">Semua Karyawan</option>
                            <?php foreach ($all_employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>" <?php echo $filter_employee == $emp['id'] ? 'selected' : ''; ?>>
                                    <?php echo $emp['name'] . ' (' . $emp['employee_id'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Summary Stats Row -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="summary-card card border-left-primary h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stat-label text-uppercase mb-1">Total Kehadiran</div>
                                    <div class="stat-value text-primary"><?php echo $total_records; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="summary-card card border-left-success h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stat-label text-uppercase mb-1">Tepat Waktu</div>
                                    <div class="stat-value text-success"><?php echo $total_records - $late_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="summary-card card border-left-warning h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stat-label text-uppercase mb-1">Terlambat</div>
                                    <div class="stat-value text-warning"><?php echo $late_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="summary-card card border-left-danger h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stat-label text-uppercase mb-1">Total Menit Terlambat</div>
                                    <div class="stat-value text-danger"><?php echo $total_late_minutes; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Data Absensi</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered attendance-table" id="attendanceTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Departemen</th>
                                    <th>Jam Masuk</th>
                                    <th>Jam Keluar</th>
                                    <th>Status</th>
                                    <th>Durasi Kerja</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($current_records as $record): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($record['date'])); ?></td>
                                    <td><?php echo $record['emp_id']; ?></td>
                                    <td><?php echo $record['name']; ?></td>
                                    <td><?php echo $record['department']; ?></td>
                                    <td><?php echo $record['time_in'] ? date('H:i:s', strtotime($record['time_in'])) : '-'; ?></td>
                                    <td><?php echo $record['time_out'] ? date('H:i:s', strtotime($record['time_out'])) : '-'; ?></td>
                                    <td>
                                        <?php if ($record['status'] == 'late'): ?>
                                            <span class="badge late-badge">
                                                Terlambat <?php echo floor($record['late_minutes']/60) . ' jam ' . ($record['late_minutes'] % 60) . ' menit'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge on-time-badge">Tepat Waktu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($record['time_in'] && $record['time_out']) {
                                            $time_in = strtotime($record['time_in']);
                                            $time_out = strtotime($record['time_out']);
                                            $diff_seconds = $time_out - $time_in;
                                            $hours = floor($diff_seconds / 3600);
                                            $minutes = floor(($diff_seconds % 3600) / 60);
                                            echo $hours . ' jam ' . $minutes . ' menit';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page-1; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&employee_id=<?php echo $filter_employee; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&employee_id=<?php echo $filter_employee; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page+1; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&employee_id=<?php echo $filter_employee; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Employee Performance Summary -->
            <?php if (!$filter_employee): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Performa Karyawan</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="employeeStatsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Departemen</th>
                                    <th>Total Kehadiran</th>
                                    <th>Terlambat</th>
                                    <th>Menit Terlambat</th>
                                    <th>Rata-rata Jam Masuk</th>
                                    <th>Rata-rata Jam Kerja</th>
                                    <th>Tingkat Ketepatan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employee_stats as $emp_id => $stats): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $stats['name']; ?></strong><br>
                                        <small class="text-muted"><?php echo $stats['position']; ?></small>
                                    </td>
                                    <td><?php echo $stats['department']; ?></td>
                                    <td><?php echo $stats['total_days']; ?></td>
                                    <td><?php echo $stats['late_days']; ?></td>
                                    <td><?php echo $stats['total_late_minutes']; ?></td>
                                    <td><?php echo $stats['avg_check_in'] ? $stats['avg_check_in'] : '-'; ?></td>
                                    <td>
                                        <?php 
                                        if (isset($stats['avg_work_hours']) && $stats['avg_work_hours'] > 0) {
                                            echo $stats['avg_work_hours'] . ' jam';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($stats['total_days'] > 0) {
                                            echo $stats['ontime_percentage'] . '%';
                                            
                                            // Progress bar color based on performance
                                            $bar_class = 'bg-danger';
                                            if ($stats['ontime_percentage'] >= 90) {
                                                $bar_class = 'bg-success';
                                            } else if ($stats['ontime_percentage'] >= 75) {
                                                $bar_class = 'bg-info';
                                            } else if ($stats['ontime_percentage'] >= 50) {
                                                $bar_class = 'bg-warning';
                                            }
                                        ?>
                                        <div class="progress mt-1" style="height:5px;">
                                            <div class="progress-bar <?php echo $bar_class; ?>" role="progressbar" style="width: <?php echo $stats['ontime_percentage']; ?>%" aria-valuenow="<?php echo $stats['ontime_percentage']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <?php } else { echo '-'; } ?>
                                    </td>
                                    <td>
                                        <a href="?from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&employee_id=<?php echo $emp_id; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($filter_employee && count($attendance_records) > 0): ?>
            <!-- Individual Employee Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Detail Kinerja: <?php echo $current_records[0]['name']; ?>
                    </h6>
                    <a href="attendance_statistics.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($employee_stats[$filter_employee])): ?>
                    <?php $stat = $employee_stats[$filter_employee]; ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item mb-3">
                                <span class="detail-label">Nama:</span>
                                <span class="detail-value"><?php echo $stat['name']; ?></span>
                            </div>
                            <div class="detail-item mb-3">
                                <span class="detail-label">ID Karyawan:</span>
                                <span class="detail-value"><?php echo $stat['emp_id']; ?></span>
                            </div>
                            <div class="detail-item mb-3">
                                <span class="detail-label">Posisi:</span>
                                <span class="detail-value"><?php echo $stat['position']; ?></span>
                            </div>
                            <div class="detail-item mb-3">
                                <span class="detail-label">Departemen:</span>
                                <span class="detail-value"><?php echo $stat['department']; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item mb-3">
                                <span class="detail-label">Total Kehadiran:</span>
                                <span class="detail-value"><?php echo $stat['total_days']; ?> hari</span>
                            </div>
                            <div class="detail-item mb-3">
                                <span class="detail-label">Keterlambatan:</span>
                                <span class="detail-value"><?php echo $stat['late_days']; ?> hari (<?php echo $stat['total_late_minutes']; ?> menit)</span>
                            </div>
                            <div class="detail-item mb-3">
                                <span class="detail-label">Rata-rata Jam Masuk:</span>
                                <span class="detail-value"><?php echo $stat['avg_check_in']; ?></span>
                            </div>
                            <div class="detail-item mb-3">
                                <span class="detail-label">Rata-rata Jam Kerja:</span>
                                <span class="detail-value"><?php echo $stat['avg_work_hours']; ?> jam/hari</span>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="font-weight-bold mb-3">Statistik Kehadiran</h6>
                    
                    <!-- OnTime vs Late Chart -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="text-center mb-2">Ketepatan Waktu</div>
                            <div class="chart-container" style="position: relative; height:200px; width:100%">
                                <canvas id="ontimeChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center mb-2">Tren Keterlambatan</div>
                            <div class="chart-container" style="position: relative; height:200px; width:100%">
                                <canvas id="lateChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Konfirmasi Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin keluar dari sistem?
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
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTable with search and sorting
        $('#attendanceTable').DataTable({
            paging: false,
            ordering: true,
            searching: true,
            info: false,
            language: {
                search: "Cari:"
            }
        });
        
        $('#employeeStatsTable').DataTable({
            paging: false,
            ordering: true,
            searching: true,
            info: false,
            language: {
                search: "Cari:"
            }
        });
        
        // Toggle sidebar
        $("#sidebarToggle").on('click', function(e) {
            $(".sidebar").toggleClass('collapsed');
            $(".content").toggleClass('expanded');
        });
        
        <?php if ($filter_employee && isset($employee_stats[$filter_employee])): ?>
        // Render charts for individual employee view
        const stat = <?php echo json_encode($employee_stats[$filter_employee]); ?>;
        
        // On-time vs Late Pie Chart
        const ontimeCtx = document.getElementById('ontimeChart').getContext('2d');
        const ontimeChart = new Chart(ontimeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Tepat Waktu', 'Terlambat'],
                datasets: [{
                    data: [stat.total_days - stat.late_days, stat.late_days],
                    backgroundColor: ['#1cc88a', '#e74a3b'],
                    hoverBackgroundColor: ['#17a673', '#d52a1a'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '70%',
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
        
        // Late minutes trend line chart
        const records = <?php echo json_encode($attendance_records); ?>;
        const dates = [];
        const lateMinutes = [];
        
        // Get the last 14 days of attendance
        const recentRecords = records.slice(0, Math.min(14, records.length));
        
        // Reverse to show chronological order
        recentRecords.reverse().forEach(record => {
            dates.push(record.date);
            lateMinutes.push(record.status === 'late' ? parseInt(record.late_minutes) : 0);
        });
        
        const lateCtx = document.getElementById('lateChart').getContext('2d');
        const lateChart = new Chart(lateCtx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Menit Terlambat',
                    data: lateMinutes,
                    backgroundColor: 'rgba(231, 74, 59, 0.1)',
                    borderColor: '#e74a3b',
                    borderWidth: 2,
                    pointBackgroundColor: '#e74a3b',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1,
                    pointRadius: 4,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    });
    </script>
</body>
</html> 