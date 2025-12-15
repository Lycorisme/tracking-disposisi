<?php
// modules/surat/surat_service.php
require_once __DIR__ . '/../../config/database.php';

class SuratService {
    
    /**
     * Get all surat dengan filter stakeholder
     * Admin (role 1): Semua surat
     * Karyawan/Magang (role 2,3): Hanya surat yang ditangani
     */
    public static function getAll($filters = [], $limit = 10, $offset = 0, $userId = null, $userRole = null) {
        $conn = getConnection();
        $sql = "SELECT s.*, j.nama_jenis, u.nama_lengkap as dibuat_oleh_nama 
                FROM surat s 
                LEFT JOIN jenis_surat j ON s.id_jenis = j.id 
                LEFT JOIN users u ON s.dibuat_oleh = u.id";
        
        // Filter by stakeholder jika bukan admin
        if ($userId && $userRole != 1) {
            $sql .= " INNER JOIN surat_stakeholders ss ON s.id = ss.surat_id 
                      WHERE ss.user_id = ? AND ss.is_active = 1";
        } else {
            $sql .= " WHERE 1=1";
        }
        
        $params = [];
        $types = "";
        
        // Add userId as first param if filtering by stakeholder
        if ($userId && $userRole != 1) {
            $params[] = $userId;
            $types .= "i";
        }

        if (!empty($filters['search'])) {
            $search = "%" . $filters['search'] . "%";
            $sql .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ? OR s.nomor_agenda LIKE ?)";
            $params[] = $search; $params[] = $search; $params[] = $search;
            $types .= "sss";
        }

        if (!empty($filters['id_jenis'])) {
            $sql .= " AND s.id_jenis = ?";
            $params[] = $filters['id_jenis'];
            $types .= "i";
        }

        if (!empty($filters['status_surat'])) {
            if ($filters['status_surat'] == 'arsip') {
                $sql .= " AND s.status_surat = 'arsip'";
            } else {
                $sql .= " AND s.status_surat = ?";
                $params[] = $filters['status_surat'];
                $types .= "s";
            }
        } else {
            $sql .= " AND s.status_surat != 'arsip'";
        }

        $sql .= " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Count surat dengan filter stakeholder
     */
    public static function count($filters = [], $userId = null, $userRole = null) {
        $conn = getConnection();
        $sql = "SELECT COUNT(DISTINCT s.id) as total FROM surat s";
        
        // Filter by stakeholder jika bukan admin
        if ($userId && $userRole != 1) {
            $sql .= " INNER JOIN surat_stakeholders ss ON s.id = ss.surat_id 
                      WHERE ss.user_id = ? AND ss.is_active = 1";
        } else {
            $sql .= " WHERE 1=1";
        }
        
        $params = [];
        $types = "";
        
        if ($userId && $userRole != 1) {
            $params[] = $userId;
            $types .= "i";
        }

        if (!empty($filters['search'])) {
            $search = "%" . $filters['search'] . "%";
            $sql .= " AND (s.nomor_surat LIKE ? OR s.perihal LIKE ? OR s.nomor_agenda LIKE ?)";
            $params[] = $search; $params[] = $search; $params[] = $search;
            $types .= "sss";
        }

        if (!empty($filters['id_jenis'])) {
            $sql .= " AND s.id_jenis = ?";
            $params[] = $filters['id_jenis'];
            $types .= "i";
        }

        if (!empty($filters['status_surat'])) {
            $sql .= " AND s.status_surat = ?";
            $params[] = $filters['status_surat'];
            $types .= "s";
        } else {
            $sql .= " AND s.status_surat != 'arsip'";
        }

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }

