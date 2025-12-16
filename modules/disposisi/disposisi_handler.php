<?php
// modules/disposisi/disposisi_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/disposisi_service.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser();
$userId = $user['id'];
$userRole = $user['id_role'] ?? 3;

try {
    switch ($action) {
        
        /**
         * ========== CREATE DISPOSISI ==========
         * Membuat disposisi baru
         */
        case 'create':
            // Validasi role - Magang tidak boleh disposisi
            if ($userRole == 3) {
                throw new Exception("Anda tidak memiliki akses untuk mendisposisi surat");
            }
            
            $idSurat = (int)($_POST['id_surat'] ?? 0);
            $keUserId = (int)($_POST['ke_user_id'] ?? 0);
            $catatan = trim($_POST['catatan'] ?? '');
            
            // Validasi input
            if (!$idSurat) {
                throw new Exception("ID Surat tidak valid");
            }
            
            if (!$keUserId) {
                throw new Exception("Pilih tujuan disposisi");
            }
            
            // Validasi tidak bisa disposisi ke diri sendiri
            if ($keUserId == $userId) {
                throw new Exception("Tidak bisa mendisposisi ke diri sendiri");
            }
            
            // Cek apakah surat bisa didisposisi
            $checkSurat = DisposisiService::checkSuratAvailability($idSurat);
            if (!$checkSurat['can_dispose']) {
                throw new Exception($checkSurat['message']);
            }
            
            // Cek apakah sudah ada disposisi ke user yang sama untuk surat yang sama
            $existingDisposisi = dbSelectOne(
                "SELECT id FROM disposisi WHERE id_surat = ? AND ke_user_id = ? AND status_disposisi NOT IN ('selesai', 'ditolak')",
                [$idSurat, $keUserId],
                'ii'
            );
            
            if ($existingDisposisi) {
                throw new Exception("Sudah ada disposisi aktif ke user tersebut untuk surat ini");
            }
            
            // Create disposisi
            $data = [
                'id_surat' => $idSurat,
                'dari_user_id' => $userId,
                'ke_user_id' => $keUserId,
                'catatan' => $catatan
            ];
            
            $disposisiId = DisposisiService::create($data);
            
            if ($disposisiId) {
                // Log activity
                $surat = dbSelectOne("SELECT nomor_agenda FROM surat WHERE id = ?", [$idSurat], 'i');
                $targetUser = dbSelectOne("SELECT nama_lengkap FROM users WHERE id = ?", [$keUserId], 'i');
                
                logActivity($userId, 'disposisi_surat', 
                    "Mendisposisi surat " . ($surat['nomor_agenda'] ?? '') . " ke " . ($targetUser['nama_lengkap'] ?? '')
                );
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Disposisi berhasil dikirim',
                    'disposisi_id' => $disposisiId
                ]);
            } else {
                throw new Exception("Gagal membuat disposisi");
            }
            break;
        
        /**
         * ========== UPDATE STATUS ==========
         * Update status disposisi (diterima, diproses, selesai, ditolak)
         */
        case 'update_status':
            $id = (int)($_POST['id'] ?? 0);
            $newStatus = trim($_POST['status'] ?? '');
            
            // PERBAIKAN: Mapping 'disetujui' ke 'selesai'
            // UI mengirim 'disetujui' untuk konsistensi bahasa, tapi DB disposisi pakai 'selesai'
            if ($newStatus === 'disetujui') {
                $newStatus = 'selesai';
            }
            
            if (!$id) {
                throw new Exception("ID Disposisi tidak valid");
            }
            
            $validStatuses = ['diterima', 'diproses', 'selesai', 'ditolak'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception("Status tidak valid: " . htmlspecialchars($newStatus));
            }
            
            // Cek apakah user berhak update disposisi ini
            $disposisi = DisposisiService::getById($id);
            
            if (!$disposisi) {
                throw new Exception("Disposisi tidak ditemukan");
            }
            
            // Yang bisa update: penerima disposisi atau superadmin
            if ($disposisi['ke_user_id'] != $userId && $userRole != 1) {
                throw new Exception("Anda tidak memiliki akses untuk mengubah status disposisi ini");
            }
            
            // Validasi flow status
            $currentStatus = $disposisi['status_disposisi'];
            $validFlow = [
                'dikirim' => ['diterima'],
                'diterima' => ['diproses', 'selesai', 'ditolak'],
                'diproses' => ['selesai', 'ditolak']
            ];
            
            // Superadmin bisa bypass flow
            if ($userRole != 1) {
                if ($currentStatus !== $newStatus) {
                    if (!isset($validFlow[$currentStatus])) {
                         throw new Exception("Status '$currentStatus' tidak dapat diubah lagi.");
                    }
                    if (!in_array($newStatus, $validFlow[$currentStatus])) {
                        throw new Exception("Perubahan status dari '$currentStatus' ke '$newStatus' tidak diizinkan");
                    }
                }
            }
            
            // Ambil catatan
            $catatan = isset($_POST['catatan']) ? trim($_POST['catatan']) : null;
            
            // Panggil Service (Service akan otomatis update status surat jadi 'disetujui' jika status disposisi 'selesai')
            if (DisposisiService::updateStatus($id, $newStatus, $catatan)) {
                logActivity($userId, 'update_disposisi', 
                    "Mengubah status disposisi ID $id menjadi $newStatus"
                );
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Status disposisi berhasil diubah menjadi ' . ucfirst($newStatus)
                ]);
            } else {
                throw new Exception("Gagal mengubah status disposisi");
            }
            break;
        
        /**
         * ========== CANCEL DISPOSISI ==========
         * Batalkan disposisi yang masih status 'dikirim'
         */
        case 'cancel':
            $id = (int)($_POST['id'] ?? 0);
            
            if (!$id) {
                throw new Exception("ID Disposisi tidak valid");
            }
            
            $result = DisposisiService::cancel($id, $userId);
            
            if ($result['success']) {
                logActivity($userId, 'cancel_disposisi', "Membatalkan disposisi ID $id");
                
                echo json_encode([
                    'status' => 'success',
                    'message' => $result['message']
                ]);
            } else {
                throw new Exception($result['message']);
            }
            break;
        
        /**
         * ========== GET DISPOSISI ==========
         * Get detail disposisi by ID
         */
        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            
            if (!$id) {
                throw new Exception("ID Disposisi tidak valid");
            }
            
            $disposisi = DisposisiService::getById($id);
            
            if (!$disposisi) {
                throw new Exception("Disposisi tidak ditemukan");
            }
            
            echo json_encode([
                'status' => 'success',
                'data' => $disposisi
            ]);
            break;
        
        /**
         * ========== GET HISTORY BY SURAT ==========
         * Get riwayat disposisi untuk surat tertentu
         */
        case 'get_history':
            $suratId = (int)($_GET['id_surat'] ?? 0);
            
            if (!$suratId) {
                throw new Exception("ID Surat tidak valid");
            }
            
            $history = DisposisiService::getHistoryBySurat($suratId);
            
            echo json_encode([
                'status' => 'success',
                'data' => $history
            ]);
            break;
        
        /**
         * ========== GET STATISTICS ==========
         * Get statistik disposisi
         */
        case 'statistics':
            $stats = DisposisiService::getStatistics($userId, $userRole);
            
            echo json_encode([
                'status' => 'success',
                'data' => $stats
            ]);
            break;
        
        /**
         * ========== BULK UPDATE STATUS ==========
         * Update status multiple disposisi sekaligus
         */
        case 'bulk_update':
            // Hanya superadmin
            if ($userRole != 1) {
                throw new Exception("Anda tidak memiliki akses untuk operasi ini");
            }
            
            $ids = $_POST['ids'] ?? [];
            $newStatus = trim($_POST['status'] ?? '');
            
            // Fix mapping for bulk too
            if ($newStatus === 'disetujui') {
                $newStatus = 'selesai';
            }
            
            if (empty($ids) || !is_array($ids)) {
                throw new Exception("Pilih disposisi yang akan diupdate");
            }
            
            $validStatuses = ['diterima', 'diproses', 'selesai', 'ditolak'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new Exception("Status tidak valid");
            }
            
            $successCount = 0;
            foreach ($ids as $id) {
                if (DisposisiService::updateStatus((int)$id, $newStatus, $userId)) {
                    $successCount++;
                }
            }
            
            logActivity($userId, 'bulk_update_disposisi', 
                "Mengubah status $successCount disposisi menjadi $newStatus"
            );
            
            echo json_encode([
                'status' => 'success',
                'message' => "$successCount disposisi berhasil diupdate"
            ]);
            break;
        
        /**
         * ========== REMIND ==========
         * Kirim reminder untuk disposisi yang belum direspon
         */
        case 'remind':
            $id = (int)($_POST['id'] ?? 0);
            
            if (!$id) {
                throw new Exception("ID Disposisi tidak valid");
            }
            
            $disposisi = DisposisiService::getById($id);
            
            if (!$disposisi) {
                throw new Exception("Disposisi tidak ditemukan");
            }
            
            // Hanya pengirim atau superadmin yang bisa remind
            if ($disposisi['dari_user_id'] != $userId && $userRole != 1) {
                throw new Exception("Anda tidak memiliki akses untuk mengirim reminder");
            }
            
            // Cek status - hanya bisa remind jika status 'dikirim' atau 'diterima'
            if (!in_array($disposisi['status_disposisi'], ['dikirim', 'diterima'])) {
                throw new Exception("Reminder hanya bisa dikirim untuk disposisi yang belum selesai");
            }
            
            // Cek cooldown reminder (1 jam)
            if ($disposisi['last_reminder_at']) {
                $lastReminder = strtotime($disposisi['last_reminder_at']);
                $cooldownEnd = $lastReminder + (60 * 60); // 1 jam
                
                if (time() < $cooldownEnd) {
                    $remainingMinutes = ceil(($cooldownEnd - time()) / 60);
                    throw new Exception("Tunggu $remainingMinutes menit lagi untuk mengirim reminder berikutnya");
                }
            }
            
            // Update last reminder time
            dbExecute(
                "UPDATE disposisi SET last_reminder_at = NOW() WHERE id = ?",
                [$id],
                'i'
            );
            
            // Kirim notifikasi reminder
            if (file_exists(__DIR__ . '/../notifications/notification_service.php')) {
                require_once __DIR__ . '/../notifications/notification_service.php';
                
                NotificationService::create([
                    'user_id' => $disposisi['ke_user_id'],
                    'type' => 'surat_reminder',
                    'title' => '⏰ Reminder: Disposisi Menunggu',
                    'message' => "Disposisi surat {$disposisi['nomor_agenda']} dari {$disposisi['dari_user_nama']} menunggu respon Anda",
                    'surat_id' => $disposisi['id_surat'],
                    'disposisi_id' => $id,
                    'url' => '/surat_detail.php?id=' . $disposisi['id_surat']
                ]);
            }
            
            logActivity($userId, 'remind_disposisi', "Mengirim reminder untuk disposisi ID $id");
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Reminder berhasil dikirim'
            ]);
            break;
        
        default:
            throw new Exception("Aksi tidak valid: $action");
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}