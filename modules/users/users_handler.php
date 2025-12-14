<?php
// modules/users/users_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/users_service.php';

// Set Header JSON
header('Content-Type: application/json');

requireLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser();

try {
    // --- HANDLER UPDATE PROFIL (VIA AJAX) ---
    if ($action === 'update_profile') {
        $data = [
            'nama_lengkap' => sanitize($_POST['nama_lengkap']),
            'email' => sanitize($_POST['email']),
            'nama_bagian_custom' => sanitize($_POST['nama_bagian_custom'] ?? '')
        ];
        
        if (empty($data['nama_lengkap']) || empty($data['email'])) throw new Exception('Nama dan email harus diisi');
        if (UsersService::emailExists($data['email'], $user['id'])) throw new Exception('Email sudah digunakan oleh user lain');
        
        if (!UsersService::updateProfile($user['id'], $data)) throw new Exception('Gagal mengupdate database');
        
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['email'] = $data['email'];
        logActivity($user['id'], 'update_profil', 'Mengupdate profil');
        
        echo json_encode(['status' => 'success', 'message' => 'Profil berhasil diperbarui', 'data' => $data]);
        exit;
    }
    
    // --- HANDLER GANTI PASSWORD ---
    if ($action === 'change_password') {
        // ... (Kode sama seperti sebelumnya) ...
        $passwordLama = $_POST['password_lama'];
        $passwordBaru = $_POST['password_baru'];
        $passwordKonfirmasi = $_POST['password_konfirmasi'];
        
        if (empty($passwordLama) || empty($passwordBaru) || empty($passwordKonfirmasi)) throw new Exception('Semua field password harus diisi');
        
        $currentUser = UsersService::getById($user['id']);
        if ($passwordLama !== $currentUser['password']) throw new Exception('Password lama tidak sesuai');
        if ($passwordBaru !== $passwordKonfirmasi) throw new Exception('Password baru tidak cocok');
        if (strlen($passwordBaru) < 6) throw new Exception('Password minimal 6 karakter');
        
        if (!UsersService::changePassword($user['id'], $passwordBaru)) throw new Exception('Gagal mengupdate password');
        
        logActivity($user['id'], 'ganti_password', 'Mengganti password');
        echo json_encode(['status' => 'success', 'message' => 'Password berhasil diubah']);
        exit;
    }

    // ============================================================================
    // HANDLER KHUSUS ADMIN
    // ============================================================================
    if (in_array($action, ['approve', 'reject', 'delete', 'change_role', 'check_activity'])) {
        
        if (!hasRole('superadmin')) throw new Exception('Akses ditolak. Hanya Superadmin.');
        
        $targetId = $_POST['id'] ?? 0;
        if (!$targetId) throw new Exception('ID User tidak valid');

        switch ($action) {
            // --- CEK AKTIVITAS SEBELUM HAPUS ---
            case 'check_activity':
                $hasData = UsersService::hasRelatedData($targetId);
                echo json_encode(['status' => 'success', 'has_data' => $hasData]);
                break;

            case 'approve':
                if (UsersService::updateStatus($targetId, 'active')) {
                    echo json_encode(['status' => 'success', 'message' => 'User berhasil diaktifkan']);
                } else { throw new Exception('Gagal mengaktifkan user'); }
                break;

            case 'reject':
                if (UsersService::updateStatus($targetId, 'rejected')) {
                    echo json_encode(['status' => 'success', 'message' => 'User berhasil ditolak']);
                } else { throw new Exception('Gagal menolak user'); }
                break;

            case 'delete':
                if ($targetId == $user['id']) throw new Exception('Tidak dapat menghapus akun sendiri.');
                
                // Panggil fungsi delete yang sudah di-update di Service (Manual Cascade)
                if (UsersService::delete($targetId)) {
                    echo json_encode(['status' => 'success', 'message' => 'User dan semua data terkait berhasil dihapus']);
                } else {
                    throw new Exception('Gagal menghapus user. Terjadi kesalahan sistem.');
                }
                break;

            case 'change_role':
                if ($targetId == $user['id']) throw new Exception('Tidak dapat mengubah role akun sendiri.');
                $roleId = $_POST['role_id'] ?? 0;
                if (UsersService::updateRole($targetId, $roleId)) {
                    echo json_encode(['status' => 'success', 'message' => 'Role berhasil diperbarui']);
                } else { throw new Exception('Gagal memperbarui role'); }
                break;
        }
        exit;
    }

    throw new Exception('Action tidak valid');

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>