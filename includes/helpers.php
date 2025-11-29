<?php
// includes/helpers.php

// Redirect helper
function redirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    } else {
        echo "<script>window.location.href='" . $url . "';</script>";
        exit;
    }
}

// Flash message helper
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function hasFlash() {
    return isset($_SESSION['flash']);
}

// Format tanggal Indonesia
function formatTanggal($date, $format = 'd M Y') {
    if (empty($date) || $date == '0000-00-00') {
        return '-';
    }
    
    $bulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $bulan[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    if ($format == 'd M Y') {
        return "$day $month $year";
    } elseif ($format == 'd M Y H:i') {
        $time = date('H:i', $timestamp);
        return "$day $month $year $time";
    } else {
        return date($format, $timestamp);
    }
}

// Format datetime Indonesia
function formatDateTime($datetime) {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
        return '-';
    }
    return formatTanggal($datetime, 'd M Y H:i');
}

// Sanitize input
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Generate nomor agenda otomatis
function generateNomorAgenda($jenisId) {
    $prefix = '';
    switch ($jenisId) {
        case 1: $prefix = 'SM'; break; // Surat Masuk
        case 2: $prefix = 'SK'; break; // Surat Keluar
        case 3: $prefix = 'PR'; break; // Proposal
        default: $prefix = 'SU';
    }
    
    $year = date('Y');
    $month = date('m');
    
    // Get last number
    $query = "SELECT nomor_agenda FROM surat 
              WHERE id_jenis = ? 
              AND YEAR(created_at) = ? 
              ORDER BY id DESC LIMIT 1";
    
    $lastSurat = dbSelectOne($query, [$jenisId, $year], 'ii');
    
    $lastNumber = 1;
    if ($lastSurat) {
        // Extract number from format: SM/001/01/2025
        $parts = explode('/', $lastSurat['nomor_agenda']);
        if (isset($parts[1])) {
            $lastNumber = (int)$parts[1] + 1;
        }
    }
    
    $number = str_pad($lastNumber, 3, '0', STR_PAD_LEFT);
    return "$prefix/$number/$month/$year";
}

// Upload file handler
function uploadFile($file, $oldFile = null) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => $oldFile];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error saat upload file'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Ukuran file maksimal 5MB'];
    }
    
    // Check extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }
    
    // Create upload directory if not exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $destination = UPLOAD_DIR . $filename;
    
    // Delete old file if exists
    if ($oldFile && file_exists(UPLOAD_DIR . $oldFile)) {
        unlink(UPLOAD_DIR . $oldFile);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Gagal menyimpan file'];
}

// Get status badge class
function getStatusBadge($status) {
    $badges = [
        'baru' => 'bg-blue-100 text-blue-800',
        'proses' => 'bg-yellow-100 text-yellow-800',
        'disetujui' => 'bg-green-100 text-green-800',
        'ditolak' => 'bg-red-100 text-red-800',
        'arsip' => 'bg-gray-100 text-gray-800'
    ];
    
    return $badges[$status] ?? 'bg-gray-100 text-gray-800';
}

// Get disposisi status badge
function getDisposisiStatusBadge($status) {
    $badges = [
        'dikirim' => 'bg-blue-100 text-blue-800',
        'diterima' => 'bg-indigo-100 text-indigo-800',
        'diproses' => 'bg-yellow-100 text-yellow-800',
        'selesai' => 'bg-green-100 text-green-800',
        'ditolak' => 'bg-red-100 text-red-800'
    ];
    
    return $badges[$status] ?? 'bg-gray-100 text-gray-800';
}

// Truncate text
function truncate($text, $length = 50) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

// Check if string is valid date
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}