<?php
// modules/settings/settings_service.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';

class SettingsService {
    
    /**
     * Ambil data settings
     */
    public static function getSettings() {
        $conn = getConnection();
        $sql = "SELECT * FROM settings WHERE id = 1 LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }

    /**
     * Initialize default settings jika tabel kosong
     */
    public static function initializeDefaults() {
        $conn = getConnection();
        
        // Cek apakah sudah ada data
        $check = $conn->query("SELECT id FROM settings WHERE id = 1");
        if ($check && $check->num_rows > 0) return;

        $sql = "INSERT INTO settings (id, app_name, app_description, theme_color, instansi_nama, ttd_jabatan, ttd_kota) 
                VALUES (1, 'Tracking Disposisi', 'Aplikasi Manajemen Surat', 'blue', 'DINAS KOMUNIKASI DAN INFORMATIKA', 'Kepala Dinas', 'Banjarmasin')";
        
        $conn->query($sql);
    }

    /**
     * Update settings (TERMASUK THEME COLOR & TTD IMAGE)
     */
    public static function update($data) {
        $conn = getConnection();
        
        $sql = "UPDATE settings SET 
                app_name = ?,
                app_description = ?,
                app_logo = ?,
                app_favicon = ?,
                theme_color = ?,  
                instansi_nama = ?,
                instansi_alamat = ?,
                instansi_telepon = ?,
                instansi_email = ?,
                instansi_logo = ?,
                ttd_nama_penandatangan = ?,
                ttd_nip = ?,
                ttd_jabatan = ?,
                ttd_kota = ?,
                ttd_image = ?
                WHERE id = 1";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }

        // Pastikan ttd_image ada dalam data, jika tidak set null
        $ttdImage = isset($data['ttd_image']) ? $data['ttd_image'] : null;

        $stmt->bind_param("sssssssssssssss", 
            $data['app_name'],
            $data['app_description'],
            $data['app_logo'],
            $data['app_favicon'],
            $data['theme_color'], // Value Baru
            $data['instansi_nama'],
            $data['instansi_alamat'],
            $data['instansi_telepon'],
            $data['instansi_email'],
            $data['instansi_logo'],
            $data['ttd_nama_penandatangan'],
            $data['ttd_nip'],
            $data['ttd_jabatan'],
            $data['ttd_kota'],
            $ttdImage
        );
        
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("Gagal menyimpan pengaturan: " . $stmt->error);
        }
        
        return $result;
    }

    /**
     * Handle file upload
     */
    public static function uploadFile($file, $oldFile = null) {
        // Validasi error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload error code: ' . $file['error']];
        }

        // Validasi tipe file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/x-icon'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Tipe file tidak diizinkan. Hanya JPG, PNG, SVG, dan ICO.'];
        }

        // Validasi ukuran (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Ukuran file terlalu besar (Max 2MB).'];
        }

        // Buat nama file unik
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'setting_' . uniqid() . '_' . time() . '.' . $extension;
        $destination = SETTINGS_UPLOAD_DIR . $filename;

        // Pastikan folder ada
        if (!is_dir(SETTINGS_UPLOAD_DIR)) {
            mkdir(SETTINGS_UPLOAD_DIR, 0755, true);
        }

        // Pindahkan file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Hapus file lama jika ada dan bukan default
            if ($oldFile && file_exists(SETTINGS_UPLOAD_DIR . $oldFile)) {
                // Opsional: jangan hapus jika default.png atau sejenisnya
                if (!in_array($oldFile, ['default.png', 'logo.png', 'favicon.ico'])) {
                    unlink(SETTINGS_UPLOAD_DIR . $oldFile);
                }
            }
            
            return ['success' => true, 'filename' => $filename];
        }

        return ['success' => false, 'message' => 'Gagal memindahkan file upload.'];
    }
}
?>