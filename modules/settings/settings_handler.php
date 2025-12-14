<?php
// modules/settings/settings_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/settings_service.php';

// Set header response ke JSON
header('Content-Type: application/json');

// Jika bukan login atau bukan superadmin, kirim error JSON
if (!isLoggedIn() || !hasRole('superadmin')) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Akses ditolak.'
    ]);
    exit;
}

$action = $_POST['action'] ?? '';
$user = getCurrentUser();

try {
    switch ($action) {
        case 'update':
            $currentSettings = SettingsService::getSettings();
            
            if (!$currentSettings) {
                SettingsService::initializeDefaults();
                $currentSettings = SettingsService::getSettings();
            }
            
            // Prepare data
            $data = [
                'app_name' => sanitize($_POST['app_name'] ?? 'Tracking Disposisi'),
                'app_description' => sanitize($_POST['app_description'] ?? ''),
                'app_logo' => $currentSettings['app_logo'],
                'app_favicon' => $currentSettings['app_favicon'],
                'theme_color' => sanitize($_POST['theme_color'] ?? 'blue'), // TANGKAP TEMA WARNA
                'instansi_nama' => sanitize($_POST['instansi_nama'] ?? ''),
                'instansi_alamat' => sanitize($_POST['instansi_alamat'] ?? ''),
                'instansi_telepon' => sanitize($_POST['instansi_telepon'] ?? ''),
                'instansi_email' => sanitize($_POST['instansi_email'] ?? ''),
                'instansi_logo' => $currentSettings['instansi_logo'],
                'ttd_nama_penandatangan' => sanitize($_POST['ttd_nama_penandatangan'] ?? ''),
                'ttd_nip' => sanitize($_POST['ttd_nip'] ?? ''),
                'ttd_jabatan' => sanitize($_POST['ttd_jabatan'] ?? 'Kepala Dinas'),
                'ttd_kota' => sanitize($_POST['ttd_kota'] ?? 'Banjarmasin'),
                'ttd_image' => $currentSettings['ttd_image'] ?? null
            ];
            
            // Variabel untuk melacak perubahan gambar agar bisa update preview di frontend
            $updatedImages = [];

            // Handle app_logo upload
            if (isset($_FILES['app_logo']) && $_FILES['app_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = SettingsService::uploadFile($_FILES['app_logo'], $currentSettings['app_logo']);
                if ($uploadResult['success']) {
                    $data['app_logo'] = $uploadResult['filename'];
                    $updatedImages['app_logo_url'] = SETTINGS_UPLOAD_URL . $uploadResult['filename'];
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }
            
            // Handle app_favicon upload
            if (isset($_FILES['app_favicon']) && $_FILES['app_favicon']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = SettingsService::uploadFile($_FILES['app_favicon'], $currentSettings['app_favicon']);
                if ($uploadResult['success']) {
                    $data['app_favicon'] = $uploadResult['filename'];
                    $updatedImages['app_favicon_url'] = SETTINGS_UPLOAD_URL . $uploadResult['filename'];
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }
            
            // Handle instansi_logo upload
            if (isset($_FILES['instansi_logo']) && $_FILES['instansi_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = SettingsService::uploadFile($_FILES['instansi_logo'], $currentSettings['instansi_logo']);
                if ($uploadResult['success']) {
                    $data['instansi_logo'] = $uploadResult['filename'];
                    $updatedImages['instansi_logo_url'] = SETTINGS_UPLOAD_URL . $uploadResult['filename'];
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }

            // Handle TTD Image upload
            if (isset($_FILES['ttd_image']) && $_FILES['ttd_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = SettingsService::uploadFile($_FILES['ttd_image'], $currentSettings['ttd_image'] ?? null);
                if ($uploadResult['success']) {
                    $data['ttd_image'] = $uploadResult['filename'];
                    $updatedImages['ttd_image_url'] = SETTINGS_UPLOAD_URL . $uploadResult['filename'];
                } else {
                    throw new Exception("Gagal upload TTD: " . $uploadResult['message']);
                }
            }
            
            // Update settings
            SettingsService::update($data);
            
            // Clear settings cache
            clearSettingsCache();
            
            // Log activity
            logActivity($user['id'], 'update_settings', 'Mengupdate pengaturan sistem');
            
            // Response SUCCESS JSON
            echo json_encode([
                'status' => 'success',
                'message' => 'Pengaturan berhasil diperbarui',
                'updated_images' => $updatedImages
            ]);
            exit;
            
        default:
            throw new Exception('Action tidak valid');
    }
    
} catch (Exception $e) {
    // Response ERROR JSON
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
?>