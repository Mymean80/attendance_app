<?php
/**
 * Firebase Configuration
 * Contains all Firebase credentials and configuration
 */

define('FIREBASE_API_KEY', 'AIzaSyAUoesykMDt0B0iYb1kXLbWut8ns-S5ZOM');
define('FIREBASE_AUTH_DOMAIN', 'attendance-app-4124d.firebaseapp.com');
define('FIREBASE_PROJECT_ID', 'attendance-app-4124d');
define('FIREBASE_STORAGE_BUCKET', 'attendance-app-4124d.firebasestorage.app');
define('FIREBASE_MESSAGING_SENDER_ID', '761752063017');
define('FIREBASE_APP_ID', '1:761752063017:web:e5a7c976d2a2340cc16038');
define('FIREBASE_MEASUREMENT_ID', 'G-ZB39V62528');

// Firebase configuration array for JavaScript usage
$firebaseConfig = [
    'apiKey' => FIREBASE_API_KEY,
    'authDomain' => FIREBASE_AUTH_DOMAIN,
    'projectId' => FIREBASE_PROJECT_ID,
    'storageBucket' => FIREBASE_STORAGE_BUCKET,
    'messagingSenderId' => FIREBASE_MESSAGING_SENDER_ID,
    'appId' => FIREBASE_APP_ID,
    'measurementId' => FIREBASE_MEASUREMENT_ID
];
?> 