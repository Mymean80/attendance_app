<?php
/**
 * Manage Shifts Page
 * Aplikasi Pencatatan Absensi Karyawan
 */

// Initialize session
session_start();

// Include functions
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

// Get user data
$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$position = $_SESSION['position'];
$is_admin = $_SESSION['is_admin'] ?? 0;
$is_supervisor = $_SESSION['is_supervisor'] ?? 0;

// Handle form submissions
$message = '';
$message_type = '';

// Add new shift
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_shift'])) {
    // Validate inputs
    if (empty($_POST['name']) || empty($_POST['start_time']) || empty($_POST['end_time'])) {
        $message = 'Semua field wajib diisi.';
        $message_type = 'danger';
    } else {
        $shift_data = [
            'name' => $_POST['name'],
            'start_time' => $_POST['start_time'],
            'end_time' => $_POST['end_time']
        ];
        
        if (addShift($shift_data)) {
            $message = 'Shift berhasil ditambahkan.';
            $message_type = 'success';
        } else {
            $message = 'Gagal menambahkan shift.';
            $message_type = 'danger';
        }
    }
}

// Update shift
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_shift'])) {
    $id = $_POST['id'];
    
    $shift_data = [
        'name' => $_POST['name'],
        'start_time' => $_POST['start_time'],
        'end_time' => $_POST['end_time']
    ];
    
    if (updateShift($id, $shift_data)) {
        $message = 'Shift berhasil diperbarui.';
        $message_type = 'success';
    } else {
        $message = 'Gagal memperbarui shift.';
        $message_type = 'danger';
    }
}

// Delete shift
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    if (deleteShift($id)) {
        $message = 'Shift berhasil dihapus.';
        $message_type = 'success';
    } else {
        $message = 'Gagal menghapus shift.';
        $message_type = 'danger';
    }
}

// Assign shift to employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_shift'])) {
    // Validate inputs
    if (empty($_POST['employee_id']) || empty($_POST['shift_id']) || empty($_POST['assigned_date'])) {
        $message = 'Semua field wajib diisi.';
        $message_type = 'danger';
    } else {
        $employee_id = $_POST['employee_id'];
        $shift_id = $_POST['shift_id'];
        $assigned_date = $_POST['assigned_date'];
        
        if (assignShift($employee_id, $shift_id, $assigned_date)) {
            $message = 'Shift berhasil ditugaskan ke karyawan.';
            $message_type = 'success';
        } else {
            $message = 'Gagal menugaskan shift ke karyawan.';
            $message_type = 'danger';
        }
    }
}

// Get shift for edit modal
$edit_shift = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit']; 
    $edit_shift = getShiftById($edit_id);
    
    // If shift not found, show error
    if (!$edit_shift) {
        $message = 'Shift dengan ID tersebut tidak ditemukan.';
        $message_type = 'danger';
    }
}

// Get all shifts
$shifts = getAllShifts();

// Get all employees for the assignment form
$employees = getAllEmployees();

// Get filter values
$filter_employee = isset($_GET['filter_employee']) ? $_GET['filter_employee'] : 0;
$filter_from = isset($_GET['filter_from']) ? $_GET['filter_from'] : date('Y-m-d', strtotime('-7 days'));
$filter_to = isset($_GET['filter_to']) ? $_GET['filter_to'] : date('Y-m-d');

