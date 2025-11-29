<?php
// modules/surat/surat_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/surat_service.php';

requireLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser();

try {
    switch ($action) {
        case 'create':
            // Validate role - all can create
            $data = [
                'id_jenis' => (int)$_POST['id_jenis'],
                'nomor_agenda' => generateNomorAgenda((int)$_POST['id_jenis']),
                'nomor_surat' => sanitize($_POST['nomor_surat']),
                'tanggal_surat' => sanitize($_POST['tanggal_surat']),
                'tanggal_diterima' => !empty($_POST['tanggal_diterima']) ? sanitize($_POST['tanggal_diterima']) : null,
                'dari_instansi' => !empty($_POST['dari_instansi']) ? sanitize($_POST['dari_instansi']) : null,
                'ke_instansi' => !empty($_POST['ke_instansi']) ? sanitize($_POST['ke_instansi']) : null,
                'alamat_surat' => sanitize($_POST['alamat_surat']),
                'perihal' => sanitize($_POST['perihal']),
                'status_surat' => 'baru',
                'dibuat_oleh' => $user['id']
            ];
            
            // Handle file upload
            if (isset($_FILES['lampiran_file']) && $_FILES['lampiran_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = uploadFile($_FILES['lampiran_file']);
                if (!$upload['success']) {
                    throw new Exception($upload['message']);
                }
                $data['lampiran_file'] = $upload['filename'];
            }
            
            $suratId = SuratService::create($data);
            logActivity($user['id'], 'tambah_surat', "Menambah surat: {$data['nomor_agenda']}");
            
            setFlash('success', 'Surat berhasil ditambahkan');
            redirect('../surat.php?success=added');
            break;
            
        case 'update':
            requireRole(['admin', 'superadmin']);
            
            $id = (int)$_POST['id'];
            $surat = SuratService::getById($id);
            
            if (!$surat) {
                throw new Exception('Surat tidak ditemukan');
            }
            
            $data = [
                'id_jenis' => (int)$_POST['id_jenis'],
                'nomor_surat' => sanitize($_POST['nomor_surat']),
                'tanggal_surat' => sanitize($_POST['tanggal_surat']),
                'tanggal_diterima' => !empty($_POST['tanggal_diterima']) ? sanitize($_POST['tanggal_diterima']) : null,
                'dari_instansi' => !empty($_POST['dari_instansi']) ? sanitize($_POST['dari_instansi']) : null,
                'ke_instansi' => !empty($_POST['ke_instansi']) ? sanitize($_POST['ke_instansi']) : null,
                'alamat_surat' => sanitize($_POST['alamat_surat']),
                'perihal' => sanitize($_POST['perihal']),
                'status_surat' => $surat['status_surat'],
                'lampiran_file' => $surat['lampiran_file']
            ];
            
            // Handle file upload
            if (isset($_FILES['lampiran_file']) && $_FILES['lampiran_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = uploadFile($_FILES['lampiran_file'], $surat['lampiran_file']);
                if (!$upload['success']) {
                    throw new Exception($upload['message']);
                }
                $data['lampiran_file'] = $upload['filename'];
            }
            
            SuratService::update($id, $data);
            logActivity($user['id'], 'edit_surat', "Mengedit surat ID: {$id}");
            
            setFlash('success', 'Surat berhasil diperbarui');
            redirect('../surat.php?success=updated');
            break;
            
        case 'delete':
            requireRole(['superadmin']);
            
            $id = (int)$_POST['id'];
            $surat = SuratService::getById($id);
            
            if (!$surat) {
                throw new Exception('Surat tidak ditemukan');
            }
            
            // Delete file if exists
            if ($surat['lampiran_file'] && file_exists(UPLOAD_DIR . $surat['lampiran_file'])) {
                unlink(UPLOAD_DIR . $surat['lampiran_file']);
            }
            
            SuratService::delete($id);
            logActivity($user['id'], 'hapus_surat', "Menghapus surat ID: {$id}");
            
            setFlash('success', 'Surat berhasil dihapus');
            redirect('../surat.php?success=deleted');
            break;
            
        case 'update_status':
            requireRole(['admin', 'superadmin']);
            
            $id = (int)$_POST['id'];
            $status = sanitize($_POST['status']);
            
            $allowedStatus = ['baru', 'proses', 'ditolak', 'disetujui', 'arsip'];
            if (!in_array($status, $allowedStatus)) {
                throw new Exception('Status tidak valid');
            }
            
            SuratService::updateStatus($id, $status);
            logActivity($user['id'], 'update_status_surat', "Mengubah status surat ID {$id} menjadi {$status}");
            
            setFlash('success', 'Status surat berhasil diperbarui');
            redirect('../surat.php?success=updated');
            break;
            
        case 'arsipkan':
            requireRole(['admin', 'superadmin']);
            
            $id = (int)$_POST['id'];
            SuratService::updateStatus($id, 'arsip');
            logActivity($user['id'], 'arsip_surat', "Mengarsipkan surat ID: {$id}");
            
            setFlash('success', 'Surat berhasil diarsipkan');
            redirect('../surat.php?success=updated');
            break;
            
        default:
            throw new Exception('Action tidak valid');
    }
    
} catch (Exception $e) {
    setFlash('error', $e->getMessage());
    redirect('../surat.php?error=process_failed');
}