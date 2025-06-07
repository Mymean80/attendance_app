<?php
/**
 * Manage Employees Page
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

// Delete employee
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Prevent deleting self
    if ($id == $user_id) {
        $message = 'Anda tidak dapat menghapus akun Anda sendiri.';
        $message_type = 'danger';
    } else {
        if (deleteEmployee($id)) {
            $message = 'Karyawan berhasil dihapus.';
            $message_type = 'success';
        } else {
            $message = 'Gagal menghapus karyawan.';
            $message_type = 'danger';
        }
    }
}

// Add new employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    // Validate inputs
    if (empty($_POST['employee_id']) || empty($_POST['name']) || empty($_POST['position']) || 
         empty($_POST['department']) || empty($_POST['email']) || empty($_POST['password']) || 
         empty($_POST['confirm_password'])) {
        $message = 'Semua field wajib diisi.';
        $message_type = 'danger';
    } else if ($_POST['password'] !== $_POST['confirm_password']) {
        $message = 'Password dan konfirmasi password tidak cocok.';
        $message_type = 'danger';
    } else {
        $employee_data = [
            'employee_id' => $_POST['employee_id'],
            'name' => $_POST['name'],
            'position' => $_POST['position'],
            'department' => $_POST['department'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'is_admin' => isset($_POST['is_admin']) ? 1 : 0,
            'is_supervisor' => isset($_POST['is_supervisor']) ? 1 : 0
        ];
        
        if (addEmployee($employee_data)) {
            $message = 'Karyawan berhasil ditambahkan.';
            $message_type = 'success';
        } else {
            $message = 'Gagal menambahkan karyawan. ID atau email mungkin sudah digunakan.';
            $message_type = 'danger';
        }
    }
}

// Update employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_employee'])) {
    $id = $_POST['id'];
    
    // Validate password if provided
    if (!empty($_POST['password']) && $_POST['password'] !== $_POST['confirm_password']) {
        $message = 'Password dan konfirmasi password tidak cocok.';
        $message_type = 'danger';
    } else {
        $employee_data = [
            'name' => $_POST['name'],
            'position' => $_POST['position'],
            'department' => $_POST['department'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'is_admin' => isset($_POST['is_admin']) ? 1 : 0,
            'is_supervisor' => isset($_POST['is_supervisor']) ? 1 : 0
        ];
        
        if (updateEmployee($id, $employee_data)) {
            $message = 'Data karyawan berhasil diperbarui.';
            $message_type = 'success';
            
            // If editing self, update session data
            if ($id == $user_id) {
                $_SESSION['name'] = $_POST['name'];
                $_SESSION['position'] = $_POST['position'];
                $_SESSION['is_admin'] = isset($_POST['is_admin']) ? 1 : 0;
                $_SESSION['is_supervisor'] = isset($_POST['is_supervisor']) ? 1 : 0;
            }
        } else {
            $message = 'Gagal memperbarui data karyawan.';
            $message_type = 'danger';
        }
    }
}

// Get employee for edit modal
$edit_employee = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit']; 
    $edit_employee = getEmployeeById($edit_id);
    
    // If employee not found, show error
    if (!$edit_employee) {
        $message = 'Karyawan dengan ID tersebut tidak ditemukan.';
        $message_type = 'danger';
    }
}

// Get all employees
$employees = getAllEmployees();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Karyawan - Aplikasi Pencatatan Absensi Karyawan</title>
    
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
            
            <div class="sidebar-heading">Admin</div>
            
            <div class="sidebar-item">
                <a href="manage_employees.php" class="sidebar-link active">
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
                <h1 class="h3 mb-0 text-gray-800">Kelola Karyawan</h1>
                
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="fas fa-plus"></i> Tambah Karyawan
                </button>
            </div>
            
            <!-- Alert -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Employee List Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">Daftar Karyawan</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered datatable" id="employees-table" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID Karyawan</th>
                                    <th>Nama</th>
                                    <th>Jabatan</th>
                                    <th>Departemen</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td><?php echo $employee['employee_id']; ?></td>
                                        <td><?php echo $employee['name']; ?></td>
                                        <td><?php echo $employee['position']; ?></td>
                                        <td><?php echo $employee['department']; ?></td>
                                        <td><?php echo $employee['email']; ?></td>
                                        <td>
                                            <?php if ($employee['is_admin']): ?>
                                                <span class="badge bg-primary">Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Member</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($employee['is_supervisor']): ?>
                                                <span class="badge bg-success">Supervisor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="javascript:void(0);" class="btn btn-sm btn-warning edit-btn" data-id="<?php echo $employee['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if ($employee['id'] != $user_id): ?>
                                                <a href="manage_employees.php?delete=<?php echo $employee['id']; ?>" class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $employee['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="view_attendance.php?employee_id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-calendar-alt"></i>
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
    </div>
    
    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEmployeeModalLabel">Tambah Karyawan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="add-employee-form" method="POST" action="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="employee-id" class="form-label">ID Karyawan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="employee-id" name="employee_id" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="position" class="form-label">Jabatan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="position" name="position" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="department" class="form-label">Departemen <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="department" name="department" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="confirm-password" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm-password" name="confirm_password" required>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is-admin" name="is_admin" value="1">
                                    <label class="form-check-label" for="is-admin">
                                        Berikan hak akses admin
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is-supervisor" name="is_supervisor" value="1">
                                    <label class="form-check-label" for="is-supervisor">
                                        Berikan hak akses supervisor
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_employee" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeeModalLabel">Edit Data Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <?php if ($edit_employee): ?>
                    <form id="edit-employee-form" method="POST" action="">
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?php echo $edit_employee['id']; ?>">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="edit-employee-id" class="form-label">ID Karyawan</label>
                                    <input type="text" class="form-control" id="edit-employee-id" value="<?php echo $edit_employee['employee_id']; ?>" readonly>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="edit-name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit-name" name="name" value="<?php echo $edit_employee['name']; ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="edit-position" class="form-label">Jabatan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit-position" name="position" value="<?php echo $edit_employee['position']; ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="edit-department" class="form-label">Departemen <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit-department" name="department" value="<?php echo $edit_employee['department']; ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="edit-email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="edit-email" name="email" value="<?php echo $edit_employee['email']; ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="edit-password" class="form-label">Password Baru</label>
                                    <input type="password" class="form-control" id="edit-password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="edit-confirm-password" class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control" id="edit-confirm-password" name="confirm_password" placeholder="Kosongkan jika tidak ingin mengubah password">
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="edit-is-admin" name="is_admin" value="1" <?php echo $edit_employee['is_admin'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="edit-is-admin">
                                            Berikan hak akses admin
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="edit-is-supervisor" name="is_supervisor" value="1" <?php echo $edit_employee['is_supervisor'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="edit-is-supervisor">
                                            Berikan hak akses supervisor
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" name="update_employee" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Silakan pilih karyawan untuk mengedit data.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                <?php endif; ?>
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
            // Initialize DataTable - prevent reinitializing if already initialized
            if (!$.fn.DataTable.isDataTable('#employees-table')) {
                $('#employees-table').DataTable({
                    responsive: true,
                    language: {
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        zeroRecords: "Tidak ada data yang cocok",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Selanjutnya",
                            previous: "Sebelumnya"
                        }
                    }
                });
            }
            
            // Edit employee validation
            $('#edit-employee-form').on('submit', function(e) {
                const name = $('#edit-name').val();
                const position = $('#edit-position').val();
                const department = $('#edit-department').val();
                const email = $('#edit-email').val();
                const password = $('#edit-password').val();
                const confirmPassword = $('#edit-confirm-password').val();
                let isValid = true;
                
                // Reset previous validation errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
                
                // Validate required fields
                if (!name.trim()) {
                    showValidationError($('#edit-name'), 'Nama tidak boleh kosong');
                    isValid = false;
                }
                
                if (!position.trim()) {
                    showValidationError($('#edit-position'), 'Jabatan tidak boleh kosong');
                    isValid = false;
                }
                
                if (!department.trim()) {
                    showValidationError($('#edit-department'), 'Departemen tidak boleh kosong');
                    isValid = false;
                }
                
                if (!email.trim()) {
                    showValidationError($('#edit-email'), 'Email tidak boleh kosong');
                    isValid = false;
                } else if (!validateEmail(email)) {
                    showValidationError($('#edit-email'), 'Format email tidak valid');
                    isValid = false;
                }
                
                // Validate password if provided
                if (password.trim() || confirmPassword.trim()) {
                    if (password.length < 6) {
                        showValidationError($('#edit-password'), 'Password minimal 6 karakter');
                        isValid = false;
                    }
                    
                    if (password !== confirmPassword) {
                        showValidationError($('#edit-confirm-password'), 'Password tidak cocok');
                        isValid = false;
                    }
                }
                
                return isValid;
            });

            // Show edit modal with correct employee data
            $('.edit-btn').on('click', function() {
                var employeeId = $(this).data('id');
                window.location.href = 'manage_employees.php?edit=' + employeeId;
            });

            // Display success message if edit was successful
            <?php if ($message && $message_type === 'success'): ?>
                showAlert('<?php echo $message; ?>', 'success');
            <?php endif; ?>

            // Add employee validation
            $('#add-employee-form').on('submit', function(e) {
                const employeeId = $('#employee-id').val();
                const name = $('#name').val();
                const position = $('#position').val();
                const department = $('#department').val();
                const email = $('#email').val();
                const password = $('#password').val();
                const confirmPassword = $('#confirm-password').val();
                let isValid = true;
                
                // Reset previous validation errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
                
                // Validate required fields
                if (!employeeId.trim()) {
                    showValidationError($('#employee-id'), 'ID Karyawan tidak boleh kosong');
                    isValid = false;
                }
                
                if (!name.trim()) {
                    showValidationError($('#name'), 'Nama tidak boleh kosong');
                    isValid = false;
                }
                
                if (!position.trim()) {
                    showValidationError($('#position'), 'Jabatan tidak boleh kosong');
                    isValid = false;
                }
                
                if (!department.trim()) {
                    showValidationError($('#department'), 'Departemen tidak boleh kosong');
                    isValid = false;
                }
                
                if (!email.trim()) {
                    showValidationError($('#email'), 'Email tidak boleh kosong');
                    isValid = false;
                } else if (!validateEmail(email)) {
                    showValidationError($('#email'), 'Format email tidak valid');
                    isValid = false;
                }
                
                if (!password.trim()) {
                    showValidationError($('#password'), 'Password tidak boleh kosong');
                    isValid = false;
                } else if (password.length < 6) {
                    showValidationError($('#password'), 'Password minimal 6 karakter');
                    isValid = false;
                }
                
                if (password !== confirmPassword) {
                    showValidationError($('#confirm-password'), 'Password tidak cocok');
                    isValid = false;
                }
                
                return isValid;
            });
        });

        // Using validation functions from script.js

        // Using showAlert function from script.js
    </script>
    
    <?php if (isset($_GET['edit'])): ?>
        <script>
            $(document).ready(function() {
                $('#editEmployeeModal').modal('show');
            });
        </script>
    <?php endif; ?>
</body>
</html> 