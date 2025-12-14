<?php
// modules/users/users_service.php
require_once __DIR__ . '/../../config/database.php';

class UsersService {
    
    // Ambil semua user dengan filter & pagination
    public static function getAll($status = null, $excludeId = null) {
        $conn = getConnection();
        
        $sql = "SELECT u.*, r.nama_role, r.keterangan as role_deskripsi, b.nama_bagian 
                FROM users u 
                LEFT JOIN roles r ON u.id_role = r.id 
                LEFT JOIN bagian b ON u.id_bagian = b.id 
                WHERE 1=1";
        
        $params = [];
        $types = "";

        if ($status && $status !== 'all') {
            $sql .= " AND u.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if ($excludeId) {
            $sql .= " AND u.id != ?";
            $params[] = $excludeId;
            $types .= "i";
        }

        $sql .= " ORDER BY u.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function getById($id) {
        $conn = getConnection();
        $query = "SELECT u.*, r.nama_role FROM users u LEFT JOIN roles r ON u.id_role = r.id WHERE u.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function countPending() {
        $conn = getConnection();
        $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'pending'");
        return $result->fetch_assoc()['total'] ?? 0;
    }

    public static function countActive() {
        $conn = getConnection();
        $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
        return $result->fetch_assoc()['total'] ?? 0;
    }

    public static function countTotal() {
        $conn = getConnection();
        $result = $conn->query("SELECT COUNT(*) as total FROM users");
        return $result->fetch_assoc()['total'] ?? 0;
    }

    public static function getRoles() {
        $conn = getConnection();
        $result = $conn->query("SELECT * FROM roles ORDER BY id ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function updateStatus($userId, $status) {
        $conn = getConnection();
        $isActive = ($status === 'active') ? 1 : 0;
        $stmt = $conn->prepare("UPDATE users SET status = ?, status_aktif = ? WHERE id = ?");
        $stmt->bind_param("sii", $status, $isActive, $userId);
        return $stmt->execute();
    }

    public static function updateRole($userId, $roleId) {
        $conn = getConnection();
        $stmt = $conn->prepare("UPDATE users SET id_role = ? WHERE id = ?");
        $stmt->bind_param("ii", $roleId, $userId);
        return $stmt->execute();
    }

    // --- LOGIKA PENGHAPUSAN AMAN ---

    // Cek apakah user punya data terkait
    public static function hasRelatedData($userId) {
        $conn = getConnection();
        
        // Cek Log
        $logs = $conn->query("SELECT 1 FROM log_aktivitas WHERE user_id = $userId LIMIT 1")->num_rows;
        if ($logs > 0) return true;

        // Cek Surat (Dibuat oleh)
        $surat = $conn->query("SELECT 1 FROM surat WHERE dibuat_oleh = $userId LIMIT 1")->num_rows;
        if ($surat > 0) return true;

        // Cek Disposisi (Pengirim atau Penerima)
        $dispo = $conn->query("SELECT 1 FROM disposisi WHERE dari_user_id = $userId OR ke_user_id = $userId LIMIT 1")->num_rows;
        if ($dispo > 0) return true;

        return false;
    }

    // Hapus user beserta data terkaitnya (Manual Cascade)
    public static function delete($userId) {
        $conn = getConnection();
        
        // Mulai Transaksi agar aman
        $conn->begin_transaction();

        try {
            // 1. Hapus Log Aktivitas
            $conn->query("DELETE FROM log_aktivitas WHERE user_id = $userId");

            // 2. Hapus Disposisi (Sebagai pengirim atau penerima)
            $conn->query("DELETE FROM disposisi WHERE dari_user_id = $userId OR ke_user_id = $userId");

            // 3. Update Surat (Set dibuat_oleh ke NULL atau hapus suratnya? 
            // Amannya set NULL jika constraint allow, atau hapus suratnya.
            // Disini kita asumsikan hapus surat buatan dia juga untuk membersihkan data total)
            // *Opsi Lebih Aman*: Hapus surat yang dibuat dia
            $conn->query("DELETE FROM surat WHERE dibuat_oleh = $userId");

            // 4. Akhirnya Hapus User
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            $conn->commit();
            return true;

        } catch (Exception $e) {
            $conn->rollback();
            return false;
        }
    }

    // --- END LOGIKA PENGHAPUSAN ---

    public static function updateProfile($id, $data) {
        $conn = getConnection();
        $bagianCustom = !empty($data['nama_bagian_custom']) ? $data['nama_bagian_custom'] : null;
        $query = "UPDATE users SET nama_lengkap = ?, email = ?, nama_bagian_custom = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $data['nama_lengkap'], $data['email'], $bagianCustom, $id);
        return $stmt->execute();
    }

    public static function changePassword($id, $newPassword) {
        $conn = getConnection();
        $query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $newPassword, $id);
        return $stmt->execute();
    }

    public static function emailExists($email, $excludeId = null) {
        $conn = getConnection();
        $query = "SELECT COUNT(*) as total FROM users WHERE email = ?";
        $types = 's';
        $params = [$email];
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
            $types .= 'i';
        }
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return ($result['total'] ?? 0) > 0;
    }
}
?>