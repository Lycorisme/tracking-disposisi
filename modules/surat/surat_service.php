<?php
// modules/surat/surat_service.php

require_once __DIR__ . '/../../config/database.php';

class SuratService {
    
    // Get all surat with pagination and filters
    public static function getAll($filters = [], $limit = 10, $offset = 0) {
        $params = [];
        $types = '';
        
        $query = "SELECT s.*, js.nama_jenis, u.nama_lengkap as dibuat_oleh_nama 
                  FROM surat s
                  JOIN jenis_surat js ON s.id_jenis = js.id
                  JOIN users u ON s.dibuat_oleh = u.id
                  WHERE 1=1";
        
        // Filter by jenis
        if (!empty($filters['id_jenis'])) {
            $query .= " AND s.id_jenis = ?";
            $params[] = $filters['id_jenis'];
            $types .= 'i';
        }
        
        // Filter by status
        if (!empty($filters['status_surat'])) {
            $query .= " AND s.status_surat = ?";
            $params[] = $filters['status_surat'];
            $types .= 's';
        }
        
        // Filter by date range
        if (!empty($filters['tanggal_dari'])) {
            $query .= " AND s.tanggal_surat >= ?";
            $params[] = $filters['tanggal_dari'];
            $types .= 's';
        }
        
        if (!empty($filters['tanggal_sampai'])) {
            $query .= " AND s.tanggal_surat <= ?";
            $params[] = $filters['tanggal_sampai'];
            $types .= 's';
        }
        
        // Search
        if (!empty($filters['search'])) {
            $query .= " AND (s.nomor_surat LIKE ? 
                        OR s.perihal LIKE ? 
                        OR s.dari_instansi LIKE ? 
                        OR s.ke_instansi LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ssss';
        }
        
        // Exclude archived if not viewing archive
        if (!isset($filters['include_arsip'])) {
            $query .= " AND s.status_surat != 'arsip'";
        }
        
        $query .= " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        return dbSelect($query, $params, $types);
    }
    
    // Count total surat with filters
    public static function count($filters = []) {
        $params = [];
        $types = '';
        
        $query = "SELECT COUNT(*) as total FROM surat s WHERE 1=1";
        
        if (!empty($filters['id_jenis'])) {
            $query .= " AND s.id_jenis = ?";
            $params[] = $filters['id_jenis'];
            $types .= 'i';
        }
        
        if (!empty($filters['status_surat'])) {
            $query .= " AND s.status_surat = ?";
            $params[] = $filters['status_surat'];
            $types .= 's';
        }
        
        if (!empty($filters['tanggal_dari'])) {
            $query .= " AND s.tanggal_surat >= ?";
            $params[] = $filters['tanggal_dari'];
            $types .= 's';
        }
        
        if (!empty($filters['tanggal_sampai'])) {
            $query .= " AND s.tanggal_surat <= ?";
            $params[] = $filters['tanggal_sampai'];
            $types .= 's';
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (s.nomor_surat LIKE ? 
                        OR s.perihal LIKE ? 
                        OR s.dari_instansi LIKE ? 
                        OR s.ke_instansi LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ssss';
        }
        
        if (!isset($filters['include_arsip'])) {
            $query .= " AND s.status_surat != 'arsip'";
        }
        
        $result = dbSelectOne($query, $params, $types);
        return $result['total'] ?? 0;
    }
    
    // Get surat by ID
    public static function getById($id) {
        $query = "SELECT s.*, js.nama_jenis, 
                         u.nama_lengkap as dibuat_oleh_nama, 
                         u.email as dibuat_oleh_email
                  FROM surat s
                  JOIN jenis_surat js ON s.id_jenis = js.id
                  JOIN users u ON s.dibuat_oleh = u.id
                  WHERE s.id = ?";
        
        return dbSelectOne($query, [$id], 'i');
    }
    
    // Create new surat
    public static function create($data) {
        $query = "INSERT INTO surat (
                    id_jenis, 
                    nomor_agenda, 
                    nomor_surat, 
                    tanggal_surat, 
                    tanggal_diterima,
                    dari_instansi, 
                    ke_instansi, 
                    alamat_surat, 
                    perihal, 
                    lampiran_file,
                    status_surat, 
                    dibuat_oleh
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['id_jenis'],
            $data['nomor_agenda'],
            $data['nomor_surat'],
            $data['tanggal_surat'],
            $data['tanggal_diterima'] ?? null,
            $data['dari_instansi'] ?? null,
            $data['ke_instansi'] ?? null,
            $data['alamat_surat'],
            $data['perihal'],
            $data['lampiran_file'] ?? null,
            $data['status_surat'] ?? 'baru',
            $data['dibuat_oleh']
        ];
        
        // 12 parameter -> 12 karakter type
        $types = 'isssssssssis';
        
        $result = dbExecute($query, $params, $types);
        return $result['insert_id'] ?? null;
    }
    
