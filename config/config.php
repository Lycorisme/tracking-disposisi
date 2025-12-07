<?php
// config/config.php

// ============================================================================
// KONFIGURASI UMUM APLIKASI
// ============================================================================
define('APP_NAME', 'Tracking Disposisi Surat');
define('APP_VERSION', '1.0.0');

// ============================================================================
// DETEKSI ENVIRONMENT (LOCAL vs PRODUCTION)
// ============================================================================
// Deteksi apakah sedang di localhost atau production
$isLocalhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1']);

// ============================================================================
// AUTO-DETECT BASE URL
// ============================================================================
if ($isLocalhost) {
    // UNTUK LOCALHOST
    // Akan otomatis mendeteksi nama folder project
    
    // Ambil protocol (http atau https)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    
    // Ambil host (localhost atau 127.0.0.1)
    $host = $_SERVER['SERVER_NAME'];
    
    // Ambil port jika bukan default (80 untuk http, 443 untuk https)
    $port = $_SERVER['SERVER_PORT'];
    $portString = '';
    if (($protocol === 'http' && $port != 80) || ($protocol === 'https' && $port != 443)) {
        $portString = ':' . $port;
    }
    
    // Deteksi folder project secara otomatis
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    
    // Jika script berada di subfolder 'public', hapus '/public' dari path
    if (basename($scriptPath) === 'public') {
        $scriptPath = dirname($scriptPath);
    }
    
    // Jika path adalah '/', set ke empty string
    $basePath = ($scriptPath === '/' || $scriptPath === '\\') ? '' : $scriptPath;
    
    // Gabungkan semua komponen
    define('BASE_URL', $protocol . '://' . $host . $portString . $basePath . '/public');
    
} else {
    // UNTUK PRODUCTION/HOSTING
    // Silakan sesuaikan dengan domain hosting Anda
    
    // OPSI 1: Jika aplikasi berada di root domain
    // Contoh: https://yourdomain.com
    define('BASE_URL', 'https://' . $_SERVER['SERVER_NAME'] . '/public');
    
    // OPSI 2: Jika aplikasi berada di subfolder
    // Contoh: https://yourdomain.com/tracking-surat
    // Uncomment baris di bawah dan sesuaikan nama folder
    // define('BASE_URL', 'https://' . $_SERVER['SERVER_NAME'] . '/tracking-surat/public');
    
    // OPSI 3: Set manual (paling aman untuk production)
    // Uncomment dan sesuaikan dengan URL hosting Anda
    // define('BASE_URL', 'https://tracking-disposisi.yourdomain.com/public');
}

// ============================================================================
// PATH UPLOAD FILE
// ============================================================================
// Gunakan absolute path untuk upload directory
define('UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'surat' . DIRECTORY_SEPARATOR);

// URL untuk mengakses file upload
if ($isLocalhost) {
    // Untuk localhost, gunakan relative URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['SERVER_NAME'];
    $port = $_SERVER['SERVER_PORT'];
    $portString = '';
    if (($protocol === 'http' && $port != 80) || ($protocol === 'https' && $port != 443)) {
        $portString = ':' . $port;
    }
    
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    if (basename($scriptPath) === 'public') {
        $scriptPath = dirname($scriptPath);
    }
    $basePath = ($scriptPath === '/' || $scriptPath === '\\') ? '' : $scriptPath;
    
    define('UPLOAD_URL', $protocol . '://' . $host . $portString . $basePath . '/uploads/surat/');
} else {
    // Untuk production
    define('UPLOAD_URL', 'https://' . $_SERVER['SERVER_NAME'] . '/uploads/surat/');
    
    // Jika aplikasi di subfolder, uncomment dan sesuaikan:
    // define('UPLOAD_URL', 'https://' . $_SERVER['SERVER_NAME'] . '/tracking-surat/uploads/surat/');
}

// Buat direktori upload jika belum ada
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// ============================================================================
// KONFIGURASI FILE UPLOAD
// ============================================================================
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png']);
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes

// ============================================================================
// TIMEZONE
// ============================================================================
date_default_timezone_set('Asia/Makassar');

// ============================================================================
// SESSION CONFIGURATION
// ============================================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Session security for production
if (!$isLocalhost) {
    ini_set('session.cookie_secure', 1); // Hanya HTTPS di production
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================================
// ERROR REPORTING (DEVELOPMENT vs PRODUCTION)
// ============================================================================
if ($isLocalhost) {
    // Development: Tampilkan semua error
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    // Production: Sembunyikan error dari user
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__DIR__) . '/logs/error.log');
    
    // Buat folder logs jika belum ada
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
}

// ============================================================================
// DEBUG MODE (Optional - untuk development saja)
// ============================================================================
define('DEBUG_MODE', $isLocalhost);

// ============================================================================
// HELPER FUNCTION - Get Full URL
// ============================================================================
/**
 * Generate full URL untuk file/page tertentu
 * 
 * @param string $path Path relatif dari folder public (contoh: 'surat.php', 'login.php')
 * @return string Full URL
 */
function getFullUrl($path = '') {
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

/**
 * Generate URL untuk file upload
 * 
 * @param string $filename Nama file
 * @return string Full URL ke file
 */
function getUploadUrl($filename) {
    return UPLOAD_URL . $filename;
}

// ============================================================================
// DEBUGGING INFO (Hanya tampil di localhost jika DEBUG_MODE aktif)
// ============================================================================
if (DEBUG_MODE && isset($_GET['debug_config'])) {
    echo "<pre style='background: #f5f5f5; padding: 20px; border: 1px solid #ddd;'>";
    echo "<h2>Configuration Debug Info</h2>";
    echo "<strong>Environment:</strong> " . ($isLocalhost ? 'LOCALHOST' : 'PRODUCTION') . "\n\n";
    echo "<strong>BASE_URL:</strong> " . BASE_URL . "\n";
    echo "<strong>UPLOAD_DIR:</strong> " . UPLOAD_DIR . "\n";
    echo "<strong>UPLOAD_URL:</strong> " . UPLOAD_URL . "\n\n";
    echo "<strong>Server Info:</strong>\n";
    echo "  - SERVER_NAME: " . $_SERVER['SERVER_NAME'] . "\n";
    echo "  - SERVER_PORT: " . $_SERVER['SERVER_PORT'] . "\n";
    echo "  - SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
    echo "  - DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
    echo "  - PHP Version: " . PHP_VERSION . "\n\n";
    echo "<strong>Example URLs:</strong>\n";
    echo "  - Login: " . getFullUrl('login.php') . "\n";
    echo "  - Dashboard: " . getFullUrl('index.php') . "\n";
    echo "  - Upload example: " . getUploadUrl('example.pdf') . "\n";
    echo "</pre>";
    exit;
}