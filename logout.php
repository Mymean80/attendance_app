<?php
/**
 * Logout Page
 * Aplikasi Pencatatan Absensi Karyawan
 */

// Initialize session
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: index.php");
exit; 