// Get shift assignments based on filters
$shift_assignments = getEmployeeShiftSchedule($filter_from, $filter_to, $filter_employee);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Shift - Aplikasi Pencatatan Absensi Karyawan</title>
    
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
    
    <!-- Additional page-specific styles -->
    <style>
        /* Ensures the table is fully visible and scrollable if needed */
        #assignmentsTable {
            width: 100% !important;
        }
        
        /* Improve layout for smaller screens */
        @media (max-width: 992px) {
            .card {
                overflow: hidden;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Force table to not be like tables on small screens */
            .table-responsive-sm {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
        
        /* Better alignment for card content */
        .card-body {
            padding: 1.25rem;
            overflow: hidden;
        }
        
        /* Fix for DataTables pagination */
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 0.5rem;
            float: right;
        }
        
        /* Fix potential wrapping issues with buttons */
        .btn {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="wrapper">
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
                        <i class="fas fa-calendar-check"></i>
                        <span>Lihat Absensi</span>
                    </a>
                </div>
                
                <?php if (canApproveLeave()): ?>
                <div class="sidebar-item">
                    <a href="leave_approval.php" class="sidebar-link">
                        <i class="fas fa-calendar-times"></i>
                        <span>Persetujuan Cuti</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="sidebar-item">
                    <a href="request_leave.php" class="sidebar-link">
                        <i class="fas fa-calendar-minus"></i>
                        <span>Ajukan Cuti</span>
                    </a>
                </div>
                
                <?php if (isAdmin()): ?>
                <div class="sidebar-heading">Admin</div>
                
                <div class="sidebar-item">
                    <a href="manage_employees.php" class="sidebar-link">
                        <i class="fas fa-users"></i>
                        <span>Kelola Karyawan</span>
                    </a>
                </div>
                
                <div class="sidebar-item">
                    <a href="manage_shifts.php" class="sidebar-link active">
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
        
        <!-- Main Content -->
        <div class="main-content-wrapper">
            <div class="main-content">
                <!-- Navbar -->
                <div class="navbar-container">
                    <nav class="navbar navbar-expand-lg navbar-light bg-white">
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        
                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                            <ul class="navbar-nav ms-auto">
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <img src="https://via.placeholder.com/32x32" alt="Profile" class="rounded-circle me-2">
                                        <?php echo $name; ?>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                        <li><a class="dropdown-item" href="#">Profil</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="../logout.php">Keluar</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>
                
                <!-- Page Content -->
                <div class="container-fluid px-2 px-md-4">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="d-sm-flex align-items-center justify-content-between mb-3">
                                <h1 class="h3 mb-0 text-gray-800">Kelola Shift</h1>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                                    <i class="fas fa-plus-circle"></i> Tambah Shift Baru
                                </button>
                            </div>
                            
                            <?php if($message): ?>
                                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                    <?php echo $message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Shift List -->
                        <div class="col-md-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold">Daftar Shift</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="shiftsTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Nama Shift</th>
                                                    <th>Waktu Mulai</th>
                                                    <th>Waktu Selesai</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($shifts as $shift): ?>
                                                    <tr>
                                                        <td><?php echo $shift['name']; ?></td>
                                                        <td><?php echo date('h:i A', strtotime($shift['start_time'])); ?></td>
                                                        <td><?php echo date('h:i A', strtotime($shift['end_time'])); ?></td>
                                                        <td>
                                                            <a href="?edit=<?php echo $shift['id']; ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="?delete=<?php echo $shift['id']; ?>" 
                                                               class="btn btn-sm btn-danger" 
                                                               onclick="return confirm('Yakin ingin menghapus shift ini?');">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Assign Shift -->
                        <div class="col-md-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold">Tugaskan Shift ke Karyawan</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="">
                                        <div class="mb-3">
                                            <label for="employee_id" class="form-label">Karyawan</label>
                                            <select class="form-select" id="employee_id" name="employee_id" required>
                                                <option value="">-- Pilih Karyawan --</option>
                                                <?php foreach($employees as $employee): ?>
                                                    <option value="<?php echo $employee['id']; ?>">
                                                        <?php echo $employee['name']; ?> (<?php echo $employee['employee_id']; ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="shift_id" class="form-label">Shift</label>
                                            <select class="form-select" id="shift_id" name="shift_id" required>
                                                <option value="">-- Pilih Shift --</option>
                                                <?php foreach($shifts as $shift): ?>
                                                    <option value="<?php echo $shift['id']; ?>">
                                                        <?php echo $shift['name']; ?> (<?php echo date('h:i A', strtotime($shift['start_time'])); ?> - <?php echo date('h:i A', strtotime($shift['end_time'])); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="assigned_date" class="form-label">Tanggal</label>
                                            <input type="date" class="form-control" id="assigned_date" name="assigned_date" required min="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        
                                        <button type="submit" name="assign_shift" class="btn btn-primary">Tugaskan Shift</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shift Assignments -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold">Jadwal Shift Karyawan</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Filter Form -->
                                    <form method="get" action="" class="mb-4">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="filter_employee" class="form-label">Karyawan</label>
                                                <select class="form-select" id="filter_employee" name="filter_employee">
                                                    <option value="0">Semua Karyawan</option>
                                                    <?php foreach($employees as $employee): ?>
                                                        <option value="<?php echo $employee['id']; ?>" <?php echo ($filter_employee == $employee['id']) ? 'selected' : ''; ?>>
                                                            <?php echo $employee['name']; ?> (<?php echo $employee['employee_id']; ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label for="filter_from" class="form-label">Dari Tanggal</label>
                                                <input type="date" class="form-control" id="filter_from" name="filter_from" value="<?php echo $filter_from; ?>">
                                            </div>
                                            
                                            <div class="col-md-3 mb-3">
                                                <label for="filter_to" class="form-label">Sampai Tanggal</label>
                                                <input type="date" class="form-control" id="filter_to" name="filter_to" value="<?php echo $filter_to; ?>">
                                            </div>
                                            
                                            <div class="col-md-2 mb-3 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                                            </div>
                                        </div>
                                    </form>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="assignmentsTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Karyawan</th>
                                                    <th>Tanggal</th>
                                                    <th>Shift</th>
                                                    <th>Waktu Mulai</th>
                                                    <th>Waktu Selesai</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($shift_assignments as $assignment): ?>
                                                    <tr>
                                                        <td><?php echo $assignment['employee_name']; ?></td>
                                                        <td><?php echo date('d-m-Y', strtotime($assignment['assigned_date'])); ?></td>
                                                        <td><?php echo $assignment['shift_name']; ?></td>
                                                        <td><?php echo date('h:i A', strtotime($assignment['start_time'])); ?></td>
                                                        <td><?php echo date('h:i A', strtotime($assignment['end_time'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Shift Modal -->
    <div class="modal fade" id="addShiftModal" tabindex="-1" aria-labelledby="addShiftModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addShiftModalLabel">Tambah Shift Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Shift</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="start_time" class="form-label">Waktu Mulai</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="end_time" class="form-label">Waktu Selesai</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                            <small class="form-text text-muted">Untuk shift malam, waktu selesai bisa lebih kecil dari waktu mulai.</small>
                        </div>
                        
                        <button type="submit" name="add_shift" class="btn btn-primary">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Shift Modal -->
    <?php if($edit_shift): ?>
    <div class="modal fade" id="editShiftModal" tabindex="-1" aria-labelledby="editShiftModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editShiftModalLabel">Edit Shift</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="">
                        <input type="hidden" name="id" value="<?php echo $edit_shift['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Nama Shift</label>
                            <input type="text" class="form-control" id="edit_name" name="name" value="<?php echo $edit_shift['name']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_start_time" class="form-label">Waktu Mulai</label>
                            <input type="time" class="form-control" id="edit_start_time" name="start_time" value="<?php echo $edit_shift['start_time']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_end_time" class="form-label">Waktu Selesai</label>
                            <input type="time" class="form-control" id="edit_end_time" name="end_time" value="<?php echo $edit_shift['end_time']; ?>" required>
                            <small class="form-text text-muted">Untuk shift malam, waktu selesai bisa lebih kecil dari waktu mulai.</small>
                        </div>
                        
                        <button type="submit" name="update_shift" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var editShiftModal = new bootstrap.Modal(document.getElementById('editShiftModal'));
            editShiftModal.show();
        });
    </script>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        $(document).ready(function() {
            $('#shiftsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                },
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Semua"]]
            });
            
            $('#assignmentsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                },
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Semua"]]
            });
        });
    </script>
</body>
</html> 