<?php
// modules/jenis_surat/jenis_surat_service.php

require_once __DIR__ . '/../../config/database.php';

class JenisSuratService {
    
    // Get all jenis surat
    public static function getAll() {
        $query = "SELECT * FROM jenis_surat ORDER BY id ASC";
        return dbSelect($query);
    }
    
    // Get jenis surat by ID
    public static function getById($id) {
        $query = "SELECT * FROM jenis_surat WHERE id = ?";
        return dbSelectOne($query, [$id], 'i');
    }
    
    // Create new jenis surat
    public static function create($data) {
        $query = "INSERT INTO jenis_surat (nama_jenis, keterangan) VALUES (?, ?)";
        
        $params = [
            $data['nama_jenis'],
            $data['keterangan'] ?? null
        ];
        
        $result = dbExecute($query, $params, 'ss');
        return $result['insert_id'];
    }
    
    // Update jenis surat
    public static function update($id, $data) {
        $query = "UPDATE jenis_surat SET nama_jenis = ?, keterangan = ? WHERE id = ?";
        
        $params = [
            $data['nama_jenis'],
            $data['keterangan'] ?? null,
            $id
        ];
        
        return dbExecute($query, $params, 'ssi');
    }
    
    // Delete jenis surat
    public static function delete($id) {
        // Check if jenis surat is being used
        $check = dbSelectOne("SELECT COUNT(*) as total FROM surat WHERE id_jenis = ?", [$id], 'i');
        
        if ($check['total'] > 0) {
            throw new Exception('Jenis surat tidak dapat dihapus karena masih digunakan');
        }
        
        $query = "DELETE FROM jenis_surat WHERE id = ?";
        return dbExecute($query, [$id], 'i');
    }
}