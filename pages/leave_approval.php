<?php
/**
 * Leave Approval Page
 * Aplikasi Pencatatan Absensi Karyawan
 */

// Initialize session
session_start();

// Include functions
require_once '../includes/functions.php';

// Check if user is logged in and can approve leave requests
if (!isLoggedIn() || !canApproveLeave()) {
    redirect('../index.php');
}

// Get user data
$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$position = $_SESSION['position'];
$is_admin = $_SESSION['is_admin'] ?? 0;
$is_supervisor = $_SESSION['is_supervisor'] ?? 0;

// Process approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['action'] == 'approve' ? 'approved' : 'rejected';
    
    updateLeaveRequestStatus($request_id, $status);
    
    // Redirect to avoid form resubmission
    header('Location: leave_approval.php');
    exit;
}

// Get all leave requests
$leave_requests = getAllLeaveRequests();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan Cuti - Aplikasi Pencatatan Absensi Karyawan</title>
    
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
                    <a href="leave_approval.php" class="sidebar-link active">
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
                <h1 class="h3 mb-0 text-gray-800">Persetujuan Cuti</h1>
            </div>
            
            <!-- Leave Requests Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">Daftar Pengajuan Cuti</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="leave-table" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Nama Karyawan</th>
                                    <th>ID Karyawan</th>
                                    <th>Tanggal Cuti</th>
                                    <th>Alasan</th>
                                    <th>Status</th>
                                    <th>Dokumen</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($leave_requests) > 0): ?>
                                    <?php foreach ($leave_requests as $request): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($request['created_at'])); ?></td>
                                            <td><?php echo $request['name']; ?></td>
                                            <td><?php echo $request['emp_id']; ?></td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($request['start_date'])); ?> - 
                                                <?php echo date('d/m/Y', strtotime($request['end_date'])); ?>
                                            </td>
                                            <td>
                                                <strong><?php echo $request['reason']; ?></strong>
                                                <?php if ($request['description']): ?>
                                                    <p class="small text-muted mb-0"><?php echo $request['description']; ?></p>
                                                <?php endif; ?>
                                            </td>
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
                                            <td>
                                                <?php if ($request['status'] == 'pending'): ?>
                                                    <form action="" method="POST" class="d-inline">
                                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Yakin ingin menyetujui pengajuan cuti ini?')">
                                                            <i class="fas fa-check"></i> Setuju
                                                        </button>
                                                    </form>
                                                    
                                                    <form action="" method="POST" class="d-inline">
                                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menolak pengajuan cuti ini?')">
                                                            <i class="fas fa-times"></i> Tolak
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted">Sudah diproses</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Belum ada pengajuan cuti.</td>
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
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.body.classList.toggle('sidebar-toggled');
            document.querySelector('.sidebar').classList.toggle('toggled');
        });
        
        // Initialize DataTables
        $(document).ready(function() {
            $('#leave-table').DataTable({
                "language": {
                    "lengthMenu": "Tampilkan _MENU_ entri per halaman",
                    "zeroRecords": "Tidak ada data yang ditemukan",
                    "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                    "infoEmpty": "Tidak ada data yang tersedia",
                    "infoFiltered": "(difilter dari _MAX_ total entri)",
                    "search": "Cari:",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                },
                "order": [[ 0, "desc" ]]
            });
        });
    </script>
</body>
</html> 