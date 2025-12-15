<?php
// modules/notifications/notification_service.php

require_once __DIR__ . '/../../config/database.php';

class NotificationService {
    
    /**
     * Create notification untuk user tertentu
     */
    public static function create($data) {
        $query = "INSERT INTO notifications (
                    user_id, type, title, message, surat_id, disposisi_id, url
                  ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['user_id'],
            $data['type'],
            $data['title'],
            $data['message'] ?? null,
            $data['surat_id'] ?? null,
            $data['disposisi_id'] ?? null,
            $data['url'] ?? null
        ];
        
        return dbExecute($query, $params, 'isssiis');
    }
    
    /**
     * Notifikasi: Surat baru ditugaskan (untuk ANAK MAGANG)
     */
    public static function notifyDisposisiBaru($disposisiId) {
        $query = "SELECT d.*, s.nomor_agenda, s.perihal,
                         u1.nama_lengkap as dari_nama,
                         u2.id as ke_user_id, u2.nama_lengkap as ke_nama
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  JOIN users u1 ON d.dari_user_id = u1.id
                  JOIN users u2 ON d.ke_user_id = u2.id
                  WHERE d.id = ?";
        
        $disposisi = dbSelectOne($query, [$disposisiId], 'i');
        
        if ($disposisi) {
            self::create([
                'user_id' => $disposisi['ke_user_id'],
                'type' => 'disposisi_baru',
                'title' => 'Surat Baru Ditugaskan',
                'message' => "Anda ditugaskan untuk menangani surat: {$disposisi['nomor_agenda']} - {$disposisi['perihal']}",
                'surat_id' => $disposisi['id_surat'],
                'disposisi_id' => $disposisiId,
                'url' => '/public/surat_detail.php?id=' . $disposisi['id_surat']
            ]);
        }
    }
    
    /**
     * Notifikasi: Surat masuk baru (untuk KARYAWAN)
     * Dipanggil saat admin/karyawan create surat baru
     */
    public static function notifySuratMasuk($suratId, $excludeUserId = null) {
        $query = "SELECT id, nomor_agenda, perihal FROM surat WHERE id = ?";
        $surat = dbSelectOne($query, [$suratId], 'i');
        
        if ($surat) {
            // Ambil semua karyawan (role admin) kecuali yang buat surat
            $userQuery = "SELECT id FROM users 
                          WHERE id_role = 2 
                          AND status_aktif = 1";
            
            $params = [];
            $types = '';
            
            if ($excludeUserId) {
                $userQuery .= " AND id != ?";
                $params[] = $excludeUserId;
                $types = 'i';
            }
            
            $karyawans = dbSelect($userQuery, $params, $types);
            
            foreach ($karyawans as $karyawan) {
                self::create([
                    'user_id' => $karyawan['id'],
                    'type' => 'surat_masuk',
                    'title' => 'Surat Baru Masuk',
                    'message' => "Surat baru: {$surat['nomor_agenda']} - {$surat['perihal']}",
                    'surat_id' => $suratId,
                    'url' => '/public/surat_detail.php?id=' . $suratId
                ]);
            }
        }
    }
    
    /**
     * Notifikasi: Update status dari anak magang (untuk KARYAWAN yang assign)
     */
    public static function notifySuratUpdate($disposisiId, $newStatus) {
        $query = "SELECT d.*, s.nomor_agenda, s.perihal,
                         u2.nama_lengkap as user_nama
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  JOIN users u2 ON d.ke_user_id = u2.id
                  WHERE d.id = ?";
        
        $disposisi = dbSelectOne($query, [$disposisiId], 'i');
        
        if ($disposisi) {
            $statusLabel = ucfirst($newStatus);
            
            self::create([
                'user_id' => $disposisi['dari_user_id'], // Kirim ke yang assign
                'type' => 'surat_update',
                'title' => "Surat {$statusLabel}",
                'message' => "{$disposisi['user_nama']} telah mengupdate status surat {$disposisi['nomor_agenda']} menjadi: {$statusLabel}",
                'surat_id' => $disposisi['id_surat'],
                'disposisi_id' => $disposisiId,
                'url' => '/public/surat_detail.php?id=' . $disposisi['id_surat']
            ]);
        }
    }
    
    /**
     * Notifikasi: Surat selesai (untuk SUPERADMIN)
     */
    public static function notifySuratSelesai($suratId) {
        $query = "SELECT nomor_agenda, perihal FROM surat WHERE id = ?";
        $surat = dbSelectOne($query, [$suratId], 'i');
        
        if ($surat) {
            // Kirim ke semua superadmin
            $superadmins = dbSelect(
                "SELECT id FROM users WHERE id_role = 1 AND status_aktif = 1"
            );
            
            foreach ($superadmins as $admin) {
                self::create([
                    'user_id' => $admin['id'],
                    'type' => 'surat_selesai',
                    'title' => 'Surat Selesai Dianalisa',
                    'message' => "Surat {$surat['nomor_agenda']} telah selesai dianalisa",
                    'surat_id' => $suratId,
                    'url' => '/public/surat_detail.php?id=' . $suratId
                ]);
            }
        }
    }
    
    /**
     * Get notifikasi user (max 5 terbaru)
     */
    public static function getRecent($userId, $limit = 5) {
        $query = "SELECT * FROM notifications 
                  WHERE user_id = ? 
                  ORDER BY created_at DESC 
                  LIMIT ?";
        
        return dbSelect($query, [$userId, $limit], 'ii');
    }
    
    /**
     * Count unread notifications
     */
    public static function countUnread($userId) {
        $query = "SELECT COUNT(*) as total 
                  FROM notifications 
                  WHERE user_id = ? AND is_read = 0";
        
        $result = dbSelectOne($query, [$userId], 'i');
        return $result['total'] ?? 0;
    }
    
    /**
     * Mark notification as read
     */
    public static function markAsRead($notificationId, $userId) {
        $query = "UPDATE notifications 
                  SET is_read = 1, read_at = CURRENT_TIMESTAMP 
                  WHERE id = ? AND user_id = ?";
        
        return dbExecute($query, [$notificationId, $userId], 'ii');
    }
    
    /**
     * Mark all as read
     */
    public static function markAllAsRead($userId) {
        $query = "UPDATE notifications 
                  SET is_read = 1, read_at = CURRENT_TIMESTAMP 
                  WHERE user_id = ? AND is_read = 0";
        
        return dbExecute($query, [$userId], 'i');
    }
    
    /**
     * Delete old notifications (older than 30 days)
     */
    public static function cleanup() {
        $query = "DELETE FROM notifications 
                  WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        return dbExecute($query);
    }
}
