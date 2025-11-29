<?php
// modules/disposisi/disposisi_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/disposisi_service.php';
require_once __DIR__ . '/../surat/surat_service.php';

requireLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser();

try {
    switch ($action) {
        case 'create':
            $suratId = (int)$_POST['id_surat'];
            $keUserId = (int)$_POST['ke_user_id'];
            $catatan = sanitize($_POST['catatan'] ?? '');
            
            // Validate surat exists
            $surat = SuratService::getById($suratId);
            if (!$surat) {
                throw new Exception('Surat tidak ditemukan');
            }
            
            // Check if user can dispose
            if (!DisposisiService::canDispose($user['id'], $suratId) && $surat['dibuat_oleh'] != $user['id']) {
                throw new Exception('Anda tidak memiliki akses untuk mendisposisi surat ini');
            }
            
            // Validate target user
            $targetUser = dbSelectOne("SELECT id, nama_lengkap FROM users WHERE id = ? AND status_aktif = 1", [$keUserId], 'i');
            if (!$targetUser) {
                throw new Exception('User tujuan tidak valid');
            }
            
            // Prevent disposing to self
            if ($keUserId == $user['id']) {
                throw new Exception('Tidak dapat mendisposisi ke diri sendiri');
            }
            
            $data = [
                'id_surat' => $suratId,
                'dari_user_id' => $user['id'],
                'ke_user_id' => $keUserId,
                'status_disposisi' => 'dikirim',
                'catatan' => $catatan
            ];
            
            $disposisiId = DisposisiService::create($data);
            
            // Update surat status to 'proses' if still 'baru'
            if ($surat['status_surat'] === 'baru') {
                SuratService::updateStatus($suratId, 'proses');
            }
            
            logActivity($user['id'], 'disposisi_surat', "Mendisposisi surat {$surat['nomor_agenda']} ke {$targetUser['nama_lengkap']}");
            
            setFlash('success', 'Disposisi berhasil dikirim');
            
            // Redirect based on referrer
            $redirect = $_POST['redirect'] ?? 'disposisi_outbox.php';
            redirect("../{$redirect}?success=sent");
            break;
            
        case 'update_status':
            $id = (int)$_POST['id'];
            $status = sanitize($_POST['status']);
            $catatan = sanitize($_POST['catatan'] ?? '');
            
            // Validate status
            $allowedStatus = ['diterima', 'diproses', 'selesai', 'ditolak'];
            if (!in_array($status, $allowedStatus)) {
                throw new Exception('Status tidak valid');
            }
            
            // Get disposisi
            $disposisi = DisposisiService::getById($id);
            if (!$disposisi) {
                throw new Exception('Disposisi tidak ditemukan');
            }
            
            // Check if user is the recipient
            if ($disposisi['ke_user_id'] != $user['id']) {
                throw new Exception('Anda tidak memiliki akses untuk mengubah disposisi ini');
            }
            
            DisposisiService::updateStatus($id, $status, $catatan);
            
            // Update surat status based on disposition status
            if ($status === 'selesai') {
                // Check if all dispositions are completed
                $allDispositions = DisposisiService::getHistoryBySurat($disposisi['id_surat']);
                $allCompleted = true;
                foreach ($allDispositions as $disp) {
                    if ($disp['status_disposisi'] !== 'selesai' && $disp['id'] != $id) {
                        $allCompleted = false;
                        break;
                    }
                }
                
                if ($allCompleted) {
                    SuratService::updateStatus($disposisi['id_surat'], 'disetujui');
                }
            } elseif ($status === 'ditolak') {
                SuratService::updateStatus($disposisi['id_surat'], 'ditolak');
            }
            
            logActivity($user['id'], 'update_disposisi', "Mengubah status disposisi ID {$id} menjadi {$status}");
            
            setFlash('success', 'Status disposisi berhasil diperbarui');
            
            $redirect = $_POST['redirect'] ?? 'disposisi_inbox.php';
            redirect("../{$redirect}?success=updated");
            break;
            
        default:
            throw new Exception('Action tidak valid');
    }
    
} catch (Exception $e) {
    setFlash('error', $e->getMessage());
    
    $redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? 'disposisi.php';
    redirect("../{$redirect}?error=process_failed");
}