<?php
// public/partials/header.php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - <?= APP_NAME ?></title>
    
    <!-- Tailwind CSS via Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        /* Custom styles */
        [x-cloak] { display: none !important; }
        
        .sidebar-active {
            @apply bg-blue-50 border-r-4 border-blue-600 text-blue-600;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php
    // Handle flash messages
    if (hasFlash()) {
        $flash = getFlash();
        $icon = $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'error' : 'info');
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: '{$icon}',
                    title: '" . ucfirst($flash['type']) . "',
                    text: '" . addslashes($flash['message']) . "',
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        </script>";
    }
    
    // Handle query parameter alerts
    if (isset($_GET['success'])) {
        $messages = [
            'added' => 'Data berhasil ditambahkan',
            'updated' => 'Data berhasil diperbarui',
            'deleted' => 'Data berhasil dihapus',
            'sent' => 'Disposisi berhasil dikirim',
            'approved' => 'Surat berhasil disetujui',
            'rejected' => 'Surat berhasil ditolak'
        ];
        $msg = $messages[$_GET['success']] ?? 'Operasi berhasil';
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: '{$msg}',
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        </script>";
    }
    
    if (isset($_GET['error'])) {
        $messages = [
            'not_logged_in' => 'Silakan login terlebih dahulu',
            'unauthorized' => 'Anda tidak memiliki akses ke halaman ini',
            'invalid_data' => 'Data yang dikirim tidak valid',
            'not_found' => 'Data tidak ditemukan'
        ];
        $msg = $messages[$_GET['error']] ?? 'Terjadi kesalahan';
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{$msg}',
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        </script>";
    }
    ?>