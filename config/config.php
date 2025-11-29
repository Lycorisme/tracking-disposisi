<?php
// config/config.php

// Konfigurasi umum aplikasi
define('APP_NAME', 'Tracking Disposisi Surat');
define('APP_VERSION', '1.0.0');

// Base URL - sesuaikan dengan environment Anda
define('BASE_URL', 'http://localhost/tracking-disposisi/public');

// Path upload file surat
define('UPLOAD_DIR', __DIR__ . '/../uploads/surat/');
define('UPLOAD_URL', BASE_URL . '/../uploads/surat/');

// Allowed file extensions
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes

// Timezone
date_default_timezone_set('Asia/Makassar');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}