<?php
// modules/disposisi/disposisi_service.php

require_once __DIR__ . '/../../config/database.php';

class DisposisiService {
    
    // ==========================================
    // STAKEHOLDER METHODS
    // ==========================================
    
    /**
     * Add user sebagai stakeholder surat
     */
    public static function addStakeholder($suratId, $userId, $roleType, $assignedBy = null) {
        // Check if already exists
        $existing = dbSelectOne(
            "SELECT id FROM surat_stakeholders WHERE surat_id = ? AND user_id = ?",
            [$suratId, $userId],
            'ii'
        );
        
        if ($existing) {
            return true; // Already a stakeholder
        }
        
        $query = "INSERT INTO surat_stakeholders (surat_id, user_id, role_type, assigned_by, assigned_at, is_active) 
                  VALUES (?, ?, ?, ?, NOW(), 1)";
        
        $params = [$suratId, $userId, $roleType, $assignedBy];
        $types = 'iisi';
        
        return dbExecute($query, $params, $types);
    }
    
    /**
     * Get all stakeholders for a surat
     */
    public static function getStakeholders($suratId) {
        $query = "SELECT ss.*, u.nama_lengkap, u.email, r.nama_role
                  FROM surat_stakeholders ss
                  JOIN users u ON ss.user_id = u.id
                  JOIN roles r ON u.id_role = r.id
                  WHERE ss.surat_id = ?
                  ORDER BY ss.assigned_at ASC";
        
        return dbSelect($query, [$suratId], 'i');
    }
    
    /**
     * Check if user is stakeholder of a surat
     */
    public static function isStakeholder($suratId, $userId) {
        $result = dbSelectOne(
            "SELECT id FROM surat_stakeholders WHERE surat_id = ? AND user_id = ? AND is_active = 1",
            [$suratId, $userId],
            'ii'
        );
        
        return $result !== null;
    }
    
    /**
     * Deactivate all stakeholders when surat is completed
     */
    public static function deactivateStakeholders($suratId) {
        $query = "UPDATE surat_stakeholders SET is_active = 0 WHERE surat_id = ?";
        return dbExecute($query, [$suratId], 'i');
    }

    // ==========================================
    // GET ALL DISPOSISI - DENGAN FILTER STAKEHOLDER
    // ==========================================

    /**
     * Get all disposisi with filters
     * Support filter by stakeholder untuk inbox/monitoring
     */
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
        
