<?php
// modules/jenis_surat/jenis_surat_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/jenis_surat_service.php';

// Set Header JSON agar browser tahu ini bukan halaman HTML
header('Content-Type: application/json');

// Pastikan login
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login']);
    exit;
}

requireRole(['admin', 'superadmin']);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser();

try {
    switch ($action) {
        case 'create':
            $data = [
                'nama_jenis' => sanitize($_POST['nama_jenis']),
                'keterangan' => sanitize($_POST['keterangan'] ?? '')
            ];
            
            if (empty($data['nama_jenis'])) {
                throw new Exception('Nama jenis surat harus diisi');
            }
            
            JenisSuratService::create($data);
            logActivity($user['id'], 'tambah_jenis_surat', "Menambah jenis surat: {$data['nama_jenis']}");
            
            echo json_encode(['status' => 'success', 'message' => 'Jenis surat berhasil ditambahkan']);
            break;
            
        case 'update':
            $id = (int)$_POST['id'];
            $data = [
                'nama_jenis' => sanitize($_POST['nama_jenis']),
                'keterangan' => sanitize($_POST['keterangan'] ?? '')
            ];
            
            if (empty($data['nama_jenis'])) {
                throw new Exception('Nama jenis surat harus diisi');
            }
            
            JenisSuratService::update($id, $data);
            logActivity($user['id'], 'edit_jenis_surat', "Mengedit jenis surat ID: {$id}");
            
            echo json_encode(['status' => 'success', 'message' => 'Jenis surat berhasil diperbarui']);
            break;
            
        case 'delete':
            requireRole('superadmin');
            
            $id = (int)$_POST['id'];
            JenisSuratService::delete($id);
            logActivity($user['id'], 'hapus_jenis_surat', "Menghapus jenis surat ID: {$id}");
            
            echo json_encode(['status' => 'success', 'message' => 'Jenis surat berhasil dihapus']);
            break;
            
        default:
            throw new Exception('Action tidak valid');
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>