    public static function getById($id) {
        $conn = getConnection();
        $sql = "SELECT s.*, j.nama_jenis, u.nama_lengkap as dibuat_oleh_nama 
                FROM surat s 
                LEFT JOIN jenis_surat j ON s.id_jenis = j.id 
                LEFT JOIN users u ON s.dibuat_oleh = u.id 
                WHERE s.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function generateNomorSurat() {
        $conn = getConnection();
        $year = date('Y');
        
        $sql = "SELECT nomor_surat FROM surat WHERE nomor_surat LIKE '%/SRT/$year' ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        
        $lastNum = 0;
        if ($result && $row = $result->fetch_assoc()) {
            $parts = explode('/', $row['nomor_surat']);
            if (isset($parts[0]) && is_numeric($parts[0])) {
                $lastNum = (int)$parts[0];
            }
        }
        
        $nextNum = $lastNum + 1;
        return sprintf("%03d/SRT/%s", $nextNum, $year);
    }

    public static function create($data) {
        $conn = getConnection();
        
        $today = date('Ymd');
        $checkSql = "SELECT COUNT(*) as total FROM surat WHERE DATE(created_at) = CURDATE()";
        $checkResult = $conn->query($checkSql)->fetch_assoc();
        $count = $checkResult['total'] + 1;
        $nomorAgenda = 'AGD-' . $today . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        $nomorSurat = $data['nomor_surat'];
        if (empty($nomorSurat)) {
            $nomorSurat = self::generateNomorSurat();
        }

        $sql = "INSERT INTO surat (id_jenis, nomor_surat, nomor_agenda, tanggal_surat, tanggal_diterima, dari_instansi, ke_instansi, alamat_surat, perihal, lampiran_file, dibuat_oleh) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssssssi", 
            $data['id_jenis'], 
            $nomorSurat,
            $nomorAgenda,
            $data['tanggal_surat'], 
            $data['tanggal_diterima'],
            $data['dari_instansi'], 
            $data['ke_instansi'], 
            $data['alamat_surat'], 
            $data['perihal'], 
            $data['lampiran_file'],
            $data['dibuat_oleh']
        );
        
        $result = $stmt->execute();
        
        if ($result) {
            $suratId = $conn->insert_id;
            
            // FIX: role_type harus 'creator' bukan 'pembuat'
            self::addStakeholder($suratId, $data['dibuat_oleh'], 'creator');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * FIX: role_type enum: 'creator', 'recipient', 'processor'
     */
    public static function addStakeholder($suratId, $userId, $roleType, $assignedBy = null) {
        $existing = dbSelectOne(
            "SELECT id FROM surat_stakeholders WHERE surat_id = ? AND user_id = ?",
            [$suratId, $userId],
            'ii'
        );
        
        if ($existing) {
            return true;
        }
        
        $query = "INSERT INTO surat_stakeholders (surat_id, user_id, role_type, assigned_by, assigned_at, is_active) 
                  VALUES (?, ?, ?, ?, NOW(), 1)";
        
        return dbExecute($query, [$suratId, $userId, $roleType, $assignedBy], 'iisi');
    }

    public static function update($id, $data) {
        $conn = getConnection();
        $sql = "UPDATE surat SET id_jenis=?, nomor_surat=?, tanggal_surat=?, tanggal_diterima=?, dari_instansi=?, ke_instansi=?, alamat_surat=?, perihal=?, lampiran_file=? WHERE id=?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssssi", 
            $data['id_jenis'], 
            $data['nomor_surat'], 
            $data['tanggal_surat'], 
            $data['tanggal_diterima'],
            $data['dari_instansi'], 
            $data['ke_instansi'], 
            $data['alamat_surat'], 
            $data['perihal'], 
            $data['lampiran_file'],
            $id
        );
        
        return $stmt->execute();
    }

    public static function delete($id) {
        $conn = getConnection();
        $stmt = $conn->prepare("DELETE FROM surat WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public static function arsipkan($id) {
        $conn = getConnection();
        $stmt = $conn->prepare("UPDATE surat SET status_surat = 'arsip' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        
        if ($result) {
            if (file_exists(__DIR__ . '/../notifications/notification_service.php')) {
                require_once __DIR__ . '/../notifications/notification_service.php';
                NotificationService::clearBySurat($id);
                NotificationService::deactivateStakeholders($id);
            }
        }
        
        return $result;
    }
    
    public static function countArsip() {
        $conn = getConnection();
        $result = $conn->query("SELECT COUNT(*) as total FROM surat WHERE status_surat = 'arsip'");
        return $result->fetch_assoc()['total'];
    }
    
    public static function getArsip($limit, $offset) {
        return self::getAll(['status_surat' => 'arsip'], $limit, $offset);
    }

    public static function getStatistics() {
        $conn = getConnection();
        $stats = [
            'masuk' => 0, 'keluar' => 0, 'proposal' => 0, 'total_arsip' => 0,
            'by_jenis' => [], 'by_status' => [], 'recent' => []
        ];
        
        $res = $conn->query("SELECT id_jenis, COUNT(*) as total FROM surat WHERE status_surat != 'arsip' GROUP BY id_jenis");
        while($row = $res->fetch_assoc()) {
            if($row['id_jenis'] == 1) $stats['masuk'] = $row['total'];
            if($row['id_jenis'] == 2) $stats['keluar'] = $row['total'];
            if($row['id_jenis'] == 3) $stats['proposal'] = $row['total'];
        }

        $stats['total_arsip'] = self::countArsip();

        $resJenis = $conn->query("SELECT j.nama_jenis, COUNT(s.id) as total FROM surat s JOIN jenis_surat j ON s.id_jenis = j.id WHERE s.status_surat != 'arsip' GROUP BY j.nama_jenis");
        $stats['by_jenis'] = $resJenis->fetch_all(MYSQLI_ASSOC);

        $resStatus = $conn->query("SELECT status_surat, COUNT(*) as total FROM surat WHERE status_surat != 'arsip' GROUP BY status_surat");
        $stats['by_status'] = $resStatus->fetch_all(MYSQLI_ASSOC);
        
        $stats['recent'] = self::getAll([], 5, 0);

        return $stats;
    }

    public static function uploadLampiran($file) {
        $targetDir = __DIR__ . '/../../uploads/surat/';
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
        
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            return ['success' => false, 'message' => 'Format file tidak diizinkan (Hanya PDF, JPG, PNG)'];
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Ukuran file terlalu besar (Max 5MB)'];
        }
        
        $filename = uniqid() . '_' . time() . '.' . $ext;
        $targetPath = $targetDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => true, 'filename' => $filename];
        }
        
        return ['success' => false, 'message' => 'Gagal mengupload file ke server'];
    }

    public static function updateStatus($id, $status) {
        $conn = getConnection();
        $stmt = $conn->prepare("UPDATE surat SET status_surat = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $result = $stmt->execute();
        
        if ($result && in_array($status, ['disetujui', 'ditolak', 'arsip'])) {
            if (file_exists(__DIR__ . '/../notifications/notification_service.php')) {
                require_once __DIR__ . '/../notifications/notification_service.php';
                NotificationService::clearBySurat($id);
                NotificationService::deactivateStakeholders($id);
            }
        }
        
        return $result;
    }
    
    public static function reopenSurat($id) {
        $conn = getConnection();
        
        $stmt = $conn->prepare("UPDATE surat SET status_surat = 'proses' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        
        if ($result) {
            $query = "UPDATE surat_stakeholders SET is_active = 1 WHERE surat_id = ?";
            dbExecute($query, [$id], 'i');
        }
        
        return $result;
    }
}