        // Filter by user (inbox/outbox) - LEGACY
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
            $query .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ? OR s.nomor_agenda LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        $query .= " ORDER BY d.tanggal_disposisi DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        return dbSelect($query, $params, $types);
    }
    
    /**
     * Get disposisi untuk INBOX berdasarkan stakeholder
     * Surat akan tetap muncul meskipun sudah didelegasikan
     */
    public static function getInboxByStakeholder($userId, $filters = [], $limit = 10, $offset = 0) {
        $params = [$userId];
        $types = 'i';
        
        $query = "SELECT DISTINCT d.*, 
                         s.nomor_agenda, s.nomor_surat, s.perihal, s.status_surat,
                         js.nama_jenis,
                         u1.nama_lengkap as dari_user_nama,
                         u2.nama_lengkap as ke_user_nama,
                         ss.role_type as stakeholder_role
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  JOIN jenis_surat js ON s.id_jenis = js.id
                  JOIN users u1 ON d.dari_user_id = u1.id
                  JOIN users u2 ON d.ke_user_id = u2.id
                  JOIN surat_stakeholders ss ON ss.surat_id = s.id AND ss.user_id = ?
                  WHERE d.ke_user_id = ? OR ss.user_id = ?";
        
        $params[] = $userId;
        $params[] = $userId;
        $types .= 'ii';
        
        // Filter by status
        if (!empty($filters['status_disposisi'])) {
            $query .= " AND d.status_disposisi = ?";
            $params[] = $filters['status_disposisi'];
            $types .= 's';
        }
        
        // Search
        if (!empty($filters['search'])) {
            $query .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ? OR s.nomor_agenda LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        // Exclude completed/archived unless specifically requested
        if (empty($filters['include_completed'])) {
            $query .= " AND s.status_surat NOT IN ('disetujui', 'ditolak', 'arsip')";
        }
        
        $query .= " ORDER BY d.tanggal_disposisi DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        return dbSelect($query, $params, $types);
    }
    
    /**
     * Count inbox by stakeholder
     */
    public static function countInboxByStakeholder($userId, $filters = []) {
        $params = [$userId];
        $types = 'i';
        
        $query = "SELECT COUNT(DISTINCT d.id) as total
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  JOIN surat_stakeholders ss ON ss.surat_id = s.id AND ss.user_id = ?
                  WHERE d.ke_user_id = ? OR ss.user_id = ?";
        
        $params[] = $userId;
        $params[] = $userId;
        $types .= 'ii';
        
        // Filter by status
        if (!empty($filters['status_disposisi'])) {
            $query .= " AND d.status_disposisi = ?";
            $params[] = $filters['status_disposisi'];
            $types .= 's';
        }
        
        // Search
        if (!empty($filters['search'])) {
            $query .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ? OR s.nomor_agenda LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        // Exclude completed/archived
        if (empty($filters['include_completed'])) {
            $query .= " AND s.status_surat NOT IN ('disetujui', 'ditolak', 'arsip')";
        }
        
        $result = dbSelectOne($query, $params, $types);
        return $result['total'] ?? 0;
    }
    
    /**
     * Get disposisi untuk MONITORING berdasarkan role
     * - Superadmin: Semua disposisi
     * - Karyawan: Surat yang dia terima + delegasi ke magang
     * - Magang: Hanya surat yang dia tangani
     */
    public static function getForMonitoring($userId, $userRole, $filters = [], $limit = 10, $offset = 0) {
        $params = [];
        $types = '';
        
        $query = "SELECT DISTINCT d.*, 
                         s.nomor_agenda, s.nomor_surat, s.perihal, s.status_surat,
                         js.nama_jenis,
                         u1.nama_lengkap as dari_user_nama,
                         u2.nama_lengkap as ke_user_nama
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  JOIN jenis_surat js ON s.id_jenis = js.id
                  JOIN users u1 ON d.dari_user_id = u1.id
                  JOIN users u2 ON d.ke_user_id = u2.id";
        
        // Role-based filtering
        if ($userRole == 1) {
            // Superadmin: Lihat semua
            $query .= " WHERE 1=1";
        } elseif ($userRole == 2) {
            // Karyawan: Surat yang dia terlibat sebagai stakeholder (termasuk delegasi ke magang)
            $query .= " JOIN surat_stakeholders ss ON ss.surat_id = s.id
                       WHERE ss.user_id = ?";
            $params[] = $userId;
            $types .= 'i';
        } else {
            // Magang: Hanya surat yang dia tangani langsung
            $query .= " WHERE d.ke_user_id = ?";
            $params[] = $userId;
            $types .= 'i';
        }
        
        // Filter by status
        if (!empty($filters['status_disposisi'])) {
            $query .= " AND d.status_disposisi = ?";
            $params[] = $filters['status_disposisi'];
            $types .= 's';
        }
        
        // Search
        if (!empty($filters['search'])) {
            $query .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ? OR s.nomor_agenda LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        $query .= " ORDER BY d.tanggal_disposisi DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        return dbSelect($query, $params, $types);
    }
    
    /**
     * Count untuk monitoring
     */
    public static function countForMonitoring($userId, $userRole, $filters = []) {
        $params = [];
        $types = '';
        
        $query = "SELECT COUNT(DISTINCT d.id) as total
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id";
        
        // Role-based filtering
        if ($userRole == 1) {
            $query .= " WHERE 1=1";
        } elseif ($userRole == 2) {
            $query .= " JOIN surat_stakeholders ss ON ss.surat_id = s.id
                       WHERE ss.user_id = ?";
            $params[] = $userId;
            $types .= 'i';
        } else {
            $query .= " WHERE d.ke_user_id = ?";
            $params[] = $userId;
            $types .= 'i';
        }
        
        // Filter by status
        if (!empty($filters['status_disposisi'])) {
            $query .= " AND d.status_disposisi = ?";
            $params[] = $filters['status_disposisi'];
            $types .= 's';
        }
        
        // Search
        if (!empty($filters['search'])) {
            $query .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ? OR s.nomor_agenda LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }
        
        $result = dbSelectOne($query, $params, $types);
        return $result['total'] ?? 0;
    }
    
    // ==========================================
    // EXISTING METHODS
    // ==========================================
    
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
            $query .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ? OR s.nomor_agenda LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
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
    
    // Create new disposisi with stakeholder tracking
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
        
        if ($result) {
            $disposisiId = dbLastInsertId();
            
            // Determine role type for stakeholder
            // Check if sender is already a stakeholder
            $senderIsStakeholder = self::isStakeholder($data['id_surat'], $data['dari_user_id']);
            
            // If sender received this surat from someone else, receiver is delegasi
            $roleType = 'penerima_utama';
            if ($senderIsStakeholder) {
                $senderRole = dbSelectOne(
                    "SELECT role_type FROM surat_stakeholders WHERE surat_id = ? AND user_id = ?",
                    [$data['id_surat'], $data['dari_user_id']],
                    'ii'
                );
                if ($senderRole && $senderRole['role_type'] != 'pembuat') {
                    $roleType = 'penerima_delegasi';
                }
            }
            
            // Add receiver as stakeholder
            self::addStakeholder($data['id_surat'], $data['ke_user_id'], $roleType, $data['dari_user_id']);
            
            // Send notification
            if (file_exists(__DIR__ . '/../notifications/notification_service.php')) {
                require_once __DIR__ . '/../notifications/notification_service.php';
                NotificationService::notifyDisposisiBaru($disposisiId);
            }
            
            return $disposisiId;
        }
        
        return false;
    }
    
    // Update disposisi status
    public static function updateStatus($id, $status, $catatan = null) {
        $query = "UPDATE disposisi 
                  SET status_disposisi = ?, 
                      catatan = COALESCE(?, catatan),
                      tanggal_respon = CURRENT_TIMESTAMP
                  WHERE id = ?";
        
        $result = dbExecute($query, [$status, $catatan, $id], 'ssi');
        
        if ($result) {
            // Get disposisi info for notification
            $disposisi = self::getById($id);
            
            if ($disposisi) {
                // Send notification to sender
                if (file_exists(__DIR__ . '/../notifications/notification_service.php')) {
                    require_once __DIR__ . '/../notifications/notification_service.php';
                    NotificationService::notifySuratUpdate($id, $status);
                    
                    // If completed or rejected, handle surat status and notifications
                    if ($status === 'selesai' || $status === 'ditolak') {
                        // Check if all disposisi are completed
                        $allCompleted = self::checkAllDisposisiCompleted($disposisi['id_surat']);
                        
                        if ($allCompleted || $status === 'ditolak') {
                            // Update surat status
                            $suratStatus = ($status === 'selesai') ? 'disetujui' : 'ditolak';
                            dbExecute(
                                "UPDATE surat SET status_surat = ? WHERE id = ?",
                                [$suratStatus, $disposisi['id_surat']],
                                'si'
                            );
                            
                            // Clear notifications and deactivate stakeholders
                            NotificationService::clearBySurat($disposisi['id_surat']);
                            NotificationService::deactivateStakeholders($disposisi['id_surat']);
                            
                            // Send completion notification
                            if ($status === 'selesai') {
                                NotificationService::notifySuratSelesai($disposisi['id_surat']);
                            }
                        }
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Check if all disposisi for a surat are completed
     */
    public static function checkAllDisposisiCompleted($suratId) {
        $query = "SELECT COUNT(*) as total FROM disposisi 
                  WHERE id_surat = ? AND status_disposisi NOT IN ('selesai', 'ditolak')";
        
        $result = dbSelectOne($query, [$suratId], 'i');
        return ($result['total'] ?? 0) == 0;
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
    
    // Get active disposisi count (untuk badge)
    public static function getActiveInboxCount($userId) {
        $query = "SELECT COUNT(DISTINCT d.id) as total 
                  FROM disposisi d
                  JOIN surat s ON d.id_surat = s.id
                  JOIN surat_stakeholders ss ON ss.surat_id = s.id AND ss.user_id = ?
                  WHERE ss.is_active = 1
                  AND s.status_surat NOT IN ('disetujui', 'ditolak', 'arsip')
                  AND (d.ke_user_id = ? OR ss.user_id = ?)";
        
        $result = dbSelectOne($query, [$userId, $userId, $userId], 'iii');
        return $result['total'] ?? 0;
    }
    
    // Get statistics
    public static function getStatistics($userId = null) {
        $stats = [
            'total' => 0,
            'dikirim' => 0,
            'diterima' => 0,
            'diproses' => 0,
            'selesai' => 0,
            'ditolak' => 0
        ];
        
        $query = "SELECT status_disposisi, COUNT(*) as total FROM disposisi";
        $params = [];
        $types = '';
        
        if ($userId) {
            $query .= " WHERE ke_user_id = ? OR dari_user_id = ?";
            $params = [$userId, $userId];
            $types = 'ii';
        }
        
        $query .= " GROUP BY status_disposisi";
        
        $results = dbSelect($query, $params, $types);
        
        foreach ($results as $row) {
            $stats[$row['status_disposisi']] = $row['total'];
            $stats['total'] += $row['total'];
        }
        
        return $stats;
    }

    // Get active disposisi for a surat
    public static function getActiveDisposisi($suratId) {
        $query = "SELECT d.*, u.nama_lengkap as ke_user_nama
                  FROM disposisi d
                  JOIN users u ON d.ke_user_id = u.id
                  WHERE d.id_surat = ? 
                  AND d.status_disposisi NOT IN ('ditolak')
                  ORDER BY d.tanggal_disposisi DESC
                  LIMIT 1";
        
        return dbSelectOne($query, [$suratId], 'i');
    }
    
    // Check if surat has active disposisi
    public static function hasActiveDisposisi($suratId) {
        $query = "SELECT COUNT(*) as total FROM disposisi 
                  WHERE id_surat = ? AND status_disposisi NOT IN ('ditolak', 'selesai')";
        
        $result = dbSelectOne($query, [$suratId], 'i');
        return $result['total'] > 0;
    }

    // Check if user can dispose surat
    public static function canDispose($userId, $suratId) {
        // User can dispose if they are a stakeholder
        return self::isStakeholder($suratId, $userId);
    }

    /**
     * Check apakah surat bisa didisposisi
     */
    public static function checkSuratAvailability($suratId) {
        $activeDisposisi = self::getActiveDisposisi($suratId);
        
        if (!$activeDisposisi) {
            return [
                'can_dispose' => true,
                'message' => 'Surat dapat didisposisi'
            ];
        }
        
        $userName = $activeDisposisi['ke_user_nama'] ?? 'User ID: ' . $activeDisposisi['ke_user_id'];
        
        return [
            'can_dispose' => true, // Allow multiple disposisi
            'message' => 'Surat dapat didisposisi',
            'existing_disposisi' => $activeDisposisi
        ];
    }

    /**
     * Get disposisi untuk surat tertentu
     */
    public static function getForSurat($suratId) {
        $query = "SELECT d.*, 
                         u1.nama_lengkap as dari_nama,
                         u2.nama_lengkap as ke_nama,
                         u2.email as ke_email
                  FROM disposisi d
                  JOIN users u1 ON d.dari_user_id = u1.id
                  JOIN users u2 ON d.ke_user_id = u2.id
                  WHERE d.id_surat = ?
                  ORDER BY d.tanggal_disposisi DESC";
        
        return dbSelect($query, [$suratId], 'i');
    }
    
    /**
     * Auto-accept disposisi saat user buka detail surat
     */
    public static function autoAcceptDisposisi($suratId, $userId) {
        $query = "SELECT id FROM disposisi 
                  WHERE id_surat = ? 
                  AND ke_user_id = ? 
                  AND status_disposisi = 'dikirim'
                  LIMIT 1";
        
        $disposisi = dbSelectOne($query, [$suratId, $userId], 'ii');
        
        if ($disposisi) {
            dbExecute(
                "UPDATE disposisi SET status_disposisi = 'diterima', tanggal_respon = NOW() WHERE id = ?",
                [$disposisi['id']],
                'i'
            );
            
            return true;
        }
        
        return false;
    }
}