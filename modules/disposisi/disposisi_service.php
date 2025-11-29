<?php
// modules/disposisi/disposisi_service.php

require_once __DIR__ . '/../../config/database.php';

class DisposisiService {
    
    // Get all disposisi with filters
    public static function getAll($filters = [], $limit = 10, $offset = 0) {
        $params = [];
        $types = '';
        
        $query = "SELECT d.*, 
                         s.nomor_agenda, s.nomor_surat, s.perihal, s.status_surat,
                         js.nama_jenis,
                         u1.nama_lengkap as dari_user_nama,
                         u2.nama_lengkap as ke_user_nama
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  JOIN jenis_surat js ON s.id_jenis = js.id
                  JOIN users u1 ON d.dari_user_id = u1.id
                  JOIN users u2 ON d.ke_user_id = u2.id
                  WHERE 1=1";
        
        // Filter by status
        if (!empty($filters['status_disposisi'])) {
            $query .= " AND d.status_disposisi = ?";
            $params[] = $filters['status_disposisi'];
            $types .= 's';
        }
        
        // Filter by user (inbox/outbox)
        if (!empty($filters['ke_user_id'])) {
            $query .= " AND d.ke_user_id = ?";
            $params[] = $filters['ke_user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['dari_user_id'])) {
            $query .= " AND d.dari_user_id = ?";
            $params[] = $filters['dari_user_id'];
            $types .= 'i';
        }
        
        // Filter by surat
        if (!empty($filters['id_surat'])) {
            $query .= " AND d.id_surat = ?";
            $params[] = $filters['id_surat'];
            $types .= 'i';
        }
        
        // Search
        if (!empty($filters['search'])) {
            $query .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        
        $query .= " ORDER BY d.tanggal_disposisi DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        return dbSelect($query, $params, $types);
    }
    
    // Count disposisi
    public static function count($filters = []) {
        $params = [];
        $types = '';
        
        $query = "SELECT COUNT(*) as total 
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  WHERE 1=1";
        
        if (!empty($filters['status_disposisi'])) {
            $query .= " AND d.status_disposisi = ?";
            $params[] = $filters['status_disposisi'];
            $types .= 's';
        }
        
        if (!empty($filters['ke_user_id'])) {
            $query .= " AND d.ke_user_id = ?";
            $params[] = $filters['ke_user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['dari_user_id'])) {
            $query .= " AND d.dari_user_id = ?";
            $params[] = $filters['dari_user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['id_surat'])) {
            $query .= " AND d.id_surat = ?";
            $params[] = $filters['id_surat'];
            $types .= 'i';
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        
        $result = dbSelectOne($query, $params, $types);
        return $result['total'] ?? 0;
    }
    
    // Get disposisi by ID
    public static function getById($id) {
        $query = "SELECT d.*, 
                         s.nomor_agenda, s.nomor_surat, s.perihal, s.status_surat,
                         js.nama_jenis,
                         u1.nama_lengkap as dari_user_nama, u1.email as dari_user_email,
                         u2.nama_lengkap as ke_user_nama, u2.email as ke_user_email
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  JOIN jenis_surat js ON s.id_jenis = js.id
                  JOIN users u1 ON d.dari_user_id = u1.id
                  JOIN users u2 ON d.ke_user_id = u2.id
                  WHERE d.id = ?";
        
        return dbSelectOne($query, [$id], 'i');
    }
    
    // Get disposisi history for a surat
    public static function getHistoryBySurat($suratId) {
        $query = "SELECT d.*, 
                         u1.nama_lengkap as dari_user_nama,
                         u2.nama_lengkap as ke_user_nama,
                         r1.nama_role as dari_user_role,
                         r2.nama_role as ke_user_role
                  FROM disposisi d
                  JOIN users u1 ON d.dari_user_id = u1.id
                  JOIN users u2 ON d.ke_user_id = u2.id
                  JOIN roles r1 ON u1.id_role = r1.id
                  JOIN roles r2 ON u2.id_role = r2.id
                  WHERE d.id_surat = ?
                  ORDER BY d.tanggal_disposisi ASC";
        
        return dbSelect($query, [$suratId], 'i');
    }
    
    // Create new disposisi
    public static function create($data) {
        $query = "INSERT INTO disposisi (
                    id_surat, dari_user_id, ke_user_id, status_disposisi, catatan
                  ) VALUES (?, ?, ?, ?, ?)";
        
        $params = [
            $data['id_surat'],
            $data['dari_user_id'],
            $data['ke_user_id'],
            $data['status_disposisi'] ?? 'dikirim',
            $data['catatan'] ?? null
        ];
        
        $types = 'iiiss';
        
        $result = dbExecute($query, $params, $types);
        return $result['insert_id'];
    }
    
    // Update disposisi status
    public static function updateStatus($id, $status, $catatan = null) {
        $query = "UPDATE disposisi 
                  SET status_disposisi = ?, 
                      catatan = COALESCE(?, catatan),
                      tanggal_respon = CURRENT_TIMESTAMP
                  WHERE id = ?";
        
        return dbExecute($query, [$status, $catatan, $id], 'ssi');
    }
    
    // Get inbox count by status
    public static function getInboxCount($userId, $status = null) {
        $query = "SELECT COUNT(*) as total FROM disposisi WHERE ke_user_id = ?";
        $params = [$userId];
        $types = 'i';
        
        if ($status) {
            $query .= " AND status_disposisi = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        $result = dbSelectOne($query, $params, $types);
        return $result['total'] ?? 0;
    }
    
    // Get outbox count
    public static function getOutboxCount($userId, $status = null) {
        $query = "SELECT COUNT(*) as total FROM disposisi WHERE dari_user_id = ?";
        $params = [$userId];
        $types = 'i';
        
        if ($status) {
            $query .= " AND status_disposisi = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        $result = dbSelectOne($query, $params, $types);
        return $result['total'] ?? 0;
    }
    
    // Get statistics
    public static function getStatistics($userId = null) {
        $stats = [];
        
        if ($userId) {
            // User specific stats
            $stats['inbox_total'] = self::getInboxCount($userId);
            $stats['inbox_dikirim'] = self::getInboxCount($userId, 'dikirim');
            $stats['inbox_diproses'] = self::getInboxCount($userId, 'diproses');
            $stats['inbox_selesai'] = self::getInboxCount($userId, 'selesai');
            
            $stats['outbox_total'] = self::getOutboxCount($userId);
            $stats['outbox_dikirim'] = self::getOutboxCount($userId, 'dikirim');
            $stats['outbox_selesai'] = self::getOutboxCount($userId, 'selesai');
        } else {
            // Global stats
            $query = "SELECT status_disposisi, COUNT(*) as total
                      FROM disposisi
                      GROUP BY status_disposisi";
            $stats['by_status'] = dbSelect($query);
            
            $query = "SELECT COUNT(*) as total FROM disposisi";
            $result = dbSelectOne($query);
            $stats['total'] = $result['total'] ?? 0;
        }
        
        return $stats;
    }
    
    // Check if user can dispose surat
    public static function canDispose($userId, $suratId) {
        // Get surat info
        $query = "SELECT dibuat_oleh, status_surat FROM surat WHERE id = ?";
        $surat = dbSelectOne($query, [$suratId], 'i');
        
        if (!$surat) {
            return false;
        }
        
        // Check if user is the creator or has received disposition
        if ($surat['dibuat_oleh'] == $userId) {
            return true;
        }
        
        // Check if user has active disposition for this surat
        $query = "SELECT id FROM disposisi 
                  WHERE id_surat = ? AND ke_user_id = ? 
                  AND status_disposisi IN ('diterima', 'diproses')
                  LIMIT 1";
        $disposition = dbSelectOne($query, [$suratId, $userId], 'ii');
        
        return $disposition !== null;
    }
}