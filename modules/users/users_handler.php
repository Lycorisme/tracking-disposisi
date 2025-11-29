<?php
// modules/users/users_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/users_service.php';

requireLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser();

try {
    switch ($action) {
        case 'update_profile':
            $data = [
                'nama_lengkap' => sanitize($_POST['nama_lengkap']),
                'email' => sanitize($_POST['email'])
            ];
            
            if (empty($data['nama_lengkap']) || empty($data['email'])) {
                throw new Exception('Nama dan email harus diisi');
            }
            
            // Check email uniqueness
            if (UsersService::emailExists($data['email'], $user['id'])) {
                throw new Exception('Email sudah digunakan oleh user lain');
            }
            
            UsersService::updateProfile($user['id'], $data);
            
            // Update session
            $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
            $_SESSION['email'] = $data['email'];
            
            logActivity($user['id'], 'update_profil', 'Mengupdate profil');
            
            setFlash('success', 'Profil berhasil diperbarui');
            redirect('../profil.php?success=updated');
            break;
            
        case 'change_password':
            $passwordLama = $_POST['password_lama'];
            $passwordBaru = $_POST['password_baru'];
            $passwordKonfirmasi = $_POST['password_konfirmasi'];
            
            if (empty($passwordLama) || empty($passwordBaru) || empty($passwordKonfirmasi)) {
                throw new Exception('Semua field password harus diisi');
            }
            
            // Get current user data
            $currentUser = UsersService::getById($user['id']);
            
            // Verify old password (plain text as per requirement)
            if ($passwordLama !== $currentUser['password']) {
                throw new Exception('Password lama tidak sesuai');
            }
            
            // Validate new password
            if ($passwordBaru !== $passwordKonfirmasi) {
                throw new Exception('Password baru dan konfirmasi tidak cocok');
            }
            
            if (strlen($passwordBaru) < 6) {
                throw new Exception('Password baru minimal 6 karakter');
            }
            
            UsersService::changePassword($user['id'], $passwordBaru);
            logActivity($user['id'], 'ganti_password', 'Mengganti password');
            
            setFlash('success', 'Password berhasil diubah');
            redirect('../profil.php?success=password_changed');
            break;
            
        default:
            throw new Exception('Action tidak valid');
    }
    
} catch (Exception $e) {
    setFlash('error', $e->getMessage());
    redirect('../profil.php?error=process_failed');
}