    // Update surat
    public static function update($id, $data) {
        $query = "UPDATE surat SET
                    id_jenis = ?,
                    nomor_surat = ?,
                    tanggal_surat = ?,
                    tanggal_diterima = ?,
                    dari_instansi = ?,
                    ke_instansi = ?,
                    alamat_surat = ?,
                    perihal = ?,
                    lampiran_file = ?,
                    status_surat = ?
                  WHERE id = ?";
        
        $params = [
            $data['id_jenis'],
            $data['nomor_surat'],
            $data['tanggal_surat'],
            $data['tanggal_diterima'] ?? null,
            $data['dari_instansi'] ?? null,
            $data['ke_instansi'] ?? null,
            $data['alamat_surat'],
            $data['perihal'],
            $data['lampiran_file'],
            $data['status_surat'],
            $id
        ];
        
        // 11 parameter -> 11 type
        $types = 'isssssssssi';
        
        return dbExecute($query, $params, $types);
    }
    
    // Delete surat
    public static function delete($id) {
        $query = "DELETE FROM surat WHERE id = ?";
        return dbExecute($query, [$id], 'i');
    }
    
    // Update status surat
    public static function updateStatus($id, $status) {
        $query = "UPDATE surat SET status_surat = ? WHERE id = ?";
        return dbExecute($query, [$status, $id], 'si');
    }
    
    // Get statistics for dashboard
    public static function getStatistics() {
        $stats = [];
        
        // Total surat by jenis
        $query = "SELECT js.nama_jenis, COUNT(s.id) as total
                  FROM jenis_surat js
                  LEFT JOIN surat s 
                    ON js.id = s.id_jenis 
                   AND s.status_surat != 'arsip'
                  GROUP BY js.id, js.nama_jenis";
        $stats['by_jenis'] = dbSelect($query);
        
        // Total by status
        $query = "SELECT status_surat, COUNT(*) as total
                  FROM surat
                  WHERE status_surat != 'arsip'
                  GROUP BY status_surat";
        $stats['by_status'] = dbSelect($query);
        
        // Recent surat
        $query = "SELECT s.*, js.nama_jenis
                  FROM surat s
                  JOIN jenis_surat js ON s.id_jenis = js.id
                  WHERE s.status_surat != 'arsip'
                  ORDER BY s.created_at DESC
                  LIMIT 5";
        $stats['recent'] = dbSelect($query);
        
        // Total archived
        $query = "SELECT COUNT(*) as total 
                  FROM surat 
                  WHERE status_surat = 'arsip'";
        $result = dbSelectOne($query);
        $stats['total_arsip'] = $result['total'] ?? 0;
        
        return $stats;
    }
    
    // Get arsip surat
    public static function getArsip($limit = 10, $offset = 0) {
        $query = "SELECT s.*, js.nama_jenis, u.nama_lengkap as dibuat_oleh_nama
                  FROM surat s
                  JOIN jenis_surat js ON s.id_jenis = js.id
                  JOIN users u ON s.dibuat_oleh = u.id
                  WHERE s.status_surat = 'arsip'
                  ORDER BY s.updated_at DESC
                  LIMIT ? OFFSET ?";
        
        return dbSelect($query, [$limit, $offset], 'ii');
    }
    
    // Count arsip
    public static function countArsip() {
        $query = "SELECT COUNT(*) as total 
                  FROM surat 
                  WHERE status_surat = 'arsip'";
        $result = dbSelectOne($query);
        return $result['total'] ?? 0;
    }
}
