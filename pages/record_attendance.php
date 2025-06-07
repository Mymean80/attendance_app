<?php
/**
 * Record Attendance Page
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

    // Process AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {
    $type = $_POST['type'];
    $result = recordAttendance($user_id, $type);
    
    $response = [];
    
    if ($result) {
        if ($type === 'in') {
            if (is_array($result)) {
                $response['success'] = true;
                $response['message'] = 'Check-in berhasil dicatat.';
                $response['shift_name'] = htmlspecialchars($result['shift_name']);
                $response['shift_start'] = date('h:i A', strtotime($result['shift_start']));
                
                // Add late information to response
                if ($result['is_late']) {
                    $response['is_late'] = true;
                    $response['late_minutes'] = $result['late_minutes'];
                    $response['late_message'] = htmlspecialchars($result['late_message']);
                }
            } else {
                $response['success'] = true;
                $response['message'] = 'Check-in berhasil dicatat.';
            }
        } else {
            if (is_array($result)) {
                $response['success'] = true;
                $response['message'] = 'Check-out berhasil dicatat.';
                $response['shift_name'] = htmlspecialchars($result['shift_name']);
                $response['work_hours'] = $result['work_hours'];
            } else {
                $response['success'] = true;
                $response['message'] = 'Check-out berhasil dicatat.';
            }
        }
    } else {
        $response['success'] = false;
        if ($type === 'in') {
            $response['message'] = 'Anda sudah check-in hari ini.';
        } else {
            $response['message'] = 'Anda belum check-in atau sudah check-out hari ini.';
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get today's attendance status
$today = date('Y-m-d');
$attendance_status = '';
$time_in = '';
$time_out = '';

$query = "SELECT * FROM attendance WHERE employee_id = $user_id AND date = '$today'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catat Absensi - Aplikasi Pencatatan Absensi Karyawan</title>
    
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
                <a href="dashboard.php" class="sidebar-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="sidebar-item">
                <a href="record_attendance.php" class="sidebar-link active">
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
        
        <!-- Alert Container -->
        <div id="alert-container"></div>
        
        <!-- Late Alert Container -->
        <div id="late-alert" class="mb-4" style="display: none;"></div>
        
        <!-- Main Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Catat Absensi</h1>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Absensi Hari Ini</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <h4><?php echo $name; ?></h4>
                                <p class="text-muted"><?php echo $position; ?></p>
                            </div>
                            
                            <div class="attendance-clock">
                                <div id="current-time" class="current-time" style="font-size: 3rem; font-weight: bold;">--:--:--</div>
                                <div id="current-date" class="current-date" style="font-size: 1.2rem;">--</div>
                                
                                <div class="attendance-status my-5">
                                    <?php if ($attendance_status === 'not-checked-in'): ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Anda belum check-in hari ini
                                        </div>
                                    <?php elseif ($attendance_status === 'checked-in'): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle"></i> Check-in: <?php echo formatTime($time_in); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i> Check-in: <?php echo formatTime($time_in); ?> | Check-out: <?php echo formatTime($time_out); ?>
                                            <div class="mt-2">
                                                <strong>Durasi Kerja:</strong> <?php echo calculateWorkHours($time_in, $time_out); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="attendance-buttons">
                                    <?php if ($attendance_status === 'not-checked-in'): ?>
                                        <button id="check-in-btn" class="btn btn-primary btn-lg px-5">
                                            <i class="fas fa-sign-in-alt"></i> Check-In
                                        </button>
                                    <?php elseif ($attendance_status === 'checked-in'): ?>
                                        <button id="check-out-btn" class="btn btn-danger btn-lg px-5">
                                            <i class="fas fa-sign-out-alt"></i> Check-Out
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-lg px-5" disabled>
                                            <i class="fas fa-check"></i> Absensi Selesai
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-4 text-center">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Catatan: Pastikan Anda melakukan check-in ketika mulai bekerja dan check-out ketika selesai bekerja.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Attendance Guidelines Card -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Peraturan Absensi</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <i class="fas fa-clock text-primary me-2"></i> <strong>Jam kerja: 08:00 - 17:00</strong> (Check-in setelah pukul 08:00 akan dihitung terlambat)
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i> Keterlambatan lebih dari 15 menit akan dicatat
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-calendar-check text-success me-2"></i> Karyawan wajib melakukan check-in dan check-out setiap hari kerja
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-mobile-alt text-info me-2"></i> Pastikan perangkat yang digunakan memiliki waktu yang akurat
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-user-shield text-danger me-2"></i> Dilarang melakukan absensi untuk karyawan lain
                                </li>
                            </ul>
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
            
            // Check if past 8 AM
            const startTime = new Date();
            startTime.setHours(8, 0, 0, 0); // Set to 8:00:00 AM
            
            if (now > startTime && document.querySelector('.alert-warning')) {
                const diffMs = now - startTime;
                const diffMins = Math.floor(diffMs / 60000); // Convert to minutes
                const hours = Math.floor(diffMins / 60);
                const mins = diffMins % 60;
                
                let lateText = 'Anda terlambat ';
                if (hours > 0) {
                    lateText += `${hours} jam `;
                }
                if (mins > 0 || hours === 0) {
                    lateText += `${mins} menit `;
                }
                lateText += 'dari waktu mulai (08:00 AM)';
                
                const warningAlert = document.querySelector('.alert-warning');
                if (warningAlert) {
                    warningAlert.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${lateText}`;
                }
            }
        }
    });
    </script>
    
    <!-- Add this JavaScript code right before the closing </body> tag -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check-in button click handler
        const checkInBtn = document.getElementById('check-in-btn');
        if (checkInBtn) {
            checkInBtn.addEventListener('click', function() {
                fetch('record_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'type=in'
                })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        // Show success message
                        showAlert(response.message, 'success');
                        
                        // Show late message if applicable
                        if (response.is_late) {
                            document.getElementById('late-alert').innerHTML = `
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> ${response.late_message}
                                </div>
                            `;
                            document.getElementById('late-alert').style.display = 'block';
                        }
                        
                        // Reload page after 2 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showAlert(response.message, 'danger');
                    }
                })
                .catch(() => {
                    showAlert('Terjadi kesalahan. Silakan coba lagi.', 'danger');
                });
            });
        }
        
        // Check-out button click handler
        const checkOutBtn = document.getElementById('check-out-btn');
        if (checkOutBtn) {
            checkOutBtn.addEventListener('click', function() {
                fetch('record_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'type=out'
                })
                .then(response => response.json())
                .then(response => {
                    if (response.success) {
                        showAlert(response.message, 'success');
                        
                        // Reload page after 2 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showAlert(response.message, 'danger');
                    }
                })
                .catch(() => {
                    showAlert('Terjadi kesalahan. Silakan coba lagi.', 'danger');
                });
            });
        }
    });
    
    // Using showAlert function from script.js
    </script>
    
    <!-- JavaScript to modify attendance AJAX functions -->
    <script>
    $(document).ready(function() {
        // Check-in button click
        $('#check-in-btn').on('click', function() {
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    type: 'in'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success alert
                        $('#alert-container').html(
                            '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            response.message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                            '</div>'
                        );
                        
                        // Show shift information
                        let shiftInfo = '';
                        if (response.shift_name) {
                            shiftInfo = '<div class="alert alert-info" role="alert">' +
                                '<i class="fas fa-clock me-2"></i> ' +
                                'Anda telah check-in untuk shift <strong>' + response.shift_name + '</strong> ' +
                                'yang dimulai pada <strong>' + response.shift_start + '</strong>' +
                                '</div>';
                        }
                        
                        // If late, show late alert
                        if (response.is_late) {
                            $('#late-alert').html(
                                shiftInfo +
                                '<div class="alert alert-warning" role="alert">' +
                                '<i class="fas fa-exclamation-triangle me-2"></i> ' +
                                response.late_message +
                                '</div>'
                            ).show();
                        } else if (shiftInfo) {
                            // If not late but we have shift info, still show it
                            $('#late-alert').html(shiftInfo).show();
                        }
                        
                        // Update UI to checked-in state
                        updateAttendanceStatus('checked-in');
                    } else {
                        // Show error alert
                        $('#alert-container').html(
                            '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                            response.message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                            '</div>'
                        );
                    }
                },
                error: function() {
                    // Show error alert for AJAX failure
                    $('#alert-container').html(
                        '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        'Terjadi kesalahan. Silakan coba lagi.' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>'
                    );
                }
            });
        });
        
        // Check-out button click
        $('#check-out-btn').on('click', function() {
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    type: 'out'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success alert
                        $('#alert-container').html(
                            '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                            response.message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                            '</div>'
                        );
                        
                        // Show work hours and shift information
                        let checkoutInfo = '';
                        if (response.shift_name && response.work_hours) {
                            checkoutInfo = '<div class="alert alert-info" role="alert">' +
                                '<i class="fas fa-clock me-2"></i> ' +
                                'Anda telah check-out dari shift <strong>' + response.shift_name + '</strong>. ' +
                                'Total waktu kerja: <strong>' + response.work_hours + '</strong> jam' +
                                '</div>';
                            
                            $('#late-alert').html(checkoutInfo).show();
                        }
                        
                        // Update UI to checked-out state
                        updateAttendanceStatus('checked-out');
                    } else {
                        // Show error alert
                        $('#alert-container').html(
                            '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                            response.message +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                            '</div>'
                        );
                    }
                },
                error: function() {
                    // Show error alert for AJAX failure
                    $('#alert-container').html(
                        '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        'Terjadi kesalahan. Silakan coba lagi.' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>'
                    );
                }
            });
        });
    });
    </script>
</body>
</html> 