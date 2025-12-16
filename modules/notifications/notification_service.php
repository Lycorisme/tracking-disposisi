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
     * Notifikasi: Disposisi baru diterima
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
                'title' => 'Disposisi Surat Baru',
                'message' => "Anda menerima disposisi surat: {$disposisi['nomor_agenda']} - {$disposisi['perihal']} dari {$disposisi['dari_nama']}",
                'surat_id' => $disposisi['id_surat'],
                'disposisi_id' => $disposisiId,
                'url' => '/surat_detail.php?id=' . $disposisi['id_surat']
            ]);
        }
    }
    
    /**
     * Notifikasi: Surat masuk baru
     */
    public static function notifySuratMasuk($suratId, $excludeUserId = null) {
        $query = "SELECT id, nomor_agenda, perihal FROM surat WHERE id = ?";
        $surat = dbSelectOne($query, [$suratId], 'i');
        
        if ($surat) {
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
                    'url' => '/surat_detail.php?id=' . $suratId
                ]);
            }
        }
    }
    
    /**
     * Notifikasi: Update status
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
                'user_id' => $disposisi['dari_user_id'],
                'type' => 'surat_update',
                'title' => "Status Surat: {$statusLabel}",
                'message' => "{$disposisi['user_nama']} mengubah status surat {$disposisi['nomor_agenda']} menjadi: {$statusLabel}",
                'surat_id' => $disposisi['id_surat'],
                'disposisi_id' => $disposisiId,
                'url' => '/surat_detail.php?id=' . $disposisi['id_surat']
            ]);
        }
    }
    
    /**
     * Notifikasi: Surat selesai
     */
    public static function notifySuratSelesai($suratId) {
        $query = "SELECT nomor_agenda, perihal, dibuat_oleh FROM surat WHERE id = ?";
        $surat = dbSelectOne($query, [$suratId], 'i');
        
        if ($surat) {
            // Kirim ke superadmin
            $superadmins = dbSelect(
                "SELECT id FROM users WHERE id_role = 1 AND status_aktif = 1"
            );
            
            foreach ($superadmins as $admin) {
                self::create([
                    'user_id' => $admin['id'],
                    'type' => 'surat_selesai',
                    'title' => '✅ Surat Disetujui',
                    'message' => "Surat {$surat['nomor_agenda']} - {$surat['perihal']} telah selesai diproses",
                    'surat_id' => $suratId,
                    'url' => '/surat_detail.php?id=' . $suratId
                ]);
            }
            
            // Kirim ke pembuat
            $pembuat = dbSelectOne(
                "SELECT id, id_role FROM users WHERE id = ?",
                [$surat['dibuat_oleh']],
                'i'
            );
            
            if ($pembuat && $pembuat['id_role'] != 1) {
                self::create([
                    'user_id' => $pembuat['id'],
                    'type' => 'surat_selesai',
                    'title' => '✅ Surat Anda Disetujui',
                    'message' => "Surat {$surat['nomor_agenda']} telah disetujui",
                    'surat_id' => $suratId,
                    'url' => '/surat_detail.php?id=' . $suratId
                ]);
            }
        }
    }
    
    /**
     * Get recent notifications (filter surat aktif)
     */
    public static function getRecent($userId, $limit = 5) {
        $query = "SELECT n.*, s.status_surat 
                  FROM notifications n
                  LEFT JOIN surat s ON n.surat_id = s.id
                  WHERE n.user_id = ? 
                  AND (
                      s.id IS NULL 
                      OR s.status_surat NOT IN ('disetujui', 'ditolak', 'arsip')
                      OR n.type = 'surat_selesai'
                  )
                  ORDER BY n.created_at DESC 
                  LIMIT ?";
        
        return dbSelect($query, [$userId, $limit], 'ii');
    }
    
    /**
     * Count unread notifications
     */
    public static function countUnread($userId) {
        $query = "SELECT COUNT(*) as total 
                  FROM notifications n
                  LEFT JOIN surat s ON n.surat_id = s.id
                  WHERE n.user_id = ? 
                  AND n.is_read = 0
                  AND (
                      s.id IS NULL 
                      OR s.status_surat NOT IN ('disetujui', 'ditolak', 'arsip')
                      OR n.type = 'surat_selesai'
                  )";
        
        $result = dbSelectOne($query, [$userId], 'i');
        return $result['total'] ?? 0;
    }
    
    /**
     * FIX: Count UNIQUE surat yang disposisi-nya masih aktif UNTUK SIDEBAR
     * Kriteria Aktif:
     * 1. Status Disposisi: dikirim, diterima, atau diproses
     * 2. Status Surat: baru atau proses
     */
    public static function countActiveNotifications($userId) {
        $query = "SELECT COUNT(DISTINCT d.id_surat) as total
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  WHERE d.ke_user_id = ?
                  AND d.status_disposisi IN ('dikirim', 'diterima', 'diproses')
                  AND s.status_surat IN ('baru', 'proses')";
        
        $result = dbSelectOne($query, [$userId], 'i');
        return $result['total'] ?? 0;
    }
    
    /**
     * Mark as read
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
     * Clear notifications by surat
     */
    public static function clearBySurat($suratId) {
        $query = "UPDATE notifications 
                  SET is_read = 1, read_at = CURRENT_TIMESTAMP 
                  WHERE surat_id = ? 
                  AND is_read = 0
                  AND type != 'surat_selesai'";
        
        return dbExecute($query, [$suratId], 'i');
    }
    
    /**
     * Deactivate stakeholders
     */
    public static function deactivateStakeholders($suratId) {
        $query = "UPDATE surat_stakeholders 
                  SET is_active = 0 
                  WHERE surat_id = ?";
        
        return dbExecute($query, [$suratId], 'i');
    }
    
    /**
     * Cleanup old notifications
     */
    public static function cleanup() {
        $query = "DELETE FROM notifications 
                  WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        return dbExecute($query);
    }
}