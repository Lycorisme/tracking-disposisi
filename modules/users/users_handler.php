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
    switch ($action) {
        // --- HANDLER UPDATE PROFIL (VIA AJAX) ---
        case 'update_profile':
            // 1. Tangkap Input
            $data = [
                'nama_lengkap' => sanitize($_POST['nama_lengkap']),
                'email' => sanitize($_POST['email']),
                // Tangkap input 'nama_bagian_custom'. Jika kosong string, biarkan string kosong agar di service bisa dicek.
                'nama_bagian_custom' => isset($_POST['nama_bagian_custom']) ? sanitize($_POST['nama_bagian_custom']) : ''
            ];
            
            // 2. Validasi
            if (empty($data['nama_lengkap']) || empty($data['email'])) {
                throw new Exception('Nama dan email harus diisi');
            }
            
            // 3. Cek Email Kembar
            if (UsersService::emailExists($data['email'], $user['id'])) {
                throw new Exception('Email sudah digunakan oleh user lain');
            }
            
            // 4. Update Database
            if (!UsersService::updateProfile($user['id'], $data)) {
                throw new Exception('Gagal mengupdate database');
            }
            
            // 5. Update Session (Opsional, nama & email tetap diupdate)
            $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
            $_SESSION['email'] = $data['email'];
            
            // 6. Log Aktivitas
            logActivity($user['id'], 'update_profil', 'Mengupdate profil');
            
            // 7. RETURN JSON
            echo json_encode([
                'status' => 'success', 
                'message' => 'Profil berhasil diperbarui',
                'data' => $data
            ]);
            exit;

        // --- HANDLER GANTI PASSWORD ---
        case 'change_password':
            $passwordLama = $_POST['password_lama'];
            $passwordBaru = $_POST['password_baru'];
            $passwordKonfirmasi = $_POST['password_konfirmasi'];
            
            if (empty($passwordLama) || empty($passwordBaru) || empty($passwordKonfirmasi)) {
                throw new Exception('Semua field password harus diisi');
            }
            
            $currentUser = UsersService::getById($user['id']);
            
            if ($passwordLama !== $currentUser['password']) {
                throw new Exception('Password lama tidak sesuai');
            }
            
            if ($passwordBaru !== $passwordKonfirmasi) {
                throw new Exception('Password baru dan konfirmasi tidak cocok');
            }
            
            if (strlen($passwordBaru) < 6) {
                throw new Exception('Password baru minimal 6 karakter');
            }
            
            if (!UsersService::changePassword($user['id'], $passwordBaru)) {
                throw new Exception('Gagal mengupdate password');
            }

            logActivity($user['id'], 'ganti_password', 'Mengganti password');
            
            echo json_encode([
                'status' => 'success', 
                'message' => 'Password berhasil diubah'
            ]);
            exit;

        // --- HANDLER ADMIN ---
        case 'approve':
        case 'reject':
        case 'delete':
        case 'change_role':
            if (!hasRole('superadmin')) throw new Exception('Akses ditolak.');
            
            $targetId = $_POST['id'] ?? 0;
            if (!$targetId) throw new Exception('ID tidak valid');

            $success = false;
            $msg = '';

            if ($action === 'approve') {
                $success = UsersService::updateStatus($targetId, 'active');
                $msg = 'User berhasil diaktifkan';
            } elseif ($action === 'reject') {
                $success = UsersService::updateStatus($targetId, 'rejected');
                $msg = 'User berhasil ditolak';
            } elseif ($action === 'delete') {
                if ($targetId == $user['id']) throw new Exception('Tidak bisa menghapus akun sendiri');
                $success = UsersService::delete($targetId);
                $msg = 'User berhasil dihapus';
            } elseif ($action === 'change_role') {
                if ($targetId == $user['id']) throw new Exception('Tidak bisa mengubah role sendiri');
                $roleId = $_POST['role_id'] ?? 0;
                $success = UsersService::updateRole($targetId, $roleId);
                $msg = 'Role berhasil diperbarui';
            }

            if ($success) {
                echo json_encode(['status' => 'success', 'message' => $msg]);
            } else {
                throw new Exception('Gagal memproses permintaan');
            }
            exit;

        default:
            throw new Exception('Action tidak valid');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
    exit;
}
?>