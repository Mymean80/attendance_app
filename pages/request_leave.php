<?php
/**
 * Request Leave Page
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

// Process form submission
$success = false;
$error = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate inputs
    if (empty($_POST['start_date']) || empty($_POST['end_date']) || empty($_POST['reason'])) {
        $error = "Semua field wajib diisi kecuali attachment.";
    } else {
        // Check file size if attachment is provided
        $file_error = false;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            // Check file type by extension and MIME type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($_FILES['attachment']['tmp_name']);
            
            // Only allow PDF files
            if ($mime_type !== 'application/pdf') {
                $error = "Hanya file PDF yang diperbolehkan.";
                $file_error = true;
            }
            
            // 8MB in bytes
            $max_file_size = 8 * 1024 * 1024; 
            if ($_FILES['attachment']['size'] > $max_file_size) {
                $error = "Ukuran file terlalu besar. Maksimal 8MB.";
                $file_error = true;
            }
        }
        
        if (!$file_error) {
            // Prepare data
            $data = [
                'employee_id' => $user_id,
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'reason' => $_POST['reason'],
                'description' => $_POST['description'] ?? ''
            ];
            
            // Handle file upload
            $file = isset($_FILES['attachment']) && $_FILES['attachment']['error'] != 4 ? $_FILES['attachment'] : null;
            
            // Submit request
            $result = submitLeaveRequest($data, $file);
            
            if ($result) {
                $success = "Permintaan cuti berhasil diajukan.";
            } else {
                $error = "Gagal mengajukan permintaan cuti. Pastikan format file adalah PDF.";
            }
        }
    }
}

// Get employee's leave requests
$leave_requests = getEmployeeLeaveRequests($user_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Cuti - Aplikasi Pencatatan Absensi Karyawan</title>
    
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
                <a href="request_leave.php" class="sidebar-link active">
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
                <h1 class="h3 mb-0 text-gray-800">Ajukan Cuti</h1>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Leave Request Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">Form Pengajuan Cuti</h6>
                </div>
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start-date" class="form-label">Tanggal Mulai Cuti</label>
                                <input type="date" class="form-control" id="start-date" name="start_date" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="end-date" class="form-label">Tanggal Selesai Cuti</label>
                                <input type="date" class="form-control" id="end-date" name="end_date" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Alasan Cuti</label>
                            <select class="form-select" id="reason" name="reason" required>
                                <option value="" selected disabled>-- Pilihan --</option>
                                <option value="Cuti Tahunan">Cuti Tahunan</option>
                                <option value="Cuti Sakit">Cuti Sakit</option>
                                <option value="Cuti Melahirkan">Cuti Melahirkan</option>
                                <option value="Cuti Keluarga">Cuti Keluarga</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="attachment" class="form-label">Attachment (PDF)</label>
                            <input class="form-control" type="file" id="attachment" name="attachment" accept=".pdf">
                            <div class="form-text">Unggah dokumen pendukung jika diperlukan (hanya file PDF, maksimal 8MB).</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Ajukan Cuti
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Leave Request History -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">Riwayat Pengajuan Cuti</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="leave-table" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Tanggal Cuti</th>
                                    <th>Alasan</th>
                                    <th>Status</th>
                                    <th>Dokumen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($leave_requests) > 0): ?>
                                    <?php foreach ($leave_requests as $request): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($request['created_at'])); ?></td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($request['start_date'])); ?> - 
                                                <?php echo date('d/m/Y', strtotime($request['end_date'])); ?>
                                            </td>
                                            <td><?php echo $request['reason']; ?></td>
                                            <td>
                                                <?php if ($request['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning">Menunggu</span>
                                                <?php elseif ($request['status'] == 'approved'): ?>
                                                    <span class="badge bg-success">Disetujui</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Ditolak</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($request['attachment']): ?>
                                                    <a href="../uploads/leave_requests/<?php echo $request['attachment']; ?>" target="_blank" class="btn btn-sm btn-info">
                                                        <i class="fas fa-file-pdf"></i> Lihat
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Tidak ada</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Belum ada pengajuan cuti.</td>
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
                    <h5 class="modal-title" id="logoutModalLabel">Yakin ingin keluar?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Pilih "Logout" jika Anda ingin mengakhiri sesi Anda.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a class="btn btn-primary" href="../logout.php">Logout</a>
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
        
        // DataTable initialization is handled by initDataTables() in script.js
        $(document).ready(function() {
            
            // Validate date range
            $('#start-date, #end-date').on('change', function() {
                let startDate = $('#start-date').val();
                let endDate = $('#end-date').val();
                
                if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
                    alert('Tanggal selesai harus lebih besar atau sama dengan tanggal mulai!');
                    $('#end-date').val('');
                }
            });
        });
    </script>
</body>
</html> 