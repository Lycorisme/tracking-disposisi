<?php
// modules/users/users_service.php
require_once __DIR__ . '/../../config/database.php';

class UsersService {
    
    // Ambil semua user dengan filter status
    public static function getAll($status = null) {
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

        $sql .= " ORDER BY u.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Ambil detail user berdasarkan ID (PERBAIKAN UTAMA DISINI)
    public static function getById($id) {
        $conn = getConnection();
        // Ambil juga 'keterangan' dari tabel roles sebagai default bagian
        $query = "SELECT u.*, r.nama_role, r.keterangan as role_deskripsi
                  FROM users u
                  LEFT JOIN roles r ON u.id_role = r.id
                  WHERE u.id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Update Profil (Termasuk nama_bagian_custom)
    public static function updateProfile($id, $data) {
        $conn = getConnection();
        
        // Cek apakah 'nama_bagian_custom' dikirim, jika kosong set NULL
        $bagianCustom = !empty($data['nama_bagian_custom']) ? $data['nama_bagian_custom'] : null;

        $query = "UPDATE users SET nama_lengkap = ?, email = ?, nama_bagian_custom = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        
        // 'sssi' -> string, string, string (bisa null), integer
        $stmt->bind_param("sssi", $data['nama_lengkap'], $data['email'], $bagianCustom, $id);
        
        return $stmt->execute();
    }

    // --- FUNGSI LAINNYA TETAP SAMA ---

    public static function countPending() {
        $conn = getConnection();
        $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'pending'");
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
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

    public static function delete($userId) {
        $conn = getConnection();
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
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