document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initClock();
    initSidebar();
    initFormValidation();
    initDataTables();
    
    // Add event listeners for attendance buttons
    setupAttendanceButtons();
});

/**
 * Initialize real-time clock
 */
function initClock() {
    const clockElement = document.getElementById('current-time');
    const dateElement = document.getElementById('current-date');
    
    if (clockElement && dateElement) {
        updateClock();
        
        // Update every second
        setInterval(updateClock, 1000);
    }
    
    function updateClock() {
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
        
        // If this is the attendance page, check if past 8 AM and update display
        if (document.getElementById('check-in-btn') && document.getElementById('check-in-btn').offsetParent !== null) {
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
    }
}

/**
 * Initialize sidebar toggle functionality
 */
function initSidebar() {
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    
    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-toggled');
            document.querySelector('.sidebar').classList.toggle('toggled');
            document.querySelector('.content').classList.toggle('content-collapsed');
        });
    }
    
    // On mobile, hide sidebar when clicking outside
    if (window.innerWidth < 768) {
        document.addEventListener('click', function(e) {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            if (sidebar && sidebar.classList.contains('toggled') && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target)) {
                
                document.body.classList.remove('sidebar-toggled');
                sidebar.classList.remove('toggled');
                document.querySelector('.content').classList.remove('content-collapsed');
            }
        });
    }
}

/**
 * Initialize form validation
 */
function initFormValidation() {
    // Login form validation
    const loginForm = document.getElementById('login-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const employeeId = document.getElementById('employee-id');
            const password = document.getElementById('password');
            let isValid = true;
            
            // Reset validation
            clearValidationErrors();
            
            // Validate employee ID
            if (!employeeId.value.trim()) {
                showValidationError(employeeId, 'ID Karyawan tidak boleh kosong');
                isValid = false;
            }
            
            // Validate password
            if (!password.value.trim()) {
                showValidationError(password, 'Password tidak boleh kosong');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Employee form validation
    const employeeForm = document.getElementById('employee-form');
    
    if (employeeForm) {
        employeeForm.addEventListener('submit', function(e) {
            const employeeId = document.getElementById('employee-id');
            const name = document.getElementById('name');
            const position = document.getElementById('position');
            const department = document.getElementById('department');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm-password');
            let isValid = true;
            
            // Reset validation
            clearValidationErrors();
            
            // Validate required fields
            if (!employeeId.value.trim()) {
                showValidationError(employeeId, 'ID Karyawan tidak boleh kosong');
                isValid = false;
            }
            
            if (!name.value.trim()) {
                showValidationError(name, 'Nama tidak boleh kosong');
                isValid = false;
            }
            
            if (!position.value.trim()) {
                showValidationError(position, 'Jabatan tidak boleh kosong');
                isValid = false;
            }
            
            if (!department.value.trim()) {
                showValidationError(department, 'Departemen tidak boleh kosong');
                isValid = false;
            }
            
            if (!email.value.trim()) {
                showValidationError(email, 'Email tidak boleh kosong');
                isValid = false;
            } else if (!validateEmail(email.value)) {
                showValidationError(email, 'Format email tidak valid');
                isValid = false;
            }
            
            // Only validate password if it's a new employee or password is being changed
            if (password && confirmPassword) {
                const isNewEmployee = !document.getElementById('employee-id').hasAttribute('readonly');
                
                if (isNewEmployee || password.value.trim()) {
                    if (password.value.length < 6) {
                        showValidationError(password, 'Password minimal 6 karakter');
                        isValid = false;
                    }
                    
                    if (password.value !== confirmPassword.value) {
                        showValidationError(confirmPassword, 'Password tidak cocok');
                        isValid = false;
                    }
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
}

/**
 * Show validation error message
 * 
 * @param {HTMLElement} element Input element
 * @param {string} message Error message
 */
function showValidationError(element, message) {
    element.classList.add('is-invalid');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    
    element.parentNode.appendChild(errorDiv);
}

/**
 * Clear all validation errors
 */
function clearValidationErrors() {
    document.querySelectorAll('.is-invalid').forEach(function(element) {
        element.classList.remove('is-invalid');
    });
    
    document.querySelectorAll('.invalid-feedback').forEach(function(element) {
        element.remove();
    });
}

/**
 * Validate email format
 * 
 * @param {string} email Email to validate
 * @return {boolean} True if valid
 */
function validateEmail(email) {
    const re = /\S+@\S+\.\S+/;
    return re.test(email);
}

/**
 * Initialize DataTables for tables
 */
function initDataTables() {
    const tables = document.querySelectorAll('.datatable');
    
    tables.forEach(function(table) {
        if (typeof $.fn.DataTable !== 'undefined') {
            // Check if table is already initialized before initializing
            if (!$.fn.DataTable.isDataTable(table)) {
                $(table).DataTable({
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
        }
    });
}

/**
 * Setup attendance buttons (check-in and check-out)
 */
function setupAttendanceButtons() {
    const checkInBtn = document.getElementById('check-in-btn');
    const checkOutBtn = document.getElementById('check-out-btn');
    
    if (checkInBtn) {
        checkInBtn.addEventListener('click', function() {
            recordAttendance('in');
        });
    }
    
    if (checkOutBtn) {
        checkOutBtn.addEventListener('click', function() {
            recordAttendance('out');
        });
    }
}

/**
 * Record attendance (check-in or check-out)
 * 
 * @param {string} type Type of attendance record (in/out)
 */
function recordAttendance(type) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'record_attendance.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            
            if (response.success) {
                showAlert(response.message, 'success');
                
                // Refresh page after 2 seconds to update status
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                showAlert(response.message, 'danger');
            }
        } else {
            showAlert('Terjadi kesalahan saat mencatat absensi.', 'danger');
        }
    };
    
    xhr.onerror = function() {
        showAlert('Terjadi kesalahan saat mencatat absensi.', 'danger');
    };
    
    xhr.send('type=' + type);
}

/**
 * Show alert message
 * 
 * @param {string} message Message to display
 * @param {string} type Type of alert (success, danger, warning, info)
 */
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alert-container');
    
    if (alertContainer) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
        alertDiv.role = 'alert';
        
        alertDiv.innerHTML = message + 
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        
        alertContainer.innerHTML = '';
        alertContainer.appendChild(alertDiv);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            alertDiv.classList.remove('show');
            
            setTimeout(function() {
                alertDiv.remove();
            }, 150);
        }, 5000);
    }
}

/**
 * Confirm action (e.g., delete employee)
 * 
 * @param {string} message Confirmation message
 * @return {boolean} True if confirmed
 */
function confirmAction(message) {
    return confirm(message);
} 